<?php

/**
 * HTML generates valid HTML/xHTML for a variety of elements..
 * 
 * @author  Alex Bentley
 * @history	8.0		major rewrite of tag functions
 *			7.0     fixed title function to return correct title.
 *          6.8     change setPageTitle and pageTitle to title (performs two functions now)
 *          6.7		move CSS generation inside makePage function
 *			6.6     minor changes to allow for loading from outside the webroot
 *          6.5		added new function javascript to provide custom page configurations
 *			6.4		process INIT setting alliowing all references to be absolute and not relative.
 *			6.3		fix to allow setPageTitle to function in the newer page building method
 *			6.2		new options for tags for passing attributes
 *			6.1		new function plainpage
 *			6.0		wild update to deal with sidebars in a brand new way.
 *			5.4		addition of back, setBack, forward, and setForward functions
 *			5.3		fix for subdirectory
 *			5.2		minor fixes to table function
 *			5.1		fixed RSS link creation
 *			5.0		added new jsattributes function
 *			4.18	fix to embedMedia added new selfclosing function and fix to special case empty values in openTag attributes
 *			4.17	new functions openTag, closeTag, and tag
 *			4.16	code cleanup
 *			4.15	allow CSS to be served from base PHP
 *			4.14	update to pagetitle to allow for more patterns
 *			4.13	fixed a bug when no page title was defined
 *			4.12	removed dependence on ABOUT class
 *			4.11	addition of isMobile function
 *			4.10	slight change to page titles
 *			4.9		additions to deamp to manage lt and gt entities
 *			4.8		change to deamp to include oacute and ntilde
 *			4.7		provide custom settings based on version
 *			4.6		include new MOBILE XHTML doctype and provide means to pass it in makepage
 *			4.5		remove extraneous parameter in send function
 *			4.4		new function shapedBox outputs callout type divs
 *			4.3		new functions set supports lists, table outputs tables
 *			4.2		minor fix to links
 *			4.1		add ability to add additional link entries, changed processing of existing ones
 *			4.0		major rewrite of page creation code
 *			3.5		updated site processing
 *			1.0		initial release
 */

