<?php

/**
 * FILE_MANAGER provides for a web-based FTP sort of client.
 * 
 * @author	Alex Bentley
 * @history	7.0		rewrote class to use site-sirectory gloabal setting
 *			6.0     converted all tag calls to short tags
 *          5.1		no longer allows for leading or trailing spaces in directory or file names
 *			5.0		can now delete whole directories assuming the user has that permission
 *			4.0		now supports unzip of zip files on upload
 *			3.6		code simplification
 *			3.5		minor fix to session-params
 *			3.4		allow for an array of parameters to be carried during this session of the file manager
 *			3.3		fix to icon display
 *			3.2		fix to home link
 *			3.1		add move restrictions
 *			3.0		now sorts by metadata if used
 *			2.20	fix to parameter passing as well as hidden fmid fields in forms
 *			2.19	added the concept of an id to allow for mutiple instances on a single page
 *			2.18	fix to thumboptions when building a tag
 *			2.17	support for generic icons
 *			2.16	removed deprecated function calls
 *			2.15	documentation update
 *			2.14	added new option to NOT serve resources
 *			2.13	better embedded support
 *			2.12	removed dependence on ABOUT class
 *			2.11	updated documentation
 *			2.10	fix to hidden file processing in getFileList
 *			2.9		added new options for breadcrumbs and "empty" directory reporting and removal of upload form is insufficient permission
 *			2.8		remove deprecated calls
 *			2.7		removed old permission check show-permissions
 *			2.6		added new function getCount to determine contents of the target directory
 *			2.5		provide a means to override the default id
 *			2.4		fixed upload and delete
 *			2.3		additional permission changes
 *			2.2		updated permission functionality
 *			1.0		initial release
 */
