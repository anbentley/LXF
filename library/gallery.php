<?php

/**
 * GALLERY incapsulates all functionality to produce a file gallery.
 * There are a number of configurable entries, but commonly only one method is used.
 *
 * The most commonly used method is display.
 *
 * Below is an example of a gallery in use:
 *
 *   GALLERY::display(array('base' => 'images', 'thumbs' => true, 'width' => 320, 'height' => '240'));
 *
 * only one value is required: base - this defines where the gallery directory exists.
 *
 * This same function can also return an RSS feed based on the files in the base directory. Subdirectories are ignored for this
 * format. Below is an example of this function invoked for an RSS feed.
 *
 * 	 self::display(array('base' => 'images', 'format' => 'RSS'));
 * 
 * @author      Alex Bentley
 * @history     3.1     updated flow+ layout to be more complex and have larger photos
 *              3.0		modified layout when format is list and top level is just directories
 *				2.16	fixed text links
 *				2.15	updated directory mapping
 *				2.14	removed dependence on ABOUT class
 *				2.13	updated documentation
 *				2.12	across, right, and left fixes
 *				2.11	fix to flow layout for performance
 *				2.10	removed deprecated calls
 *				2.9		added directory based formats
 *				2.8		corrected file size issue
 *				1.0		initial release
 */
