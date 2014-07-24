<?php

class AnySms {

	public $baseUrl;
	public $id;
	public $pass;
	public $gateway;
	
	function __construct() {
		$this->baseUrl = "http://gateway.any-sms.biz/send_sms.php";
		$this->id = HSetting::Get('username', 'sms');
		$this->pass = HSetting::Get('password', 'sms');
		$this->gateway = HSetting::Get('gateway', 'sms');
	}
	
	public function sendSms($sender, $receiver, $msg) {
		$url = $this->generateUrl($sender, $receiver, $msg);
		$handle = fopen($url, "rb");
		$retVal = array();
		if($handle == false) {
			$retVal['error'] = true;
			$retVal['statusMsg'] = 'Could not open connection.';
		}
		else {
			$serverResponse = stream_get_contents($handle);
			$retVal = $this->interpretResponse($serverResponse);
		}
		return $retVal;
	}
	
	/**
	 * Interpret a string response from the server and convert to a predefined array.
	 * @param string $response the server response to a send sms.
	 * @return array[string] an array containing following keys: {error, statusMsg, furtherInfo}, where error is true/false, statusMsg the status message and furtherInfo an array with further information
	 *  
	 */
	private function interpretResponse($response) {
		
		$values = array();
		foreach(explode("\n", $response) as $line) {
			$keyValuePair = explode(":", $line);
			if(sizeof($keyValuePair) >= 2) {
				$values[$keyValuePair[0]] = $keyValuePair[1];
			} 
		}
		
		$retVal = array();
		$retVal['furtherInfo'] = $values;
		$retVal['error'] = $values['err'] != 0;
		
		switch($values['err']) {
			case 0:
				$retVal['statusMsg'] = 'SMS has been successfully sent.';
				break;
			case -1:
				$retVal['statusMsg'] = 'Invalid user id and/or password.';
				break;
			case -2:
				$retVal['statusMsg'] = 'Invalid IP address.';
				break;
			case -3:
				$retVal['statusMsg'] = 'No sufficient credit available for sub-account.';
				break;
			case -4:
				$retVal['statusMsg'] = 'No sufficient credit available for main-account.';
				break;
			case -5:
				$retVal['statusMsg'] = 'SMS has been rejected/couldn\'t be delivered.';
				break;
			case -6:
				$retVal['statusMsg'] = 'Gateway isn\'t available for this network.';
				break;
			case -9:
				$retVal['statusMsg'] = 'SMS with identical message text has been sent too often within the last 180 secondsSMS with identical message text has been sent too often within the last 180 seconds.';
				break;
			case -18:
				$retVal['statusMsg'] = 'SMS is lacking indication of price (premium number ads).';
				break;
			default:
				$retVal['statusMsg'] = 'An unknown error occurred.';
				break;
		}
		
		return $retVal;
	}
	
	/**
	 * Build SMS API Url
	 */
	private function generateUrl($sender, $receiver, $msg) {

		$url = ($this->baseUrl)."?";
		$url .= http_build_query(array(
			'id' => $this->id,
			'pass' => $this->pass,
			'gateway' => $this->gateway,
			'text' => urlencode($msg),
			'nummer' => urlencode($receiver),
			'absender' => urlencode($sender),
			'test' => 1
		));
		return $url;
	}
} 

?>