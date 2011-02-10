<?php

// ****************
// BEGIN Intrinsics
// ****************
//
// A number of functions that should be treated as intrinsic functions in PHP

/**
 * This function determines if the parameter is an associative array.
 *
 * @param	$var	the item to check
 * @return	a boolean indicating if the item is an associative array.
 */
function is_assoc($var) { return (is_array($var) && (count($var)==0 || 0 !== count(array_diff_key($var, array_keys(array_keys($var)))))); }

/**
 * This function removes all buffering except the lowest layer, discarding the contents of all layers.
 * If buffering is off, no action is taken.
 */
function ob_empty() {
	while (ob_get_level() > 1) ob_end_clean();
	if (ob_get_level()) ob_clean();
}

/**
 * This function removes all buffering flushing the contents of all layers, and then exits.
 */
function ob_flush_all() {
	while (ob_get_level()) ob_end_flush();
	exit;
}

/**
 * Display a string with any characters.
 *
 * @param  $string		the string to echo.
 */
function show($string, $raw=false) {
	if (!$raw) $string = htmlspecialchars($string, ENT_COMPAT, 'UTF-8');
	echo $string;
}

/**
 * Append one string to another using the separator if the first string is not empty.
 *
 * @param  $string		the initial string and return of updated string.
 * @param  $append		the string to append.
 * @param  $separator	the string to use as a separator.
 */
function append (&$string, $append, $separator='') {
	if ($string == '') {
		$string = $append;
		
	} else if ($append != '') {
		$string .= $separator.$append;
	}
}

/**
 * Checks to see if the beginning of the string matches the test value(s).
 *
 * @param	$haystack	the string you are checking the beginning of.
 * @param	$needle		the string or array of strings you are comparing against. If an array is passed, any match is returned as true.
 * @param	$sensitive	boolean indicating if comparison is case sensitive.
 * @param	$returnKey	a boolean indicating if the key of the array should be returned.
 * @return	the boolean result of the comparison.
 */
function str_begins($haystack, $needle, $sensitive=true, $returnKey=false) {
	if ($haystack == '') return false;
	
	if (!is_array($needle)) $needle = array(1 => $needle);
	$hlen = strlen($haystack);
	
	foreach ($needle as $key => $pin) {
		$plen = strlen($pin);
		if ($plen > $hlen) continue; // needle must be less than or equal to test string
		if (!$returnKey) $key = true;
		
		if ($sensitive) {
			if (!strncmp($haystack, $pin, $plen)) return $key;
		} else {
			if (!strncasecmp($haystack, $pin, $plen)) return $key;
		}
	}
	
	// none of the test strings matched
	return false;
}

/**
 * Checks to see if the end of the string matches the test value. 
 *
 * @param	$haystack	the string you are checking the end of.
 * @param	$needle		the string or array of strings you are comparing against. If an array is passed, any match is returned as true.
 * @param	$sensitive	boolean indicating if comparison is case sensitive.
 * @return	the boolean result of the comparison.
 */
function str_ends($haystack, $needle, $sensitive=true, $returnKey=false) {
	if ($haystack == '') return false;
	if (!is_array($needle)) $needle = array(1 => $needle);
	$hlen = strlen($haystack);
	
	foreach ($needle as $key => $pin) {
		$plen = strlen($pin);
		if ($plen > $hlen) continue; // needle must be less than or equal to test string
		if (!$returnKey) $key = true;
		
		if ($sensitive) {
			if (!strcmp(substr($haystack, $hlen-$plen), $pin)) return $key;
		} else {
			if (!strcasecmp(substr($haystack, $hlen-$plen), $pin)) return $key;
		}
	}
	return false;
}

/**
 * Checks to see if the string contains the test value. 
 *
 * @param	$haystack	the string you are checking.
 * @param	$needle		the string or array of strings you are comparing against. If an array is passed, any match is returned as true.
 * @param	$sensitive	boolean indicating if comparison is case sensitive.
 * @param	$returnKey	if true, returns the needle matched, if false returns a boolean indicating if any were found.
 * @param	$direction	indicates how to evaluate the needle { first | last }.
 * @return	the boolean result of the comparison.
 */
