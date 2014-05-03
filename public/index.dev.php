<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

// Correct QUERY_STRING and _GET on local development with Grunt
if ( "/" == $_SERVER["QUERY_STRING"]{0} && ( $__pos = strpos( $_SERVER["QUERY_STRING"], '?' ) ) ):
	$_SERVER["REQUEST_URI"] = substr($_SERVER["QUERY_STRING"], 0, $__pos);
	$_SERVER["QUERY_STRING"] = substr($_SERVER["QUERY_STRING"], $__pos + 1);
	$_SERVER["SERVER_PORT"] = '9000';
	parse_str($_SERVER["QUERY_STRING"], $_GET);
else:
	$_GET = array();
	$_SERVER["REQUEST_URI"] = $_SERVER["QUERY_STRING"];
	$_SERVER["QUERY_STRING"] = '';
	$_SERVER["SERVER_PORT"] = '9000';
endif;

// Action happens a level higher
include '../index.php';
