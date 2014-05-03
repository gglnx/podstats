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
namespace Application\Controllers;

/**
 *
 */
class Pages extends MasterController {
	public function indexAction() {
		// Display homepage
	}
	
	public function renderAction() {
		// Check if template exists
		if ( false == file_exists( $template = 'pages/' . $this->attr('page') ) )
			return $this->render404();

		// Use custom template
		$this->useTemplate($template);
	}
}
