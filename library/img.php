<?php

/**
 * IMG encapsulates all the image manipulation functions.
 * All functions are intended to be used statically.
 * 
 * @author	Alex Bentley
 * @history	3.2		new forms of options
 *			3.1		fix to generalize the serve function handling of buffering
 *			3.0		new box-position attribute
 *			2.19	fix to getScale function
 *			2.18	all images are not absolute links due to redirect issues
 *			2.17	fix to scale
 *			2.16	fix to scaling calculation
 *			2.15	standardized tag generation
 *			2.14	integrated icon support
 *			2.13	updated documentation
 *			2.12	removed dependence on ABOUT class
 *			2.11	new function getscaledsize
 *			2.10	removed deprecated calls
 *			2.9		provide support for inline images
 *			2.8		updated serving processing
 *			1.0		initial release
 * 
 */
class IMG {
 
/**
 * Returns an array of default values for images.
 *
 * @return	the keyed array of values.
 * @details	alt;		image;			alt tag for image
 *			width;		0;		 		width of image - 0 means use actual image size
 *			height;		0;		 		height of image - 0 means use actual image size
 *			box;		0;				size of image as a square limit box - 0 means use actual image size
 *			box-position;	none;		where the image is placed within the box
 *			scale;		1;				how much to scale this image as a decimal rate
 *			class;		;				CSS class
 *			id;			;				CSS id
 *			title;		;				HTML title
 *			within;		false;			limit scale to 1
 *			style;		;				include this string in the style tag
 *			url;		relative;		use relative URLs or external ones
 *			serve;		false;			set up tag as a served image
 *			inline;		false;			set up tag as an embedded image
 *          usemap;     ;               if this image uses a map, what is the name
 *          border;     ;               specify a border if any
 */
function defaults() {
	return array(
		'alt'           => 'image',	// alt tag for image
		'width'         => 0, 		// width of image - 0 means use actual image size
		'height'        => 0, 		// height of image - 0 means use actual image size
		'box'           => 0,		// size of image as a square limit box - 0 means use actual image size
		'box-position'  => 'none',	// where the image sits within the box if one is specified
		'scale'         => 0,		// how much to scale this image as a decimal rate
		'class'         => '',		// CSS class
		'id'            => '',		// CSS id
		'title'         => '',		// HTML title
		'within'        => false,	// limit scale to 1
		'style'         => '',      // include this string in the style tag
		'url'           => 'relative',// use relative URLs or external ones
		'serve'         => false,	// set up tag as a served image
		'inline'        => false,	// set up tag as an embedded image
		'usemap'        => '',      // if this image uses a map, what is the name
		'border'        => '',      // specify a border if any
	);
}

/**
 * Create an image tag in a standard way. If the image file does not exist, return nothing. Allows for scaling the image.
 *
 * @param	$src		the location of the image file.
 * @param	$options	an optional keyed array of values to use for generating the img tag.
 * @return	the HTML img tag.
 * @see		smart_merge
 * @see		HTML::quotes
 */
function tag ($src, $options=array()) {
	$options = as_array($options);
	
	$def = smart_merge(self::defaults(), $options);

	if (!str_begins($src, '?')) {
		$src = SITE::file($src);
		if (!file_exists($src)) return ''; // no image if no file
	}
	
	$def['src'] = $src;
	
	list($w, $h) = self::size($src);

	if ($def['box'] != 0) {
		if ($w > $h) { // use the larger of the two to define the scale
			$def['width'] = $def['box'];
			$def['height'] = 0;
		} else {
			$def['width'] = 0;
			$def['height'] = $def['box'];
		}
	}
    
    // if one dimension is not set force the scale to 1 so the other will be the basis
    if ((($def['width'] == 0) || ($def['height'] == 0)) && ($def['scale'] == 0)) $def['scale'] = 1;
	
    if (($def['width'] == 0) && ($def['height'] == 0)) {
        $def['width'] = $w;
        $def['height'] = $h;
    }
    
	if ($def['scale'] == 1) $def['scale'] = self::getscale($src, $def['width'], $def['height']);
	
	if (($def['scale'] > 1) && $def['within']) $def['scale'] = 1; // don't rescale if within is set

	if ($def['scale'] != 0) {
        $def['width']  = (int)($w * $def['scale']);
        $def['height'] = (int)($h * $def['scale']);
    }
        
	if ($def['url'] != 'relative') {
		$def['src'] = page('server').$def['src'];
	} else {
		if ($def['inline']) {
			$def['src'] = self::inlinesrc($src, $def['width'], $def['height']);
		} else if ($def['serve']) {
			$src = urldecode($src);
			$dir = FILE::mapDir(FILE::path($src));
			$file = FILE::filename($src);
            $uri = page('uri');
			$def['src'] = LINK::url(substr($uri, 0, strpos($uri, '?')).'?'.get('file-serve'), array('drm' => $dir, 'w' => $def['width'], 'h' => $def['height'], 'f' => $file));
		} else {
			$def['src'] = str_replace(' ', '%20', $def['src']);
		}
	}
	
	// see if we need to put the image in a box margin: T R B L
	if ($def['box'] != 0) {
		$difw = $def['box'] - $def['width'];
		$difl = ceil($difw/2);
		$difr = floor($difw/2);
		
		$difh = $def['box'] - $def['height'];
		$dift = ceil($difh/2);
		$difb = floor($difh/2);
		
        $margin = 'margin:';
		switch ($def['box-position']) {
			case 'bottom-right':    $edges = array($difh, 0,     0,     $difw); break;
			case 'bottom-center':   $edges = array($difh, $difr, 0,     $difl); break;
			case 'bottom-left':     $edges = array($difh, $difw, 0,     0);     break;
				
			case 'middle-right':    $edges = array($dift, $difw, $difb, 0);     break;
			case 'middle-center':   $edges = array($dift, $difr, $difb, $difl); break;
			case 'middle-left':     $edges = array($dift, $difw, $difb, 0);     break;
				
			case 'top-right':		$edges = array(0,     $difw, $difh, 0);     break;
			case 'top-center':      $edges = array(0,     $difl, $difh, $difr);	break;
			case 'top-left':		$edges = array(0,     0,     $difh, $difw); break;
            
			default:                $edges = array();     $margin = '';         break;
		}
        
        foreach ($edges as $edge) append($margin, $edge.'px', ' ');
        if ($margin != '') $def['style'] .= ' '.$margin.';';
	}
	
	$attrs = array();
	
	$attrlist = array('src', 'class', 'id', 'height', 'width', 'alt', 'title', 'border', 'style', 'usemap');
	foreach ($attrlist as $attr) $attrs[$attr] = $def[$attr];

	return tag('img', $attrs);
}

/**
 * Return the dimensions of an image if it exists.
 *
 * @param	$file		the location of the image file.
 * @return	an array of the dimensions of the image or and array of 0,0.
 */
function size($file) {
	if (file_exists($file)) {
		return getimagesize($file);
	} else {
		return array(0, 0);
	}
}

/**
 * Return the scale factor for an image to fit within the specified bounding box or dimension if none is specified.
 *
 * @param	$file		the location of the image file.
 * @param	$width		the desired width of the image.
 * @param	$height		the desired height of the image.
 * @return	the scale factor.
 */
function getscale($file, $width=0, $height=0) {
	if (!file_exists($file)) return 1;
	
	list($realWidth, $realHeight) = self::size($file); // get actual values
	$s1 = 10000;
	$s2 = 10000;
	
	if (($width > 0) && ($realWidth > 0)) $s1 = $width/$realWidth;
	if (($height > 0) && ($realHeight > 0)) $s2 = $height/$realHeight;
	
	$scale = min($s1, $s2);
	if ($scale == 10000) $scale = 1;
	
	return $scale;
}

/**
 * Return the dimensions for an image to fit within the specified bounding box or dimension if none is specified.
 *
 * @param	$file		the location of the image file.
 * @param	$width		the desired width of the image.
 * @param	$height		the desired height of the image.
 * @return	the scaled dimensions.
 */
function getscaledsize($file, $width=0, $height=0) {
	if (!file_exists($file)) return false;
	
	$size = self::size($file);
	$s1 = 10000;
	$s2 = 10000;
	
	if ($width > 0) $s1 = $width/$size[0];
	if ($height > 0) $s2 = $height/$size[1];
	
	$scale = min($s1, $s2);
	if ($scale == 10000) $scale = 1;
	
	return array(floor($scale*$size[0]), floor($scale*$size[1])); 
}

/**
 * Return a scaled version of the image, or save to a file.
 *
 * @param	$file		the location of the image file.
 * @param	$t_wd		the desired width of the image.
 * @param	$t_ht		the desired height of the image.
 * @param	$mime		an optional mime type for the image.
 * @return	the image object.
 */
function scaleToImage($file, $t_wd = 100, $t_ht = 100, $mime=null) {
	$image_info = self::size($file) ; // see EXIF for faster way
	
	if ($mime == null) $mime = $image_info['mime']; // use mime type of original image if not specified
	$supportedImages = array(
        'image/gif'  => array('type' => IMG_GIF,  'function' => 'imagecreatefromgif',  'name' => 'GIF'),
        'image/jpeg' => array('type' => IMG_JPG,  'function' => 'imagecreatefromjpeg', 'name' => 'JPEG'),
        'image/png'  => array('type' => IMG_PNG,  'function' => 'imagecreatefrompng',  'name' => 'PNG'),
        'image/wbmp' => array('type' => IMG_WBMP, 'function' => 'imagecreatefromwbmp', 'name' => 'WBMP'),
    );
    
	$imgtypes = imagetypes();
    if (array_key_exists($image_info['mime'], $supportedImages)) {
        $si = $supportedImages[$image_info['mime']];
        
        if ($imgtypes & $si['type']) {
            $o_im = $si['function']($file) ;
        } else {
            $ermsg = $si['name'].' images are not supported'.br();
        }
    } else {
        $ermsg = $image_info['mime'].' images are not supported'.br();
	}
	
	if (!isset($ermsg)) {
		$o_ht = imagesy($o_im);
		$o_wd = imagesx($o_im);

		$t_im = imageCreateTrueColor($t_wd, $t_ht);

		imageCopyResampled($t_im, $o_im, 0, 0, 0, 0, $t_wd, $t_ht, $o_wd, $o_ht);
		imageDestroy($o_im);
		return $t_im;
	} else {
		return null;
	}
}

/**
 * Output a scaled version of the image, or save to a file.
 *
 * @param	$file		the location of the image file.
 * @param	$t_wd		the desired width of the image.
 * @param	$t_ht		the desired height of the image.
 * @param	$file		an optional file to save the image to.
 * @param	$mime		an optional mime type for the image.
 * @return	any error that may have occured.
 */
function scaleImage($o_file, $t_wd = 100, $t_ht = 100, $file=null, $mime=null) {
	$image_info = self::size($o_file) ; // see EXIF for faster way
	
	if ($mime == null) $mime = $image_info['mime']; // use mime type of original image if not specified
	
	$t_im = self::scaleToImage($o_file, $t_wd, $t_ht, $mime);
	if ($t_im != null) {
		if ($file == null) header('Content-type: '.$mime);
		
		switch ($mime) {
			case 'image/gif':	imageGIF($t_im, $file);                     break;
			case 'image/jpeg':	imageJPEG($t_im, $file, 100);               break;
			case 'image/png':	imagePNG($t_im, $file, 9, PNG_NO_FILTER);	break;
			case 'image/wbmp':	imageWBMP($t_im, $file);                    break;
			default:            $ermsg = $mime.' images are not supported'.br();
		}

		imageDestroy($t_im);
	}
	return isset($ermsg)?$ermsg:NULL;
}

/**
 * Serve a scaled version of the image with full header support.
 *
 * @param	$file		the location of the image file.
 * @param	$w			the desired width of the image.
 * @param	$h			the desired height of the image.
 * @return	any error that may have occured.
 */
function serve($file, $w, $h) {
	$image_info = self::size($file) ; // see EXIF for faster way
	$mime = $image_info['mime'];
	
	while(@ob_end_clean());	// remove any prior buffers
	
	// output all header data
	header('Content-Disposition: inline; filename='.$file);  
	header('Cache-Control: must-revalidate,post-check=0,pre-check=0');
	header('Expires: 0');
	header('Pragma: public');	
	header('Last-Modified: '. gmdate('D, d M Y H:i:s', filemtime($file)) .' GMT');

	self::scaleImage($file, $w, $h, null, $mime);
	ob_end_flush(); // turn off buffering and output page data
	exit();
}

/**
 * Return external image data for an image object.
 *
 * @param	$im		the image object.
 * @param	$mime	the desired mime type for the image.
 * @return	the external image data.
 */
function imageData($im, $mime='image/jpeg') {
	// extract imagedata
	ob_start();
		switch($mime) {
			case 'image/gif':	imageGIF($im); break;
			case 'image/jpeg':	imageJPEG($im, null, 100); break;
			case 'image/png':	imagePNG($im, null, 9, PNG_NO_FILTER); break;
			case 'image/wbmp':	imageWBMP($im, null); break;
			default:
		}
	$img = ob_get_contents();
	ob_end_clean();
	return $img;
}

/**
 * Return an inline image tag for an image object.
 *
 * @param	$im		the image object.
 * @param	$mime	the desired mime type for the image.
 * @param	$alt	the alt text for the image.
 * @return	the inline img tag.
 */
function inline($im, $mime='image/jpg', $alt='image') {
	// extract imagedata
	$img = self::imageData($im, $imie);
	
	//encode it into an line image tag
	$imgdata = base64_encode($img);
	return tag('img', array('src' => 'data:'.$mime.';base64,'.$imgdata, 'alt' => $alt));
}

/**
 * Return an inline image tag src value for an image file.
 *
 * @param	$file		the image file.
 * @param	$w			the desired width of the image.
 * @param	$h			the desired height of the image.
 * @param	$mime		the desired mime type for the image.
 * @return	the inline img tag.
 */
function inlinesrc($file, $w, $h) {
	$image_info = self::size($file) ; // see EXIF for faster way
	$mime = $image_info['mime'];
	
	//encode it into an line image tag
	$imgdata = base64_encode(self::imageData(self::scaleToImage($file, $w, $h)));
	return 'data:'.$mime.';base64,'.$imgdata;
}

/**
 * Serve a scaled version of the image with full header support.
 *
 * @param	$file		the location of the image file.
 * @param	$w			the desired width of the image.
 * @param	$h			the desired height of the image.
 * @return	any error that may have occured.
 */
function image($file, $w, $h) {
	self::serve($file, $w, $h);
}

/**
 * Returns an appropriate icon based on type
 *
 * @param	$name	name of file or operation
 * @param	$type	{ op | ext | eil }
 * @return	returns the correct icon
 */
function icon($name, $type='op', $options=array()) {
	if (is_string($options)) $options = strtoarray($options);
	
	$file = '';
	$iconoptions = array();
	$defaulticons = array(
		'op' => array(
			'rename'    => 'Rename.gif',
			'edit'      => 'Edit.gif',
			'move'      => 'Move.gif',
			'delete'    => 'Delete.gif',
			'copy'      => 'Copy.gif',
			'newfolder' => 'NewFolder.gif',
			'newfile'   => 'NewFile.gif',
		),
		'ext' => array(
			'Video'		=> array('3gp', 'm4v', 'mov', 'avi', 'mpg'),
			'Picture'	=> array('jpg', 'png', 'psd', 'gif', 'tif', 'tiff', 'pict'),
			'Text'		=> array('txt', 'rtf', 'rtfd', 'odt', 'xml', 'css', 'html'),
			'Word'		=> array('doc', 'docx'),
			'Audio'		=> array('mp3', 'mp4', 'wav', 'aac', 'flac'),
			'PDF'		=> array('pdf'),
			'RTF'		=> array('rtf', 'rtfd', 'rtfm'),
			'Flash'		=> array('flv', 'swf'),
			'Zip'		=> array('zip', 'gzip', 'tar', 'sit'),
			'Presentation'	=> array('ppt'),
			'Unknown'	=> array('*'),
		),
		'eil' => array(
			'edit-icon'   => 'icon_edit.gif',
			'delete-icon' => 'icon_delete.gif',
			'add-icon'    => 'icon_add.gif',
		),
	);
	$icons = array_merge($defaulticons, get('icons', array()));
	if ($type == 'ext') {
		if (is_dir($name)) {
			$icon = 'Folder';
		} else {
			$ext = FILE::ext($name);
			foreach ($icons['ext'] as $icon => $map) {
				if (array_search($ext, $map) !== false) {
					break;
				}
			}
		}
		$file = HTML::siteFile('images/icons/ext/'.$icon.'.gif');
		if (!file_exists($file)) $file = HTML::siteFile('images/icons/ext/Unknown.gif');
		$iconoptions = array_merge(array('box' => 24), $options);
		
	} else { // other
		if (array_key_exists($type, $icons)) $file = 'images/icons/'.$type.'/'.$icons[$type][$name];
		$iconoptions = $options;
	}

	return IMG::tag($file, $iconoptions);
}

} // end IMG class

function img ($src, $options=array()) {
    return IMG::tag($src, $options);
}

?>