class FILE_MANAGER {

/**
 * Returns an array of default values.
 *
 * @details	maximum-file-size;	100000000;                  the largest allowed upload
 *			file-permission;	0664;                       octal file permission for uploaded files
 *			label;              top;                        text to use for the top level directory
 *			ignore-files;		'gconfig.xml';              an array of files to ignore
 *			bad-ext;			;                           an array of disallowed file extensions
 *			good-ext;			'*';                        an array of good file extensions
 *			file-ext;			'*';                        an array of displayable file extensions
 *			edit-ext;			'txt','html','xml','php';	an array of extensions for files that can be editted
 *			download-ext;		'doc','xls','ppt','zip';	an array of extensions to download rather than send to the browser
 *			image-thumbnails;	false;                      boolean to indicate if image thumbnails should be displayed
 *			image-ext;          'jpg','bmp','gif','png';	an array of file extensions that apply to images
 *			use-meta-data;      true;                      a boolean to indicate if metadata support be enabled
 *			admin;              false;                      boolean to indicate admin mode
 *			id;                 'filelist';                 default id
 *			breadcrumb;         true;                       boolean to indicate if a breadcrumb should be displayed
 *			security;			'open';                     'open' or 'secure'
 *			embedded;           false;                      a boolean to indicate if this is embedded in a page
 *			serve-resources;	true;                       a boolean to indicate if resources should be served
 *			generic-icons;		false;                      a boolean to indicate if generic file icons should be used
 *			fmid;				;                           an identifier to allow for multiple instances on a page
 *			move-directories;	;                           restrict moves to items in this array if specified.
 *			session-params;	;                               an optional array of parameters to include during this session of the file manager.
 *			dir-expansion-ok;	false;                      a boolean indicating if display of files allows for individual directory expansion in main list.
 *
 * @return	the array of default values.
 */
 
function openDirs($dir='', $action='') {
	static $openDirs;
	
	if ($openDirs == null) {
		$openDirs = array();
	}
	
	switch($action) {
		case 'remove':
			unset($openDirs[array_search($dir, $openDirs)]);
			break;
			
		case 'add':
			$openDirs[] = $dir;
			break;
			
		default;	
			return $openDirs;
	}
}

function defaults() {
	$defaults = array(
		'maximum-file-size' => 1000000000, // 1000Mb
		'file-permission'   => '0664',
		'label'             => 'top',
		'ignore-files'      => array(get('meta-file', '_gconfig.xml')),
		'bad-ext'           => array(),
		'file-ext'          => array('*'), // 'jpg', 'png', 'pdf', 'mov', 'xml'),
		'good-ext'          => array('*'), // 'jpg', 'png', 'pdf', 'mov', 'xml'),
		'edit-ext'          => array('txt', 'html', 'xml', 'php'),
		'download-ext'      => array('doc', 'xls', 'ppt', 'zip'),
		'image-thumbnails'  => false,
		'image-ext'         => array('jpg', 'bmp', 'gif', 'png'),
		'use-meta-data'     => true,
		'admin'             => false,
		'id'                => 'filelist',
		'breadcrumb'        => true,
		'security'			=> 'open', // { open, secure }
		'embedded'          => false,
		'serve-resources'   => true,
		'generic-icons'     => false,
		'fmid'              => '',
		'move-directories'  => array(),
		'session-params'    => array(),
		'dir-expansion-ok'  => false,
	);
	
	return $defaults;
}

/**
 * Returns an option value so option array doesn't need to be passed.
 *
 * @param	$name		the option name.
 * @return	the value of the named option.
 * @see		smart_merge
 */
function option($name, $opts=null) {
	static $options;
	
	if ($opts != null) {
		if (is_string($options)) $options = strtoarray($options); // allow for simpler format
		
		$options = smart_merge(self::defaults(), $opts);
		
		// special processing for session-params
		$params = array();
		$fields = array();
		foreach ($options['session-params'] as $name) {
			$params[$name] = param($name);
			$fields[] = array('element' => 'text', 'name' => $name, 'hidden');
		}
		$options['session-params'] = $params;
		$options['session-fields'] = $fields;
	}

	if ($options == null) return '';
	
	if (array_key_exists($name, $options)) return $options[$name];
	
	if ($name == '*') return $options; // return all settings
	
	return '';
}

function getCount($dir) {
	$count = 0;
	if ($dirlink = @opendir(SITE::file($dir['dir']))) {
		while (($file = readdir($dirlink)) !== false) {
			$count++;
		}
	}
	
	return $count;
}

/**
 * Returns a normalized ini value.
 *
 * @param	$name		the ini name.
 * @return	the value of the named ini item.
 */
function iniValue($name) {
	$value = ini_get($name);
	if (str_contains($value, 'K')) $value = $value * 1024;
	if (str_contains($value, 'M')) $value = $value * 1024 * 1024;
	if (str_contains($value, 'G')) $value = $value * 1024 * 1024 * 1024;
	
	return $value;
}

/**
 * Returns the maximum upload size based on ini, option, and form settings.
 *
 * @return	the maximum upload size based on ini, option, and form settings.
 * @see		iniValue
 * @see		option
 */
function getMaxUploadSize() { // Looks at ini settings and maximum-file-size to determine max upload size	
	return min(self::iniValue('post_max_size'), self::iniValue('upload_max_filesize'), self::option('maximum-file-size'));
}

/**
 * Determines if file should be ignored.
 *
 * @param	$file		the name of the file to check.
 * @return	true if this file should be skipped.
 * @see		option
 */
function ignoreFile($file) {
	return (in_array($file, self::option('ignore-files')) || in_array($file, array('.', '..', '.DS_Store', '.htaccess', '_gconfig.xml', 'gconfig.xml')));
}

/**
 * Determines if file should be downloaded.
 *
 * @param	$file		the name of the file to check.
 * @return	true if this file should be downloaded.
 * @see		option
 */
function download($file) {
	$type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	
	if (in_array('*', self::option('download-ext')) || in_array($type, self::option('download-ext'))) return true;
}

/**
 * Determines if file is an allowed extension.
 *
 * @param	$file		the name of the file to check.
 * @return	true if this file is allowed.
 * @see		option
 */
function allowedFileType($file) {
	$type = strtolower(pathinfo($file, PATHINFO_EXTENSION));
	
	if ((in_array('*', self::option('good-ext')) && !in_array($type, self::option('bad-ext'))) || in_array($type, self::option('good-ext'))) return true;
	
	return false;
}

/**
 * Determines if file is editable.
 *
 * @param	$file		the name of the file to check.
 * @return	true if this file is editable.
 * @see		FMSM::permit
 * @see		workingDirectory
 * @see		option
 */
function editable($file) {
	return FMSM::permit('edit', array('user' => self::option('user'), 'dir' => self::workingDirectory(), 'file' => $file));
}

/**
 * Cleans up strings.
 *
 * @param	$content	the string to clean up.
 * @return	the cleaned up string.
 */
function cleanContent($content) {
	$content = stripslashes($content);
	
	return $content;
}

function validFMID() {
	$fmid = self::option('fmid');
	return (($fmid == '') || (param('fmid') == $fmid));
}

function value($name, $def='') {
	$value = trim(param($name, 'value', $def));
	return self::validFMID() ? $value : '';
}

function pagevalue($def='') {
	$page = self::validFMID() ? page('value', $def) : '';
	if (str_begins($page, '/')) $page = substr($page, 1);
	return $page;
}

/**
 * Returns the working directory.
 *
 * @return	the working directory.
 * @see		self::option('dir')
 */
function workingDirectory() { 	
	return self::option('dir').'/'.self::pagevalue();
}

/**
 * Get a list of acceptable files in a directory.
 *
 * @param	$dir	the name of the directory to check.
 * @param	$hidden	the number of files not deemed acceptable (returned).
 * @return	the array of files.
 * @see		FMSM::permit
 * @see		workingDirectory
 * @see		option
 */
function getFileList($dir, &$hidden) {
	$wd = self::workingDirectory();
	$filelist = array();
	$subdirs = array();
	
	if ($dirlink = @opendir(SITE::file($wd))) {
		$hidden = 0;
		while (($file = readdir($dirlink)) !== false) {
			$thefile = SITE::file($dir.'/'.$file);
			
			if (FMSM::permit('show', array('user' => self::option('user'), 'file' => $file, 'dir' => $wd, 'ignore-files' => self::option('ignore-files'), 'file-ext' => self::option('file-ext')))) {
				$c = array(
					'name' => $file, 
					'type' => 'file',
					'writeable' => is_writeable($thefile),
					'size' => filesize($thefile),
					'perms' => substr(base_convert(fileperms($thefile), 10, 8), -4),
					'modified' => filemtime($thefile),
				);
				if (self::option('use-meta-data')) {
					$c['meta'] = META::get(SITE::file($wd).'/'.$c['name']);
				}

				if (is_dir($thefile)) {
					if (FMSM::permit('show', array('user' => self::option('user'), 'dir' => $dir.'/'.$file, 'file' => '', 'file-ext' => self::option('file-ext')))) {
						$c['size'] = 0;
						$c['type'] = 'dir';
						if ($sublink = @opendir($thefile)) {
							while (($current = readdir($sublink)) !== false) {
								if (FMSM::permit('show', array('user' => self::option('user'), 'file' => $current, 'dir' => $thefile, 'ignore-files' => self::option('ignore-files'), 'file-ext' => self::option('file-ext')))) {
									/*
									if (self::option('dir-expansion-ok') &&  in_array(self::workingDirectory().'/'.$thefile, self::openDirs())) {
										$expanded = FILE::getFileList(self::workingDirectory().'/'.$thefile, $hidden);
									} else {
									*/
										$expanded = array();
										$c['size']++;
									//}
								}
							}
							closedir($sublink);
						}
						$name = $c['name'];
						if (self::option('use-meta-data') && ($c['short'] != null)) $name = $c['short'];

						while (array_key_exists($name, $subdirs)) $name .= ' ';
						if ($expanded != array()) {
							$c['more'] = $expanded;
						}
						$subdirs[$name] = $c;							
					} else {
						// DEBUG::display($thefile);
					}
				} else {
					$name = $c['name'];
					if (self::option('use-meta-data') && ($c['short'] != null)) $name = $c['short'];

					while (array_key_exists($name, $filelist)) $name .= ' ';
					$filelist[$name] = $c;
				}
			} else {
				if (!self::ignoreFile($file)) $hidden++;
			}
			
		}
		closedir($dirlink);
		
		if (count($filelist)) uksort($filelist, 'strnatcasecmp');
		if (count($subdirs )) uksort($subdirs, 'strnatcasecmp');
		while ($subdirs) array_unshift($filelist, array_pop($subdirs));
		
		return $filelist;
	} else {
		return 'dono';
	}
}

/**
 * Execute a user requested operation.
 *
 * @see		FMSM::permit
 * @see		workingDirectory
 * @see		option
 */
function perform () { 
	$userdir = self::workingDirectory();
	$dir = self::pagevalue();
	$sts = self::value('act', self::value('a'));
	
	$sessionParams = self::option('session-params');
	$sessionFields = self::option('session-fields');
	
	switch ($sts) {
		case 'cd':	// create directory
			if (!FMSM::permit('upload', array('user' => self::option('user'), 'dir' => $userdir))) break;
		
			if (strstr(self::value('new'), '')) {
				$sts .= 'no';
			} else {
				$new = SITE::file($userdir).'/'.self::value('new');
				umask(0);
				comment($new);
				if (!@mkdir($new, 0775)) $sts .= 'no';
			}
			if (param('go', 'exists')) $userdir .= '/'.self::value('new');
			$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'new' => self::value('new'), 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;
		
		case 'pc':
			if (!FMSM::permit('permit', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('file')))) break;
		
			$oldp = self::value('oldp');
			$perm = self::value('perm');
			if ($oldp == $perm) {
				$redirect = LINK::url('', array(page() => $dir, 'fmid' => self::option('fmid')));
				LINK::redirect(LINK::url($redirect, $sessionParams));
			} else if (!is_numeric($perm)) {
				$sts .= 'no';
			} else {
				$file = $userdir.'/'.self::value('file');

				if (!@chmod(SITE::file($file), intval($perm, 8))) $sts .= 'nm';
			}
			$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'old' => self::value('file'), 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;
		
		case 'dr': 
			if (!FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('file')))) break;
		
			$file = self::value('file');
            
			if (self::value('delete') == 'Delete') {
				$tdir = SITE::file($userdir.'/'.$file);
				if (empty($file) || !FMSM::permit('delete', array('user' => self::option('user'), 'dir' => $userdir, 'file' => $file))) {
					$sts .= 'no'; // no file or ignorable file
					
				} else if (is_dir($tdir)) {
					$files = self::getFileList($tdir, $hidden);
					if (count($files) && !FMSM::permit('delete-dir', array('user' => self::option('user'), 'dir' => $userdir))) {
						$sts .= 'ne';
					} else {
						// delete all contents since they are all hidden files or user has permission to delete whole directories
						$result = self::delete_directory($tdir);
					}
					
					if (file_exists($tdir) && ($sts == 'dr') && !@rmdir($tdir)) $sts .= 'no';
				} else {
					
					if (file_exists($tdir) && !@unlink($tdir)) $sts .= 'no';
					if (self::option('use-meta-data')) 	META::clean(SITE::file($tdir));
				}

			} else {
                $sts .= 'cn';
			}
			$redirParams = array_merge(array(page() => $dir, 's' => $sts, 'd' => $dir, 'old' => $file, 'fmid' => self::option('fmid')), $sessionParams);
			LINK::redirect(LINK::url($redirect, $redirParams));
			break;	
		
		case 'rn':	// rename
		case 'dp':	// 
			if (!FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('old')))) break;
		
			$old = self::value('old');
			$new = self::value('new');
						
			if (empty($old) || empty($new)) {
				$sts .= 'nn'; // name not set

			} else if ($old == $new) {
				$redirect = LINK::url('', array(page() => $dir, 'fmid' => self::option('fmid')));
				LINK::redirect(LINK::url($redirect, $sessionParams));

			} else if (!self::allowedFileType($new)  || self::ignoreFile($new)) {
				$sts .= 'bt'; // type not allowed
				
			} else if (file_exists(SITE::file($userdir).'/'.$new)) {
				$sts .= 'ex'; // destination exists
				
			} else if (!is_writeable(SITE::file($userdir).'/'.$old)) {
				$sts .= 'cw'; // old file isn't writeable
				
			} else { // all prior checks pass, attempt action
				if (self::value('a') == "rn") {
					if (!@rename(SITE::file($userdir).'/'.$old, SITE::file($userdir).'/'.$new)) $sts .= 'no';
				} else {
					if (!@copy(SITE::file($userdir).'/'.$old, SITE::file($userdir).'/'.$new)) $sts .= 'no';
				}
			}
			if (self::value('a') == "rn" && self::option('use-meta-data')) {
				$meta = META::get(SITE::file($userdir).'/'.$old);
				$meta['name'] = $new;
				META::set(SITE::file($userdir).'/'.$new, $meta);
				
				META::clean(SITE::file($userdir).'/'.$old);
			}
			$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'old' => $old, 'new' => $new, 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;
			
		case 'mv':  // move
			if (!FMSM::permit('move', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('old'), 'to' => self::value('moveto')))) break;
		
			$old = self::value('file');
			$mvto = self::option('dir').self::value('moveto');
			
			if (empty($old) || empty($mvto)) {
				$sts .= 'nn'; // name not set

//			} else if (!self::allowedFileType($old)  || self::ignoreFile($old)) {
//				$sts .= 'bt'; // type not allowed
				
			} else if (file_exists(SITE::file($mvto).'/'.$old)) {
				$sts .='ex'; // destination exists
				
			} else if (!is_writeable(SITE::file($userdir).'/'.$old)) {
				$sts .= 'cw'; // old file isn't writeable
				
			} else { // all prior checks pass, attempt action
				if (!@copy(SITE::file($userdir).'/'.$old, $mvto.'/'.$old) || (!@unlink($userdir.'/'.$old))) $sts .= 'no';
			}
			
			if (self::option('use-meta-data')) {
				$meta = META::get(SITE::file($userdir).'/'.$old);
				META::set(SITE::file($mvto).'/'.$old, $meta);

				META::clean(SITE::file($userdir).'/'.$old);
			}
			$redirect = LINK::url('', array(page() => self::value('old'), 's' => $sts, 'old' => $old, 'new' => self::value('moveto'), 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;
			
		case 'up':  // upload
            if (!FMSM::permit('upload', array('user' => self::option('user'), 'dir' => $userdir))) break;
						
			$uploadoptions = array(
				'file-id' => 'localfile',					// name of field in form
				'file-dir' => SITE::file(self::option('dir')).'/'.$dir,	// directory to put file into
				'allowed-extensions' => self::option('good-ext'),	// allowed ext
				'transfer-method' => 'move_uploaded_file',		// method to use to transfer file to final location
			);
			if (param('unzip') == 'on') $uploadoptions['unzip'] = true;
			
			$result = FORM::handleUpload($uploadoptions);
			
			if (!$result['success']) {
				$sts .= 'no';
			} else {
				// update session file list
				$session_files = get_session('session-files');
				$session_files[] = $result['file'];
				set_session('session-files', $session_files);
			}
			
			$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'new' => $result['file'], 'e' => $result['reason'], 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;
			
		case 'ed':  // edit
			if (!FMSM::permit('edit', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('file')))) break;
			$file = self::value('file');
			$content = file_get_contents($userdir.'/'.$file);
			$fields = array(
                'element:text | hidden | name:a | value:ed',
			    'element:text | hidden | name:file',
			    'element:text | name:data | label:File Contents | value:'.$content.' | rows:30 | size:100',
            );
			$fields = array_merge($fields, $sessionFields);
			if (FORM::complete($fields, array('submit' => 'Update', 'return'))) {
				// convert content
				$content = self::cleanContent(self::value('data'));
				$sts = 'ed';
				if (FILE::write($userdir.'/'.$file, $content) === false) {
					$sts = 'edno';
				}
				$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'old' => $file, 'fmid' => self::option('fmid')));
				LINK::redirect(LINK::url($redirect, $sessionParams));
			}
			break;
			
		case 'um':  // update metadata
			if (!FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $userdir, 'file' => self::value('file')))) break;
			$file = self::value('file');
			$type = self::value('type');
			$short = self::value('short');
			$long = self::value('long');
			$icon = self::value('icon');
			$sts = 'um';
			if (META::set(SITE::file(self::option('dir').'/'.$dir.'/'.$file), array('short' => $short, 'long' => $long, 'icon' => $icon)) === false) $sts .= 'no';
			$redirect = LINK::url('', array(page() => $dir, 's' => $sts, 'old' => $file, 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
			break;

		default:
	}
}

