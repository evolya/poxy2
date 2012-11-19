<?php

// For debug purpose only
error_reporting(E_ALL | E_STRICT);

// Load libs
require_once '../src/poxy2.php';
require_once '../src/poxy2_httpresponse.php';
require_once '../src/poxy2_httprequest.php';
require_once '../src/poxy2_plugin_cache.php';
require_once '../src/poxy2_plugin_adblock.php';
require_once '../src/poxy2_plugin_noscript.php';
require_once '../src/poxy2_plugin_logger.php';
require_once '../src/poxy2_plugin_cookiemonster.php';
require_once '../src/poxy2_plugin_browser.php';
require_once '../src/poxy2_plugin_search.php';

// Init poxy
$poxy = new PoxyII();

// Cache
/*$cacheDir = dirname(__FILE__) . '/cache/';
 @mkdir($cacheDir);
$poxy->addPlugin(new PoxyII_Plugin_Cache($cacheDir));*/

// CookieMonster
$cookieMonster = new PoxyII_Plugin_CookieMonster(array(
	'*@*.evolya.fr'
));
$poxy->addPlugin($cookieMonster);

// NoScript
$noScript = new PoxyII_Plugin_NoScript(array(
	'*.evolya.fr',
	'localhost',
	'jdocumentary.com'
));
$noScript->enableWebservice = true;
$poxy->addPlugin($noScript);

// Search
$search = new PoxyII_Plugin_Search();
$poxy->addPlugin($search);

// AdBlock
/*if ($poxy->hasPlugin('cache')) {
 $cache_file = $poxy->getPluginByName('cache')->dir . '/AdBlock.list';
}
if (isset($cache_file) && is_file($cache_file)) {
$list = unserialize(file_get_contents($cache_file));
}
else {
$list = file_get_contents('http://easylist-downloads.adblockplus.org/easylist.txt');
if ($list) {
$list = explode("\n", $list);
foreach ($list as $n => &$line) {
$line = trim($line);
if (empty($line) || $line{0} == '!' || $line{0} == '[') {
unset($list[$n]);
continue;
}
}
if (isset($cache_file)) {
file_put_contents($cache_file, serialize($list));
}
}
}
if (is_array($list)) {
$poxy->addPlugin(new PoxyII_Plugin_AbBlock($list));
}*/

// Poxy settings
$poxy->setConfig(array(
	'include_form'    => false,
	'remove_scripts'  => false,
	'accept_cookies'  => true,
	'show_images'     => true,
	'show_referer'    => false,
	'rotate13'        => false,
	'base64_encode'   => false,
	'strip_meta'      => false,
	'strip_title'     => false,
	'session_cookies' => true,
	'expose_poxy'	  => false,
	'remove_cookies'  => array('PHPSESSID'),
	'user_agent'	  => 'User Agent 1.0',
	'remove_headers'  => array('access-control-allow-origin', 'x-frame-options'),
	'debug'			  => false
));

?>