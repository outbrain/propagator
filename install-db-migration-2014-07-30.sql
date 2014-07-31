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
) ENGINE=InnoDB DEFAULT CHARSET=utf8
;

