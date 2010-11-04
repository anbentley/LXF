<?php
// T_ML_COMMENT does not exist in PHP 5. The following three lines define it in order to preserve backwards compatibility.
// The next two lines define the PHP 5 only T_DOC_COMMENT, which we will mask as T_ML_COMMENT for PHP 4.
if (!defined('T_ML_COMMENT')) {
	define('T_ML_COMMENT', T_COMMENT);
} else {
	define('T_DOC_COMMENT', T_ML_COMMENT);
}

/**
 * PHPDOC provides an API to Javadoc documentation from PHP class files.
 * 
 * @author	Alex Bentley
 * @history	3.0	Now attempts to at least provide SOME documentation for non documented methods.
 *			2.4	fixed method links when name included class
 *			2.3	minor fix to list call to eliminate warning
 *			2.2	corrected library file lookup
 *			2.1	fix to see processing
 *			2.0	consolidated core pages for checking
 *			1.9	minor fix to correct when only class has documention and not functions
 *			1.8	reimplemented versionNumber function
 *			1.7	updated documentation and internal processing
 *			1.6	improved parsing and simplified URLs
 *			1.5	new tag @details
 *			1.4	new function versionNumber
 *			1.3	incorporated includes and check functions
 *			1.2	added private and extends functionality and cross class linking.
 *			1.1	fix to page link names and minor HTML formatting.
 *			1.0	initial release
 */