/**
 * Delete an entire directory tree (recursive).
 *
 * @param	$dir	the directory to start at.
 * @return	the success or failure of the operation as a boolean
 */
function delete_directory($dir) {
	// if the path has a slash at the end we remove it here
	if (substr($dir,-1) == '/') $dir = substr($dir, 0, -1);

	// if the path is not valid or is not a directory we return false and exit the function
	if (!file_exists($dir) || !is_dir($dir)) return false;

	// if the path is not readable we return false and exit the function
	if (!is_readable($dir)) return false;

	//sanity checks done
	$handle = opendir($dir); // we open the directory

	while (false !== ($item = readdir($handle))) { // and scan through the items inside

		if (!in_array($item, array('.','..'))) { // if the filepointer is not the current directory or the parent directory
			
			$path = $dir.'/'.$item; // we build the new path to delete

			// if the new path is a directory we call this function with the new path
			if (is_dir($path)) { 
				if (!self::delete_directory($path)) {
					closedir($handle);
					return false; // we couldn't delete it all
				}
				
			// if the new path is a file we remove the file
			} else { 
				unlink($path);
			}
		}
	}
	
	closedir($handle);
	
	// try to delete directory and return the result
	return rmdir($dir);
}

/**
 * Determine the status message.
 *
 * @return	the message.
 */
