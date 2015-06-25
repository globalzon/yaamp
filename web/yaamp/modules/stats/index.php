<?php

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$t1 = time() - 2*24*60*60;
$t2 = time() - 7*24*60*60;
$t3 = time() - 30*24*60*60;

$row1 = dborow("select avg(hashrate) as a, sum(earnings) as b from hashstats where time>$t1 and algo=:algo", array(':algo'=>$algo));
$row2 = dborow("select avg(hashrate) as a, sum(earnings) as b from hashstats where time>$t2 and algo=:algo", array(':algo'=>$algo));
$row3 = dborow("select avg(hashrate) as a, sum(earnings) as b from hashstats where time>$t3 and algo=:algo", array(':algo'=>$algo));

if($row1['a']>0 && $row2['a']>0 && $row3['a']>0)
{
	$btcmhday1 = bitcoinvaluetoa($row1['b'] / $row1['a'] * 1000000 / 2);
	$btcmhday2 = bitcoinvaluetoa($row2['b'] / $row2['a'] * 1000000 / 7);
	$btcmhday3 = bitcoinvaluetoa($row3['b'] / $row3['a'] * 1000000 / 30);
}
else
{
	$btcmhday1 = 0;
	$btcmhday2 = 0;
	$btcmhday3 = 0;
}

$hashrate1 = Itoa2($row1['a']);
$hashrate2 = Itoa2($row2['a']);
$hashrate3 = Itoa2($row3['a']);

$total1 = bitcoinvaluetoa($row1['b']);
$total2 = bitcoinvaluetoa($row2['b']);
$total3 = bitcoinvaluetoa($row3['b']);

$height = '240px';

$algos = yaamp_get_algos();

$string = '';
foreach($algos as $a)
{
	if($a == $algo)
		$string .= "<option value='$a' selected>$a</option>";
	else
		$string .= "<option value='$a'>$a</option>";
}

echo <<<end

<div id='resume_update_button' style='color: #444; background-color: #ffd; border: 1px solid #eea;
	padding: 10px; margin-left: 20px; margin-right: 20px; margin-top: 15px; cursor: pointer; display: none;'
	onclick='auto_page_resume();' align=center>
	<b>Auto refresh is paused - Click to resume</b></div>

<div align=right>
Select Algo: <select id='algo_select'>$string</select>&nbsp;
</div>

<script>

$('#algo_select').change(function(event)
{
	var algo = $('#algo_select').val();
	window.location.href = '/site/algo?algo='+algo;
});
	
</script>

<table width=100%><tr><td valign=top width=33%>

<div class="main-left-box">
<div class="main-left-title">Last 48 Hours</div>
<div class="main-left-inner">

<ul>
<li>Average Hashrate: <b>{$hashrate1}h/s</b></li>
<li>BTC Value: <b>$total1</b></li>
<li>BTC/Mh/d: <b>$btcmhday1</b></li>
</ul>

<br>
<div id='graph_results_1' style='height: $height;'></div><br><br>
<div id='graph_results_2' style='height: $height;'></div><br><br>
<div id='graph_results_3' style='height: $height;'></div><br><br>

</div></div><br>

</td>
<td></td>
<td valign=top width=33%>
		
<div class="main-left-box">
<div class="main-left-title">Last 7 Days</div>
<div class="main-left-inner">

<ul>
<li>Average Hashrate: <b>{$hashrate2}h/s</b></li>
<li>BTC Value: <b>$total2</b></li>
<li>BTC/Mh/d: <b>$btcmhday2</b></li>
</ul>

<br>
<div id='graph_results_4' style='height: $height;'></div><br><br>
<div id='graph_results_5' style='height: $height;'></div><br><br>
<div id='graph_results_6' style='height: $height;'></div><br><br>

</div></div><br>
		
</td>
<td></td>
<td valign=top width=33%>
		
<div class="main-left-box">
<div class="main-left-title">Last 60 Days</div>
<div class="main-left-inner">

<ul>
<li>Average Hashrate: <b>{$hashrate3}h/s</b></li>
<li>BTC Value: <b>$total3</b></li>
<li>BTC/Mh/d: <b>$btcmhday3</b></li>
</ul>

<br>
<div id='graph_results_7' style='height: $height;'></div><br><br>
<div id='graph_results_8' style='height: $height;'></div><br><br>
<div id='graph_results_9' style='height: $height;'></div><br><br>

</div></div><br>
		
</td></tr></table>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

function page_refresh()
{
	main_refresh_1();
	main_refresh_2();
	main_refresh_3();
	main_refresh_4();
	main_refresh_5();
	main_refresh_6();
	main_refresh_7();
	main_refresh_8();
	main_refresh_9();
}

end;

for($i = 1; $i < 10; $i++)
{
	echo <<<end
	///////////////////////////////////////////////////////////////////////
	
	function main_ready_$i(data)
	{
		graph_init_$i(data);
	}
	
	function main_refresh_$i()
	{
		var url = "/stats/graph_results_$i";
		$.get(url, '', main_ready_$i);
	}
end;
}

echo <<<end

function graph_init_1(data)
{
	$('#graph_results_1').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_1', [t],
	{
		title: '<b>Hashrate (Mh/s)</b>',
		axes: {
			xaxis: {
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_2(data)
{
	$('#graph_results_2').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_2', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_3(data)
{
	$('#graph_results_3').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_3', [t],
	{
		title: '<b>BTC/Mh/d</b>',
		axes: {
			xaxis: {
				tickInterval: 14400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%#Hh</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

//////////////////////////////////////////////////////////////////////////////////////////////

function graph_init_4(data)
{
	$('#graph_results_4').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_4', [t],
	{
		title: '<b>Hashrate (Mh/s)</b>',
		axes: {
			xaxis: {
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_5(data)
{
	$('#graph_results_5').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_5', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_6(data)
{
	$('#graph_results_6').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_6', [t],
	{
		title: '<b>BTC/Mh/d</b>',
		axes: {
			xaxis: {
				tickInterval: 86400,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

//////////////////////////////////////////////////////////////////////////////////////////////

function graph_init_7(data)
{
	$('#graph_results_7').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_7', [t],
	{
		title: '<b>Hashrate (Mh/s)</b>',
		axes: {
			xaxis: {
				tickInterval: 604800,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.3f</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_8(data)
{
	$('#graph_results_8').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_8', [t],
	{
		title: '<b>BTC/Day</b>',
		axes: {
			xaxis: {
				tickInterval: 604800,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}

function graph_init_9(data)
{
	$('#graph_results_9').empty();

	var t = $.parseJSON(data);
	var plot1 = $.jqplot('graph_results_9', [t],
	{
		title: '<b>BTC/Mh/d</b>',
		axes: {
			xaxis: {
				tickInterval: 604800,
				renderer: $.jqplot.DateAxisRenderer,
				tickOptions: {formatString: '<font size=1>%m/%d</font>'}
			},
			yaxis: {
				tickOptions: {formatString: '<font size=1>%#.8f &nbsp;</font>'}
			}
		},

		seriesDefaults:
		{
			renderer: $.jqplot.BarRenderer,
			rendererOptions: {barWidth: 3}
		},

		grid:
		{
			borderWidth: 1,
			shadowWidth: 2,
			shadowDepth: 2
		},

	});
}


</script>
end;


