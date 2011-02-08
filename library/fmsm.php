<?php

/**
 * File Manager Security Module API
 *
 * @author	Alex Bentley
 * @history 2.0		added permission to delete a whole directory
 *			1.9		changes to defaults
 *			1.8		removed directory sort since it wasn't performing as expected. Now position within the conf is important.
 *			1.7		changes to display only some extensions
 *			1.6		removed dependence on ABOUT class
 *			1.5		eliminated warning with new directory start/end check
 *			1.4		start and end directory checking
 *			1.3		added hasDescription call to role test
 *			1.2		reordered defaults
 *			1.1		new permission model
 *			1.0		initial release
 */
class FMSM {

/**
 * Defines the default settings for the various security modes by action.
 *
 * @details	show
 *			ignore;			'.', '..', '.DS_Store', '.htaccess', '_gconfig.xml';	base files to ignore.
 *			ignore-files;	;														other files to ignore.
 *
 * @details	edit
 *			edit-ext;		'txt', 'html', 'xml', 'php';							edittable file types.
 *
 * @details	upload, delete, delete-dir, move, permit, session-delete, modify
 *			permission;		false;													default is 'not permitted'.
 *
 * @param	$action	the action to get defaults for.
 * @return			the array of default setttings for this action.
 * @see				permissionMatrix
 */
function defaults($action) {
	$defaults = array();
	
	switch ($action) {
		case 'show':
			$defaults['ignore'] = array('.', '..', '.DS_Store', '.htaccess', get('meta-file', '_gconfig.xml'));
			$defaults['ignore-files'] = array();
			break;
		
		case 'edit':
			$defaults['edit-ext'] = array('txt', 'html', 'xml', 'php');
			break;
		
		case 'upload':
		case 'delete':
		case 'delete-dir':
		case 'move':
		case 'permit':
		case 'session-delete':
		case 'modify':
			$defaults[$action] = false;
			break;
			
		default:
	}
	
	return $defaults;
}

/**
 * Converts basic directory pattern into a valid regular expression.
 *
 * @param	$entry	the source string.
 * @return	the translated pattern for comparision to a directory
 */
function translatePattern($entry) {
	$source = array();
	$replace = array();
	
	foreach (array('\\', '^', '$', '.', '[', ']', '|', '(', ')', '?', '+', '{', '}', '-', '/') as $char) {
		$source[] = $char;
		$replace[] = '\\'.$char;
	}
	$source[] = '*';
	$replace[] = '[^\\/]*';
	$pattern = '/^'.str_replace($source, $replace, $entry).'[^\n]*$/';
	
	return $pattern;
}
	
/**
 * Determines the permissions allowed for the user based on configured settings.
 * It works by determining what role the user has for the directory and then applying the settings for that role over denied values.
 *
 * This model is designed around the following:
 * for each directory, there are multiple group settings based on a selection of permissions for each grouping
 *
 *	'fm-roles' => array(
 *		'edit' => array('show', 'edit', 'upload', 'delete'),
 *		'read' => array('show'),
 *		'none' => array(),
 *	),
 *	
 *	'fm-directories' => array(
 *		'resources/staff' => array(
 *			'edit' => array('FS Admin'),
 *			'read' => array('FS Resources'),
 *		),
 *		
 *		'resources/staff/multimedia/MET_Videos' => array(
 *			'edit' =>  array('Web Admin', 'FS Admin', 'MET Videos'),
 *		),
 *	),
 *
 * @param	$user	the user to determine the permissions for.
 * @param	$details	the current settings for this invocation of the file manager.
 * @return	the array of default setttings for this action.
 * @see		get
 * @see		translatePattern
 */
function permissionMatrix($user, $details) {
	static $roles = null;
	static $matrix = null;
	static $deny = array('show' => false, 'edit' => false, 'upload' => false, 'delete' => false, 'delete-dir' => false, 'permit' => false, 'session-delete' => false);
	
	if ($roles == null) $roles = get('fm-roles');
	if ($matrix == null) {
		$matrix = array();
		foreach (get('fm-directories') as $entry => $entrydetails) {
			$matrix[self::translatePattern(get('site-directory').$entry)] = $entrydetails;
		}
	}
	
	$dir = SITE::file(array_extract($details, array('dir'), ''));	
	$permits = array();
	
	// find the appropriate entry to apply to this element
	foreach ($matrix as $entry => $permissions) {		
		if ($rslt = preg_match($entry, $dir)) {
			$permits = $permissions;
			break;
		}
	}
	
	foreach ($permits as $role => $permissions) {
		foreach ($permissions as $permission) {
			if (($permission == '*') || hasRole($user, $permission) || hasEntry($user, $permission, 'Masters Cohort')) {
				$actions = smart_merge($deny, $roles[$role]); // merge with denied values to insure all settings are passed
				return $actions;
			} 
		}
	}
	
	// deny all actions
	return $deny;
}

/**
 * Authorization is given or denied based on the action requested and the user permissions as determined by the permissionMatrix.
 *
 * @param	$action	the action to get defaults for.
 * @param	$details	the current settings for this invocation of the file manager.
 * @return			the array of default setttings for this action.
 * @see				permissionMatrix
 */
function permit($action, $details=array()) {	
	$matrix = self::permissionMatrix($details['user'], $details);
	$matrix = smart_merge(self::defaults($action), $matrix);
	$details = smart_merge($matrix, $details);

	switch ($action) {
		case 'show':
			if (in_array($details['file'], array_merge($details['ignore'], $details['ignore-files']))) return false; // deny this entry
			if (!$details['show']) return false;
			if (is_dir($details['file']) || ($details['file'] == '')) {
				$type = 'dir';
			} else {
				$type = strtolower(pathinfo($details['file'], PATHINFO_EXTENSION));
			}

			if ($type && (!in_array($type, $details['file-ext']) && !in_array('*', $details['file-ext']))) return false; // don't show wrong types
			break;
		
		case 'edit':
			$type = strtolower(pathinfo($details['file'], PATHINFO_EXTENSION));
			if (!in_array($type, $details['edit-ext'])) return false; // don't edit wrong types
			if (!$details['edit']) return false;
			break;
		
		case 'upload':
			if (!$details['upload']) return false;
			break;
		
		case 'delete':
			if (!$details['delete']) return false;
			break;
		
		case 'delete-dir':
			if (!$details['delete-dir']) return false;
			break;
		
		case 'permit':
			if (!$details['permit']) return false;
			break;
			
		case 'session-delete':
			if (!$details['session-delete']) return false;
			break;
			
		/* the following permissions are never set but are derived from other settings */
		case 'move':
			if (!$details['upload'] || !$details['delete']) return false;
			break;
		
		case 'modify':
			if (!$details['edit'] && !$details['upload'] && !$details['delete'] && !$details['permit']) return false;
			break;
			
		default:
			return false;
	}
	return true;
}

}

?>
