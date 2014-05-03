##
# @package     Podstats
# @link        http://podstats.org/
# @author      Dennis Morhardt <info@dennismorhardt.de>
# @copyright   Copyright 2014, Dennis Morhardt
# @license     BSD-3-Clause, http://opensource.org/licenses/BSD-3-Clause
##

# Download timeline
define ['jquery', 'moment', 'morris'], ($, moment) -> $ ()->
	$('[data-type="download-timeline"]').each (index, el)->
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

			# Init morris
			new Morris.Area
				element: $(el).attr 'id'
				data: response.data
				gridTextFamily: '"PT Sans", Helmet, Freesans, sans-serif'
				xkey: 'date'
				fillOpacity: 0.4
				resize: true
				xLabels: $(el).data 'xlabel'
				hideHover: true
				hoverCallback: (index, options, content)->
					row = options.data[index]
					'<div class="morris-hover-row-label">' + moment(row.date).format('DD.MM.YYYY') + '</div><div class="morris-hover-point" style="color: #0b62a4">Downloads: ' + row.downloads + '</div>'
				xLabelFormat: (date)->
					moment(date).format $(el).data 'xlabel-format'
				ykeys: ['downloads']
				labels: ['Downloads']
