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
