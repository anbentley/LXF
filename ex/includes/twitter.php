<?php
/**
 * This class encapsulates the simple API for Twitter
 *
 * @author	Alex Bentley
 * @history	1.0 initial release
 */
class TWITTER {
	
/**
 * This function sends a Twitter request
 *
 * @param	$username	the Twitter username
 * @param	$password	the Twitter password for this user
 * @param	$url		the url to send
 * @param	$post		a boolean indicating if this request should be sent at a post
 * @return	the result of the request
 */
function send($username, $password, $url, $post=true) {		
	$curlURL = "https://twitter.com/statuses/$url";
	
	// create and setup the curl session
	$curl = curl_init();
	curl_setopt($curl, CURLOPT_VERBOSE, 1);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
	curl_setopt($curl, CURLOPT_POST, $post);
	curl_setopt($curl, CURLOPT_URL, $url);
	
	// execute and get the code to see if it was successful
	$result = curl_exec($curl);	
	$resultArray = curl_getinfo($curl);
	curl_close($curl);
			
	return $resultArray;
}

/**
 * This function sends and verifies a status change for the specified user
 *
 * @param	$username	the Twitter username
 * @param	$password	the Twitter password for this user
 * @param	$status		the status message to send
 * @return	a boolean indicating the success of the update
 * @see		send
 */
function setStatus($username, $password, $status) {	
	if (strlen($status) < 1) return false; // don't send an empty status
	
	$url = 'update.xml?status='. urlencode($status); // use a secure connection
	$resultArray = self::send($username, $password, $url, true);
				
	return ($resultArray['http_code'] == 200);
}

/**
 * This function gets recent status messages for the specified user
 *
 * @param	$username	the Twitter username
 * @param	$password	the Twitter password for this user
 * @param	$count		the number of status messages to return (must be less than 200)
 * @return	an array of status messages
 * @see		send
 */
function getRecentStatuses($username, $password, $count=20) {
	$count = max(1, min($count, 200)); // make sure the request is between 1 and 200 messages
	$url = 'user_timeline.xml';
	
	$resultArray = self::send($username, $password, $url, true);
				
	return $resultArray;
}

}
?>
