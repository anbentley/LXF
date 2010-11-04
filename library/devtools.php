<?php

/**
 * This class embodies the set of devtools and their functions so the tool pages are much simplified.
 *
 * @author	Alex Bentley
 * @history	4.0     compare tool
 *          3.0		new switcher
 *			2.0		new tools
 *			1.0		initial release
 */
class DEVTOOLS {

function toolList() {
	$tools = array(
		'core/tools&amp;tool' => array(
			'name' => 'Developer Tools Main', 
			'short' => 'Tools', 
			'description' => 'This is the main Developer Tools Page.',
		),
/*
		'core/tools&amp;tool=check' => array(
			'name' => 'Version Check', 
			'short' => 'Versions', 
			'description' => 'This checks the active versions of classes on multiple sites and compares them.',
		),
*/
		'core/tools&amp;tool=doc' => array(
			'name' => 'PHP Library Documentation', 
			'short' => 'Documentation', 
			'description' => 'This displays all available classes in the library directories and the embedded JavaDoc style documentation within them.',
		),
		'core/tools&amp;tool=phpinfo' => array(
			'name' => 'PHP Installation Information', 
			'short' => 'Installation', 
			'description' => 'This displays the results from phpinfo().
				There are links that allow you to select specific parts to display.',
		),
		'core/tools&amp;tool=search' => array(
			'name' => 'Site Search', 
			'short' => 'Search', 
			'description' => 'This allows the developer to search a collection of files within the site in a variety of ways to find code references, content, or css references.
				Regular expressions (regex) are supported.',
		),
       'core/tools&amp;tool=googlesafe' => array(
            'name' => 'GoogleSafe', 
            'short' => 'GoogleSafe', 
            'description' => 'This allows the developer to check a domain name using the GoogleSafe tool.',
            ),
       'core/fileTool' => array(
			'name' => 'Push or Pull Data to Live Server',
			'short' => 'Push/Pull',
			'description' => 'This tool allows users to deploy changes they have made on the development server to the live server, 
				or to retrieve files from the live server to the development server.',
		),
       'core/tools&amp;tool=compare' => array(
              'name' => 'Compare Dev File to Live Server',
              'short' => 'Compare',
              'description' => 'This tool allows developers to compare a file on the development server to the live server.',
              ),
       'core/tools&amp;tool=recent' => array(
			'name' => 'Recent Changes', 
			'short' => 'Recent', 
			'description' => 'This page is intended to show recent updates to the libraries.
				This will be updated periodically to allow developers to learn about new methods, classes, or other elements as they become available.',
		),
		"http://$_SERVER[HTTP_HOST]/admin/phpMyAdmin" => array(
			'name' => 'PHPMyAdmin', 
			'short' => 'PHPMyAdmin', 
			'description' => 'This tool provides access to the PHPMYAdmin page.',
		),
	);
	return $tools;
}

function nav() {
	$site = 'dev:';
	$tl = self::toolList();
	
	foreach ($tl as $url => $details) {
		$tools[$details['short']] = array('url' => $url, 'title' => $details['description']);
	}
	
	// calculate host
	$host = 'http';
	if ($_SERVER['HTTPS'] == 'on') $host .= 's';
	$host .= '://'. $_SERVER['HTTP_HOST'];
	
	$sites = array(
		'https://dev.chds.us'		=> 'dev CHDS',
		'http://dev.hsaj.org'		=> 'dev HSAJ',
		'https://dev.hsdl.org'		=> 'dev HSDL',
		'https://www.chds.us'       => 'live CHDS',
		'http://www.hsaj.org'		=> 'live HSAJ',
		'https://www.hsdl.org'      => 'live HSDL',
	);
	
	if (in_array($host, $sites)) {
		$fields = array(
			array(
				'element'   => 'popup', 
				'name'      => 'switchto',
				'class'     => 'inline',
				'values'    => $sites, 
				'value'     => $host, 
				'label'     => 'select',
				'onchange'  => 'this.form.submit();', 
			),
			array('element' => 'text', 'name' => 'rq', 'value' => $_SERVER['REQUEST_URI'], 'hidden'),
		);
		$links = FORM::display($fields, array('name' => 'switch', 'submit-suppress' => true, 'redisplay', 'mode' => 'GET'));
	}
	
	$sep = '';
	foreach ($tools as $label => $details) {
		append($links, LINK::local($details['url'], $label, array('return', 'title' => $details['title'])), $sep);
		if ($links) $sep = ($sep) ? '|' : ':';
	}
	
	echo div('id:dev-nav', $links);
	
	if (in_array($host, $sites) && (($newsite = param('switchto')) != '')) LINK::redirect($newsite.param('rq'));
}

function showPHPinfo($name='') {	
	$options = array(
		'general'		=> INFO_GENERAL,
	//	'credits'		=> INFO_CREDITS,
		'configuration' => INFO_CONFIGURATION,
		'modules'		=> INFO_MODULES,
		'environment'	=> INFO_ENVIRONMENT,
		'variables'		=> INFO_VARIABLES,
		'license'		=> INFO_LICENSE,
	);
	
	$links = '';
	$params = array();
	foreach ($options as $name => $value) {
		if (param($name, 'exists')) {
			$params[] = $name;
		}
	}
	
	foreach ($options as $name => $value) {
		$p = $params;
		if (param($name, 'exists')) {
			unset($p[array_search($name, $p)]);
			$linkclass = 'turn-off';
		} else {
			$p[] = $name;
			$linkclass = 'turn-on';
		}
		
		append($links, LINK::paramtag(page('full'), $p, $name, array('class' => $linkclass, 'return')), ' | ');
	}
	echo br();
	echo div('class:phpselect', $links);

	$optval = 0;
	foreach($options as $pn => $pv) if (param($pn, 'exists')) $optval += $pv;
	
	if ($optval == 0) $optval = -1;
	
	ob_start();
	phpinfo($optval);
	$results = ob_get_clean();
	$startstr = '<div class="center">';
	$start = strpos($results, $startstr) + strlen($startstr);
	$end	= strpos($results, '</body>');
	$results = substr($results, $start, $end - $start);
	echo div('class:phpinfo', $results);
}

function addSubDirectories(&$dirs, $dir, $subdirs) {
	$dirs[] = $dir;
	
	if ($subdirs == array()) $subdirs = array_keys((array)FILE::getlist($dir, array('file-ext' => 'dir')));
	foreach ($subdirs as $subdir) self::addSubDirectories($dirs, $subdir, array());
}
	
function siteSearch() {

	$options = array(
		'developer'      =>	array('value' => 'on',		'label' => 'show all files',	'title' => 'Show all files, not just pages', 'onclick' => 'setSearchCheckboxes();'),
		'regex'          =>	array('value' => 'PARAM',	'label' => 'regex mode',		'title' => 'Interpret search text as a regular expression', 'onclick' => 'setSearchCheckboxes();'),
		'exact'          =>	array('value' => 'on',		'label' => 'exact',				'title' => 'Attempt to find the exact string'),
		'case-sensitive' =>	array('value' => 'PARAM',	'label' => 'match case',		'title' => 'Search will be case sensitive'),
		'show'           => array('value' => 'on',		'label' => 'show matches',		'title' => 'Show lines that match'),
	);
	
	$exts = array(
		'php'  => array('value' => 'on',    'label' => 'PHP',  'title' => 'Search PHP files'),
		'html' => array('value' => 'on',    'label' => 'HTML', 'title' => 'Search HTML files'),
		'css'  => array('value' => 'on',    'label' => 'CSS',  'title' => 'Search CSS files'),
		'js'   => array('value' => 'PARAM', 'label' => 'JS',   'title' => 'Search Javascript files'),
		'xml'  => array('value' => 'PARAM', 'label' => 'XML',  'title' => 'Search XML files'),
		'txt'  => array('value' => 'PARAM', 'label' => 'TXT',  'title' => 'Search Text files'),
	);
	
	// get configured directories
	$maindirs = array(
		'pages' =>		array('value' => 'on',		'label' => 'pages',			'title' => 'search for text in pages'),
		'parts' =>		array('value' => 'on',		'label' => 'parts',			'title' => 'search for text in parts'),
		'includes' =>	array('value' => 'on',		'label' => 'includes',		'title' => 'search for text in library files',   'subdirs' => 'pages'),
		'css' =>		array('value' => 'PARAM',	'label' => 'css',			'title' => 'search for text in css files',       'subdirs' => 'pages'),
		'jsincludes' =>	array('value' => 'PARAM',	'label' => 'javascript',	'title' => 'search for text in javascript files','subdirs' => 'pages'),
		'conf' =>		array('value' => 'PARAM',	'label' => 'conf',			'title' => 'search for text in configuration files'),
		'images' =>		array('value' => 'skip'),
	);
	
	$host = $_SERVER['HTTP_HOST'];
	list($dp, $sitename, $ext) = explode('.', $host);
	
	$packages = get('include', array());
	$packages[$sitename] = '../'.$sitename.'/';
	
	$topdirs = array_keys(FILE::getlist('.', array('file-ext' => 'dir', 'ignore' => array_keys($maindirs))));
	$tdirs = array();
	foreach ($topdirs as $tdir) {
		$td = substr($tdir, 2);
		
		$nextdirs = FILE::getlist($td, array('file-ext' => 'dir'));
		if ($nextdirs) {
			$nextdirs = array_keys($nextdirs);
			foreach ($maindirs as $testdir => $details) {
				if (($details['value'] != 'skip') && in_array($td.'/'.$testdir, $nextdirs) && !in_array($td.'/', $packages)) {
					$tdirs[] = $td;
					break;
				}
			}
		}
	}	
	
	// options
	$field = array();
	$field[] = array('element' => 'text', 'name' => 'tool', 'hidden');
	$field[] = array('element' => 'text', 'name' => 'text', 'label' => 'Search Text', 'size' => 30, 'use-entities', 'required');
	foreach ($options as $name => $details) {
		$field[] = array('element' => 'checkbox', 'name' => 'option['.$name.']', 'label' => $details['label'], 'value' => $details['value'], 'title' => $details['title'], 'onclick' => @$details['onclick'], 'class' => 'inline');
	}
	
	$field[] = array('element' => 'comment', 'value' => br().br().'<strong>Search File Extensions</strong>');
	$field[] = array('element' => 'checkbox', 'name' => 'ext[a~l~l]', 'label' => '<strong><em>search all</em></strong>', 'title' => 'checking this searches all file extentions', 'class' => 'inline');
	foreach ($exts as $name => $details) {
		$field[] = array('element' => 'checkbox', 'name' => 'ext['.$name.']', 'label' => $details['label'], 'value' => $details['value'], 'title' => $details['title'], 'onclick' => @$details['onclick'], 'class' => 'inline');
	}
	
	// base directories
	$field[] = array('element' => 'comment', 'value' => br().br().'<strong>Search Directories</strong>');
	$field[] = array('element' => 'checkbox', 'name' => 'main[a~l~l]', 'label' => '<strong><em>search all</em></strong>', 'title' => 'checking this searches all directories', 'class' => 'inline');
	foreach ($maindirs as $dir => $details) {
		if ($details['value'] == 'skip') continue;
		$field[] = array('element' => 'checkbox', 'name' => 'main['.$dir.']', 'label' => $dir, 'value' => $details['value'], 'title' => $details['title'], 'onclick' => @$details['onclick'],'class' => 'inline');
	}
	
	// site and packages
	$field[] = array('element' => 'comment', 'value' => br().br().'<strong>Search Sections</strong>');
	$field[] = array('element' => 'checkbox', 'name' => 'site[a~l~l]', 'label' => '<strong><em>search all</em></strong>', 'title' => 'checking this searches all section', 'class' => 'inline');		
	
	foreach ($packages as $package => $pdir) {
		$field[] = array('element' => 'checkbox', 'name' => 'site['.$package.']', 'label' => $package, 'value' => 'on', 'class' => 'inline');
	}
	
	$devnames = array('alex', 'chris', 'jake', 'jeff', 'jodi', 'john', 'penny', 'tolley');
	
	// site directories
	if ($tdirs) {
		$field[] = array('element' => 'comment', 'value' => br().br().'<strong>Site Directories</strong>');
		$field[] = array('element' => 'group', 'class' => 'search-directories');
		$field[] = array('element' => 'checkbox', 'name' => 'directory[a~l~l]', 'label' => '<strong><em>search all</em></strong>', 'title' => 'checking this searches all subdirectories', 'class' => 'inline');
		foreach ($tdirs as $tdir) {
			$tdirlabel = $tdir;
			if (in_array($tdir, $devnames)) $tdirlabel = '<strong>'.$tdir.'</strong>';
			$field[] = array('element' => 'checkbox', 'name' => 'directory['.$tdir.']', 'label' => $tdirlabel, 'class' => 'inline');
		}	
		$field[] = array('element' => 'group');
	}
	
	// excluded directories
	$field[] = array('element' => 'text', 'name' => 'other', 'label' => br().br().'<strong>Sub-directories to exclude</strong>', 'size' => 50);
	
	if (FORM::complete($field, array('redisplay', 'submit' => 'Search'))) {
		$dirs = array();
		
		$main = array_keys(param('main', 'value', array()));
		if (in_array('a~l~l', $main)) $main = array_keys($maindirs);
		
		$direct = array_keys(param('directory', 'value', array()));
		if (in_array('a~l~l', $direct)) $direct = $tdirs;
		
		$site = array_keys(param('site', 'value', array()));
		if (array_key_exists('a~l~l', $site)) $site = array_keys($packages);

		foreach ($maindirs as $dir => $details) {
			if (in_array($dir, $main)) {
				$subdirs = array();
				if (array_key_exists('subdirs', $details)) $subdirs = explode(' ', $details['subdirs']);
												
				foreach ($packages as $package => $pdir) {
					if (in_array($package, $site)) {
						$mdir = substr($pdir, 0, strlen($pdir)-1);
						self::addSubDirectories($dirs, $pdir.$dir, $subdirs);
					}
				}
				foreach ($tdirs as $tdir) if (in_array($tdir, $direct)) self::addSubDirectories($dirs, $tdir.'/'.$dir, $subdirs);
			}
		}
		
		$ext = param('ext', 'value', array());
		if (array_key_exists('a~l~l', $ext)) {
			$searchext = array_keys($exts);
		} else {
			$searchext = array_keys($ext);
		}
		
		// process directories to exclude
		if ($otherdirs = explode(',', param('other'))) {
			$odirs = array();
			foreach ($otherdirs as $odir) if ($odir = trim($odir)) $dirs[] = '-'.$odir;
		}
		
		$dirs = array_unique($dirs);
		sort($dirs);
		
		$searchoptions = array(
			'file-ext' => $searchext,
			'where' => $dirs,
			'time' => true,
		);
		
		$option = param('option', 'value', array());
		foreach ($option as $opt => $on) $searchoptions[$opt] = true;
		
		SEARCH::execute(param('text'), $searchoptions);
	}
}

function versionCheck() {	
	if (param('list', 'exists')) {
		PHPDOC::includedClasses();
	} else {
		if (function_exists('membersOnly')) membersOnly();
		$sites = page('value', get('PHPDOC-check-sites'));
		$history = param('h', 'value', -1);
		$showunmatched = param('u', 'exists');
		
		if ($history == -1) {
			$historylink = LINK::paramtag(page(), array('h' => 5), 'Limit history to last 5', LINK::rtn());
		} else {
			$historylink = LINK::paramtag(page(), array('h' => -1), 'Don&rsquo;t limit history', LINK::rtn());
		}
		
		if (!$showunmatched) {
			$matchlink = LINK::paramtag(page(), array('u' => null), 'Show unmatching', LINK::rtn());
		} else {
			$matchlink = LINK::paramtag(page(), '', 'Show all classes', LINK::rtn());
		}
		
		echo div(array('class' => 'version-links'), $historylink.'|'.$matchlink);
		
		$check = '';
		if (is_array($sites)) {
			foreach ($sites as $site) {
				append($check, trim($site), ',');
			}
		} else {
			$check = $sites;
		}

		PHPDOC::check($check, $history, $showunmatched);
	}
}

function displayRecent() {	
	function displayRecent($title, $changes) {
		$titles = array(
			'af' => 'Added Functions:',
			'uf' => 'Updated functions:',
			'rf' => 'Removed functions:',
			'al' => 'Added Libraries:',
			'ul' => 'Updated Libraries:',
			'rl' => 'Removed Libraries:',
			'tl' => 'Tools:',
			'cm' => 'General Comments:',
		);

		echo "<h1>Changes to PHP Libraries since <strong><em>$title</em></strong></h1>\n";
		foreach ($changes as $section => $names) {
			if ($names) {
				echo "<br /><h2>$titles[$section]</h2>\n";
				echo "<ul class='recent-change-list'>\n";
				foreach ($names as $name => $desc) {
					echo "<li class='recent'>";
					switch ($section) {
						case 'af':
						case 'uf':
						case 'al':
						case 'ul':
							@list($class, $fname) = explode('::', $name);
							if (PHPDOC::findClassFile($class)) { // only show classes relevent to this site.
								$linkname = $class;
								if ($fname) {
									$linkname .= '#'.PHPDOC::fixFunctionName($fname);
									$classversion = '';
								} else {
									$classversion = PHPDOC::versionNumber($class);
								}
								echo LINK::local("dev:core/doc=$linkname", "$name $classversion", LINK::rtn())." <span class='description'>$desc</span>";
							}
							break;
							
						case 'rf':
						case 'rl':
							echo "$name <span class='description'>$desc</span>";
							break;
						
						case 'cm':
							if (!is_numeric($name)) echo "<span class='lead-in'>$name</span><br />";
							echo $desc;
							break;
						default:
							echo LINK::local($name, $desc, LINK::rtn());
					}
					echo "</li>\n";
				}
				echo "</ul>\n";
			}
		}
	}

	function showArchive($changes, $current) {
		echo "<div class='recent-archive'>\n";
		echo "<h4>Change Archive</h4>\n";
		foreach (array_keys($changes) as $title) {
			if ($title == $current) {
				echo "<div>$title</div>\n";
			} else {
				echo "<div>".LINK::paramtag('', array(page() => 'recent', 'when' => $title), $title, LINK::rtn())."</div>\n";
			}
		}
		echo "</div>\n";
	}

	$dir = FILE::get('core/recent', 'parts');
	if (is_dir($dir)) {
		$recentFiles = FILE::getlist($dir, array('file-ext' => 'html'));

		$allchanges = array();
		foreach ($recentFiles as $file) {
			$f = "$dir/$file";
			include $f; // grab all these
			$allchanges[FILE::name($file)] = $changes;
		}
		krsort($allchanges);

		$current = param('when', 'value', array_shift(array_keys($allchanges)));

		showArchive($allchanges, $current);

		displayRecent(date('F j, Y', strtotime($current)), $allchanges[$current]);
	}
}

function compare () {
	$server = $_SERVER['HTTP_HOST'];
    $site = param('site', 'value', 'core');
    $dir = param('dir', 'value', '../'.$site);
    
    $filelist = FILE::getlist($dir, array('file=ext' => array('html', 'css', 'js', 'php')));
    $dirs = array();
    $files = array();
    foreach ($filelist as $newdir => $file) {
        if ($file == '') { // this is a dir
            $did = count($dirs);
            $dirname = substr($newdir, strlen($dir)+1);
            $dirs[] = 'element:checkbox | group:df | class:compare-dir | name:dir-'.$dirname.' | label:'.$dirname.' | id:dir'.$did;
        } else { // this is a file in the current directory
            $fid = count($files);
            $files[] = 'element:checkbox | group:df | class:compare-file | name:file-'.$fid.' | label:'.$file.' | id:file-'.$fid;
        }
    }
    
    $fields = array(
        'element:popup | name:site | values:(chds:CHDS ~ hsdl:HSDL ~ hsaj:HSAJ ~ core:CORE) | value:'.$site,
        'element:text | name:dir | value:'.$dir.' | hidden',
    );
    if ((count($dirs) > 0) || (count($files) > 0)) {
        $fields[] = 'element:group | min:1 | max:1 | name:df | title:Directory or File';
        if (count($dirs) > 0) $fields = array_merge($fields, $dirs);
        if (count($files) > 0) $fields = array_merge($fields, $files);
    }
    $fields[] = 'element:group-end';
    
    if (FORM::complete($fields, 'method:get')) {
        $results = FORM::getFieldPairs($fields);
        foreach ($results as $name => $state) {
            if ($state == 'on') {
                break;
            }
        }
        list($type, $value) = explode('-', $name, 2);
        if ($type == 'dir') { // a subdirectory was selected, so drop into that dir
            $newdir = $dir.'/'.$value;
            LINK::redirect(page('fullhost').'?'.page('site', ':').page().'&amp;tool='.param('tool').'&amp;dir='.$newdir);
            
        } else {  // a file was selected so compare it
            list($www_dev, $site, $ext) = explode('.', $server);
            foreach ($fields as $field) {
                if (!is_array($field)) $field = strtoarray($field);
                if (array_key_exists('id', $field) && ($field['id'] == $name)) {
                    $value = $field['label'];
                    break;
                }
            }
            $leftSite = 'dev.'.$site.'.'.$ext;
            $leftFile = $dir.'/'.$value;
            $leftText = self::get_contents($leftSite, $leftFile);
            
            $rightSite = 'www.'.$site.'.'.$ext;
            $rightFile = $dir.'/'.$value;
            $rightText = self::get_contents($rightSite, $rightFile);
            
            echo COMPARE::text ($leftText, $rightText, $leftSite.' '.$leftFile, $rightSite.' '.$rightFile);
        }
    }
}

function get_contents($site, $file) {
    if ($site == $_SERVER['HTTP_HOST']) {
        $result = file_get_contents($file);
    } else {
        $path = FILE::path($file);
        $f = FILE::name($file).'.'.FILE::ext($file);
        
        $url = 'https://'.$site.'/?dev:core/retrieve&drm='.$path.'&f='.$f.'&code='.UTIL::secretCode();
        
        $result = file_get_contents($url);
    }
    
    return $result;
}
    
function googlesafe() {
    $fields = array('element:text | name:url | label:Enter the URL of the site you want to check | size:100 | required:true');
    
    if (FORM::complete($fields)) {
        $url = 'http://www.google.com/safebrowsing/diagnostic?site='.param('url');
        LINK::redirect($url);
    }
}

}
?>