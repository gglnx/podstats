<?php
/**
 * @package     Podstats
 * @version     1.0
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2013, Dennis Morhardt
 */
 
// Paths
define('APP', dirname(__FILE__) . '/application/');
define('VENDOR', dirname(__FILE__) . '/vendor/');

// Load composer autoloader
include VENDOR . 'autoload.php';

// Load application
include APP . 'Application.php';

// Start application
\App\Application::run();