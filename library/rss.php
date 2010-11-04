<?php

/**
 * RSS converts RSS feeds to HTML and can also make a feed.
 * 
 * @author  Alex Bentley
 * @history	3.0 rewrote most of the code
 *          2.4	removed dependence on ABOUT
 *			2.3	update documentation and strings
 *			2.2	updated transform
 *			1.0	initial release
 */
class RSS {

function XMLopn() { return '<?xml version="1.0"'; }
function XMLcls() { return '?'.'>'; }

/**
 * Parse an XML stream into an HTML entity using XSLT.
 *
 * @param	$xmlsource	the XML string to transform.
 * @param	$xslsource	the XSLT string to use for the transformation.
 * @return	HTML representing the XML content.
 */
function XMLtransform($xmlsource, $xslsource) {
	// Load the XML source
	$xml = new DOMDocument;

	// get xml source
	if (file_exists($xmlsource) || str_begins($xmlsource, 'http:')) {
		$xmlsource = str_replace('&amp;', '&', $xmlsource);
		$xmlsource = file_get_contents($xmlsource);
	}

	// attempt to parse
	$er = error_reporting(E_ERROR);
	$xml->loadXML($xmlsource);

	if ($xml->saveXML() == self::XMLopn().self::XMLcls()."\n") {
		// insert encoding and try again
		if (str_begins($xmlsource, self::XMLopn())) $xmlsource = self::XMLopn().'  encoding="ISO-8859-1" '.self::XMLcls().substr($xmlsource, strlen(self::XMLopn()));
		$xml->loadXML($xmlsource);
	}
	error_reporting($er);

	$xsl = new DOMDocument;
	if (file_exists($xslsource)) {
		$xsl->load($xslsource);
	} else {
		$xsl->loadXML($xslsource);
	}

	// Configure the transformer
	$proc = new XSLTProcessor;
	$proc->importStyleSheet($xsl); // attach the xsl rules
	
	$contents = $proc->transformToXML($xml);
	return $contents;
}

/**
 * Obtain and parse an RSS feed.
 *
 * @param	$label		the name for this feed.
 * @param	$xml		the XML string.
 * @param	$filename	the cache file.
 * @param	$format		{ dl | summary }.
 * @return	HTML representing the feed content.
 * @see		readCache
 * @see		XMLtrasform
 */
function feed($label, $xml, $filename='', $format='dl') { 
	if ($filename != '') {
		$xml = self::readCache($filename, $xml);
	}

	if ($format == 'dl') {
		$title = 'News from '; 
		$xsl = 'xml/rssdetail.xsl';
	} else {
		$title = '';
		$xsl = 'xml/rsssummary.xsl';
	}

	$result = h1('', $title.$label);

	$result2 = self::XMLtransform($xml, $xsl);
	$result2 = str_replace(array('&lt;', '&gt;'), array('<', '>'), $result2);
	$result2 = str_replace(array('target="_blank"', 'target="_BLANK"'), 'rel="external"', $result2); // correct non W3C standard
	$result2 = str_replace(array('<a href=', '</A>', '<br>',  'IMG SRC', 'ALT='),
						   array('<a href=', '</a>', '<br/>', 'img src', 'alt='), $result2); // correct non W3C standard
	$result .= $result2;
	
	if ($format != 'dl') { // limit the output to the first 10.
		$starttext = '<a';
		$endtext   = '</a>';
		$startlink = strpos($result, $starttext);
		$endlink   = strpos($result, $endtext);
		$links = '';
		for ($i = 1; $i < 10; $i++) {
			$link = substr($result, $startlink, $endlink-$startlink+4);
			if (str_contains($link, 'ADV:')) {
				$i--;
			} else {
				$links .= $link;
			}
			$startlink = strpos($result, $starttext, $endlink+1);
			if ($startlink === false) break; // no more links
			
			$endlink = strpos($result, $endtext, $startlink+1);
		}
		
		$result = div('class:header');
		if ($format == 'm') $result .= LINK::local('news', 'more news...', 'class:toplink | return');
		$result .= h1('', $title.$label);
        $result .= div('/');
        $result .= $links;
	}
	
	return $result;
}

/**
 * Update RSS feed cache.
 *
 * @param	$filename	the cache file.
 * @param	$newdata	new feed data.
 * @see		readCache
 * @see		XMLtrasform
 */
function writeCache($filename, $newdata) {
	FILE::write($filename, $newdata);
}

/**
 * Read RSS feed cache.
 *
 * @param	$filename	the cache file.
 * @param	$url		feed url.
 * @return	the feed data
 * @see		writeCache
 */
function readCache($filename, $url) {
	//error_reporting(0);
	$contents = '';
	
	$filename = "xml/$filename.xml";
	
	if (file_exists($filename)) {
		$modified = 1800 + filemtime($filename); // every 30 minutes
		if (time() > $modified) {
			touch($filename); // make reasonably sure only this request is updating the file
			$error = '';
			$contents = file_get_contents($url) or $error = 'x';
				
			if ($error == '') self::writeCache($filename, $contents);
		} else {
			$contents = file_get_contents($filename);
		}
	}
	return $contents;
}

/**
 * Make an RSS feed.
 *
 * @param	$title			the title for this feed.
 * @param	$description	the RSS description.
 * @param	$file_directiory	the directory where attachments are kept.
 * @param	$weburl			the feed url.
 * @param	$content		the array of items.
 * @param	$itunes			the itunes tags.
 * @param	$namespace		any namespace items needed.
 * @return	the feed data.
 */
function makefeed($title, $description, $file_directory, $weburl, $content, $itunes='', $namespace='') {
	$RSSdateformat = 'r';  // Sat, 10 Mar 2005 15:16:08 MST
    $rtn = "\n";
    
	$rss = self::XMLopn().' encoding="utf-8" '.self::XMLcls(); // initialize result
	append($rss, "<rss version='2.0' $namespace>", $rtn);
    append($rss, '<channel>', $rtn); 
	$link = str_replace('&', '&amp;', 'http://'.$_SERVER['HTTP_HOST']);
	
	$today = date($RSSdateformat);
    $rssdata = array(
        'title'          => $title,
        'description'    => $description,
        'language'       => 'en-us',
        'pubDate'        => $today,
        'lastBuildDate'  => $today,
        'generator'      => 'RSS 1.1.2',
        'managingEditor' => get('webmaster'),
        'webMaster'      => get('webmaster'),
    );
    foreach ($rssdata as $tag => $value) append($rss, tag($tag, '', $value), $rtn);
    append($rssdata, '<link>'.$link.'</link>', $rtn);
	append($rss, $itunes, '');

	if (is_array($content)) {		
		foreach($content as $key => $value) {
			if ($value['dev'] == 0) {
				if (array_key_exists('rss-attachment', $value)) {
					$filename = $file_directory.$value['rss-attachment'];
					
					$item = tag('item', '');
					append($item, '<title>'.str_replace('&rsquo;', '&apos;', $value['title']).'</title>', $rtn);
					append($item, '<link>'.$weburl.$key.'</link>', $rtn);
					append($item, tag('guid',        '', $weburl.$key), $rtn);
					append($item, tag('description', '', str_replace('&rsquo;', '&apos;', $value['rss-description'])), $rtn);
					append($item, tag('pubDate',     '', date ($RSSdateformat, strtotime($value['publish_date']))), $rtn);
										
					$type = GALLERY::mimetype($filename);
					if ($type != '') {
                        append($item, tag('enclosure', 'url:'.str_replace(' ', '%20', 'http://'.$_SERVER['HTTP_HOST'].'/'.$filename).' | length:'.filesize($filename).' | type:'.$type), $rtn);
					}
					
					append($item, tag('item', '/'), $rtn);
                    append($rss, $item, $rtn);
                }
			}
		}
	} else if ($content != '') {
		$result .= $content;
	} else {
		return '';
	}

	append($rss, '	</channel>', $rtn);
    append($rss, '</rss>', $rtn);

	while(@ob_end_clean());	// remove any prior buffers
	
	header('Content-type: application/rss+xml');
	echo $rss;
	
	exit();
}

}

?>