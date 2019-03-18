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
namespace Application\Controllers\Api;

/**
 * Dependencies
 */
use \Nautik\Nautik;
use \Hateoas\HateoasBuilder;
use \Hateoas\Hateoas;

/**
 * The base controller for all controllers in this package,
 * extends the application MasterController
 *
 * @author Dennis Morhardt <info@dennismorhardt.de>
 */
class MasterController extends \Application\Controllers\MasterController {
	/**
	 * @var Hateoas $hateoas Builded instance of Hateoas
	 */
	protected $hateoas;

	/**
	 * Constructor
	 *
	 * Gets the injected Nautik application and initializes Hateoas
	 *
	 * @param Nautik $application Nautik application instance
	 */
	public function __construct( Nautik $application ) {
		// Inject application
		parent::__construct( $application );

		// Create instance of Hateoas
		$this->hateoas = HateoasBuilder::create()
			// Use applications url generator
			->setUrlGenerator(
				null,
				new \Hateoas\UrlGenerator\SymfonyUrlGenerator( $application->routing->getGenerator() )
			)

			// Cache directory
			->setCacheDir( APP . 'Cache' . DIRECTORY_SEPARATOR . 'api' )

			// Debug mode
			->setDebug( $application->debug )

			// Build
			->build();
	}
}
