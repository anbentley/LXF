<?php

/**
 * SEARCH provides a means to search the contents of pages on the site.
 * 
 * @author	Alex Bentley
 * @history	6.0	added ability to search limited databases for content.
 *			5.0	undated and simplified cetain function names and such
 *			4.5	better handling of case insensitive search
 *			4.4	simplified search logic
 *			4.3	show for non exact searches
 *			4.2	update to allow for excluded directories
 *			4.1	fix to show where case insensitive
 *			4.0	final fixes for found lines display
 *			3.9	improved display and fixed some reporting issues
 *			3.8	added case sensitive searching
 *			3.7	display of found matches
 *			3.6	exact searching and location display
 *			3.5	improved display
 *			3.4	simplified calling sequences
 *			3.3	fix for subdirectory path inclusion and developer search support
 *			3.2	added new patterns for titles, removed special cases for article pages
 *			3.1	removed dependence on ABOUT class
 *			3.0	major rewrite
 *			2.1	updated site processing
 *			1.0	initial release
 */
class SEARCH {

/**
 * Default options for search
 *
 * @return	the array of default settings.
 * @details	developer;			false;			a boolean to indicate if we are in developer mode.
 *			regex;				false;			a boolean to indicate if the search text should be treated as a regular expression.
 *			exact;				false;			a boolean to indicate if the exact string should be matched.
 *			show;				false;			a boolean to indicate if matches should be returned.
 *			case-sensitive;		false;			a boolean to indicate if the search should be case-sensitive.
 *			map;				;				an optional array to use to map parts to containing pages for normal search if appropriate.
 *			file-ext;			(html, dir);	an array of file extensions to search within.
 *			where;				pages;			an array of directories to search within or exclude (-dir will exclude that dir and anything within it).
 *			time;				false;			a boolean indicating if search time should be displayed.
 *			search-databases;	false;			do a search on databases as well for page content not 'on' a page.
 */
function defaults() {
	return array(
		'developer' => false,
		'regex' => false,
		'exact' => false,
		'show' => false,
		'case-sensitive' => false,
		'map' => array(),
		'file-ext' => array('html', 'dir'),
		'where' => array('pages'),
		'time' => false,
	);
}

/**
 * Returns an array of pages containing the search terms.
 *
 * @param	$dir		the directory to search.
 * @param	$words		the array of words to look for.
 * @param	$options	search options.
 * @return	the array of pages.
 * @see		searchFiles
 */
function site($dir, $words, $options) {		
	$files = FILE::getlist($dir, array('file-ext' => $options['file-ext'], 'recursive'));
    
	if ($files) {
		$results = self::searchFiles($dir, $files, $words, $options);
	} else {
		$results = array();
	}
	
	return $results;
}

/**
 * Extract the line and line # of a found line.
 *
 * @param	$source		the text to search
 * @param	$target		the text to find
 * @param	$offset		where to start from.
 * @param	$options	search options.
 * @return	the found line
 * @see		find
 */
function findline ($source, $target, $offset, $options) {
	$searchtext = $source;
	$searchtarget = $target;
	if (!$options['case-sensitive'] && !$options['regex']) {
		$searchtext = strtolower($searchtext);
		$searchtarget = strtolower($searchtarget);
	}
		
	$where = @strpos($searchtext, $searchtarget, $offset);
	if ($where === false) return false;
	
	
	$bline = strrpos(substr($source, 0, $where), "\n");
	$eline = strpos($source."\n", "\n", $where+strlen($target));
	$prior = substr($source, 0, $bline);
	$linecount = sprintf('[%4d] ', preg_match_all('/\n/', $prior, $matches)+2);
	
	$sourceline = substr($source, $bline+1, $eline-$bline);
	if (!$options['case-sensitive'] && !$options['regex']) {
		$local = strpos(strtolower($sourceline), $searchtarget);
	} else {
		$local = strpos($sourceline, $searchtarget);
	}
	$before = substr($sourceline, 0, $local);
	$after  = substr($sourceline, $local+strlen($target));
	$actual = substr($sourceline, $local, strlen($target));
	$highlighted = htmlencode($before).span('class:search-text', htmlencode($actual)).htmlencode($after);

	$line = "$linecount ".str_replace(array("\t", "\n"), array('    ', "\n        "), $highlighted)."\n";
	
	return array('offset' => $eline+1, 'line' => $line);
}

/**
 * Returns an array of pages containing the search terms, this call can be recursive.
 *
 * @param	$dir		the directory to search.
 * @param	$files		the list of files.
 * @param	$words		the array of words to look for.
 * @param	$options	search options.
 * @return	the array of pages.
 * @see		find
 * @see		findline
 * @see		str_contains
 * @see		FILE::name
 */
function searchFiles($dir, $files, $searchtext, $options) {	
	$results = array();
	if (!$dir) return $results;

	$mapping = $options['map'];
	
	foreach($files as $subdir => $file) {
		if (!is_array($file)) {
			$realfile = str_replace('//', '/', "$dir/$file");
			$content = file_get_contents($realfile);
			if ($found = preg_match($searchtext, $content)) {
				if ($options['show']) {
					preg_match_all($searchtext, $content, $matches, PREG_PATTERN_ORDER);
					$found = '';
					$sourcelen = strlen($content);
					$offset = 0;
					foreach ($matches[0] as $match) {
						$result = self::findline($content, $match, $offset, $options);
						append($found, p('class:search-line', $result['line']), '');
						$offset = $result['offset'];
					}
                }

				// get the page
				$page = FILE::name($file);

				$pathitems = explode('/', $dir);
				$path = $pathitems;
				$base = array_shift($path); // extract top directory
				$pd = '';
				foreach ($path as $d) append($pd, $d, '/');
				append($pd, $page, '/');
				$page = $pd;
				
				if ($options['developer']) $title = "[{$realfile}]";
				
				if ($options['developer'] || in_array('pages', $pathitems) || $mapped) { // we've located a file to include.
					if (in_array('pages', $pathitems) && (!in_array($base, array('pages', 'css', 'parts', 'includes', 'jsincludes')))) { // this is a "site" page, so prefix it correctly
						$page = "$base:".substr($page, 6);
					}
					$results[$page] = array('title' => $title, 'found' => $found);
				}
			}
		} else {
			$current = FILE::name($subdir);
			if (!in_array("-$current", $options['where'])) {
				if ($newresults = self::searchFiles($subdir, $file, $searchtext, $options)) $results = array_merge($results, $newresults);
			}
		}
	}
	uksort($results, 'strnatcmp');
	
	return $results;
}

/**
 * Displays the results of the search.
 *
 * @param	$searchtext		the text being searched.
 * @param	$results		the array of pages found.
 * @see		str_contains
 */
function displayResults($searchtext, $results, $options, $ts=0) {	
	if (count($results) > 0) {
		if ($options['developer']) {
			$term = 'Files';
		} else {
			$term = 'Pages';
		}
		if (!$options['exact']) {
			$terms = explode(' ', $searchtext);
			$searchtext = '';
			foreach ($terms as $trm) append($searchtext, '&lsquo;'.htmlencode($trm).'&rsquo;', ' AND ');
		} else {
			$searchtext = '&lsquo;'.htmlencode($searchtext).'&rsquo;';
		}
		if ($ts > 0) echo h4('', 'Search took '.$ts.' seconds');
			
		echo h3('', $term.' containing '.span('class:search-term', $searchtext));
		
		echo ulist('class:search-results');
		
		$host = $_SERVER['HTTP_HOST'];
		list($dp, $sitename, $ext) = explode('.', $host);
		
		foreach($results as $page => $result) {
			$pagetitle = trim($result['title']);
				echo li();
					echo str_replace('../'.$sitename.'/', '', $pagetitle);
					if ($options['show']) echo br().$result['found'];
				echo li('/');
			//}
		}
		echo ulist('/');
	} else {
		echo h3('', span('class:search-term', '&lsquo;'.htmlencode($searchtext).'&rsquo;').' was not found.');
	}
}

/**
 * Converts basic pattern into a valid regular expression.
 *
 * @param	$entry	the source string.
 * @return	the translated pattern for regex
 */
function translatePattern($entry) {
	$source = array();
	$replace = array();
	
	foreach (array('\\', '^', '$', '.', '[', ']', '|', '(', ')', '?', '+', '{', '}', '-', '/') as $char) {
		$source[] = $char;
		$replace[] = '\\'.$char;
	}
	$pattern = str_replace($source, $replace, $entry);

	return $pattern;
}

/**
 * External entry to search.
 *
 * @param	$searchtext		the text being searched.
 * @param	$ftypes			an array the file types to search on.
 * @param	$locations		the array of directories to search in.
 * @param	$options		search options.
 * @param	$results		the array of files/pages found.
 * @see		site
 * @see		displayResults
 */
function execute($searchtext, $options=array()) {
	$options = smart_merge(self::defaults(), $options);
	
	if ($options['regex']) {
		$pattern = $searchtext;
	} else {
		if (!$options['exact']) { // strip the special characters
			$words = str_word_count($searchtext, 1);
			
		} else { // process just what was passed
			$words = array($searchtext);
		}
		
		$searchtext = '';
		$pattern = '';
		foreach ($words as $word) {
			append($searchtext, $word, ' ');
			append($pattern, self::translatePattern($word), '[^\n]*');
		}
	}

	$pattern = "/$pattern/";
	if (!$options['case-sensitive']) $pattern .= 'i';
	
	if (DEBUG::on() && $options['show']) echo h4('', 'Pattern is '.$pattern);
	
	$options['regex'] = true; // everything becomes regex
	
	$start = microtime(true);
	$results = array();
	foreach ($options['where'] as $location) {
		$newresults = self::site($location, $pattern, $options);
		if ($newresults) $results = array_merge($results, $newresults);
	}
	
	if (array_key_exists('search-data', $options) && $options['search-data']) {
		$newresults = self::searchData($searchText, $options);
		if ($newresults) $results = array_merge($results, $newresults);
	}
	
	$end = microtime(true);
	$ts = 0;
	if ($options['time']) $ts = round(($end-$start)*1000)/1000;
	
	self::displayResults($searchtext, $results, $options, $ts);
}

function searchData($searchtext, $options=array()) {
	$dbs = array('metadata'); //people.programs people.curriculum people.materials people.thesis_main people.pubs
	
	foreach ($dbs as $db) {
		switch($db) {
			case 'metadata':
				$query = "SELECT
							i.*,
							p.name AS publisher, 
							p.phone AS publisher_phone, 
							p.address AS publisher_address, 
							p.title AS publisher_title, 
							p.organization AS publisher_organization,
							c.name AS collection
						  FROM 
							item as i
							LEFT JOIN publisher p ON i.publisherid = p.id,
							collection_item AS ci,
							collection AS c
						  WHERE 
							ci.item = i.id AND ci.collection = c.id AND
							i.release_status = 'RELEASED' 
							i.name LIKE '$searchtext' OR
							i.alternate_name LIKE '$searchtext' OR
							i.caption LIKE '$searchtext' OR
							i.description LIKE '$searchtext'";
		}
	}
}

} // end class SEARCH

?>