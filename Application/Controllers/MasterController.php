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
 * The BaseController is base for all controllers this app
 */
class MasterController extends \Nautik\Controller {
	/**
	 * Translate timeframe URL parameter into DateTime and human
	 * readable format
	 */
	public function timeframe() {
		// Strings
		$strings = [
			'singluar' => ['h' => 'Eine Stunde', 'd' => 'Ein Tag', 'm' => 'Ein Monat', 'y' => 'Ein Jahr'],
			'plural' => ['h' => '%d Stunden', 'd' => '%d Tage', 'm' => '%d Monate', 'y' => '%d Jahre'],
			'today' => 'Heute',
			'yesterday' => 'Gestern'
		];

		// Get timeframe from URL
		$timeframe_attr = $this->attr("timeframe");

		// Timeframe
		$timeframe = (object) array("raw" => $timeframe_attr);

		// Time since now filter
		if ( preg_match( '^(\d+)((hour|day|month|year)(s|))^i', $timeframe_attr, $matched ) ):
			// Query filter
			$timeframe->query = array(
				'$gt' => new \MongoDate((new \DateTime("-{$timeframe_attr}"))->getTimestamp())
			);
			
			// Human readable string
			$number = abs(intval($matched[1]));
			$mode = ( 1 == $number ) ? 'singluar' : 'plural';
			$timeframe->human = sprintf($strings[$mode][$matched[2]{0}], $number);

			// Render labels as hours, days or months
			if ( "hour" == $matched[3] || ( "day" == $matched[3] && 2 >= $matched[1] ) )
				$timeframe->label = 'H:00';
			elseif ( "day" == $matched[3] || ( "month" == $matched[3] && 1 == $matched[1] ) )
				$timeframe->label = 'd.m.';
			else
				$timeframe->label = 'm.Y';

			// Display one day as 24 hours, two days as 48 hours
			if ( "day" == $matched[3] && 2 >= $matched[1] )
				$matched = [( $matched[1] * 24 ) . 'hours', $matched[1] * 24, 'hours', 'hour'];
			// Display one month as 30 days
			elseif ( "month" == $matched[3] && 1 == $matched[1] )
				$matched = ['30days', 30, 'days', 'day'];

			// Generate data sample
			$timeframe->dataset[] = new \DateTime("now");
			for ( $i = 1; $i < $matched[1]; $i++ ):
				$datapoint = clone $timeframe->dataset[$i-1];
				$timeframe->dataset[] = $datapoint->modify("-1" . $matched[3]);
			endfor;
		// Yesterday / Today (default)
		else:
			// Start and end value
			$start = ( 'yesterday' == $timeframe_attr ) ? 'yesterday' : 'today';
			$end = ( 'yesterday' == $timeframe_attr ) ? 'today' : 'tomorrow';

			// Query filter
			$timeframe->query = array(
				'$gte' => new \MongoDate((new \DateTime($start))->getTimestamp()),
				'$lt' => new \MongoDate((new \DateTime($end))->getTimestamp())
			);

			// Human readable string
			$timeframe->human = $strings[$timeframe_attr];

			// Render labels as hours
			$timeframe->label = 'H:00';

			// Generate data sample
			$timeframe->dataset[] = new \DateTime("{$end} -1hour");
			for ( $i = 1; $i < 24; $i++ ):
				$datapoint = clone $timeframe->dataset[$i-1];
				$timeframe->dataset[] = $datapoint->modify("-1hour");
			endfor;
		endif;

		// Reserve dateset
		$timeframe->dataset = array_reverse($timeframe->dataset);

		return $timeframe;
	}
}
