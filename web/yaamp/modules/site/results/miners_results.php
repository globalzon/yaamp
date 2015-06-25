<?php

function WriteBoxHeader($title)
{
	echo "<div class='main-left-box'>";
	echo "<div class='main-left-title'>$title</div>";
	echo "<div class='main-left-inner'>";
}

$algo = user()->getState('yaamp-algo');

$target = yaamp_hashrate_constant($algo);
$interval = yaamp_hashrate_step();
$delay = time()-$interval;

$total_workers = getdbocount('db_workers', "algo=:algo", array(':algo'=>$algo));
$total_extranonce = getdbocount('db_workers', "algo=:algo and subscribe", array(':algo'=>$algo));
$total_hashrate = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and algo=:algo", array(':algo'=>$algo));
$total_invalid = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and algo=:algo", array(':algo'=>$algo));

WriteBoxHeader("Miners Version ($algo)");

//echo "<br><table class='dataGrid2'>";
showTableSorter('maintable2');
echo "<thead>";
echo "<tr>";
echo "<th>Version</th>";
echo "<th align=right>Count</th>";
echo "<th align=right>Extranonce</th>";
echo "<th align=right>Percent</th>";
echo "<th align=right>Hashrate*</th>";
echo "<th align=right>Reject</th>";
echo "</tr>";
echo "</thead><tbody>";

$error_tab = array(
	20=>'Invalid nonce size',
	21=>'Invalid job id',
	22=>'Duplicate share',
	23=>'Invalid time rolling',
	24=>'Invalid extranonce2 size',
	25=>'Invalid share',
	26=>'Low difficulty share',
);

$versions = dbolist("select version, count(*) as c, sum(subscribe) as s from workers where algo=:algo group by version order by c desc", array(':algo'=>$algo));
foreach($versions as $item)
{
	$version = $item['version'];
	$count = $item['c'];
	$extranonce = $item['s'];
	
	$hashrate = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where valid and time>$delay and 
		workerid in (select id from workers where algo=:algo and version='$version')", array(':algo'=>$algo));

	$invalid = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where not valid and time>$delay and 
		workerid in (select id from workers where algo=:algo and version='$version')", array(':algo'=>$algo));

	$title = '';
 	foreach($error_tab as $i=>$s)
 	{
 		$invalid2 = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where error=$i and time>$delay and
 			workerid in (select id from workers where algo=:algo and version='$version')", array(':algo'=>$algo));
		
		if($invalid2)
		{
			$bad2 = round($invalid2*100/($hashrate+$invalid2), 2).'%';
			$title .= "$bad2 - $s\n";
		}
 	}
	
	$percent = $total_hashrate&&$hashrate? round($hashrate * 100 / $total_hashrate, 2).'%': '';
	$bad = ($hashrate+$invalid)? round($invalid*100/($hashrate+$invalid), 1).'%': '';
	$hashrate = $hashrate? Itoa2($hashrate).'h/s': '';
	$version = substr($version, 0, 30);
	
	echo "<tr class='ssrow'>";
	echo "<td><b>$version</b></td>";
	echo "<td align=right>$count</td>";
	echo "<td align=right>$extranonce</td>";
	echo "<td align=right>$percent</td>";
	echo "<td align=right>$hashrate</td>";
	echo "<td align=right title='$title'>$bad</td>";
	echo "</tr>";
}

echo "</tbody>";

$title = '';
foreach($error_tab as $i=>$s)
{
	$invalid2 = dboscalar("select sum(difficulty) * $target / $interval / 1000 from shares where error=$i and time>$delay and
		workerid in (select id from workers where algo=:algo)", array(':algo'=>$algo));

	if($invalid2)
	{
		$bad2 = round($invalid2*100/($total_hashrate+$invalid2), 2).'%';
		$title .= "$bad2 - $s\n";
	}
}

$bad = ($total_hashrate+$total_invalid)? round($total_invalid*100/($total_hashrate+$total_invalid), 1).'%': '';
$total_hashrate = Itoa2($total_hashrate).'h/s';

echo "<tr class='ssrow'>";
echo "<td><b>Total</b></td>";
echo "<td align=right>$total_workers</td>";
echo "<td align=right>$total_extranonce</td>";
echo "<td align=right></td>";
echo "<td align=right>$total_hashrate</td>";
echo "<td align=right title='$title'>$bad</td>";
echo "</tr>";

echo "</table>";

echo "<p style='font-size: .8em'>
		&nbsp;* approximate from the last 5 minutes submitted shares<br>
		</p>";

echo "<br></div></div><br>";






