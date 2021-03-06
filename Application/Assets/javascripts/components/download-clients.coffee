##
# @package     Podstats
# @link        http://podstats.org/
# @author      Dennis Morhardt <info@dennismorhardt.de>
# @copyright   Copyright 2014, Dennis Morhardt
# @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
##

# Download clients
define ['jquery', 'moment', 'Raphael', 'morris'], ($, moment, Raphael) -> $ ()->
	$('[data-type="download-clients"]').each (index, el)->
		# Setup moment
		moment.lang 'de'

		# 1. Get data from API
		$.ajax
			url: $(el).data 'source'
			dataType: 'json'

		# 2. Generate graph
		.done (response)->
			# Check if response is ok and we got data
			if response.ok == false or response.data? == false
				return

			# Remove loading class
			$(el).removeClass 'graph-loading'

			# No data? Show message
			if response.data.length == 0
				$(el).addClass 'no-data'
				$(el).parent().addClass 'no-data'
				$(el).append 'Keine Daten für diesen Zeitraum vorhanden.'
				return

			# Init morris
			new Morris.Donut
				element: $(el).attr 'id'
				data: response.data
				resize: true
				formatter: (y)-> return y + '%'
