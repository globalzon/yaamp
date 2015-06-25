<?php

$symbol = getparam('symbol');
$string = "<option value='all'>-all-</option>";

$list = getdbolist('db_coins', "enable and id in (select distinct coinid from accounts where balance>0.0001)");
foreach($list as $coin)
{
	if($coin->symbol == $symbol)
		$string .= "<option value='$coin->symbol' selected>$coin->symbol</option>";
	else
		$string .= "<option value='$coin->symbol'>$coin->symbol</option>";
}

echo <<<end

<a href='/site/common'>Summary</a>&nbsp;
<a href='/site/admin'>Coins</a>&nbsp;
<a href='/site/exchange'>Exchange</a>&nbsp;
<a href='/site/user'>Users</a>&nbsp;
<a href='/site/worker'>Workers</a>&nbsp;
<a href='/site/version'>Version</a>&nbsp;
<a href='/site/earning'>Earnings</a>&nbsp;
<a href='/site/payments'>Payments</a>&nbsp;
<a href='/site/monsters'>Big Miners</a>&nbsp;
<a href='/site/emptymarkets'>EmptyMarket</a>&nbsp;

<div>
Select Algo: <select id='coin_select'>$string</select>&nbsp;
</div>

<div id='main_results'></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

$(function()
{
	$('#coin_select').change(function(event)
	{
		var symbol = $('#coin_select').val();
		window.location.href = '/site/user?symbol='+symbol;
	});
		
	main_refresh();
});

var main_delay=30000;
var main_timeout;

function main_ready(data)
{
	$('#main_results').html(data);
	main_timeout = setTimeout(main_refresh, main_delay);
}

function main_error()
{
	main_timeout = setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var symbol = $('#coin_select').val();
	var url = "/site/user_results?symbol="+symbol;

	clearTimeout(main_timeout);
	$.get(url, '', main_ready).error(main_error);
}

</script>

end;



