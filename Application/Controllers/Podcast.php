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
class Podcast extends MasterController {
	public function indexAction() {
		// Try to find the requested podcast
		$podcast = Query::init("podcasts")->is("slug", $this->attr("podcast"))->findOne();
		
		// Get downloads for last 30 days
		$data = Connection::getCollection("downloads")->aggregate(array(
			array('$match' => array('downloaded_at' => array('$gte' => new \MongoDate(time() - (60*60*24*30))), 'podcast' => $this->attr("podcast"))),
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
			$date = '"' . $date->format("d.m.") . '"';
			$days[$date] = $day["downloads"];
		endforeach;
		
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
			"episodes" => $episodes,
			"last30days" => implode(", ", array_values($days)),
			"last30days_labels" => implode(", ", array_keys($days))
		);
	}
}
