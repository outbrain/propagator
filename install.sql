-- Uncomment the following for a fresh install:
-- DROP DATABASE IF EXISTS `propagator`;

CREATE DATABASE IF NOT EXISTS `propagator`;
USE `propagator`;

set foreign_key_checks := 0;

--
-- 
--

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


CREATE TABLE `database_instance_role` (
`database_instance_id` int(10) unsigned NOT NULL,
`database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
PRIMARY KEY (`database_instance_id`,`database_role_id`),
KEY `role_idx` (`database_role_id`),
CONSTRAINT `instance_role_database_instance_id_fk` FOREIGN KEY (`database_instance_id`) REFERENCES `database_instance` (`database_instance_id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
CONSTRAINT `instance_role_database_role_id_fk` FOREIGN KEY (`database_role_id`) REFERENCES `database_role` (`database_role_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


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


CREATE TABLE `database_role` (
`database_role_id` varchar(32) CHARACTER SET ascii NOT NULL,
`database_type` enum('mysql','hive') NOT NULL DEFAULT 'mysql',
`description` varchar(1024) CHARACTER SET utf8 DEFAULT NULL,
`is_default` tinyint(3) unsigned DEFAULT '0',
PRIMARY KEY (`database_role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


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


CREATE TABLE `general_query_mapping` (
`general_query_mapping_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`mapping_type` varchar(32) CHARACTER SET ascii DEFAULT NULL,
`mapping_key` varchar(4096) DEFAULT NULL,
`mapping_value` varchar(4096) NOT NULL,
PRIMARY KEY (`general_query_mapping_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


CREATE TABLE `known_deploy_schema` (
`known_deploy_schema_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`schema_name` varchar(64) NOT NULL,
`is_default` tinyint(3) unsigned DEFAULT '0',
PRIMARY KEY (`known_deploy_schema_id`),
KEY `schema_idx` (`schema_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


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


CREATE TABLE `propagate_script_query` (
`propagate_script_query_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
`propagate_script_id` int(10) unsigned NOT NULL,
`sql_code` text,
PRIMARY KEY (`propagate_script_query_id`),
KEY `propagate_script_idx` (`propagate_script_id`),
CONSTRAINT `query_propagate_script_id_fk` FOREIGN KEY (`propagate_script_id`) REFERENCES `propagate_script` (`propagate_script_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
