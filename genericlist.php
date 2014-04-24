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
		 * Used in GenericList::sort to get around usort scope issue
		 * @var string
		 */
		private $_key = null;

		/**
		 * Number of elements in the _bucket
		 * @var integer
		 */
		public $length = 0;

		/**
		 * Create the list and add default elements to it if required
		 * @param array $source Default item array
		 */
		public function __construct($source = array(), $type = "numeric"){
			$this->setType($type);

			if(is_array($source) && sizeof($source) > 0){
				$this->_bucket = $source;

				$this->length = sizeof($source);
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
		 * Prepend items to the beginning of _bucket
		 * Note: Does NOT preserve keys
		 * @param  mixed $args  String|Array, items you want to prepend to the list
		 * @return array
		 */
		public function unshift($args){
			$diff = array_unshift($this->_bucket, $args);

			//size of array changes, modify length property accordingly
			$this->length = ($this->length - $diff);

			return $this->_bucket;
		}

		/**
		 * Append items to the end of _bucket
		 * Note: Does NOT preserve keys
		 * @return array
		 */
		public function shift(){
			array_shift($this->_bucket);

			//size of array changes, modify length property accordingly
			$this->length++;

			return $this->_bucket;
		}

		/**
		 * Returns a specific item by key
		 * @param  string $key     The key whose value you want to return
		 * @param  string $default A default value to return if the key value does not exist
		 * @return string
		 */
		public function indexOf($key, $default = null){
			if(isset($this->_bucket[$key])){
				return $this->_bucket[$key];
			}

			return $default;
		}

		/**
		 * Returns a value in the array, if an element in the array satisfies 
		 * the provided testing function. Otherwise a default is returned.
		 * @param  string $value   The value you want to locate within _bucket
		 * @param  mixed  $default A default value to return
		 * @return mixed
		 */
		public function find($value, $default = null){
			$match = null;

			for($i = 0; $i < $this->length; $i++){
				if(is_object($this->_bucket[$i]) || is_array($this->_bucket[$i])){
					foreach($this->_bucket[$i] as $key => $_item){
						if($key == $value || $_item == $value){
							$match = $this->_bucket[$i];
						}
					}
				}else {
					if($value == $this->_bucket[$i]){
						$match = $this->_bucket[$i];
					}
				}
			}

			if(false === is_null($match)){
				return $match;
			}

			return $default;
		}

		/**
		 * Get the key values of the _bucket list
		 * @param  string $filter  Only get keys whose values equal this
		 * @return array
		 */
		public function keys($filter = null){
			if(false === is_null($filter))
				return array_keys($this->_bucket, $filter);

			return array_keys($this->_bucket);
		}

		/**
		 * Sort the _bucket list by KEY
		 * @param string $key  Key value to sort the array by
		 * @param string $dir  Sorting direction
		 * @return array
		 */
		public function sort($key, $dir = "DESC"){
			//hack to get around scoping issue
			$this->_key = $key;
			$dir = strtoupper($dir);

			if($dir == "DESC"){
				usort($this->_bucket, function($a, $b){
					if(is_object($a) && is_object($b)){
						if(property_exists($a, $this->_key) && property_exists($b, $this->_key))
							return $a->{$this->_key} - $b->{$this->_key};

						return 0;
					}

					return $a[$this->_key] - $b[$this->_key];
				});
			}elseif($dir == "ASC"){
				usort($this->_bucket, function($a, $b){
					if(is_object($a) && is_object($b)){
						if(property_exists($a, $this->_key) && property_exists($b, $this->_key))
							return $a->{$this->_key} - $b->{$this->_key} * -1;

						return 0;
					}

					return $a[$this->_key] - $b[$this->_key] * -1;
				});
			}

			return $this->_bucket;
		}

		/**
		 * View the raw _bucket list
		 * @return array
		 */
		public function dump(){
			return $this->_bucket;
		}

		/**
		 * String representation of the array
		 * @return string
		 */
		public function toString(){
			return serialize($this->_bucket);
		}

		/**
		 * Short hand for method to loop through GenericList items
		 * TODO: implement a counter to pass to callback
		 * @param  function $callback      A function to call which handles data within the loop
		 * @param  array   $out_of_scopes  A list of objects which should be added to the local scope
		 * @return mixed
		 */
		public function loop($callback, $out_of_scopes = array()){
			try {
				if(is_callable($callback)){
					$oos = new GenericList($out_of_scopes);
					$counter = 1;

					switch($this->_type){
						case "numeric":
							for($i = 0; $i < sizeof($this->_bucket); $i++){
								$oos->push($counter++);
								$callback($i, $this->_bucket[$i], $oos);
							}
						break;

						case "associative":
							foreach($this->_bucket as $item){
								$oos->push($counter++);
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