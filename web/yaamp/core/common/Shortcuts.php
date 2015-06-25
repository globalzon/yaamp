<?php

function app()
{
	return Yii::app();
}

function cs()
{
	return Yii::app()->clientScript;
}

function url($route,$params=array(),$ampersand='&')
{
	return Yii::app()->createUrl($route,$params,$ampersand);
}

///////////////////////////////////////////////////////////////////////

function l($text, $url = '#', $htmlOptions = array())
{
	return CHtml::link($text, $url, $htmlOptions);
}

function img($text, $url = '#', $htmlOptions = array())
{
	return CHtml::image($text, $url, $htmlOptions);
}

function t($message, $category = 'stay', $params = array(), $source = null, $language = null)
{
	return Yii::t($category, $message, $params, $source, $language);
}

function bu($url=null)
{
	static $baseUrl;
	if ($baseUrl===null)
	$baseUrl=Yii::app()->request->baseUrl;
	return $url===null ? $baseUrl : $baseUrl.'/'.ltrim($url,'/');
}

function tf($url = null)
{
	error_log("tf($url) called");
	return '';
}

function user()
{
	return Yii::app()->user;
}

function now()
{
	return date("Y-m-d H:i:s");
}

/////////////////////////////////////////////////////////////

function JavascriptFile($filename)
{
	echo CHtml::scriptFile($filename);
}

function Javascript($javascript)
{
	echo "<script>$javascript</script>";
}

function JavascriptReady($javascript)
{
	echo "<script>$(function(){ $javascript})</script>";
}



