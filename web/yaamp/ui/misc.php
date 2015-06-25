<?php

function showFlashMessage()
{
	if(user()->hasFlash('message'))
	{
		echo "<div style='color: green;'><br>";
		echo user()->getFlash('message');
		echo "</div>";
	}

	if(user()->hasFlash('error'))
	{
		echo "<div style='color: red;'><br>";
		echo user()->getFlash('error');
		echo "</div>";
	}
}

function showPageContent($content)
{
	echo "<div class='content-out'>";
	
	if(controller()->id=='renting')
		echo "<div class='content-inner' style='background: url(/images/beta_corner_banner2.png) top right no-repeat; '>";
	else
		echo "<div class='content-inner'>";
	
	showFlashMessage();
	echo $content;

//	echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
//	echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
	
	echo "</div>";
	echo "</div>";
}