function msg() {

	$s = self::value('s');
	$new = self::value('new');
	$old = self::value('old');
	$pagevalue = self::pagevalue();
	
	$badmsg = array(
		'dono' => "'$pagevalue' directory could not be opened.",
		'cdno' => "'$new' directory could not be created.",
		'pcno' => "'$old' permissions could not be changed.",
		'pcnm' => "'$old' permissions could not be changed.",
		'upno' => "'$new' could not be uploaded because ".self::value('e').".",
		'rnno' => "'$old' could not be renamed.",
		'rncw' => "'$old' could not be renamed (write failed).",
		'rnbt' => "'$old' could not be renamed to '$new' (type not allowed).",
		'rnnn' => "'$old' could not be renamed since the new name was empty.",
		'rnex' => "'$old' could not be renamed since '$new' already exists.",
		'mvno' => "'$old' could not be moved to '$new'.",
		'mvcw' => "'$old' could not be moved to '$new' (write failed).",
		'mvbt' => "'$old' could not be moved to '$new' (type not allowed).",
		'mvnn' => "'$old' could not be moved since the new location was empty.",
		'mvex' => "'$old' could not be moved since '$new' already exists.",
		'dpcw' => "'$old' could not be duplicated (write failed).",
		'dpbt' => "'$old' could not be duplicated to '$new' (type not allowed).",
		'dpex' => "'$old' could not be duplicated since '$new' already exists.",
		'drno' => "'$old' could not be deleted.",
		'drcn' => "'$old' was not deleted.",
		'drne' => "'$old' was not deleted (not empty).",
		'drdr' => "'$old' deleted.",
		'edno' => "'$old' could not be rewritten.",
		'umno' => "'$old' info could not be saved.",
	);
	
	$goodmsg = array(
		'cd' => "'$new' directory created.",
		'pc' => "'$old' permissions set.",
		'rn' => "'$old' was renamed to '$new'",
		'dp' => "'$old' was duplicated to '$new'",
		'dr' => "'$old' deleted.",
		'up' => "'$new' uploaded.",
		'ed' => "'$old' updated.",
		'um' => "'$old' info updated.",
	);
	
	$class = 'ok';
	$sts = '';
	if (array_key_exists($s, $badmsg)) {
		$sts = $badmsg[$s];
		$class = 'error';
	} else if (array_key_exists($s, $goodmsg)) {
		$sts = $goodmsg[$s];
		$class = 'ok';
	}
	return span('class:'.$class, $sts);
}

