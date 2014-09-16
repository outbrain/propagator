<?php
/**
 * This is the configuration file for the Propagator application.  All of your
 * environment specific settings will go here, and there should not be any need
 * edit other files.
 *
 * @author Shlomi Noach <snoach@outbrain.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2013-10-25
 *
 **/


$conf['db'] = array(
	'host'	=> '127.0.0.1',
	'port'	=> 5532,
	'db'	=> 'propagator_test',
	'ut_data_schema' => 'propagator_ut_data_schema',
	'user'	=> 'msandbox',
	'password' => 'msandbox'
);

$conf['default_action'] = 'splash';

/*
 * People with privileges to see sensitive data
 */
$conf['dbas'] = array('snoach', 'unknown');

$conf['instance_type_deployment'] = array(
		'production' 	=> 'manual',
		'build' 		=> 'automatic',
		'qa' 			=> 'automatic',
		'dev' 			=> 'automatic'
);
$conf['two_step_approval_environments'] = array(
		'production',
		'build'
);

$conf['history_visible_to_all'] = true;

/*
 * Change default event listeners dir for tests
 */
$conf['event_listener_dir'] = "./Tests/lib/listeners/";

/*
 * Event listeners to test with
 */
$conf['event_listeners'] = array(
    array(
        'event' => array('new_script', 'approve_script', 'comment_script'),
        'class' => 'DumpListener',
        'file'  => 'DumpListener.php',
    ),
    array(
        'event' => 'approve_script',
        'class' => 'DumpAndStopListener',
        'file'  => 'DumpAndStopListener.php'
    ),
    array(
        'event' => 'approve_script',
        'class' => 'DumpListener',
        'file'  => 'DumpListener.php'
    ),
    array(
        'event' => 'mark_script',
        'class' => 'DumpListener',
        'file'  => 'DumpListener.php',        
    ),
    array(
        'event' => 'redeploy_script',
        'class' => 'BogusListener',
        'file'  => 'BogusListener.php',
    ),
);


// dump like this:
// ./my sqldump propagator --no-data | egrep -v "[/][*]" | egrep -v "\-\-" | sed -r -e "s/AUTO_INCREMENT=([0-9])+ //g"
//
// Also:
// ./my sqldump propagator --no-data | egrep -v "[/][*]" | egrep -v "\-\-" | sed -r -e "s/AUTO_INCREMENT=([0-9])+ //g" | egrep -v "^DROP" | sed -r -e "s/CHARSET=latin1/CHARSET=ascii/g"

$conf['create_fixture_db'] = "

set foreign_key_checks := 0;


