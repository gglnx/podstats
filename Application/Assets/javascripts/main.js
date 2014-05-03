/**
 * @package     Podstats
 * @link        http://podstats.org/
 * @author      Dennis Morhardt <info@dennismorhardt.de>
 * @copyright   Copyright 2014, Dennis Morhardt
 * @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */

/**
 * RequireJS configuration
 */
requirejs.config({
	// Initialize the application with the main application file
	deps: ['main'],

	paths: {
		'jquery': '../components/jquery/dist/jquery.min',
		'moment': '../components/moment/min/moment-with-langs.min',
		'Raphael': '../components/raphael/raphael-min',
		'morris': '../components/morris.js/morris.min'
	},

	shim: {
		'morris': ['Raphael', 'jquery']
	},

	// Prevent caching issues, by adding an additional URL argument
	urlArgs: 'bust=' + (new Date()).getDate()
});

/**
 * Main
 */
define([
	'components/download-clients',
	'components/download-timeline'
], function() {});