class GALLERY {

/**
 * Returns an array of default values for the gallery.
 *
 * @return	the array of default values.
 * @details	width;				100;	width of thumbnails
 *			height;				100;	height of thumbnails
 *			format;				list;	format { list | RSS | block | across | left | right | flow }
 *			back;				true;	show a back link
 *			recursive;		 	false;	display all contents including subdirectories in one list
 *			thumbs;				false;	display thumbnails of image files
 *			file-details;	 	full;	display all file details (size, LM date, type)
 *			order-by;		 	name;	what order to display the files { name | lmd }
 *			downloadable;		false;	do we provide direct links to the files?
 *			prevnext;		 	false;	display previous / next links in detail pages
 *			back-link;		 	&crarr;	text of back link
 *			prev-link;		 	&lt;		text of prev link
 *			next-link;		 	&gt;		text of next link
 *			icon-width;		 	200;	allowed width of directory icon
 *			icon-height;		200;	allowed height of directory icon
 *			detail-width;		300;	allowed width of detail item
 *			detail-height;		240;	allowed height of detail item
 *			layout-size;		600;	use this width for custom layouts
 *			use-link-images;	false;	use thumbnails as prev/next links
 *			image-link-height;	75;		size of link images
 *			serve-images;		false;	should PHP create images on the fly rather than using HTML to resize them?
 *			inline-images;		true;	should PHP embed the images?
 *			constrain-full-image;	true;	should we constrain the full size image?
 */
function defaults() {
	return 	array(
		'width'                => 100,          // width of thumbnails
		'height'               => 100, 			// height of thumbnails
		'format'               => 'list', 		// format { list | RSS | block | across | left | right | flow }
		'back'                 => true, 		// show a back link
		'recursive'            => false,        // display all contents including subdirectories in one list
		'thumbs'               => false, 		// display thumbnails of image files
		'file-details'         => 'full',		// display all file details (size, LM date, type)
		'order-by'             => 'name', 		// what order to display the files { 'name' | 'lmd' }
		'downloadable'         => false, 		// do we provide direct links to the files?
		'prevnext'             => false,		// display previous / next links in detail pages
		'back-link'            => '&crarr;', 	// text of back link
		'prev-link'            => '&lt;', 		// text of prev link
		'next-link'            => '&gt;',       // text of next link
		'icon-width'           => 200,			// allowed width of directory icon
		'icon-height'          => 200,			// allowed height of directory icon
		'detail-width'         => 300,			// allowed width of detail item
		'detail-height'        => 240,			// allowed height of detail item
		'layout-size'          => 600,			// use this width for custom layouts
		'use-link-images'      => false,		// use thumbnails as prev/next links
		'image-link-height'    => 75,			// size of link images
		'serve-images'         => false, 		// should PHP create images on the fly rather than using HTML to resize them?
		'inline-images'        => true,         // should PHP embed the images?
		'constrain-full-image' => true,         // should we constrain the full size image?
	);		
}

/**
 * Removes 'bad' entries from a path.
 *
 * @param	$path	the path to clean up.
 * @return			the fixed path.
 */
function cleanPath($path) {
	$p2 = $path;
	do {
		$p1 = $p2;
		$p2 = str_replace('/..', '', $p1);
	} while ($p1 != $p2);
	
	return $p2;
}

/**
 * Displays a gallery.
 *
 * @param	$details	configuration values for this gallery.
 * @see		mediaItem
 * @see		arrayToAnimation
 * @see		arrayToRSS
 * @see		specialLayout
 */
function display($details) { 
	if (is_string($details)) $details = strtoarray($details);
	$details = smart_merge(self::defaults(), $details);
	$details['base'] = SITE::file($details['base']);
	
	// see if there is an internal functional request here... 
	if (page('value') != '') { // there was a gallery function
		if (param('in') != '') ob_clean(); // remove any prior output
		
		if (page('value') == 'animate') {
			$dir = param('in');
			$files = FILE::getlist($dir, array('file-ext' => '*', 'order-by' => $details['order-by'], 'recursive' => $details['recursive'], 'ignore' => array(get('meta-file'))));
			self::arrayToAnimation($dir, $files, $details);
		} else {
			echo self::mediaItem(param('m'), param('d'), $details);
		}
		// enable if the layout is not flow or normal
		if (param('in') != '') {
			exit();
		}
		
		return;
	}
	
	// display a list
	$d = param('d');
	$d = FILE::dir($d, false); // map from logical to physical
		
	$dir = $details['base'];

	if ($d != '') {
		if (!str_begins($d, $dir)) {
			$dir .= '/'.$d;
		} else {
			$dir = $d;
		}
	}
	
	$dir = SITE::file($dir);
	
	if (!file_exists($dir)) return;
	
	if (!is_dir($dir)) { // this is just a file
		echo div('class:file', self::filelink($file, $dir, '', '', $details));
		
	} else { // this is a directory
		$files = FILE::getlist($dir, array('file-ext' => '*', 'order-by' => $details['order-by'], 'recursive' => $details['recursive'], 'ignore' => array(get('meta-file'))));
	
		if (in_array($details['format'], array('block', 'across', 'left', 'right', 'flow', 'autoflow'))) { // don't use these formats if subdirectories exist
			if (is_array($files)) {
				foreach($files as $file) {
					if (is_dir($dir.'/'.$file)) {
						$details['format'] = 'list';
						break;
					}
				}
			}
		}
		
		$meta = META::get($dir);
		if (array_key_exists('format', $meta)) {
			$details['format'] = $meta['format']['value'];
		}
		
		switch ($details['format']) {
			case 'list':
				echo self::arrayToDisplay($dir, $files, $details);
				break;
			
			case 'block':
			case 'across':
			case 'left':
			case 'right':
			case 'flow':
			case 'autoflow':
				echo self::specialLayout($dir, $files, $details);
				break;
			
			case 'RSS':
				echo self::arrayToRSS($dir, $files, $details);
				break;
				
			case 'animate':
				echo div('class:file', "<img src='?".LINK::url('', array(page() => 'animate', 'in' => $dir))."' />");
				break;
			
			default:
		}
	}
}

/**
 * Converts an array of files to a GIF animation.
 *
 * @param	$dir		the directory where the files reside.
 * @param	$files		the array of file names.
 * @param	$details	configuration values for this gallery.
 */
function arrayToAnimation($dir, $files, $details) {
	$animation = '';
	foreach ($files as $file) {
		$im = null;
		$fn = $dir.'/'.$file;
		
		$image_info = IMG::size($fn) ; // see EXIF for faster way
		
		$mime = $image_info['mime']; // use mime type of original image if not specified
		
		$imgtypes = imagetypes();
		switch ($image_info['mime']) {
		   case 'image/gif':
			   if ($imgtypes & IMG_GIF)  { // not the same as IMAGETYPE
				   $im = imageCreateFromGIF($fn) ;
			   }
			   break;
		   case 'image/jpeg':
			   if ($imgtypes & IMG_JPG)  {
				   $im = imageCreateFromJPEG($fn) ;
			   }
			   break;
		   case 'image/png':
			   if ($imgtypes & IMG_PNG)  {
				   $im = imageCreateFromPNG($fn) ;
			   }
			   break;
		   case 'image/wbmp':
			   if ($imgtypes & IMG_WBMP)  {
				   $im = imageCreateFromWBMP($fn) ;
			   }
			   break;
			   
		   default:
			   break;
		}
		self::addFrame($animation, $im, array('speed' => 1));
		if ($im != null) imageDestroy($im);
		$count++;
	}
		
	ob_end_clean();
	Header('Content-type: image/gif');
	header('Cache-control: no-cache, no-store');
	echo $animation;
	exit();
}

/**
 * Adds a frame to a GIF animation.
 *
 * @param	$animation	the GIF animation.
 * @param	$image		the image object to add.
 * @param	$options	configuration values for this gallery.
 */
function addFrame(&$animation, $image, $options) {
	ob_start();
	imageGif($image);
	$gif = ob_get_contents();
	ob_end_clean();

	// extract and append
	$data = unpack('C*', $gif);
	$start = 14 + 3 * (2 << ($data[11] & 0x07));
	$size = count($data);
	
	if ($animation == '') $animation = 'GIF89a'.substr($gif, 6, $start-7).pack('C3', 33, 255, 11).'NETSCAPE2.0'.pack('C5', 3, 1, 0, 0, 0);
	$animation .= pack('C8', 33, 249, 4, 8, $options['speed'], 0, 0, 0).substr($gif, $start-1, $size-$start);
}

/**
 * Converts an array of files to a gallery.
 *
 * @param	$dir		the directory where the files reside.
 * @param	$files		the array of file names.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the gallery.
 */
function arrayToDisplay($dir, $files, $details) {
	$result = ''; // initialize result
	
	if (($dir != $details['base']) && !$details['recursive']) {
		$back = dirname($dir);
		$result .= div('class:file-nav', self::link($back, $details['back-link'], '', $details));
	}
	
	$directory = substr($dir, strlen($details['base'])+1);

	if (($dir == $details['base']) || (($dir != $details['base']) && !$details['recursive'])) {
		$meta = META::get($dir);
		$short = $meta['short'];
		$long = $meta['long'];
		$short = str_replace(array('{', '}', '&'), array("<", ">", '&amp;'), $short);
		$long = str_replace(array('{', '}', '&'), array("<", ">", '&amp;'), $long);

		$result .= p('class:directory');
		if ($long != '') $result .= span('class:file-details', $long);
		$result .= p('/');
	}
	
	if (!is_array($files)) {
		$result .= p('', 'empty directory');
		
		return $result;
	}
	
	$result .= ul('class:gallery');
	foreach($files as $key => $value) {
		$location = "$dir/$value";
		if (str_begins($dir, $details['base'])) {
			$directory = substr($dir, strlen($details['base'])+1);
		} else {
			$directory = $dir;
		}
		
		if (is_numeric($key)) { // this is a leaf node (file)
			$meta = META::get($dir.'/'.$value);
			$short = $meta['short'];
			$long = $meta['long'];
			$short = str_replace('&', '&amp;', $short);
			$long = str_replace('&', '&amp;', $long);

			switch (FILE::ext($value)) {
				case 'jpg':
				case 'png':
				case 'gif':
					if ($details['thumbs']) {
						$result .= li('class:file-image');
						$result .= LINK::paramtag('', array(page() => 'm', 'd' => $directory, 'm' => $value), 
									self::imageItem($value, $location, $short, $long, $details), array('return'));
						$result .= li('/');
						break;
					}

				case 'mov':
				case 'mpg':
				case 'html':
					$result .= li('class:file', self::mediaLink($value, $directory, $short, $long, $details));
					break;
					
				default:
					$result .= li('class:file', self::filelink($value, $dir, $short, $long, $details));
			}
		
		} else { // this is a directory: $key is name, $value is new content
			$meta = META::get($key);				
			$metaitems = array('short', 'long', 'icon');
			foreach ($metaitems as $mi) {
				$$mi = $meta[$mi];
			}
			$short = str_replace('&', '&amp;', $short);
			$long = str_replace('&', '&amp;', $long);
	
			if ($icon != '') {
				$icon = IMG::tag($details['base'].'/'.$icon, 'alt:'.$short.' | box:'.$details['icon-width'].' | box-position:middle-center | within | class:icon | serve:'.$details['serve-images']);
			}

			if ($long != '') $long = br().span('class:file-details', $long);
			
			if ($details['recursive']) {
				$result .= li('class:directory', $current.$long.self::arrayToDisplay($key, $value, $details));
			} else {
				if ($icon != '') {
					$result .= li('class:directory-icon', self::link($key, $icon.' '.$short, $long, $details));
				} else {
					$result .= li('class:directory', self::link($key, $short, $long, $details));
				}
			}
		}
	}
	$result .= ul('/');

	return $result;
}

/**
 * Converts an array of files to a special layout.
 *
 * @param	$dir		the directory where the files reside.
 * @param	$files		the array of file names.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the gallery.
 */
function specialLayout($dir, $files, $details) {
	$meta = META::get($dir);
	$result = h1('', $meta['short']).p('class:directory');
	if ($meta['long'] != '') $result .= span('class:file-details', $meta['long']);
	$result .= p('/');
	echo $result;
		
	if (($dir != $details['base']) && !$details['recursive']) {
		$back = substr($dir, 0, strrpos($dir, '/'));
		echo div('class:file-nav', self::link($back, $details['back-link'], '', $details));
	}
	
	$max = $details['layout-size'];
	$d = substr($dir, strlen($details['base'])+1);
	
	$link = LINK::url('', array(page() => 'm', 'd' => $d));
	$serve = LINK::url(get('file-serve'), array('drm' => $dir));
	
	$images = array();
	if (is_array($files)) {
		foreach ($files as $file) {
			$filename = $dir.'/'.$file;
			$linkname = LINK::url($link, array('m' => $file));
			if ($details['serve-images']) {
				$servename = LINK::url($serve, array('f' => $file));

			} else {
				$servename = $filename;
			}
			
			list ($w, $h) = getimagesize($filename);
			$meta = META::get($dir.'/'.$file);
			$short = $meta['short'];

			if ($w != 0) { // skip bad images
				$images[$servename] = array('title' => $short, 'serve' => $details['serve-images'], 
				'src' => $servename, 'link' => $linkname, 'height' => $h, 'width' => $w, 'fullname' => $filename, 'file' => $file, 'dir' => $dir);
			} else {
				DEBUG::display("$servename is an invalid image.");
			}
		}
	}
	$count = count($images);

	if ($count) {
		switch ($details['format']) {
			case 'block':
				$half = floor($count/2);
				
				$left = $half;
				$right = $count - $half;
				
				echo IMG_LAYOUT::block($left, $right, $images, $max, 6, 0);
				break;
			
			case 'flow':
				$flip = true;
				while ($count) {
					if ($count > 7) {
						$imgs = array();
						for ($i = 1; $i <= 3; $i++) { $imgs[] = array_shift($images); $count--; }
						if ($flip) {
							echo IMG_LAYOUT::block(2, 1, $imgs, $max, 6, 0);
						} else {
							echo IMG_LAYOUT::block(1, 2, $imgs, $max, 6, 0);
						}
						
						$flip = !$flip;
					}
					if ($count == 6) {
						$imgs = array();
						for ($i = 1; $i <= 6; $i++) { $imgs[] = array_shift($images); $count--; }
						echo IMG_LAYOUT::block(3, 3, $imgs, $max, 6, 0);
					}
					
					if ($count > 4) {
						$imgs = array();
						for ($i = 1; $i <= 3; $i++) { $imgs[] = array_shift($images); $count--; }
						echo IMG_LAYOUT::across(3, $imgs, $max, 6, 0);
						
					} else if ($count) {
						$imgs = array();
						for ($i = 1; $i <= $count; $i++) { $imgs[] = array_shift($images); }
						echo IMG_LAYOUT::across($count, $imgs, $max, 6, 0);
						$count = 0;
					}
				}
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
					
					if ($right == 0) {
						echo IMG_LAYOUT::across($left, $imgs, $max, 6, 0);
					} else {
						if (rand(0, 1)) {
							echo IMG_LAYOUT::block($left, $right, $imgs, $max, 6, 0);
						} else {
							echo IMG_LAYOUT::block($right, $left, $imgs, $max, 6, 0);
						}
					}
				}
				break;
				
			case 'across':
				$width = $details['layout-size'];
				
				$result = div('style:overflow: auto; overflow-y: hidden; height: 130px; width: '.$width.'px; white-space: nowrap; margin-bottom: 10px;');
				$first = '';
				foreach ($images as $image => $options) {
					$link = LINK::url('', 
						array(page() => 'm', 
							'in' => 'in', 
							'd' => str_replace($details['base'].'/', '', $options['dir']), 
							'm' => $options['file'],
							'nn',
						));
					if ($first == '') $first = $link;
					$result .= LINK::local($link, 
						IMG::tag($options['fullname'], array_merge($options, array('height' => 100, 'style' => 'margin: 0 0 0 5px; padding: 0;', 'alt' => '', 'serve' => $details['serve-images'], 'within'))),
						array('return', 'target' => 'detail')).' ';
				}
				$result .= div('/');
				$result .= "<iframe src='?$first' name='detail' class='detail' style='height: 720px; width: {$width}px; border: none;'>\n";
				$result .= "</iframe>\n";
				echo $result;
				break;
				
			case 'right':
				$width = $details['layout-size'];
				$result = '';
				$result .= div('style:float: right; overflow: auto; overflow-x: hidden; height: 720px; width: 100px; white-space: wrap;');
				$first = '';
				foreach ($images as $image => $options) {
					$link = LINK::url('', 
						array(page() => 'm', 
							'in' => 'in', 
							'd' => str_replace($details['base'].'/', '', $options['dir']), 
							'm' => $options['file'],
							'nn',
						));
					if ($first == '') $first = $link;
					$result .= LINK::local($link, 
						IMG::tag($options['fullname'], array_merge($options, array('width' => 80, 'style' => "margin: 5px 0 0 0; padding: 0;", 'alt' => '', 'serve' => $details['serve-images'], 'within'))),
						array('return', 'target' => 'detail')).' ';
				}
				$result .= div('/');
				$result .= "<iframe src='?$first' name='detail' class='detail' style='height: 720px; width: {$width}px; border: none;'>\n";
				$result .= "</iframe>\n";
				echo $result;
				break;
				
			case 'left':
				$width = $details['layout-size'];
				$result = '';
				$result .= div('style:float: left; overflow: auto; overflow-x: hidden; height: 720px; width: 100px; white-space: wrap; margin-right: 10px;');
				$first = '';
				foreach ($images as $image => $options) {
					$link = LINK::url('', 
						array(page() => 'm', 
							'in' => 'in', 
							'd' => str_replace($details['base'].'/', '', $options['dir']), 
							'm' => $options['file'],
							'nn',
						));
					if ($first == '') $first = $link;
					$result .= LINK::local($link, 
						IMG::tag($options['fullname'], array_merge($options, array('width' => 80, 'style' => "margin: 5px 0 0 0; padding: 0;", 'alt' => '', 'serve' => $details['serve-images'], 'within'))),
						array('return', 'target' => 'detail')).' ';
				}
				$result .= div('/');
				$result .= "<iframe src='?$first' name='detail' class='detail' style='height: 720px; width: {$width}px; border: none;'>\n";
				$result .= "</iframe>\n";
				echo $result;
				break;
				
			default:
		}
	}
}

