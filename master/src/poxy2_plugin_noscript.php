<?php

/**
 * NoScript plugin
 *
 *  Filtering javascripts using white list.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_NoScript implements PoxyII_Plugin {

	/**
	 * @var PoxyII
	 */
	protected $poxy;

	/**
	 * @var string[]
	 */
	protected $whitelist;

	/**
	 * @var string[][]
	 */
	protected $blocked = array();
	
	/**
	 * @var boolean
	 */
	public $enableWebservice = false;
	
	/**
	 * Constructor.
	 * 
	 * @param string[] $whitelist A list of pattern to allow scripts.
	 * This function uses fnmatch() to validate the pattern.
	 */
	public function __construct(array $whitelist) {
		$this->whitelist = $whitelist;
	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::init()
	 */
	public function init(PoxyII $poxy) {
		
		// Save poxy instance
		$this->poxy = $poxy;
		
		// Bind proxify events
		$poxy->subscribeEvent('beforeProxifyJavascriptLink', array($this, 'beforeProxifyJavascriptLink'));
		$poxy->subscribeEvent('beforeProxifyJavascriptInline', array($this, 'beforeProxifyJavascriptInline'));
		$poxy->subscribeEvent('beforeProxifyJavascriptHref', array($this, 'beforeProxifyJavascriptHref'));
		$poxy->subscribeEvent('beforeProxifyHTML', array($this, 'beforeProxifyHTML'));
		
		// Bind browser events
		$poxy->subscribeEvent('browser:afterUIComponentsCreated', array($this, 'createBrowserIcon'));
		
		// Bind webservice events
		if ($this->enableWebservice) {
			$poxy->subscribeEvent('browser:onNoScriptButtonClick', array($this, 'runWebserviceRequest'));
			$poxy->subscribeEvent('beforeRequestHandled', array($this, 'handleWebserviceRequest'));
			$poxy->subscribeEvent('afterJavascriptLinkBlocked', array($this, 'saveBlocked'));
			$poxy->subscribeEvent('afterJavascriptInlineBlocked', array($this, 'saveBlocked'));
			$poxy->subscribeEvent('afterJavascriptHrefBlocked', array($this, 'saveBlocked'));
		}

	}
	
	/**
	 * Create the CookieMonster buttton in browser top bar.
	 *
	 * @param JavascriptPoxyBrowser $form
	 */
	public function createBrowserIcon(JavascriptPoxyBrowser $browser) {
	
		// Get UI components
		$ui = $browser->getUIComponents();
	
		// Create button
		$ui->noScript = $document->createElement('a');
		$ui->noScript->className = 'poxy2-bt poxy2-bt-noscript clear';
	
		// Create popup panel
		$ui->noScriptPopup = $document->createElement('div');
		$ui->noScriptPopup->className = 'poxy-bt-popup';
		
		// On click handler
		$ui->noScript->onclick = function ($e) {
			if ($e->srcElement != $ui->noScript) {
				return;
			}
			$ui->noScriptPopup->classList->toggle('visible');
			$browser->trigger("onNoScriptButtonClick", array($browser, $ui->noScript));
		};
		
		// Assembly
		$ui->noScript->appendChild($ui->noScriptPopup);
		$ui->options->appendChild($ui->noScript);
	
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param JavascriptPoxyBrowser $browser
	 */
	public function runWebserviceRequest(JavascriptPoxyBrowser $browser) {

		// This feature require jQuery
		if (!$window['jQuery']) {
			return;
		}
		
		// Set loading indicator
		$browser->ui->noScriptPopup->innerHTML = '<img src="images/wait.gif" />';

		// Query URL to webservice
		$q = $browser->getConfig("script_url") . '?noscript=' . ''; // TODO urlencode($browser->unproxifyURL($url));
		
		// Run a ajax query
		$window['jQuery']->ajax(array(
			'url'		=> $q,
			'method'	=> 'get',
			'cache'		=> false,
			'success' 	=> function ($data, $textStatus, $jqXHR) {
				
				$browser->ui->noScriptPopup->innerHTML = '';
				
				$list = $document->createElement('ul');
				
				if (is_array($data['@all-domains'])) {
					foreach ($data['@all-domains'] as $domain) {
						$li = $document->createElement("li");
						$li->innerHTML = escapeHtml($domain);
						$list->appendChild($li);
					}
				}
				
				$browser->ui->noScriptPopup->appendChild($list);
				
			},
			'error'		=>  function ($jqXHR, $textStatus, $errorThrown) {
				$li->innerHTML = 'Error';
			}
		));
		
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param PoxyII_HttpRequest $request
	 * @param PoxyII $poxy
	 */
	public function handleWebserviceRequest(PoxyII_HttpRequest $request, PoxyII $poxy) {
		
		// Check parameters
		if ($request->method == 'GET' && array_key_exists('noscript', $request->_GET)) {
			
			// Retrieve the targeted URL
			$url = $request->_GET['noscript'];
			
			// Send valid content type
			header('Content-type: application/json');
			
			// Start PHP session
			if (!$this->startSession()) {
				header('HTTP/1.0 500 Internal Error', 500, true);
				exit();
			}
			
			// No data stored
			if (!is_array($_SESSION['Poxy2_NoScript'])) {
				echo '{}';
				return false;
			}
			
			// List of last blocked scripts
			$blocked = array();
			
			// Fetch blocked domains
			foreach ($_SESSION['Poxy2_NoScript'] as $index => $data) {
				// Is expired
				if ($data[1] + 3600 < $_SERVER['REQUEST_TIME']) {
					unset($_SESSION['Poxy2_NoScript'][$index]);
					continue;
				}
				// Save host name
				$blocked[$data[0]['host']] = true;
			}
			
			// Return the list as JSON
			echo '{"@all-domains":' . json_encode(array_keys($blocked)) . '}';
			
			// Prevent any other behavior
			return false;
		}
		
	}
	
	/**
	 * DOCTODO
	 */
	public function saveBlocked() {
		// TODO Keep a reference between the blocked scripts and the current page
		if ($this->startSession()) {
			if (!isset($_SESSION['Poxy2_NoScript'])) {
				$_SESSION['Poxy2_NoScript'] = array();
			}
			foreach ($this->blocked as $data) {
				$key = md5(serialize($data));
				if (!array_key_exists($key, $_SESSION['Poxy2_NoScript'])) {
					$_SESSION['Poxy2_NoScript'][$key] = array($data, $_SERVER['REQUEST_TIME']);
				}
			}
		}
	}
	
	/**
	 * Indicates whether a script should be blocked.
	 * 
	 * @param string $domain
	 * @param string $path
	 * @return boolean
	 */
	public function blockScript($domain, $path = '') {
		foreach ($this->whitelist as $pattern) {
			if (fnmatch($pattern, $domain)) {
				return false;
			}
		}
		return true;
	}
	
	/**
	 * @param PoxyII_HttpResponse $response
	 */
	public function beforeProxifyHTML(PoxyII_HttpResponse $response) {
		if ($this->blockScript($response->host)) {
			// TODO Broadcast individual events, and store in $this->blocked 
			$response->body = preg_replace('#<\s*script[^>]*? >.*?<\s*/\s*script\s*>#si', '', $response->body);
			$response->body = preg_replace("#(\bon[a-z]+)\s*=\s*(?:\"([^\"]*)\"?|'([^']*)'?|([^'\"\s>]*))?#i", '', $response->body);
		}
	}
	
	/**
	 * This function is called before the proxification a link to a JavaScript file
	 * outside the page, ie when <script src=""></script> is encountered.
	 * 
	 * @param string[] $base
	 * @param string[] $attr
	 * @return boolean
	 */
	public function beforeProxifyJavascriptLink(array $base, array &$attr) {
		
		// Parse the URL
		$url = parse_url($attr['src']);
		
		// If we can not parse the URL, the link is removed
		if (!is_array($url)) {
			return false;
		}
		
		// Determining the host or in the URL is from the base
		$host = isset($url['host']) ? $url['host'] : $base['host'];
		
		// Do not block if the script is allowed to pass
		if (!$this->blockScript($host, $url['path'])) {
			return;
		}

		// We send an event before blocking, to allow the possibility of avoiding
		if (!$this->poxy->broadcastEvent('beforeJavascriptLinkBlocked', array($base, &$attr))) {
			return;
		}
		
		// Saves a script has been blocked
		$this->blocked[] = array(
			'host' => $host,
			'attr' => $attr,
			'url' => $url
		);
		
		// Event after
		$this->poxy->broadcastEvent('afterJavascriptLinkBlocked', array($base, &$attr));
		
		// Removes the tag by returning false for this event
		return false;

	}
	
	/**
	 * This function is called when JavaScript code line is detected in the page.
	 * 
	 * @param string[] $base
	 * @param string $html
	 */
	public function beforeProxifyJavascriptInline(array $base, $html) {
		
		// Do not block if the script is allowed to pass
		if (!$this->blockScript($base['host'])) {
			return;
		}
		
		// Event before
		if (!$this->poxy->broadcastEvent('beforeJavascriptInlineBlocked', array($base, &$html))) {
			return;
		}
		
		// Saves a script has been blocked
		$this->blocked[] = array(
			'host' => $base['host']
		);
		
		// Event after
		$this->poxy->broadcastEvent('afterJavascriptInlineBlocked', array($base, &$html));

		// Removes the tag by returning false for this event
		return false;
		
	}
	
	/**
	 * TOCTODO
	 * 
	 * @param string[] $base
	 * @param string &$url
	 */
	public function beforeProxifyJavascriptHref(array $base, &$url) {
		
		// Do not block if the script is allowed to pass
		if (!$this->blockScript($base['host'])) {
			return;
		}
		
		// Event before
		if (!$this->poxy->broadcastEvent('beforeJavascriptHrefBlocked', array($base, &$html))) {
			return;
		}
		
		// Saves a script has been blocked
		$this->blocked[] = array(
				'host' => $base['host']
		);
		
		// Event after
		$this->poxy->broadcastEvent('afterJavascriptHrefBlocked', array($base, &$html));
		
		// Removes the tag by returning false for this event
		return false;
		
	}
	
	/**
	 * DOCTODO
	 */
	public function startSession() {
		if (session_id() == '') {
			return session_start();
		}
		return true;
	}
	
	/**
	 * Returns the list of pattern that make up the white list.
	 *
	 * @return string[]
	 */
	public function getWhitelist() {
		return $this->whitelist;
	}
	
	/**
	 * Returns an array containing information about blocked scripts.
	 *
	 * @return mixed[][]
	 */
	public function getBlockedScripts() {
		return $this->blocked;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'noscript';
	}

}

?>