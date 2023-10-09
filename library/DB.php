<?php
	namespace TexasHoldemBundle;
	
	define("HOSTNAME", "localhost");
	define("USER", "");
	define("PASSWORD", "");
	define("DATABASE", "poker666");
	
	class CustomException extends \Exception {
		// Custom message
		public function errorMessage() {
			$errorMsg = 'Error on line '.$this->getLine().' in '.$this->getFile()
			.': <b>'.$this->getMessage().'</b> is not a valid E-Mail address';
			return $errorMsg;
		}
	}
	
	function makeRefArr(&$arr) {
		$refs = array();
		
		foreach($arr as $key => &$val) {
			$refs[$key] = &$val;
		}
		
		return $refs;
	}

	function array_copy($arr, $deep= true) {
		$newArr = array();
		
		if ($deep) {
			foreach ($arr as $key=>$val) {
				if (is_object($val)) {
					$newArr[$key] = clone($val);
				} else if (is_array($val)) {
					$newArr[$key] = array_copy($val);
				} else {
					$newArr[$key] = $val;
				}
			}
		} else {
			foreach ($arr as $key=>$val) {
				$newArr[$key] = $val;
			}
		}
		
		return $newArr;
	}

	class DB {
		public $db = null;
		
		public function connect() {		
			$this->db= new \mysqli(HOSTNAME, USER, PASSWORD, DATABASE);
			
			if (mysqli_connect_errno()) {
				throw new CustomException('Connection failed: ' . mysqli_connect_error());
			}
			
			$this->db->set_charset("utf8");
		}
		
		function __construct() {
			$this->connect();
		}
		
		function __destruct() {
			$this->close();
		}
		
		public function getLastInsertID() {
			if ($this->db) {
				return $this->db->insert_id;
			}
			return 0;
		}
		
		public function close() {
			
		}
		
		public function query($query, $objs = array()) {
			if (!$this->db) $this->connect();
			$objs = (array)$objs;
			$statement = $this->db->prepare($query);
			if (!$statement) {
				throw new CustomException('Query failed: ' . $this->db->error);
			}
			$types = array();
			$values = array();
			
			if (count($objs)>0) {
				foreach ($objs as $obj) {
					$type = gettype($obj);
					
					switch ($type) {
						case 'boolean': case 'integer':
							$types[] = 'i';
							$values[] = intval($obj);
							break;
						case 'double':
							$types[] = 'd';
							$values[] = doubleval($obj);
							break;
						case 'string':
							$types[] = 's';
							$values[] = (string)$obj;
							break;
						case 'array': case 'object':
							$types[] = 's';
							$values[] = json_encode($obj);
							break;
						case 'resource': case 'null': case 'unknown type': default:
							throw new CustomException('Unsupported object passed through as query prepared object!');
					}
				}
				
				$params = makeRefArr($values);
				array_unshift($params, implode('', $types));
				call_user_func_array(array($statement, 'bind_param'), $params);
			}
			
			if (!$statement->execute()) {
				return null;
			} else {
				$statement->store_result();
				return $statement;
			}
		}
		
		public function objectExists($query, $objs = array()) {
			$statement = $this->query($query, $objs);
			
			return (is_object($statement) && $statement->num_rows>0);
		}
		
		private function getFieldNames($statement) {
			$result = $statement->result_metadata();
			$fields = $result->fetch_fields();
			
			$fieldNames = array();
			foreach($fields as $field) {
				$fieldNames[$field->name] = null;
			}
			
			return $fieldNames;
		}
		
		public function getObject($query, $objs = array()) {
			$statement = $this->query($query, $objs);

			if (!is_object($statement) || $statement->num_rows<1) {
				return null;
			}

			$result = $statement->result_metadata();
			$fields = $result->fetch_fields();
			
			$fieldNames = array();
			foreach($fields as $field) {
				$fieldNames[$field->name] = "";
			}

			$params = [];
			foreach ($fieldNames as $key => $_) {
				$params[] = &$fieldNames[$key];
			}

			call_user_func_array(array($statement, 'bind_result'), $params);
			
			$statement->fetch();
			$statement->close();
			
			return $fieldNames;
		}

		
		public function getObjects($query, $objs = array()) {
			$statement = $this->query($query, $objs);

			if (!is_object($statement) || $statement->num_rows<1) {
				return array();
			}

			$result = $statement->result_metadata();
			$fields = $result->fetch_fields();
			
			$fieldNames = array();
			foreach($fields as $field) {
				$fieldNames[$field->name] = "";
			}

			$params = [];
			foreach ($fieldNames as $key => $_) {
				$params[] = &$fieldNames[$key];
			}

			call_user_func_array(array($statement, 'bind_result'), $params);
			
			$results = array();
			while ($statement->fetch()) {
				$results[] = array_copy($fieldNames, false);
			}
			
			$statement->close();
			
			return $results;
		}

		
		public function getTable($tableName) {
			if (!$this->db) $this->connect();
			
			$tableName = $this->db->escape_string($tableName);
			
			return $this->getObjects('SELECT * FROM `' . $tableName . '`;');
		}
		
		public function getTableRow($tableName, $field, $value) {
			if (!$this->db) $this->connect();
			
			$tableName = $this->db->escape_string($tableName);
			$field = $this->db->escape_string($field);
			
			return $this->getObject('SELECT * FROM `' . $tableName . '` WHERE `' . $field . '` = ? LIMIT 1;', $value);
		}
		
		public function getTableRows($tableName, $field, $value, $sortField = null, $sortDesc = false) {
			if (!$this->db) $this->connect();
			
			$tableName = $this->db->escape_string($tableName);
			$field = $this->db->escape_string($field);
			
			if ($sortField == null) {
				$sortField = $field;
			} else {
				$sortField = $this->db->escape_string($sortField);
			}
			
			return $this->getObjects('SELECT * FROM `' . $tableName . '` WHERE `' . $field . '` = ? ORDER BY `' . $sortField . '` ' . ($sortDesc ? 'DESC' : 'ASC') . ';', $value);
		}
	}
?>