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

	return $name.'/'.INIT::get('meta-file');
}

/**
 * Returns metadata for this item.
 *
 * @param	$name	the name of the file.
 * @return	the metadata associated with this file.
 * @see		metafile
 */
function getMetadata($name) {	
	$metaobject = NULL;
	
	$metafile = self::metafile($name);
	if (file_exists($metafile)) {
		switch (FILE::ext($metafile)) {
			case 'xml':
				$xml = simplexml_load_file($metafile);
				$metaobject = array();
				foreach ($xml->children() as $child) {
					$name = $child->getName();
					switch ($name) {
						case 'file':
							$fileentry = array();
							foreach ($child->children() as $field) $fileentry[$field->getName()] = trim((string)$field);
							$metaobject['directory']['file'][$fileentry['name']] = $fileentry;
							break;
						default:
							$metaobject['directory'][$child->getName()] = trim((string)$child);
					}
				}
				break;
			
			case 'php':
				include_once ($metafile);
				break;
				
			default:
		}
	} 
	
	if ($metaobject != NULL && is_array($metaobject['directory'])) {
		if (is_file($name)) {
			if (!array_key_exists('file', $metaobject['directory']) || !array_key_exists($file, $metaobject['directory']['file'])) {
				$file = basename($name);
				$metaobject['directory']['file'][$file] = array(
					'name' => $file,
					'short' => $file,
					'long' => '',
				);
			}
		} else { // dir
			if (!array_key_exists('icon', $metaobject['directory'])) $metaobject['directory']['icon'] = '';
		}
	
	// build default metadata if there is none
	} else {
		$metaobject = array('directory' => array('short' => dirname($name), 'long' => '', 'icon' => ''));
		if (is_file($name)) {
			$file = basename($name);
			$metaobject['directory']['file'][$file] = array(
				'name' => $file,
				'short' => $file,
				'long' => '',
			);
		}
	}
	
	return $metaobject;
}

/**
 * Returns the metadata for a file.
 *
 * @param	$name	the name of the file.
 * @return	the metadata for a file.
 * @see		getMetadata
 */
function getFileMetadata($name) {
	$metaobject = self::getMetadata($name);
	
	if (is_dir($name)) {
		$meta = $metaobject['directory'];
	} else {
		$meta = $metaobject['directory']['file'][basename($name)];
	}
	
	return $meta;
}

/**
 * Updates the metadata for a file.
 *
 * @param	$name	the name of the file.
 * @param	$meta	the metadata for a file.
 * @see		getMetadata
 * @see		convertMetadataToXML
 */
function updateMetadata($name, $meta) {
	$metaobject = self::getMetadata($name);
	
	if (is_dir($name)) {
		$metaobject['directory']['short'] = $meta['short'];
		$metaobject['directory']['long'] =  $meta['long'];
		$metaobject['directory']['icon'] =  $meta['icon'];
	} else {
		$file = basename($name);
		$metaobject['directory']['file'][$file]['short'] = $meta['short'];
		$metaobject['directory']['file'][$file]['long'] =  $meta['long'];
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
function sanitizeMetadata($name) {	
	$metaobject = self::getMetadata($name);
	
	$files = FILE::getlist(dirname($name));
	foreach ($metaobject['directory']['file'] as $file => $details) if (!in_array($element, $files)) unset($metaobject['directory']['file'][$file]);
	
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
	$item = $metaobject['directory'];
	$fields = array('short', 'long', 'icon');
	foreach ($fields as $field) $xml->addChild($field, $item[$field]);

	foreach ($metaobject['directory']['file'] as $item) {
		$child = $xml->addChild('file');
		$fields = array_keys($item);
		foreach ($fields as $field) $child->addChild($field, str_replace('&', '&amp;', str_replace('&amp;', '&', $value)));
	}
	$xml = $xml->asXML();
	
	return $xml;
}

}

?>