/**
 * Flatten the subdirectories to a single level array.
 *
 * @param	$array	the input array.
 * @param	$prefix	the prefix string to use to indicate indenting.
 * @return	the new array.
 * @see		self::option('dir')
 */
function flatten_dirs($array, $prefix='&nbsp;&nbsp;') {
	$result = array();
	
	foreach ($array as $key => $value) {		
		$result[substr($key, strlen(self::option('dir')))] = $prefix.substr($key, strrpos($key, '/')+1);
		if (is_array($value)) $result = array_merge($result, self::flatten_dirs($value, $prefix.'&nbsp;&nbsp;'));
	}
	
	return $result;
}

/**
 * Build a breadcrumb style nav.
 *
 * @return	the HTML for the nav.
 * @see		self::option('dir')
 * @see		getName
 */
function fileNav() {
	$sessionParams = self::option('session-params');
	if (array_key_exists('uf', $sessionParams)) $sessionParams = array('act' => 'uf');
	$sessionFields = self::option('session-fields');
	
	
	if (self::option('breadcrumb')) {
		$bd = self::option('dir'); 
		$dir = self::pagevalue();
		
		$str = 'Files: '.LINK::paramtag(page(), $sessionParams, self::option('label'), array('title' => 'Go to home folder', 'return'));
		
		if ($dir) {
			$crumbs = explode('/', $dir);
		
			$path = '';
			foreach ($crumbs as $c) {
				$name = self::getName(array('name' => $c, 'type' => 'dir'), $bd.$path);
				if ($name == '') $name = $c;
				append($path, $c, '/');
				$link = LINK::url('', array(page() => $path));
				append($str, LINK::paramtag($link, $sessionParams, $name, 'title:Go to folder | return'), ' / ');
			}
		}

		return $str;
	}
}

/**
 * Produce an HTML table of files in the working Directory.
 *
 * @return	the HTML for the table.
 * @see		workingDirectory
 */
