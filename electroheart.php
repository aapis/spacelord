<?php
	defined("BASEPATH") or die;

	/**
	 * Electroheart - mcrypt for your Codeigniterz
	 *
	 * To use this outside of Codeigniter, roll your own self::_generateKey 
	 * method (CI functions are used to generate some random strings there)
	 */
	class Electroheart {
		/**
		 * Hexadecimal string of randomness
		 * @var string
		 */
		private $_key;

		/**
		 * Encryption algorithm
		 * @var string
		 */
		private $_cipher;

		/**
		 * Encryption mode
		 * @var string
		 */
		private $_mode;

		/**
		 * Final encrypted string hash
		 * @var string
		 */
		private $_hash;

		/**
		 * Build the object
		 */
		public function __construct(){
			try {
				if(false === function_exists("mcrypt_encrypt")){
					throw new Exception("Fatal Error: function MCRYPT_ENCRYPT does not exist.  Please enable the MCRYPT_ENCRYPT extension in your PHP settings.");
				}

				if(false === function_exists("hash_hmac")){
					throw new Exception("Fatal Error: function HASH_HMAC does not exist.  Please enable the HASH_HMAC extension in your PHP settings");
				}

				//setup required properties
				$this->_key = $this->_generateKey();

				$this->_cipher = MCRYPT_RIJNDAEL_256;

				$this->_mode = MCRYPT_MODE_CBC;

				$this->_iv_size = mcrypt_get_iv_size($this->_cipher, $this->_mode);				
			}catch(Exception $e){
				die($e->getMessage());
			}
		}

		/**
		 * Encrypt a string using AES-256 encryption
		 * @param  string  $string         The string you want to encrypt
		 * @param  boolean $enable_decrypt Set to TRUE to allow decryption, FALSE to make it a one-way hash
		 * @return string
		 */
		public function encrypt($string, $enable_decrypt = true){
			$iv = mcrypt_create_iv($this->_iv_size, MCRYPT_RAND);

			$toEncode = mcrypt_encrypt($this->_cipher, $this->_key, $string, $this->_mode, $iv);

			if($enable_decrypt){
				$toEncode = $iv . $toEncode;
			}

			$this->_hash = base64_encode($toEncode);

			return $this->_hash;
		}

		/**
		 * Decrypt an encrypted string
		 * @param  string $string The ENCRYPTED string you want to decode (encrypted by self::encrypt)
		 * @return string
		 */
		public function decrypt($string, $trim = true){
			try {
				$decoded = base64_decode($string);
				$get_iv = substr($decoded, 0, $this->_iv_size);
				$decoded = substr($decoded, $this->_iv_size);

				if(false === $decoded){
					throw new Exception("Decrypt failed: decoded value too short.. was this encrypted with self::encrypt?");
				}

				$decrypted = mcrypt_decrypt($this->_cipher, $this->_key, $decoded, $this->_mode, $get_iv);
				
				//strip the \0's off because we cannot guarantee user passwords will be long enough
				if($trim){
					return rtrim($decrypted);
				}

				return $decrypted;
			}catch(Exception $e){
				die($e->getMessage());
			}
		}

		/**
		 * Generate the encryption key
		 * @param  integer $length Length of final key (note: must not exceed maximum length supported by self::_mode)
		 * @return string
		 */
		private function _generateKey($length = 32){
			$_tmp = array(random_string("encrypt", $length), random_string("encrypt", $length));
			$pool = implode("", $_tmp);
			$ret = $pool;

			if(strlen($pool) > $length){
				$ret = substr($pool, 0, $length);
			}			

			$ret = pack("H*", hash_hmac("sha256", $ret, $this->_key));

			return $ret;
		}
	}
	
?>