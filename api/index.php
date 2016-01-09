<?php
require 'vendor/autoload.php';
require 'DAO.php';

use BitcoinPHP\BitcoinECDSA\BitcoinECDSA;

$DAO = new \OneBlock\DAO();

$app = new \Slim\Slim();
$app->get('/', function () {
    echo "API";
});
/**
	API endpoint: /login
	method: GET
	Web page requests a new challenge URI.
	Create one and send.
 **/
$app->get('/login', function () use ($DAO) {
    header("Content-Type: application/json");
	// initial challenge
	$nonce = $DAO->setNonce($_SERVER['REMOTE_ADDR']);
	$challenge = "oneblock://{$_SERVER['HTTP_HOST']}/api/login?x={$nonce}";
	if(empty($_SERVER["HTTPS"])) {
		$challenge .= '&u=1';
	}
	echo '{"challenge":"' . $challenge . '"}';
});
/**
	API endpoint: /options
	method: OPTIONS
	Allow OPTIONS check
 **/
$app->options('/login', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
});
/**
	API endpoint: /login
	method: POST
	Login device posts challenge response.
	Validate signature, set logged in flag.
 **/
$app->post('/login', function () use ($DAO, $app) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    $valid = true;
    $nonce = !empty($_GET['x']) ? $_GET['x'] : null;
	$data = json_decode(file_get_contents('php://input'), true);
	if(!empty($data['isRevoking']) && !$DAO->isExistingAccount($data['pubAddress'])) {
		$app->halt(403, 'Invalid ID, Cannot Revoke');
	} else if(!$DAO->isValidNonce($nonce)) {
		$app->halt(403, 'Invalid Login');		
    } else {
		// validate and login
		//error_log(print_r($data, true));
		$challenge = 'oneblock://' . $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
		//error_log("challenge: $challenge");
		$bitcoinECDSA = new BitcoinECDSA();
		if(empty($data['pubAddress'])
			|| empty($data['loginSig'])
			|| !$DAO->getNonce($nonce)
			|| !$bitcoinECDSA->validateAddress($data['pubAddress'])
			|| !$bitcoinECDSA->checkSignatureForMessage($data['pubAddress'], $data['loginSig'], $challenge)) {
			$app->halt(403, 'Invalid Login');		
		}
    }
	/*
		Once the nonce is successfully signed, there are several options
		depending on what you want your application to do. Examples:
		* match the pubAddress with an existing account and login
		* register a new account and/or take user to a registration page
		* connect pubAddress with a currently logged in account (eg. from account settings page)
		* give an login error if no matching account exists (eg. no registration supported through 1block)
	*/
	$returnData = array('isNew' => true);
	$rowCount = $DAO->updateAccount($data['pubAddress'], $nonce);
	if($rowCount == 0) {
		// no existing account, register it
		$DAO->registerAccount($data['pubAddress'], $nonce);
		$returnData['setRevokeURL'] = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . str_replace('login','setrevoke',$_SERVER['REQUEST_URI']);
	} else {
		// log them in
		$returnData['isNew'] = false;
		$DAO->setLoggedIn($nonce);    	
	}
	if(!empty($data['isRevoking'])) {
		// ID in revoke mode, send back revoke public key
		$user_data = $DAO->getUserData($data['pubAddress']);
		$returnData['revokePubKey'] = $user_data['revokePubKey'];
		$returnData['revokeURL'] = (!empty($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'] . str_replace('login','revoke',$_SERVER['REQUEST_URI']);
	}
	$app->response->setStatus(200);
	echo json_encode($returnData);
});
/**
	API endpoint: /check
	method: POST
	Web page periodically checks if login succeeded.
	If so, set session login information for user.
 **/
$app->post('/check', function () use ($DAO, $app) {
	session_start();
    header("Content-Type: application/json");
    $data = json_decode(file_get_contents('php://input'), true);
    $nonce = !empty($data['nonce']) ? $data['nonce'] : null;
    if($DAO->isValidNonce($nonce)) {
		$row = $DAO->getNonce($nonce, $_SERVER['REMOTE_ADDR']);
		if(!empty($row) && $row['logged_in'] == 'Y') {
			$DAO->deleteNonce($nonce);
			$acctData = $DAO->getAccountDataByNonce($nonce);
			$acctData['logged_in'] = true;
			$_SESSION['oneblock'] = $acctData;
			echo json_encode($acctData);
		} else {
			echo '{"logged_in":false}';
			$app->halt(403, 'Not Logged In');
		}
	} else {
		echo '{"error":"Nonce Invalid"}';
		$app->halt(406, 'Nonce Invalid');
	}
});

/**
	API endpoint: /setrevoke
	method: POST
	Set the revoke key/secret for given account
 **/
$app->post('/setrevoke', function () use ($DAO, $app) {
	session_start();
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json");
	// get account address (id) from nonce
    $nonce = !empty($_GET['x']) ? $_GET['x'] : null;
    $ret = false;
    if($nonce) {
		$acctData = $DAO->getAccountDataByNonce($nonce);
		if($acctData) {
			$id = $acctData['id'];
			$data = json_decode(file_get_contents('php://input'), true);
			$ret = $DAO->setRevokeKeys($id,$data['revokePubKey'],$data['revokeSecretKey']);
		}
    }
    if($ret) {
    	// revoke set, log them in
    	$DAO->setLoggedIn($nonce);    	
		$app->response->setStatus(200);
	} else {
		$app->halt(406, 'Set Revoke Failure');
	}
	
});

/**
	API endpoint: /setrevoke
	method: OPTIONS
	Allow OPTIONS check
 **/
$app->options('/setrevoke', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
});

/**
	API endpoint: /revoke
	method: POST
	Revoke an ID and replace with another
 **/
$app->post('/revoke', function () use ($DAO, $app) {
	session_start();
    header('Access-Control-Allow-Origin: *');
    header("Content-Type: application/json");
	// get account address (id) from nonce
    $nonce = !empty($_GET['x']) ? $_GET['x'] : null;
    $ret = false;
    if($nonce) {
		$acctData = $DAO->getAccountDataByNonce($nonce);
		if($acctData) {
			$pubAddress = $acctData['id'];
			$user_data = $DAO->getUserData($pubAddress);
			$data = json_decode(file_get_contents('php://input'), true);
			if($user_data['revokePubKey'] == $data['revokePubKey'] && $user_data['revokeSecretKey'] == $data['revokeSecretKey']) {
				$ret = $DAO->revokeAndReplaceId($pubAddress, $data['replaceIdPubAddress'], $data['replaceIdRevPubKey'], $data['replaceIdRevSecretKey']);
			}
		}
    }
    if($ret) {
    	// revoke set, log them in
    	//$DAO->setLoggedIn($nonce);    	
		$app->response->setStatus(200);
	} else {
		$app->halt(406, 'Revoke Failure');
	}
	
});
/**
	API endpoint: /revoke
	method: OPTIONS
	Allow OPTIONS check
 **/
$app->options('/revoke', function () {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Content-Type');
});

$app->run();