function str_contains($haystack, $needle, $sensitive=true, $returnKey=false, $direction='first') {
	if ($haystack == '') return false;
	if (!is_array($needle)) $needle = array(1 => $needle);
	
	if ($direction == 'first') {
		if ($sensitive) $compare = 'strpos'; else $compare = 'stripos';
	} else {
		if ($sensitive) $compare = 'strrpos'; else $compare = 'strripos';
	}
	
	$hlen = strlen($haystack);
	
	$matches = array();
	
	foreach ($needle as $key => $pin) {
		$plen = strlen($pin);
		if ($plen > $hlen) continue; // needle must be less than or equal to test string
		
		$result = @$compare($haystack, $pin);
		if ($result !== false) $matches[$pin] = $result;
	}
	
	if (count($matches)) {
		sort($matches);
		if ($direction == 'left') {
			$key = array_shift(array_keys($matches));
		} else {
			$key = array_pop(array_keys($matches));
		}
		if (!$returnKey) $key = true;
		
		return $key;
	}
		
	return false;
}

/**
 * Attempts to extract a string from between two strings. 
 * The function is greedy in that it looks for the first and last occurances of the prefix and suffix respectively.
 *
 * @param	$haystack	the string you are checking.
 * @param	$prefix		the string that preceeds the piece you want.
 * @param	$suffix 	the string that follows the piece you want.
 * @param   $sensitive  boolean indicating if comparison is case sensitive (default).
 * @return	the string between.
 */
function str_between($haystack, $prefix, $suffix, $sensitive=true) {
	if ($haystack == '') return false;
	$len = strlen($prefix);
	if ($sensitive) {
		if (($start = strpos($haystack, $prefix)) && ($end = strrpos($haystack, $suffix))) {
			$start = $start + strlen($prefix);
			return substr($haystack, $start, $end-$start);
		}
	} else {
		if (($start = stripos($haystack, $prefix)) && ($end = strripos($haystack, $suffix))) {
			$start = $start + strlen($prefix);
			return substr($haystack, $start, $end-$start);
		}
	}
	
	return '';
}

/**
 *	Tokenize a string treats quoted strings as a token.
 *
 *	@param $string	the string to process
 *	@return	an ordered array of strings.
 */
function tokenize($string) {
	$tokens = token_get_all('<'.'?php '.$string.' ?'.'>');
	$result = array();
	foreach ($tokens as $token) {
		if (is_string($token)) { // simple 1-character token
			if ($token == '"') $token = '';
		} else {
			switch ($token[0]) {
				case T_OPEN_TAG:
				case T_CLOSE_TAG:
				case T_WHITESPACE:
					$token = '';
					break;
				default:
				$token = $token[1];
			}
			if (str_ends($token, ' ?'.'>')) $token = str_replace(' ?'.'>', '', $token);
			if ($token) $result[] = trim($token);
		}
	}
	
	return $result;
}

/**
 * Wraps a string with the passed characters if the target is found.
 *
 * @param	$string		the string to process.
 * @param	$when		if true, the the string is always wrapped, otherwise it is the string to look for to determine if the string should be wrapped (default is true).
 * @param	$wrapper	the characters to wrap the string with (default is double quotes).
 *
 * @return	returns the original string or the string surrounded by the wrapping characters.
 */
function wrap($string, $when=true, $wrapper='""') {
	if (($when === true) || (strpos($string, $when) !== false)) {
		$wrapper = split_str($wrapper);
		$string = $wrapper[0].$string.$wrapper[1];
	}
	
	return $string;
}

/* Unwraps and removes string elements within the wrapping characters.
 * 
 * @param	$string	the string to process
 * @param	$wrapper	the border characters (default "")
 * @param	$strip		a boolean indicating if we should remove unbalanced leading characters (default true)
 */
function unwrap(&$string, $wrapper='""', $strip=true) {
	$wrapper = split_str($wrapper);
	
	$wrapped = array();
	$start = strpos($string, $wrapper[0]);
	while ($start !== false) {
		$end = @strpos($string, $wrapper[1], $start+1);
		if ($end === false) { 
			// string was not wrapped so eliminate wrapper
			if ($strip) $string = str_replace($wrapper[0], '', $string);
			break;
		}
		$wrapped[] = substr($string, $start+1, $end-$start-1);
		$string = substr($string, 0, $start).substr($string, $end+1);
		$start = strpos($string, $wrapper[0]);
	}
	
	return $wrapped;
}

