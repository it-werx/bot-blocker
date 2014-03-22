<?php
/**
 * @file
 * The file that serves all functions for SQL.
 * @package scanatize
 * @author Ron Mac Quarrie
 * @link http://www.it-werx.net
 * @license http://opensource.org/licenses/GPL-3.0
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 *
 */

/**
 *  Pork.FormValidator
 *  Validates arrays or properties by setting up simple arrays. 
 *  Note that some of the regexes are for dutch input!
 *  Example:
 * 
 *  $validations = array('name' => 'anything','email' => 'email','alias' => 'anything','pwd'=>'anything','gsm' => 'phone','birthdate' => 'date');
 *  $required = array('name', 'email', 'alias', 'pwd');
 *  $sanatize = array('alias');
 *
 *  $validator = new FormValidator($validations, $required, $sanatize);
 *                  
 *  if($validator->validate($_POST))
 *  {
 *      $_POST = $validator->sanatize($_POST);
 *      // now do your saving, $_POST has been sanatized.
 *      die($validator->getScript()."<script type='text/javascript'>alert('saved changes');</script>");
 *  }
 *  else
 *  {
 *      die($validator->getScript());
 *  }   
 *  
 * To validate just one element:
 * $validated = new FormValidator()->validate('blah@bla.', 'email');
 * 
 * To sanatize just one element:
 * $sanatized = new FormValidator()->sanatize('<b>blah</b>', 'string');
 * 
 * @package pork
 * @author SchizoDuckie
 * @copyright SchizoDuckie 2008
 * @version 1.0
 * @access public
 */
class FormValidator{
	
    /**
     * regexes
     * 
     * @var mixed
     * @access public
     * @static
     */
    public static $regexes = Array(
            'date' => "^[0-9]{1,2}[-/][0-9]{1,2}[-/][0-9]{4}\$",
            'amount' => "^[-]?[0-9]+\$",
            'number' => "^[-]?[0-9,]+\$",
            'alfanum' => "^[0-9a-zA-Z ,.-_\\s\?\!]+\$",
            'not_empty' => "[a-z0-9A-Z]+",
            'words' => "^[A-Za-z]+[A-Za-z \\s]*\$",
            'phone' => "^[0-9]{10,11}\$",
            'zipcode' => "^[1-9][0-9]{3}[a-zA-Z]{2}\$",
            'plate' => "^([0-9a-zA-Z]{2}[-]){2}[0-9a-zA-Z]{2}\$",
            'price' => "^[0-9.,]*(([.,][-])|([.,][0-9]{2}))?\$",
            '2digitopt' => "^\d+(\,\d{2})?\$",
            '2digitforce' => "^\d+\,\d\d\$",
            'anything' => "^[\d\D]{1,}\$"
    );
    private $validations, $sanatations, $mandatories, $errors, $corrects, $fields;

	
    /**
     * __construct function.
     * 
     * @access public
     * @param array $validations (default: array())
     * @param array $mandatories (default: array())
     * @param array $sanatations (default: array())
     * @return void
     */
    public function __construct($validations=array(), $mandatories = array(), $sanatations = array())
    {
        $this->validations = $validations;
        $this->sanatations = $sanatations;
        $this->mandatories = $mandatories;
        $this->errors = array();
        $this->corrects = array();
    }
    
    /**
     * validate function.
     * Validates an array of items (if needed) and returns true or fals
     * @access public
     * @param mixed $items
     * @return void
     */
    public function validate($items){
        $this->fields = $items;
        $havefailures = false;
        foreach($items as $key=>$val){
            if((strlen($val) == 0 || array_search($key, $this->validations) === false) && array_search($key, $this->mandatories) === false){
                $this->corrects[] = $key;
                continue;
            }
            $result = self::validateItem($val, $this->validations[$key]);
            if($result === false) {
                $havefailures = true;
                $this->addError($key, $this->validations[$key]);
            }
            else {
                $this->corrects[] = $key;
            }
        }

        return(!$havefailures);
    }
    