DROP TABLE IF EXISTS `database_instance`;
CREATE TABLE `database_instance` (
  `database_instance_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(128) CHARACTER SET ascii NOT NULL,
  `port` smallint(5) unsigned NOT NULL,
  `environment` enum('production','build','qa','dev') NOT NULL DEFAULT 'production',
  `description` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `is_active` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `is_guinea_pig` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`database_instance_id`),
  UNIQUE KEY `host_port_uidx` (`host`,`port`),
  KEY `type_idx` (`environment`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `database_instance_query_mapping`;
CREATE TABLE `database_instance_query_mapping` (
  `database_instance_query_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `database_instance_id` int(10) unsigned NOT NULL,
  `mapping_type` varchar(32) CHARACTER SET ascii DEFAULT NULL,
  `mapping_key` varchar(4096) DEFAULT NULL,
  `mapping_value` varchar(4096) NOT NULL,
  PRIMARY KEY (`database_instance_query_mapping_id`),
  KEY `instance_type_idx` (`database_instance_id`,`mapping_type`),
  CONSTRAINT `query_mapping_database_instance_id_fk` FOREIGN KEY (`database_instance_id`) REFERENCES `database_instance` (`database_instance_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `database_instance_role`;
CREATE TABLE `database_instance_role` (
  `database_instance_id` int(10) unsigned NOT NULL,
  `database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
  PRIMARY KEY (`database_instance_id`,`database_role_id`),
  KEY `role_idx` (`database_role_id`),
  CONSTRAINT `instance_role_database_instance_id_fk` FOREIGN KEY (`database_instance_id`) REFERENCES `database_instance` (`database_instance_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `instance_role_database_role_id_fk` FOREIGN KEY (`database_role_id`) REFERENCES `database_role` (`database_role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `database_instance_schema_mapping`;
CREATE TABLE `database_instance_schema_mapping` (
  `database_instance_schema_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `database_instance_id` int(10) unsigned NOT NULL,
  `from_schema` varchar(64) NOT NULL,
  `to_schema` varchar(64) NOT NULL,
  PRIMARY KEY (`database_instance_schema_mapping_id`),
  UNIQUE KEY `instance_from_to_schema_uidx` (`database_instance_id`,`from_schema`,`to_schema`),
  KEY `from_schema_idx` (`from_schema`),
  CONSTRAINT `schema_mapping_database_instance_id_fk` FOREIGN KEY (`database_instance_id`) REFERENCES `database_instance` (`database_instance_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `database_role`;
CREATE TABLE `database_role` (
  `database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `database_type` enum('mysql','hive') NOT NULL DEFAULT 'mysql',
  `description` varchar(1024) CHARACTER SET utf8 DEFAULT NULL,
  `is_default` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`database_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `database_role_query_mapping`;
CREATE TABLE `database_role_query_mapping` (
  `database_role_query_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `mapping_type` varchar(32) CHARACTER SET ascii DEFAULT NULL,
  `mapping_key` varchar(4096) DEFAULT NULL,
  `mapping_value` varchar(4096) NOT NULL,
  PRIMARY KEY (`database_role_query_mapping_id`),
  KEY `role_type_idx` (`database_role_id`,`mapping_type`),
  CONSTRAINT `query_mapping_database_role_id_fk` FOREIGN KEY (`database_role_id`) REFERENCES `database_role` (`database_role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `general_query_mapping`;
CREATE TABLE `general_query_mapping` (
  `general_query_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mapping_type` varchar(32) CHARACTER SET ascii DEFAULT NULL,
  `mapping_key` varchar(4096) DEFAULT NULL,
  `mapping_value` varchar(4096) NOT NULL,
  PRIMARY KEY (`general_query_mapping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `known_deploy_schema`;
CREATE TABLE `known_deploy_schema` (
  `known_deploy_schema_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `schema_name` varchar(64) NOT NULL,
  `is_default` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`known_deploy_schema_id`),
  KEY `schema_idx` (`schema_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `propagate_script`;
CREATE TABLE `propagate_script` (
  `propagate_script_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_by` varchar(32) DEFAULT NULL,
  `database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `default_schema` varchar(64) NOT NULL DEFAULT '',
  `sql_code` text,
  `description` varchar(4096) CHARACTER SET utf8 DEFAULT NULL,
  `checksum` char(32) CHARACTER SET ascii NOT NULL DEFAULT '',
  PRIMARY KEY (`propagate_script_id`),
  KEY `submitted_at_idx` (`submitted_at`),
  KEY `submitted_by_at_idx` (`submitted_by`,`submitted_at`),
  KEY `script_database_role_id_fk` (`database_role_id`),
  KEY `checksum_idx` (`checksum`),
  CONSTRAINT `script_database_role_id_fk` FOREIGN KEY (`database_role_id`) REFERENCES `database_role` (`database_role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `propagate_script_comment`;
CREATE TABLE `propagate_script_comment` (
  `propagate_script_comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `propagate_script_id` int(10) unsigned NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_by` varchar(32) DEFAULT NULL,
  `comment` varchar(8192) CHARACTER SET utf8 DEFAULT NULL,
  `comment_mark` enum('','ok','fixed','todo','cancelled') NOT NULL DEFAULT '',
  PRIMARY KEY (`propagate_script_comment_id`),
  KEY `propagate_script_submitted_at_idx` (`propagate_script_id`,`submitted_at`),
  KEY `submitted_by_at_idx` (`submitted_by`,`submitted_at`),
  CONSTRAINT `comment_propagate_script_id_fk` FOREIGN KEY (`propagate_script_id`) REFERENCES `propagate_script` (`propagate_script_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `propagate_script_deployment`;
CREATE TABLE `propagate_script_deployment` (
  `propagate_script_deployment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `propagate_script_id` int(10) unsigned NOT NULL,
  `deployment_environment` enum('production','test','dev') NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `submitted_by` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`propagate_script_deployment_id`),
  KEY `submitted_at_idx` (`submitted_at`),
  KEY `submitted_by_at_idx` (`submitted_by`,`submitted_at`),
  KEY `script_idx` (`propagate_script_id`),
  CONSTRAINT `deployment_propagate_script_id_fk` FOREIGN KEY (`propagate_script_id`) REFERENCES `propagate_script` (`propagate_script_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `propagate_script_instance_deployment`;
CREATE TABLE `propagate_script_instance_deployment` (
  `propagate_script_instance_deployment_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `propagate_script_id` int(10) unsigned NOT NULL,
  `propagate_script_deployment_id` int(10) unsigned NOT NULL,
  `database_instance_id` int(10) unsigned NOT NULL,
  `deploy_schema` varchar(64) CHARACTER SET utf8 NOT NULL,
  `is_approved` tinyint(3) unsigned NOT NULL DEFAULT '0',
  `deployment_type` enum('automatic','manual') NOT NULL,
  `deployment_status` enum('awaiting_approval','disapproved','not_started','awaiting_guinea_pig','deploying','failed','passed','deployed_manually','paused','awaiting_dba_approval') NOT NULL DEFAULT 'awaiting_approval',
  `current_propagate_script_query_id` int(10) unsigned DEFAULT NULL,
  `failed_propagate_script_query_id` int(10) unsigned DEFAULT NULL,
  `processing_start_time` timestamp NULL DEFAULT NULL,
  `processing_end_time` timestamp NULL DEFAULT NULL,
  `last_message` varchar(4096) DEFAULT NULL,
  `submitted_by` varchar(32) NOT NULL,
  PRIMARY KEY (`propagate_script_instance_deployment_id`),
  UNIQUE KEY `script_instance_schema_uidx` (`propagate_script_id`,`database_instance_id`,`deploy_schema`),
  KEY `instance_script_idx` (`database_instance_id`,`propagate_script_id`),
  KEY `instance_deployment_propagate_script_deployment_id_fk` (`propagate_script_deployment_id`),
  KEY `instance_deployment_current_propagate_script_query_id` (`current_propagate_script_query_id`),
  CONSTRAINT `instance_deployment_current_propagate_script_query_id` FOREIGN KEY (`current_propagate_script_query_id`) REFERENCES `propagate_script_query` (`propagate_script_query_id`),
  CONSTRAINT `instance_deployment_database_instance_id_fk` FOREIGN KEY (`database_instance_id`) REFERENCES `database_instance` (`database_instance_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `instance_deployment_propagate_script_deployment_id_fk` FOREIGN KEY (`propagate_script_deployment_id`) REFERENCES `propagate_script_deployment` (`propagate_script_deployment_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `instance_deployment_propagate_script_id_fk` FOREIGN KEY (`propagate_script_id`) REFERENCES `propagate_script` (`propagate_script_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS `propagate_script_query`;
CREATE TABLE `propagate_script_query` (
  `propagate_script_query_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `propagate_script_id` int(10) unsigned NOT NULL,
  `sql_code` text,
  PRIMARY KEY (`propagate_script_query_id`),
  KEY `propagate_script_idx` (`propagate_script_id`),
  CONSTRAINT `query_propagate_script_id_fk` FOREIGN KEY (`propagate_script_id`) REFERENCES `propagate_script` (`propagate_script_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

alter table propagate_script_instance_deployment modify deployment_status enum('awaiting_approval','disapproved','not_started','awaiting_guinea_pig','deploying','failed','passed','deployed_manually','paused', 'awaiting_dba_approval') NOT NULL DEFAULT 'awaiting_approval';
alter table propagate_script_instance_deployment 
  add column manual_approved tinyint unsigned default 0;
alter table database_role add column has_schema tinyint unsigned default 1;

CREATE TABLE `database_role_known_deploy_schema` (
  `database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
  `known_deploy_schema_id` int(10) unsigned NOT NULL,
  `is_default` tinyint(3) unsigned DEFAULT '0',
  PRIMARY KEY (`database_role_id`,`known_deploy_schema_id`),
  KEY `schema_idx` (`known_deploy_schema_id`),
  CONSTRAINT `role_schema_known_deploy_schema_id_fk` FOREIGN KEY (`known_deploy_schema_id`) REFERENCES `known_deploy_schema` (`known_deploy_schema_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `role_schema_database_role_id_fk` FOREIGN KEY (`database_role_id`) REFERENCES `database_role` (`database_role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
				
		
set foreign_key_checks := 1;
		
		";


$conf['populate_fixture_db'] = "
		
INSERT INTO `database_instance` 
		(`database_instance_id`, `host`, `port`, `environment`, `is_active`, `is_guinea_pig`) 
	VALUES 
		(1,'127.0.0.1',5532,'qa',1,1),
		(2,'127.0.0.1',5533,'dev',1,0),
		(3,'127.0.0.1',5534,'production',1,0),
		(4,'127.0.0.1',5535,'production',1,0),
		(5,'127.0.0.1',5536,'build',1,1),
		(6,'127.1',5532,'build',1,1)
		;
INSERT INTO `database_role` 
		(`database_role_id`, `database_type`, `description`) 
	VALUES 
		('olap','mysql','DWH server'),
		('oltp','mysql','main OLTP data backend'),
		('web','mysql','web interface backend')
	;
INSERT INTO `database_instance_role` 
	VALUES 
		(1,'olap'),(2,'olap'),(4,'olap'),(5,'olap'),(6,'olap'),
		(1,'oltp'),(2,'oltp'),(3,'oltp'),(5,'oltp'),(6,'oltp'),
		(1,'web'),(2,'web'),(3,'web'),(5,'web'),(6,'web')
	;
INSERT INTO `database_instance_schema_mapping` (database_instance_schema_mapping_id, database_instance_id, from_schema, to_schema) VALUES (1,2,'web_data','dev_web_data'), (2,1,'wildcard_schema','propagator_ut_data_schem%');
INSERT INTO `known_deploy_schema` (known_deploy_schema_id, schema_name) VALUES (3,'propagator_ut_data_schema'),(2,'test'),(1,'web_data');
INSERT INTO `database_role_query_mapping` (database_role_id, mapping_type, mapping_key, mapping_value) VALUES ('oltp', 'regex', '/^[\\s]*create[\\s]+trigger/i', 'CREATE DEFINER=\'root\'@\'localhost\' TRIGGER');
";



/**
 * end of configuration settings
 */
?>