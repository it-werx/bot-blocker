<?php 
/**
 * Bot Blocker
 * @file
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
define('DOC_ROOT', getcwd());
require_once DOC_ROOT . '/includes/bootstrap.inc.php';

$from     = 'bot.blocker@yourdomain.com'; // from email
$recip    = 'webmaster@yourdomain.com'; // to email
$subject  = 'Bad Bot Alert!';
$filename = 'blackhole.dat'; //File name to write to. Make sure that you give the WWW server permision to write to this file.
$message  = '';
//Do a whois lookup?
$lookup   = '';
$badbot   = 0;

$request   = sanitize($_SERVER['REQUEST_URI']);
//sanitize($_SERVER['REMOTE_ADDR']);
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
 * fp
 * 
 * (default value: fopen($filename, 'r') or die('<p>Error opening file.</p>'))
 * 
 * @var string
 * @access public
 */
$fp = fopen($filename, 'r') or die('<p>Error opening file.</p>');
while ($line = fgets($fp)) {
	if (!preg_match("/(googlebot|slurp|msnbot|teoma|yandex)/i", $line)) {
		$u = explode(' ', $line);
		if ($u[0] == $ipaddress) ++$badbot;
	}
}
fclose($fp);

// record hit
if ($badbot == 0) {
	$message   = $date . "\n\n";
	$message  .= 'URL Request: ' . $request . "\n";
	$message  .= 'IP Address: ' . $ipaddress . "\n";
	$message  .= 'User Agent: '  . $useragent . "\n\n";
	$message  .= 'Whois Lookup: ' . "\n\n" . $whois . "\n";

	mail($recip, $subject, $message, 'From: '. $from);

	$fp = fopen($filename, 'a+');
	fwrite($fp, $ipaddress ." - ". $method ." - ". $protocol ." - ". $date ." - ". $useragent ."\n");
	fclose($fp);

// 1st visit (warning) ?>
<!DOCTYPE html>
	<title>Welcome to Bot Blocker!</title>
	<style>
		body { color: #fff; background-color: #851507; font: 14px/1.5 Helvetica, Arial, sans-serif; }
		#blackhole { margin: 20px auto; width: 700px; }
		pre { padding: 20px; white-space: pre-line; border-radius: 10px; background-color: #b34334; }
		a { color: #fff; }
	</style>
	<body>
		<div id="blackhole">
			<h1>You have fallen into a trap!</h1>
			<p>The <a href="/robots.txt">robots.txt</a> file explicitly forbids your presence at this location. If you believe this is a mistake, you may <a href="/contact/">contact the administrator</a>.
			</p>
			<h3>IP Address <?php var_dump($ipaddress); ?> has been added to the blacklist.</h3>
		</div>
	</body>
</html><?php 
/*
* Might use this?
* <pre>WHOIS Lookup for <?php echo $ipaddress ."\n". $date ."\n\n". $whois; ?></pre>
*/
// 2nd+ visit (banned)
} else if ($badbot > 0) {
	echo '<h1>You have been banned from this domain</h1>';
	echo '<p>If you believe this is a mistake, you may <a href="/contact/">contact the administrator</a> via proxy server.<br> <?php var_dump($percent); ?></p>';
} else { 
	die(); 
}
?>