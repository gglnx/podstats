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
use \Mongoium\Query as Query;
use \Mongoium\Connection as Connection;
use \Mongoium\Document as Document;

/**
 *
 */
class Episode extends MasterController {
	/**
	 * 
	 */
	public function indexAction() {
		// Timeframe
		$this->timeframe = $this->timeframe();

		// Try to find the requested podcast
		$episode = $this->attr("episode");
		$podcast = Query::init("podcasts")->is("slug", $this->attr("podcast"))->findOne();

		// Get downloads for last 30 days
		$data = Connection::getCollection("downloads")->aggregate(array(
			array('$match' => array('episode' => $episode, 'downloaded_at' => $this->timeframe->query, 'podcast' => $this->attr("podcast"))),
			array('$project' => array('day' => array('$dayOfYear' => '$downloaded_at'), 'year' => array('$year' => '$downloaded_at'))),
			array('$group' => array('_id' => '$day', 'year' => array('$addToSet' => '$year'), 'downloads' => array('$sum' => 1))),
			array('$sort' => array('_id' => 1))
		));
	
		// Check for errors
		if ( 0 == $data["ok"] )
			throw new \Exception("Fehler: " . $data["errmsg"] . " (Code: " . $data["code"] . ")");

		// Format data
		$days = array();
		foreach ( $data["result"] as $day ):
			$date = \DateTime::createFromFormat('z Y', strval($day['_id']-1) . ' ' . strval($day['year'][0]));
			$days[] = (object) array('date' => $date->format("Y-m-d"), 'downloads' => $day["downloads"]);
		endforeach;

		// Return to view
		return array(
			"podcast" => $podcast,
			"episode" => $episode,
			"downloads" => json_encode($days)
		);
	}
}
