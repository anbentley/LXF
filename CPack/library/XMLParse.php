<?php

/**
 * XMLParse
 * 
 * @author      Alex Bentley
 * @history	1.0		initial release
 */
class XMLParse {
	var $data;		// Input XML data buffer
	var $vals;		// Struct created by xml_parse_into_struct
	var $collapse_dups;	// If there is only one tag of a given name,
				//   shall we store as scalar or array?
	var $index_numeric;	// Index tags by numeric position, not name.

// Read in XML on object creation.
// We can take raw XML data, a stream, a filename, or a url.
function XMLParse($data_source, $data_source_type='string', $collapse_dups=false, $index_numeric=false) {
	$this->collapse_dups = $collapse_dups;
	$this->index_numeric = $index_numeric;
	$this->data = '';
	if ($data_source_type == 'string')
		$this->data = $data_source;

	elseif ($data_source_type == 'stream') {
		while (!feof($data_source))
			$this->data .= fread($data_source, 1000);

	// try filename, then if that fails...
	} elseif (file_exists($data_source))
		$this->data = implode('', file($data_source)); 

	// try url
	else {
		$fp = fopen($data_source,'r');
		while (!feof($fp))
			$this->data .= fread($fp, 1000);
		fclose($fp);
	}
}

// Parse the XML file into a verbose, flat array struct.
// Then, coerce that into a simple nested array.
function getArray() {
	$parser = xml_parser_create('ISO-8859-1');
	xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
	xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
	xml_parse_into_struct($parser, $this->data, $vals, $index); 
	xml_parser_free($parser);

	$i = -1;
	return $this->getsubelements($vals, $i);
}

// internal function: build a node of the tree
function buildtag($thisvals, $vals, &$i, $type) {
	$tag = array();
	
	if (isset($thisvals['attributes']))
		$tag['attributes'] = $thisvals['attributes']; 

	// complete tag, just return it for storage in array
	if ($type === 'complete') {
		if (isset($thisvals['value'])) {
			$tag['value'] = $thisvals['value'];
		}
	}

	// open tag, recurse
	else
		$tag = array_merge($tag, $this->getsubelements($vals, $i));

	return $tag;
}

// internal function: build a nested array representing subelements
function getsubelements($vals, &$i) { 
	$subelements = array();     // Contains node data

	// Node has CDATA before it's subelements
	if ($i > -1 && isset($vals[$i]['value']))
		$subelements['value'] = $vals[$i]['value'];

	// Loop through subelements, until hit close tag or run out of tags
	while (++$i < count($vals)) { 

		$type = $vals[$i]['type'];

		// 'cdata':	Node has CDATA after one of it's subelements
		// 		(Add to cdata found before in this case)
		if ($type === 'cdata')
			$subelements['value'] .= $vals[$i]['value'];

		// 'complete':	At end of current branch
		// 'open':	Node has subelements, recurse
		elseif ($type === 'complete' || $type === 'open') {
			$tag = $this->buildtag($vals[$i], $vals, $i, $type);
			if ($this->index_numeric) {
				$tag['tag'] = $vals[$i]['tag'];
				$subelements[] = $tag;
			} else
				$subelements[$vals[$i]['tag']][] = $tag;
		}

		// 'close:	End of node, return collected data
		//		Do not increment $i or nodes disappear!
		elseif ($type === 'close')
			break;
	} 
	if ($this->collapse_dups)
		foreach($subelements as $key => $value)
			if (is_array($value) && (count($value) == 1))
				$subelements[$key] = $value[0];
	return $subelements;
}

function XMLtoArray($source, $type='file', $collapse=true, $index=false) {
	$parser = new XMLParse($source, $type, $collapse, $index);
	return $parser->getArray();
}

}

?>