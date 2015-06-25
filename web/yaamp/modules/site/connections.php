<?php

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

<div id='main_results'></div>

<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>
<br><br><br><br><br><br><br><br><br><br>

<script>

var main_delay = 30000;

$(function()
{
	main_refresh();
});

function main_ready(data)
{
	$('#main_results').html(data);
	setTimeout(main_refresh, main_delay);
}

function main_error()
{
	setTimeout(main_refresh, main_delay*2);
}

function main_refresh()
{
	var url = "/site/connections_results";
	$.get(url, '', main_ready).error(main_error);
}

</script>

end;






