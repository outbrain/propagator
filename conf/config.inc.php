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
	'db'	=> 'propagator',
	'user'	=> 'msandbox',
	'password' => 'msandbox'
);

$conf['default_action'] = 'about';

// Default user. Propagator is assumed to work with ldap, so credentials are passed to PHP via htaccess.
// The only alternative to ldap at this stage is to simply auto-assign a login
$conf['default_login'] = 'gromit';

//
// Accounts with DBA privileges: mark deployments as "manually deployed", restart deployments, view topologies
$conf['dbas'] = array('gromit', 'penguin');
$conf['blocked'] = array('badboy');

// By default production deployments are 'manual', such that the user has to explicitly click the "reload" button
// so as to deploy. Change to 'automatic' in you have great faith
$conf['instance_type_deployment'] = array(
		'production' 	=> 'manual',
		'build' 		=> 'automatic',
		'qa' 			=> 'automatic',
		'dev' 			=> 'automatic'
);

//
// Should script deployment history be visible to all users? If 'false' then only to 'dbas' group (see above);
$conf['history_visible_to_all'] = true;

// patterns to highlight on topology view. Patterns are matched against host names.
// Each matching pattern gets its own color via css. Search propagator.css for span.palette-*
$conf['instance_topology_pattern_colorify'] = array (
	"/-1[0-9]{4}-/",
	"/-2[0-9]{4}-/",
	"/-3[0-9]{4}-/",
	"/-4[0-9]{4}-/",
	"/localhost/"
);


// Choose how propagator gets credentials to deployment servers (MySQL, Hive, ...)
// If empty/undefined, then user is prompted to enter credentials. These must apply on any server the user wishes
// to deploy to (though the user is allowed to resubmit credentials and execute on particular servers as she pleases)
//
// Otherwise, provide path to passwords file. This file will list host credentials in plaintext, so file's
// permissions/ACL should be as strict as possible, ideally only readable by the apache user (or whichever user is running
// the PHP code).
// Make sure you understand the impact of having plaintext credentials!
// Even if credentials file is provided, the user is allowed to override by submiting his own credentials.
//include "propagator-hosts.conf.php";


/**
 * end of configuration settings
 */
?>