class HTML {
    
/**
 * Embed an HTML style comment for the text with an optional title.
 *
 * @param  $content	The comment content.
 * @param  $title	The optional title of the comment.
 */
function comment() {
	alias('comment');
}

/**
 * Replaces embedded quotes with the HTML equivalent. 
 *
 * @param  $string	the string to process the quotes in.
 * @return			the modified string.
 */
function quotes($string) {
	return str_replace(array("'", '"'), array('&#34;', '&#39;'), $string);
}

/**
 * Embed a block of content inside a set of pre tags to insure the formatting of the string is retained. 
 *
 * @param  $thing	the string to embed.
 * @param  $title	an optional title.
 */
function show($thing, $title='') {
	if ($title != '') echo br().h3('', $title);
	echo pre('', print_r($thing, true)).br();
}

/**
 * Finds a site specific file if there is one or a non specific file
 *
 * @param  $filename	the name of the file being located.
 * @param  $base		the directory to look in.
 * @param  $default		the string to return in the event the file is not found.
 * @return	the relative path to the located file.
 * @see		SITE::file
 */
function siteFile($filename, $base='', $default='') {
	return SITE::file($filename, $base, $default);	
}

/**
 * Returns the valid form for the specified doctype and version.
 *
 * @param  $type		the name of the type (HTML and XHTML).
 * @param  $version		the version for the type specified.
 * @return	the doctype tag.
 */
function doctype($type, $version) {
	$doctype['HTML'] ['TRANSITIONAL'] = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	$doctype['HTML'] ['STRICT']       = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"              "http://www.w3.org/TR/html4/strict.dtd">';
	$doctype['HTML'] ['FRAMESET']     = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN"     "http://www.w3.org/TR/html4/frameset.dtd">';
	$doctype['XHTML']['STRICT']       = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
	$doctype['XHTML']['TRANSITIONAL'] = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
	$doctype['XHTML']['FRAMESET']     = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
	$doctype['XHTML']['MOBILE']       = '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" "http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
	$doctype['HTML5']['STRICT']       = '<!DOCTYPE html>';
	
	$result = "<?xml version=\"1.0\" ?>\n".$doctype[$type][$version]."\n";
	$result .= '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'."\n";
	return $result;
}

/**
 * Change the page title.
 *
 * @param  $title	the new title to use.
 */
function setPageTitle($title) {
	return page('title', $title);
}

function pageTitle($title) {
	return page('title', $title);
}

/**
 * Sets or gets the page title for the current page. 
 * Can use the first <h#> tag or 
 * the first HTML comment contained in the page or 
 * what is in 'page-title' in INIT.
 *
 * @param	$title      if true then return the title, else set the title to the passed value.
 * @param   $page       if empty then use the current page, otherwise use the passed page.
 * @return	the string title for this page if getting or nothing if setting.
 */
function title($title=true, $page='') {
	return page('title', $title, $page);
}

/**
 * Constructs the meta tag section of the HTML page based on configured values.
 *
 * @param  $version		the name of the configured section to use.
 * @return	the string containing the meta tags for this page.
 * @see		title
 * @see		page
 */
function metatags($version) {
	return SITE::metatags($version);
}

/**
 * Constructs the head tag of the HTML page based on configured values.
 *
 * @param  $version		the name of the configured section to use.
 * @return	the string containing the head tag for this page.
 * @see		metatags
 * @see		page
 */
function head($version) {
	return SITE::head($version);
}

/**
 * Converts pt size font sizes to relative sizes
 *
 * @param  $fs		the pt size to convert.
 * @return	the string containing the relative font size.
 */
function pt2rel($fs) {
	switch ($fs) {
		case '18pt': return 'xx-large';
		case '17pt': return 'xx-large';
		case '16pt': return 'x-large';
		case '15pt': return 'x-large';
		case '14pt': return 'large';
		case '13pt': return 'large';
		case '12pt': return 'medium';
		case '11pt': return 'small';
		case '10pt': return 'small';
		case  '9pt': return 'small';
		case  '8pt': return 'x-small';
		case  '7pt': return 'xx-small';
		default:
			$fsn = $fs + 0; // convert $fs to numeric
			if ($fsn > 18) return 'xx-large';
			if ($fsn <  7) return 'xx-small';
	}
}

/**
 * Converts pt size font sizes to pixel sizes
 *
 * @param  $fs		the pt size to convert.
 * @return	the string containing the pixel font size.
 */
function pt2px($fs) {
	switch ($fs) {
		case '18pt': return '24px';
		case '17pt': return '23px';
		case '16pt': return '22px';
		case '15pt': return '21px';
		case '14pt': return '19px';
		case '13pt': return '18px';
		case '12pt': return '16px';
		case '11pt': return '14px';
		case '10pt': return '13px';
		case  '9pt': return '12px';
		case  '8pt': return '11px';
		case  '7pt': return '9px';
		default:
			$fsn = $fs + 0; // convert $fs to numeric
			if ($fsn > 18) return '24px';
			if ($fsn <  7) return '9px';
	}
}

/**
 * Executes a GET or POST request against a webserver and returns the content obtained.
 *
 * @param  $host		the name of the server host to send the request to.
 * @param  $path		the path (URL) for the request.
 * @param  $data		the query string part of the request.
 * @param  $method		the method ot use for the request, 'GET' or 'POST'.
 * @return	the string containing the content returned for the request.
 */
function send ($host, $path, $data, $method='POST', $mime='application/x-www-form-urlencoded') {
	$method = strtoupper($method);
	
	@list($server, $port) = explode(':', $host);
	if ($port == '') $port = 80;
	
	$fp = fsockopen($server, $port);
	
	if ($method == 'GET') $path .= '?'.$data;	
	fputs($fp, "$method /$path HTTP/1.1\r\n");
	fputs($fp, "Host: $host\r\n");
	fputs($fp, "Content-type: $mime\r\n");
	fputs($fp, "Content-length: " . strlen($data) . "\r\n");    
	fputs($fp, "Connection: close\r\n\r\n");
	if ($method == 'POST') fputs($fp, $data);
	
	
	$buf = '';
	while (!feof($fp)) $buf .= fgets($fp, 128);
	fclose($fp);
	return $buf;
}

/**
 * Fixes overly HTML encoded strings.
 *
 * @param  $text		the text to process.
 * @return	the corrected string.
 */
function deamp($text) {
	$text = preg_replace('/&amp;(ldquo|lsquo|rdquo|rsquo|oacute|ntilde|gt|lt);/', '&$1;', $text);
	$text = str_replace(array('&lt;', '&gt;'), array('<', '>'), $text);
	return $text;
}

/**
 * Fixes under HTML encoded strings.
 *
 * @param  $text		the text to process.
 * @return	the corrected string.
 */
function fixAMPs($text) {
	if (str_contains($text, '&')) {
		$text = str_replace('&', '&amp;', $text);
		$text = str_replace('&amp;amp;', '&amp;', $text);
	}
	return $text;
}
/**
 * Display the contents of an array as a table.
 *
 * @param  $array	the array to display.
 * @param  $options	a keyed array that can override the defaults.
 *					'class' => 'basictable', 'titles' => false, 'skip-numeric' => false
 * @return	a string containing an HTML table of the content of the array.
 * @see smart_merge
 */
function table($array, $options=array()) {
	$defaults = array(
					  'class' => 'basictable',
					  'style' => '',
					  'id' => '',
					  'js' => '',
					  'titles' => false,
					  'skip-numeric' => false,
					  );
	
	$options = smart_merge($defaults, $options);
	
	@list($jsaction, $js) = @explode('=', $options['js']);
	$js = substr($js, 1, strlen($js)-2);
	
	$result = table(array('id' => $options['id'], 'class' => $options['class'], 'style' => $options['style'], $jsaction => $js));
	
	foreach ($array as $label => $row) {
		$result .= tr('');
		
		$attrs = $options['titles'];
		if (!is_numeric($label)) $result .= td($attrs, $label);
		
		if (is_array($row)) foreach ($row as $key => $item) {
			if ($options['skip-numeric'] && is_numeric($key)) {
			} else {
				$result .= self::tag('td', $attrs, $item);
			}
		}
		$result .= tr('/');
	}
	$result .= table('/');
	
	return $result;
}

/**
 * Display the contents of an array as any of an ordered list, an unordered list, a dictionary list, or a table.
 *
 * @param  $array	the array to display.
 * @param  $options	a keyed array that can override the defaults.
 *					'type' => 'ul', 'class' => 'basictable', 'titles' => false, 'skip-numeric' => false
 * @return	a string containing an HTML element of the content of the array.
 * @see smart_merge
 * @see table
 */
function set($array, $options=array()) {
	$options = smart_merge(array('type' => 'ul'), $options);
	$type = $options['type'];
	
	switch ($type) {
		case 'ul':
		case 'ol':
			$ntag = '';
			$itag = 'li';
			break;
			
		case 'dl':
			$ntag = 'dt';
			$itag = 'dd';
			break;
			
		case 'table':
			return self::table($array, $options);
			break;
			
		default:
			$ntag = '';
			$itag = '';
			break;
	}
	
	$result = tag($type, array('id' => $options['id'], 'class' => $options['class'], 'style' => $options['style'], $jsaction => $js));
	
	foreach ($array as $name => $value) {
		if (is_numeric($name)) $name = ''; // don't display numeric keys
		if (is_array($value)) {
			$suboptions = smart_merge($options, array('type' => $type));
			$value = self::set($value, $suboptions);
		}
		if ($ntag != '') {
			$result .= tag($ntag, '', $name);
		} else {
			$value = "$name $value";
		}
		$result .= tag($itag, '', $value);
	}
	$result .= tag($type, '/');
	
	return $result;
}

/**
 * Display the content inside a 'shaped' box.
 *
 * @param  $content	the content to display.
 * @param  $options	a keyed array that can override the defaults.
 *					'class' => 'callout', 'content' => 'boxcontent', 'layers' => 4
 * @return	a string containing an HTML element enclosing the content.
 * @see smart_merge
 */
function shapedBox($content, $options=array()) {
	$defaults = array(
					  'class' => 'callout',
					  'content' => 'boxcontent',
					  'layers' => 4,
					  );
	
	$options = smart_merge($defaults, $options);
	
	$result = div(array('id' => $options['id'], 'class' => $options['class'], 'style' => $options['style'], $jsaction => $js));
	
	for ($i = 1; $i <= $options['layers']; $i++) $result .= tag('b', array('class' => "b{$i}"),'&nbsp;');
	
	$result .= div(array('class' => $options['content']), $content);
	
	for ($i = $options['layers']; $i >= 1; $i--) $result .= tag('b', array('class' => "b{$i}b"), '&nbsp;');
	
	$result .= div('/');
	
	return $result;
}

/**
 * Construct an opening tag with the optional values.
 *
 * @param  $tag		the name of the tag to construct.
 * @param  $options	a keyed array of entries to include, or a list in the form 'attr:value | attr: value'.
 * @return	a string containing an HTML element enclosing the content.
 */
function openTag($tag, $attributes=array()) {
	return tag($tag, $attributes);
}

/**
 * Construct an closing tag.
 *
 * @param  $tag		the name of the tag to construct.
 * @return	a string containing an HTML element.
 */
function closeTag($tag) {
	return tag($tag, '/');
}

/**
 * Construct a complete HTML tag with the attributes in a standardized manner.
 *
 *	br, hr, img, meta, link, input, and HTML tags are self-closing.
 *
 * @param  $tag			the name of the tag to construct.
 * @param  $attributes	a keyed array of attributes to include, or a list in the form 'attr:value | attr: value'.
 * @param  $contents	optional content for the tag.
 * @return	a string containing an HTML element enclosing the content.
 */
function tag($tag, $attributes=array(), $contents=null) {	
	return tag($tag, $attributes, $contents);
}

/**
 * Construct an HTML container for a media file in a valid form.
 *
 * @param  $filename	the name of the file to contain.
 * @param  $width		width of the container.
 * @param  $height		height of the container.
 * @return	a string containing an HTML element enclosing the media.
 */
function embedMedia($filename, $width, $height, $options=array()) {
	$class = 'media-window';
	$defaults = array(
					  'scale'         => 1,
					  'cache'         => 'true',
					  'autoplay'      => 'true',
					  'controller'    => 'true',
					  'type'          => FILE::mimetype($filename),
					  'src'           => $filename,
					  'href'          => $filename,
					  'posterframe'   => '',
					  );
	
	$defaults = array_merge($defaults, $options); // doing this allows only those elements we want to change to do so
	
	$tags = array(
				  'object' => array(
									'width'         => $width,
									'height'        => $height,
									
									'classid'       => 'clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B',
									'codebase'      => 'https://www.apple.com/qtactivex/qtplugin.cab',
									),
				  'param' => array(
								   'src'           => $filename,
								   
								   'scale'         => $defaults['scale'],
								   'cache'         => $defaults['cache'],
								   'autoplay'      => $defaults['autoplay'],
								   'controller'    => $defaults['controller'],
								   ),
				  'embed' => array(
								   'width'         => $width,
								   'height'        => $height,
								   'posterframe'   => $defaults['posterframe'],
								   'src'           => $defaults['src'],
								   //	'href'          => $defaults['href'],
								   
								   'scale'         => $defaults['scale'],
								   'cache'         => $defaults['cache'],
								   'autoplay'      => $defaults['autoplay'],
								   'controller'    => $defaults['controller'],
								   
								   'pluginspage'   => 'https://www.apple.com/quicktime/download/',
								   'type'          => $defaults['type'],
								   ),
				  );
	
	$result = "\n".span(array('class' => $class))."\n".tag('object', $tags['object'])."\n";
	
	foreach ($tags['param'] as $name => $value) $result .= self::tag('param', array('name' => $name, 'value' => $value));
	
	$result .= tag('embed', $tags['embed']).tag('object', '/').span('/');
	
	return $result;
}

/**
 * Returns the names of all the javascript attributes.
 *
 * @return the names of all the javascript attributes.
 */
function jsattributes() {
	return array(
				 'onabort',     'onreset',     'onsubmit',   'onresize', 'onerror',
				 'onblur',      'onfocus',     'onchange',   'onselect', 'onclick', 
				 'ondblclick',  'onkeydown',   'onkeypress', 'onkeyup',
				 'onmousedown', 'onmousemove', 'onmouseout', 'onmouseover', 
				 'onmouseup',   'onload',      'onunload',
				 );
}

/**
 * Returns an iframe tag
 *
 * @return	the HTML for a proper iframe
 */
function iframe ($options) {
	$defaults = array('width' => 0, 'height' => 0, 'frameborder' => 0, 'scrolling' => 'no');
	$options = smart_merge($defaults, $options);
	$result = tag('iframe', $options);
	
	return $result;
}

/**
 * returns true if the USER AGENT indicates this is a mobile device
 *
 * @return	a boolean indicating if the USER AGENT indicates this is a mobile device.
 */
function isMobile() {
	return str_contains($_SERVER['HTTP_USER_AGENT'], 'Mobile');
}

/**
 * sets the URL that will get returned when the back function is called.
 *
 * @param	url		the url to go back to.
 * @param	sid		the user making the request.
 * @return	a boolean indicating the URL was set.
 */
function setBack($url, $sid) {
	return self::setNav('back', $url, $sid);
}

/**
 * gets the URL most recently set in setBack and removes is from the list.
 *
 * @param	sid		the user making the request.
 * @return	the URL.
 */
function back($sid) {
	return self::nav('back', $sid);
}

/**
 * gets the URL most recently set in setBack .
 *
 * @param	sid		the user making the request.
 * @return	the URL.
 */
function getback($sid) {
	return self::nav('getback', $sid);
}

/**
 * sets the URL that will get returned when the forward function is called.
 *
 * @param	url		the url to go to.
 * @param	sid		the user making the request.
 * @return	a boolean indicating the URL was set.
 */
function setForward($url, $sid) {
	return self::setNav('forward', $url, $sid);
}

/**
 * gets the URL most recently set in setForward and removes is from the list.
 *
 * @param	sid		the user making the request.
 * @return	the URL.
 */
function forward($sid) {
	return self::nav('forward', $sid);
}

/**
 * gets the URL most recently set in setForward.
 *
 * @param	sid		the user making the request.
 * @return	the URL.
 */
function getforward($sid) {
	return self::nav('getforward', $sid);
}

/**
 * sets the URL that will get returned when the back/forward function is called.
 *
 * @param	url		the url to go to.
 * @param	sid		the user making the request.
 * @return	a boolean indicating the URL was set.
 */
function setNav($mode, $url, $sid) {
	return FILE::write("./nav/{$mode}/{$sid}.txt", "\n$url", 'a');
}

/**
 * gets the URL most recently set in setForward/setBackward and removes it from the list.
 *
 * @param	sid		the user making the request.
 * @return	the URL.
 */
function nav($mode, $sid) {
	$get = str_begins($mode, 'get');
	if ($get) $mode = substr($mode, 3);
	
	$data = FILE::read("./nav/{$mode}/{$sid}.txt");
	$urls = explode("\n", $data);
	
	if (is_array($urls)) {
		if ($get) {
			$url = end($urls);
		} else {
			$url = array_pop($urls);
		}
	} else {
		$url = '';
	}
	
	$data = '';
	
	foreach ($urls as $line) {
		if ($line != '') $data .= "\n".$line;
	}
	
	FILE::write("./nav/{$mode}/{$sid}.txt", $data, 'w');
	
	return $url;
}

/**
 * Checks to see if a default sidebar file exists. It identifies the most specific version.
 * This is not generally called by a user page.
 */
function checkForSidebar() {
	page('sidebar');
}	

/**
 * Checks to see if a sidebar should be used. This is not generally called by a user page.
 *
 * @return	returns the name of the file to use or false.
 */
function hasSidebar() {
	return page('sidebar');
}

/**
 * Sets a sidebar file to use. This is generally caled within a user page. Default is no sidebar.
 *
 * @param	$sidebarfile	the name of the file to use. deault is none.
 */
function setSidebar($sidebarfile=false) {
	page('sidebar', $sidebarfile);
}

/**
 * Sets the isFS value for the page to determine if it should render the page as full width or not.
 *
 * @param	$fs		boolean indicating if FS should be used (default is true).
 */
function setFS ($fs=true) {
	set('isFS', $fs);
}

/**
 * Checks the isFS value for the page to determine if it should render the page as full width or not.
 *
 * @return	the current isFS setting or false (the default).
 */
function isFS () {
	return get('isFS', false);
}

/**
 * This function creates the HTML to produce an interpage nav link in a standard way.
 *
 * @param	$sections	an array of names and labels to build up a nav bar.
 * @return	returns the HTML necessary.
 */
function pageNav($sections) {
	$links = '';
	foreach ($sections as $name => $label) {
		append($links, LINK::toName($name, $label, LINK::rtn()), ' ');
	}
	return p('class:page-nav', $links);
}

/**
 * this function produces a very plain page 
 * 
 * @param	$content	the actual content of the page
 * @param	$doctype	the doctype to use
 * @param	$docversion	the docversion to use
 * @returns the HTML of the page
 */
function plainPage($content, $doctype='XHTML', $docversion='STRICT') {
	while(@ob_end_clean());
	
	ob_start();
	self::doctype($doctype, $docversion);
	echo tag('head');
	echo tag('body');
	echo $content;
	echo tag('body', '/');
	echo tag('html', '/');
	
	$results = ob_get_contents();
	ob_end_clean();
	return $results;
}

/**
 *
 * Formats the passed data as an APA citation.
 *
 * @param	$author		the author of the item
 * @param	$date		the date of publication
 * @param	$title		the title of the item
 * @param	$edition	any edition
 * @param	$location	where it was published if available
 * @param	$publisher	the publisher
 * @param	$isbn		the isbn of the item
 * @return	the HTML to format the data as an APA citation which is:
 *
 * author (date).  <em>title</em> (edition if it exists).
 *	location: publisher
 *	isbn
 */
function APA_citation($author, $date, $title, $edition, $location, $publisher, $isbn) {
	$citation  = '';
	$author    = trim($author);
	$publisher = trim($publisher);
	$location  = trim($location);
	$date      = trim($date);
	$title     = trim($title);
	$edition   = trim($edition);
	$isbn      = trim($isbn);
	
	append($citation, $author);
	
	if ($date != '') append($citation, "($date).", ' ');
	
	append($citation, em($title), ' ');
	
	if ($edition != '') append($citiation, "($edition)", ' ');
	
	append($citation, '.', '');
	
	if ($location  != '') append($citation, $location.':', ' ');
	if ($publisher != '') append($citation, $publisher, br().nbsp(4));
	if ($isbn      != '') append($citation, $isbn, br().nbsp(4));
	
	return $citation;
}
	
/**
 * This function allows you to modify javascript loading rules at runtime
 *
 * @param	$setting	allows for one of two values: 'page-onload' and 'skip-load'
 * @param	$value		if $setting is 'page-onload' value is the javascript to execute when the page finishes loading
 *						if $setting is 'skip-load' $value is an array of filenames to NOT load when loading javascript files
 * @return	true if the set operation was successful and false if not.
 */
function javascript($setting, $value='') {
	if (!in_array($setting, array('page-onload', 'skip-load'))) return false; 
	
	if ($value === null) {
		return clear($setting);
		
	} else if ($value === '') {
		$val = get($setting, false);
		if ($setting == 'page-onload') { 			
			if ($val === false) $val = array_extract(get('javascript-onload'), array(page()), '');
		}
		return $val;
		
	} else {
		return set($setting, $value);
	}
}

function short() {
	alias('tag');
}
    
}

