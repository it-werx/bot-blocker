<?php
/**
 * @file
 * The file that serves all functions for SQL.
 * @package sqlinterface
 * @author Ron Mac Quarrie
 * @link http://www.it-werx.net
 * @license http://opensource.org/licenses/GPL-3.0
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 * Contributions by;
 *     Ed Rackham (http://github.com/a1phanumeric/PHP-MySQL-Class)
 *  Changes to Version 0.8.1 copyright (C) 2013
 *	Christopher Harms (http://github.com/neurotroph)
 *
 */

/** MySQL class. */
class MySQL {

	// Base variables
	var $lastError;					// Holds the last error
	var $lastQuery;					// Holds the last query
	var $result;						// Holds the MySQL query result
	var $records;						// Holds the total number of records returned
	var $affected;					// Holds the total number of records affected
	var $rawResults;				// Holds raw 'arrayed' results
	var $arrayedResult;			// Holds an array of the result

	var $hostname;	// MySQL Hostname
	var $username;	// MySQL Username
	var $password;	// MySQL Password
	var $database;	// MySQL Database

	var $databaseLink;		// Database Connection Link



	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $database
	 * @param mixed $username
	 * @param mixed $password
	 * @param string $hostname (default: 'localhost')
	 * @param int $port (default: 3306)
	 * @return void
	 */
	function __construct($database, $username, $password, $hostname='localhost', $port=3306){
		$this->database = $database;
		$this->username = $username;
		$this->password = $password;
		$this->hostname = $hostname.':'.$port;

		$this->Connect();
	}
	/**
	 * Connect function.
	 * 
	 * @access private
	 * @param bool $persistant (default: false)
	 * @return void
	 */
	private function Connect($persistant = false){
		$this->CloseConnection();

		if($persistant){
			$this->databaseLink = mysql_pconnect($this->hostname, $this->username, $this->password);
		}else{
			$this->databaseLink = mysql_connect($this->hostname, $this->username, $this->password);
		}

		if(!$this->databaseLink){
   		$this->lastError = 'Could not connect to server: ' . mysql_error($this->databaseLink);
			return false;
		}

		if(!$this->UseDB()){
			$this->lastError = 'Could not connect to database: ' . mysql_error($this->databaseLink);
			return false;
		}
		return true;
	}

	/**
	 * UseDB function.
	 * 
	 * @access private
	 * @return Database connection made or not
	 */
	private function UseDB(){
		if(!mysql_select_db($this->database, $this->databaseLink)){
			$this->lastError = 'Cannot select database: ' . mysql_error($this->databaseLink);
			return false;
		}else{
			return true;
		}
	}

	/**
	 * SecureData function.
	 * 
	 * @access private
	 * @param mixed $data
	 * @return Return $data in s secure fashion.
	 */
	private function SecureData($data){
		if(is_array($data)){
			foreach($data as $key=>$val){
				if(!is_array($data[$key])){
					$data[$key] = mysql_real_escape_string($data[$key], $this->databaseLink);
				}
			}
		}else{
			$data = mysql_real_escape_string($data, $this->databaseLink);
		}
		return $data;
	}

	/**
	 * ExecuteSQL function.
	 * 
	 * @access public
	 * @param mixed $query
	 * @return Return results of the SQL connection
	 */
	function ExecuteSQL($query){
		$this->lastQuery 	= $query;
		if($this->result 	= mysql_query($query, $this->databaseLink)){
			$this->records 	= @mysql_num_rows($this->result);
			$this->affected	= @mysql_affected_rows($this->databaseLink);

			if($this->records > 0){
                         $this->ArrayResults();
                         return $this->arrayedResult;
                        }else{
                         return true;
                        }

		}else{
			$this->lastError = mysql_error($this->databaseLink);
			return false;
		}
	}
	
	/**
	 * Insert function.
	 * 
	 * @access public
	 * @param mixed $vars
	 * @param mixed $table
	 * @param string $exclude (default: '')
	 * @return Adds a record to the database based on the array key name
	 */
	function Insert($vars, $table, $exclude = ''){

		// Catch Exclusions
		if($exclude == ''){
			$exclude = array();
		}

		array_push($exclude, 'MAX_FILE_SIZE'); // Automatically exclude this one

		// Prepare Variables
		$vars = $this->SecureData($vars);

		$query = "INSERT INTO `{$table}` SET ";
		foreach($vars as $key=>$value){
			if(in_array($key, $exclude)){
				continue;
			}
			//$query .= '`' . $key . '` = "' . $value . '", ';
			$query .= "`{$key}` = '{$value}', ";
		}

		$query = substr($query, 0, -2);

		return $this->ExecuteSQL($query);
	}

