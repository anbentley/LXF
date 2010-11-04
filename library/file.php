<?php

/**
 * FILE provides methods intended to simplify file access.
 * 
 * @author	Alex Bentley
 * @history	4.1		I've reverted back to the hard coded list of mime types because the 'magic' version' appears broken
 *			4.0		supports unzip of a file.
 *			3.12	update to serve function to allow for a filename change during transfer
 *			3.11	minor addition to resources
 *			3.10	code cleanup
 *			3.9		simplified code in mime function
 *			3.8		fix to get
 *			3.7		fix to getList to avoid warnings
 *			3.6		added new resource functions
 *			3.5		updated name function
 *			3.4		new function determineLMD
 *			3.3		minor change to write function
 *			3.2		update to name function to remove the _ to space conversion
 *			3.1		updated serve feature
 *			1.0		initial release
 */
class FILE {

/**
 * Returns a random file from a directory.
 *
 * @param	$dir	the directory to look in.
 * @return			one of the filenames in the directory.
 */
 
function random($dir) {
	$files = self::getlist($dir);
	return $files[array_rand($files)];
}

/**
 * Returns a list of files from a directory.
 *
 * @param	$dir		the directory to look in.
 * @param	$details	an optional keyed array of settings to use that override defaults.
 * @details	order-by;			name;	either name or LMD
 *			recursive;			false;	should we look in subdirectories?
 *			file-ext;			*;		what extensions are we looking for?
 *			ignore;				;		what file names should we exclude?
 *			absolute;			false;	are directories relative or absolute?
 *			include-directory;	false;	include directory name in the result?
 * @return	an array of files.
 */
function getlist($dir, $details=array()) {
	$defaults = array(
		'order-by'	=>	'name', // name or LMD
		'recursive'	=>	false,
		'file-ext'	=>	'*',
		'ignore'	=>	array(),
		'absolute'	=> false,
		'include-directory' => false,
	);
		
	$details = smart_merge($defaults, $details);
	if (!is_array($details['file-ext'])) $details['file-ext'] = array($details['file-ext']);
	
	$skip = array('.', '..', '_notes', '.DS_Store');

	$list = NULL; // start with nothing
	$dirs = NULL;
	
	$realdir = '';
	if (!$details['absolute']) $realdir .= './';
	$realdir .= $dir;
	
	if ($handle = @opendir($realdir)) {
		while ($entry = @readdir($handle)) {
			if (in_array($entry, $skip) || in_array($entry, (array)$details['ignore'])) continue; // dont include unimportant files

			$valid = false;
			if (in_array('*', $details['file-ext'])) {
				$valid = true;
			} else if (is_dir($dir.'/'.$entry) && in_array('dir', $details['file-ext'])) {
				$valid = true;
			} else if (in_array(self::ext($entry), (array)$details['file-ext'])) {
				$valid = true;
			}
			if ($details['include-directory']) $entry = $dir.'/'.$entry;
			if ($valid) $list[] = $entry;			
		}
		@closedir($handle);
		
	}

	if (!is_array($list)) return $list;
	
	switch ($details['order-by']) {
		case 'name':
			natcasesort($list);
			break;
	
		case 'lmd':
		case 'LMD':
			$reorder = $list;
			$switched = true;
			while ($switched) {
				$switched = false;
				for ($i = 0; $i < count($reorder)-1; $i++) {
					if (filemtime($dir.'/'.$reorder[$i]) < filemtime($dir.'/'.$reorder[$i+1])) {
						$temp = $reorder[$i];
						$reorder[$i] = $reorder[$i+1];
						$reorder[$i+1] = $temp;
						$switched = true;
					}
				}
			}
			$list = $reorder;
			break;
		
		default:
	}
	
	$flist = array();
	foreach($list as $entry) {
		$path = $dir.'/'.$entry;
		if (is_dir($path)) {
			$contents = '';
            if ($details['recursive']) { // get recursively
				$contents = self::getlist($path, $details);
				if ($contents == array()) $contents = ''; // nothing was returned
			}
			$flist[$path] = $contents;
		} else {
			if (!in_array($entry, $details['ignore']) && !in_array($entry, $skip) && !str_begins($entry, '.')) {
				$flist[] = $entry;
			}
		}
	}

	return $flist;
}

/**
 * Search a set of directories for a file.
 *
 * @param	$file		the file to look for.
 * @param	$dir		the directories to look in.
 * @return	the filename of the located file if found.
 */
function find ($file, $dirs) {
	if (!file_exists($file)) {		
		foreach ($dirs as $dir) {
			if (file_exists("$dir/$file")) {
				$file = "$dir/$file";
				break;
			}
		}
	}
	
	return $file;
}

/**
 * Returns the file extension if there is one for a file.
 *
 * @param	$value		the filename to process.
 * @return	the extension of the filename if any.
 */
function ext($value) {
	if (0 == ($dot = strrpos($value, '.'))) return '';
	
	return strtolower(substr($value, $dot+1));
}

/**
 * Returns the file without the extension if there is one for a file.
 *
 * @param	$value		the filename to process.
 * @return	the filename without the extension.
 */
function name($value) {
	$value = self::filename($value); // remove the path if there is one
	
	if (0 != ($dot = strrpos($value, '.'))) $value = substr($value, 0, $dot);
	
	return $value;
}

/**
 * Returns the path for a file.
 *
 * @param	$value		the filename to process.
 * @return	the path without the filename.
 */
function path($value) {
	$slash = strrpos($value, '/');
	if ($slash !== false) $value = substr($value, 0, $slash);
	
	return $value;
}

/**
 * Returns the file portion of a filename.
 *
 * @param	$value		the filename to process.
 * @return	the name without the path.
 */
function filename($value) {
	$slash = strrpos($value, '/');
	if ($slash !== false) $value = substr($value, $slash+1);
	
	return $value;
}

/**
 * Returns a human readable file size.
 *
 * @param	$file		the filename to process.
 * @return	the size of the file.
 */
function size($file) {
	return normalize(filesize($file));
}

/**
 * Writes data to a file. It will overwrite an existing file.
 *
 * @param	$filename		the filename to write to.
 * @param	$newdata		the data to write.
 * @param	$mode			the write mode { w | a }.
 * @return	a boolean indicating success of the write.
 */
function write($filename, $newdata, $mode='w') {
	$f = @fopen($filename, $mode);
	$result = false;
	if ($f) {
		$result = @fwrite($f, $newdata);
		@fclose($f);
	}
	
	return $result;
}

/**
 * Writes data to a file. It will append to an existing file.
 *
 * @param	$filename		the filename to write to.
 * @param	$newdata		the data to write.
 * @return	a boolean indicating success of the write.
 * @see		FILE::write()
 */
function append($filename, $newdata) {
	return self::write($filename, $newdata, 'a');
}

/**
 * Reads data from a file.
 *
 * @param	$filename		the filename to write to.
 * @return	the read data.
 */
function read($filename) {
	$f = @fopen($filename, 'r');
	$data = false;
	if ($f) {
		$data = @fread($f, filesize($filename));
		@fclose($f);  
	}
	
	return $data;
}

/**
 * Primary method to obtain a resource.
 *
 * @param	$name		logical resource identifier.
 * @param	$type		resource type.
 * @param	$default	values to override resource defaults
 * @return	the desired resource or default value.
 * @see		array_extract
 * @see		resourceDefaults
 */
function get($name, $type, $default='') {
	$options = array_merge(self::resourceDefaults(), get('resources', array()));
	$name = trim($name); // insure no extra spaces
	
	// perform any mapping if necessary
	// map to a new type	
	$type = array_extract($options, array('map', 'type', $type), $type);
	
	// map to a new file
	$name = array_extract($options, array('map', $type, $name), $name);
	
	$rsrcDir = array_extract($options, array($type, 'dir'), $type);
	$ext     = array_extract($options, array($type, 'ext'), $type);

	if ($rsrcDir != '') $rsrcDir .= '/';
	if ($ext != '') $ext = ".$ext";
	
	$file = "{$rsrcDir}{$name}{$ext}";
	
	return SITE::file($file);
}

/**
 * Default values for predefined resource types.
 *
 * @details	icon;	default => , dir => images/icons, ext => gif;		finds icons
 *			page;	default => nofile.html, dir => pages, ext => html;	finds page files
 *			pages;	default => nofile.html, dir => pages, ext => ;		allows for directory lookups
 *			part;	default => , dir => parts, ext => html;				finds part files
 *			parts;	default => , dir => parts, ext => ;					allows for directory lookups
 *			image;	default => , dir => images, ext => jpg;				finds image files
 *			images;	default => , dir => images, ext => ;				allows for directory lookups
 *			map;	types => opicon => icon, icon => flash => ext/Flash;	allows for resource mapping
 *
 * @return	the array of defaults.
 * @see		get
 */
function resourceDefaults() {
	return array(
		'icon' =>	array('default' => '', 'dir' => 'images/icons', 'ext' => 'gif'),
		'page' =>	array('default' => 'nofile.html', 'dir' => 'pages', 'ext' => 'html'),
		'pages' =>	array('default' => 'nofile.html', 'dir' => 'pages', 'ext' => ''), // allows for directory lookups
		'part' =>	array('default' => '', 'dir' => 'parts', 'ext' => 'html'),
		'parts' =>	array('default' => '', 'dir' => 'parts', 'ext' => ''), // allows for directory lookups
		'image' =>	array('default' => '', 'dir' => 'images', 'ext' => 'jpg'),
		'images' =>	array('default' => '', 'dir' => 'images', 'ext' => ''), // allows for directory lookups
		'map' => array(
			'types' => array('opicon' => 'icon'),
			'icon' => array('flash' => 'ext/Flash'),
		),
	);
}

/**
 * Helper method for icon resources.
 *
 * @param	$name		logical name of resource.
 * @param	$default	default values if none preconfigured (generally unused).
 * @return	the value for this resource type.
 * @see		get
 */
function icon ($name, $default='') {
	return self::get($name, 'icon', $default);
}

/**
 * Helper method for page resources.
 *
 * @param	$name		logical name of resource.
 * @param	$default	default values if none preconfigured (generally unused).
 * @return	the value for this resource type.
 * @see		get
 */
function page ($name, $default='') {
	return self::get($name, 'page', $default);
}

/**
 * Helper method for part resources.
 *
 * @param	$name		logical name of resource.
 * @param	$default	default values if none preconfigured (generally unused).
 * @return	the value for this resource type.
 * @see		get
 */
function part ($name, $default='') {
	return self::get($name, 'part', $default);
}

/**
 * Helper method for image resources.
 *
 * @param	$name		logical name of resource.
 * @param	$default	default values if none preconfigured (generally unused).
 * @return	the value for this resource type.
 * @see		get
 */
function img ($name, $default='') {
	return self::get($name, 'image', $default);
}

/**
 * Maps resources from logical to physical and back.
 * Uses configuration values from 'resources'.
 *
 * @param	$dir	the directory to process.
 * @param	$tological	a boolean to indicate if the transform is physical to logical.
 * @return	the other form of directory if located.
 */
function dir($dir, $tological=true) {
	$resources = get('resources', array());
	$dirmap = array();
	if (array_key_exists('map', $resources) && array_key_exists('dir', $resources['map'])) $dirmap = $resources['map']['dir'];
	
	foreach ($dirmap as $logical => $physical) {
		if ($tological) { 
			$source = $physical;
			$replace = $logical;
		} else {
			$source = $logical;
			$replace = $physical;
		}
		
		if (str_begins($dir.'/', $source.'/')) {
			$newdir = $replace;
			$add = substr($dir, strlen($source));
			append($newdir, substr($dir, strlen($source)));
			$dir = $newdir;
			break;
		}
	}

	return $dir;
}
	
/**
 * Dynamically maps resources from physical to logical.
 * Uses configuration values in conf.php as 'file-map'.
 *
 * @param	$dir		the directory to map.
 * @return	the mapped result.
 */
function mapDir($dir) {
	$mapping = get('file-map');
	if (is_array($mapping)) {
		foreach ($mapping as $name => $value) {
			if (str_begins($dir, $value.'/')) {
				$dir = $name.'/'.substr($dir, strlen($value));
				break;
			}
		}
	}
	return $dir;
}
	
/**
 * Dynamically maps resources from physical to logical.
 * Uses configuration values in conf.php as 'file-map'.
 *
 * @param	$dir		the directory to map.
 * @return	the mapped result.
 */
function translate($dir) {
	$mapping = get('file-map');
	if (is_array($mapping)) {
		foreach ($mapping as $name => $value) {
			if ($name == $dir) {
				$dir = $value;
				break;
			}
		}
	}
	return $dir;
}
	
/**
 * Dynamically maps resources from logical to physical.
 * Uses configuration values in conf.php as 'file-map'.
 *
 * @param	$dir		the directory to map.
 * @return	the mapped result.
 */
function resolveDir($dir) {
	$mapping = get('file-map');
	if (is_array($mapping)) {
		foreach ($mapping as $name => $value) {
			if (str_begins($dir, $name.'/')) {
				$dir = $value.'/'.substr($dir, strlen($name));
				break;
			}
		}
	}
	return $dir;
}

function safeToServe($filename) {
    $pi = pathinfo($filename);

    $badDirs = array('./', 'conf/', 'courses/', 'css/', 'includes/', 'jsincludes/', 'pages/', 'parts/');
    foreach ($badDirs as $bd) if (str_contains($pi['dirname'], $bd)) return false;
    
    $badExts = array('css', 'exe', 'js', 'php');
    if (in_array($pi['extension'], $badExts)) return false;
    
    return true;
}

/**
 * Serve a file allowing for dynamic resource mapping of the directory.
 *
 * @param	$drm		the directory (may be logical).
 * @param	$f			the file name.
 * @param	$mode		the mode to serve the file { inline | dl }.
 * @param	$altf		an alternate file name to use on download.
 */
function serve($drm, $f, $mode='inline', $altf='') {
	$rdrm = FILE::dir($drm, false);
	
	if ($rdrm != '') {
		$filename = $rdrm.'/'.$f;
	} else {
		$filename = $f;
	}
    
    if (($rdrm == $drm) && !self::safeToServe($filename)) $filename = get('no-resource', 'resources/noresource.txt');
    
	$temp = explode('/', $f);
	$f = array_pop($temp);
	
	// look for added parameters
    if ($f != '') $amp = strpos('&', $f);
	if ($amp > 0) $f = substr($f, 0, $amp - 1);
			
	if (file_exists($filename)) { // we have a valid source file
		if (($mode == 'dl') || in_array(FILE::ext($filename), get('force-download'))) $mode = 'attachment';

		if ($altf == '') $altf = $f; // use the original name if the alternate is empty
		
		// firefox bug doesn't allow spaces
		if (str_contains($_SERVER['HTTP_USER_AGENT'], 'Firefox')) $altf = str_replace(' ', '_', $altf);
		
		// get mimetype and cleanup
		$mime = FILE::mimetype($filename);
		
		if ($mime == 'text/html') $mime .='; charset=UTF-8';
		
		// is this is an image use IMG::serve instead
		if (in_array($mime, array('image/gif', 'image/png', 'image/jpeg', 'image/wbmp'))) {
			IMG::serve($filename, param('w', 'value', 0), param('h', 'value', 0));
		}
		
		$length = filesize($filename);
		
		$header = array(
			'Pragma: private',
			'Cache-control: private, must-revalidate',
			"Content-type: $mime",
			"Content-Transfer-Encoding: Binary",
			"Accept-Ranges: bytes",
			"Content-length: $length",
		);
		if (str_contains($_SERVER['HTTP_USER_AGENT'], ' Chrome/')) $mode = 'attachment';
		$header[] = "Content-disposition: $mode; filename=".$altf;

		while(@ob_end_clean());	// remove any prior buffers

		ob_start();
		foreach ($header as $entry) header($entry);
		readfile($filename);
		ob_end_flush();
		exit();
	}
}

/**
 * Returns the mimetype for a given file.
 *
 * @param	$filename	the file to process.
 * @return	the mimetype identified.
 */
function mime($filename) {
	$finfo = finfo_open(FILEINFO_MIME); // return mime type ala mimetype extension
	list($mime) = explode(' ', finfo_file($finfo, $filename)); // returns the value twice for some odd reason, so we strip off the extra
	finfo_close($finfo);
	return $mime;
}

/**
 * Returns the mimetype for a given extension.
 *
 * @param	$filename		the file to process.
 * @return	the mimetype identified.
 */
function mimetype($filename) {
    $mimeinfo = array (
		'3gp'	=>	'video/quicktime',
		'ai'	=>	'application/postscript',
		'aif'	=>	'audio/x-aiff',
		'aifc'	=>	'audio/x-aiff',
		'aiff'	=>	'audio/x-aiff',
		'applescript'	=>	'text/plain',
		'asc'	=>	'text/plain',
		'asf'	=>	'video/x-ms-asf',
		'asm'	=>	'text/plain',
		'au'	=>	'audio/basic',
		'avi'	=>	'video/x-msvideo',
		'bcpio'	=>	'application/x-bcpio',
		'bin'	=>	'application/octet-stream',
		'bmp'	=>	'image/bmp',
		'c'		=>	'text/plain',
		'cc'	=>	'text/plain',
		'ccad'	=>	'application/clariscad',
		'cct'	=>	'shockwave/director',
		'cdf'	=>	'application/x-netcdf',
		'class'	=>	'application/octet-stream',
		'cpio'	=>	'application/x-cpio',
		'cpp'	=>	'text/plain',
		'cpt'	=>	'application/mac-compactpro',
		'cs'	=>	'application/x-csh',
		'csh'	=>	'application/x-csh',
		'css'	=>	'text/css',
		'dcr'	=>	'application/x-director',
		'dif'	=>	'video/x-dv',
		'dir'	=>	'application/x-director',
		'dms'	=>	'application/octet-stream',
		'doc'	=>	'application/msword',
		'drw'	=>	'application/drafting',
		'dv'	=>	'video/x-dv',
		'dvi'	=>	'application/x-dvi',
		'dwg'	=>	'application/acad',
		'dxf'	=>	'application/dxf',
		'dxr'	=>	'application/x-director',
		'eps'	=>	'application/postscript',
		'etx'	=>	'text/x-setext',
		'exe'	=>	'application/octet-stream',
		'ez'	=>	'application/andrew-inset',
		'f'		=>	'text/plain',
		'f90'	=>	'text/plain',
		'fli'	=>	'video/x-fli',
		'gif'	=>	'image/gif',
		'gtar'	=>	'application/x-gtar',
		'gz'	=>	'application/g-zip',
		'gzip'	=>	'application/g-zip',
		'h'		=>	'text/plain',
		'hdf'	=>	'application/x-hdf',
		'hh'	=>	'text/plain',
		'hpp'	=>	'text/plain',
		'hqx'	=>	'application/mac-binhex40',
		'htm'	=>	'text/html',
		'html'	=>	'text/html',
		'ice'	=>	'x-conference/x-cooltalk',
		'ico'	=>	'image/vnd.microsoft.icon',
		'ief'	=>	'image/ief',
		'iges'	=>	'model/iges',
		'igs'	=>	'model/iges',
		'ips'	=>	'application/x-ipscript',
		'ipx'	=>	'application/x-ipix',
		'java'	=>	'text/plain',
		'jcb'	=>	'text/xml',
		'jcl'	=>	'text/xml',
		'jcw'	=>	'text/xml',
		'jmt'	=>	'text/xml',
		'jmx'	=>	'text/xml',
		'jpe'	=>	'image/jpeg',
		'jpeg'	=>	'image/jpeg',
		'jpg'	=>	'image/jpeg',
		'jqz'	=>	'text/xml',
		'js'	=>	'application/x-javascript',
		'kar'	=>	'audio/midi',
		'latex'	=>	'application/x-latex',
		'lha'	=>	'application/octet-stream',
		'lsp'	=>	'application/x-lisp',
		'lzh'	=>	'application/octet-stream',
		'm'		=>	'text/plain',
		'm3u'	=>	'audio/x-mpegurl',
		'm4a'	=>	'audio/x-m4a',
		'm4b'	=>	'audio/x-m4b',
		'm4v'	=>	'video/m4v',
		'man'	=>	'application/x-troff-man',
		'me'	=>	'application/x-troff-me',
		'mesh'	=>	'model/mesh',
		'mid'	=>	'audio/midi',
		'midi'	=>	'audio/midi',
		'mif'	=>	'application/vnd.mif',
		'mime'	=>	'www/mime',
		'mov'	=>	'video/quicktime',
		'movie'	=>	'video/x-sgi-movie',
		'mp2'	=>	'audio/mpeg',
		'mp3'	=>	'audio/mpeg',
		'mp4'	=>	'video/mp4',
		'mpe'	=>	'video/mpeg',
		'mpeg'	=>	'video/mpeg',
		'mpg'	=>	'video/mpeg',
		'mpga'	=>	'audio/mpeg',
		'ms'	=>	'application/x-troff-ms',
		'msh'	=>	'model/mesh',
		'nc'	=>	'application/x-netcdf',
		'oda'	=>	'application/oda',
		'odb'	=>	'application/vnd.oasis.opendocument.database',
		'odc'	=>	'application/vnd.oasis.opendocument.chart',
		'odf'	=>	'application/vnd.oasis.opendocument.formula',
		'odg'	=>	'application/vnd.oasis.opendocument.graphics',
		'odi'	=>	'application/vnd.oasis.opendocument.image',
		'odm'	=>	'application/vnd.oasis.opendocument.text-master',
		'odp'	=>	'application/vnd.oasis.opendocument.presentation',
		'ods'	=>	'application/vnd.oasis.opendocument.spreadsheet',
		'odt'	=>	'application/vnd.oasis.opendocument.text',
		'ogg'	=>	'audio/ogg',
		'otg'	=>	'application/vnd.oasis.opendocument.graphics-template',
		'oth'	=>	'application/vnd.oasis.opendocument.text-web',
		'otp'	=>	'application/vnd.oasis.opendocument.presentation-template',
		'ots'	=>	'application/vnd.oasis.opendocument.spreadsheet-template',
		'ott'	=>	'application/vnd.oasis.opendocument.text-template',
		'pbm'	=>	'image/x-portable-bitmap',
		'pct'	=>	'image/pict',
		'pdb'	=>	'chemical/x-pdb',
		'pdf'	=>	'application/pdf',
		'pgm'	=>	'image/x-portable-graymap',
		'pgn'	=>	'application/x-chess-pgn',
		'php'	=>	'text/plain',
		'pic'	=>	'image/pict',
		'pict'	=>	'image/pict',
		'pls'	=>	'audio/x-mpegurl',
		'png'	=>	'image/png',
		'pnm'	=>	'image/x-portable-anymap',
		'pot'	=>	'application/mspowerpoint',
		'ppm'	=>	'image/x-portable-pixmap',
		'pps'	=>	'application/vnd.ms-powerpoint',
		'ppt'	=>	'application/mspowerpoint',
		'ppz'	=>	'application/mspowerpoint',
		'pre'	=>	'application/x-freelance',
		'prt'	=>	'application/pro_eng',
		'ps'	=>	'application/postscript',
		'qt'	=>	'video/quicktime',
		'ra'	=>	'audio/x-realaudio',
		'ram'	=>	'audio/x-pn-realaudio',
		'ras'	=>	'image/cmu-raster',
		'rgb'	=>	'image/x-rgb',
		'rhb'	=>	'text/xml',
		'rm'	=>	'audio/x-pn-realaudio',
		'roff'	=>	'application/x-troff',
		'rpm'	=>	'audio/x-pn-realaudio-plugin',
		'rtf'	=>	'text/rtf',
		'rtx'	=>	'text/richtext',
		'scm'	=>	'application/x-lotusscreencam',
		'set'	=>	'application/set',
		'sgm'	=>	'text/sgml',
		'sgml'	=>	'text/sgml',
		'sh'	=>	'application/x-sh',
		'shar'	=>	'application/x-shar',
		'silo'	=>	'model/mesh',
		'sit'	=>	'application/x-stuffit',
		'skd'	=>	'application/x-koan',
		'skm'	=>	'application/x-koan',
		'skp'	=>	'application/x-koan',
		'skt'	=>	'application/x-koan',
		'smi'	=>	'application/smil',
		'smil'	=>	'application/smil',
		'snd'	=>	'audio/basic',
		'sol'	=>	'application/solids',
		'spl'	=>	'application/x-futuresplash',
		'sqt'	=>	'text/xml',
		'src'	=>	'application/x-wais-source',
		'stc'	=>	'application/vnd.sun.xml.calc.template',
		'std'	=>	'application/vnd.sun.xml.draw.template',
		'step'	=>	'application/STEP',
		'sti'	=>	'application/vnd.sun.xml.impress.template',
		'stp'	=>	'application/STEP',
		'stw'	=>	'application/vnd.sun.xml.writer.template',
		'sv4cpio'	=>	'application/x-sv4cpio',
		'sv4crc'	=>	'application/x-sv4crc',
		'swa'	=>	'application/x-director',
		'swf'	=>	'application/x-shockwave-flash',
		'swfl'	=>	'application/x-shockwave-flash',
		'sxc'	=>	'application/vnd.sun.xml.calc',
		'sxd'	=>	'application/vnd.sun.xml.draw',
		'sxg'	=>	'application/vnd.sun.xml.writer.global',
		'sxi'	=>	'application/vnd.sun.xml.impress',
		'sxm'	=>	'application/vnd.sun.xml.math',
		'sxw'	=>	'application/vnd.sun.xml.writer',
		't'		=>	'application/x-troff',
		'tar'	=>	'application/x-tar',
		'tcl'	=>	'application/x-tcl',
		'tex'	=>	'application/x-tex',
		'texi'	=>	'application/x-texinfo',
		'texinfo'	=>	'application/x-texinfo',
		'tif'	=>	'image/tiff',
		'tiff'	=>	'image/tiff',
		'tr'	=>	'application/x-troff',
		'tsi'	=>	'audio/TSP-audio',
		'tsp'	=>	'application/dsptype',
		'tsv'	=>	'text/tab-separated-values',
		'txt'	=>	'text/plain',
		'unv'	=>	'application/i-deas',
		'ustar'	=>	'application/x-ustar',
		'vcd'	=>	'application/x-cdlink',
		'vda'	=>	'application/vda',
		'viv'	=>	'video/vnd.vivo',
		'vivo'	=>	'video/vnd.vivo',
		'vrml'	=>	'model/vrml',
		'wav'	=>	'audio/x-wav',
		'wmv'	=>	'video/x-msvideo',
		'wrl'	=>	'model/vrml',
		'xbm'	=>	'image/x-xbitmap',
		'xlc'	=>	'application/vnd.ms-excel',
		'xll'	=>	'application/vnd.ms-excel',
		'xlm'	=>	'application/vnd.ms-excel',
		'xls'	=>	'application/vnd.ms-excel',
		'xlw'	=>	'application/vnd.ms-excel',
		'xml'	=>	'application/xml',
		'xpm'	=>	'image/x-xpixmap',
		'xsl'	=>	'text/xml',
		'xwd'	=>	'image/x-xwindowdump',
		'xxx'	=>	'document/unknown',
		'xyz'	=>	'chemical/x-pdb',
		'zip'	=>	'application/zip',
	);

	return array_extract($mimeinfo, array(self::ext($filename)), $mimeinfo['xxx']);
}

/**
 * Returns the last modified date of a set of files.
 *
 * @param  $files	a simple array of filenames.
 *
 * @return			the date of the most recently modified file.
 */
function determineLMD($files) {
	$lmd = 0;
	foreach ($files as $file) {
		if (file_exists($file)) {
			$ft = filemtime($file);
			$lmd = max($ft, $lmd);
		}
	}
	return date('Y-m-d', $lmd);
}

/**x
 * Unzips a file to a directory. WILL OVERWRITE EXISTING FILES!
 *
 * @param	$zipfile	the Zip file
 * @param	$toDir		the directory to unzip the file to
 *
 * @return	a boolean indicating success of the unzip process
 */
function unZip ($zipfile, $toDir) {
	
	$result = false;

    $zip = new ZipArchive;
    $res = $zip->open($zipfile);
		
    if ($res) {
		$index = ($zip->numFiles)-1;
		$extractFiles = array();
		
		while ($name = $zip->getNameIndex($index)) {
			if (str_contains($name, array('__MACOSX', '.DS_Store', '._.DS_Store'))) {
				$zip->deleteIndex($index);
			} else {
				$extractFiles[] = $name;
			}
			$index--;
		}
		
        $zip->extractTo($toDir, $extractFiles);		
        $zip->close();
        $result = true;
    }
	$me = array('__MACOSX', basename($zipfile, '.zip'));
	$s = '/';
	$removefiles = array(
		$toDir.$s.$me[0].$s.$me[1],
		$toDir.$s.$me[0],
		$zipfile,
	);
	
	foreach ($removefiles as $rf) {
		if (file_exists($rf)) {
			if (is_file($rf)) {
				unlink($rf);
			} else {
				rmdir($rf);
			}
		}
	}
	
	return $result;
}

} // end class FILE

?>
