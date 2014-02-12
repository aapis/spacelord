<?php
	
	namespace SpaceLord\Status;

	/**
	 * Status
	 * 
	 * Sends HEAD requests to a site, or list of sites, and returns status 
	 * information (i.e. website is down, website is behind basic auth, etc)
	 */
	class Status {
		/**
		 * The URL you want to test
		 * @var string
		 */
		private $_url;
		
		/**
		 * How should the resulting data be formatted? 
		 * @var string (json|raw)
		 */
		private $_output_type;

		/**
		 * A social dashboard vendor to format the output for
		 * @var string
		 */
		private $_vendor;
		
		/**
		 * Predefined list of sites to test - the default action will run these
		 * sites
		 * @var array
		 */
		private $_predefined_sites = array(
			"http://aksis.ca",
			"http://aroscalgary2014.ca",
			"http://emeraldfoundation.ca",
			"http://robynbraun.wearefree.ca",
			);

		/**
		 * A list of approved vendors whose object formatting is known
		 * @var array
		 */
		private $_approved_vendors = array(
			"raw",
			"geckoboard",
			"ducksboard",
			);

		/**
		 * Configuration options
		 * @var array
		 */
		private $_options = array(
			"print_headers" => true,
			);

		/**
		 * A list of custom URLs, write-only, accessible by self::setCustomTargets()
		 * @var array
		 */
		private $_custom_urls = array();

		/**
		 * Setup basic class parameters
		 * @param string $type The set the output type directly (default is JSON)
		 */
		public function __construct($type = "json"){
			if(isset($_GET["url"]) && $_GET["url"] !== ""){
				$this->_url = $this->_cleanURL($_GET["url"]);
			}

			$this->_output_type = $type;

			return $this;
		}

		/**
		 * Execute the requests and display the results
		 * @return mixed
		 */
		public function run(){
			$action = (isset($_GET["url"]) ? "single" : "predefined");

			switch($action){
				case "single":
					if(false === is_null($this->_url)){
						$this->_runSingle();
					}
				break;

				default:
				case "predefined":
					$this->_runMultiple();
			}

			return $this->_postRun();
		}

		/**
		 * Public method to set vendor for output formatting
		 * @param string $vendor
		 */
		public function setVendor($vendor = null){
			$this->_vendor = "raw";

			if(false === is_null($vendor) && in_array($vendor, $this->_approved_vendors)){
				$this->_vendor = $vendor;
			}

			return $this->_vendor;
		}

		/**
		 * Populate the custom targets array with data
		 * @param array $urls
		 */
		public function setCustomTargets($urls = array()){
			if(is_array($urls)){
				$this->_custom_urls = $urls;
			}

			return $this->_custom_urls;
		}

		/**
		 * Modify the value of a configuration option
		 * @param string $key
		 * @param string $value
		 */
		public function setOption($key, $value){
			if(isset($this->_options[$key])){
				$this->_options[$key] = $value;
				
				return $this->_options[$key];
			}

			return false;
		}

		/**
		 * Modify the output type after class instantiation (i.e. if the class
		 * is auto-instantiated, this allows you to change the output from 
		 * JSON to RAW)
		 * @param string $type (raw|json)
		 */
		public function setType($type = null){
			if(false === is_null($type)){
				$this->_output_type = $type;
			}

			return $this->_output_type;
		}

		/**
		 * Creates complete URLs if only a fragment is provided, but does so very
		 * lazily - don't trust this to build a real URL, it just creates valid
		 * ones for the get_header() function call
		 * @param  string $url The raw URL, taken directly from the query string
		 * @return string
		 */
		private function _cleanURL($url){
			//prefix http:// if it is missing
			//var_dump(false === strpos($url, "http://"));
			if(false === strpos($url, "http://") && false === strpos($url, "https://")){
				$url = sprintf("http://%s", $url);
			}

			//there is a query string, strip out all that crap
			if(strpos($url, "?") > 0){
				$_parts = explode("?", $url);

				$url = $_parts[0];
			}

			//append .com to the end if it's missing a TLD
			$_domain_parts = explode(".", $url);

			if(sizeof($_domain_parts) < 3 && strpos($url, ".com") === false && strpos($url, ".ca") === false && strpos($url, ".net") === false && strpos($url, ".org") === false){
				$url = sprintf("%s.com", $url);
			}

			return $url;
		}

		/**
		 * Spit out the data in the requested format
		 * @return mixed (string|bool)
		 */
		private function _postRun(){
			if($this->_output_type == "json"){
				return $this->output->set_content_type("application/json")->set_output(json_encode($this->_formatted()));
			}

			if($this->_output_type == "raw"){
				return $this->_formatted();
			}

			return false;
		}

		/**
		 * Formats the raw data according to the chosen vendor's object prototype
		 * requirements
		 * @return array
		 */
		private function _formatted(){
			$formatted = $this->_output;

			switch($this->_vendor){
				case "geckoboard":

				break;

				case "ducksboard":

				break;

				default:
				case "raw":
					break;
			}

			return $formatted;
		}

		/**
		 * Gets the HTTP response code from the header
		 * @param  string  $header 
		 * @return int
		 */
		private function _getHTTPCode($headers){
			$_codes = array();

			for($i = 0; $i < sizeof($headers); $i++){
				if(strpos($headers[$i], "HTTP") !== false){
					$_tmp = explode(" ",  $headers[$i]);
					$_codes[] = (int) $_tmp[1];
				}
			}
			
			return max($_codes);
		}

		/**
		 * Get data from a single URL
		 * @return void
		 */
		private function _runSingle(){
			$output = array("status" => "error", "message" => sprintf("%s is down", $this->_url));
			$headers = @get_headers($this->_url);

			//if it's a good status code, change the message and status
			if($this->_getHTTPCode($headers[0]) < 400){
				$output[$this->_url] = array("status" => "success", "message" => sprintf("%s is up", $this->_url));
			}

			if($this->_options["print_headers"]){
				$output[$this->_url]["headers"] = $headers;
			}

			$this->_output = $output;
		}
		
		/**
		 * Get data from a list of URLs (predefined and custom)
		 * @return void
		 */
		private function _runMultiple(){
			$output = array();
			$source = (sizeof($this->_custom_urls) === 0 ? $this->_predefined_sites : $this->_custom_urls);

			foreach($source as $site){
				if($headers = @get_headers($site)){
					$httpCode = $this->_getHTTPCode($headers);
					
					//if it's a status code header AND the code is 200 (aka, good connection)
					if($httpCode < 400){
						$output[$site]["status"] = "success";
						$output[$site]["message"] = sprintf("No issues reported", $httpCode);
					}else {
						//there are other headers, the site is probably up but we're not totally sure
						if($httpCode > 499){
							$output[$site]["status"] = "error";
							$output[$site]["message"] = "Website is reporting issues";
						}

						if($httpCode >= 400 && $httpCode < 500){
							$output[$site]["status"] = "warning";
							$output[$site]["message"] = "The status could not be determined";
						}

						if($httpCode === 401){
							$output[$site]["status"] = "authblocked";
							$output[$site]["message"] = "Protected by HTTP authentication, but is responding";
						}
					}
				}else {
					$output[$site]["status"] = "error";
					$output[$site]["message"] = "Website is reporting issues";
				}

				if($this->_options["print_headers"]){
					$output[$site]["headers"] = $headers;
				}
			}

			$this->_output = $output;
		}
	}

?>