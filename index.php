<?php 
/*

Title: Bot Blocker
Description: Automatically trap and block bots that don't obey robots.txt rules
Project URL: http://www.it-werx.net
Author: Ron Mac Quarrie
Version: 2.0
License: GPLv2 or later

This program is free software; you can redistribute it and/or modify it under the 
terms of the GNU General Public License as published by the Free Software Foundation; 
either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; 
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
See the GNU General Public License for more details.

Credits: Includes customized/modified versions of these fine scripts:
 - Network Query Tool @ http://www.drunkwerks.com/docs/NetworkQueryTool/
 - Kloth.net Bot Trap @ http://www.kloth.net/internet/bottrap.php

*/
/**
 * Root directory of installation.
 */
define('DOC_ROOT', getcwd());
require_once DOC_ROOT . '/includes/bootstrap.inc';
/**
 * version
 * 
 * (default value: '2.0')
 * 
 * @var string
 * @access public
 */
$version = '2.0';
$from     = 'bot.blocker@yourdomain.com'; // from email
$recip    = 'webmaster@yourdomain.com'; // to email
$subject  = 'Bad Bot Alert!';
$filename = 'blackhole.dat';
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
 * whois_lookup function.
 * 
 * @access public
 * @param mixed $ipaddress
 * @return void
 */
function whois_lookup($ipaddress) {
	$msg = '';
	$server = 'whois.arin.net';
	if (!$ipaddress = gethostbyname($ipaddress)) {
		$msg .= 'Can not perform lookup without an IP address.' ."\n\n";
	} else {
		if (!$sock = fsockopen($server, 43, $num, $error, 20)) {
			unset($sock);
			$msg .= 'Timed-out connecting to $server (port 43).' ."\n\n";
		} else {
			//fputs($sock, "$ipaddress\n");
			fputs($sock, "n $ipaddress\n");
			$buffer = '';
			while (!feof($sock))
			$buffer .= fgets($sock, 10240); 
			fclose($sock);
		}
		if (eregi('RIPE.NET', $buffer)) {
			$nextServer = 'whois.ripe.net';
		} else if (eregi('whois.apnic.net', $buffer)) {
			$nextServer = 'whois.apnic.net';
		} else if (eregi('nic.ad.jp', $buffer)) {
			$nextServer = 'whois.nic.ad.jp';
			$extra = '/e'; // suppress JaPaNIC characters
		} else if (eregi('whois.registro.br', $buffer)) {
			$nextServer = 'whois.registro.br';
		}
		if (isset($nextServer)) {
			$buffer = '';
			$msg .= 'Deferred to specific whois server: '. $nextServer .'...' ."\n\n";
			if (!$sock = fsockopen($nextServer, 43, $num, $error, 10)) {
				unset($sock);
				$msg .= 'Timed-out connecting to ' . $nextServer . ' (port 43)' ."\n\n";
			} else {
				fputs($sock, $ipaddress . $extra ."\n");
				while (!feof($sock))
				$buffer .= fgets($sock, 10240);
				fclose($sock);
			}
		}
		$msg .= nl2br($buffer);
	}
	$msg = htmlspecialchars(trim(ereg_replace('#', '', strip_tags($msg))));
	$msg = preg_replace("/\\n\\n\\n\\n/i", "\n", $msg);
	$msg = preg_replace("/\\n\\n\\n/i", "\n\n", $msg);
	return $msg;
}
$whois = whois_lookup($ipaddress);

// check target | bugfix
if (!$ipaddress || !preg_match("/^[\w\d\.\-]+\.[\w\d]{1,4}$/i", $ipaddress)) { 
	exit('Error: You did not specify a valid target host or IP.');
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
			<h3>IP Address <?php echo $ipaddress; ?> has been added to the blacklist.</h3>
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
	echo '<p>If you believe this is a mistake, you may <a href="/contact/">contact the administrator</a> via proxy server.</p>';
} else { 
	die(); 
}
?>