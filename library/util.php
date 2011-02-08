<?php

/**
 * UTIL is a collection of utility classes used within the archtecture to perform common operations.
 * 
 * @author	Alex Bentley
 * @history	4.0		fixes to correct various functions to proper display.
 *			3.5		conversion to use 'Intrinsic' functions
 *			3.2		fix to stringToArray function to correctly identify true and false values
 *			3.1		update to array_extract function
 *			3.0		added new show function
 *			2.22	added new function to standardized names of elements
 *			2.21	new function like
 *			2.20	fixed updated functions from 2.19
 *			2.19	allow str_begins, str_contains, and str_ends to be case insensitive
 *			2.18	removed strcontains function
 *			2.17	code cleanup
 *			2.16	new function array_extract
 *			2.15	fix to str_ functions when string is empty
 *			2.14	added new capability to str_ functions to allow for an array of matches to be passed.
 *			2.13	added new function characterCleanup
 *			2.12	removed dependence on ABOUT class
 *			2.11	updated secretCode function
 *			2.10	added str_ends function
 *			2.9		fix to not add separator if appended text is empty
 *			2.8		minor fix to normalizeAge
 *			2.7		fix to normalizeAge
 *			2.6		new function to normalize dates to time from now
 *			2.5		fixed merge for array values
 *			2.4		interface to new INIT function to retrieve keyed array value
 *			2.3		convert array to keyed array
 *			2.2		updated append and merge methods
 *			1.0		initial release
 */
 
