<?php

/**
 * CRYPT provides a generalized encryption solution by providing a simplified interface to the mcrypt PHP module.
 * 
 * @author      Alex Bentley
 * @history     1.3		removed dependence on ABOUT class
 *				1.2		updated to include basic encryption
 *				1.0		initial release
 */
 
class CRYPT {

/**
 * Encrypts a block of data using the supplied key using the specified method. 
 *
 * @param  $key		the value to use as the key value for encryption.
 * @param  $data	the data to encrypt.
 * @param  $method	the mcrypt method to use
 * @return			the encrypted block of data.
 * @see				execute
 */
function en ($key, $data, $method=MCRYPT_CAST_256) {
	return CRYPT::execute($key, $data, $method, true);
}

/**
 * Decrypts a block of data using the supplied key using the specified method. 
 *
 * @param  $key		the value to use as the key value for decryption.
 * @param  $data	the data to decrypt.
 * @param  $method	the mcrypt method to use
 * @return			the decrypted block of data.
 * @see				execute
 */
function de ($key, $data, $method=MCRYPT_CAST_256) {
	return CRYPT::execute($key, $data, $method, false);
}

/**
 * Encrypts or decrypts a block of data using the supplied key using the specified method. 
 *
 * @param  $key		the value to use as the key value for encryption/decryption.
 * @param  $data	the data to encrypt/decrypt.
 * @param  $method	the mcrypt method to use
 * @param  $encrypting	true if encryption is desired.
 * @return			the encrypted/decrypted block of data.
 * @see				en
 * @see				de
 */
private function execute ($key, $data, $method, $encrypting) {
	/* Open module, and create IV */ 
	$td = mcrypt_module_open($method, '', MCRYPT_MODE_CBC, '');

	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	
	/* Initialize encryption handle */
	if (mcrypt_generic_init($td, $key, $iv) != -1) {	   
		if ($encrypting) {
			$result = mcrypt_generic($td, $data);
		} else {
			$result = mdecrypt_generic($td, $data);
		}
		
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
	}
	return $result;
}

/**
 * Encrypts/decrypts a block of data using the supplied key using a simplistic method. 
 *
 * @param  $key		the value to use as the key value for encryption/decryption.
 * @param  $data	the data to encrypt/decrypt.
 * @return			the encrypted/decrypted block of data.
 * @see				en
 * @see				de
 */
function simple ($data, $key=null) {
	if ($key == null) $key = get('simple-key');
	
	$key = crypt($key, $key);

	// make sure key is at least as long as the data
	while (strlen($data) > strlen($key)) $key .= crypt(md5($key), md5($key));

	// xor all bytes in the original data
	for ($i = 0; $i < strlen($data); $i++) $data[$i] = ~($data[$i] ^ $key[$i]);

	return $data;
}

/* convenience functions */

/**
 * Encrypts a block of data using the supplied key using the AES mcrypt method. 
 *
 * @param  $key		the value to use as the key value for encryption.
 * @param  $data	the data to encrypt.
 * @return			the encrypted block of data.
 * @see				execute
 */
function encryptAES ($key, $data) {
	return self::execute($key, $data, MCRYPT_RIJNDAEL_256, true);
}

/**
 * Decrypts a block of data using the supplied key using the AES method. 
 *
 * @param  $key		the value to use as the key value for decryption.
 * @param  $data	the data to decrypt.
 * @return			the decrypted block of data.
 * @see				execute
 */
function decryptAES ($key, $data) {
	return self::execute($key, $data, MCRYPT_RIJNDAEL_256, false);
}

}
?>