<?php
	
/**
 * INIT is the fundamental building block for page creation.
 * 
 * @author	Alex Bentley
 * @history	8.0		this class now only exists for compatibility
 *			7.0     major changes to support subdirectory processing
 *          6.0		moved functions to in front of INIT to operate as intrinsic functions
 *			5.0		moved some functions from UTIL to INIT and added support for skipping files at loadtime
 *			4.2		update to arrayExtract
 *			4.1		fix to js to remove duplicated files in lower levels
 *			4.0		fix to include file list to eliminate duplicates
 *			3.11	minor change to eliminate &amp; from javascript redirects.
 *			3.10	code cleanup
 *			3.9		updated getset to use array_extract
 *			3.8		added new function array_scan and removed array_value
 *			3.7		standardized resource mapping
 *			3.6		update to site function
 *			3.5		generalized conf settings and minor change to site function
 *			3.4		added site capability to packages
 *			3.3		now set header in includeFiles for CSS
 *			3.2		fixed outstanding bug that would not load css or pages from a package
 *			3.1		removed ABOUT style interface code
 *			3.0		implement packages
 *			2.5		array retrieval function
 *			2.4		added page sections function
 *			2.3		updated site function
 *			1.0		initial release
 */
 
class INIT extends SITE {

function siteFile($filename, $base='', $default='') {
	return file($filename, $base, $default);
}

function get($n, $d='') {
	return get($n, $d);
}

function set($n, $v) {
	set($n, $v);
}
	
}

?>