class UTIL {

/**
 * Append one string to another using the separator if the first string is not empty.
 *
 * @param  $string		the initial string and return of updated string.
 * @param  $append		the string to append.
 * @param  $separator	the string to use as a separator.
 */
function append (&$string, $append, $separator='') {
    append($string, $append, $separator);
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
 * Walks an array to find an element based on a set of array keys
 *
 * For example, say you wanted to get this entry in an array:
 * $smpl = $resource['map']['page']['sample']
 * You could write all the code necessary to make sure the entries existed and so on so it wouldn't generate an error.
 * Or you could simply use:
 * $smpl = array_extract($resource, array('map', 'page', 'sample'));
 *
 * @param	$array			the array to walk
 * @param	$keys			the multilevel array keys to traverse the array.
 * @param	$default		the value to return if not found.
 * @param	$defaultIfEmpty	a boolean to return the default if the result is empty.
 * @return	the entry of the element in question or $default if not found.
 * @see		array_extract
 */
function array_extract ($array, $keys, $default='', $defaultIfEmpty=true) {
    return array_extract($array, $keys, $default, $defaultIfEmpty);
}

function show($value, $label='', $mode='') {
	return display($value, $label, $mode);
}

/**
 * Puts a list of values into a keyed array from a keyed data source
 *
 * @param	$names		an array of names to extract. can be of the form 'result' => 'name' to allow for name mapping or simply 'name'.
 * @param	$data		an optional array of data values, if not passed, the source is the parameters for the page.
 * @param	$default	an optional array of data values or a single value for all items.
 * @return	a keyed array of names and values
 * @see		array_extract
 * @see		param
 */
function extract_items ($names, $data='', $default='') {
	$result = array();
	
	if (is_array($names)) {
		 foreach ($names as $name => $extractname) {
			if (is_numeric($name)) $name = $extractname; // only a simple name was passed, use as source and result
			
			$def = array_extract($default, $extractname, $default);
		 
			if (is_array($data)) {
				if (!is_array($extractname)) $extractname = array($extractname); // make an array if it isn't
				$result[$name] = array_extract($data, $extractname, $def);
			} else {
				$result[$name] = param($extractname, 'value', $def);
			}
		}
	}
	
	return $result;
}

function extract_data ($data, $array=null, $default='') {
	$result = array();
	
	if ($array == null) $array = page('parameters');
	
	foreach ((array)$data as $name) if (array_key_exists($name, $array)) { $result[$name] = $array[$name]; } else { $result[$name] = $default; }
	return $result;
}

/**
 * Perform a 'smart' merge on two arrays. Existence of a value with a numeric key implies a 'true' value.
 * In other words, array(3 => 'alternate') is considered to mean array('alternate' => true).
 *
 * @param  $base	a starting array or a string to convert to an array first.
 * @param  $replace	the array to merge into the starting array, or a string to convert to an array first.
 * @return			the new merged array.
 */
function merge($base, $replace) {
	return smart_merge($base, $replace);
}

/**
 * Convert a byte count to a kinder value.
 *
 * @param  $size		the byte count.
 * @return				the string representation of the kinder value.
 */
function normalize($size) {
	return normalize($size);
}

/**
 * Convert a timestamp into a simpler age value.
 *
 * @param  $time		the timestamp to convert.
 * @param  $units		which entry to use in the labels array.
 * @return				the string representation of the age.
 */
function normalizeAge($time, $units=1) {
	$form = array(1 => 'simple', 2 => 'short', 3 => 'long');
	return normalize($time, 'duration', $form[$units]);
}

/**
 * Construct and send an email in a standard way.
 *
 * @param  $to			the email address to send the message to.
 * @param  $from		the email address to indicate the sender.
 * @param  $subject		the subject of the message.
 * @param  $message		the content of the message.
 * @param  $format		the format of the message (default is HTML).
 * @param  $bcc			any bcc entries.
 * @param  $opt			an optional parameter to send to sendmail.
 * @return				the boolean result of the operation.
 */
function eMail($to, $from, $subject, $message, $format='text/html', $bcc='', $opt='') {
	$sep = "\r\n";
    $headers = '';
	if ($opt == '') $opt  = '-f '.$from;
	
	append($headers, 'From: '.$from, $sep);
	append($headers, 'Return-Path: '.$from, $sep);
	append($headers, 'Reply-To: CHDS@chds.us', $sep); // this is a hack!
	append($headers, 'MIME-Version: 1.0', $sep);
	append($headers, 'Content-type: '.$format.'; charset=utf-8', $sep);
	append($headers, 'Message-ID: <'.time().'web@'.$_SERVER['SERVER_NAME'].'>', $sep);
	append($headers, 'X-Mailer: PHP v'.phpversion(), $sep);
	if ($bcc != '') append($headers, 'BCC: '.$bcc, $sep);
	$headers .= $sep;
    	
	return mail($to, $subject, $message, $headers, $opt);
}

/**
 * Construct a secret code partially based on date.
 *
 * @param	$prefix		an optional prefix to add to the date value for the code construction.
 * @param	$when		an optional date to use instead of time().
 * @param	$max		an optional length limit on the key.
 * @return	the secret code.
 */
function secretCode($prefix='', $when=null, $max=0) {
	if ($when == null) $when = time();
	
	$key = md5($prefix.date('Y-m-d', $when));
	
	// shorten it if necessary
	if ($max != 0) $key = substr($key, 0, $max);
	
	return $key;
}

/**
 * Clean up special characters that perform badly in HTML
 *
 * @param	$text	the string to clean up
 * @return	the repaired string
 */
function characterCleanup($text) {
	static $bad = null;
	if ($bad == null) {
		$bad = array(
			chr(226).chr(128).chr(148) => '-',
			chr(226).chr(128).chr(152) => '\'',
			chr(226).chr(128).chr(153) => '\'',
			chr(226).chr(128).chr(156) => '"',
			chr(226).chr(128).chr(157) => '"',
			chr(226).chr(128).chr(166) => '...',
		);
	}
	
	$text = str_replace(array_keys($bad), array_values($bad), $text);
	
	return $text;
}

/**
 * Convert string from a pattern to an SQL LIKE value
 *
 * @param $target		typically an '*'.
 * @param $replacement	often a simple '%'.
 */
 function like($target='*', $replacement='%', $text) {
	return str_replace($target, $replacement, $text);
}


function goodName($name) {
	return $name;
	
	$alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';
	$good = str_split($alphabet.'0123456789-_:.', 1);
	
	if (!in_array(substr($name, 0, 1), str_split($alphabet))) $name = 'N'.$name;
	
	$expandedname = str_split($name, 1);
	
	$name = '';
	foreach ($expandedname as $value) {
		if (!in_array($value, $good)) $value = '_';
		$name .= $value;
	}
	
	return $name;
}


function compareIPs($ip1, $ip2, $bitlength) {
	$ip1 = self::convertToBinary($ip1);
	$ip2 = self::convertToBinary($ip2);
	
	$ip1 = base_convert(substr($ip1, 0, $bitlength), 2, 10);
	$ip2 = base_convert(substr($ip2, 0, $bitlength), 2, 10);
	
	return ($ip1 == $ip2);
}
	

function convertToBinary($ip) {
	$octets = explode('.', $ip);
	$zeros = '00000000';

	$result = '';
	foreach ($octets as $oc) $result .= substr($zeros.base_convert($oc, 10, 2), -8, 8);

	return $result;
}

function getOrgID($ip) {
	static $orglist = null;
	static $orglistp = null;
	
	if ($orglist == null) {
		
		$query = 'SELECT * FROM ip_access_list WHERE cidr = 32 ORDER BY orgid DESC';
		$result = DB::query('hsdl', $query);
		$orgid = -1;
		foreach ($result as $org) {
			if ($orgid != $org['orgid']) {
				$orgid = $org['orgid'];
				$orglist[$orgid] = array();
			}
			$orglistp[$orgid][] = $org['ip_domain'];
		}
		
		$orglist = array();
		$query = 'SELECT * FROM ip_access_list WHERE cidr != 0 ORDER BY orgid DESC';
		$result = DB::query('hsdl', $query);
		
		$orgid = -1;
		foreach ($result as $org) {
			if ($orgid != $org['orgid']) {
				$orgid = $org['orgid'];
				$orglist[$orgid] = array('ipdomain' => array(), 'cidr' => array());
			}
			
			$orglist[$orgid]['ipdomain'][] = $org['ip_domain'];
			$orglist[$orgid]['cidr'][] = $org['cidr'];
		}
		
	}

			
	foreach ($orglistp as $orgid => $details) {
		foreach ($details as $ipd) {
			if (self::compareIPs($ip, $ipd, 32)) {
				return $orgid;
			}
		}
	}
	
	foreach ($orglist as $orgid => $details) {
		foreach ($details['ipdomain'] as $index => $ipd) {
			if (self::compareIPs($ip, $ipd, $details['cidr'][$index])) {
				return $orgid;
			}
		}
	}
		
	return false;
}

function getOrgIDbyDomain($domain) {
	static $orglist = null;
	
	if ($orglist == null) {
		
		$query = 'SELECT * FROM ip_access_list WHERE cidr = 0 ORDER BY orgid DESC';
		$result = DB::query('hsdl', $query);
		$orgid = -1;
		foreach ($result as $org) {
			if ($orgid != $org['orgid']) {
				$orgid = $org['orgid'];
				$orglist[$orgid] = array();
			}
			$orglist[$orgid][] = $org['ip_domain'];
		}
		
	}

	foreach ($orglist as $orgid => $details) {
		foreach ($details as $ipd) {
			$ipd = '/^'.str_replace('*', '[\S]+', $ipd).'$/';
				
			if (preg_match_all($ipd, $domain, $matches)) return $orgid;
		}
	}
	
	return false;
}

//returns access_group.id for input into tracking_ip if the user is in organizations
//looks for a match with organizations.name, then looks for ip match (user_ip), then looks for domain match (access_match)
function getAccessIDifExists($group_name='', $ip='', $match='') {
	//grab organizations id for relation to tracking_ip if it exists
	if ($group_name !== '') {
		$accessid = 'SELECT id FROM organizations WHERE name = :group';
		$accessQ  = DB::query('hsdl', $accessid, array(':group' => $group_name));
	} else {
		$accessQ = ''; //not array
	}
	if (is_array($accessQ)) {
        $orgID = $accessQ['0']['id'];
	} else {
		$orgID = self::getOrgID($ip);
		if ($orgID == false) {
			$orgID = self::getOrgIDbyDomain($match);
			if ($orgID == false) $orgID = '0';
		}
	}
	return $orgID;
}

function error_message($text, $severity='notice', $return=false) {
	$error_color = array(
        'notice'  => '#B85D0D', 
        'warning' => '#FF9', 
        'error'   => '#F66', 
        'fatal'   => '#F00',
    );
	$color = $error_color['notice'];
	$color = @$error_color[$severity];
		
	$result = span('style:border: 1px solid black; padding: 4px; margin: 5px; background-color: '.$color, $text).br().br();
	if ($return) {
		return $result;
	} else {
		echo $result;
	}
}

function chrencode($text) {
	$result = '';
	if ($text != '') {
		foreach(str_split($text) as $char) {
			$result .= ord($char).".";
		}
	}
	return $result;
}

function chrdecode($text) {
	$result = '';
	if ($text != '') foreach(explode('.', $text) as $char) $result .= chr($char);
	
	return $result;
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
 * @param	$string		the string to process
 * @return	the array
 */
function stringToArray($string) {
	return strtoarray($string);
}

function arrayToString($array) {
	return arraytostr($array);
}

function setConsoleColor($foreground='', $background='', $cmd='') {
    $color  = array('0' => 'black', '1' => 'red',  '2' => 'green',       '3' => 'brown',  '4' => 'blue',        '5' => 'purple',  '6' => 'cyan',       '7' => 'gray');
    $lcolor = array('0' => 'grey',  '1' => 'pink', '2' => 'light-green', '3' => 'yellow', '4' => 'bright-blue', '5' => 'magenta', '6' => 'light-blue', '7' => 'white');
    $cmds   = array('0' => 'reset', '5' => 'blink', '7' => 'reverse', '25' => 'no-blink', '27' => 'no-reverse');
    
    $result = "\033[";
    
    if ($background != '') {
        $bcolor = array_search($background, $color);
        if ($bcolor === false) $bcolor = 0;
        $result .= '4'.$bcolor.';'; // 4 is background
    }
    
    if ($foreground != '') {
        $fcolor = array_search($foreground, $color);
        if ($fcolor === false) {
            $fcolor = array_search($foreground, $lcolor);
            if ($fcolor === false) {
                $fcolor = 7;
            } else {
                $fcolor .= ';1';
            }
        }
        $result .= '3'.$fcolor; // 3 is foreground
    }
    
    $cmdx = array_search($cmd, $cmds);
    if ($cmdx !== false) $result .= ';'.$cmdx;
    
    $result .= 'm';
    
    return $result;
}

function websafeRGB($rgbString) {
    $r = strtoupper(dechex(floor(hexdec(substr($rgbString, 0, 2))/16)));
    $g = strtoupper(dechex(floor(hexdec(substr($rgbString, 2, 2))/16)));
    $b = strtoupper(dechex(floor(hexdec(substr($rgbString, 4, 2))/16)));
    return $r.$r.$g.$g.$b.$b;
}

function RGBtoHSL($rgbString) {
    $r = hexdec(substr($rgbString, 0, 2))/256;
    $g = hexdec(substr($rgbString, 2, 2))/256;
    $b = hexdec(substr($rgbString, 4, 2))/256;
    
    $maxc = max($r, $g, $b);
    $minc = min($r, $g, $b);
    
    // lightness
    $l = ($maxc + $minc)/2;
    
    // saturation
    if (in_array($l, array(0, .5, 1))) {
        $s = 0;
        
    } else if ($l < .5) {
        $s = ($maxc - $minc)/($maxc + $minc);
        
    } else if ($l > .5) { 
        $s = ($maxc - $minc)/(2.0 - $maxc - $minc);
    }
    
    // hue
    if ($maxc == $minc) {
        $h = 0;
        
    } else if ($r == $maxc) { 
        $h = ($g-$b)/($maxc - $minc);
        
    } else if ($g == $maxc) {
        $h = 2.0 + ($b - $r)/($maxc - $minc);
        
    } else {
        $h = 4.0 + ($r - $g)/($maxc - $minc);
    }
    
    $h = $h * 60;
    if ($h < 0) $h = $h + 360;
    
    return sprintf('%3d:%3d:%3d', $h, $s*100, $l*100);
}
    
	
} //end class

function scc($foreground='', $background='', $cmd='') {
    return UTIL::setConsoleColor($foreground, $background, $cmd);
}

    
?>