    /**
     * getScript function.
     * Adds unvalidated class to thos elements that are not validated. Removes them from classes that are.
     * @access public
     * @return void
     */
    public function getScript(){
        if(!empty($this->errors)){
            $errors = array();
            foreach($this->errors as $key=>$val) { $errors[] = "'INPUT[name={$key}]'"; }

            $output = '$$('.implode(',', $errors).').addClass("unvalidated");'; 
            $output .= "new FormValidator().showMessage();";
        }
        if(!empty($this->corrects)){
            $corrects = array();
            foreach($this->corrects as $key) { $corrects[] = "'INPUT[name={$key}]'"; }
            $output .= '$$('.implode(',', $corrects).').removeClass("unvalidated");';   
        }
        $output = "<script type='text/javascript'>{$output} </script>";
        return($output);
    }
    
    /**
     * sanatize function.
     * Sanatizes an array of items according to the $this->sanatations
     * sanatations will be standard of type string, but can also be specified.
     * For ease of use, this syntax is accepted:
     * $sanatations = array('fieldname', 'otherfieldname'=>'float');
     * @access public
     * @param mixed $items
     * @return void
     */
    public function sanatize($items){
        foreach($items as $key=>$val){
            if(array_search($key, $this->sanatations) === false && !array_key_exists($key, $this->sanatations)) continue;
            $items[$key] = self::sanatizeItem($val, $this->validations[$key]);
        }
        return($items);
    }
     
    /**
     * addError function.
     * Adds an error to the errors array.
     * @access private
     * @param mixed $field
     * @param string $type (default: 'string')
     * @return void
     */
    private function addError($field, $type='string'){
        $this->errors[$field] = $type;
    }
    
    /**
     * sanatizeItem function.
     * Sanatize a single var according to $type.
     * Allows for static calling to allow simple sanatization
     * @access public
     * @static
     * @param mixed $var
     * @param mixed $type
     * @return void
     */
    public static function sanatizeItem($var, $type){
        $flags = NULL;
        switch($type){
            case 'url':
                $filter = FILTER_SANITIZE_URL;
            break;
            case 'int':
                $filter = FILTER_SANITIZE_NUMBER_INT;
            break;
            case 'float':
                $filter = FILTER_SANITIZE_NUMBER_FLOAT;
                $flags = FILTER_FLAG_ALLOW_FRACTION | FILTER_FLAG_ALLOW_THOUSAND;
            break;
            case 'email':
                $var = substr($var, 0, 254);
                $filter = FILTER_SANITIZE_EMAIL;
            break;
            case 'string':
            default:
                $filter = FILTER_SANITIZE_STRING;
                $flags = FILTER_FLAG_NO_ENCODE_QUOTES;
            break;
        }
        $output = filter_var($var, $filter, $flags);        
        return($output);
    }
    
    /**
     * validateItem function.
     * Validates a single var according to $type.
     * Allows for static calling to allow simple validation.
     * @access public
     * @static
     * @param mixed $var
     * @param mixed $type
     * @return void
     */
    public static function validateItem($var, $type){
        if(array_key_exists($type, self::$regexes)){
            $returnval =  filter_var($var, FILTER_VALIDATE_REGEXP, array("options"=> array("regexp"=>'!'.self::$regexes[$type].'!i'))) !== false;
            return($returnval);
        }
        $filter = false;
        switch($type){
            case 'email':
                $var = substr($var, 0, 254);
                $filter = FILTER_VALIDATE_EMAIL;    
            break;
            case 'int':
                $filter = FILTER_VALIDATE_INT;
            break;
            case 'boolean':
                $filter = FILTER_VALIDATE_BOOLEAN;
            break;
            case 'ip':
                $filter = FILTER_VALIDATE_IP;
            break;
            case 'url':
                $filter = FILTER_VALIDATE_URL;
            break;
        }
        return ($filter === false) ? false : filter_var($var, $filter) !== false ? true : false;
    }       



}

/*
class scanatize{


	/**
	 * sanitize function.
	 * 
	 * @access public
	 * @param mixed $string
	 * @return void
	 */
/*
	function sanitize($string) {
		$string = trim($string); 
		$string = strip_tags($string);
		$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
		$string = str_replace("\n", "", $string);
		$string = trim($string); 
		return $string;
	}

}
*/
?>