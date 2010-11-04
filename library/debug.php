<?php

/**
 * DEBUG provides for a debugging capability on a live site without interfering with the normal operation of the site.
 * 
 * @author      Alex Bentley
 * @history     3.0		now utilizes display funtions in UTIL to format most elements
 *				2.2		added additional param to show function
 *				2.1		display null as [null]
 *				2.0		allow for change of state of debug
 *				1.9		removed internal need for simple debug function
 *				1.8		removed dependence on ABOUT class
 *				1.7		added deprecated function
 *				1.6		added trace back feature
 *				1.0		initial release
 */
 
class DEBUG {

/**
 * Changes the state of debug.
 *
 * @param	$state	a boolean to set the state of debug to.
 */
function set($state='') {
	debug($state);
}

/**
 * Determines if debug mode is enabled.
 *
 * @return	true if debug is ON.
 */
function on() {
	return debug();
}

/**
 * Determines if debug mode is enabled.
 *
 * @return	true if debug if OFF.
 */
function off() {
	return !debug();
}

/**
 * When the request invokes debug mode, this method displays a message to the developer when deprecated calls are invoked.
 *
 * @param  $use		the name of the method that should be used instead.
 * @see				debug
 * @see				show
 */
function deprecated($use) {
	if (debug()) {
		$backtrace = debug_backtrace();
		array_shift($backtrace);
		$entry = array_shift($backtrace);
		self::show("Deprecated: ".self::formatTrace($entry, false)." use {$use}() instead");
	}
}

/**
 * When the request invokes debug mode, this method maintains a structured set of debugging info.
 *
 * @param  $label	the label to use to identify the data.
 * @param  $item	the data/object to save.
 * @param  $mode	can be add, dump, or count
 * @see				add
 * @see				dump
 */
function info ($label, $item, $mode) {
	static $store = array();
	
	if (!debug()) return false; // get out fast if we aren't in debug
	
	switch ($mode) {
		case 'add':		$store[] = array($label => $item); break;
		case 'dump':	self::display($store); break;
		case 'count':	return count($store); break;
		default:
	}
}

/**
 * When the request invokes debug mode, this method adds a piece of data to the store.
 *
 * @param  $label	the label to use to identify the data.
 * @param  $item	the data/object to save.
 * @see				info
 */
function add($label, $item) {
	self::info($label, $item, 'add');
}

/**
 * When the request invokes debug mode, this method returns all of the data in the store.
 *
 * @see				info
 */
function dump() {
	if (debug() && self::info('', '', 'count')) {
		echo "<div class='debug-dump'>";
		self::info('', '', 'dump');
		echo "</div>";
	}
}

/**
 * Returns a formatted string of a trace entry for a debug display
 *
 * @param  $entry	the trace entry to format.
 * @param  $includeArgs		a boolean to indicate if arguments should be included in the display.
 * @return			the formatted string.
 * @see				backtrace
 */
function formatTrace($entry, $includeArgs=true) {
	$entry = array_merge(array('function' => '', 'class' => '', 'object' => '', 'type' => '', 'args' => array()), $entry);
	$args = '';
	if ($includeArgs) {
		foreach ($entry['args'] as $arg) {
			if ($args != '') $args .= ', ';
			$args .= $arg;
		}
	}
	if ($entry['object'] != '') {
		$element = '('.$entry['class'].')'; //.(string)$entry['object'];
	} else {
		$element = $entry['class'];
	}
	
	$parts = explode('/', $entry['file']);
	
	$file = '';
	$start = false;
	foreach ($parts as $part) {
		if (in_array($part, array('includes', 'pages', 'parts'))) $start = true;
		if ($start) append($file, $part, '/');
	}
	return $element.$entry['type'].$entry['function'].'('.$args.') called at ['.$file.':'.$entry['line'].']';
}

/**
 * Displays a backtrace for a debug display. This is the normal entry point for typical debug code.
 *
 * @see				backtrace
 */
function trace() {
	self::display(self::backtrace(2));
}

/**
 * Returns a formatted display of a backtrace for a debug display. Common use is to call trace().
 *
 * @param  $slice	how many steps to ignore in the backtrace before formatting the remainder.
 * @return			the formatted display string.
 * @see				trace
 */
function backtrace($slice=1) {
	$backtrace = array_slice(debug_backtrace(), $slice);
	
	$trace = array();
	foreach ($backtrace as $entry) $trace[] = self::formatTrace($entry);
	
	return $trace;
}

/**
 * Displays of a variable or literal in a properly formatted display. Only appears when request is in debug mode.
 *
 * @param  $var		the data to format and display.
 * @param  $type	the type to force the formatter to assume, default is automatic type determination.
 * @see				show
 */
function display($var, $type='') {
	if (debug()) self::show($var, $type);
}

/**
 * Displays of a variable or literal in a properly formatted display.
 *
 * @param  $var		the data to format and display.
 * @param  $type	the type to force the formatter to assume, default is automatic type determination.
 * @param  $return	if true then return the content as a string, otherwise echo the result.
 * @see				evaluate
 */
function show ($var, $type='', $return=false) {
	$result = '<div class="debug">'.display($var, $type, 'debug').'</div>';
	if ($return) {
		return $result;
	} else {
		echo $result;
	}
}

}
?>