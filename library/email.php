<?php

/**
* EMAIL provides for bulk email sending.
* 
* @author	Alex Bentley
* @history	1.0	initial release
*/
class EMAIL {

/**
 * takes in a message and an array of emails and attempts to send the message to the complete list.
 *
 * @param	$to			an array of email addresses.
 * @param	$from		an email address the message is to be sent from.
 * @param	$subject	a string subject.
 * @param	$message	a string message.
 * @param	$format		the email format of the message, usually 'text/html'.
 * @param	$bcc		an optional string of addresses to send as BCC entries.
 * @param	$opt		additional optional headers.
 * @return	success or failure to complete the delivery.
 *
 */

function send ($to, $from, $subject, $message, $format='text/html', $bcc='', $opt='', $reply='CHDS@chds.us') {
	if (!is_array($to)) $to = array($to);
	
	$count = count($to);	
	set_time_limit(10*$count); // set the script timeout to allow for full execution
	
	$sep = "\r\n";
	$headers = '';
	$envfrom = $from;
	if (preg_match("/<(.+)>/",$from,$matches)) $envfrom = $matches[1];

	append($headers, 'From: '.$from, $sep);
	append($headers, 'Return-Path: '.$from, $sep);
	append($headers, 'Reply-To: '.$reply, $sep);
	append($headers, 'MIME-Version: 1.0', $sep);
	append($headers, 'Content-type: '.$format.'; charset=utf-8', $sep);
	append($headers, 'Message-ID: <'.microtime(true).'web@'.$_SERVER['SERVER_NAME'].'>', $sep);
	append($headers, 'X-Mailer: PHP v'.phpversion(), $sep);
	if ($bcc != '') append($headers, 'BCC: '.$bcc, $sep);
	$headers .= $sep;
	
	$success = 0;
	foreach ($to as $email) {	
		if (mail($email, $subject, $message, $headers, "-f".$envfrom)) $success++;	
		usleep(100); // slight delay in an attempt to not overload the system
	}
	
	return $success;
}
	
function subscribe($email, $subscription, $description, $loggedin=true) {
	if ($loggedin && ($email != getUser())) return false;
	
	// look for user in account
	$whoid = '';
	$query = 'SELECT id, disabled FROM account WHERE email = :email';
	$res = DB::query('people', $query, array(':email' => $user));
	
	// does the user exist?
	if ($res !== false) {
		$whoid = $res[0]['id'];
		$disabled = $res[0]['disabled'];
		if ($disabled) {
			self::send('techsupport@chds.us', 'infoservices@chds.us', 
					   'Account '.$email.' attempted to subscribe to '.$subscription.', but account is disabled.', 
					   'Please review this account to determine if account should be re-enabled.');
			return;
		}
	}
	
	// if there is no such user
    if ($whoid == '') {
		$password = array_rand(str_split('bcdfghjklmnpqrstvwxz')).
					array_rand(str_split('aeiouy')).
					array_rand(str_split('!@#$%^&*()+=/~')).
					array_rand(str_split('bcdfghjklmnpqrstvwxz')).
					array_rand(str_split('aeiouy')).
					rand(1000, 9999);
				
        $whoid = DB::insert('people', 'account', 
							array('first_name', 'last_name', 'email', 'pref_email', 'password', 'created'), 
							array(PARAM::value('first_name'), PARAM::value('last_name'), PARAM::value('email_address'), PARAM::value('email_address'), md5($pwd), date('Y-m-d')));			
		
		// add in necessary records for new accounts
		DB::insert('people', 'notes', array('userid', 'type', 'description', 'date', 'created'), array($whoid, 'discipline', 'none selected', date('Y-m-d'), date('Y-m-d')));
		DB::insert('people', 'employment', 
				   array('userid', 'created', 'title', 'organization', 'description', 'hs_role', 'full_part_time', 'start_date', 'end_date', 'jurisdiction', 'area_represented', 'current'), 
				   array($whoid, date('Y-m-d'), '', '', '', '', 'Full', '', '', '', '', 1));	
	}
	
	// is there already a record?
	if (DB::query('people', 'SELECT * FROM mail_subscriptions WHERE jobid = :jobid AND type = :type AND userid = :userid', 
				  array('jobid' => $subscription, 'type' => 'account', 'userid' => $whoid))) {	
		DB::update('people', 'mail_subscriptions', array('disabled' => 0), array('jobid' => 2, 'type' => 'account', 'userid' => $whoid));
	} else {
		DB::insert('people', 'mail_subscriptions', array('jobid' => 2, 'type' => 'account', 'userid' => $whoid));
	}
	
	DB::insert('people', 'permission', array('userid', 'type', 'text', 'created'), array($whoid, 'Description', $description, date('Y-m-d')));
	
	// update log
	DB::insert('people', 'timeline', array('userid', 'created', 'text'), array($whoid, date('Y-m-d'), $description));
}


function insertTracking($jobid, $url){
	$userRole = array(
		'Web Admin',
		'Staff - Office of the Director',
		'Staff - Academic Programs',
		'Staff - Executive Education',
		'Staff - Educational Technologies',
		'Staff - Operations and Logistics',
		'Staff - Finance and Administration',
		'Staff - Strategic Communications',
		'Staff - HSDL',
		'Staff - Other',
		'Faculty',
		'Masters Participant',
		'Exec Participant',
		'FCLP Participant',
		'Masters Alum',
		'Exec Alum',
		'FCLP Alum',
		'SME',
		'UAPI Participant',
		'Noncredit Participant',
		'HSDL Participant'
	);
	
	$Role = 'None';
	
	// only do this operation IF this function exists on this server
	if (function_exists('get_auth_info')) {
		$user = get_auth_info('CHDS_login');
		if ($user == '') $user = get_auth_info('HSDL_login');
		if ($user == '') $user = get_auth_info('UAPI_login');
		
		if($user){
			foreach($userRole as $value){
				if(hasRole($user,$value)){
					$Role = $value;
					break;
				}else{
					$Role = 'Other';
				}
			}
		}
	}
	
	$keys = array('jobid','url','ip','role');
	$vals = array($jobid, $url, $_SERVER['REMOTE_ADDR'], $Role);
	$insert = DB::insert('people','mail_tracking',$keys,$vals);
		
	return $insert;
}
}
?>