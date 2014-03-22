<?php
/**
 * Bot Blocker
 * @file
 * The file for all contants in the project
 * @api
 * @package Bot Blocker
 * Automatically trap and block bots that don't obey robots.txt rules. Program votes on a list of DNSBL sites then if found the
 * IP address is found "guilty" it is banned a nd added to the local black list.
 * @author Ron Mac Quarrie
 * @link http://www.it-werx.net
 * @license http://opensource.org/licenses/GPL-3.0
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 * See the GNU General Public License for more details.
 *
 */
namespace bot_blocker;
/** Defines the VERSION for the entire project. */
define('VERSION','0.0.1');
 
/**
 * Constant vars for entire project.
 *
 */
//Email configuration
$from     = 'bot.blocker@yourdomain.com'; // from email
$recip    = 'webmaster@yourdomain.com'; // to email
$subject  = 'Bad Bot Alert!';
//File name to write data to
$filename = 'blacklist.dat';
$message  = '';
//Do a whois lookup?
$lookup   = '';
//Number of times visitor is allowed to vist before being black listed.
$badbot   = 0;
$request   = sanitize($_SERVER['REQUEST_URI']);
$ipaddress = get_ip_address();
$useragent = sanitize($_SERVER['HTTP_USER_AGENT']);
$protocol  = sanitize($_SERVER['SERVER_PROTOCOL']);
$method    = sanitize($_SERVER['REQUEST_METHOD']);


date_default_timezone_set('America/Los_Angeles');
$date = date('l, F jS Y @ H:i:s');
$time = time();

/**
 * sanitize function.
 * 
 * @access public
 * @param mixed $string
 * @return void
 */
function sanitize($string) {
	$string = trim($string); 
	$string = strip_tags($string);
	$string = htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
	$string = str_replace("\n", "", $string);
	$string = trim($string); 
	return $string;
}


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
 * @return $data A variable by reference.
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
 * get_ip_address function.
 * 
 * @access public
 * @return $ipaddress IP address of client machine, adjusted for reverse proxy and/or cluster environments.
 */
function get_ip_address() {
  $ipaddress = &buster_static(__FUNCTION__);

  if (!isset($ipaddress)) {
    $ipaddress = $_SERVER['REMOTE_ADDR'];

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
        $ipaddress = array_pop($untrusted);
      }
    }
  }

  return $ipaddress;
}

/**
 * blacklist_query function.
 * You can find a list of DNSBL services @ http://www.dnsbl.info/dnsbl-list.php The idea being the more sites involved in the
 * proccess the more accurate the outcome.
 * @access public
 * @param mixed $ipaddress return the IP address of the visitor. 
 * @type var array $dnsbl_lookup returns an array of DNSBL sites.
 * @todo Make $dnsbl_lookup call from a config file for easier editing of the list.   
 * @return bool Depending on how many sites say that the IP address is a spammer it will pass the variable as true or false.
 */
function blacklist_query($ipaddress){ 
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
    if($ipaddress){ 
        $reverse_ip = implode(".", array_reverse(explode(".", $ipaddress))); 
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
 
if(blacklist_query($ipaddess)){ 
    die("Your on the blacklist!");
}

/**
 * variable_get function.
 * 
 * @access public
 * @param mixed $name
 * @param mixed $default (default: NULL)
 * @return void
 */
function variable_get($name, $default = NULL) {
  global $conf;

  return isset($conf[$name]) ? $conf[$name] : $default;
}
?>