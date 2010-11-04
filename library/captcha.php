<?php

/**
 * A generalized CAPTCHA generator with lots of options.  The image method provides the image for use.
 *
 * @author	Alex Bentley
 * @history	1.0	initial release
 */
class CAPTCHA {

function set($length=6) {
}

/**
 *	Generate CAPTCHA image. This is the function most will use to access this functionality.
 *	Gets the captcha string from the session.
 *
 * @param	$options	an optional array of values to use when generating the CAPTCHA image.
 * @see		captchaDefaults
 */
function image($options=array()) {
	static $defaults = array(
		'width' => 200, // width of image
		'height' => 75, // height of image
		'frames' => 8, // number of frames
		'randomness' => 10, // how much randomness to add
		'background-color' => 0, // 0 white, 1 random background color, all frames the same. 2 every frame has a random background color
		'character-color' => 3, // 0  all are black, 1 key random color, 2 key character random color, 3 key random color in every frame
		'size-variance' => 10, // how much do we change the size of each character
		'rotation' => 20, // how much do we rotate each character
		'speed' => 60, // how fast do we run the animation
		'color' => true, // do we use color or just grays?
		'scale' => .5, // how big should characters be relative to height
		'obscure' => 'noise', // method used to obscure the text
		'use-seed' => false, // numeric if a seed value should be used or false
		'font-dir' => '/usr/share/fonts/bitstream-vera', // the directory of font files to use
	);
	$options = array_merge($defaults, $options);
	
	if ($options['use-seed']) srand ($options['use-seed']);

	$characters = str_split('23456789abcdefghjkmnpqrstuvwxyz');
	$codekeys = array_rand($characters, 6);
	$string = '';
	foreach ($codekeys as $key) $string .= $characters[$key];
	$_SESSION['captcha'] = $string;
	
	$font = FILE::getlist($options['font-dir'], array('file-ext' => 'ttf', 'absolute'));
	$fontsize = $options['height'] * $options['scale'];
	
	$animation = '';
	$im = imageCreate($options['width'], $options['height']);

	imageColorAllocate($im, 0, 0, 0);
	imageColorAllocate($im, 255, 255, 255);

	for ($i = 1; $i < 256; $i++) {
		$r = rand(16, 240);
		$g = rand(16, 240);
		$b = rand(16, 240);
		if (!$options['color']) {
			$b = $r;
			$g = $r;
		}
		imageColorAllocate($im, $r, $b, $g);
	}

	$background = 1;
	if ($options['background-color'] > 0) $background = rand(2, 255);

	// compute size, angle, and placement for code characters
	$color = 0;
	if ($options['character-color'] > 0) $color = rand(2, 255);

	for ($i = 0; $i < strlen($string); $i++) {
		$code[$i]->fontsize = rand($fontsize - $options['size-variance'], $fontsize + $options['size-variance']);
		$code[$i]->rotation = rand(-$options['rotation'], $options['rotation']);
		
		$code[$i]->color = $color;
		if ($options['character-color'] > 1) $code[$i]->color = rand(2, 255);
		
		$char = substr($string, $i, 1);
		$fnt = $options['font-dir'].'/'.$font[rand(0, count($font)-1)];
		
		$box = imageTtfBbox($code[$i]->fontsize, $code[$i]->rotation, $fnt, $char);
		$code[$i]->x = ($i + 0.5) * $options['width'] / (strlen($string) + 1) + ($options['width'] / (strlen($string) + 1) - $box[4] + $box[6]) / 2;
		
		$box = imageTtfBbox($code[$i]->fontsize, $code[$i]->rotation, $fnt, '|');
		$code[$i]->y = $options['height'] / 2 - $box[7] / 3;
		
		if ($options['frames'] > 1) {
			$code[$i]->Skip = rand(0, $options['frames'] - 1);
		} else {
			$code[$i]->Skip = 2;
		}
	}

	// insure that at least 1 code character is skipped in first frame
	for ($i = 0; $i < strlen($string); $i++) {
		if ($code[$i]->Skip == 0) break;
	}
	if ($options['frames'] > 1) if ($i < strlen($string)) $code[rand(0, strlen($string - 1))]->Skip = 0;

	// build frames
	$animation = '';
	
	for ($frame = 0; $frame < $options['frames']; $frame++) {
		if ($options['background-color'] > 1) $background = rand(2, 255);
		
		imageFilledRectangle($im, 0, 0, $options['width'], $options['height'], $background);

		// draw code
		for ($i = 0; $i < strlen($string); $i++) {
			if ($code[$i]->Skip == $frame) continue;
			
			if ($options['character-color'] > 2) $code[$i]->color = rand(2, 255);
			$fnt = $options['font-dir'].'/'.$font[rand(0, count($font)-1)];
			
			imageTtfText($im, $code[$i]->fontsize, $code[$i]->rotation, $code[$i]->x, $code[$i]->y, $code[$i]->color, $fnt, substr($string, $i, 1));
		}
		
		switch ($options['obscure']) {
			case 'shapes':
				self::addShapes($im, $options);
				break;
				
			case 'noise':
				self::noise($im, $options);
				break;
				
			case 'letters':
				self::addCharacters($im, $options, $font, $fontsize, round($options['randomness']/2));
				break;
				
			default:
		}
		
		self::addFrame($animation, $im, $options);
	}
	$animation .= ';';
	imageDestroy($im);
	
	while(ob_end_clean());
	header("Content-type: image/gif");
	header('Cache-control: no-cache, no-store');
	echo $animation;
}

/**
 * Add an image as a frame to a GIF animation.
 *
 * @param	$animation	the current animation.
 * @param	$image		the image to add.
 * @param	$options	the options array.
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
 * Adds noise to an image.
 *
 * @param	$image	the image to modify
 * @param	$options	the options array.
 */
function noise($image, $options) {
	for ($x = 0; $x < imagesx($image); $x++) {
		for ($y = 0; $y < imagesy($image); $y++) {
			if (!rand(0, 3)) {
				$color = rand(2, 255);
				imagesetpixel($image, $x, $y, $color);
			}
		}
	}
}

/**
 * Adds random characters to an image.
 *
 * @param	$image	the image to modify
 * @param	$options	the options array.
 * @param	$font	the font to use
 * @param	$fontsize	the font size to use.
 * @param	$randomness	a randomness factor to use.
 */
function addCharacters($image, $options, $font, $fontsize, $randomness) {
	// draw random characters
	$characters = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
	for ($i = 1; $i < $randomness; $i++) {
		$fnt = $options['font-dir'].'/'.$font[rand(0, count($font)-1)];
		
		$char = $characters[rand(0, count($characters)-1)];
		$size = rand($fontsize - $options['size-variance'], $fontsize + $options['size-variance']);
		$angle = rand(-$options['rotation'], $options['rotation']);
		
		$box = imageTtfBbox($size, $angle, $fnt, $char);
		
		$x = rand(0, $options['width'] - $box[4]);
		$y = rand($box[1], $options['height']);
		
		$color = 0;
		if ($options['character-color'] > 0) $color = rand(2, 255);
		
		imageTtfText($image, $size, $angle, $x, $y, $color, $fnt, $char);
	}
}

/**
 * Adds shapes to an image.
 *
 * @param	$image	the image to modify
 * @param	$options	the options array.
 */
function addShapes($image, $options) {
	$width = imagesx($image);
	$height = imagesy($image);
	
	$mask = imageCreate($width, $height);
	
	for ($i = 0; $i < $options['randomness']; $i++) {
		$x1 = rand(0, $width/2);
		$x2 = rand($x1+$minwidth, $width-1);
		
		$y1 = rand(0, $height/2);
		$y2 = rand($y1+$minheight, $height-1);

		$color = rand(2, 255);
		
		imagefilledrectangle ($mask, 0, $width-1, 0, $height-1, 0); // start with black
		
		$shape = rand(0, 2);
		switch ($shape) {
			case 0: // ellipse
			$tempmask = imageCreate($width, $height);
			imagefilledellipse ($tempmask, ($x2+$x1)/2, ($y2+$y1)/2, ($x2-$x1), ($y2-$y1), $color);
			$angle = rand(-90, 90);
			$tempmask = imagerotate($tempmask, $angle, 0);
			imagecopy ($mask, $tempmask, 0, 0, 0, 0, imagesx($tempmask), imagesx($tempmask));
			imageDestroy($tempmask);
			break;
						
			case 1: // polygon
			$points = rand(3, 8);
			
			$point = array();
			for ($p = 0; $p < $points; $p++) {
				$point[] = rand(0, $width-1);
				$point[] = rand(0, $height-1);
			}
			imagefilledpolygon($mask, $point, $points, $color);
			break;
			
			case 2: // arc
			$tempmask = imagecreatetruecolor($width, $height);
			imagefilledarc ($tempmask, ($x2+$x1)/2, ($y2+$y1)/2, ($x2-$x1), ($y2-$y1), rand(0,360), rand(0, 360), $color, rand(0, 4));
			$angle = rand(-90, 90);
			$tempmask = imagerotate($tempmask, $angle, 0);
			imagecopy ($mask, $tempmask, 0, 0, 0, 0, imagesx($tempmask), imagesx($tempmask));
			imageDestroy($tempmask);
			break;
			
			default:
		}

		self::changecolor($image, $mask);
	}

	imageDestroy($mask);
}

/**
 * Changes colors of pixels based on a mask.
 *
 * @param	$image	the image to modify
 * @param	$mask	the image to use as a mask.
 */
function changecolor($image, $mask) {	
	for ($x = 0; $x < imagesx($image); $x++) {
		for ($y = 0; $y < imagesy($image); $y++) {
			$maskcolor = imagecolorat($mask, $x, $y);
			$color = imagecolorat($image, $x, $y) ^ $maskcolor;			
			imagesetpixel($image, $x, $y, $color);
		}
	}
}

}

?>