<?php
/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
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
	 * Default timezone of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/timezones
	 */
	public $defaultTimezone = "Europe/Berlin";

	/**
	 * Locale of the application
	 * See the php.net docs for configuration options
	 *
	 * @see http://www.php.net/setlocale
	 */
	public $locale = array('de_DE.UTF-8', 'de_DE@euro', 'de_DE', 'deu_deu');

	/**
	 * Default route
	 * Used to catch not found routes and display an 404 error page
	 */
	public $defaultRoute = ['_controller' => 'Errors', '_action' => '404'];

	/**
	 * Configure database connection
	 */
	public function preApplicationStart() {
		// Connect to database
		\Mongoium\Connection::init(getenv('MONGO_HOST'), getenv('MONGO_DATABASE'));

		// Add twig dump function
		$this->templateRender->addExtension(new \Twig_Extension_Debug());
	}
}
