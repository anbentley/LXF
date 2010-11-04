<?php

/**
 * LINK provides a common interface to creating various types of HTML links.
 * It is intended for use to hide the details of links from the developer so that hard links are not used.
 * This allows for sitewide changes in link behavior without impacting page code.
 * All functions are intended to be used statically.
 * 
 * @author	Alex Bentley
 * @history	7.2		integration with new architecture
 *			7.1		simplified the absolute functionality.
 *			7.0		added new abilities for options 
 *			6.8		modified LINK so that if no label is given, the resulting URL is converted to a more human readable format.
 *			6.7		added back and forward options to a link
 *			6.6		added js function
 *			6.5		added toName function
 *			6.4		update to download function to account for a site prefix.
 *			6.3		additional fix to force page names to get processed whether they have a value or not
 *			6.2		fix to url function for parameters that are just passed as 'name'
 *			6.1		renamed source to tag for more consistent naming and fixed this page processing
 *			6.0		now supports javascript actions directly
 *			5.15	code cleanup
 *			5.14	removed suppressid attribute
 *			5.13	removed addParams function
 *			5.12	fix to selected processing.
 *			5.11	update to handle when PHP is running as a CGI
 *			5.10	fix to source to handle links when a default-site not defined
 *			5.9		documentation update
 *			5.8		removed dependence on ABOUT class
 *			5.7		support for new value INIT::get("environment-params") to add to all url calls
 *			5.6		redirect improvement for session handling
 *			5.5		eliminated warning when no link was specified
 *			5.4		added replace function
 *			5.3		add url function to ultimately replace addParams function
 *			5.2		fix redirect
 *			5.1		simplfied addParams interface
 *			5.0		updated site processing
 *			1.0		initial release
 */
class LINK {

