<?php

	//Safaricom Till Number JSON POST///
		// read JSON input
		$data = json_decode(file_get_contents('php://input'), true);
		
		// set json string to php variables
		$sender_phone = $data['sender_phone'];
		$transaction_reference = $data['transaction_reference'];
		$signature = $data['signature'];
		$amount = $data['amount'];
		$first_name = $data['first_name'];
		$middle_name = $data['middle_name'];
		$last_name = $data['last_name'];
		
		
	//Conditions for type of voucher sent
	/*
	if($amount >= 30 && $amount < 80)//2 HOURS
		{
			$quota=1;
			$minutes=120;
		}
		elseif($amount >= 80 && $amount < 380)//DAILY
		{
			$quota=1;
			$minutes=1440;			
		}
		elseif($amount >= 380 && $amount < 1000)//WEEKLY
		{
			$quota=1;
			$minutes=10080;
		}
		elseif($amount >= 1000 && $amount < 10000)//MONTHLY
		{
			$quota=0;
			$minutes=43800;
		}

	*/
	
	
	/**
	 * Unifi API 
	 */

	/**
	 * using the composer autoloader
	 */
	require_once('vendor/autoload.php');

	/**
	 * include the config file (place your credentials etc. there if not already present)
	 * see the config.template.php file for an example
	 */
	require_once('config.php');

	/**
	 * minutes the voucher is valid after activation (expiration time)
	 */
	//$voucher_expiration = 10;
	$voucher_expiration = $minutes;

	/**
	 * the number of vouchers to create
	 */
	$voucher_count = 1;

	/**
	 * single-use = 1 or multi-use = 0
	 */
	//$voucher_quota = 1;
	$voucher_quota = $quota;

	/**
	 * M-PESA Transaction ID
	 */
	$voucher_note = $transaction_reference;

	/**
	 * The site where you want to create the voucher(s)
	 */
	$site_id = $controllersiteid;

	/**
	 * initialize the UniFi API connection class and log in to the controller
	 */
	$unifi_connection = new UniFi_API\Client($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
	$set_debug_mode   = $unifi_connection->set_debug($debug);
	$loginresults     = $unifi_connection->login();

	/**
	 * then we create the required number of vouchers with the requested expiration value
	 */
	$voucher_result = $unifi_connection->create_voucher($voucher_expiration, $voucher_count, $voucher_quota, $voucher_note);

	/**
	 * we then fetch the newly created vouchers by the create_time returned
	 */
	$vouchers = $unifi_connection->stat_voucher($voucher_result[0]->create_time);

	/**
	 * provide feedback (the newly created vouchers) in json format
	 */
	//echo json_encode($vouchers, JSON_PRETTY_PRINT);
	$json = json_encode($vouchers);
	$json = json_decode($json, TRUE);
	$coder = $json[0]['code'];    
	//$coder = json_decode($vouchers, TRUE)['response']['code'];

	

	
	
	/**
	 * Infobip API 
	 */
	
	use infobip\api\client\SendSingleTextualSms;
	use infobip\api\configuration\BasicAuthConfiguration;
	use infobip\api\model\sms\mt\send\textual\SMSTextualRequest;

	// Initializing SendSingleTextualSms client with appropriate configuration
	$client = new SendSingleTextualSms(new BasicAuthConfiguration($infobipuser, $infobippassword));

	// Creating request body
	$requestBody = new SMSTextualRequest();
	$requestBody->setFrom($infobipsenderid);
	$requestBody->setTo([$sender_phone]);
	$requestBody->setText("Hi $first_name, your hotspot login code is $coder");

	// Executing request
	try {
		$response = $client->execute($requestBody);
		$sentMessageInfo = $response->getMessages()[0];
		echo "Message ID: " . $sentMessageInfo->getMessageId() . "\n";
		echo "Receiver: " . $sentMessageInfo->getTo() . "\n";
		echo "Message status: " . $sentMessageInfo->getStatus()->getName();
	} catch (Exception $exception) {
		echo "HTTP status code: " . $exception->getCode() . "\n";
		echo "Error message: " . $exception->getMessage();
	}