class PHPDOC {

/**
 * This function attempts to identify all classes loaded from files with the same name.
 * It displays a list of the identified classes as links to their documentation.
 *
 * @see		LINK::local
 */
function displayIncludedFiles() {
	$files = array();

	$entries = get_included_files();
	
	foreach ($entries as $index => $entry) {
		$class = basename($entry, '.php');
		if (class_exists($class)) {
			$path = explode('/', $entry);
			$filename = strtolower(array_pop($path));
			$files[$filename] = $entry;
		}
	}

	ksort($files);

	echo "<div class='class-nav'>\n";
	foreach ($files as $filename => $file) {
		$path = explode('/', $file);
		array_pop($path);
		array_pop($path);
		$from = array_pop($path);
		$classname = strtoupper(FILE::name($filename));
		$title = "$from: ".date ("Y-m-d @ H:i:s", filemtime($file));

		LINK::local(page('full').'='.$classname, $classname, array('title' => $title));
		echo "\n";
	}
	echo "</div>\n";
}

/**
 * This function attempts to locate the associated file for a given class name.
 * 
 * @param	$class	the name of the class.
 * @return	the name of the file likely associated with this class or false.
 */
function findClassFile($class) {
	static $includedfiles = null;
	
	if ($includedfiles == null) { // only need to load this once.
		$includedfiles = array();
		$files = get_included_files();
		foreach ($files as $filename) if (str_contains($filename, '/includes/')) $includedfiles[strtoupper(basename($filename, '.php'))] = $filename;
	}
	$class = strtoupper($class); // upshift both pieces
	
	if (array_key_exists($class, $includedfiles)) {
		return $includedfiles[$class];
	} else {
		return false;
	}
}
		
/**
 * Converts the name to a link name.
 * 
 * @param	$name	the name to translate.
 * @return	the corrected name.
 */
function fixFunctionName($name) {
	return strtolower(trim(array_pop(explode(':', $name))));
}

/**
 * This function displays a class or function.
 * 
 * @param	$name			the name of the item.
 * @param	$pl				the parameter list if a function.
 * @param	$details		an array of documentation elements,
 * @param	$currentclass	the name of the class this method refers to.
 * @param	$private		the string indicating if this item is private.
 */
function displayItem($name, $pl, $details, $currentclass='', $private='') {
	$elements = array(
		'param' => 'Parameters', 
		'return' => 'Returns', 
		'see' => 'See also', 
		'author' => 'Author', 
		'version' => 'Version', 
		'since' => 'Since', 
		'deprecated' => 'Deprecated',
		'history' => 'Version History',
		'details' => 'Details',
	);
	
	echo "\n"; // make it easier to read source
	if (!str_begins(strtolower(trim($name)), 'class')) {
		LINK::name(self::fixFunctionName($name));
	//} else {
		//DEBUG::display($name);
	}
	echo "\n"; // make it easier to read source
	
	if ($details == array()) {
		$plist = $pl;
		if (str_begins($plist, '('))	$plist = substr($plist, 1);
		if (str_ends($plist, ')'))	$plist = substr($plist, 0, strlen($plist)-1);
		if ($plist != '') {
			$params = explode(',', $plist);
			foreach ($params as $p) {
				@list($pn, $pnotes) = explode('=', $p);
				if ($pnotes != '') $pnotes = "default value is $pnotes";
				$details['param'][] = "$pn $pnotes";
			}
		}
	}
	
	if (str_contains($name, ' extends ')) {
	} else {
		$pl = " <span class='parameter-list'>$pl</span>";
	}

	echo "<div class='element'>\n<h2>$private$name$pl</h2>\n<table>\n";
	if (is_array($details)) foreach ($details as $section => $values) {
		if (array_key_exists($section, $elements)) {
			$label = "$elements[$section]:";
		} else {
			$label = '';
		}
		if ($label) {
			echo "<tr><td class='section'>$label</td></tr>\n";
		}
		foreach ($values as $value) {
			$value = trim($value);
			switch ($section) {
			case 'details':
				@list($name, $val, $desc) = explode(';', $value, 3);
				if ($desc != '') { // no description, this is likely a comment for a section
					echo "<tr class='details'><td></td><td class='param'>$name</td><td class='param-value'>$val</td><td class='value'>$desc</td></tr>\n";
				} else {
					echo "<tr class='details'><td></td><td class='detail-comment' colspan='4'>$name</td></tr>\n";
				}
				break;
			case 'history':
				list($ver) = explode(' ', $value);
				$desc = trim(substr($value, strlen($ver)+1));
				echo "<tr class='history'><td></td><td class='param'>$ver</td><td class='value' colspan='2'>$desc</td></tr>\n";
				break;
			case 'param':
				list($var) = explode(' ', $value);
				if (!str_begins($var, '$')) {
					$var = '';
					$desc = $value;
				} else {
					$desc = trim(substr($value, strlen($var)+1));
				}
				echo "<tr class='params'><td></td><td class='param'>$var</td><td class='value' colspan='2'>$desc</td></tr>\n";
				break;
			case 'comments':
				if ($value == '') {
					echo "<tr class='comments'><td colspan='4' class='comments-blank'>&nbsp;</td></tr>\n";
				} else {
					echo "<tr class='comments'><td colspan='4' class='comments'>$value</td></tr>\n";
				}
				break;
			case 'deprecated':
				echo "<tr class='deprecated'><td></td><td colspan='3'>$value</td></tr>\n";
				break;
			case 'see':
				if (!str_contains($value, '::')) { // reference is local to this class
					$value = LINK::toName(strtolower($value), $currentclass.'::'.$value, array('return' => true));
					
				} else { // references another class
					list($classname, $method) = explode('::', $value);
					if (self::findClassFile($classname)) {
						$value = LINK::local(page('full').'='.$classname."#{$method}", $value, LINK::rtn());
					}
				}
				echo "<tr class='see'><td></td><td colspan='3'>$value</td></tr>\n";
				break;
			default:
				echo "<tr class='see'><td></td><td colspan='3'>$value</td></tr>\n";
			}
		}
	}

	echo "</table>\n";
	LINK::toName('top', '[top]');
	echo "\n</div>\n";
}

/**
 * Extracts latest version number from documentation.
 *
 * @param	$class	the name of the class to get the version number for.
 * @return	the version number if found.
 */
function versionNumber($class) {
	$file = self::findClassFile($class);
	$version = '';
	if ($file) {
		$classInfo = self::parseFile($file, true);
		list($version) = explode(' ', array_shift($classInfo['class']['details']['history']));
	}
	return $version;
}
		
/**
 * Parses a file for documentation.
 *
 * @param	$file	the file to parse.
 * @param	$justClassInfo	a boolean to indicate if we should stop parsing after the class header has been processed.
 * @return	a structured array which contains the elements of the documentation found.
 * @see		LINK::local
 */
function parseFile($file, $justClassInfo=false) {
	$source = file_get_contents($file);

	$tokens = token_get_all($source);
	$private = '';
	
	$functions = array();
	$classObject = array();
	$e = array();
	$grab = false;
	foreach ($tokens as $token) {
		// simple 1-character token
		if (is_string($token)) {
			if ($grab) { // dump this item since we have all we need
				if ($type == 'class') {
					$classObject = array('name' => trim($buffer), 'details' => $e);
					$grab = false; // stop gathering
					$e = array(); // empty details
					if ($justClassInfo) break ; // exit early
				} else { // function
					if ($token == '{') {						
						$buffer = str_replace(':: ', '::', trim($buffer));
						list($fn, $pl) = explode('(', $buffer, 2);
						$functions[$fn] = array('pl' => '('.$pl, 'details' => $e, 'private' => $private);
						$e = array();
						$grab = false; // stop gathering
						$private = '';
					} else {
						$buffer .= $token;
					}
				}
			}
		} else {
			// token array
			list($id, $text) = $token;

			switch ($id) { 
				case T_PRIVATE:
					$private = $text.' ';
					break;
					
				case T_FUNCTION:
					$type = 'function';
					$buffer = $class.'::';					
					$grab = true;
					break;
					
				case T_CLASS:
					$type = 'class';
					$buffer = $text;
					$getclass = true;					
					$grab = true;
					$extends = '';
					break;
					
				case T_COMMENT: 
				case T_ML_COMMENT: // we've defined this
				case T_DOC_COMMENT: // and this

					$comment = $text;
					if (str_begins($comment, '/**')) { // Javadoc style comment
						$e = array();
						$lines = explode("\n", $comment);
						$sect = 'comments';
						foreach ($lines as $line) {
							if (preg_match('/[\/]*[\*]+[\s]*@([\S]+)[\s]+([\s,\S]*)$/', $line, $matches)) {
								$sect = $matches[1];
								$line = trim($matches[2]);
							} else {
								$line = trim(preg_replace('/[\/]*[\*]+[\s]*([\s,\S]*)$/', '$1', $line));
							}
							if (!in_array($line, array('/'))) $e[$sect][] = str_replace("\t", ' ', $line);
						}
					}

					break;
					
				default:
					if ($grab) {
						if ($extends && trim($text)) {
							if (self::findClassFile($text)) {
								$text = LINK::local(page('full').'='.$text, $text, array('return', 'style' => 'display: inline;'));
							}
							$extends = false;
						}
						$buffer .= $text;
						
						if ($getclass && trim($text)) {
							$class = $text;
							$getclass = false;
						}
						if ($id == T_EXTENDS) $extends = true;
					}
					break;
			}
	   }
	}
	
	return array('class' => $classObject, 'methods' => $functions);
}

/**
 * Displays the formatted documentation for a parsed file.
 *
 * @param	$docObject	the structured data for this element.
 * @see		displayItem
 * @see		fixFunctionName
 * @see		LINK::local
 */
function display($docObject) {	

	// display results
	@list($label, $currentclass, $extends, $parentclass) = explode(' ', $docObject['class']['name']);
	self::displayItem($docObject['class']['name'], '', $docObject['class']['details']);
	
	$functionnames = array_keys($docObject['methods']);
	natcasesort($functionnames);
	if (count($functionnames)) {
		echo "<div class='method-list'>\n<div class='element'>\n<h3>Class Methods</h3>\n";
		foreach ($functionnames as $name) echo "<a href='#".self::fixFunctionName($name)."'>$name</a>\n";
		echo "</div>\n</div>\n";
	}
	
	foreach ($functionnames as $name) {
		self::displayItem($name, $docObject['methods'][$name]['pl'], $docObject['methods'][$name]['details'], $currentclass, $docObject['methods'][$name]['private']);
	}
}

/**
 * Displays summary information about identified loaded classes.
 *
 * @see		parseFile
 */
function includedClasses() {
	$entries = get_included_files();

	natsort($entries);
	$libdirs = get('library-directories', array('includes', 'include', 'lib'));
	foreach ($entries as $entry) {
		$lib = false;
		foreach ($libdirs as $libdir) {
			if (str_contains($entry, "/$libdir/")) {
				$lib = true;
				break; // make sure this is a library file
			}
		}
		if (!$lib) continue; // if not a library file skip it
		$class = FILE::name(FILE::filename($entry));
		if (class_exists($class)) {
			$classinfo = self::parseFile($entry, true);
			if (is_array($classinfo)) {
				$classinfo = $classinfo['class'];
				$classinfo['details']['lmd'] = date('Y-m-d', filemtime($entry));
				$classinfo['details']['size'] = filesize($entry);				
				$includes[$class] = $classinfo;
			}
		}
	}

	echo "<table class='basictable'>\n";
	echo "<tr><td class='class'>Class</td><td>Version</td><td>Date</td><td>Size</td><td>History</td></tr>\n";
	foreach ($includes as $name => $values) {
		$values = $values['details'];
		// if (!param('raw', 'exists')) $values['size'] = normalize($values['size']);
		$values['lmd'] = normalize(strtotime($values['lmd']), 'duration', param('f', 'value', 2));
		$history = '';
		$ver = '';
		if (array_key_exists('history', $values) && is_array($values['history'])) {
			if (!array_key_exists('version', $values)) {
				$ver = $values['history'][0];
				list($ver) = explode(' ', $ver);
				$values['version'] = array($ver);
			}
			foreach ($values['history'] as $desc) append($history, "$desc", '|');
		}
		if (array_key_exists('version', $values)) $ver = $values['version'][0];
		echo "<tr><td class='class'>$name</td><td>$ver</td><td>$values[lmd]</td><td>$values[size]</td><td>$history</td></tr>\n";
	}
	echo "</table>\n";
}

/**
 * Compares the summary information about sites and displays differences.
 *
 * @param	$sites	an array of hostnames to check.
 * @param	$full	the number of lines of history information to display or -1 for all lines.
 * @param	$changed	a boolean to indicate if only classes that are different should be displayed.
 */
function check($sites, $full, $changed) {
	$sites = array_unique(array_merge(array(get('PHPDOC-reference', page('host'))), explode(',', $sites))); // check passed sites against BSI
	
	$startstr = '<table';
	$endstr = '</table>';

	foreach ($sites as $site) {	
		if ($site == '') continue;
		$data = file_get_contents("http://$site/?core/check&list");
		
		$start = strpos($data, $startstr);
		$end = strpos($data, $endstr, $start) + strlen($endstr);
		
		$table = substr($data, $start, $end - $start);
		$sxo = simplexml_load_string($table);
		
		if (is_object($sxo)) {
			foreach ($sxo->tr as $row) {
				$kids = $row->children();
				$item = array();
				foreach ($kids as $key => $value) {
					$item[] = (string)$value;
				}
				list($name, $version, $lmd, $size, $history) = $item;
				$lmd = str_replace(' ', '&nbsp;', $lmd);
				$all[strtoupper($name)][$site] = array('version' => $version, 'lmd' => $lmd, 'size' => $size, 'history' => $history);
			}
		} else {
			DEBUG::display($site);
			DEBUG::display($table);
		}
	}

	echo "<div class='about'>\n";
	echo "<table class='basictable'>\n";
	echo "<tr class='titles'><td>Class</td><td>Site</td><td>Version</td><td>Date</td><td>Size</td><td>History</td></tr>\n";

	$color = array(true => '#EEE', false => '#FFF');
	$diff = array('#FF9', '#FD8', '#FB7', '#F96', '#F75', '#F54', '#F33', '#F12');
	$on = false;

	ksort($all);

	foreach ($all as $name => $sitedata) {
		if ($name == 'CLASS') continue; // ignore the titles
		$on = !$on;
		$clr = " style='background-color: $color[$on];'";
		$sitecount = count($sitedata);
		ksort($sitedata);
		
		$versions = array('not found');
		$history = array();
		
		foreach ($sitedata as $site => $details) {
			if (!in_array($details['version'], $versions)) {
				$versions[] = $details['version'];
				$history["V$details[version]"] = $details['history'];
			}
		}
		natsort($versions);
		$versions = array_reverse($versions);
		
		if ($changed && count($versions) <= 2) continue;
			
		echo "<tr><td $clr rowspan='$sitecount'>$name</td>";	
		$once = true;
		foreach ($sitedata as $site => $details) {
			if (count($versions) > 2) {
				$diffindex = array_search($details['version'], $versions);
				$clr = " style='background-color: $diff[$diffindex];'";
			}
			echo "<td $clr>$site</td>";
			foreach ($details as $field => $value) {
				if ($field != 'history') {
					$title = '';
					if ($field == 'size') {
						if (is_int($value+0)) {
							$title = " title='$value bytes'";
							$value = normalize($value);
							$value = str_replace(' ', '&nbsp;', $value);
						}
					}
					echo "<td $clr$title>$value</td>";
				}
			}
			
			if ($once) { // show history
				$historyitems = explode('|', $history["V$versions[1]"]);
				$count = count($historyitems);
				if ($full > -1) $count = min($full, $count);
				
				$historytext = '';
				for ($i = 0; $i < $count; $i++) append($historytext, $historyitems[$i], '<br />');

				if (count($versions) > 2) $clr = " style='background-color: $diff[1];'";
				echo "<td $clr rowspan='$sitecount'>$historytext</td>";
				$once = false;
			}

			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	echo "</div>\n";
}

/**
 * Main entry point to documentation system.
 *
 * @see		displayIncludedFiles
 * @see		findClassFile
 * @see		parseFile
 */
function main() {
	self::displayIncludedFiles();
	$classname = page('value'); // look for a class name
	$classfile = self::findClassFile($classname);
	
	if ($classfile) {
		self::display(self::parseFile($classfile));
	} else {
		echo "<h2>PHP Class Library Documentation</h2>
		<p>
		On the right is a list of all class files that are currently loaded on this site where the name matches the filename.
		Since this interprets installed code, the information should be up to date.
		Select a class to view the documentation for that class including calling sequence as well as defaults and related methods.
		</p>
		<p>
		Hovering over each link will display where each class file is loaded from.
		</p>";
	}
}

}
?>