<?php

/**
* CRYPT provides a generalized encryption solution by providing a simplified interface to the mcrypt PHP module.
* 
* @author	Alex Bentley
* @history	1.0		initial release
*/

class CRYPT {

/** encrypt a complete block of data
 *
 *	@param   $data    data
 *	@param   $key
 *	@param   $base64   base64 encode
 *
 *	@return  encrypted data or false on error
 */
function encrypt ($data, $key, $base64=false) {
	
	if (!$td = mcrypt_module_open('rijndael-256', '', 'ctr', '')) return false;
	
	$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
	
	if (mcrypt_generic_init($td, $key, $iv) !== 0) return false;
	
	$data = serialize($data);
	
	$data	= mcrypt_generic($td, $data);
	$data	= $iv.$data;
	$mac	= self::keygen($data, $key, 1000, 32);
	$data  .= $mac;
	
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	
	if ($base64) $data = base64_encode($data);
	
	return $data;
}

/** decrypt a block of data
 *
 *	@param   $data
 *	@param   $key		encryption key
 *	@param   $base64	base64 decode
 *
 *	@return  decrypted data or false on error
 */
function decrypt ($data, $key, $base64=false) {
	
	if ($base64) $data = base64_decode($data);
	
	if (!$td = mcrypt_module_open('rijndael-256', '', 'ctr', '')) return false;
	
	$iv		= substr($data, 0, 32);
	$em		= substr($data, strlen($data) - 32);
	$data	= substr($data, 32, strlen($data)-64);
	$mac	= self::keygen($iv.$data, $key, 1000, 32);
	
	// verify embedded mac matches and mcrypt can be initialized
	if (($em !== $mac) || (mcrypt_generic_init($td, $key, $iv) !== 0)) return false;
	
	$data = unserialize(mdecrypt_generic($td, $data));
	
	mcrypt_generic_deinit($td);
	mcrypt_module_close($td);
	
	return $data;
}

/** keygen
 *
 *	@param   $password
 *	@param   $salt
 *	@param   $count   iteration count (use 1000 or higher)
 *	@param   $keylen  key length
 *
 *	@return  key
 */
function keygen ($password, $salt, $count=1000, $keylen) {
	
	$algorithym = 'sha256';
	$hashlen	= strlen (hash($algorithym, null, true));
	$keyblocks	= ceil ($keylen/$hashlen);
	$key		= '';
	
	for ($block=1; $block <= $keyblocks; $block++) {
		$iteratedBlock = $blk = hash_hmac($algorithym, $salt.pack('N', $block), $password, true); // hash for this block
		for ($i = 1; $i < $count; $i++) $iteratedBlock ^= ($blk = hash_hmac($algorithym, $blk, $password, true)); // XOR each iterate
		$key .= $iteratedBlock; // add iterated block
	}
	
	return substr($key, 0, $keylen);
}

/**
 * encrypt or decrypt a file using ncrypt
 *
 * @param	$in		the input file
 * @param	$out	the output file
 * @param	$mode	the direction { d | e }
 * @param	$key	the key to use
 * @return	the success of the operation
 */
function ncrypt($in, $out, $mode='e', $key=null) {
	if ($key == null) {
		$seed = get('random-seed');
		//$key = CRYPT::keygen(substr($seed, 32, 32), substr($seed, 0, 16), 1000, 32);
	}
	$cmd = '/usr/local/bin/ncrypt -'.$mode.' -i '.$in.' -o '.$out.' -k '.substr($seed, 32, 32);
	exec($cmd, $output, $status);

	return $status;
}

}
?>