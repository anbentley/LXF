<?php
/**
 * This class is intended to do a reasonable attempt to compare two text files and display the differences.
 *
 * @author	Alex Bentley
 * @history	1.0	initial release
 *
 */
class COMPARE {

/**
 * This is the common entry point for comparing the contents of two files.
 * It does not obtain the contents. It simply is passed the unmodified content.
 * It breaks the content into line arrays and passes them to the lines function.
 *
 * @param	$leftText	the content of the first file.
 * @param	$rightText	the content of the second file.
 * @param	$leftTitle	the title for the first file.
 * @param	$rightTitle	the title for the second file.
 *
 * @return	an HTML table formatted to display the two files side by side.
 * @see		lines
 *
 */
function text($leftText, $rightText, $leftTitle='', $rightTitle='') {
	// convert to lines
	$tab = str_repeat(' ', 4);
	$leftText = str_replace("\t", $tab, $leftText);
	$rightText = str_replace("\t", $tab, $rightText);
	
	$leftLines = explode("\n", $leftText);
	$rightLines = explode("\n", $rightText);
	
	return self::lines($leftLines, $rightLines, $leftTitle, $rightTitle);
}

/**
 * This compares two arrays of strings. It uses the function characters to do detailed comparisons.
 *
 * @param	$ar1	the first array.
 * @param	$ar2	the second array.
 *
 * @return	an HTML table formatted to display the two arrays side by side.
 * @see		characters
 * @see		fix
 */
function lines($ar1, $ar2, $t1='', $t2='') {

	$inter1 = array_intersect($ar1, $ar2);
	$inter2 = array_intersect($ar2, $ar1);
	$diff1 = array_diff($ar1, $ar2);
	$diff2 = array_diff($ar2, $ar1);

	$i1 = 0;
	$i2 = 0;
	
	$result = HTML::openTag('table', 'class:basictable');
    if (($t1 != '') | ($t2 != '')) {
        $result .= tag('tr', 'class:titles', tag('td').tag('td', '', $t1).tag('td').tag('td', '', $t2));
    }
	while (($i1 < count($ar1)) && ($i2 < count($ar2))) {
	
		$k = array(
			'i1' => array_key_exists($i1, $inter1), 
			'i2' => array_key_exists($i2, $inter2), 
			'd1' => array_key_exists($i1, $diff1), 
			'd2' => array_key_exists($i2, $diff2),
		);
		
		if (($k['i1'] && $k['i2']) && ($ar1[$i1] === $inter1[$i1]) && ($ar2[$i2] === $inter2[$i2])) { // matching lines
			$result .= tag('tr', 'class:match', tag('td', '', $i1).tag('td', '', self::fix($ar1[$i1])).tag('td', '', $i2).tag('td', '', self::fix($ar2[$i2])));
			$i1++;
			$i2++;
			
		} else if (($k['d1'] && $k['d2']) && ($ar1[$i1] === $diff1[$i1]) && ($ar2[$i2] === $diff2[$i2])) {
			list($cr1, $cr2) = self::characters($ar1[$i1], $ar2[$i2]);
			$result .= tag('tr', 'class:match', tag('td', '', $i1).tag('td', '', $cr1).tag('td', '', $i2).tag('td', '', $cr2));
			$i1++;
			$i2++;

		} else if ($k['d1'] && ($ar1[$i1] === $diff1[$i1])) {
			$result .= tag('tr', '', tag('td', 'class:extra', $i1).tag('td', 'class:extra', self::fix($ar1[$i1])).tag('td', '', '').tag('td', '', ''));
			$i1++;

		} else if ($k['d2'] && ($ar2[$i2] === $diff2[$i2])) {
			$result .= tag('tr', '', tag('td', '', '').tag('td', '', '').tag('td', 'class:extra', $i2).tag('td', 'class:extra', self::fix($ar2[$i2])));
			$i2++;
		}
	}

	$result .= HTML::closeTag('table');
	
	return $result;
}

/**
 * This compares two lines. It breaks the lines into individual characters.
 *
 * @param	$line1	the first line.
 * @param	$line2	the second line.
 *
 * @return	an array of each of the two lines using HTML spans to display the differences.
 * @see		fix
 */
function characters($line1, $line2) {
	$c1 = str_split($line1);
	if ($c1 === false) $c1 = array();
	
	$c2 = str_split($line2);
	if ($c2 === false) $c2 = array();
	
	$inter1 = array_intersect($c1, $c2);
	$inter2 = array_intersect($c2, $c1);
	$diff1 = array_diff($c1, $c2);
	$diff2 = array_diff($c2, $c1);
	$found = false;
	$i1 = 0;
	$i2 = 0;
	
	$rc1 = '';
	$rc2 = '';
	
	while (($i1 < count($c1)) && ($i2 < count($c2))) {
		$k = array(
			'i1' => array_key_exists($i1, $inter1), 
			'i2' => array_key_exists($i2, $inter2), 
			'd1' => array_key_exists($i1, $diff1), 
			'd2' => array_key_exists($i2, $diff2),
		);
		
		if (($k['i1'] && $k['i2']) && ($c1[$i1] === $inter1[$i1]) && ($c2[$i2] === $inter2[$i2])) { // matching characters
			$rc1 .= '='.$c1[$i1]; // tag('span', 'class:match', $c1[$i1]);
			$rc2 .= '='.$c2[$i2]; // tag('span', 'class:match', $c2[$i2]);
			$i1++;
			$i2++;
			
		} else if ((($k['d1'] && $k['d2']) && ($c1[$i1] === $diff1[$i1]) && ($c2[$i2] === $diff2[$i2])) |
				   (($k['d1'] && $k['i2']) && ($c1[$i1] === $diff1[$i1]) && ($c2[$i2] === $inter2[$i2])) |
				   (($k['i1'] && $k['d2']) && ($c1[$i1] === $inter1[$i1]) && ($c2[$i2] === $diff2[$i2]))) {
			$rc1 .= '!'.$c1[$i1]; // tag('span', 'class:diff', $c1[$i1]);
			$rc2 .= '!'.$c2[$i2]; // tag('span', 'class:diff', $c2[$i2]);
			$i1++;
			$i2++;

		} else if ($k['d1'] && ($c1[$i1] === $diff1[$i1])) {
			$rc1 .= '+'.$c1[$i1]; // tag('span', 'class:extra', $c1[$i1]);
			$i1++;

		} else if ($k['d2'] && ($c2[$i2] === $diff2[$i2])) {
			$rc2 .= '+'.$c2[$i2]; // tag('span', 'class:extra', $c2[$i2]);
			$i2++;
		}
	}
	
	return array(self::convert($rc1), self::convert($rc2));
	
}

/**
 * This function takes a character encoded string and converts it to
 * an optimized HTML encoded string using CSS.
 *
 * @param	$str	the string to convert.
 * @return	the converted string.
 */
function convert($str) {
	$coded = str_split($str, 2);
	
	$result = '';
	$lastClass = '';
	$chrs = '';
	foreach ($coded as $code) {
		list($state, $chr) = str_split($code);
		if ($state == '=') $class = 'match';
		if ($state == '+') $class = 'extra';
		if ($state == '!') $class = 'diff';
		
		if (($class != $lastClass) && ($chrs != '')) {
			$result .= tag('span', 'class:'.$lastClass, $chrs);
			$lastClass = $class;
			$chrs = '';
		}
		$chrs .= $chr;
		$lastClass = $class;
	}
	if ($chrs != '') $result .= tag('span', 'class:'.$lastClass, $chrs);

	return $result;
}
		
/**
 * This function converts a string to a browser safe display.
 *
 * @param	$str	the input string
 * @return	the formatted string
 */
function fix($str) {
	return str_replace(' ', '&nbsp;', htmlentities($str));
}

}
?>