/**
 * Construct an HTML tag with the optional values.
 *
 * @param	$tag			the name of the tag to construct.
 * @param	$attributes	a keyed array of entries to include, or a list in the form 'attr:value | attr: value'.
 * @param	$contents	the stuff between the open and closing tag.
 * @return	a string containing an HTML element enclosing the content.
 */
function tag($tag, $attributes='', $contents=null) {
	static $selfclosing = array(
		'br', 'hr', 'img', 'meta', 'link', 'img', 'input', 'html', 'param', 'embed', 'enclosure', 'area',
		);
		
	static $requiredattributes = array(
	   'applet'		=> array('height' => '1', 'width' => '1'),
	   'area'		=> array('alt' => 'clickable area'),
	   'basefont'	=> array('size' => '12px'),
	   'bdo'		=> array('dir' => ''),
	   'img'		=> array('src' => '', 'alt' => 'image'),
	   'form'		=> array('action' => '', 'method' => 'post'),
	   'map'		=> array('name' => 'map'),
	   'meta'		=> array('content' => ''),
	   'optgroup'	=> array('label' => '-'),
	   'param'		=> array('name' => 'param'),
	   'textarea'	=> array('cols' => '1', 'rows' => '1'),
	   'script'		=> array('type' => 'text/javascript'),
	   'style'		=> array('type' => 'text/css'),
	   );
	
	static $newline = array(
		'body', 'br', 'head', 'hr', 'div', 'link', 'meta', 'p', 'pre',
		'fieldset', 'form', 'optgroup', 'select',
		'dl', 'dt', 'ol', 'ul', 'li',
		'table', 'tbody', 'thead', 'tr',
		);
	
	$tag = strtolower($tag);
	$result = '';
	
	if ($attributes == '/')	{ 	// this is an explicit close request
		$close = true;
		$contents = null;
	} else {
		$close = false;
		$result = '<'.$tag;
		$attributes = strtoarray($attributes);
		
		// get the XHTML required attributes for this tag
		$required = array();
		if (array_key_exists($tag, $requiredattributes)) $required = $requiredattributes[$tag];
		
		$attributes = array_merge($required, $attributes);
		
		foreach ($attributes as $name => $value) {
			if (get('absolute-references') && in_array($name, array('src', 'href')) && !str_begins($value, 'http')) {
				$value = page('fullHost').$value;
			}
			$value = str_replace(array("'", '"'), array('&#34;', '&#39;'), $value); // clean up any quotes
			if (($value != '') || ($tag == 'option')) append($result, $name.'="'.$value.'"', ' ');
		}
				
		if (in_array($tag, $selfclosing)) $result .= ' /';
		$result .= '>';
		
	}
	
	if (in_array($tag, $newline)) $result .= "\n";
	
	if (!in_array($tag, $selfclosing)) {
		if ($contents !== null) {
			$result .= $contents;
			$close = true;
		}
		
		if ($close) {
			$result .= '</'.$tag.'>';
			if (in_array($tag, $newline)) $result .= "\n";
		}
	}
	
	return $result;
}

