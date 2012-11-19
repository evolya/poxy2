<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<title>PoxyII - Demo</title>
	<meta name="description" lang="fr" content="">
	<meta name="author" content="blog.evolya.fr">
	<meta name="viewport" content="width=device-width">

	<link rel="stylesheet" type="text/css" href="../src/poxy2_plugin_browser.css"></link>
	<script type="text/javascript" src="../src/poxy2_plugin_browser.js"></script>
	<script type="text/javascript" src="jquery-1.8.1.min.js"></script>

	<style type="text/css">
	
	#demo-browser {
		width: 100%;
		height: 500px;
		border: 1px solid #aaa;
	}
	
	.poxy2-browser a.poxy2-bt {
		width: 15px;
		height: 16px;
		padding: 3px;
		text-indent: -999px;
		margin: 3px 2px;
	}
	
	.poxy2-browser a.poxy2-bt-refresh {
		overflow: hidden;
	}
	
	.poxy2-browser a.poxy2-bt:before {
		content: "";
		display: block;
		width: 15px;
		height: 13px;
		background: red;
		margin-top: 2px;
	}
	
	.poxy2-browser a.poxy2-bt-back:before {
		background: url(images/sprite.png) 0 0 no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-back.disabled:before {
		background: url(images/sprite.png) 0 -13px no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-next:before {
		background: url(images/sprite.png) 0 -26px no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-next.disabled:before {
		background: url(images/sprite.png) 0 -39px no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-refresh:before {
		height: 15px;
		margin-top: 1px;
		background: url(images/sprite.png) 0 -52px no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-refresh.stop:before {
		background: url(images/sprite.png) 0 -67px no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-cookiemonster:before {
		height: 15px;
		margin-top: 1px;
		background: url(images/cookie.gif) 0 0 no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-noscript:before {
		height: 16px;
		width: 16px;
		margin-top: 0;
		background: url(images/noscript.png) 0 0 no-repeat;
	}
	
	.poxy2-browser a.poxy2-bt-noscript.clear:before {
		background-image: url(images/noscript-clear.png); 
	}

	</style>
</head>
<body>

<h1>PoxyII - Demo</h1>

<?php

// Include poxy configuration
include 'config.php';

// Set the URL of the proxify page 
$poxy->setConfig(array('script_url' => 'relay.php'));

// Required library
include 'php2js.php';

// Create a new browser
$browser = new PoxyII_Plugin_Browser();

// Add the browser plugin
$poxy->addPlugin($browser);

// Change the browser URL when setup is ready 
$poxy->subscribeEvent('browser:afterBrowserInitialized', function (JavascriptPoxyBrowser $browser) {
	$browser->setURL("http://localhost/poxy2/master/test/test1.html");
});

// Debug
$poxy->subscribeEvent('browser:beforeURLChanged', function (JavascriptPoxyBrowser $browser, $url) {
	if ($console->log) $console->log("[Poxy2] setURL: " . $url);
});
$poxy->subscribeEvent('browser:onStateChanged', function (JavascriptPoxyBrowser $browser, $state) {
	if ($console->log) $console->log("[Poxy2] State changed: " . $state);
});
$poxy->subscribeEvent('browser:onPageChanged', function (JavascriptPoxyBrowser $browser, $url) {
	if ($console->log) $console->log("[Poxy2] URL changed: " . $url);
});

// Display the browser
echo $browser->render('demo-browser');

?>

</body>
</html>