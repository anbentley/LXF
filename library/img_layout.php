<?php

/**
 * IMG_LAYOUT takes a set of images and arranges them in a number of interesting ways.
 * 
 * @author	Alex Bentley
 * @history	4.0 tightened up the code a lot
 *          3.1 finally fixed the layout for block so the bottoms line up exactly.
 *          3.0 moved padding into class
 *          2.5	removed dependence on ABOUT class
 *			2.4	updated documentation
 *			2.3	improvement for getAspectRatio to eliminate bad images
 *			2.2	updated serving code
 *			1.0	initial release
 */
class IMG_LAYOUT {

/**
 * Returns an array of aspect ratios of all images.
 *
 * @param  $images	a simple array of images.
 * @return			the array of the version history.
 */
function getAspectRatios($images) {
	$r = array();
	foreach ($images as $image => $details) {
		$w = $details['width'];
		$h = $details['height'];
		if ($w == 0) {
			//echo "$image is $w x $h<br />";
			$r[] = 0; // deal with bad images
		} else {
			$r[] = $h/$w;
		}
	}
	return $r;
}

/**
 * Scales all images so their heights are all the same and their combined width plus padding fits within the maximum
 *
 * @param  $n		the number of images.
 * @param  $images	a simple array of images.
 * @param  $max		maximum allowed width.
 * @return			the html to layout the images within the allowed space.
 * @see				getAspectRatios
 * @see				formatImage
 */
function across($n, $images, $max) {
	$block = $max;
	$r = self::getAspectRatios($images);
	
	foreach ($images as $entry => $details) {
		$image[] = $entry;
		$detail[] = $details;
	}
	
	// compute the multiplication factor (the product of all ratios)
	$factor = 1;
	for ($i = 1; $i < $n; $i++) $factor *= $r[$i];
	
	// compute the division factor  (sum of (product of all ratios missing one ratio))
	// for example: with 3 ratios (r1, r2, & r3) we get (r1*r2 + r1*r3 + r2*r3)
	$divisor = 0;
	for ($i = 0; $i < $n; $i++) {
		$term = 1;
		for ($j = 0; $j < $n; $j++) if ($j != $i) $term *= $r[$j];
		$divisor += $term;
	}
	
	// compute the width of the first image
	$w[0] = round( ($factor*($block - ($n-1)*4)) / $divisor );
	$wid = $w[0] + (get('gallery-padding')*2);
	
	// compute the width of all remaining images (except the last one)
	for ($i = 1; $i < $n-1; $i++) {
		$w[$i] = round($w[0]*$r[0]/$r[$i]);
		$wid += $w[$i] + (get('gallery-padding')*2);
	}

	// compute the width of the last image
	if ($n > 1) $w[$n-1] = $block - $wid - get('gallery-padding');
	
	// compute the height of all images
	$h1 = round($w[0]*$r[0]);
	
	// create the HTML for this image block
	$result = div('class:image-block | style:width: '.$block.'px;');
	for ($i = 0; $i < $n-1; $i++) $result .= self::formatImage($images[$i], $w[$i], $h1, $detail[$i], get('gallery-padding'), get('gallery-padding'), get('gallery-padding'));
	$result .= self::formatImage($images[$n-1], $w[$n-1], $h1, $detail[$n-1], get('gallery-padding'), get('gallery-padding'));

	$result .= div('/');
	
	return $result;
}

/**
 * Creates a two column layout where the width of both columns plus padding fits within the maximum
 *
 * @param  $left	the number of images for the left column.
 * @param  $right	the number of images for the right column.
 * @param  $images	a simple array of images.
 * @param  $max		maximum allowed width.
 * @return			the html to layout the images within the allowed space.
 * @see				getAspectRatios
 * @see				formatImage
 */
function block($left, $right, $images, $max) {
	$block = $max;
    
	$r = self::getAspectRatios($images);
	
	$image = array_keys($images);
	$detail = array_values($images);
	/*
	foreach ($images as $entry => $details) {
		$image[] = $entry;
		$detail[] = $details;
	}
	*/
	// compute width of left column
	$factor1 = 0; // the sum of all ratios
	$factor2 = 0; // the sum of all ratios on the right side
	for ($i = 0; $i < ($left + $right); $i++) {
		$factor1 += $r[$i];
		if ($i >= $left) $factor2 += $r[$i];
	}
	
	$w[0] = round($factor2 * $max/$factor1);
	$wlc = $w[0] + get('gallery-padding')*2;
	
	// use this width for all left images
	for ($i = 1; $i < $left; $i++) $w[$i] = $w[0];	
	
	// compute width of all right images
	$wrc = $block - $wlc;
     
	$wri = $wrc - get('gallery-padding')*2;
    
	for ($i = $left; $i < ($left+$right); $i++) $w[$i] = $wri;
	
	// compute height of all images based on ratios except the last image
	for ($i = 0; $i < ($left + $right - 1); $i++) $h[$i] = round($w[$i] * $r[$i]);
	
	// compute height of left column of images
	$last = 0;
	// add all the left side images
	for ($i = 0; $i < $left; $i++) $last += $h[$i];
	$last += ($left * get('gallery-padding')*2);
		
	// subtract off the height of all the right images except the last to see what is left
	for ($i = $left; $i < ($left + $right-1); $i++) $last -= $h[$i];
	$last -= ($right * get('gallery-padding')*2);
	
	// compute the height of the last image
	$h[$left+$right-1] = $last - get('gallery-padding')*2;
			
	// output the image block
    $pad = get('gallery-padding');
    $leftmargin = 'margin: 0; padding: 0';
    $rightmargin = 'margin: 0; padding: 0';
    
	$result = div('class:image-block | style:width: '.$block.'px;').div('class:left-column | style:width: '.$wlc.'px;');
	for ($i = 0; $i < ($left+$right); $i++) {
		// putting the right column in a separate div makes the CSS simpler
		if ($i == $left) $result .= div('/')."\n".div('class:right-column | style:width: '.$wrc.'px;');
		$result .= self::formatImage($images[$i], $w[$i], $h[$i], $detail[$i]);
	}
	$result .= div('/').div('/');
	
	return $result;
}

/**
 * Produces a formatted image link based on passed parameters
 *
 * @param  $image	an image file name.
 * @param  $w		width.
 * @param  $h		height.
 * @param  $details	a keyed array of options.
 * @param  $tpad	top padding.
 * @param  $lpad    left padding.
 * @return			the html to display the image.
 * @see				LINK::local
 */
function formatImage($image, $w, $h, $details) {
	$defaults = array(
        'alt'   => '',
        'link'  => '',
	    'title' => '',
        'serve' => false,
	);

    $details = array_merge($defaults, $details);
    $details['height'] = $h;
    $details['width']  = $w;
    $details['style']  = 'margin: 0; padding: '.get('gallery-padding').'px; border: none;';
    $link = $details['link'];
    $items = array('width', 'height', 'alt', 'title', 'style');
    
	foreach (array('alt', 'title') as $item) $details[$item] = str_replace("'", '&quot;', $details[$item]);
	

	// get the image tag
	if ($details['serve']) {
        $src = '?'.$image['src'].'&amp;w='.$w.'&amp;h='.$h;
	} else {
		$src = $image['src'];
	}
	$options = '';
	foreach ($items as $item) append($options, $item.':'.$details[$item].'"', ' | ');
	$img = IMG::tag($src, $options);
	
	// create the HTML for this image	
	if ($link != '') { // if a link was specified, include it
		$result = LINK::local($link, $img, 'return | style:margin: 0; padding: 0;');
	} else {
		$result = $img;
	}
	
	return $result;
}

}

?>