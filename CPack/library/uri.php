<?php

/**
 * This class encompasses the URI/URL related functions and is included in the INIT file to allow for proper loading and use by INIT.
 *
 * @author  Alex Bentley
 * @history 2.0		now only for compatability
 *			1.1     fix to site file to include the subdirectory correctly
 *          1.0     initial release
 */
class URI {
	
/**
 * This function returns the extended path of the php script.
 * Used in sitefile to determine which file to use to satisfy the request.
 * 
 * @return  the extended path
 * @see     page
 */
function path() {
	return page('path');
}

function script() {
	return page('script');
}

/**
 * get host name 
 * @return	the host name
 */
function host() {
	return page('host');
}

function hostpage() {
	return page('hostpage');
}

/**
 * gets host reference
 *
 * @param	$secure	the http/https setting you want, default is unchanged.
 */
function server($secure='inherit') {
	return page('server', $secure);
}

/**
 * get request URI 
 * @return	the request URI
 * @see		page
 */
function request() {
	return page('request');
}

/**
 * get full request uri
 *
 * @param	$secure	the http/https setting you want, default is unchanged.
 * @return	the request uri
 * @see		page
 */
function fullURI($secure='inherit') {
	return page('uri', $secure);
}

/**
 * returns URI up to but not including the page name
 *
 * @param	$secure	the http/https setting you want, default is unchanged.
 * @see		page
 */
function fullHost($secure='inherit') {
	return page('fullhost', $secure);
}

/**
 * returns URI from 'http' up to and including page name
 *
 * @param	$secure	the http/https setting you want, default is unchanged.
 * @see		page
 */
function fullPage($secure='inherit') {
	return page('fullpage', $secure);
}

/**
 * Get first parameter passed.
 *
 * @return	the name of the requested page from the URL or the defaultpage.
 * @see		page
 */
function page() {
	return page('name');
}

/**
 * Get the logical 'site' name from the page reference.
 *
 * @param	$suffix		an optional addition to the site name (usually a ':').
 * @return	the name of the logical site.
 * @see		page
 */
function site($suffix='') {
	return page('site', $suffix);
}

/**
 * Return all directories in the page name.
 *
 * @return	an array of directories that are specified in the page.
 */
function sections() {
	return page('sections');
}

/**
 * get beginning of first parameter passed
 *
 * @return	the first part of the path name of the page
 */
function pageprefix() {
	return page('prefix');
}

/**
 * Get last element of the page name (the file name without directories).
 *
 * @return	the name of the requested page.
 * @see		page
 */
function pagesuffix() {
	return page('suffix');
}    

/**
 * Finds a 'site' specific file if there is one or a non specific file.
 *
 * @param	$filename	the file to locate.
 * @param	$base		the base directory name.
 * @param	$default	the text to return if the file is not found.
 * @return	the actual location of the target file if it was found.
 * @see		SITE::file
 */
function siteFile($filename, $base='', $default='') {
	return SITE::file($filename, $base, $default);
}

/**
 * Get all parameters passed.
 *
 * @return	the text of the logical query string.
 */
function full() {
	return page('full');
}
	
}


?>