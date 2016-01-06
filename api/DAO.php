<?php 

namespace OneBlock;

class DAO {

	var $dbh;
	var $type = 'mysql';
	var $host = 'localhost';
	var $dbname = '1block';
	var $dbuser = '1block';
	var $dbpass = '1block';
	
	function __construct() {
		$this->dbh = new \PDO("{$this->type}:host={$this->host};dbname={$this->dbname}", $this->dbuser, $this->dbpass);
	}
	
	function isValidNonce($nonce) {
		return !empty($nonce) && preg_match('!^[a-zA-Z0-9]{32}$!', $nonce);
	}
	
	function setNonce($ip) {
		$nonce = bin2hex(openssl_random_pseudo_bytes(16));
		$stmt = $this->dbh->prepare("INSERT INTO nonce (id, ip, add_date, logged_in) VALUES (?, ?, NOW(), 'N')");
		$stmt->bindParam(1, $nonce);
		$stmt->bindParam(2, $ip);
		$stmt->execute();
		return $nonce;
	}
	
	function getNonce($nonce, $ip = null) {
		if($ip) {
			$stmt = $this->dbh->prepare("SELECT * from nonce where id=? and ip=? and add_date > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1");
			$stmt->bindParam(1, $nonce);				
			$stmt->bindParam(2, $ip);				
		} else {
			$stmt = $this->dbh->prepare("SELECT * from nonce where id=? and add_date > DATE_SUB(NOW(), INTERVAL 5 MINUTE) LIMIT 1");
			$stmt->bindParam(1, $nonce);		
		}
		$stmt->execute();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}

	function setLoggedIn($nonce) {
		$stmt = $this->dbh->prepare("UPDATE nonce SET logged_in='Y' where id=?");
		$stmt->bindParam(1, $nonce);
		$stmt->execute();
		return $nonce;
	}

	function updateAccount($address, $nonce) {
		$stmt = $this->dbh->prepare("UPDATE users SET logins=logins+1, last_login=NOW(), last_nonce=? where id = ?");
		$stmt->bindParam(1, $nonce);
		$stmt->bindParam(2, $address);
		$stmt->execute();
		// returns 0 if no account, 1 if updated existing account
		return $stmt->rowCount();
	}

	function registerAccount($address, $nonce) {
		$stmt = $this->dbh->prepare("INSERT INTO users (id,logins,last_login,last_nonce) values (?,1,NOW(),?)");
		$stmt->bindParam(1, $address);
		$stmt->bindParam(2, $nonce);
		$stmt->execute();
		// returns 1 for new account
		return $stmt->rowCount();
	}

	function isExistingAccount($pubAddress) {
		$stmt = $this->dbh->prepare("SELECT id from users where id=? LIMIT 1");
		$stmt->bindParam(1, $pubAddress);		
		$stmt->execute();
		return $stmt->rowCount() > 0;
	}


	function setRevokeKeys($pubAddress,$revokePubKey,$revokeSecretKey) {
		$result = $this->getUserData($pubAddress);
		if(empty($result)) {
			$user_data = array();
	 	} else {
			$user_data = json_decode($result, true);
		}
		if(!is_array($user_data))
			return false;
		$user_data['revokePubKey'] = $revokePubKey;
		$user_data['revokeSecretKey'] = $revokeSecretKey;
		$stmt = $this->dbh->prepare("UPDATE users SET user_data=? where id=?");
		$stmt->bindParam(1, json_encode($user_data));
		$stmt->bindParam(2, $pubAddress);
		return $stmt->execute();
	}

	function getUserData($pubAddress) {
		$stmt = $this->dbh->prepare("SELECT user_data from users where id=? LIMIT 1");
		$stmt->bindParam(1, $pubAddress);		
		$stmt->execute();
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return !empty($row['user_data']) ? json_decode($row['user_data'], true) : false;
	}

	function deleteNonce($nonce) {
		$stmt = $this->dbh->prepare("DELETE from nonce where id=?");
		$stmt->bindParam(1, $nonce);
		$stmt->execute();
		return $nonce;
	}
	
	function getAccountDataByNonce($nonce) {
		$stmt = $this->dbh->prepare("SELECT * from users where last_nonce=? LIMIT 1");
		$stmt->bindParam(1, $nonce);		
		$stmt->execute();
		return $stmt->fetch(\PDO::FETCH_ASSOC);
	}
	
	function revokeAndReplaceId($pubAddress, $replaceAddress, $replaceRevokePubKey, $replaceRevokeSecretKey) {
		if(empty($pubAddress)||empty($replaceAddress)||empty($replaceRevokePubKey)||empty($replaceRevokeSecretKey)) {
			return false;
		}
		$user_data = array();
		$user_data['revokePubKey'] = $replaceRevokePubKey;
		$user_data['revokeSecretKey'] = $replaceRevokeSecretKey;
		$stmt = $this->dbh->prepare("UPDATE users SET id=?, user_data=? where id=?");
		$stmt->bindParam(1, $replaceAddress);
		$stmt->bindParam(2, json_encode($user_data));
		$stmt->bindParam(3, $pubAddress);
		return $stmt->execute();
	}
	
}