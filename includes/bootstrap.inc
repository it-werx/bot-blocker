<?php
/**
 * @file
 * Functions that need to be loaded on every request.
 */
 
/**
 * Defines the VERSION for the entire project.
 */
 define('VERSION','0.0.1');
 


/**
 * buster_static function.
 * 
 * @access public
 * @param mixed $name  Globally unique name for the variable. For a function with only one static, variable, the function name
 * (e.g. via the PHP magic __FUNCTION__ constant) is recommended. For a function with multiple static variables add a
 * distinguishing suffix to the function name for each one.
 *
 * @param mixed $default_value Optional default value.
 *
 * @param mixed $reset TRUE to reset a specific named variable, or all variables if $name is NULL. Resetting every variable should
 * only be used, for example, for running unit tests with a clean environment. Should be used only though via function
 * buster_static_reset() and the return value should not be used in this case.
 *
 * @return Returns a variable by reference.
 */
function &buster_static($name, $default_value = NULL, $reset = FALSE) {
  static $data = array(), $default = array();
  // First check if dealing with a previously defined static variable.
  if (isset($data[$name]) || array_key_exists($name, $data)) {
    // Non-NULL $name and both $data[$name] and $default[$name] statics exist.
    if ($reset) {
      // Reset pre-existing static variable to its default value.
      $data[$name] = $default[$name];
    }
    return $data[$name];
  }
  // Neither $data[$name] nor $default[$name] static variables exist.
  if (isset($name)) {
    if ($reset) {
      // Reset was called before a default is set and yet a variable must be
      // returned.
      return $data;
    }
    // First call with new non-NULL $name. Initialize a new static variable.
    $default[$name] = $data[$name] = $default_value;
    return $data[$name];
  }
  // Reset all: ($name == NULL). This needs to be done one at a time so that
  // references returned by earlier invocations of buster_static() also get
  // reset.
  foreach ($default as $name => $value) {
    $data[$name] = $value;
  }
  // As the function returns a reference, the return should always be a
  // variable.
  return $data;
} 
/**
 * ip_address function.
 * 
 * @access public
 * @return IP address of client machine, adjusted for reverse proxy and/or cluster environments.
 */
function get_ip_address() {
  $ip_address = &buster_static(__FUNCTION__);

  if (!isset($ip_address)) {
    $ip_address = $_SERVER['REMOTE_ADDR'];

    if (variable_get('reverse_proxy', 0)) {
      $reverse_proxy_header = variable_get('reverse_proxy_header', 'HTTP_X_FORWARDED_FOR');
      if (!empty($_SERVER[$reverse_proxy_header])) {
        // If an array of known reverse proxy IPs is provided, then trust
        // the XFF header if request really comes from one of them.
        $reverse_proxy_addresses = variable_get('reverse_proxy_addresses', array());

        // Turn XFF header into an array.
        $forwarded = explode(',', $_SERVER[$reverse_proxy_header]);

        // Trim the forwarded IPs; they may have been delimited by commas and spaces.
        $forwarded = array_map('trim', $forwarded);

        // Tack direct client IP onto end of forwarded array.
        $forwarded[] = $ip_address;

        // Eliminate all trusted IPs.
        $untrusted = array_diff($forwarded, $reverse_proxy_addresses);

        // The right-most IP is the most specific we can trust to be true.
        $ip_address = array_pop($untrusted);
      }
    }
  }

  return $ip_address;
}

/**
 * variable_get function.
 * 
 * @access public
 * @param mixed $name
 * @param mixed $default (default: NULL)
 * @return Retrieve a variable.
 */
function variable_get($name, $default = NULL) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}


/**
 * variable_set function.
 * 
 * @access public
 * @param mixed $name
 * @param mixed $value
 * @return Set a variable global.
 */
/*function variable_set($name, $value) {
  global $conf;

  db_merge('variable')->key(array('name' => $name))->fields(array('value' => serialize($value)))->execute();

  cache_clear_all('variables', 'cache_bootstrap');

  $conf[$name] = $value;
}
*/


/**
 * variable_del function.
 * 
 * @access public
 * @param mixed $name
 * @return Delete a variable from cached variables.
 */
/*function variable_del($name) {
  global $conf;

  db_delete('variable')->condition('name', $name)->execute();
  cache_clear_all('variables', 'cache_bootstrap');

  unset($conf[$name]);
}
*/


/**
 * blacklist_query function.
 * 
 * @access public
 * @param mixed $ip
 * @return Depending on how many sites say that the IP address is a spammer it will pass the variable as true or false.
 */
function blacklist_query($ip){ 
    $listed = true; 
    $dnsbl_lookup = array( 
        "dnsbl-1.uceprotect.net", 
        "dnsbl-2.uceprotect.net", 
        "dnsbl-3.uceprotect.net", 
        "dnsbl.dronebl.org", 
        "dnsbl.sorbs.net", 
        "zen.spamhaus.org" 
    ); // Add your preferred list of DNSBL's 
    $lookups = count($dnsbl_lookup); 
    $total = 0; 
    if($ip){ 
        $reverse_ip = implode(".", array_reverse(explode(".", $ip))); 
        foreach($dnsbl_lookup as $host){ 
            if(checkdnsrr($reverse_ip.".".$host.".", "A")){ 
                $total++; 
            } 
        } 
    } 
    $percent = ($total / $lookups) * 100; 
    if($percent >= 50){ 
        return true; 
    }else{ 
        return false; 
    } 
} 
 
if(blacklist_query($_SERVER["REMOTE_ADDR"])){ 
    die("Your on the blacklist!"); 
}
?>