/**
 * Builds the HTML to display an item.
 *
 * @param	$file		the name of the file.
 * @param	$location	the directory where the file resides.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function mediaItem($file, $location, $details) {
	$result = '';
	if ($location != '') {
		$loc = $details['base'].'/'.$location;
	} else {
		$loc = $details['base'];
	}

	$meta = META::get($loc.'/'.$file);
	$short = $meta['short'];
	$long = $meta['long'];
	$short = str_replace('&', '&amp;', $short);
	$long = str_replace('&', '&amp;', $long);
	
	if (!param('nn', 'exists')) {
		// add in a back link
		if ($details['prevnext'] && !$details['recursive']) {
			$pnlinks = self::getPrevNext($loc, $details, $file);
			
			$result .= div('class:file-nav');
			
			$result .= self::link($loc, $details['back-link'], '', $details)." ";

			if (array_key_exists('prev', $pnlinks)) {
				$value = $pnlinks['prev'];
				$link = $details['prev-link'];
				
				if ($details['use-link-images'] && (FILE::ext($value) == 'jpg')) { //function serve($dir, $file, $label, $options=array()) {
					$link = IMG::tag("$details[base]/$location/$value", array('height' => $details['image-link-height'], 'alt' => 'prev', 'serve' => $details['serve-images'], 'within'));
				}
					
				$result .= LINK::paramtag('', array(page() => 'm', 'd' => $location, 'm' => $value), $link, array('return', 'title' => 'prev', 'class' => 'prev'));
			} else {
				if (!$details['use-link-images']) {
					$result .= "<a href='#' class='prev'>".$details['prev-link']."</a>";
				} else {
					$result .= "<a href='#' class='prev'>&nbsp;</a>";
				}
			}
			
			if (array_key_exists('next', $pnlinks)) {
				$value = $pnlinks['next'];
				$link = $details['next-link'];
				
				if ($details['use-link-images'] && (FILE::ext($value) == 'jpg')) {
					$link = IMG::tag("$details[base]/$location/$value", array('height' => $details['image-link-height'], 'alt' => 'next', 'serve' => $details['serve-images'], 'within'));
				}
				
				$result .= LINK::paramtag('', array(page() => 'm', 'd' => $location, 'm' => $value), $link, array('return', 'title' => 'next', 'class' => 'next'));
			} else {
				if (!$details['use-link-images']) {
					$result .= "<a href='#' class='next'>".$details['next-link']."</a>";
				} else {
					$result .= "<a href='#' class='next'>&nbsp;</a>";
				}
			}
			$result .= div('class:file-nav-end', '&nbsp;').div('/');
		}
	}
	$extra = '';
	switch (FILE::ext($file)) {
		case 'jpg':
		case 'png':
		case 'gif':
			list($iw, $ih) = getimagesize($loc.'/'.$file);

			if ($details['constrain-full-image']) {
				$scale = min(1, IMG::getscale("$loc/$file", $details['detail-width'], $details['detail-height']));
					
				$w = round($scale * $iw);
				$h = round($scale * $ih);
				$result .= IMG::tag($details['base'].'/'.$location.'/'.$file, array('alt' => $short, 'height' => $h, 'width' => $w, 'serve' => $details['serve-images']));
			} else {
				$result .= "<img src='$loc/$file' alt='$short' />";
			}
			
			$extra = "$iw X $ih px";
			$class = 'file-image';
			break;
			
		case 'mov':
		case 'mpg':
		case 'mp4':
			$result .= self::videoItem($short, $loc.'/'.$file, 700, 600, $details);
			$class = 'file-movie';
			break;
			
		case 'mp3':
			$result .= self::audioItem($file, $loc, $width, 16);
			$class = 'file-audio';
			break;
			
		case 'swf':
			$result .= self::objectItem($file, $loc, $width, $height, 'application/x-shockwave-flash');
			$class = 'file-flash';
			break;
			
		case 'html':
			include $loc.'/'.$file;
			return;
			break;
			
		default:
			$result .= self::filelink($file, $loc, $short, $long, $details);
			$class = 'file';
	}
	
	$result .= br();
	if ($short != '') $result .= span('class:file-details', $short).br();
	if ($details['file-details'] == 'full') {
		if ($extra != '') $result .= span('class:file-details', $extra).br();
		$result .= span('class:file-details', 'Updated: '.date ('M d Y H:i', filemtime($loc.'/'.$file)));
	}
	$result .= br();
	if ($long != '') $result .= span('class:file-details', $long);

	return div('class:'.$class, $result);
}

/**
 * Builds the HTML to display an image.
 *
 * @param	$name		the name of this item.
 * @param	$file		the name of the file.
 * @param	$alt		the text for the alt tag.
 * @param	$long		the long metadata.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function imageItem($name, $file, $alt, $long, $details) {
	list($iw, $ih) = IMG::size($file);
	
	if ($alt == '') $alt = FILE::name($name);
	$alt = str_replace('&', '&amp;', $alt);

	$result = IMG::tag($file, 'within:true | alt:'.$alt.' | box:'.$details['width'].' | height:'.$details['height'].' | serve:'.$details['serve-images']);
	
	$result .= span('class:description');
	if ($alt != '') $result .= span('class:file-details', $alt).br();
	if ($details['file-details'] == 'full') {
		$result .= span('class:file-details', $iw.' X '.$ih.' px');
		$result .= br().span('class:file-details', 'Updated: '.date ('M d Y H:i', filemtime($file)));
	}
	if ($long != '') $result .= br().span('class:file-details', $long);
	$result .= span('/');
	
	return $result;
}

/**
 * Builds the HTML to display an audio file.
 *
 * @param	$name		the name of this item.
 * @param	$file		the name of the file.
 * @param	$width		the width of the item.
 * @param	$height		the height of the item.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function audioItem($name, $file, $width, $height) {
	$http = 'http';
	if (isset($_SERVER['HTTPS'])) $http = 'https';

	$result = "<script src='$jsfile' type='text/javascript'></script>";
	$result .= "<script type='text/javascript'>QT_WriteOBJECT_XHTML ('$file','$width','$height', '$http', '', 'controller', 'true', 'autoplay', 'false');";
	$result .= "</script>";
    $result .= br().span('class:file-details', $name);
	
	return $result;
}

/**
 * Builds the HTML to display a video file.
 *
 * @param	$name		the name of this item.
 * @param	$file		the name of the file.
 * @param	$w			the width of the item.
 * @param	$h			the height of the item.
 * @return	the HTML for the item.
 */
