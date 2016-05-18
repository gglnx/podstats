<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

// Paths
define('APP', dirname(__FILE__) . '/Application/');
define('VENDOR', dirname(__FILE__) . '/vendor/');

// Load composer autoloader
$composer = include VENDOR . 'autoload.php';
$composer->set("Application", array(dirname(__FILE__)));

// Exception handler
$whoops = new \Whoops\Run;
$whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
$whoops->register();

// Load environment variables, setup required variables
Dotenv::load(__DIR__);
Dotenv::required(['MONGO_HOST', 'MONGO_DATABASE']);

// Create application
$application = new \Application\Application;
$application->debug = ( getenv('DEBUG') && 1 == getenv('DEBUG') ) ? true : false;

return $application;
