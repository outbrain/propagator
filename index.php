<?php
/**
 * This is the main loader and controller init script for the Propagator project
 * It just loads the config, creates a controller and invokes it. See
 * lib/Propagator.php for the main project code.
 * 
 * @author Shlomi Noach <snoach@outbrain.com>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2013-10-25
 * 
 **/

/**
 * This file and others in this project are forked from Box Anemometer, 
 * see https://github.com/box/Anemometer
 * Box Anemometer authors and license are:
 * 
 *   @author Gavin Towey <gavin@box.com> and Geoff Anderson <geoff@box.com>
 *   @license Apache 2.0 license.  See LICENSE document for more info
 * 
 **/

set_include_path( get_include_path() . PATH_SEPARATOR . "./lib");
require "Helpers.php";
require "Propagator.php";

error_reporting(E_ALL);
$action = (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) ? $_REQUEST['action'] : 'index';

$conf = array();
include "conf/config.inc.php";
if (empty($conf))
{
	$action = 'noconfig';
}

$controller = new Propagator($conf);

if(!$controller->get_auth_user()) {
	print "Unauthorized access";
    throw new Exception("Unauthorized access");
}

if (is_callable(array($controller, $action)))
{
	$controller->$action();
}
else
{
	print "Invalid action ($action)";
}

?>