function videoItem($name, $file, $w, $h, $details) {
	$http = 'http';
	if (isset($_SERVER['HTTPS'])) $http = 'https';

	$result = "<script type='text/javascript'>QT_WriteOBJECT_XHTML ('$file','$w','$h', '$http', '', 'controller', 'true', 'autoplay', 'false');</script>";
	$result .= br().span('class:file-details', $name);

	return $result;
}

/**
 * Builds the HTML to display an audio file.
 *
 * @param	$name		the name of this item.
 * @param	$file		the name of the file.
 * @param	$width		the width of the item.
 * @param	$height		the height of the item.
 * @param	$type		the type of the item.
 * @param	$controller		the value for the controller.
 * @param	$autoplay		the value for autoplay.
 * @return	the HTML for the item.
 */
function objectItem($name, $file, $width, $height, $type, $controller='', $autoplay='') {
	$result = "<object type='$type' data='$file' width='$width' height='$height' >
	 <param name='movie' value='$file' /> 
	 <param name='menu' value='true' /> 
	 <param name='quality' value='high' />
	 <param name='bgcolor' value='#FFFFFF' />";
     
	if ($controller != '') $result .= "<param name='controller ' value='$controller' />";
	if ($autoplay != '') $result .= "<param name='autoplay ' value='$autoplay' />";

	$result .= "</object>";
	
	return $result;
}

