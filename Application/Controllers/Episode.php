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

		// Aggregate by day or hour?
		$id = ['year' => '$year', 'month' => '$month', 'day' => '$day'];
		$format = 'Y-m-d';
		if ( 'hour' == $this->timeframe->matched[3] ):
			$id = ['year' => '$year', 'month' => '$month', 'day' => '$day', 'hour' => '$hour'];
			$format = 'Y-m-d H:00';
		endif;

		// Get downloads for last 30 days
		$data = Connection::getCollection("downloads")->aggregate(array(
			// Build query
			array('$match' => array(
				'downloaded_at' => $this->timeframe->query,
				'podcast' => $this->attr("podcast"),
				'episode' => $episode
			)),

			// Build project
			array('$project' => array(
				'hour' => ['$hour' => '$downloaded_at'],
				'month' => ['$month' => '$downloaded_at'],
				'day' => ['$dayOfYear' => '$downloaded_at'],
				'year' => ['$year' => '$downloaded_at']
			)),

			// Group data
			array('$group' => array(
				'_id' => $id,
				'downloads' => array('$sum' => 1)
			)),

			// Sort data
			array('$sort' => array('_id' => 1))
		));

		// Check for errors
		if ( 0 == $data["ok"] )
			throw new \Exception("Fehler: " . $data["errmsg"] . " (Code: " . $data["code"] . ")");

		// Format data
		$dataPoints = array();
		foreach ( $data["result"] as $day ):
			// Create date object
			$date = \DateTime::createFromFormat('z Y', strval($day['_id']['day']-1) . ' ' . strval($day['_id']['year']));

			// Set hour
			if ( isset( $day['_id']['hour'] ) )
				$date->setTime($day['_id']['hour'], 0);

			// Add data point
			$dataPoints[] = (object) array('date' => $date->format($format), 'downloads' => $day["downloads"]);
		endforeach;

		// Return to view
		return array(
			"podcast" => $podcast,
			"episode" => $episode,
			"downloads" => json_encode($dataPoints)
		);
	}
}
