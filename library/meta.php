<?php

/**
 * META manages metadata about files.
 * 
 * @author	Alex Bentley
 * @history	3.0		major rewrite of functions to streamline processing
 *			2.0		improved XML metadata handling of & characters.
 *			1.6		removed dependence on ABOUT class
 *			1.5		updated documentation
 *			1.4		fixed bug introduced in 1.3
 *			1.3		eliminated warning regarding missing value entries
 *			1.2		minor fix
 *			1.1		updated parsing
 *			1.0		initial release
 */
class META {

/**
 * Returns the name of the metafile for this item.
 *
 * @param	$name	the name of the file.
 * @return	the name of the metafile for this item.
 */
function metafile($name) {	
	if (!is_dir($name)) $name = dirname($name);

	return $name.'/'.get('meta-file');
}

/**
 * Returns metadata for this item.
 *
 * @param	$name	the name of the file.
 * @return	the metadata associated with this file.
 * @see		metafile
 */
function getMetadata($name) {			
	$metaobject = array();
	$metafile = self::metafile($name);

	if (file_exists($metafile)) {
		switch (FILE::ext($metafile)) {
			case 'xml':
				$xml = simplexml_load_file($metafile);
				foreach (array('short', 'long', 'icon') as $field) {
					$metaobject[$field] = array_shift($xml->xpath('/directory/'.$field));
					if (is_object($metaobject[$field])) $metaobject[$field] = $metaobject[$field]->__toString();
				}
				foreach ($xml->xpath('/directory/file') as $fileentry) {
					$file = array();
					$file['name'] = $fileentry->name->__toString();
					$file['short'] = $fileentry->short->__toString();
					$file['long'] = $fileentry->long->__toString();

					$metaobject['file'][$file['name']] = $file;
				}
				break;
			
			case 'php':
				include_once ($metafile);
				break;
				
			default:
		}
	} 

	$file = basename($name);
	if (!array_key_exists('short', $metaobject)) {
		if (is_dir($name)) {
			$metaobject['short'] = $file;
		} else {
			$metaobject['short'] = basename(dirname($name));
		}
	} else {
		if ($metaobject['short'] == '') $metaobject['short'] = dirname($name);
	}
	if (!array_key_exists('long', $metaobject)) $metaobject['long'] = '';
	if (!array_key_exists('icon', $metaobject)) $metaobject['icon'] = '';
	
		
	if (!is_dir($name)) {
		if (!array_key_exists('file', $metaobject)) $metaobject['file'] = array();
		if (!array_key_exists($file, $metaobject['file'])) $metaobject['file'][] = array('name' => $file, 'short' => $file, 'long' => '');
	}
	
	return $metaobject;
}

/**
 * Gets metadata for an entity.
 *
 * @param	$name	the name of the entity.
 * @param	$fields	the fields to be returned (default is all fields).
 * @return	the metadata for the entity.
 * @see		getMetadata
 */
function get ($name, $fields=null) {
	$meta = self::getMetadata($name);
	$bname = basename($name);
	
	if (is_file($name)) $meta = $meta['file'][$bname];

	if ($meta == null) {
		$meta = array('name' => $bname, 'short' => $bname, 'long' => '');
	}
	
	if ($fields != null) foreach ($meta as $field => $value) if (!in_array($field, (array)$fields)) unset($meta[$field]);
	
	return $meta;
}

/**
 * Sets metadata for an entity.
 *
 * @param	$name	the name of the entity.
 * @param	$values	the metadata values for an entity to update.
 * @see		getMetadata
 * @see		convertMetadataToXML
 */
function set ($name, $values=array()) {
	$metaobject = self::getMetadata($name);
	
	if (is_dir($name)) {
		foreach (array('short', 'long', 'icon') as $field) if (array_key_exists($field, $values)) $metaobject[$field] = $values[$field];
	} else {
		$file = basename($name);
		foreach (array('short', 'long') as $field) if (array_key_exists($field, $values)) $metaobject['file'][$file][$field] = $meta[$field];
	}
	
	$xml = self::convertMetadataToXML($metaobject);
	
	return FILE::write(self::metafile($name), $xml);
}

/**
 * Cleans up metadata for files that have been moved or deleted.
 *
 * @param	$name	the name of the file.
 * @see		getMetadata
 * @see		convertMetadataToXML
 */
function clean($name) {	
	$metaobject = self::getMetadata($name);
	
	$files = FILE::getlist(dirname($name));
	foreach ($metaobject['file'] as $file => $details) if (!in_array($element, $files)) unset($metaobject['file'][$file]);
	
	$xml = self::convertMetadataToXML($metaobject);
	
	return FILE::write(self::metafile($name), $xml);
}

/**
 * Converts internal metadata structure to external XML format.
 *
 * @param	$metaobject	the internal metadata object.
 * @return	the XML for the object.
 */
function convertMetadataToXML($metaobject) {
	$xml = new SimpleXMLElement('<directory></directory>');
	$fields = array('short', 'long', 'icon');
	foreach ($fields as $field) $xml->addChild($field, $metaobject[$field]);

	foreach ($metaobject['file'] as $item) {
		$child = $xml->addChild('file');
		if ($item['name'] == '') continue;
		foreach (array('name', 'short', 'long') as $field) $child->addChild($field, str_replace('&', '&amp;', str_replace('&amp;', '&', $item[$field])));
	}
	$xml = $xml->asXML();

	return $xml;
}

}

?>