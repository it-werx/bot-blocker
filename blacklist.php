<?php
/**
 * Bot Blocker
 * @file
 * The file used to include into your application. ( include 'blacklist.php'; )
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
require_once 'includes/bootstrap.inc.php';

$fp = fopen($filename, 'r') or die('<p>Error opening file.</p>');
while ($line = fgets($fp)) {
	if (!preg_match("/(googlebot|slurp|msnbot|teoma|yandex)/i", $line)) {
		$u = explode(' ', $line);
		if ($u[0] == $ipaddress) ++$badbot;
	}
}
fclose($fp);

if ($badbot > 0) {
	echo '<h1>You have been banned from this domain</h1>';
	echo '<p>If you think there has been a mistake, <a href="/contact/">contact the administrator</a> via proxy server.</p>';
	// Uncomment below if your PHP version is greater then 5.4
	//http_response_code(403);
}

die();
?>