<?php

/**
 * This class helps to gerenate valid XML
 * 
 * @author	Alex Bentley
 * @history	1.1		added some documentation
 *			1.0		initial release
 */
class XML {

/**
 * This function gets attributes from an XML tag
 * 
 * @param	$object	a SimpleXML object
 * @param	$name	the name of the atttribute
 * @return	the value of the attribute
 */
function getAttribute($object, $name) {
	$attr = $object->attributes();
	return strval($attr[$name]);
}

/**
 * This function converts an array to an XML string
 * 
 * @param	$array	a keyed data array
 * @param	$parent	the name of an paraent tag
 * @return	the XML text
 * @see		array_extract
 */
function arraytoXML($array, $parent=null) {
	$result = '';
	$entries = count($array);
	$count = 0;
	foreach ($array as $key => $value) {
		$count++;
		$atvalues = array_extract($value, array('@'), array());
		if ($atvalues) { // process attributes first
			$result .= "<$key ";
			foreach ($value['@'] as $name => $val) {
				$result .= "$name='$val' ";
			}
		} else {
			if (($key != '@') && !is_numeric($key)) {
				$result .= "<$key";
			}
		}
		if (is_array($value)) {
			if ($key != '@') { // skip attributes, we've already processed them
				if (count($value)) {
					$result .= ">\n".XML::arraytoXML($value, $key)."\n</$key>\n";
				} else {
					$result .= " />\n";
				}
			}
		} else {
			if (is_numeric($key)) { // this is just an indexed value
				$result .= "$value";
				if ($count < $entries) $result .= "\n</$parent>\n<$parent>\n";
			} else {
				$result .= ">$value</$key>\n";
			}
		}
	}
	
	return $result;
}

/**
 * This function strips a specific XML tag from an XML string
 * 
 * @param	$raw	the XML text
 * @param	$tag	the name of the tag to extract
 * @return	the contents of the XML tag
 */
function trimXML($raw, $tag) {
	$starttag = "<$tag";
	$endtag = "</$tag>";
	$start = strpos($raw, $starttag);
	$end = strpos($raw, $endtag) + strlen($endtag);
	return substr($raw, $start, $end-$start);
}

}

?>