/**
 * Builds the HTML to display a file link.
 *
 * @param	$file		the name of the file.
 * @param	$dir		the directory of this item.
 * @param	$short		the short metadata of the item.
 * @param	$long		the long metadata of the item.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function filelink($value, $dir, $short, $long, $details) {
	$ext = FILE::ext($value);
	$name = FILE::name($value);
	
	if ($short == '') $short = $name;
	
	$size = FILE::size($dir.'/'.$value);
	
	if ($ext == 'html') {
		$result = LINK::tag($dir/$name, $short, LINK::rtn());
		if ($long != '') $result .= br().span('class:file-details', $long);
	} else {
		$result = LINK::paramtag(get('file-serve'), array('drm' => FILE::dir($dir, true), 'f' => $value), $short, array('return'));
		$result .= span('class:file-details', " [$ext] $size");
		if ($long != '') $result .= br().span('class:file-details', $long);
		$result .= br().span('class:file-details', 'Updated: '.date ('M d Y H:i', filemtime($dir.'/'.$value)));
	}
	
	return $result;
}

/**
 * Builds the HTML to display a media link.
 *
 * @param	$file		the name of the file.
 * @param	$dir		the directory of this item.
 * @param	$short		the short metadata of the item.
 * @param	$long		the long metadata of the item.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function mediaLink($value, $dir, $short, $long, $details) {
	$file = $details['base'].'/'.$dir.'/'.$value;
	
	$ext = FILE::ext($value);
	$name = FILE::name($value);
	
	if ($short == '') $short = $name;
	
	$size = FILE::size($file);
	if ($details['downloadable']) {
		$file = str_replace(' ', '%20', $file);
		$result = "<a href='$file' class='download'>$short</a>";
	} else {
		$result = LINK::paramtag(page(), array('d' => $dir, 'm' => $value), $short, array('return'));
	}
	
	if ($ext != 'html') {
		$result .= span('class:file-details', " [$ext] $size");
	}

	if ($details['downloadable']) {
		$result .= LINK::paramtag(page(), array('d' => $dir, 'm' => $value), 'view', array('return', 'class' => 'button'));
	}

	if ($details['file-details'] == 'full') {
		if (in_array($ext, array('jpg', 'png', 'gif'))) {
			list($iw, $ih) = IMG::size($file);
			$result .= br().span('class:file-details', "$iw X $ih px");
		}
		$result .= br().span('class:file-details', 'Updated: '.date ('M d Y H:i', filemtime($file)));
	}
	if ($long != '') $result .= br().span('class:file-details', $long);
	
	return $result;
}

/**
 * Builds the HTML to display a link.
 *
 * @param	$dir		the directory of this item.
 * @param	$label		the label for this link.
 * @param	$desc		the description of the item.
 * @param	$details	configuration values for this gallery.
 * @return	the HTML for the item.
 */