function acronym($attributes='', $contents=null) { return tag('acronym', $attributes, $contents); }

function area($attributes='', $contents=null) { return tag('area', $attributes, $contents); }

function br() { return tag('br'); }

function dd($attributes='', $contents=null) { return tag('dd', $attributes, $contents); }

function div($attributes='', $contents=null) { return tag('div', $attributes, $contents); }

function dlist($attributes='') { return tag('dl', $attributes, ''); }

function dt($attributes='', $contents=null) { return tag('dt', $attributes, $contents); }

function em($contents=null) { return tag('em', '', $contents); }

function fieldset($attributes='', $contents=null) { return tag('fieldset', $attributes, $contents); }

function form($attributes='', $contents=null) { return tag('form', $attributes, $contents); }

function h1($attributes='', $contents=null) { return tag('h1', $attributes, $contents); }

function h2($attributes='', $contents=null) { return tag('h2', $attributes, $contents); }

function h3($attributes='', $contents=null) { return tag('h3', $attributes, $contents); }

function h4($attributes='', $contents=null) { return tag('h4', $attributes, $contents); }

function h5($attributes='', $contents=null) { return tag('h5', $attributes, $contents); }

function h6($attributes='', $contents=null) { return tag('h6', $attributes, $contents); }

function hr($attributes='') { return tag('hr', $attributes); }

