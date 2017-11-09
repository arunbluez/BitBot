@extends('voyager::master')

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script>
      google.charts.load('current', {'packages':['corechart']});
      google.charts.setOnLoadCallback(drawChart);



  function drawChart() {
	var data2 = google.visualization.arrayToDataTable([
		@for($l=0;$l<100;$l++)
			['{{ $vtcdata[$l]['L'] }}', {{ $vtcdata[$l]['C'] }} ],
		@endfor
		], true);

	data2.addColumn('number', 'Time');
    data2.addColumn('number', 'BTC rate');

    var options = {
    };

	var chart2 = new google.visualization.LineChart(document.getElementById('chart_ema'));
	chart2.draw(data2, options);
	updateChart(data2, chart2, options);
	return(chart2);
  }

function updateChart(data, chart, options) {

	data = google.visualization.arrayToDataTable([
		@for($l=0;$l<100;$l++)
			['{{ $vtcdata[$l]['L'] }}', {{ $vtcdata[$l]['C'] }} ],
		@endfor
		], true);
	console.log("{{ $vtcdata[99]['L'] }}");
	var chart = new google.visualization.LineChart(document.getElementById('chart_ema'));
    chart.draw(data, options);
    
    setTimeout(function(){updateChart(data, chart, options)}, 3000);
}



  </script>


@section('content')

	<h1>TESTING CHARTS </h1>

	<div id="chart_ema" style="width: 800px; height: 600px;"></div>

	TOTAL PROFIT: {{ $portfolio['total'][0]['total'] }} 
	<table>
		<thead>
			<td>ID</td>
			<td>Units</td>
			<td>Buy rate</td>
			<td>Sell rate</td>
			<td>Profit</td>
			<td>Profit %</td>
		</thead>
			@for($j=0;$j<count($portfolio['orders']);$j++)
				<tr>
					<td>{{ $j }}</td>
					<td>{{ $portfolio['orders'][$j]['unit'] }}</td>
					<td>{{ $portfolio['orders'][$j]['buy'] }}</td>
					<td>{{ $portfolio['orders'][$j]['sell'] }}</td>
					<td>{{ $portfolio['orders'][$j]['profit'] }}</td>
					<td>{{ $portfolio['orders'][$j]['profitPercent'] }}</td>
				</tr>
			@endfor
	</table>

@endsection
