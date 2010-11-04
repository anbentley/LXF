<?php

/**
 * BLOCKS takes a set of raw dimensions and arranges and scales them in a number of interesting ways.
 * 
 * @author	Alex Bentley
 * @history	1.0	initial release
 */
class BLOCKS {

/**
 * Returns an array of aspect ratios of all dimensions.
 *
 * @param  $blocks	a simple array of dimensions.
 * @return			the array of the aspect ratios (height/width).
 */
function aspect($h, $w) {
	if ($w == 0) return 0; // deal with bad dimensions because we can't divide by zero

	return $h / $w;
}

/**
 * Scales all blocks so their heights are all the same and their combined width plus border fits within the maximum
 *
 * @param  $blocks	a simple array of blocks.
 * @param  $n		the number of blocks.
 * @return			the scaled dimensions to layout the blocks within the allowed space.
 * @see				getAspectRatios
 */
function across($blocks, $n) {
	$ratio = array();
	$factor = 1;
	foreach ($blocks as $block) {
		$aspect = self::aspect($block['h'], $block['w']);
		$factor *= $ratio[$i];	// compute the multiplication factor (the product of all ratios)
		$ratio[] = $aspect;
	}
	
	
	// compute the division factor  (sum of (product of all ratios missing one ratio))
	// for example: with 3 ratios (r1, r2, & r3) we get (r1*r2 + r1*r3 + r2*r3)
	$divisor = 0;
	for ($i = 0; $i < $n; $i++) {
		$term = 1;
		for ($j = 0; $j < $n; $j++) if ($j != $i) $term *= $ratio[$j];
		$divisor += $term;
	}
	
	// compute the width of the first block
	$w = array();
	$w[0] = $factor * (100 - ($n-1)*4) / $divisor;
	$wid = $w[0];
	
	// compute the width of all remaining blocks (except the last one)
	for ($i = 1; $i < $n-1; $i++) {
		$w[$i] = $w[0] * $ratio[0] / $ratio[$i];
		$wid += $w[$i];
	}

	// compute the width of the last block
	if ($n > 1) $w[$n-1] = 100 - $wid;
	
	// compute the height of all blocks
	$h = $w[0] * $ratio[0];
	
	// create the scales for these blocks
	$scaled = array();
	foreach ($w as $wi) $scaled[] = array('w' => $wi, 'h' => $h);
	
	return $scaled;
}

/**
 * Creates a two column layout where the width of both columns fits within the maximum
 *
 * @param  $blocks	a simple array of dimensions.
 * @param  $left	the number of blocks in the left column.
 * @param  $right	the number of blocks in the right column.
 * @return			the scaled dimensions to layout the blocks within the allowed space.
 * @see				aspect
 */
function block($blocks, $left, $right) {
	$ratio = array();
	$factor1 = 0; // the sum of all ratios
	$factor2 = 0; // the sum of all ratios on the right side
	$index = 1;
	foreach ($blocks as $block) {
		$aspect = self::aspect($block['h'], $block['w']);
		$factor1 += $aspect;
		if ($index > $left) $factor2 += $aspect;
		$ratio[] = $aspect;
		$index++;
	}
	
	// compute width of left column as a percentage
	$wlc = $factor2 * 100/$factor1;
	$w = array();
	$h = array();
	$w[0] = $wlc;
	
	// use this width for all left blocks
	for ($i = 1; $i < $left; $i++) $w[$i] = $w[0];	
	
	// compute width of all right blocks as a percentage
	$wrc = 100 - $wlc;     
    
	// use this width for all right blocks
	for ($i = $left; $i < ($left + $right); $i++) $w[$i] = $wrc;
	
	// compute height of all blocks based on ratios except the last block
	for ($i = 0; $i < ($left + $right - 1); $i++) $h[$i] = $w[$i] * $ratio[$i];
	
	// compute height of left column of blocks
	$last = 0;
	for ($i = 0; $i < $left; $i++) $last += $h[$i];
		
	// subtract off the height of all the right blocks except the last to see what is left
	for ($i = $left; $i < ($left + $right-1); $i++) $last -= $h[$i];
		
	// compute the height of the last block
	$h[$left+$right-1] = $last + ($left - $right);
			
	// create the scales for these blocks
	$scaled = array('l' => $wlc, 'r' => $wrc);
	for ($i = 0; $i < ($left+$right); $i++) $scaled[$i] = array('w' => $w[$i], 'h' => $h1);
	
	comment($scaled, 'scaled');
	return $scaled;
}

}

?>