/**
 * This function takes a string in the form 'class:test | size:20 | type:table'
 * and converts it to the form:
 *
 * array (
 *	'class' => 'test',
 *	'size'	=> 50,
 *	'type'	=> 'table',
 *	);
 *
 * If you simply want to pass a parameter with no value, just use the form 'xlass:test | archive | size:20'
 *
 * If a parameter may or may not have a value and is passed like "may:$value" and $value is empty the parameter will be dropped.
 *
 * This can also be used to parse valid URLs by passing in the following $options array:
 *
 *    $defaults = array(
 *       'trim'        => true,
 *       'urlencoded'  => true,
 *       'item'        => '&amp;',
 *       'key-value'   => '=',
 *   );
 *
 * @param	$string		the string to process
 * @return	the array
 */
function strtoarray($string, $options=array()) {
	if (is_array($string)) return $string; // if this has already been translated, we're done.
	
	$defaults = array(
					  'trim'        => true,
					  'urlencoded'  => false,
					  'item'        => '|',
					  'key-value'   => ':',
					  'array-start' => '(',
					  'array-end'   => ')',
					  'array-item'  => '~',
					  );
	$options = array_merge($defaults, $options);
	
	$result = array();
	if ($options['trim']) $string = trim($string);
	
	if ($string == '') return $result;
	
	$items = explode($options['item'], $string);
		
	foreach ($items as $item) {
		if ($options['trim']) $item = trim($item);
		if (str_contains($item, $options['key-value'])) {
			@list($name, $value) = explode($options['key-value'], $item, 2);
			
			if ($options['trim']) $name = trim($name);
			if ($options['trim']) $value = trim($value);
			if ($options['urlencoded']) $value = urldecode($value);
			
			switch (strtoupper($value)) {
				case 'TRUE':
					$value = true;
					break;
					
				case 'FALSE':
					$value = false;
					break;
					
				case 'NULL':
					$value = null;
					break;
					
				default:
					// this in an embedded array of the form (name:value ~ name: value)
					if (str_begins($value, $options['array-start']) && str_ends($value, $options['array-end']) && str_contains($value, $options['array-item'])) {
						$len = strlen($value); // we'll remove the parens and process it again
						$value = strtoarray(str_replace($options['array-item'], $options['item'], substr($value, 1, $len-2)), $options);
					}
			}
			
			$result[$name] = $value;
			
		} else {
			$result[$item] = true; // passing a parameter that just needs to exist
		}
	}
	return $result;
}

/**
 * This function takes a array in the form:
 * array (
 *	'class' => 'test',
 *	'size'	=> 50,
 *	'type'	=> 'table',
 *	);
 *
 * and converts it to the form:
 *
 * 'class:test | size:20 | type:table'
 *
 * This can also be used to create valid URLs by passing in the following $options array:
 *
 *    $defaults = array(
 *       'trim'        => true,
 *       'urlencoded'  => true,
 *       'item'        => '&amp;',
 *       'key-value'   => '=',
 *   );
 *
 * @param	$array		the array to process
 * @return	the string
 */
function arraytostr($array, $options=array()) {
	if (is_string($array)) return $array; // if this has already been translated, we're done.
	$defaults = array(
					  'trim'        => true,
					  'urlencoded'  => false,
					  'item'        => '|',
					  'key-value'   => ':',
					  'array-start' => '(',
					  'array-end'   => ')',
					  'array-item'  => '~',
					  );
	$options = array_merge($defaults, $options);
	
	$result = '';
	if ($array == array()) return $result;
	
	$urlencode = function_exists('URL_encode') ? 'URL_encode' : 'urlencode';
	foreach ($array as $key => $value) {
		if ($value === true) {
			$value = 'true';
			
		} else if ($value === false) {
			$value = 'false';
			
		} else if ($value === null) {
			$value = 'null';
			
			// this in an embedded array so convert to the form (name:value ~ name: value)
		} else if (is_array($value)) {
			$list = '';
			foreach ($value as $k => $v) {
				append($list, $k.$options['key-value'].$v, $options['array-item']);
			}
			if ($options['urlencoded']) $list = $urlencode($list);
			append($result, $key.$options['key-value'].$options['array-start'].$list.$options['array-end'], $options['item']);
			
			// a numeric key so only a value was passed	
		} else if ($key+0 === $key) { 
			if ($options['trim']) $value = trim($value);
			append($result, $value, $options['item']);
			
		} else {
			if ($options['trim']) $key   = trim($key);
			if ($options['trim']) $value = trim($value);
			if ($options['urlencoded']) $value = $urlencode($value);
			append($result, $key.$options['key-value'].$value, $options['item']);
		}
	}
	return $result;
}

