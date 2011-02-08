<?php
/**
* SITE is the fundamental building block for website creation.
* This architecture is based on a previous design using the bootstrap loader pattern.
*
* @author	Alex Bentley
* @history	1.6		support for AJAX clean replies
*			1.5		code cleanup, allows for full use of non-webroot functionality
*			1.1		generalized the sidebar functionality
*			1.0		initial release
*/

class SITE {

/**
 * This function constructs the HTML for the page.
 */
function makePage($configuration='') {
	self::loadLibraries($configuration);
	
	if (param('CSS', 'exists') && (param('CSS') == '')) { // if this is a CSS request, process it, and return
		self::includeFiles('CSS');
		return;

	} else if (param('JavaScript', 'exists') && (param('JavaScript') == '')) { // if this is a JS request, process it, and return
		self::includeFiles('JavaScript');
		return;
	}

	$pageparts = get('page-parts');

	// get page contents
	$file = self::file(page().'.html', get('pages'), self::file(get('file-not-found'), get('pages')));

	ob_start();
	if (file_exists($file)) $success = include_once $file;
	$content = ob_get_contents();
	ob_end_clean();

	if (get('mobile-support') && str_contains($_SERVER['HTTP_USER_AGENT'], 'Mobile')) {
		$version = 'MOBILE';
	} else {
		$version = get('document-version', 'STRICT');
	}

	// get head section for page
	ob_clean();

	ob_start();
	echo self::doctype(get('document-type', 'XHTML'), $version);
	echo self::head($version);

	// now build the page around the content (I know this sounds odd...)
	foreach ($pageparts as $name => $part) {
		if ($name == 'content') {
			echo $content;
		} else {
			$file = self::file($part,get('parts'), '');
			if (file_exists($file)) $success = include $file;
		}
	}
	page('environment', null); // erase any outstanding parameters.
}

/**
 * This function loads all needed libraries.
 */
function loadLibraries($configuration) {
	// load the intrinsic functions before we do ANYTHING else
	$files = get_included_files();
	foreach ($files as $file) {
		if (basename($file) == 'site.php') { // this file...
			$librarypath = dirname($file);
			$success = include_once $librarypath.'/intrinsics.php';
			$frameworkpath = dirname($librarypath).'/';
			set('packages', array('framework' => $frameworkpath));
			break;
		}
	}
	
	$defaults = array(
		'doctype' => array('type' => 'XHTML', 'version' => 'STRICT'),

		'library'			=> 'library',
		'javascript'		=> 'javascript',
		'css'				=> 'css',
		'pages'				=> 'pages',
		'parts'				=> 'parts',
		'configuration'		=> 'configuration',

		'page-parts' => array(
			'page-start'	=> 'page-start.html',
			'header'		=> 'header.html',
			'content-start' => 'content-header.html',
			'content'		=> 'nofile.html',
			'content-end'	=> 'content-footer.html',
			'footer'		=> 'footer.html',
			'page-end'		=> 'page-end.html',
			),

		'default-page' => 'index',
		'file-serve' => 'file',
		'force-download' => array('m4a', 'm4b', 'mp4'),
		'default-site' => 'site',
		'javascript-onload' => array(),

		'packages' => array(
		  'framework' => str_replace(dirname(getcwd()), '..', $frameworkpath),
		  ),
		'extensions' => array(),
		);
	
	set('public-directory', '');
	set('site-directory', $configuration);
	set('configuration', $defaults, 'load'); // load configuration
	
	$site = page('site', '/');
	$includeDirs = array_unique(
		array_merge(
			array(page('path'), page('path').$site, ini_get('include_path'), get('site-directory')),
			get('packages'),
			get('extensions')
			)
		);
	
	// build usable path
	$includePath = array_shift($includeDirs);
	
	foreach($includeDirs as $dir) $includePath .= ':'.dirname($dir.'/x');
	
	ini_set('include_path', $includePath); // insert it into the path
	
	self::includeFiles('PHP');
}

/**
 * Return all relevant directories to process.
 *
 * @param	$includeDir	the base directory name.
 * @return	an array of directories to use for locating files.
 * @see		page
 * @see		site
 * @see		get
 */
function getIncludeDirectories($includeDir) {
	// allow for core code sharing
	$site = page('site', '/');

	$packages = array_merge(
		get('packages'),
		get('extensions'),
		array(get('site-directory'), '.')
	);

	$basepaths = array();
	if (is_array($packages)) {
		foreach ($packages as $package) {
			$basepaths[] = $package.$includeDir;
			if ($site) $basepaths[] = $package.$site.$includeDir;
		}
	}

	return $basepaths;
}

/**
 * Return all relevant files to process.
 *
 * @param	$dirs		a list of directories to look in.
 * @param	$ext		a file extension for the target files.
 * @return	an array of files in the directories with the extension specified.
 */
function filesWithExt($dirs, $ext='.php') {
	$files = array();
	$pagefiles = array();

	foreach ($dirs as $dir) {
		if ($handle = @opendir($dir)) {
			unset($tempfiles);
			unset($temppagefiles);
			while ($entry = readdir($handle)) {
				$file = $dir.$entry;
				if (!file_exists($file) || is_dir($file)) continue;
				if (str_begins($entry, '.')) continue;
				$tempfiles[$entry] = $dir;
			}
			if(!empty($tempfiles)){
				ksort($tempfiles);
				$files=array_merge($files,$tempfiles);
			}
			closedir($handle);
			$sections = page('sections');
			$path = '';
			foreach ($sections as $section) {
				append($path, $section, '/');
				$file = $dir.get('pages').'/'.$path.$ext;
				if (!file_exists($file) || is_dir($file)) continue;
				$temppagefiles[$path.$ext] = $dir.get('pages').'/';
				$pagefiles[$path.$ext] = $dir.get('pages').'/';
			}
			if(!empty($temppagefiles)){
				ksort($temppagefiles);
				$pagefiles=array_merge($pagefiles, $temppagefiles);
			}
		}
	}

	return array_merge($files, $pagefiles);
}

/**
 * Process all includes for the specified type.
 *
 * @param	$type		the type of file to process { PHP | JS | CSS }.
 * @return	potentially a string of javascript code.
 */
function includeFiles($type, $skip_files=array()) { // type is { PHP | JS | CSS }
	ob_empty();
	switch ($type) {
		case 'PHP':
			$basedir = get('library');
			$ext = '.php';
			break;

		case 'JavaScript':
			header('Content-type: text/javascript; charset=UTF-8'); // required for correct operation
			$basedir = get('javascript');
			$ext = '.js';
			break;

		case 'CSS':
			header('Content-type: text/css; charset=UTF-8'); // required for correct operation
			$basedir = get('css');
			$ext = '.css';
			break;

		default:
	}

	$files = self::filesWithExt(self::getIncludeDirectories($basedir.'/'), $ext);

	// cascade order is critical for CSS files.
	// alphabetical order is necessary for other types to allow developers to determine the load order

	if ($type == 'JavaScript' ) $skip_files = get('skip-load');

	if ($type == 'CSS') {
		$cssconfig = get('site-directory').get('configuration').'/css.php';
		if (file_exists($cssconfig)) include $cssconfig; // if found
	}

	$siteDir = dirname(get('site-directory'));
	$realDir = dirname(realpath(get('site-directory')));

	$processedFiles = array();
	foreach ($files as $file => $dir) {
		$filename = $dir.$file; //str_replace('//', '/', $dir.$file);

		// should we skip this file?
		if (!file_exists($filename) ||
			in_array($filename, $processedFiles) ||
			(is_array($skip_files) && in_array($file, $skip_files)) ||
			('.'.pathinfo($filename, PATHINFO_EXTENSION) != strtolower($ext))) continue;

		$processedFiles[] = $filename;

		$siteURIs = false;
		$basename = basename($filename);
		$process = 'include';

		if (strpos($basename, '_') !== false) {	// process files with a _ as raw
			$process = 'raw';

		} else if (strpos($basename, '~') === false) {	// process files with a ~ as PHP
			$process = 'eval';
		}


		$label = "\n".'/* File: '.str_replace(array($realDir, $siteDir), '', $filename).' */'."\n\n";
		switch ($type) {
			case 'CSS':
				if (param('absolute', 'exists')) set('absolute-references', true);
				$siteURIs = true;
				break;

			case 'JavaScript':
				if ($process == 'include') $process = 'raw';
				if ($process == 'eval') $process = 'include';
				break;

			case 'PHP':
				$label = '';
				$process = 'include';
				break;

			default:
				continue;
		}
		if ($process != 'include') {
			$contents = file_get_contents($filename, true);

			if ($siteURIs) { // convert to site specific urls
				$count = preg_match_all('/url\(([^\)]*)\)/', $contents, $matches);
				$find = array();
				$replace = array();
				$match = $matches[1];
				if (count($match)) {
					foreach ($match as $file) {
						$newfile = SITE::file($file);

						$find[] = 'url('.$file.')';
						$replace[] = 'url('.$newfile.')';
					}
					$contents = str_replace($find, $replace, $contents);
				}
			}
		}

		echo $label;
		switch ($process) {
			case 'include': $success = include_once $filename; break;
			case 'raw':		echo $contents; break;
			case 'eval':	eval("\$css = \"$contents\";"); echo $css; break;
			default:
		}
	}

	if ($type == 'CSS') {
		$css = self::CSS(ob_get_contents());
		ob_clean();
		echo $css;
	}
}

/**
 * Converts possibly nested CSS source test into un-nested CSS.
 *
 * @param	$css	the CSS text.
 * @return	the converted CSS text.
 */
function CSS ($css) {
	function CSS_selector_decoder($select) {
		$css = htmlentities(trim($select[2]));
		$comment = '';

		$selector = '';
		while (($cstart = strpos($css, '/*')) !== false) {
			$selector .= substr($css, 0, $cstart);
			$css = substr($css, $cstart+2);

			$cend = strpos($css, '*/');

			$comment .= '<element type="comment">'.htmlentities(substr($css, 0, $cend)).'</element>';
			$css = substr($css, $cend+2);
		}
		$selector .= $css;

		$selector = trim($selector);

		if ($selector[0] == '@') {
			preg_match('/@([^\s]+)\s*(.*)/sx',$selector, $match);
			return $comment.$select[1]."\n".'<element type="at" name="'.$match[1].'" params="'.$match[2].'">';

		} else {
			return $comment.$select[1]."\n".'<element type="selector" name="'.$selector.'">';
		}
	}

	$xml = preg_replace_callback('/(\;|^|{|}|\s*)\s*([^{};]*?)\{/sx', 'CSS_selector_decoder', $css);

	$xml = preg_replace_callback('/@([^\s]+)\s(.*?);/', create_function('$select', 'return \'<at name="\'.$select[1].\'" params="\'.$select[2].\'" />\';'), $xml);

	$xml = preg_replace('/((\:|\+)[^;])*?\}/', "$1;}", $xml);

	$xml = preg_replace('/\;?\s*\}/', '</element>', $xml);

	$xml = '<'.'?xml version="1.0" ?'.'>'."\n".'<css>'.$xml.'</css>';

	$xml = @simplexml_load_string($xml);

	if ($xml) $css = html_entity_decode(self::XMLtoCSS($xml->children()));

	return $css;
}

/**
 * Converts the XML back to un-nested CSS (recursive)
 *
 * @param	$children	 SimpleXMLElement
 * @param	$parent_name parent name if any (default is null).
 * @return string
 */
function XMLtoCSS(SimpleXMLElement $children, $parent_name=null) {
	$output = '';

	foreach($children as $key => $value) {
		if ($key == 'element') {
			$type = (string)$value->attributes()->type;

			$content = (string)$value;

			switch ($type) {
				case 'selector':
					$selector_name = (string)$value->attributes()->name;

					if ($parent_name !== null) { // We need to append each parent to each child selector

						$parents 	= explode(',', $parent_name);
						$child 		= explode(',', htmlentities($selector_name));
						$new		= array();

						foreach ($child as $sv) {
							foreach ($parents as $pv) {
								if (strstr($sv, htmlentities('&'))) {
									$new[] = str_replace(htmlentities('&'), $pv, trim($sv));
								} else {
									$new[] = $pv.' '.trim($sv);
								}
							}
						}

						$selector_name = implode(','."\n", $new);
					}
					$content = trim($content);
					if (!empty($content))
						$output .= "\n".$selector_name.' {'.preg_replace('/\t+/', "\t", $content).'}'."\n";

					$output .= self::XMLtoCSS($value->element, $selector_name);
					break;

				case 'at':
					$output .= '@'.(string) $value->attributes()->name.' '.(string) $value->attributes()->params;
					$content = trim($content);
					$output .= ' {'.$content.self::XMLtoCSS($value->children()).'}'."\n";
					break;

				case 'comment':
					$output .= "\n".'/* '.html_entity_decode((string)$value).' */'."\n";
					break;

				default:
			}

		} else if ($key == 'at') {
			$output .= '@'.(string) $value->attributes()->name.' '.(string) $value->attributes()->params.';';
		}
	}

	return $output;
}

/**
 * Adds a value to a stack.
 *
 * @param	value	the value to add to the stack.
 * @return	a boolean indicating the operation was successful.
 */
function setStack($name, $value) {
	$stack = get($name);

	if (!is_array($stack)) $stack = array();
	$stack[] = $value;

	return set($name, $stack);
}

/**
 * Gets the top item in a stack and removes is from the stack.
 *
 * @return	the item.
 */
function getStack($name) {
	$stack = get($name);
	$value = '';

	if (is_array($stack)) $value = array_pop($stack);
	set($name, $stack);

	return $value;
}

/**
 * Determines if debug mode is enabled.
 *
 * @param	$newstate	an optional boolean that can change the state of debug on the fly.
 * @return	the state of debug.
 */
function debug($newstate='') {
	static $state;
	if (!isset($state)) $state = (self::script() != 'index.php');
	if (is_bool($newstate)) $state = $newstate;

	return $state;
}

/**
 * Finds a file for a 'site' if there is one or a non specific file.
 *
 * @param	$filename	the file to locate.
 * @param	$base		the base directory name.
 * @param	$default	the text to return if the file is not found.
 * @return	the actual location of the target file if it was found.
 * @see		site
 * @see		get
 */
function file ($filename, $base='', $default='') {

	$site = page('site', '/');
	if ($base != '') $base .= '/';

	$siteDirs = array_merge(array(get('public-directory'), get('site-directory')), get('packages'), get('extensions'));
	foreach ($siteDirs as $path) {
		if ($site) $filedirs[] = $path.$site.$base;
		$filedirs[] = $path.$base;
	}

	foreach ($filedirs as $filedir) if (file_exists($filedir.$filename)) {
		return $filedir.$filename;
	}
	return $default;
}

/**
 * Returns the valid form for the specified doctype and version.
 *
 * @param  $type		the name of the type (HTML and XHTML).
 * @param  $version		the version for the type specified.
 * @return	the doctype tag.
 */
function doctype($type, $version) {
	$doctype = get('document-types');

	$result = "<"."?xml version=\"1.0\" encoding=\"UTF-8\"?".">\n".$doctype[$type][$version]."\n";
	$result .= '<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">'."\n";
	return $result;
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
	// output head section
	$result = tag('head', '');
	append($result, self::metatags($version), "\n");

	$s = page('site', ':');
	$p = page();

	// output css and icon links
	$scripturi = page('uri');
	$abs = '';
	if (get('absolute-references')) $abs = '&amp;absolute';

	append($result, tag('link', 'rel:stylesheet | type:text/css | href:'.str_replace('&', '&amp;', $scripturi).'&amp;CSS'.$abs.' | media:screen, print'), "\n");
	append($result, script('type:text/javascript | src:'.str_replace('&', '&amp;', $scripturi).'&amp;JavaScript'.$abs, ''));

	// process extra links
	$metadata = get('site-metadata');
	$md = array_extract($metadata, array($s), array_extract($metadata, array($version), $metadata['default']));

	$links = array_extract($md, array('links'), '');
	if (is_array($links)) {
		foreach ($links as $rel => $details) {
			$details['rel'] = $rel;
			append($result, tag('link', $details), "\n");
		}
	}

	$rss = array_extract(get('rss-feed'), array($p), false);
	if ($rss) {
		if (!str_begins($rss, 'feed')) $rss = '?'.$rss;
		append($result, tag('link', 'rel:alternate | type:application/rss+xml | title:RSS | href:'.$rss), "\n");
	}
	$refresh = array_extract(get('auto-refresh'), array($p), '');
	if ($refresh) append($result, tag('HTML', 'http-equiv:refresh | content:'.$refresh), "\n");

	append($result, tag('head', '/'), "\n");

	return $result;
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
	$pt = page('title'); // this needs to be first to force the virtual page processing

	$site = page('site');
	$metadata = get('site-metadata', array());
	if (array_key_exists($site, $metadata)) {
		$page = $metadata[$site];
	} else {
		if (!array_key_exists($version, $metadata)) $version = 'default';
		$page = $metadata[$version];
	}

	$pagetitle = $page['title'];
	append($pagetitle, $pt, ': ');
	$result = tag('title', '', $pagetitle);
	append($result, meta('http-equiv:Content-Type | content:text/html; charset=utf-8'), "\n");
	unset($page['title']);

	foreach ($page as $name => $field) {
		if (!is_array($field)) append($result, meta('name:'.$name.' | content:'.$field), "\n");
	}

	return $result;
}

} // ***** end of SITE class *****


