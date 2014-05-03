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
namespace Application\Repository;

/**
 * UserAgents
 */
class UserAgents {
	/**
	 *
	 */
	public static function byPodcast($podcast, $timeframe) {
		// Build final query
		$query = self::prepareQuery(['podcast' => $podcast, 'downloaded_at' => $timeframe]);
	
		// Get grouped user agents for podcast and timeframe
		$user_agents = \Mongoium\Connection::getCollection('$cmd')->findOne($query);
		
		// Format data
		$user_agents = self::formatData($user_agents['results'], $user_agents['counts']['input']);
		
		// Return formed user agents
		return $user_agents;
	}
	
	/**
	 *
	 */
	public static function byEpisode($podcast, $episode, $timeframe) {
		// Build final query
		$query = self::prepareQuery(['podcast' => $podcast, 'episode' => $episode, 'downloaded_at' => $timeframe]);
	
		// Get grouped user agents for podcast and timeframe
		$user_agents = \Mongoium\Connection::getCollection('$cmd')->findOne($query);
		
		// Format data
		$user_agents = self::formatData($user_agents['results'], $user_agents['counts']['input']);
		
		// Return formed user agents
		return $user_agents;
	}
	
	/**
	 *
	 */
	private static function formatData($results, $count) {
		// Format data
		return array_map(function($client) use($count) {
			// Set label
			$client['label'] = $client['_id'];
			
			// Calculate percents
			$client['raw_value'] = $client['value'];
			$client['value'] = number_format(( $client['value'] / $count ) * 100, 2, ',', '.');
			
			return $client;
		}, $results);
	}
	
	/**
	 *
	 */
	private static function prepareQuery($query) {
		// Get user agents
		$user_agents = \Symfony\Component\Yaml\Yaml::parse(APP . 'Config/user-agents.yml');
	
		// Build map function
		$map = 'function() {';
		foreach ( $user_agents as $user_agent ):
			$map.= ' if ( this.user_agent.match(' . $user_agent['regex'] . ') ) {';
			$map.= 'emit("' . $user_agent['label'] . '", 1); } else';
		endforeach;
		$map.= ' { emit("Sonstige", 1); } }';
		
		// Build final query
		return array(
			"mapreduce" => "downloads",
			"map" => new \MongoCode($map),
			"reduce" => new \MongoCode("function(k, vals) { var sum = 0; for (var i in vals) { sum += vals[i]; } return sum; }"),
			"query" => $query,
			"out" => ['inline' => 1]
		);
	}
}
