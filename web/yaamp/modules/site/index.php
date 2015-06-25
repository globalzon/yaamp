<?php 

$algo = user()->getState('yaamp-algo');

JavascriptFile("/extensions/jqplot/jquery.jqplot.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.dateAxisRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.barRenderer.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.highlighter.js");
JavascriptFile("/extensions/jqplot/plugins/jqplot.cursor.js");
JavascriptFile('/yaamp/ui/js/auto_refresh.js');

$height = '240px';

echo <<<END

<div id='resume_update_button' style='color: #444; background-color: #ffd; border: 1px solid #eea;
	padding: 10px; margin-left: 20px; margin-right: 20px; margin-top: 15px; cursor: pointer; display: none;'
	onclick='auto_page_resume();' align=center>
	<b>Auto refresh is paused - Click to resume</b></div>

<table cellspacing=20 width=100%>
<tr><td valign=top width=50%>

<!--  -->

<div class="main-left-box">
<div class="main-left-title">YET ANOTHER ANONYMOUS MINING POOL</div>
<div class="main-left-inner">

<ul>

<li>YAAMP is multipool multialgo with auto exchange to Bitcoin or any coin we mine.</li>
<li>We distribute hashpower in real time among the best coins.</li>
<li>No registration required.</li>
<li>Just plug in your stratum miner using your bitcoin wallet address as the username.</li> 
<li>We also allow payouts in any currency we mine. Use your wallet address as the username.</li>
<li>Payouts are made automatically twice a day for all balances above <b>0.001</b> or <b>0.0001</b> on Sunday.</li>
<li>Blocks are distributed proportionally among valid submitted shares.</li>
<li>We also rent hashpower you can use on third party pools.</li>

<br>

</ul>
</div></div><br>

<!--  -->

<div class="main-left-box">
<div class="main-left-title">STRATUM SERVERS</div>
<div class="main-left-inner">

<ul>

<li>
<p class="main-left-box" style='padding: 3px; font-size: .8em; background-color: #ffffee; font-family: monospace;'>
	-o stratum+tcp://yaamp.com:PORT -u WALLET_ADDRESS -p xx</p>
</li>

<li>WALLET_ADDRESS can be of any currency we mine or a BTC address.</li>
<li>Use "-p c=SYMBOL" if yaamp does not recognize the currency correctly.</li>
<li>See the "Pool Status" area in the top right corner for PORT numbers.</li>

<br>

</ul>
</div></div><br>

<!--  -->

<div class="main-left-box">
<div class="main-left-title">LINKS</div>
<div class="main-left-inner">

<ul>

<li><b>BitcoinTalk</b> - <a href='https://bitcointalk.org/index.php?topic=508786.0' target=_blank >https://bitcointalk.org/index.php?topic=508786.0</a></li>
<!--li><b>IRC</b> - <a href='http://webchat.freenode.net/?channels=#yaamp' target=_blank >http://webchat.freenode.net/?channels=#yaamp</a></li-->
		
<li><b>API</b> - <a href='/site/api'>http://yaamp.com/site/api</a></li>
<li><b>Difficulty</b> - <a href='/site/diff'>http://yaamp.com/site/diff</a></li>
<li><b>Algo Switching</b> - <a href='/site/multialgo'>http://yaamp.com/site/multialgo</a></li>
	
<br>

</ul>
</div></div><br>

<!--  -->

<a class="twitter-timeline" href="https://twitter.com/YAAMP1" data-widget-id="434887348677918721">Tweets by @YAAMP1</a>
<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>

</td><td valign=top>

<!--  -->

<div id='pool_current_results'>
<br><br><br><br><br><br><br><br><br><br>
</div>

<div id='pool_history_results'>
<br><br><br><br><br><br><br><br><br><br>
</div>

</td></tr></table>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

function page_refresh()
{
	pool_current_refresh();
	pool_history_refresh();
}

function select_algo(algo)
{
	window.location.href = '/site/algo?algo='+algo;
}

////////////////////////////////////////////////////

function pool_current_ready(data)
{
	$('#pool_current_results').html(data);
}

function pool_current_refresh()
{
	var url = "/site/current_results";
	$.get(url, '', pool_current_ready);
}

////////////////////////////////////////////////////

function pool_history_ready(data)
{
	$('#pool_history_results').html(data);
}

function pool_history_refresh()
{
	var url = "/site/history_results";
	$.get(url, '', pool_history_ready);
}

</script>

END;

