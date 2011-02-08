<?php

/**
 * SESSION manages session values.
 * 
 * @author  Alex Bentley
 * @history	3.0		converted get and set to be aliases of get_session and set_session respectively
 *			2.0		fix to forceSSL and forceNoSSL
 *			1.9		removed dependence on ABOUT class
 *			1.8		updated documentation
 *			1.7		updated serialization options
 *			1.6		updated set to handle arbitrary contents
 *			1.0		initial release
 */
class SESSION {

/**
 * This function (which must appear prior to any HTML) forces an http redirect if https.
 *
 * @see				LINK::redirect
 */
function forceNoSSL() {
    forceSSL(false);
}

/**
 * This function (which must appear prior to any HTML) forces an https redirect if not already https.
 *
 * @see				LINK::redirect
 */
function forceSSL() {
    forceSSL();
}

/**
 * This function sets or resets session information.
 *
 * @param	$name	the name of the item.
 * @param	$value	the value to set or clear.
 * @param	$serialize	should the value be seairlized before storing?
 */
function set ($name, $value) {
	set_session($name, $value);
}

/**
 * This function reads session information.
 *
 * @param	$name	the name of the item.
 * @return	the value of the item.
 */
function get ($name) {	
	get_session($name);
}

} // end SESSION class

?>