function fileList() {
	$sessionParams = self::option('session-params');
	$sessionFields = self::option('session-fields');
	
	$wd = self::workingDirectory();
	
	$act = (self::value('a')) ? self::value('a') : self::value('act');
	$dir = self::pagevalue();
	$pv = $dir;
	$s = self::value('s');
	$d = self::value('d');
	$file = self::value('file');

	$session_files = get_session('session-files');
	if (!is_array($session_files)) $session_files = array();
	
	$table = '';
	$str = '';
	if (in_array($act, array('', 'rf', 'uf', 'cf', 'mf', 'cp', 'em', 'dv', 'exdr', 'cpdr'))) {
		$totalsize = 0;
		$files = self::getFileList($wd, $hidden);
		
		if (!is_array($files) && ($s == '') && !self::option('embedded')) { 
			$redirect = LINK::url('', array(page() => $pv, 's' => 'dono', 'new' => $d, 'fmid' => self::option('fmid')));
			LINK::redirect(LINK::url($redirect, $sessionParams));
		} else {
			$cols = 3;
			if (FMSM::permit('permit', array('user' => self::option('user'), 'dir' => $wd))) $cols++;
			if (FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $wd))) $cols++;
					
			if ($act == 'cf') {
				$str .= tr().td('colspan:'.$cols);
				$fields = array(
                    'element:text | hidden | name:'.page().' | value:'.$dir,
                    'element:text | hidden | name:act | value:cd',
                    'element:text | hidden | name:fmid',
                    'element:comment | value:New Folder',
                    'element:text | name:new | id:new | size:16 | class:inline'
                );
				$fields = array_merge($fields, $sessionFields);
				$str .= FORM::display($fields, 'submit:Ok | submit-class:inline | return');
				$str .= td('/').tr('/');
			}
			
			if ($act == 'uf') { // upload file form
				if (FMSM::permit('upload', array('user' => self::option('user'), 'dir' => $wd))) {
					$str .= tr().td('colspan:'.$cols);
					$fields = array(
                        'element:text | hidden | name:act | value:up',
                        'element:text | hidden | name:MAX_FILE_SIZE | value:'.self::getMaxUploadSize(),
                        'element:text | hidden | name:fmid',
                        'element:comment | value:Upload File',
                        'element:fileselect | name:localfile | size:12 | class:inline',
                        'element:checkbox | name:unzip | label:Un-Zip | value:false | class:inline'
                    );
					$fields = array_merge($fields, $sessionFields);
					$str .= FORM::display($fields, 'submit:Upload | submit-class:inline | return'); 
					$str .= td('/').tr('/');
				}
			}
			
			if ($act == 'exdr') self::openDirs($wd, 'add');
				
			if ($act == 'cpdr') self::openDirs($wd, 'remove');
				
			if (!is_array($files) || !count($files)) {
				if (!$hidden || (self::option('security') == 'open')) {
					$str .= tr('', td('colspan:'.$cols.' | class:error', 'Directory is empty.'));
				} else if ($hidden) {
					$str .= tr('', td('colspan:'.$cols.' | class:error', 'The account has insufficient permissions to view these files.'));
				}
			} else {
				$first = $files;
				$first = array_shift($first);
				$previous = $first['type'];
				$i = 0;
				foreach ($files as $c) {		
					$odd = '';
					$class = '';
					$i++;
					if ($i % 2 != 0) $odd .= 'odd';
					if ($previous != $c['type']) $odd .= ' separator ';

					$previous = $c['type'];
					$str .= tr('class:'.$c['type'].' '.$odd);
					$details = ($c['writeable'] && FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $wd, 'file' => $c['name'])));
					
					// File/Directory entry
					$str .= td('class:name');
					$file = $wd.'/'.$c['name'];
					$fileext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
					if (self::option('generic-icons')) {
						if ($c['type'] == 'file' && self::option('image-thumbnails') && in_array($fileext, self::option('image-ext'))) {
							$str .= div('class:thumb');
							$thumboptions =  array('box' => 50, 'within');
							if (self::option('serve-resources')) $thumboptions[] = 'serve';
							
							$str .= IMG::tag($file, $thumboptions);
							$str .= div('/');
						} else {
							$str .= div('class:icon');
							$str .= IMG::icon($file, 'ext');
							$str .= div('/');
						}
					}
					$defaultDisplay = true;
					if (self::value('file') == $c['name']) {
						if ($act == 'rf') {
							$defaultDisplay = false;
							$fields = array(
                                'element:text | hidden | name:act | value:rn',
                                'element:text | hidden | name:old | value:'.$c['name'],
                                'element:text | hidden | name:fmid',
                                'element:text | name:new | value:'.$c['name'].' | size:50 | class:inline',
                            );
							$fields = array_merge($fields, $sessionFields);
							$str .= FORM::display($fields, 'submit-suppress | return');
							
						} else if ($act == 'dv') {
							$defaultDisplay = false;
                           // $link = LINK::paramTag('', page().':'.$dir.' | s:drcn | old:'.$c['name'].' | fmid:'.self::option('fmid'), 'cancel', 'return');
							$fields = array(
                                'element:text | hidden | name:act | value:dr',
                                'element:text | hidden | name:file | value:'.$c['name'],
                                'element:text | hidden | name:fmid',
                                'element:comment | value:Delete \''.$c['name'].'\'? | class:inline',
                                'element:button | name:cancel | value:Cancel | class:inline',
                            );
							$fields = array_merge($fields, $sessionFields);
							$str .= FORM::display($fields, 'submit:Delete | submit-name:delete | submit-class:inline | return');

						} else if ($act == 'mf') {
							$defaultDisplay = false;
							if (!$dirs = self::option('move-directories')) {
								$dirs = FILE::getlist(self::option('dir'), array('recursive', 'file-ext' => 'dir'));
							}
							$popdir = array_str(array_merge(array(''=> self::option('label')), self::flatten_dirs($dirs)));
							
							$fields = array(
                                'element:text | hidden | name:old | value:'.$dir,
                                'element:text | hidden | name:act | value:mv',
                                'element:text | hidden | name:file | value:'.$c['name'],
                                'element:text | hidden | name:fmid',
                                'element:comment | class:inline | value:'.$c['name'],
                                'element:popup | name:moveto | class:inline |values:'.$popdir.' | value:'.$dir,
                            );
							$fields = array_merge($fields, $sessionFields);
							$str .= FORM::display($fields, 'submit-class:inline | return');
							
						} else if ($act == 'em') {
							$defaultDisplay = false;
							$name = $c['name'];
							$fullname = SITE::file(self::option('dir')).'/'.$dir.'/'.$c['name'];
							$c['meta'] = META::get($fullname);
							
							if ($c['type'] == 'dir') $icon = trim($c['meta']['icon']);
							$short = trim($c['meta']['short']);
							$long = trim($c['meta']['long']);
							$icons = array();
							if ($c['type'] == 'dir') {
								$localdir = basename($fullname);
								$files = FILE::getlist($fullname, array('file-ext' => array('jpg', 'png', 'gif')));
								if (count($files)) {
									foreach ($files as $f) {
										$filemeta = META::get(SITE::file($fullname.'/'.$f));
										$shrt = $f;
										if ($filemeta['short'] != '') $shrt = $filemeta['short'];
										$icons[$localdir.'/'.$f] = $shrt;
									}
								}
							}
							
							$fields = array(
                                'element:text | hidden | name:old | value:'.$dir,
                                'element:text | hidden | name:act | value:um',
                                'element:text | hidden | name:file | value:'.$c['name'],
                                'element:text | hidden | name:type | value:'.$c['type'],
                                'element:text | hidden | name:fmid',
                            );
							if (self::option('admin')) $fields[] = 'element:comment | class:inline | value:'.$c['name'];
                                
							if (count($icons)) $fields[] = 'element:popup | name:icon | class:inline | '.arraytostr(array('values' => $icons)).' | value:'.$icon;
							
							$fields[] = 'element:text | name:short | size:60 | value:'.$short;
                            $fields[] = 'element:text | name:long | size:60 | rows:3 | class:inline | value:'.$long;
                            
							$fields = array_merge($fields, $sessionFields);
							$str .= FORM::display($fields, 'submit-class:inline | return');
							
						}
					}
					if ($defaultDisplay) {
						$name = self::getName($c, $wd);						
						$dname = $c['name'];
						if ($c['type'] == 'file') {
							$mode = 'inline';
							if (self::download($c['name'])) $mode = 'dl';
								
							if (self::option('serve-resources')) {
								$link = LINK::serve($wd, $c['name'], $dname, 'return | external | mode:'.$mode);
							} else {
								$link = LINK::internal($wd.'/'.$c['name'], $dname, 'return | external | mode:'.$mode);
							}
							$str .= $link;
							if ($name != $c['name']) $str .= span('class:ext', '['.FILE::ext($c['name']).']');
							
						} else {
							$page = page('site', ':').page();
							if (self::option('embedded')) {
								$url = self::option('embedded').$dir.'/'.$c['name'];
							} else {
								$params = array();
								$params[$page] = $dir.'/'.$dname;
								$params['fmid'] = self::option('fmid');
								$url = LINK::url('', $params);
							}
							
							//keep uploader showing
							if (array_key_exists('uf', $sessionParams)) $sessionParams = array('act' => 'uf');

							$url = LINK::url($url, $sessionParams);
							
							//show yellow/grey folder based upon files in dir
							$str .= ($c['size'] > 0) ? IMG::icon('folder', 'other', array('alt' => $c['size'].' files inside')) : IMG::icon('empty-folder', 'other', array('alt' => 'no files inside')) ;
							
							if (self::option('dir-expansion-ok')) {
								if (!in_array($name, self::openDirs())) {
									$url .= '&amp;a=exdr';
									$str .= LINK::local($url, '>', 'title:Expand this directory | return');
								} else {
									$url .= '&amp;a=cldr';
									$str .= LINK::local($url, 'V', 'title:Collapse this directory | return');
								}
							}
							
							$str .= LINK::local($url, $dname, 'title:Show files in this directory | return');
						}
						if (self::option('use-meta-data')) {
							$c['meta'] = META::get(SITE::file(self::option('dir').$dir.'/'.$c['name']));
							$long = trim($c['meta']['long']);
							if ($long != '') $str .= p('class:long', $long);
						}
					}
					$str .= td('/');
										
					// operations links
					if (FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $wd))) { // only include this column if the user can modify something
						$str .= td('class:operations');
		
						if (self::option('use-meta-data') && self::option('admin')) { // provide meta data support
							$link = LINK::url('', page().':'.$dir.' | a:em | file:'.$c['name'].' | fmid:'.self::option('fmid'));
							$str .= LINK::paramtag($link, $sessionParams, IMG::icon('rename'), 'return | title:edit info');
						}
						
						if ($details && (($act != 'rf') || (($act == 'rf') && ($file != $c['name'])))) {
														
							if (!self::option('admin')) {
								if (self::option('use-meta-data')) {
									$link = LINK::url('', page().':'.$dir.' | a:em | file:'.$c['name'].' | fmid:'.self::option('fmid'));
									$str .= LINK::paramtag($link, $sessionParams, IMG::icon('rename'), 'return | title:rename');
								} else {
									$link = LINK::url('', page().':'.$dir.' | a:rf | file:'.$c['name'].' | fmid:'.self::option('fmid'));
									$str .= LINK::paramtag($link, $sessionParams, IMG::icon('rename'), 'return | title:rename');
								}
							}
							$session_delete = FMSM::permit('session-delete', array('user' => self::option('user'), 'dir' => $wd));
							if (!$session_delete || ($session_delete && in_array($c['name'], $session_files))) {
								$link = LINK::url('', page().':'.$dir.' | a:dv | file:'.$c['name'].' | fmid:'.self::option('fmid'));
								$str .= LINK::paramtag($link, $sessionParams, IMG::icon('delete'), 'return | title:delete');
							}
							
							if ($c['type'] == 'file') {
								$new = FILE::name($c['name'])." copy.".FILE::ext($c['name']);
								$link = LINK::url('', page().':'.$dir.' | a:dp | old:'.$c['name'].' | new:'.$new.' | fmid:'.self::option('fmid'));
								$str .= LINK::paramtag($link, $sessionParams, IMG::icon('copy'), 'return | title:duplicate');

								
								$link = LINK::url('', page().':'.$dir.' | a:mf | file:'.$c['name'].' | fmid:'.self::option('fmid'));
								$str .= LINK::paramtag($link, $sessionParams, IMG::icon('move'), 'return | title:move');
								
								if (FMSM::permit('edit', array('user' => self::option('user'), 'dir' => $wd, 'file' => $c['name'])) && self::editable($c['name'])) {									
									$link = LINK::url('', page().':'.$dir.' | a:ed | file:'.$c['name'].' | fmid:'.self::option('fmid'));
									$str .= LINK::paramtag($link, $sessionParams, IMG::icon('edit'), 'return | title:edit');
								}
								
							}
						}
						$str .= td('/');
						$str = str_replace("\n", '', $str);
					}

					// permissions
					if (FMSM::permit('permit', array('user' => self::option('user'), 'dir' => $wd))) {
						$str .= td('class:perms');
						if (FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $wd))) {
							if (($act == 'cp') && (self::value('file') == $c['name'])) {
								$fields = array(
                                    'element:text | hidden | name:act | value:pc',
                                    'element:text | hidden | name:file | value:'.$c['name'],
                                    'element:text | hidden | name:oldp | value:'.$c['perms'],
                                    'element:text | hidden | name:fmid',
                                    'element:text | name:perm | size:5 | class:inline | value:'.$c['perms'],
                                );
								$fields = array_merge($fields, $sessionFields);
								$str .= FORM::display($fields, 'submit-suppress | return');
							} else {
								$link = LINK::url('', page().':'.$pv, 'act:cp | file:'.$c['name'].' | fmid:'.self::option('fmid'));
								$str .= LINK::paramtag($link, $sessionParams, $c['perms'], 'return');
							}
						} else {
							$str .= $c['perms'];
						}
						$str .= td('/');
					}

					// entry size
					$str .= td('class:size');
					if ($c['type'] == 'file') {
						$str .= normalize($c['size']);
						$totalsize += $c['size'];
 					} else {
						$str .= $c['size'].' item';
						if ($c['size'] != 1) $str .= 's';
					}
					
					$str .= td('/');
					
					// lmd
					$str .= td('class:modified', date('Y-m-d', $c['modified']));
                    $str .= tr('/');
                }
			}
			if ($totalsize == 0) {
				$totalsize = '';
			} else {
				$totalsize = ' ('.normalize($totalsize).')';
			}
						
			$s = self::msg();
			
			$title = self::fileNav();
			$links = '';
			if (FMSM::permit('modify', array('user' => self::option('user'), 'dir' => $wd))) {
				$label = 'new folder';
				if (self::option('generic-icons')) $label = IMG::icon('newfolder');
				$link = LINK::url('', page().':'.$pv.' | a:cf | fmid:'.self::option('fmid'));
				append($links, LINK::paramtag($link, $sessionParams, $label, 'title:create new folder | return'), ' | ');
			}
			if (FMSM::permit('upload', array('user' => self::option('user'), 'dir' => $wd))) {
				$label = 'upload file';
				if (self::option('generic-icons')) $label = IMG::icon('newfile');
				$link = LINK::url('', page().':'.$pv.' | a:uf | fmid:'.self::option('fmid'));
				append($links, LINK::paramtag($link, $sessionParams, $label, 'title:upload new file | return'), ' | ');
			}
			if ($links != '') $title .= span('class:title-links', $links);
			
			$items = count($files).' item';
			if (count($files) != 1) $items .= 's';
            $items .= ' '.$totalsize;
            
			$tableid = self::option('id');
			$table .= table('id:'.$tableid.' | class:basictable');
			$table .= thead('', tr('', th('colspan:'.$cols, $title)));
			$table .= tbody('', tr('class:status', td('class:status | colspan:'.($cols-2), $s).td('class:size | colspan:2', $items)).$str);
			$table .= table('/');
		}
	}
	return $table;
}

/**
 * Return the metadata name for an item.
 *
 * @param	$c		the item.
 * @param	$dir	the directory where the item is.
 * @return	the name.
 * @see		workingDirectory
 * @see		option
 */
function getName($c, $dir) {
	$name = $c['name'];
	if (self::option('use-meta-data')) {
		$c['meta'] = META::get(SITE::file($dir).'/'.$c['name']);

		$short = $c['meta']['short'];
		if (!FMSM::permit('modify', array('user' => self::option('user'), 'dir' => self::workingDirectory())) || !self::option('admin')) $name = $short;
	}
	
	return $name;
}

/**
 * External entry point.
 *
 * @param	$options	the keyed array of configured items.
 * @see		perform
 * @see		fileList
 * @see		option
 */
function main ($options=array()) {	
	self::option('', $options);

	if (function_exists('date_default_timezone_set')) date_default_timezone_set(date_default_timezone_get());
    
	self::perform();
	echo self::fileList();
}

} // end FILE_MANAGER class

?>