/**
 * This function takes an input value and either converts it to an array 
 * using the strtoarray function for strings or
 * wrapping it in an array for all other types
 *
 * @param   $value  the value to process.
 * @return  the resulting array.
 * @see strtoarray
 */
function as_array($value) {
	if (is_array($value))  return $value;
	if (is_string($value)) return strtoarray($value);
	
	return array($value);
}

/**
 * Walks an array based on a set of array keys to determine key existence.
 * Any missing key denotes failure.
 *
 * @param	$array	the array to walk
 * @param	$keys	the multilevel array keys to traverse the array
 * @return	a boolean indicating if the keys exist.
 */
function array_keys_exist($array, $keys) {
	$keys = as_array($keys);
	
	foreach ($keys as $key) {
		if (!is_array($array) || !array_key_exists((string)$key, $array)) return false;
		$array = $array[$key]; // move to sub array
	}
	
	return true;
}

/**
 * Walks an array to determine key existence.
 *
 * @param	$key	the key to search for
 * @param	$array	the array to walk
 * @return	a boolean indicating if the key exists.
 */
function array_has_key($key, $array) {
	if (is_array($array)) {
		if (array_key_exists($key, $array)) return true;
		foreach ($array as $value) if (is_array($value) && array_has_key($key, $value)) return true;
	}
	
	return false;
}

/**
 * Extracts an element from an array based on a set of keys.
 *
 * For example, say you wanted to get this entry in an array:
 *     $smpl = $resource['map']['page']['sample'];
 *
 * You could write all the code necessary to make sure all the entries existed so it wouldn't generate an error.
 *     if (is_array($resource) && array_key_exists('map', $resource) && 
 *         is_array($resource['map']) && array_key_exists('page', $resource['map']) &&
 *         is_array($resource['map']['page']) && array_key_exists('sample', $resource['map']['page'])) {
 *         $smpl = $resource['map']['page']['sample'];
 *     } else {
 *         $smpl = '';
 *     }
 *
 * Or you could simply use:
 *     $smpl = array_extract($resource, 'map | page | sample', '');
 *
 * @param	$array		the array.
 * @param	$keys       the array of keys to traverse the array
 * @param	$default	the value to return if not found.
 * @return	the entry of the element or $default if not found.
 * @see     array_keys_exist
 */
function array_extract($array, $keys, $default='') {
	$keys = as_array($keys);
	
	if (!array_keys_exist($array, $keys)) return $default;
	
	foreach ($keys as $key) $array = $array[$key];
	
	return $array;
}

/**
 * Encode all characters in a string to HTML entities.
 *
 * @param	$text	the text to encode.
 * @return	the encoded string.
 */
function htmlencode($text) {
	static $zwnj = '&#38;&#122;&#119;&#110;&#106;&#59;';
	$result = '';
	if ($text != '') {
		foreach(str_split($text) as $char) {
			$result .= "&#".ord($char).";";
		}
		$result = str_replace($zwnj, '&zwnj;', $result);
	}
	
	return $result;
}

/**
 * Embed a block of content inside a hidden span. 
 *
 * @param  $thing	the string to embed.
 * @param  $title	an optional title.
 */
function comment($thing, $title='') {
	echo '<span style="display: none;">'.$title.' &mdash; '.$thing.'</span>';
}

/**
 * Converts a string to Title Case, and returns the result.
 * This function does NOT ensure uppercased words are lowercased to allow for acronyms.
 *
 * @param	$title	the string to convert.
 * @param	$extras	additional words to exclude from capitalization.
 * @return	the converted string.
 */
