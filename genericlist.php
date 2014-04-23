<?php

	namespace SpaceLord\GenericList;

	/**
	 * GenericList
	 *
	 * Pretty self explanatory.  jQuery-esque syntax for ease of use.
	 */
	class GenericList {
		/**
		 * Stores list data
		 * @var array
		 */
		private $_bucket = array();
		/**
		 * Type of the array (associative|numeric)
		 * @var string
		 */
		private $_type = null;

		/**
		 * Create the list and add default elements to it if required
		 * @param array $source Default item array
		 */
		public function __construct($source = array(), $type = "numeric"){
			$this->setType($type);

			if(is_array($source) && sizeof($source) > 0){
				$this->_bucket = $source;
			}

			return $this;
		}

		/**
		 * Set the type of the list
		 * @param string $type Desired array generic type
		 */
		public function setType($type){
			try {
				if(in_array($type, array("associative", "numeric"))){
					$this->_type = $type;
				}
			}catch(Exception $e){
				echo $e->getMessage();
			}

			return $this;
		}

		/**
		 * Get the type of list
		 * @return string
		 */
		public function getType(){
			return $this->_type;
		}

		/**
		 * Add an item to the list
		 * @param  mixed  $item Item you want to add to the list
		 * @param  string $key  An optional key value
		 * @return mixed
		 */
		public function push($item, $key = null){
			if(false === is_null($key) || $this->_type === "associative"){
				$this->_bucket[$key] = $item;
			}else {
				$this->_bucket[] = $item;
			}

			return $item;
		}

		/**
		 * Remove an item from the list by key
		 * @param  string $key Key value for the item you want to delete
		 * @return array
		 */
		public function pop($key){
			if(isset($this->_bucket[$key])){
				unset($this->_bucket[$key]);
			}

			return $this->_bucket;
		}

		/**
		 * Returns a specific item by key
		 * @param  string $key     The key whose value you want to return
		 * @param  string $default A default value to return if the key value does not exist
		 * @return string
		 */
		public function get($key, $default = null){
			if(isset($this->_bucket[$key])){
				return $this->_bucket[$key];
			}

			return $default;
		}

		/**
		 * View the raw _bucket list
		 * @return array
		 */
		public function dump(){
			return $this->_bucket;
		}

		/**
		 * Short hand for method to loop through GenericList items
		 * @param  function $callback      A function to call which handles data within the loop
		 * @param  array   $out_of_scopes  A list of objects which should be added to the local scope
		 * @return mixed
		 */
		public function loop($callback, $out_of_scopes = array()){
			try {
				if(is_callable($callback)){
					$oos = new GenericList($out_of_scopes);

					switch($this->_type){
						case "numeric":
							for($i = 0; $i < sizeof($this->_bucket); $i++){
								$callback($i, $this->_bucket[$i], $oos);
							}
						break;

						case "associative":
							foreach($this->_bucket as $item){
								$callback($item, $oos);
							}
						break;

						default:
							throw new Exception("GenericList::loop - invalid array type");
					}

					return $this;
				}else {
					throw new Exception("GenericList::loop requires a function for the callback argument.");
				}
			}catch(Exception $e){
				echo $e->getMessage();
			}

			return false;
		}
	}

?>