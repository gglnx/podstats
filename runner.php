<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

// Startup application
$GLOBALS['application'] = include dirname(__FILE__) . '/application.php';

// Load resque runner
include VENDOR . 'chrisboulton/php-resque/resque.php';