function strtotitle($title, $extras=array()) { 
	// An array of words which shouldn't be capitalised if they aren't the first word.
	$smallwords = array_merge(array( 
		'a', 'an', 'and', 'at',
		'but', 'by',
		'else',
		'for', 'from',
		'if', 'in', 'into', 'is',
		'nor',
		'of', 'off', 'on', 'or', 'out', 'over',
		'the', 'then', 'to',
		'when', 'with' 
		), $extras);
	
	// Split the string into separate words.
	$words = explode(' ', str_replace('/', ' ~/~ ', $title));
	
	// Uppercase the first and all non-small words.
	foreach ($words as $key => $word) if (($key == 0) || !in_array($word, $smallwords)) $words[$key] = ucwords($word); 
	
	return str_replace(' ~/~ ', '/', implode(' ', $words)); 
}
	
/**
 * Makes the current function an alias to the referenced function. 
 *
 * @param  $original	the original function.
 */
function alias($original) {
	$trace = debug_backtrace();
	return call_user_func_array($original, $trace[1]['args']);
}

/**
 * Perform a 'smart' merge on two arrays. Existence of a value with a numeric key implies a 'true' value.
 * In other words, array(3 => 'alternate') is considered to mean array('alternate' => true).
 *
 * @param  $base	a starting array or a string to convert to an array first.
 * @param  $replace	the array to merge into the starting array, or a string to convert to an array first.
 * @return			the new merged array.
 */
function smart_merge($base, $replace) {
	$base = array_merge(as_array($base), as_array($replace));
	$merge = array();
	foreach ($base as $key => $value) {
		// single named element without a value such as "array('hidden')" implies "array('hidden' => true)"
		if (is_int($key) && !is_array($value)) {
			$merge[$value] = true; 
		} else {
			$merge[$key] = $value;
		}
	}
	return $merge;
}

/**
 * Convert a number to a kinder value.
 *
 * @param	$size	the number.
 * @param	$type	the type of number.
 * @param	$form	any special form.
 * @return	the string representation of the kinder value.
 */
function normalize ($size, $type='bytes', $form='default') {
	switch ($type) {
		case 'duration':
			$age = time() - $time;
			
			static $timeperiods = array(
				'second'	=> array(60, 's', 'sec', 'second'),
				'minute'	=> array(60, 'm', 'min', 'minute'),
				'hour'		=> array(24, 'h', 'hrs', 'hour'),
				'day'		=> array(7,	 'd', 'day', 'day'),
				'week'		=> array(4,	 'w', 'wk',  'week'),
				'month'  	=> array(12, 'm', 'mon', 'month'),
				'year'		=> array(1,	 'y', 'yr',  'year'),
			);

			switch ($form) {
				case 'short':
				case 1:
					$plural = '';
					$labelIndex = 2;
					break;
					
				case 'long':
				case 2:
					$plural = 's';
					$labelIndex = 3;
					break;
					
				case 'short':
				case 3:
				default:
					$plural = '';
					$labelIndex = 1;
					break;
			}
			$lastunit = '';
			$fraction = 0;
			
			$result = '';
			foreach ($timeperiods as $unit => $details) {
				$limit = $details[0];
				$label = $details[$labelIndex];
				
				if ($age < $limit) {
					$value = ' '.$age.' '.$label;
					if ($age != 1) $value .= $plural;
					
					append($result, $value, ', ');
					if ($fraction != 0) {
						$fraction = round($fraction);
						$value = ' '.$fraction.' '.$lastunit;
						if ($fraction != 1) $value .= $plural;
						
						append($result, $value, ', ');
					}
					break;
				} else {
					$age = $age/$limit;
					$fraction = $limit * ($age - floor($age));
					$lastunit = $label;
					$age = floor($age);
				}
			}
			
			return $result;
			
		case 'bytes':
		default:
			static $byteunits = array(
				'B'  => 'bytes',
				'KB' => 'kilobytes', 
				'MB' => 'megabytes', 
				'GB' => 'gigabytes', 
				'TB' => 'terabytes', 
				'PB' => 'petabytes', 
				'EB' => 'exabytes', 
				'ZB' => 'zettabytes', 
				'YB' => 'yottabytes',
			);
			
			$scaledsize = $size;
			
			for ($i = 0; $scaledsize > 1024; $i++) $scaledsize = round($scaledsize/1024);
			
			$bn = array_keys($byteunits);
			$bt = array_values($byteunits);
			
			return span('title:'.$size, $scaledsize).acronym('title:'.$bt[$i], $bn[$i]);
	}
}

