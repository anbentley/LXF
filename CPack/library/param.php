<?php

/**
 * PARAM provides a common interface to passed parameters in URIs whether from a GET or POST.
 * This class encapsulates all the HTML parameter parsing. All functions are intended to be used statically.
 * 
 * @author      Alex Bentley
 * @history	5.0		this class is now only in existence for compatibility
 *			4.0     Moved URI functions to URI, but extend URI to allow for compaibility.
 *          3.1		fixed the inclusion fix I fixed before
 *			3.0		fixed the inclusion of parameters that have no value, but exist
 *			2.19	improved paqrameter name processing
 *			2.18	added fullHost function
 *			2.17	added server function
 *			2.16	fixed conversion of composite fields
 *			2.15	removed find function
 *			2.14	added special formatting capability for composite fields
 *			2.13	improved character handling
 *			2.12	removed dependence on ABOUT class
 *			2.11	deprecated find method
 *			2.10	move limit params to getParameters'
 *			2.9		update to value function to translate parameter names as PHP does
 *			2.8		add buildURL function to create a pseudo URL from all passed parameters
 *			2.7		fix to getParameter function to return page name as first entry
 *			2.6		added default to pagevalue function
 *			2.5		added sections function
 *			2.4		updated site processing
 *			1.0		initial release
 */
class PARAM {

function environment($params=null) {
    return page('environment', $params);
}

/**
 * Puts all parameters into one place - GET parameters override POST parameters.
 *
 * @param	$limitParams	an optional array of parameter names to include, otherwise include them all
 * @return	a keyed array of parameters and values
 * @see		page
 */
function getParameters($limitParams=false) {
	return page('parameters', $limitParams);
}

/**
 * Build a logical URL from all passed parameters.
 *
 * @param	$limitParams	an optional array of parameter names to include, otherwise include them all
 * @return	the URL
 * @see		page
 */
function buildURL($limitParams=false) {
	return page('url');
}

/**
 * Check for the existence of a param irrespective of how it is passed
 *
 * @param	$var	the name of the parameter to check
 * @return	a boolean indicating if the parameter was found or not
 * @see		param
 */
function exists($var) {
	return param($var, 'exists');

}

function hostpage() {
	return page('hostpage');
}

/**
 * Return value of page parameter (first parameter in the query string).
 *
 * @param	$default	the value to return if no value was found in the query string.
 * @return	the value or default.
 * @see		page
 */
function pagevalue($default='') {
	return page('value', $default);
}

/**
 * get the value of a parameter in the query string.
 *
 * @param	$var	the name of the parameter. Whne multiple fields are named as name-field these can be collectively returned using just name.
 * @param	$def	an optional default value to return if the parameter was not passed.
 * @return	the value of the parameter or the default value.
 * @see		param
 */
function value ($var, $def='') {
	return param($var, 'value', $def);
}

function full() {
	return page('url');
}

function fullURI() {
	return page('uri');
}

	function site($v='') {
		return page('site', $v);
	}
	
	function page() {
		return page();
	}
	
	function pageprefix() {
	return page('prefix');
}

function pagesuffix() {
	return page('suffix');
}

/**
 * convert an array to a URL 
 *
 * @param	$params		a keyed array
 * @return	a properly urlencoded logical URL
 */
function arraytoURL($params) {
	return http_build_query ($params, '',  '&amp;');
}

} // end class PARAM

?>