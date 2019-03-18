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
use Application\Documents\Podcast;

/**
 * Controller for an API endpoint for debugging purposes
 *
 * @author Dennis Morhardt <info@dennismorhardt.de>
 */
class Debug extends MasterController {
	/**
	 *
	 */
	public function indexAction() {
		// Get podcast
		$podcast = Podcast::query()
			->is( 'slug', 'anycast' )
			->findOne();

		return $this->renderText( $this->hateoas->serialize( $podcast, 'json' ) );
	}
}
