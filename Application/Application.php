<?php
/**
 * @package     Podstats
 * @version     1.0
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2013, Dennis Morhardt
 */

/**
 * Namespace
 */
namespace Application;

/**
 * Application main class
 */
class Application extends \Nautik\Nautik {
	/**
	 * With this configuration variable you can enable the debug mode
	 * of the application. All errors will be displayed.
	 */
	public static $debug = true;

	/**
	 * Default timezone of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/timezones
	 */
	public static $defaultTimezone = "Europe/Berlin";
	
	/**
	 * Locale of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/setlocale
	 */
	public static $locale = array('de_DE.UTF-8', 'de_DE@euro', 'de_DE', 'deu_deu');

	/**
	 * Default route
	 * Used to catch not found routes and display an 404 error page
	 */
	public static $defaultRoute = ['_controller' => 'Errors', '_action' => '404'];
	
	/**
	 * Configure database connection
	 */
	public static function preApplicationStart() {
		\Mongoium\Connection::init("192.168.30.8", "podcasts");
	}
}