    static $rtn = array('return' => true);

/**
 * Defines the default settings used for all links.
 *
 * @details	class;			;			this text is placed in the class attribute of this link.
 *			id;				;			this text is placed in the id attribute of this link.
 *			title;			;			this text is placed in the title attribute of this link.
 *			javascript;		;			this text is placed in the javascript attribute of this link.
 *			style;			;			this text is placed in the style attribute of this link.
 *			target;			;			this text is placed in the target attribute of this link.
 *			rel;			;			this text is placed in the rel attribute of this link.
 *			subnav;			;			if specified; this text if it matches the beginning of the page; causes this link have the class selected.
 *			secure;			inherit;	http or https: values are inherit; true or false.
 *			use-full-link;	false;		a boolean indicating if we should use the entire URL to determine if this link refers to this same content.
 *			return;			false;		a boolean indicating if we should return the HTML for the link instead of echoing it.
 *			external;		false;		a boolean indicating if this link should open a new window.
 *			internal;		false;		a boolean indicating if this link should not be interpretted as a page request.
 *          absolute;       false;      a boolean indicating if the URL should contain the full host name.
 *			mailto;			false;		a boolean indicating if this is a mailto link.
 *			redirect;		false;		a boolean indicating if this is a redirect.
 *			close-session;	true;		a boolean indicating if we should close the session before a redirect.
 *			permanent;		false;		Allows for a redirect to return HTTP/1.1 301 Moved Permanently.
 *			back;			;			allows for a HTML::setBack call to be made.
 *			forward;		;			allows for a HTML::setForward call to be made.
 *			sid;			;			the userid currently logged in.
 */
function defaults() {
	$defaults = array(
		'class'         => '', 
		'id'            => '', 
		'title'         => '', 
		'style'         => '',
		'target'        => '',
		'rel'           => '',
		'subnav'        => '', 
		'secure'        => 'inherit', 
		'use-full-link' => true, 
		'return'        => false,
		'external'      => false,
		'new-window'	=> false,
		'internal'      => false,
        'absolute'      => false,
		'mailto'        => false,
		// redirect parameters
		'redirect'      => false,
		'close-session' => true,
		'permanent'     => false,
		'back'          => '',
		'forward'       =>'',
		'sid'           => '',
        'use-site'      => false,
	);
    
	foreach(HTML::jsattributes() as $jsa) $defaults[$jsa] = '';
	
	return $defaults;
}

/**
 * Converts a relative URI to an absolute URI.
 *
 * @param	$uri	the URI to convert
 * @return			the converted URI
 */
function toAbsolute($uri, $secure=false) {		
	$parts = array_merge(array('scheme' => 'http', 'port' => '', 'host' => $_SERVER['SERVER_NAME'], 'path' => '', 'query' => ''), parse_url($uri));
	if ($parts['port']  != '') $parts['port'] = ':'.$parts['port'];
	if ($parts['query'] != '') $parts['query'] = '?'.$parts['query'];
	if ($secure) $parts['scheme'] .= 's';
	
	return $parts['scheme'].'://'.$parts['host'].$parts['port'].$parts['path'].$parts['query'];
}

/**
 * Clean implementation of return array value
 *
 * @return	a simple array containing the return option so a link doesn't echo but returns the text of the link.
 */
function rtn() {
	return self::$rtn;
}

function display_entities($text) {
	return preg_replace('/&amp;(#[\d]*|[a-z]{2,8});/', '&$1;', str_replace('&', '&amp;', $text));
}

/**
 * Produce a standard link to a page on this site with the added ability to null out links to this page
 *
 * @param	$page	the url of the page for this link.
 * @param	$label	the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 * @see		smart_merge
 */
function tag ($page, $label='', $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	
	$options = smart_merge(self::defaults(), $options);
    
	$defaultSite = get('default-site');
	
	// get just the page name part of the request
	parse_str($page, $params);
	$pg = array_shift(array_keys($params));
	if (is_numeric($pg)) $pg = urldecode(array_shift(array_values($params)));
	
	@list($site, $pg) = explode(':', $pg, 2);
	if ($pg == '') { // no colon (no site)
		$pg = $site;
		$site = page('site', ':'); // use the site from the current page if none was specified
	} else {
		$site .= ':';
	}
	
	if ($site == $defaultSite.':') $site = '';
		
	if (($options['subnav'] != '') && str_begins($pg, $options['subnav'])) $options['class'] = 'selected';
	
	// is this a link to the current page?	
	if (!$options['redirect'] && (($site.$pg == page('site', ':').page()) && !$options['use-full-link']) 
	|| ($options['use-full-link'] && (str_replace('&amp;', '&', $site.$page) == substr(page('request'), 1)))) {
		$options['class'] = 'selected';
		$page .= '#';
	}
	
	// process all secure options
	$prefix = '';
	if (!str_begins($page, 'http')) {
		 if (($options['secure'] === true) && !isset($_SERVER['HTTPS'])) { // if secure is forced
			$prefix = self::toAbsolute('', $options['secure']).'/';
			
		} else if (($options['secure'] === false) && isset($_SERVER['HTTPS'])) { // we are secure and need to be not secure
			$prefix = self::toAbsolute('', $options['secure']).'/';
			
		} else if (($options['redirect'] === true)) { // if this is just a redirect maintain the same settings
			$prefix = self::toAbsolute('', isset($_SERVER['HTTPS'])).'/';
			
		} else if ($options['absolute']) {
			$prefix = self::toAbsolute('', $options['secure']).'/';
		}
	}

	if (($path = page('path')) != '/') $prefix .= $path; // add in a path if necessary
	if (($script = page('script')) != 'index.php') $prefix .= $script; // add in the script name if necessary
	
	if (!$options['external'] && !$options['internal'] && !str_begins($page, array('http', '?'))) $prefix .= '?'; // just a page was specified
	if (($site != '') && !str_begins($page, $site)) {
		if (str_begins($page, '?')) {
			$page = '?'.$site.substr($page, 1);
		} else {
			$prefix .= $site;
		}
	}
	
	if ($options['mailto']) {
		$prefix = 'mailto:';
		$prefix = htmlencode($prefix);
		@list($addr, $params) = explode('?', $page, 2);
		if ($params != '') $params = '?'.$params;
		$page = htmlencode($addr).$params;
		$label = htmlencode($label);
	} else {
		$label = self::display_entities($label);
	}
	
	if ($defaultSite != '') $page = str_replace(array(' ', $defaultSite.':'), array('%20', ''), $page); // remove the default site
	if ($page == '/') $page = ''; // if no page was specified
	
	// process back and forward options    
	if ($options['back']    != '') $options['onclick'] .= "\n".'setBack("'.urlencode($options['back']).'", '.$options['sid'].', "'.$page.'");';
	if ($options['forward'] != '') $options['onclick'] .= "\n".'setForward("'.urlencode($options['forward']).', '.$options['sid'].'");';
	
	
	// process link attributes
	if ($options['external'] || $options['new-window']) $options['rel'] = 'external';
    	
    // handle absolute links
    if ($options['absolute']) {
        $page = $prefix.$page;
		$prefix = '';
    }
	
	if ($label == '') $label = urldecode($page);
	
	$attrs = array_merge(array('href' => $prefix.$page), UTIL::extract_items(array('class', 'id', 'rel', 'style', 'target', 'title'), $options));
	
	// process any javascript options
	foreach(HTML::jsattributes() as $jsa) $attrs[$jsa] = $options[$jsa];
	
	$result = tag('a', $attrs, $label);
	
	if ($options['return']) {
		return $result;
		
	} else if ($options['redirect']) {
		$page = str_replace('&amp;', '&', $page); // change separator to & from &amp; when performing a redirect.
		
		if ($options['permanent']) header('HTTP/1.1 301 Moved Permanently');
		
		header('Location: '.$prefix.$page);

		if (isset($_SESSION) && $options['close-session']) session_write_close();
		
		exit();
	} else {
		echo $result;
	}
}

/**
 *	Provides a link capability including parameters in a single call
 *
 * @param	$page		the url of the page for this link.
 * @param	$params		an optional array of parameters.
 * @param	$label		the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		tag
 * @see		url
 */
function paramtag($page, $params=array(), $label, $options=array()) {
	return self::tag(self::url($page, $params), $label, $options);
}

/**
 * Determine the location of the page.
 *
 * @param	$p		the name of the page/file.
 * @param	$base	the logical directory where this file resides.
 * @return	the actual filename.
 * @see		SITE::file
 */
function pageLocation($p, $base='pages') {
	return SITE::file($p.'.html', get($base));
}

/**
 * Produce a standard link to a page on this site with the added ability to null out links to this page
 *
 * @param	$page	the url of the page for this link.
 * @param	$label	the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 * @see		tag
 */
function local ($page, $label='', $options=array()) { 
	return self::tag(self::url($page), $label, $options);
}

/**
 * Produce a standard link with a specific option.
 *
 * @param	$page	    the url of the page for this link.
 * @param	$setOption	the specific option to apply (absolute, external, internal, mailto, or redirect).
 * @param	$label      the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 * @see		tag
 */
function standard ($page, $setOption, $label='', $options='') {
    if (!in_array($setOption, array('absolute', 'external', 'internal', 'mailto', 'redirect'))) return false;
	$options = smart_merge(array($setOption => true), $options);
	if ($label == '') $label = $page;
    
    return self::tag(self::url($page, array()), $label, $options);
}

/**
 * Produce a standard link to an absolute url (full host name, not relative).
 *
 * @param	$page	the url of the page for this link.
 * @param	$label	the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see     standard
 */
function absolute ($page, $label='', $options='') {
    return self::standard($page, 'absolute', $label, $options);
}

/**
 * Produce a standard link to an external url (new window).
 *
 * @param	$page	the url of the page for this link.
 * @param	$label	the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see     standard
 */
function external ($page, $label='', $options='') {
    return self::standard($page, 'external', $label, $options);
}

/**
 * Produce a link to an internal page outside the normal mechanism in a standard way.
 *
 * @param	$page	the url of the page for this link.
 * @param	$label	the text of the link displayed to the user (defaults to the url).
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see     standard
 */
function internal ($page, $label='', $options='') {
    return self::standard($page, 'internal', $label, $options);
}

/**
 * Produce a mailto link in a standard way.
 * Provides address obfuscation.
 *
 * @param	$address	the email address for this link.
 * @param	$label		the text of the link displayed to the user.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see     standard
 */
function mailto ($address, $label='', $options='') {
    return self::standard($address, 'mailto', $label, $options);
}

/**
 * Produce a redirect in a standard way.
 *
 * @param	$page		the url to redirect to.
 * @param	$options	a keyed array of values to override defaults.
 * @see     standard
 */
function redirect ($page, $options=array()) {
    return self::standard($page, 'redirect', '', $options);
}

/**
 * Produce a named link in a standard way.
 *
 * @param	$name	the name for this link.
 * @param	$label	the text of the link displayed to the user.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 */
function name ($name, $label=' ', $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	$name = str_replace(array(',', '&', ' '), '', $name);
	$result = tag('a', array('name' => $name), $label);
	
	if (array_key_exists('return', $options) && $options['return']) return $result;

    echo $result;
}

/**
 * Produce a link to a named target link in a standard way.
 *
 * @param	$name	the name for this link.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 */
function toName ($name, $label, $options=array()) {
	$options = smart_merge(array('return' => false), $options);
	$name = str_replace(array(',', '&', ' '), '', $name);
	$result = tag('a', array('href' => '?'.str_replace('&', '&amp;', page('fullPage')).'#'.$name), $label);
	
	if ($options['return']) return $result;
    echo $result;
}

/**
 * Determine the URL prior to the query string.
 *
 * @return	the beginning of the current URL.
 */
function pageURL () {
	$loc = $_SERVER['HTTP_HOST'];
    
	if (array_key_exists('REDIRECT_HANDLER', $_SERVER)) {
		$script = $_SERVER['PHP_SELF'];
	} else {
		$script = $_SERVER['SCRIPT_NAME'];
	}
    
	if (!str_ends($script, '.php')) $script = '/index.php';
    
	// necessary since chdsnew is in there
	if (str_ends($script, '/index.php')) $script = str_replace('/index.php', '/', $script);
	
	return $loc.$script;
}
	
/**
 * Convert a url link to a form submission.
 *
 * @param	$url		the url for this link.
 * @param	$label		the text of the link displayed to the user.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 */
function form ($url, $label, $options=array()) {
	$url = str_replace('&amp;', '&', $url); // normalize url
	$params = explode('&', $url);
	$page = array_slice($params, 0, 1);
	$page = $page[0];
	unset($params[0]);
	
	foreach ($params as $param) {
		list($p, $v) = explode('=', $param);
		$field[] = 'element:text | hidden | name:'.$p.' | value:'.$v;
	}
		
	return FORM::display($field, smart_merge(array('method' => 'post', 'action' => '?'.$page, 'submit' => $label), $options));
}

/**
 * Given an input array of links, produce a ul with the links as entries.
 *
 * @param	$linkarray	an array of logical links.
 * @param	$options	a keyed array of values to override defaults.
 * @see		defaults
 */
function ulist($linkarray, $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	
	$options = array_merge(array('class' => '', 'id' => '', 'title' => ''), $options);

	// process link attributes
	echo ulist('class:'.$options['class'].' | id:'.$options['id'].' | title:'.$options['title']);
	foreach ($linkarray as $url => $label) {
		echo li();
		if (str_begins($url, 'http')) {
			self::external($url, $label);
		} else {
			self::local($url, $label);
		}
		echo li('/');
	}
	echo ulist('/');
}

/**
 * Produce a valid link to a file if the file exists 
 *
 * @param	$name		name of the file.
 * @param	$dir		the directory where the file is supposed to be.
 * @param	$page		the page to link to.
 * @param	$param		the name of the parameter to use for the file.
 */
function directory($name, $dir, $page, $param) {
	$file = str_replace(' ', '', $name);
	if (file_exists($dir.'/'.$file.'.html')) {
		self::local($page.'&amp;'.$param.'='.$file, $name);
	} else {
		echo $name;
	}
}

/* produce a valid link to this course description if it exists */
function course($course) {
	return self::directory($course, 'parts/course','showcourse', 'course');
}

/**
 * Add parameters to a URL in a safe way (urlencoded parameter values).
 *
 * Supports global 'environment-params' adding those values to all urls if set.
 *
 * @param	$url		the url to add to.
 * @param	$params		a keyed array of parameters and values to add to the url.
 * @return	the new url string.
 */
function url($url, $params=array()) {
	if (is_string($params)) $params = strtoarray($params);
    if (!is_array($params)) $params = array();
    
    // process environment params
    $ep = page('environment');
    $evp = array();
    foreach ((array)$ep as $pn) $evp[$pn] = param($pn);
    $params = array_merge($params, $evp);
    //print_r($params);
    if (count($params)) {        
        foreach ($params as $name => $value) {            
            // we'll deal with this at the end
            if ($name === '#') continue; 
                        
            // an unnamed parameter
            if (is_numeric($name)) $name = '';			
            append($name, urlencode((string)$value), '=');
            append($url, $name, '&amp;');
        }

        // process any internal page name tag
        if (array_key_exists('#', $params)) append($url, $params['#'], '#');
	}
	return $url;
}

/**
 * Attempt to replace/remove an embedded parameters in an existing url.
 *
 * @param	$url		the source url.
 * @param	$new		a keyed array of new parameters to override any existing ones.
 * @return	the updated url.
 */
function replace($url, $new) {
	$url = str_replace('&amp;', '&', $url); // remove htmlentities
	parse_str($url, $old);

	return self::url('', array_merge($old, $new));
}

/**
 * Produce a download link for a file.
 *
 * @param	$dir		the name of the directory.
 * @param	$file		the name of the file.
 * @param	$label		the text of the link displayed to the user.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 */
function download($dir, $file, $label, $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	
	$site = page('site');
	
	if (($site != '') && (array_key_exists('use-site', $options) && $options['use-site'])) {
		$dir = $site.'/'.$dir;
	}
	$options['mode'] = 'dl';
	return self::serve($dir, $file, $label, $options);
}

/**
 * Produce a web safe link for a file.
 *
 * @param	$file		the name of the file.
 * @param	$label		the text of the link displayed to the user.
 * @param	$options	a keyed array of values to override defaults.
 * @return	if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 */
function serve($dir, $file, $label, $options=array()) {
	$defaults = strtoarray('mode:inline | external:false | h: | w: | alt-name:'.$file);

	$options = smart_merge($defaults, $options);
	
	$realdir = FILE::dir($dir, false);
	$realfile = $realdir.'/'.$file;
    
	$mode = $options['mode'];
	if ($mode == 'dl') $options['external'] = false;
    
	if (file_exists($realfile)) {
		if ($options['external']) {
			$options['rel'] = 'external';
			$options['external'] = false;
		}
		return self::paramTag(get('file-serve'), 'mode:'.$mode.' | h:'.$options['h'].' | w:'.$options['w'].' | drm:'.$dir.' | f:'.$file.' | altf:'.$options['alt-name'], $label, $options);
	}
}

/** Produce a link that invokes javascript as the target of the link
 *
 * No javascript prefix is required
 *
 * @param	$js			the javascript code to invoke.
 * @param	$label		the html to display for this link.
 * @param	$options	a keyed array of values to override defaults.
 * @return				if the return option is true, then it returns the HTML for this link.
 * @see		defaults
 */
 function js ($js, $label, $options=array()) {
	$defaults = array(
		'class'     => '', 
		'id'        => '', 
		'style'     => '',
		'return'    => false,
		'href'      => 'javascript:'.$js
	);

	$options = smart_merge($defaults, $options);
	
	// we have to manually handle the return value
    $return = $options['return'];
	unset($options['return']);
	
	$link = tag('a', $options, $label);
	if ($return) return $link;
    
    echo $link;
}
 
/**
 * Creates a javascript back link using the setBack Javascript function
 *
 * @param   $backlink   a URL to link to
 * @param   $userid     the userid to use 
 * @param   $whereto    the location of the link
 * @param   $label      the label to display for this link
 */
function setBack($backlink, $userid, $whereto, $label) {
	return self::js('setBack("'.urlencode($backlink).'", '.$userid.', "'. $whereto.'");', $label, self::rtn());
}
					
/**
 * Creates a javascript forward link using the setForward Javascript function
 *
 * @param   $forwardlink   a URL to link to
 * @param   $userid        the userid to use 
 * @param   $whereto       the location of the link
 * @param   $label         the label to display for this link
 */
function setForward($forwardlink, $userid, $whereto, $label) {
	return self::js('setForward("'.urlencode($forwardlink).'", '.$userid.', "'. $whereto.'");', $label, self::rtn());
}
	
} // end LINK class

?>