	/**
	 * Delete function.
	 * 
	 * @access public
	 * @param mixed $table
	 * @param string $where (default: '')
	 * @param string $limit (default: '')
	 * @param bool $like (default: false)
	 * @return Deletes a record from the database
	 */
	function Delete($table, $where='', $limit='', $like=false){
		$query = "DELETE FROM `{$table}` WHERE ";
		if(is_array($where) && $where != ''){
			// Prepare Variables
			$where = $this->SecureData($where);

			foreach($where as $key=>$value){
				if($like){
					//$query .= '`' . $key . '` LIKE "%' . $value . '%" AND ';
					$query .= "`{$key}` LIKE '%{$value}%' AND ";
				}else{
					//$query .= '`' . $key . '` = "' . $value . '" AND ';
					$query .= "`{$key}` = '{$value}' AND ";
				}
			}

			$query = substr($query, 0, -5);
		}

		if($limit != ''){
			$query .= ' LIMIT ' . $limit;
		}

		return $this->ExecuteSQL($query);
	}

	/**
	 * Select function.
	 * 
	 * @access public
	 * @param mixed $from
	 * @param string $where (default: '')
	 * @param string $orderBy (default: '')
	 * @param string $limit (default: '')
	 * @param bool $like (default: false)
	 * @param string $operand (default: 'AND')
	 * @param string $cols (default: '*')
	 * @return Gets a single row from $from where $where is true
	 */
	function Select($from, $where='', $orderBy='', $limit='', $like=false, $operand='AND',$cols='*'){
		// Catch Exceptions
		if(trim($from) == ''){
			return false;
		}

		$query = "SELECT {$cols} FROM `{$from}` WHERE ";

		if(is_array($where) && $where != ''){
			// Prepare Variables
			$where = $this->SecureData($where);

			foreach($where as $key=>$value){
				if($like){
					//$query .= '`' . $key . '` LIKE "%' . $value . '%" ' . $operand . ' ';
					$query .= "`{$key}` LIKE '%{$value}%' {$operand} ";
				}else{
					//$query .= '`' . $key . '` = "' . $value . '" ' . $operand . ' ';
					$query .= "`{$key}` = '{$value}' {$operand} ";
				}
			}

			$query = substr($query, 0, -(strlen($operand)+2));

		}else{
			$query = substr($query, 0, -6);
		}

		if($orderBy != ''){
			$query .= ' ORDER BY ' . $orderBy;
		}

		if($limit != ''){
			$query .= ' LIMIT ' . $limit;
		}

		return $this->ExecuteSQL($query);

	}

	/**
	 * Update function.
	 * 
	 * @access public
	 * @param mixed $table
	 * @param mixed $set
	 * @param mixed $where
	 * @param string $exclude (default: '')
	 * @return Update a record in the database based on WHERE
	 */
	function Update($table, $set, $where, $exclude = ''){
		// Catch Exceptions
		if(trim($table) == '' || !is_array($set) || !is_array($where)){
			return false;
		}
		if($exclude == ''){
			$exclude = array();
		}

		array_push($exclude, 'MAX_FILE_SIZE'); // Automatically exclude this one

		$set 		= $this->SecureData($set);
		$where 	= $this->SecureData($where);

		// SET

		$query = "UPDATE `{$table}` SET ";

		foreach($set as $key=>$value){
			if(in_array($key, $exclude)){
				continue;
			}
			$query .= "`{$key}` = '{$value}', ";
		}

		$query = substr($query, 0, -2);

		// WHERE

		$query .= ' WHERE ';

		foreach($where as $key=>$value){
			$query .= "`{$key}` = '{$value}' AND ";
		}

		$query = substr($query, 0, -5);

		return $this->ExecuteSQL($query);
	}

	
	/**
	 * ArrayResult function.
	 * 
	 * @access public
	 * @return void
	 */
	function ArrayResult(){
		$this->arrayedResult = mysql_fetch_assoc($this->result) or die (mysql_error($this->databaseLink));
		return $this->arrayedResult;
	}

	
	/**
	 * ArrayResults function.
	 * 
	 * @access public
	 * @return void
	 */
	function ArrayResults(){

		if($this->records == 1){
			return $this->ArrayResult();
		}

		$this->arrayedResult = array();
		while ($data = mysql_fetch_assoc($this->result)){
			$this->arrayedResult[] = $data;
		}
		return $this->arrayedResult;
	}

	
	/**
	 * ArrayResultsWithKey function.
	 * 
	 * @access public
	 * @param string $key (default: 'id')
	 * @return void
	 */
	function ArrayResultsWithKey($key='id'){
		if(isset($this->arrayedResult)){
			unset($this->arrayedResult);
		}
		$this->arrayedResult = array();
		while($row = mysql_fetch_assoc($this->result)){
			foreach($row as $theKey => $theValue){
				$this->arrayedResult[$row[$key]][$theKey] = $theValue;
			}
		}
		return $this->arrayedResult;
	}

	
	/**
	 * LastInsertID function.
	 * 
	 * @access public
	 * @return void
	 */
	function LastInsertID(){
		return mysql_insert_id();
	}

	
	/**
	 * CountRows function.
	 * 
	 * @access public
	 * @param mixed $from
	 * @param string $where (default: '')
	 * @return void
	 */
	function CountRows($from, $where=''){
		$result = $this->Select($from, $where, '', '', false, 'AND','count(*)');
		return $result["count(*)"];
	}

	
	/**
	 * CloseConnection function.
	 * 
	 * @access public
	 * @return void
	 */
	function CloseConnection(){
		if($this->databaseLink){
			mysql_close($this->databaseLink);
		}
	}
}
?>