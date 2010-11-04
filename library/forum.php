<?php

/**
 * FORUM is intended to replace the GALLERY and RSS classes and provide a common API for a variety of formats of structured data.
 *
 * @author	Alex Bentley
 * @history	1.1		new display options
 *			1.0		initial release
 */
class FORUM {

function defaults() {
	$defaults = array(
		'structure' => 'forum',
		'comments' => false,
		'ignore' => array(get('meta-file')),
		'file-ext' => array('dir', 'txt', 'xml', 'html', 'php', 'jpg'),
		'recursive' => false,
		'data-format' => 'php-serialized', // { php-serialized | xml }
		'list-item' => 200,
		'serve-images' => false,
		'permit-sub-threads' => false,
		'permit-comments' => false,
		
		'forum' => array(
			'permit-sub-threads' => true,
			'permit-comments' => true,
		),
		
		'blog' => array(
			'permit-comments' => true,
		),
		
		'rss' => array(
			'file-ext' => array('*'),
			'namespace' => 'xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd"',
			'itunes' => array(
				'author' => 'bentley',
				'image' => 'http://www.chds.us/images/ViewpointsRSS.jpg',
				'keywords' => 'Photos',	
				'category'	=> array(
					'Education',
				),
				'explicit' => 'clean',
				'owner' => array(
					'email'	=> 'bentley.photographic@gmail.com',
					'name'	=> 'The Feed',
				),
				'summary' => 'This is the feed in a nutshell',
			),
		),
		
		'gallery' => array(
			'file-ext' => array('jpg', 'dir'),
			'format' => 'autoflow',
		),
		
		'faq' => array(
			'file-ext' => array('*'),
		),
	);
	
	return $defaults;
}

function display($dir, $options=array()) {
	$options = smart_merge(self::defaults(), strtoarray($options));
	
	// exit early if this is a request to display an item
	if (param('m') != '') {
		self::displayItem($dir, param('m'), $options);
		return;
	}
		
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');
	
	$files = FILE::getlist($currentDir, $options);
	
	echo div('class:forum');

	$meta = self::getMetadata($dir, $files, $options);

	// display a breadcrumb
	echo self::createBreadcrumb($dir, $options, $meta);
		
	$filenames = array();
	$subdirs = array();
	if (is_array($files)) {
		foreach ($files as $key => $file) {
			if (!$file) { // $key is a directory
				$subdirs[] = $key;
			} else {				
				$filenames[] = $currentDir.'/'.$file;
			}
		}
	}

	// display Directory level meta data
	$m = $meta[$currentDir];	
	$title = h1('class:metadata-short', $m['short']);
	if ($m['long'] != '') $title .= p('class:file-details', $m['long']);
	
	$countdir = count($subdirs);
	$count = count($filenames);
	
	if ($countdir && !$count) {
		echo $title;
		$title = '';
	}
	
	if ($countdir) { // display directory info
		$result = ulist('class:directory');
		foreach ($subdirs as $subdir) {
			$m = $meta[$subdir];
			$sdir = substr($subdir, strlen($base)+1);
			$linklabel = $m['short'];
			if ($m['icon'] != '') {
				$icon = $currentDir.'/'.$m['icon'];
				$linklabel = IMG::tag($icon, array('box' => $options['list-item'], 'alt' => '', 'serve' => $options['serve-images'], 'within'));
			}
			$link = LINK::paramtag(page(), array('g' => $sdir), $linklabel, LINK::rtn());
			$metadata = p('class:metadata').span('class:short | style:font-weight:bold; color:#565495;', $m['short']);
			if ($m['long'] != '') $metadata .= br().span('class:long', $m['long']);
			$metadata .= p('/');
			$result .= li('class:list-item', $link.$metadata);
		}
		$result .= ulist('/').div('style:clear: left;').div('/');
		echo $result;
	}
	
	echo $title;
	
	if ($count) {
		switch ($options['structure']) {
			case 'forum':
			case 'blog':
				self::displayFORUM($dir, $filenames, $options);
				break;
				
			case 'rss':
				self::displayRSS($dir, $filenames, $options);
				break;
				
			case 'gallery':
				self::displayGallery($dir, $filenames, $options);
				break;
				
			case 'faq':
				self::displayFAQ($dir, $filenames, $options);
				break;
				
			default:
		}
	}
	echo div('/');
}

function displayItem($dir, $file, $options) {
	$defaults = self::defaults();
	$options = smart_merge($defaults, $options); // get type specific defaults
	
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');
	
	$files = FILE::getlist($currentDir, $options);
	$item = $file;
	$itemname = $currentDir.'/'.$item;
	
	if (param('in') != 'in') {
		echo div('class:forum');

		$meta = self::getMetadata($dir, $files, $options);
			
		// build prev/next links
		$index = array_search($item, $files);
		$navlink = LINK::url(page(), array('g' => $dir));
		
		$prev = max(0, $index-1);
		$prevlink = LINK::url($navlink, array('m' => $files[$prev]));
		$previmg = 'images/forum/prev.png';
		if ($prev == $index) $previmg = 'images/forum/grayprev.png';
		$control['prev'] = LINK::local($prevlink, IMG::tag($previmg, array('alt' => 'Previous', 'box' => 15)), LINK::rtn());
			
		$next = min(count($files)-1, $index+1);
		$nextlink = LINK::url($navlink, array('m' => $files[$next]));
		$nextimg = 'images/forum/next.png';
		if ($next == $index) $nextimg = 'images/forum/graynext.png';
		$control['next'] = LINK::local($nextlink, IMG::tag($nextimg, array('alt' => 'Next', 'box' => 15)), LINK::rtn());
		
		// display a breadcrumb
		echo self::createBreadcrumb($dir, $options, $meta, $control);
	} else {
		ob_clean();
	}
	
	$imgw = $options['width'];
	$imgh = $options['width'];
	if (in_array($options['format'], array('across', 'right', 'left'))) {
		$imgw = $imgw - 16;
		$imgh = $imgh - 16;
	}
	
	if (in_array($options['format'], array('right', 'left'))) {
		$imgw = $imgw - 125;
	}
	
	// scale image to fit
	list ($w, $h) = IMG::getscaledsize($itemname, $imgw, $imgh);

	// build image tag
	if ($options['serve-images']) {
		$serve = LINK::url(get('file-serve'), array('drm' => $currentDir, 'f' => $item, 'w' => $w, 'h' => $h));
		echo '<img src="?'.$serve.'"/>';
	} else {
		echo '<img src="'.$itemname.'" width="'.$w.'" height="'.$h.'" style="margin: 0; padding: 0;" />';
	}
	
	if (param('in') == 'in') exit(0);
}

function getMetadata($dir, $files, $options) {
	// gather metadata for all files
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');

	$meta = array();
	$meta[$base] = META::getFileMetadata($base);	
	
	$meta[$currentDir] = META::getFileMetadata($currentDir);
	if (count($files)) {
		foreach ($files as $subdir => $file) {
			if (!$file) {
				$meta[$subdir] = META::getFileMetadata($subdir);
			} else {
				$meta[$file] = META::getFileMetadata($file);
			}
		}
	}
	
	return $meta;
}

function createBreadcrumb($dir, $options, $meta, $control='') {
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');

	$result = div('class:file-path');	// display a breadcrumb
	$subdirs = explode('/', $dir); // get the path pieces that are beyond the base directory
	if (!$options['recursive']) {
		$path = '';
		$bc = LINK::local(page(), $meta[$base]['short'], LINK::rtn());
		if ($dir) foreach ($subdirs as $subdir) {
			append($path, $subdir, '/');
			$asd = $options['base'].'/'.$subdir;
			$meta[$asd] = META::getFileMetadata($asd);
			$m = $meta[$asd];
			
			append($bc, LINK::paramtag(page(), array('g' => $path), $m['short'], LINK::rtn()), ' / ');
		}
		$result .= $bc;
	}
	
	if (is_array($control)) {
		$result .= div('style:float: right; position: relative; display: inline;');
		foreach ($control as $what => $link) $result .= $link;
		$result .= div('/');
	}
	
	$result .= div('/');
	
	return $result;
}

function displayFORUM($dir, $files, $options) {
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');

	$meta = self::getMetadata($dir, $files, $options);
			
	if ($count) {
		foreach ($filenames as $file) {
			echo div('class:forum-item');
			switch(FILE::ext($file)) {
				case 'php':
				case 'xml':
				case 'txt':
				case 'html':
					echo self::getItem($file, $options);
					break;
					
				case 'jpg':
					if ($options['serve-images']) {
						$serve = LINK::url(get('file-serve'), array('drm' => FILE::mapDir($currentDir), 'f' => FILE::filename($file), 'w' => $options['width']));
						echo '<img src="'.$serve.'"/>';
					} else {
						echo IMG::tag($itemname, array('box' => $options['width']));
					}
					break;
					
				default:
			}
			echo div('/');
		}
	}
	
}

function displayRSS($dir, $files, $options) {
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');

	$meta = self::getMetadata($dir, $files, $options);

	$m = $meta[$currentDir];		
	$today = date('r'); // Sat, 10 Mar 2005 15:16:08 MST
	$link = LINK::url(page());
	
	
	$result = "\n".'<rss version="2.0" '.$options['namespace'].'><channel>'; // initialize result
	$result .= '<title>'.$m['short'].'</title>';
	$result .= '<link>'.$link.'</link>';
	$result .= '<description>'.$m['long'].'</description>';
	$result .= '<language>en-us</language>';
	$result .= '<pubDate>'.$today.'</pubDate>';
	$result .= '<lastBuildDate>'.$today.'</lastBuildDate>';
	$result .= '<generator>RSS 1.1.2</generator>';
	$result .= '<managingEditor>'.$options['owner'].'</managingEditor>';
	$result .= '<webMaster>'.$options['owner'].'</webMaster>';
	
	$itunes = $options['itunes'];
	if (is_array($itunes)) {
		foreach ($itunes as $section => $value) {
			switch($section) {
				case 'author':
				case 'keywords':
				case 'explicit':
				case 'summary':
					$result .= '<itunes:'.$section.'>'.$value.'</itunes:'.$section.'>';
					break;
				
				case 'image':
					$result .= '<itunes:image href="'.$value.'" />';
					break;
				
				case 'category':
					foreach ($value as $cat) $result .= '<itunes:category text="'.$cat.'" />';
					break;
					
				case 'owner':
					$result .= '<itunes:'.$section.'>';
					foreach ($value as $name => $val) $result .= '<itunes:'.$name.'>'.$val.'</itunes:'.$name.'>';
					$result .= '</itunes:'.$section.'>';
					break;
					
				default:
			}
		}
	}
	
	if (is_array($files)) {	
		
		foreach($files as $file) {
			$content = self::getItem($file, $options);
			$attachment = '';
			$m = $meta[$currentDir.'/'.$file];
			switch (FILE::ext($file)) {
				case 'jpg':
					$attachment = $file;
					break;
					
				case 'php':
				default:
			}
			$itemurl = 'http://'.$_SERVER['HTTP_HOST'].'/'.$file;
			$itemurl = str_replace(' ', '%20', $itemurl);
			$result .= '<item>';
			$title = str_replace('&rsquo;', '&apos;', $m['short']);
			$desc = str_replace('&rsquo;', '&apos;', $m['long']);
			
			$result .= '<title>'.$title.'</title>';
			$result .= '<link>'.$itemurl.'</link>';
			$result .= '<guid>'.$itemurl.'</guid>';
			$result .= '<description>'.$desc.'</description>';
			
			$lmd = date ($RSSdateformat, filemtime($file));
			$size = filesize($file);
			$result .= '<pubDate>'.$lmd.'</pubDate>';
			
			if ($attachment != '') {
				$type = FILE::mimetype($attachment);
				$result .= '<enclosure url="'.$itemurl.'" length="'.$size.'" type="'.$type.'" />';
			}
			
			$result .= '</item>';
		}
	}

	$result .= '</channel></rss>';

	ob_clean(); // remove any prior output
	
	header('Content-type: application/rss+xml');
	echo '<'.'?xml version="1.0" encoding="utf-8" ?'.'>';
	echo $result;
	
	exit();
}

function displayGallery($dir, $files, $options) {
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');
	
	$meta = self::getMetadata($dir, $files, $options);
	
	// process images to get details
	$link = LINK::url(page(), array('g' => $dir));
	$serve = LINK::url(get('file-serve'), array('drm' => $currentDir));
	
	$images = array();
	if (is_array($files)) {
		foreach ($files as $file) {
			$linkname = LINK::url($link, array('m' => basename($file)));
			if ($options['serve-images']) {
				$servename = LINK::url($serve, array('f' => basename($file)));

			} else {
				$servename = $file;
			}
			
			list ($w, $h) = getimagesize($file);
			$short = $meta[$file]['short'];

			if ($w != 0) { // skip bad images
				$images[$servename] = array(
					'title' => $short, 
					'serve' => $options['serve-images'], 
					'src' => $servename, 
					'link' => $linkname, 
					'height' => $h, 
					'width' => $w, 
					'fullname' => $file, 
					'file' => basename($file), 
					'dir' => $dir,
				);
			} else {
				DEBUG::display($filename.' is an invalid image.');
			}
		}
	}
	
	$count = count($images);
	
	if ($count) {
		// get layout information
		$max = $options['width'];
	
		switch ($options['gallery']['format']) {
			case 'list':
				$result = '';
				foreach ($images as $image => $opts) {
					$link = LINK::url('', 
						array(page() => 'm', 
							'in' => 'in', 
							'd' => $opts['dir'], 
							'm' => $opts['file'],
						));
					$fullname = $opts['fullname'];
					$m = $meta[$fullname];
					$result .= div('class:list-item');
					$result .= LINK::local($link, 
						IMG::tag($image, array_merge($opts, array('box' => $options['list-item'], 'alt' => '', 'serve' => $options['serve-images'], 'within'))), LINK::rtn()).' ';

					$result .= p('class:metadata-short', $m['short']);
					if ($m['long'] != '') $result .= br().span('metadata-long', $m['long']);

					$result .= br().$opts['width'].' X '.$opts['height'];
					if ($m['short'] != $opts['file']) $result .= br().$opts['file'];
					
					$result .= p('/').div('/');
				}
				echo $result;
				break;
				
			case 'block':
				$blocks = array();
				foreach ($images as $image) $blocks[] = array('w' => $image['width'], 'h' => $image['height']);
				
				$half = floor($count/2);
				
				$left = $half;
				$right = $count - $half;
				
				$sizes = BLOCKS::block($blocks, $left, $right);
				
				self::block($left, $right, $images, $max);
				break;
			
			case 'autoflow':				
				while ($count) {					
					$left  = rand(min($count, 2), min($count, 3));
					$right = rand(0, min($count, 3));
					
					$imageCount = $left + $right;
					if ($imageCount > $count) {
						$left = ceil($count/2);
						$right = $count - $left;
					}
					$imageCount = $left + $right;

					$imgs = array();
					while ($imageCount) { 
						$imageCount--; 
						$imgs[] = array_shift($images); 
						$count--; 
					}
					
					$blocks = array();
					foreach ($imgs as $image) $blocks[] = array('w' => $image['width'], 'h' => $image['height']);
					
					if ($right == 0) {
						$sizes = BLOCKS::across($blocks, $left);
						self::across($imgs, $sizes, $left);
					} else {
						if (rand(0, 1)) {
							$sizes = BLOCKS::block($blocks, $left, $right);
							self::block($imgs, $sizes, $left, $right);
						} else {
							$sizes = BLOCKS::block($blocks, $right, $left);
							self::block($imgs, $sizes, $right, $left);
						}
					}
				}
				break;
				
			case 'across':
			case 'right':
			case 'left':
				$fmt = $options['format'];
				
				$div = array(
					'across' => div('style:overflow: auto; overflow-y: hidden; height: 125px; width: '.$max.'px; white-space: nowrap; margin-bottom: 10px;'),
					'right' => div('style:float: right; overflow: auto; overflow-x: hidden; height: '.$max.'px; width: 120px; white-space: wrap;'),
					'left' => div('style:float: left; overflow: auto; overflow-x: hidden; height: '.$max.'px; width: 120px; white-space: wrap; margin-right: 10px;'),
				);
				
				$imgopts = array(
					'across' => array('height' => 100, 'style' => 'margin: 10px 0 0 10px; padding: 0;', 'alt' => '', 'serve' => $details['serve-images'], 'within'),
					'right' => array('width' => 100, 'style' => 'margin: 10px 0 0 0; padding: 0;', 'alt' => '', 'serve' => $details['serve-images'], 'within'),
					'left' => array('width' => 100, 'style' => 'margin: 10px 0 0 0; padding: 0;', 'alt' => '', 'serve' => $details['serve-images'], 'within'),
				);
								
				$first = '';
				$items = '';
				foreach ($images as $image => $opts) {
					$link = LINK::url('', array(page() => 'm', 'in' => 'in', 'g' => str_replace($options['base'].'/', '', $opts['dir']), 'm' => $opts['file']));
					if ($first == '') $first = $link;
					append($items, LINK::local($link, IMG::tag($image, array_merge($opts, $imgopts[$fmt])), array('return', 'target' => 'detail')), ' ');
				}
				
				$framewidth = $max;
				if (in_array($fmt, array('right', 'left'))) {
					$framewidth = $framewidth - 125;
				}
				echo $div[$fmt], $items, div('/'), '<iframe src="?'.$first.'" name="'.detail.'" class="'.detail.'" style="height: '.$max.'px; width: '.$framewidth.'px; padding: 0; margin: 0; border: 0px;"></iframe>';
				break;
				
			default:
				break;
		}
	}
}

function displayFAQ($dir, $files, $options) {
	$base = $options['base'];
	$currentDir = $base;
	append($currentDir, $dir, '/');

	$meta = self::getMetadata($dir, $files, $options);

	if (count($files)) {
		echo dlist('class:forum-faq');
		foreach ($files as $file) {
			$m = $meta[$file];
			switch(FILE::ext($file)) {
				case 'php':
				case 'xml':
				case 'html':
				case 'txt':
					$content = self::getItem($file, $options);
					echo dt('', $m['short']).dd('', $content);
					break;
					
				default:
					echo dt('', $m['short']).dd('', $m['long']);
					break;
			}
		}
		echo div('/');
	}
}

function setItem($dir, $object, $options) {
	switch ($options['data-format']) {
		case 'php-serialized':
			$content = serialize($object);
			break;
			
		case 'xml':			
		default:
			$content = strval($object);
			break;
	}
	FILE::write($dir, $content);
}

function getItem($dir, $options) {
	switch ($options['data-format']) {
		case 'php-serialized':
			$content = unserialize(file_get_contents($file));
			break;
			
		case 'xml':
		default:
			$content = file_get_contents($file);
	}

	return $content;
}

/**
 * Scales all images so their heights are all the same and their combined width plus padding fits within the maximum
 *
 * @param  $images	a simple array of images.
 * @param  $n		the number of images.
 * @param  $max		maximum allowed width.
 * @return			the html to layout the images within the allowed space.
 * @see				formatImage
 */
function across($images, $sizes, $n) {	
	// create the HTML for this image block
	$result = div('class:image-block | style:width: 100%;');
	foreach ($images as $image) {
		$size = array_shift($sizes);
		$result .= self::formatImage($image, $size['w'], $size['h']);
	}
	$result .= div('/');
	
	echo $result;
}

/**
 * Creates a two column layout where the width of both columns plus padding fits within the maximum
 *
 * @param  $left	the number of images for the left column.
 * @param  $right	the number of images for the right column.
 * @param  $images	a simple array of images.
 * @param  $max		maximum allowed width.
 * @return			the html to layout the images within the allowed space.
 * @see				formatImage
 */
function block($images, $sizes, $left, $right) {
	$index = 0;
	$result = div('class:image-block | style:margin: 0; width: 100%;').div('class:left-column | style:width: '.$sizes['l'].'%;');
	
	foreach ($images as $image) {
		$size = array_shift($sizes);
		
		if ($i == $left) $result .= div('/')."\n".div('class:right-column | style:width: '.$sizes['r'].'%;'); // putting the right column in a separate div makes the CSS simpler

		$result .= self::formatImage($image, $size['w'], $size['h']);
		$index++;
	}
	$result .= div('/').div('/');
	
	echo $result;
}

/**
 * Produces a formatted image link based on passed parameters
 *
 * @param  $image	an image file name.
 * @param  $w		width.
 * @param  $h		height.
 * @return			the html to display the image.
 * @see				LINK::local
 */
function formatImage($image, $w, $h) {
	$details = array(
					  'alt'   => '',
					  'link'  => '',
					  'title' => '',
					  'serve' => false,
					  );
					  
	foreach (array('alt', 'title', 'serve', 'link', 'style') as $item) if (array_key_exists($item, $image)) $details[$item] = $image[$item];
	$items = array('alt', 'title', 'style');
	
	$style = 'margin: 0; padding: 0;';
	if ($h != '') $style .= ' height:'.$h.'%;';
	if ($w != '') $style .= ' width:'.$w.'%;';
	$details['style'] = $style;
	$link = $details['link'];
	
	foreach (array('alt', 'title') as $item) $details[$item] = str_replace("'", '&quot;', $details[$item]);
	
	// get the image tag
	if ($details['serve']) {
		$imgurl = '?'.$image['src'];
		$img    = '<img src="'.$imgurl.'"';
		foreach ($items as $item) append($img, $item.'="'.$details[$item].'"', ' ');
		append($img, '/>', ' ');
	} else {
		$options = '';
		$img    = '<img src="'.$image['src'].'"';
		foreach ($items as $item) append($img, $item.'="'.$details[$item].'"', ' ');
		append($img, '/>', ' ');
	}
	
	// create the HTML for this image	
	if ($link != '') { // if a link was specified, include it
		$result = LINK::local($link, $img, 'return | style:margin: 0; padding: 0;');
	} else {
		$result = $img;
	}
	
	// wrap this in a fixed size div
	$result = div('class:fixed | style:overflow:hidden; '.$style, $result);
	
	return $result;
}

}
?>