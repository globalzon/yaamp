<?php

function processUrlKeyword($meta, $keyname, $fieldname, $attribute,
		$maxsize=0, $force=true)
{
	if(!$force && !empty($attribute)) return $attribute;
	if($meta->getAttribute($keyname) != $fieldname) return $attribute;

	$value = $meta->getAttribute('value');
	if(empty($value))
	{
		$value = $meta->getAttribute('content');
		if(empty($value))
		{
			//	debuglog("* found $keyname, $fieldname, but failed to get value");
			return $attribute;
		}
	}

	$attribute = !$maxsize? $value: substr($value, 0, $maxsize);
	return $attribute;
}

function processUrl($url)
{
	libxml_use_internal_errors(true);
	
	$html = @file_get_contents_curl($url);
	if(!$html)
	{
		debuglog("* no response from $url");
		return null;
	}
	
	$encoding = mb_detect_encoding($html, 'UTF-8, ISO-8859-1', true);
	if($encoding == 'UTF-8')
		$html = mb_convert_encoding($html, 'HTML-ENTITIES', $encoding);
	
	$doc = new DOMDocument();
	$res = $doc->loadHTML($html);
	
	$info = array();
	
	$nodes = $doc->getElementsByTagName('title');
	if(!$nodes) return null;
	
	$info['title'] = substr($nodes->item(0)->nodeValue, 0, 256);
	
	$metas = $doc->getElementsByTagName('meta');
	for($i = 0; $i < $metas->length; $i++)
	{
		$meta = $metas->item($i);
		
		$info['overview'] = processUrlKeyword($meta, 'name', 'description', $info['overview'], 512, false);
		$info['overview'] = processUrlKeyword($meta, 'property', 'og:description', $info['overview'], 512);
		$info['overview'] = processUrlKeyword($meta, 'property', 'rnews:description', $info['overview'], 512);
		
		$info['image_url'] = processUrlKeyword($meta, 'name', 'image', $info['image_url'], 1024, false);
		$info['image_url'] = processUrlKeyword($meta, 'property', 'og:image', $info['image_url'], 1024);
		
		$info['site_name'] = processUrlKeyword($meta, 'name', 'site_name', $info['site_name'], 64, false);
		$info['site_name'] = processUrlKeyword($meta, 'property', 'og:site_name', $info['site_name'], 64);
		$info['site_name'] = processUrlKeyword($meta, 'name', 'application-name', $info['site_name'], 64, false);
		
		$info['type'] = processUrlKeyword($meta, 'name', 'type', $info['type'], 64, false);
		$info['type'] = processUrlKeyword($meta, 'property', 'og:type', $info['type'], 64);
	
		$info['image_url'] = processUrlKeyword($meta, 'name', 'twitter:image', $info['image_url'], 1024);
		$info['player'] = processUrlKeyword($meta, 'name', 'twitter:player', $info['player'], 1024);
		$info['player_width'] = processUrlKeyword($meta, 'name', 'twitter:player:width', $info['player_width']);
		$info['player_height'] = processUrlKeyword($meta, 'name', 'twitter:player:height', $info['player_height']);
	}
	
	return $info;
}