/**
* Get configuration or request level settings that are cached.
* This provides an equivalent to globals without the downsides.
*
* @param	$name		the name of the element requested.
* @param	$default	the value to return if the element is not set.
* @return	the value of the configured or set value.
* @see		set
*/
function get($name, $default='') {
	return set($name, $default, 'get');
}

/**
* Get/Set/Clear a value that is cached.
* This provides an equivalent to globals without the downsides.
*
* @param	$name		the name of the element being processed.
* @param	$value		the value to use if the mode is set.
* @param	$mode		the mode of the operation { get | set(default) | clear }.
* @see		site
*/
function set($name, $value='', $mode='set') {
	static $settings;
	static $request_data = array(); // this can override values in site.php

	// include our site's configuration
	if ($mode == 'load') {
		$all = $value;

		$basedir = get('site-directory').'configuration/settings/';

		$site = page('site');

		$confdirs = array($basedir);
		if ($site != '') $confdirs[] = get('site-directory').$site.'/configuration/settings/';

		foreach ($confdirs as $confdir) {
			if ($handle = @opendir($confdir)) {
				while ($entry = @readdir($handle)) {
					if (!is_dir($confdir.$entry)) {
						include $confdir.$entry;
						$all = array_merge($all, $settings);
					}
				}
				@closedir($handle);
			}
		}

		// now load any package settings that make exist, but preference goes to the local settings
		if (array_key_exists('packages', $all)) {
			$confdirs = array_merge($all['packages'], $all['extensions']);
			foreach ($confdirs as $confdir) {
				$confdir .= $all['configuration'].'/settings/';
				if ($handle = @opendir($confdir)) {
					while ($entry = @readdir($handle)) {
						if (!is_dir($confdir.$entry)) {
							include $confdir.$entry;
							$all = array_merge($settings, $all); // packages values are overridden by site values
						}
					}
					@closedir($handle);
				}
			}
		}
		$settings = $all;
	}

	switch($mode) {
		case 'get':
			return array_extract($request_data, array($name), array_extract($settings, array($name), $value));
			break;

		case 'set':
			if ($value != NULL) {
				$request_data[$name] = $value;
				break;
			}

		case 'clear':
			unset($request_data[$name]);
			break;

		default:
	}
}