function link($dir, $label, $desc, $details) {
	$dir = substr($dir, strlen($details['base'])+1);

	if ($desc != '') $desc = br().span('class:file-details', $desc);
	return LINK::paramtag(page(), array('d' => FILE::dir($dir, true)), $label, 'return | class:back').$desc;
}

/**
 * Compute prev/next links.
 *
 * @param	$dir		the directory of this item.
 * @param	$details	configuration values for this gallery.
 * @param	$file		the name of the file.
 * @return	an array of prev/next items.
 */
function getPrevNext($dir, $details, $file) {
	$list = FILE::getlist($dir, array('order-by' => $details['order-by'], 'recursive' => $details['recursive'], 'ignore' => array(get('meta-file'))));
	$count = count($list);
	
	$result = array();
	for ($i = 0; $i < $count; $i++) {
		if (isset($list[$i]) && $file == $list[$i]) { 
			if (isset($list[$i-1])) $result['prev'] = $list[$i-1];
			if (isset($list[$i+1])) $result['next'] = $list[$i+1];
		}
	}
	return $result;
}

/**
 * Determine the mimetype for this file.
 *
 * @param	$value		the name of this item.
 * @return	the mimetype for this file.
 */
function mimetype($value) { // see http://www.webmaster-toolkit.com/mime-types.shtml
	switch (FILE::ext($value)) {
		case 'jpg': $type = 'image/jpeg'; break;
			
		case 'png': $type = 'image/png'; break;
			
		case 'gif': $type = 'image/gif'; break;
			
		case 'mpg': 
		case 'mp4': $type = 'video/mpeg'; break;
			
		case 'mov': $type = 'video/quicktime'; break;
			
		case 'swf': $type = 'application/x-shockwave-flash'; break;
			
		default: // no attachment
	}
	return $type;
}

