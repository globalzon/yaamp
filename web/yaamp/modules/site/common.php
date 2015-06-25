<?php

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");

//<a href='/site/memcached'>Memcache</a>&nbsp;
//<a href='/site/eval'>Eval</a>&nbsp;
//<a href='/renting/admin'>Jobs</a>&nbsp;
?>

<a href='/site/common'>Summary</a>&nbsp;
<a href='/site/admin'>Coins</a>&nbsp;
<a href='/site/exchange'>Exchange</a>&nbsp;
<a href='/site/user?symbol=BTC'>Users</a>&nbsp;
<a href='/site/worker'>Workers</a>&nbsp;
<a href='/site/version'>Version</a>&nbsp;
<a href='/site/earning'>Earnings</a>&nbsp;
<a href='/site/payments'>Payments</a>&nbsp;
<a href='/site/monsters'>Monitor</a>&nbsp;
<a href='/site/connections'>Conns</a>&nbsp;
<a href='/site/emptymarkets'>EmptyMarket</a>&nbsp;
<a href='/coin'>New</a>&nbsp;
<a href='/renting/admin'>Rental</a>&nbsp;

<div id='main_results'></div>

<br><a href='/site/create'><img width=16 src=''><b>CREATE COIN</b></a>
<br><a href='/site/updateprice'><img width=16 src=''><b>UPDATE PRICE</b></a>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

$(function()
{
	main_refresh();
});

var main_delay=30000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);

	main_refresh_assets();
	main_refresh_negative();
//	main_refresh_profit();
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = "/site/common_results";

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

///////////////////////////////////////////////////////////////////////

function main_ready_assets(data)
{
	graph_init_assets(data);
}

function main_refresh_assets()
{
	var url = "/site/graph_assets_results";
	$.get(url, '', main_ready_assets);
}

function graph_init_assets(data)
{
	$('#graph_results_assets').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_assets', t,
	{
	//	title: '<b></b>',
		stackSeries: true,

		seriesDefaults:
		{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
			//	tickOptions: {showLabel: false}
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size=1>%#.3f &nbsp;</font>'}
			}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},

	});
}

///////////////////////////////////////////////////////////////////////

function main_ready_negative(data)
{
	graph_init_negative(data);
}

function main_refresh_negative()
{
	var url = "/site/graph_negative_results";
	$.get(url, '', main_ready_negative);
}

function graph_init_negative(data)
{
	$('#graph_results_negative').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_negative', t,
	{
	//	title: '<b></b>',
		stackSeries: true,

		seriesDefaults:
		{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		axes: {
			xaxis: {
				tickInterval: 7200,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				min: 0,
				tickOptions: {formatString: '<font size=1>%#.3f &nbsp;</font>'}
			}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 0,
			shadowDepth: 0,
			background: '#ffffff'
		},

	});
}

///////////////////////////////////////////////////////////////////////

// function main_ready_profit(data)
// {
// 	graph_init_profit(data);
// }

// function main_refresh_profit()
// {
// 	var url = "/site/graph_profit_results";
// 	$.get(url, '', main_ready_profit);
// }

// function graph_init_profit(data)
// {
// 	$('#graph_results_profit').empty();

// 	var t = $.parseJSON(data);
// 	var plot1 = $.jqplot('graph_results_profit', t,
// 	{
// 	//	title: '<b></b>',
// 		stackSeries: true,

// 		seriesDefaults:
// 		{
// 			renderer:$.jqplot.BarRenderer,
// 			rendererOptions: {barWidth: 3}
// 		},

// 		axes: {
// 			xaxis: {
// 				tickInterval: 7200,
// 				renderer: $.jqplot.DateAxisRenderer,
// 				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
// 			},
// 			yaxis: {
// 				min: 0,
// 				tickOptions: {formatString: '<font size=1>%#.3f &nbsp;</font>'}
// 			}
// 		},

// 		grid:
// 		{
// 			borderWidth: 1,
// 			shadowWidth: 0,
// 			shadowDepth: 0,
// 			background: '#ffffff'
// 		},

// 	});
// }


</script>