/**
* This function sets or resets session information.
*
* @param	$name	the name of the item.
* @param	$value	the value to set or clear.
* @param	$serialize	should the value be seairlized before storing?
*/
function set_session ($name, $value, $serialize=true) {
	@session_start();
	if (empty($value) && array_key_exists($name, $_SESSION)) {
		unset($_SESSION[$name]);
	} else {
		if ($serialize) $svalue = serialize($value);
		$_SESSION[$name] = $svalue;
	}
	set('session:'.$name, $value); // save internally in PHP
}

/**
* This function reads session information.
*
* @param	$name	the name of the item.
* @return	the value of the item.
*/
function get_session ($name) {
	@session_start();
	$data = get('session:'.$name, null); // retrieve from internal PHP store first
	if ($data == null) {
		if (array_key_exists($name, $_SESSION)) {
			$data = $_SESSION[$name];
			while (($result = @unserialize($data)) !== false) $data = $result;
			set('session:'.$name, $data);
		} else {
			$data = '';
		}
	}

	return $data;
}

/**
* Return or set information about a page.
*
* @param	$part	the part to process.
*					Can be any of:
*						title		the HTML title
*						enviroment	environmental variables to be added to forms and links
*						parameters	the passed parameters as an array
*						host
*						hostpage
*						url
*						server
*						uri
*						fullHost
*						fullPage
*						request
*						site
*						value
*						sections
*						path
*						prefix
*						suffix
*						full, fullname
*						default
* @param	$value	an optional value.
* @return	returns the requested part or sets the requested part.
*/
function page($part='name', $value=null) {
	switch ($part) {
		case 'title':
			if (is_string($value)) {
				set('page-title', $value);
				return;
			}

			// locate the page file
			$pageFile = SITE::file(page().'.html', get('pages'));
			$title = get('page-title');

			// if title is not yet set use the patterns to locate a suitable title.
			if ($title == '') {
				if ($pageFile) {
					$pagecode = file_get_contents($pageFile);

					$titlepatterns = array(
						   '/^[\s]*<!-- ([^\r]*) -->/',
						   '/<h1>([^<]+)<\/h1>/',
						   '/<h2>([^<]+)<\/h2>/',
						   "/h1\('[\S,\s]*',[\s]*'([^']+)'\)/",
						   "/h2\('[\S,\s]*',[\s]*'([^']+)'\)/",
						   "/HTML::setPageTitle\(('[^']*)'\)/",
						   "/HTML::pageTitle\(('[^']*')\)/",
						   "/HTML::title\(('[^']*')\)/",
					);

					foreach ($titlepatterns as $pattern) {
						if (preg_match($pattern, $pagecode, $title)) {
							$title = $title[1];
							break;
						}
					}

					if ($title == array()) $title = '';

					if (str_begins($title, '$')) @eval($title);
				}
			}
			return $title;

		case 'environment':
/*
			if ($value != null) {
				ini_set('url_rewriter.tags', 'a=href,area=href,frame=src,form=,fieldset=');
				$evp = strtoarray($value);
				foreach ($evp as $name => $val) {
					print_r(ob_list_handlers());
					display(output_add_rewrite_var($name, param($name)));
				}
			} else {
				output_reset_rewrite_vars();
			}
			break;
*/
			if (func_num_args() == 1) {
				return get_session('environment', array());
			} else {
				set_session('environment', $value);
			}
			break;

		case 'parameters':
			$params = get('request-parameters', null);

			if (!is_array($params)) {
				if ($params == null) {
					$params = array();
					foreach ($_GET as $p => $v) $params[$p] = filter_input(INPUT_GET, $p, FILTER_SANITIZE_STRING);
					foreach ($_POST as $p => $v) {
						if (is_array($v)) {
							$params[$p] = $v;
						} else {
							$params[$p] = filter_input(INPUT_POST, $p, FILTER_SANITIZE_STRING);
							if ($params[$p] == null) $params[$p] = $v;
						}
					}
					// include environment params if actual parameters don't exist, not done as a merge to retain page name as first
					$evp = page('environment');
					if (is_array($evp)) foreach ($evp as $p => $v) if (!array_key_exists($p, $params)) $params[$p] = $v;

					// remove parameters passed in the $value parameter
					if ($value != null) foreach((array)$value as $p) if (array_key_exists($p, $params)) unset($params[$p]);

					set('request-parameters', $params);
				}
			}
			return $params;

		case 'host':
			return $_SERVER['HTTP_HOST'];

		case 'hostpage':
			return page('host').page('path');

		case 'url':
			return LINK::url('', page('parameters'));

		case 'server':
			$secure = 'inherit';
			if ($value != null) $secure = $value;
			$server = 'http';
			if ((array_key_exists('HTTPS', $_SERVER) && ($_SERVER['HTTPS'] == 'on') && ($secure == 'inherit')) || ($secure === true)) $server .= 's';

			return $server.'://'.$_SERVER['HTTP_HOST'].'/';

		case 'uri':
			$uri = get('uri');
			if ($uri == '') {
				$secure = 'inherit';
				if ($value != null) $secure = $value;
				$uri = page('server', $secure).page('request');
				set('uri', $uri);
			}

			return $uri;

		case 'fullPage':
			$secure = 'inherit';
			if ($value != null) $secure = $value;
			$pageURI = page('uri', $secure); // get the whole request (setting http/https as needed

			// look for where the page name ends
			$eq  = strpos($pageURI, '=');
			$amp = strpos($pageURI, '&');
			if ($eq != 0) {
				if ($amp != 0) {
					$bp = min($eq, $amp); // find the first of either
				} else {
					$bp = $eq; // use the equal
				}
			} else {
				$bp = $amp; // use the ampersand
			}
			if ($bp != 0) $pageURI = substr($pageURI, 0, $bp); // if $bp is set, use it

			return $pageURI;

		case 'request':
			$request = get('request-uri');
			if ($request == '') {
				$request = substr($_SERVER['REQUEST_URI'], 1);

				if (!str_contains($request, '?')) { // just a host name and perhaps a script name
					$request .= '?'.get('default-page');

				} else if ($request == '?') { // just a '?'
					$request = '?'.get('default-page');
				}
				set('request-uri', $request);
			}

			return $request;

		case 'site':
			$site = $_SERVER['QUERY_STRING'];
			$pieces = array('&', '=', ':');
			$site = urldecode($site);

			foreach ($pieces as $piece) {
				$parts = explode($piece, $site);
				$site = array_shift($parts);
			}
			if (!count($parts)) $site = '';
			if ($site == 'cron') $site = '';

			if ($site != '') $site .= $value;

			return $site;

		case 'value':
			return param(page('fullname'), 'value', $value);

		case 'sections':
			return explode('/', page());

		case 'path':
			return substr(dirname($_SERVER['SCRIPT_NAME']), 1).'/';

		case 'prefix':
			return array_shift(page('sections'));

		case 'suffix':
			return array_pop(page('sections'));

		case 'script':
			return basename($_SERVER['SCRIPT_NAME']);

		case 'name':
			$name = get('page');
			if ($name == '') {
				$name = array_shift(array_keys(page('parameters')));

				@list($site, $name) = explode(':', $name, 2);

				if ($name == '') $name = $site;

				if ($name == '') { // no parameters were passed, insert default values
					$name = get('default-page');
					set('query-string', $name);
					set('request-parameters', array($name => ''));
				}

				set('page', $name);
			}

			return $name;

		case 'full':
		case 'fullname':
			return page('site', ':').page('name');

		default:
			if ($value == null) {
				$partfile = get($part, false);

				if ($partfile !== false) return $partfile; // already established

				$searchpath = page('sections');

				$partlist = array();
				$path = '';
				foreach ($searchpath as $page) {
					$partlist[] = get('parts').'/'.$part.'/'.get('pages').'/'.$page.'.html';
					append($path, $page, '/');
					$partlist[] = get('parts').'/'.$part.'/'.get('pages').'/'.$path.'.html';
				}
				$partlist = array_reverse($partlist);

				$siteDir = get('site-directory');
				foreach ($partlist as $partfile) {
					$partfile = $siteDir.$partfile;
					if (file_exists($partfile) && is_file($partfile)) {
						set($part, $partfile);
						return $partfile;
					}
				}
				$partfile = get('default-'.$part, false);

				return $partfile;
			} else {
				set($part, $value);
			}
			break;
	}

}