/**
 * Convert as array to an RSS feed.
 *
 * @param	$dir		the directory of this item.
 * @param	$files		an array of filenames.
 * @param	$details	configuration values for this gallery.
 * @return	an RSS feed.
 */
function arrayToRSS($dir, $files, $details) {
	if (!is_array($files)) return '';
    
	$RSSdateformat = 'D, d M Y H:i:s T';  // Sat, 10 Mar 2005 15:16:08 MST
    $rtn = "\n";
	$rss = '<'.'?xml version="1.0" ?'.'>'; // initialize result
    
    append($rss, '<rss version=\'2.0\'>', $rtn);
    append($rss, '<channel>', $rtn); 
	
	$meta = META::get($dir);
	$short = $meta['short'];
	$long = $meta['long'];
	if ($long == '') $long = $dir;
	$long = str_replace(array('<', '>'), array('&lt;', '&gt;'), $long);

	$today = date($RSSdateformat);
	append($rss, '<title>'.$short.'</title>', $rtn);
	append($rss, '<link>http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'</link>', $rtn);
	append($rss, '<description>'.$long.'</description>', $rtn);
	append($rss, '<language>en-us</language>', $rtn);
	append($rss, '<pubDate>'.$today.'</pubDate>', $rtn);
	append($rss, '<lastBuildDate>'.$today.'</lastBuildDate>', $rtn);
	append($rss, '<generator>GALLERY 1.1</generator>', $rtn);
	append($rss, '<managingEditor>'.webmaster().'</managingEditor>', $rtn);
	append($rss, '<webMaster>'.webmaster().'</webMaster>', $rtn);
	
	foreach($files as $key => $value) {
		$location = "$dir/$value";
		if (is_numeric($key)) { // this is a leaf node (file)
			$meta = META::get($location.'/'.$value);

			$short = str_replace(array('<', '>'), array('&lt;', '&gt;'), $meta['short']);
			$long  = str_replace(array('<', '>'), array('&lt;', '&gt;'), $meta['long']);
			
			$itemurl = str_replace(' ', '%20', 'http://'.$_SERVER['HTTP_HOST'].'/'.$details['base'].'/'.$value);
			append($rss, '<item>', $rtn);
			
			append($rss, '<title>'.$short.'</title>', $rtn);
			append($rss, '<link>'.$itemurl.'</link>', $rtn);
			append($rss, '<description>'.$long.'</description>', $rtn);
			
			$lmd = date ($RSSdateformat, filemtime($location));
			$size = filesize($location);
			append($rss, '<pubDate>'.$lmd.'</pubDate>', $rtn);
			
			$type = '';
			$type = self::mimetype($value);

			if ($type != '') {
				append($rss, '<enclosure url=\''.$itemurl.'\' length=\''.$size.'\' type=\''.$type.'\' />', $rtn);
			}
			
			append($rss, '</item>', $rtn);
		}
	}
	append($rss, '	</channel>', $rtn);
    append($rss, '</rss>', $rtn);

	ob_clean(); // remove any prior output
	header('Content-type: application/rss+xml');
	echo $rss;
	
	exit();
}

}
?>