function input($attributes='') { return tag('input', $attributes, ''); }

function label($attributes='', $contents=null) { return tag('label', $attributes, $contents); }

function legend($attributes='', $contents=null) { return tag('legend', $attributes, $contents); }

function li($attributes='', $contents=null) { return tag('li', $attributes, $contents); }

function map($attributes='', $contents=null) { return tag('map', $attributes, $contents); }

function meta($attributes='') { return tag('meta', $attributes, ''); }

function nbsp($number=1) { return str_repeat('&nbsp;', $number); }

function olist($attributes='') { return tag('ol', $attributes, ''); }

function optgroup($attributes='', $contents=null) { return tag('optgroup', $attributes, $contents); }

function option($attributes='', $contents=null) { return tag('option', $attributes, $contents); }

function p($attributes='', $contents=null) { return tag('p', $attributes, $contents); }

function pre($attributes='', $contents=null) { return tag('pre', $attributes, $contents); }

function script($attributes='', $contents=null) { return tag('script', $attributes, $contents); }

function select($attributes='', $contents=null) { return tag('select', $attributes, $contents); }

function span($attributes='', $contents=null) { return tag('span', $attributes, $contents); }

function strong($attributes='', $contents=null) { return tag('strong', $attributes, $contents); }

function table($attributes='', $contents=null) { return tag('table', $attributes, $contents); }

function tbody($attributes='', $contents=null) { return tag('tbody', $attributes, $contents); }

function td($attributes='', $contents=null) { return tag('td', $attributes, $contents); }

function th($attributes='', $contents=null) { return tag('th', $attributes, $contents); }

function thead($attributes='', $contents=null) { return tag('thead', $attributes, $contents); }

function tr($attributes='', $contents=null) { return tag('tr', $attributes, $contents); }

function ulist($attributes='', $contents=null) { return tag('ul', $attributes, $contents); }

?>