/**
* get the value of a parameter in the query string.
*
* @param	$var	The name of the parameter(s) to check. When multiple fields are named as name[field] these can be collectively returned using just name.
*					If an array of names is passed they are returned as a keyed array.
* @param	$mode	the mode you want: { exists | value | default value } default is 'value'.
* @param	$def	an optional default value to return if the parameter was not passed. This can be passed as the second parameter instead.
* @return			the value of the parameter or the default value.
* @see		page
*/
function param ($var, $mode='value', $def='') {
	$params = page('parameters');

	// special case when passed an array of parameters to get the values of
	if (is_array($var)) {
		switch($mode) {
			case 'exists':
				foreach ($var as $v) if (array_key_exists($v, $params)) return true;
				return false;
			case 'value':
				$mode = $def;
			default:
				$result = array();
				foreach ($var as $name) {
					$value = $mode;
					if (array_key_exists($name, $params)) $value = $params[$name];
					$result[$name] = $value;
				}

				return $result;
		}
	} else {
		$exists = array_key_exists($var, $params);
	}

	switch ($mode) {
		case 'exists':
			return $exists;

		case 'value':
			$mode = $def;

		default:
			$val = $mode; // preset it to the default

			if ($exists) {
				if ($params[$var] != '') $val = $params[$var];
			} else {
				// see if this is a composite field or a collective one
				if (($fb = strpos($var, '[')) !== false) { // the actual values are returned as array entries
					eval(str_replace(array('[', ']'), array('[\'', '\']'), '$val = $params['.substr($var, 0, $fb).']'.substr($var, $fb).';'));
					return $val;
				}

				$vals = array();
				$prefix = $var.':';
				$prefixlen = strlen($prefix);

				foreach ($params as $param => $value) {
					if (str_begins($param, $prefix)) { // we found an entry of the form var:subvar
						$vals[substr($param, $prefixlen)] = $value;
					}
				}
				if (!$vals) return $val;

				$val = $vals;

				if (is_array($val)) {
					if (array_key_exists('format', $val)) {
						$format = $val['format'];
						switch ($format) {
							case 'datetime': // date and time
								$val = mktime ($val['hour'], $val['minute'], 0, $val['month'], $val['day'], $val['year']);
								break;

							case 'timestamp': // just a date
								$val = mktime (0, 0, 0, $val['month'], $val['day'], $val['year']);
								break;

							case 'optionaltimestamp': // just a date
								if (($val['optionalmonth'] == '00') || ($val['optionalday'] == '00') || ($val['optionalyear'] == '00')) {
									$val = '';
								} else {
									$val = mktime (0, 0, 0, $val['optionalmonth'], $val['optionalday'], $val['optionalyear']);
								}
								break;

							case 'time': // just a time
								$base = $val['base'];
								if ($base == 'now') {
									$basetime = getdate(time());
								} else {
									$baseTS = param($val['base'], 'value', time());
									if (is_array($baseTS)) {
										$basetime = $baseTS;
									} else {
										$basetime = getdate($baseTS);
									}
								}
								$val = mktime ($val['hour'], $val['minute'], 0, $basetime['mon'], $basetime['mday'], $basetime['year']);
								break;

							default:
						}
					} else {
						$val = '';
						foreach ($vals as $name => $item) append($val, $item, ' ');
					}
				}
			}

			$val = str_replace('&#39;', "'", $val);

			return $val;
	}

}