function xmltoarray($xmlstring) { 
	// set up a new XML parser to do all the work for us 
	$parser = xml_parser_create(); 
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false); 
	xml_set_element_handler($parser, 'xml_start_element', 'xml_end_element'); 
	xml_set_character_data_handler($parser, 'xml_character_data'); 
	
	// Build a Root node and initialize the node_stack... 
	set('node_stack', array()); 
	xml_start_element (null, 'root', array()); 
	
	// parse the data and free the parser... 
	xml_parse($parser, $xmlstring); 
	xml_parser_free($parser); 
	
	// recover the root node from the node stack
	$node_stack = get('node_stack', array());
	$rnode = array_pop($node_stack); 
	
	// return the root node...
	set('node_stack', null);
	
	return $rnode['_ELEMENTS'];
} 

function xml_start_element ($parser, $name, $attrs) { 
	// create a new node... 
	$node = array(); 
	$node['_NAME'] = $name; 
	foreach ($attrs as $key => $value) $node[$key] = $value; 
	
	$node['_DATA'] = ''; 
	$node['_ELEMENTS'] = array(); 
	
	// add the new node to the end of the node stack 
	$node_stack = get('node_stack', array());
	array_push($node_stack, $node); 
	set('node_stack', $node_stack); 
} 

function xml_end_element ($parser, $name) { 
	// pop this element off the node stack 
	$node_stack = get('node_stack', array());
	$node = array_pop($node_stack); 
	$node['_DATA'] = trim($node['_DATA']);
	
	$name = $node['_NAME'];
	unset($node['_NAME']);
	
	if ($node['_ELEMENTS'] == array()) {
		unset($node['_ELEMENTS']);
		if ($node['_DATA'] != '') $node = $node['_DATA'];
	}
	if ($node['_DATA'] == '') unset($node['_DATA']);
	if (count($node) && is_array($node) && array_key_exists('_ELEMENTS', $node)) $node = $node['_ELEMENTS'];
	
	if ($node == array()) $node = '';
		
	// and add it an an element of the last node in the stack... 
	$node_stack[count($node_stack)-1]['_ELEMENTS'][$name] = $node; 
	set('node_stack', $node_stack); 
} 

function xml_character_data($parser, $data) { 
	// add this data to the last node in the stack... 
	$node_stack = get('node_stack');
	$node_stack[count($node_stack)-1]['_DATA'] .= $data; 
	set('node_stack', $node_stack); 
} 

function csvtoarray($string) {
	$string = str_replace(array('\"', '""'), array('\\', '\\'), $string); // escape quotes
	 
	$array = array();
	$items = array();
	while (strlen($string)) { // more data
		if (strpos($string, '"') === 0) {
			$start = 1;
			$end = strpos($string, '"', $start) - 1;
		} else {
			$start = 0;
			$end = 0;
		}
		$rtn = strpos($string, "\n", $end);
		if ($rtn === false) $rtn = strlen($string);
		
		$comma = strpos($string, ',', $end);
		if ($comma === false) $comma = strlen($string);
		
		$nextdelim = min($rtn, $comma);
		if ($end == 0) $end = $nextdelim - 1;
		$items[] = str_replace('\\', '"', substr($string, $start, $end - $start + 1)); // get value and unescape quote
		
		if (substr($string, $nextdelim, 1) == "\n") { // we've hit a new line
			$array[] = $items;
			$items = array();
		}
		$string = ltrim(substr($string, $nextdelim + 1)); // skip the delimiter
	}
	if ($items) $array[] = $items;
	
	return $array;
}

function relative($path) {
	$cwd = explode('/', getcwd());
	$dir = explode('/', dirname(realpath($path)));
	
	do {
		if ($cwd[0] !== $dir[0]) break;
		array_shift($cwd);
		array_shift($dir);
	} while (!empty($cwd) && !empty($dir));
	foreach ($cwd as $cd) array_unshift($dir, '..');
	
	$newpath = '';
	foreach ($dir as $d) append($newpath, $d, '/');
	
	if ($newpath != '') $newpath .= '/';
	return $newpath;
}			

// **************
// END Intrinsics
// **************

?>