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
use \Mongoium\Query as Query;
use \Mongoium\Connection as Connection;
use \Mongoium\Document as Document;

/**
 *
 */
class Podcast extends MasterController {
	public function indexAction() {
		// Check if user is logged in
		if ( false == $this->sentry->check() ):
			return $this->redirect( $this->path( 'login', array(
				'origin' => $this->path( 'podcast_index', array(
					'podcast' => $this->attr( 'podcast' ),
					'timeframe' => $this->attr( 'timeframe' )
				) )
			) ) );
		endif;

		// Timeframe
		$this->timeframe = $this->timeframe();

		// Try to find the requested podcast
		$podcast = Query::init("podcasts")->is("slug", $this->attr("podcast"))->findOne();

		// Get unformated download counts
		$data = Connection::getCollection("downloads")->aggregate(array(
			array('$match' => array('podcast' => $this->attr("podcast"))),
			array('$project' => array('episode' => 1)),
			array('$group' => array('_id' => '$episode', 'downloads' => array('$sum' => 1))),
			array('$sort' => array('_id' => -1))
		));

		// Check for errors
		if ( 0 == $data["ok"] )
			throw new Exception("Fehler: " . $data["errmsg"] . " (Code: " . $data["code"] . ")");

		// Format data
		$episodes = array();
		foreach ( $data["result"] as $episode )
			$episodes[$episode["_id"]] = $episode["downloads"];

		return array(
			"podcast" => $podcast,
			"episodes" => $episodes
		);
	}
}