/**
* Displays of a variable or literal in a formatted display.
*
* @param  $var		the data to format and display.
*/
function display ($var, $label='', $where='', $raw=false) {
	$result = '';
	//	if ($label != '') $result .= $label.'<br />';

	$type = gettype($var);

	if ($type == 'string') {
		if (is_numeric($var) && (($var === 0) || (intval($var) === $var)))  {
			$type = 'integer';

		} else if ($var === '') {
			$type = 'empty';

		} else if (str_begins($var, '$')) {
			$remainder = substr($var, 1);
			if (is_numeric($remainder) && (strlen($remainder) > 0)) {
				$type = 'money';
				$var = $remainder;
			}
		}
	}

	$header = $type;

	$align = 'left';
	$color = 'black';
	switch ($type) {
		case 'array':
			$rslt = '';
			append($rslt, '<table class="basictable">');
			// evaluate this array and see if it is a repeated-array
			$repeated = true;

			$keys = array_keys($var);
			$keykeys = array_keys($keys);
			if ($keys != $keykeys) {
				$repeated = false;
			} else {
				$lastrowkeys = false;
				foreach ($keys as $key) {
					if (!is_array($var[$key])) {
						$repeated = false;
						break;
					}
					$rowkeys = array_keys($var[$key]);
					if (count($rowkeys) < 2) {
						$repeated = false;
						break;
					}

					if (!$lastrowkeys) {
						$lastrowkeys = $rowkeys;
					} else if ($lastrowkeys != $rowkeys) {
						$repeated = false;
						break;
					}
				}
			}

			// if repeated is still true we have a winner
			if ($repeated) {
				if (count($var) == 0) {
					$var = 'empty array '.$name;
					break;
				}

				$colspan = count($var);

				$raw = true;
				append($rslt, '<tr class="titles">');
				$vr = $var;
				$vf = array_shift($vr);
				foreach (array_keys($vf) as $item) append($rslt, '<td class="title" title="array key">'.$item.'</td>');
				append($rslt, '</tr>');

				foreach ($var as $repeat) {
					append($rslt, '<tr>');
					foreach ($repeat as $key => $item) {
						$v = display($item, '', true, $raw);
						$style = '';
						if (intval($v) === $v) $style = ' style="text-align: right;"';
						append($rslt, '<td'.$style.'>'.display($item, '', true, $raw).'</td>');
					}
					append($rslt, '</tr>');
				}
				append($rslt, '</table>');
				$var = $rslt;
				break;
			}

			if ($label != '') append($rslt, '<tr class="title"><td class="title" colspan="2">'.$label.'</td></tr>');
			foreach ($var as $key => $value) {
				append($rslt, '<tr><td class="key" title="array key" style="margin: 0 !important;">'.$key.'</td><td>'.display($value, $key, true, $raw).'</td></tr>');
			}
			append($rslt, '</table>');
			$var = $rslt;
			break;

		case 'object':
			$rslt = '';
			append($rslt, table());
			append($rslt, tr('class:title', td('class:title | colspan:2', 'object')));
			$objVars = get_object_vars($var);

			if (is_object($var)) {
				foreach ($objVars as $key => $value) {
					if (is_string($value)) $value = (trim($value) == '') ? '[empty]' : $value;
					append($rslt, tr().td('class:'.$key));
					if (in_array(gettype($value), array('array', 'boolean', 'object', 'resource'))) $value = display($value, '', true, $raw);
					append($rslt, td('', $value).tr('/'));
				}
				$arrObjMethods = get_class_methods(get_class($var));
				foreach ($arrObjMethods as $key => $value) append($rslt, tr('', td('class:key', $value).td('', '[method]')));
			} else {
				append($rslt, tr('', td('class:error', 'Invalid type').td('', 'object')));
			}

			append($rslt, table('/'));
			$var = $rslt;
			break;

		case 'xml':
			$var = display(xmltoarray($var), 'XML', true);
			break;

		case 'resource':
			$type = get_resource_type($var);
			switch ($type) {
				case 'xml':
					$var = display($var, 'XML', true);
					break;

				case 'gd':
					$var = table(tr('', td('colspan:2', 'GD resource')).
								 tr('', td('class:key', 'Width').td('', imagesx($var))).
								 tr('', td('class:key', 'Height').td('', imagesy($var))).tr('', td('class:key', 'Colors').td('', imagecolorstotal($var)))
								 );
					break;

				case 'fbsql result':
				case 'msql query':
				case 'mssql result':
				case 'mysql result':
				case 'pgsql result':
				case 'sybase-ct result':
				case 'sybase-db result':
					$rslt = '';
					$db = current(explode(' ', $type));
					$rows = call_user_func($db.'_num_rows', $var);
					$fields = call_user_func($db.'_num_fields', $var);

					append($rslt, table());
					append($rslt, tr('class:title', td('class:title | colspan:'.($fields+1), $db.' result')));
					append($rslt, tr().td('class:key', '&nbsp;'));
					for ($i = 0; $i < $fields; $i++) {
						$field[$i] = call_user_func($db.'_fetch_field', $var, $i);
						append($rslt, td('class:key', $field[$i]->name));
					}
					append($rslt, tr('/'));

					for ($i = 0; $i < $rows; $i++) {
						$row = call_user_func($db.'_fetch_array', $var, constant(strtoupper($db).'_ASSOC'));
						append($rslt, tr().td('class:key', $i+1));
						for ($k = 0; $k < $fields; $k++) {
							$tempField = $field[$k]->name;
							$fieldrow = $row[($field[$k]->name)];
							$fieldrow = ($fieldrow == '') ? '[empty]' : $fieldrow;
							append($rslt, td('', $fieldrow));
						}
						append($rslt, tr('/'));
					}
					append($rslt, table('/'));

					if ($rows > 0) call_user_func($db.'_data_seek', $var, 0);
					$var = $rslt;
					break;

				default:
					$var = table('', tr('class:title', td('class:title', 'resource')).tr('', td('', $type)));
			}
			break;

		case 'boolean':
			if ($var == true) {
				$var = 'TRUE';
				$color = 'green';
			} else {
				$var = 'FALSE';
				$color = 'red';
			}
			break;


		case 'datetime':
			$type = 'datetime: '.$var;
			$var = date('r', strtotime($var));
			break;

		case 'empty':
			$var = '[empty]';
			break;

		case 'NULL':
			$var = '[null]';
			break;

		case 'string':
			if (!$raw) $var = str_replace(array("\n", '<br /><br />'), '<br />', str_replace(' ', '&nbsp;', htmlentities($var)));
			break;

		case 'money':
			$var = '$'.money_format('%!.2n', $var);

		default:
			$align = 'right';
	}

	append($result, $var);

	if (($where == 'return') || ($where === true)) {
		return $result;

	} else if ($where == '') {
		echo $result;

	} else {
		//FILE::append('log/'.$where.'.html', $result);
	}
}

