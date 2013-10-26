<?php
/**
 * @package     Podstats
 * @version     1.0
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2013, Dennis Morhardt
 */

// Paths
define('APP', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Application' . DIRECTORY_SEPARATOR);
define('VENDOR', dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR);

// Load composer autoloader
$composer = include VENDOR . 'autoload.php';
$composer->set("Application", array(dirname(__FILE__)));

// Start application
\Application\Application::run();