/**
* Determines if debug mode is enabled or can save or display values.
*
* @param	$value	a value to process or a boolean that can change the state of debug on the fly.
* @param	$label	the label to identify a value
* @param	$mode	can be add|dump|trace|show
* @param	$where	default is to echo the result, 'return' returns the result
* @return	the state of debug and set the error reporting level, or a display of the values.
*/
function debug ($value=null, $label='', $mode='add', $where='') {
	static $store = array();

	// determine current debug state
	$state = @unserialize(get_session('debug-enabled', null));
	if ($state == null) { // never been set
		$state = (basename($_SERVER['SCRIPT_NAME']) == 'debug.php');
		set_session('debug-enabled', $state);
		error_reporting($state);
	}

	$numargs = func_num_args();
	if ($numargs == 0) {
		$state = unserialize(get_session('debug-enabled', 0));
		return $state;
	}

	if (($numargs == 1) && is_bool($value)) {
		set_session('debug-enabled', $value);
		error_reporting($value);
		return $value;
	}


	$backtrace = debug_backtrace();
	$caller = array_shift($backtrace);
	$cwd = dirname(getcwd());

	switch ($mode) {
		case 'add':
			$store[$label] = $value;
			break;

		case 'dump':
			if (!$state) break;

			$result = div('class:debug', display($store, 'dump', 'return'));
			$store = array();
			if ($where == 'return') return $result;
			echo $result;
			break;

		case 'trace':
			$steps = array();
			$steps[] = array('line' => $caller['line'], 'file' => str_replace($cwd, '', $caller['file']), ' ' => ' Trace started');

			foreach ($backtrace as $entry) {
				$entry = array_merge(array('function' => '', 'class' => '', 'object' => '', 'type' => '', 'args' => array()), $entry);
				$args = '';
				foreach ($entry['args'] as $arg) append($args, '\''.$arg.'\'', ', ');
				if ($entry['object'] != '') {
					$element = '('.$entry['class'].')'; //.(string)$entry['object'];
				} else {
					$element = $entry['class'];
				}

				$steps[] = array('line' => $entry['line'], 'file' => str_replace($cwd, '', $entry['file']), ' ' => $element.$entry['type'].$entry['function'].'('.$args.')');
			}
			$store[$label] = $steps;
			break;

		case 'count':
			return count($store);
			break;

		case 'show':
		default:
			if (!$state) break;

			$result = div('class:debug', display($value, $label, 'return'));
			if ($where == 'return') return $result;
			echo $result;
	}
}

/**
* This function forces an HTTP redirect to the specified value if not already so.
*
* @param	$secure	the state of SLL desired. (default is true)
*/
function forceSSL($secure=true) {
	if (array_key_exists('HTTPS', $_SERVER) !== $secure) {
		LINK::redirect(page('request'), array('secure' => $secure));
	}
}

?>

