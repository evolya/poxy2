<?php

/**
 * Poxy II
 * 
 * Poxy is a web HTTP proxy programmed in PHP meant to bypass firewalls and access otherwise inaccessible
 * resources (i.e. blocked websites). If the server this script is run on can access a resource, so can you!
 * This script takes webpages from one server and processes so that your main server is proctected/hidden.
 * Usefull for those who have or require indirect access to the web and or their server.
 * 
 * This project is an improvement of the original PHProxy project. 
 * 
 * Features: 
 *  - Free and open source 
 *  - Plug and play, just upload, setup and GO! 
 *  - Full configuration
 *  - Rewrite CSS, HTML and Javascripts
 *  - Events based architecture
 *  - Built for plugins (NoScript, CookieMonster, Logging, ...) 
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    evolya.poxy2
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    2.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 * @see			http://sourceforge.net/projects/poxy/ Original poxy project
 * @see			http://code.google.com/p/phproxyimproved/ Improved proxy project
 */
class PoxyII {
	
	/**
	 * @var string
	 */
	const VERSION = '2.0.7';
	
	/**
	 * @var mixed[]
	 */
	protected $config = array(
		'remove_scripts'  			=> false,
		'accept_cookies'  			=> true,
		'show_images'     			=> true,
		'show_referer'    			=> false,
		'rotate13'        			=> false,
		'base64_encode'   			=> true,
		'strip_meta'      			=> false,
		'strip_title'     			=> false,
		'session_cookies' 			=> true,
		'remove_cookies'			=> array(),
		'url_var_name'          	=> 'q',
		'get_form_name'         	=> '____pgfa',
		'basic_auth_var_name'   	=> '____pbavn',
		'max_file_size'         	=> -1,
		'expose_poxy'				=> true,
		'allow_hotlinking'      	=> false,
		'compress_output'       	=> false,
		'remove_headers'			=> array(),
		'proxify'					=> array('text/html', 'application/xml+xhtml', 'application/xhtml+xml', 'text/css'),
		'forbidden_remote_hosts'	=> array(),
		'allowed_hotlink_domains'	=> array(),
		'user_agent'				=> null, // String, ou null pour reprendre celle de la requête initiale
		'script_url'				=> null,
		'debug'						=> false
	);
	
	/**
	 * @var mixed[]
	 */
	protected $eventSubscriptions = array();
	
	/**
	 * @var PoxyII_Plugin[]
	 */
	protected $plugins = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		
		// HTTPS
		$https = (isset($_ENV['HTTPS']) && $_ENV['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == 443;
		
		// Host
		$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
		
		// Script URL
		$this->config['script_url'] =
			'http'
			. ($https ? 's' : '')
			. '://'
			. $host
			. ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '')
			. $_SERVER['PHP_SELF'];
		
	}

	/**
	 * @param mixed[] $config
	 * @return void
	 */
	public function setConfig(array $config) {
		$this->config = array_merge($this->config, $config);
	}
	
	/**
	 * @param string|null $key
	 * @return mixed
	 */
	public function getConfig($key = null) {
		if ($key === null) {
			return $this->config;
		}
		return $this->config[$key];
	}
	
	/**
	 * Add a plugin.
	 * 
	 * @param PoxyII_Plugin $plugin
	 * @throws PoxyII_Exception If a plugin with this name allready exists.
	 */
	public function addPlugin(PoxyII_Plugin $plugin) {
		
		// Plugin name
		$name = $plugin->getPluginName();
		
		// Plugin allready exists
		if (array_key_exists($name, $this->plugins)) {
			throw new PoxyII_Exception("Plugin '{$name}' allready exists");
		}
		
		// Add the plugin 
		$this->plugins[$name] = $plugin;
		
		// Initialize
		$plugin->init($this);
		
	}
	
	/**
	 * Check if a plugin exists.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function hasPlugin($name) {
		return array_key_exists($name, $this->plugins);
	}
	
	/**
	 * Search a plugin by his name.
	 * 
	 * @param string $name
	 * @return PoxyII_Plugin|null
	 */
	public function getPluginByName($name) {
		return $this->plugins[$name];
	}
	
	/**
	 * Search a plugin by his class name.
	 *
	 * @param string $name
	 * @return PoxyII_Plugin|null
	 */
	public function getPluginByClass($name) {
		foreach ($this->plugins[$name] as $plugin) {
			if ($plugin instanceof $name) {
				return $plugin;
			}
		}
		return null;
	}
	
	/**
	 * Add local domain to black list.
	 * 
	 * @return void
	 */
	public function ignoreLocalDomain() {
		$pattern = "/^(127\.|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[01])\.|localhost)$/i";
		if (!in_array($this->config['forbidden_remote_hosts'])) {
			$this->config['forbidden_remote_hosts'][] = $pattern;
		}
	}
	
	/**
	 * Execute a GET request.
	 * 
	 * @param string $url
	 * @return PoxyII_HttpResponse
	 */
	public function get($url) {
		
		// Create a new request
		$request = new PoxyII_HttpRequest();
		
		// Parse parameters
		$params = array();
		if (strpos($url, '?') !== false) {
			list($url, $params) = explode('?', $url, 2);
			$params = explode('&', $params);
		}
		
		// Set request URL 
		$request->setURL($url);
		
		// Set request method
		$request->method = 'GET';
		
		// Set get data
		$request->_GET = $params;
		
		// Execute the request, and retrieve the response
		$response = $request->execute($this);
		
		// Clean response
		$this->clean_response($response);
		
		// Copy host struct
		$response->host = $request->host;
		
		// Return the response
		return $response;
		
	}
	
	/**
	 * Execute a POST request.
	 * 
	 * @param string $url
	 * @param mixed[] $data
	 * @return PoxyII_HttpResponse
	 */
	public function post($url, $data) {
		
		// Create a new request
		$request = new PoxyII_HttpRequest();
		
		$request->method = 'POST';
		
		$params = array();
		// TODO Note: c'est foireux ce truc! Et puis on passe les paramètres
		// dans le _GET ! Mais en même temps c'est le code original...
		if (strpos($url, '?') !== false) {
			list($url, $params) = explode('?', $url, 2);
			$params = explode('&', $params);
		}
		
		$request->setURL($url);
		
		$request->_GET = $params;
		
		$request->_PORT = $data;
		
		$response = $request->execute($this);
		
		$this->clean_response($response);
		
		// On copie le tableau de structure de l'url
		$response->host = $request->host;
		
		return $response;
		
	}
	
	/**
	 * Relay the current request, and send the response to standard output.
	 * 
	 * @return void
	 */
	public function relay () {

		try {
			
			// Create a request object according to the current request
			$request = PoxyII_HttpRequest::createFromCurrentRequest();

			// Handle the request
			if (!$this->broadcastEvent('beforeRequestHandled', array($request, $this))) {
				return;
			}
			
			// Create the response
			$response = $this->handleRequest($request);
			
			// Event after
			$this->broadcastEvent('afterRequestHandled', array($request, $response, $this));
	
			// Proxify the response if the content type match the configuration
			if (in_array($response->content_type, $this->getConfig('proxify'))) {
				$response->proxify($this);
			}
			
			// Enable compression
			// TODO According to Accept header ?
			$gzip = $this->getConfig('compress_output') && extension_loaded('zlib') && !ini_get('zlib.output_compression');
			
			// Event before
			if (!$this->broadcastEvent('beforeExecuteResponse', array($response, $this))) {
				return;
			}
			
			// Return the response
			$response->execute($gzip);
			
			// Event after
			$this->broadcastEvent('afterExecuteResponse', array($response, $this));
			
		}
		catch (Exception $ex) {
			// TODO Handle exceptions
		}

	}
	
	/**
	 * Handle a request.
	 * 
	 * @param PoxyII_HttpRequest $request
	 * @return PoxyII_HttpResponse 
	 */
	public function handleRequest(PoxyII_HttpRequest $request) {
		
		// Debug
		if ($this->config['debug']) {
			echo "[handleRequest]\nInitial Request = $request\n";
		}

		// Create a response
		$response = new PoxyII_HttpResponse();
		
		// Add a via field (according to the configuration)
		if ($this->config['expose_poxy']) {
			$response->headers['via'] = array(array('Via', $request->server_addr . ' (PoxyII v' . self::VERSION . ')'));
		}
		
		// Copy host struct
		$response->host = $request->host;
		
		// Name of the property containing the URL
		$varName = $this->config['url_var_name'];
		
		// Redirect POST to GET
		if (isset($request->_POST[$varName]) && !isset($request->_GET[$varName])) {
			// Redirection
			$response->code = 301;
			$response->status = 'Moved Permanently';
			$response->location =
				$request->url . '?' . $varName . '=' . $this->encode_url($request->_POST[$varName]);
			return $response;
		}
		
		// Trigger a bad request error if the URL parameter wasn't sent
		if (!isset($request->_GET[$varName])) {
			$response->code = 400;
			$response->status = 'Bad Request';
			return $response;
		}

		// Create a request to the remote server
		$remote = $this->createRemoteRequest($request, $response);
		
		// Si $remote est invalide, c'est parce qu'il n'est pas possible de créer
		// une connexion vers l'hôte distant en raison de pbs de sécurités
		if (!is_object($remote)) {
			
			switch ($remote) {
				
				case 400 :
					$response->code = 400;
					$response->status = 'Invalid URL';
					break;
					
				case 406 :
					$response->code = 406;
					$response->status = 'Hotlinking Not Acceptable';
					break;
					
				case 403 :
					$response->code = 403;
					$response->status = 'Blacklisted';
					break;
					
				default :
					$response->code = 500;
					$response->status = 'Internal Server Error';
					break;
				
			}

			// Error
			if ($this->config['debug']) {
				echo "Error= $remote\n";
			}
			
			// Return the response object
			return $response;
			
		}
		
		// Remove URL parameter
		unset($remote->_GET[$varName]);
		
		// Clean the request
		$this->clean_request($remote);
		
		// Debug
		if ($this->config['debug']) {
			echo "\nRemote Request = $remote\n";
		}
		
		// Event before
		if ($this->broadcastEvent('beforeExecuteRequest', array($remote, $response))) {
		
			// Execute the request
			$remote->execute($this, $response);
		
		}
		
		// Event after
		$this->broadcastEvent('afterExecuteRequest', array($remote, $response));
		
		// Clean the response
		$this->clean_response($response);
		
		// Debug
		if ($this->config['debug']) {
			echo "Final Response= $response\n";
		}
		
		// Return the response object
		return $response;
		
	}
	
	/**
	 * Clean a request.
	 * 
	 * @param PoxyII_HttpRequest $request
	 * @return void
	 * @todo Les events ne devraient pas être propagés par cette fonction, mais par les callers
	 */
	public function clean_request(PoxyII_HttpRequest $request) {
		
		// Event before
		if (!$this->broadcastEvent('beforeCleanRequest', array($request))) {
			return;
		}
		
		// Rewrite user agent
		if (is_string($this->config['user_agent'])) {
			$request->user_agent = $this->config['user_agent'];
		}

		// Disable referer propagation
		if (!$this->config['show_referer']) {
			$request->referer = null;
		}
		
		// Disable cookies
		if (!$this->config['accept_cookies']) {
			$request->_COOKIE = array();
		}
		
		// Remove cookies according to the configuration
		else if (!empty($this->config['remove_cookies'])) {
			foreach ($this->config['remove_cookies'] as $name) {
				unset($request->_COOKIE[$name]);
			}
		}
		
		// Event after
		$this->broadcastEvent('afterCleanRequest', array($request));

	}
	
	/**
	 * Clean a response.
	 * 
	 * @param PoxyII_HttpResponse $response
	 * @return void
	 * @todo Les events ne devraient pas être propagés par cette fonction, mais par les callers
	 */
	public function clean_response(PoxyII_HttpResponse $response) {
		
		// Event before
		if (!$this->broadcastEvent('beforeCleanResponse', array($response))) {
			return;
		}
		
		// Remove headers according to the configuration
		if (!empty($this->config['remove_headers'])) {
			foreach ($this->config['remove_headers'] as $header) {
				unset($response->headers[$header]);
			}
		}
		
		// Event after
		$this->broadcastEvent('afterCleanResponse', array($response));
	}
	
	/**
	 * Create a request 
	 * 
	 * @param PoxyII_HttpRequest $request
	 * @param PoxyII_HttpResponse $response
	 * @return int|PoxyII_HttpResponse
	 */
	public function createRemoteRequest(PoxyII_HttpRequest $request, PoxyII_HttpResponse $response) {
		
		// Create the remote request, according to incoming (current) request.
		// In fact, clone it.
		$remote = new PoxyII_HttpRequest($request);
		
		// Flag as outcoming connexion
		$remote->income = false;
		
		// Remove data
		$remote->url = null;
		$remote->query_string = null;
		
		// Decode target URL
		$url = $this->decode_url($request->_GET[$this->config['url_var_name']]);

		// Store URL in the remote request
		$remote->setURL($url);
		
		// Invalid URL = Error 400
		if (!is_array($remote->parts)) {
			return 400;
		}
		
		// Hotlinking protection
		if (!$this->config['allow_hotlinking'] && isset($request->referer)) {

			// Get referer name
			$referer = trim($request->referer);
				
			// Get allower domains, and append the current domain
			$allowed = $this->config['allowed_hotlink_domains'];
			$allowed[] = $request->host;

			// Hotlinking flag
			$is_hotlinking = true;
		
			// Fetch allowed domains
			foreach ($allowed as $host) {

				// Domain is allowed, the hotlinking protection is disabled
				if (preg_match('#^https?\:\/\/(www)?\Q' . $host  . '\E(\/|\:|$)#i', $referer)) {
					$is_hotlinking = false;
					break;
				}

			}
		
			// Hotlinking = 406
			if ($is_hotlinking) {
				return 406;
			}
				
			// Cleanup
			unset($referer, $allowed, $is_hotlinking, $host);
				
		}
		
		// Check forbidden domains
		if (!empty($this->config['forbidden_remote_hosts'])) {
		
			// Fetch domains
			foreach ($this->config['forbidden_remote_hosts'] as $host) {
				// Domain is forbidden = Error 403
				if (preg_match($host, $remote->parts['host'])) {
					return 403;
				}
			}
		
			// Cleanup
			unset($host);
		
		}
		
		// Return the remote request object
		return $remote;
		
	}
	
	/**
	 * Proxify 
	 * @param string[] $base
	 * @param string $html 
	 */
	public function proxify_html($base, $html) {
		
		// Strip title
		if ($this->config['strip_title']) {
			$html = preg_replace('#(<\s*title[^>]*>)(.*?)(<\s*/title[^>]*>)#is', '$1$3', $html);
		}

		// Remove scripts
		if ($this->config['remove_scripts']) {
			$html = preg_replace('#<\s*script[^>]*?>.*?<\s*/\s*script\s*>#si', '', $html);
			$html = preg_replace("#(\bon[a-z]+)\s*=\s*(?:\"([^\"]*)\"?|'([^']*)'?|([^'\"\s>]*))?#i", '', $html);
			$html = preg_replace('#<noscript>(.*?)</noscript>#si', "$1", $html);
		}
		
		// Remove pictures
		if (!$this->config['show_images']) {
			$html = preg_replace('#<(img|image)[^>]*?>#si', '', $html);
		}
		
		// Rewrite in-page CSS
		$matches = array();
		preg_match_all('#(<\s*style[^>]*>)(.*?)(<\s*/\s*style[^>]*>)#is', $html, $matches, PREG_SET_ORDER);
		for ($i = 0, $count_i = count($matches); $i < $count_i; ++$i) {
			$html = str_replace($matches[$i][0], $matches[$i][1] . $this->proxify_css($base, $matches[$i][2]) . $matches[$i][3], $html);
		}
		
		// Setup an array with rewrited properties by tag name
		$tags = array(
			'a'          => array('href'),
			'img'        => array('src', 'longdesc'),
			'image'      => array('src', 'longdesc'),
			'body'       => array('background'),
			'base'       => array('href'),
			'frame'      => array('src', 'longdesc'),
			'iframe'     => array('src', 'longdesc'),
			'head'       => array('profile'),
			'layer'      => array('src'),
			'input'      => array('src', 'usemap'),
			'form'       => array('action'),
			'area'       => array('href'),
			'link'       => array('href', 'src', 'urn'),
			'meta'       => array('content'),
			'param'      => array('value'),
			'applet'     => array('codebase', 'code', 'object', 'archive'),
			'object'     => array('usermap', 'codebase', 'classid', 'archive', 'data'),
			'script'     => array('src'),
			'select'     => array('src'),
			'hr'         => array('src'),
			'table'      => array('background'),
			'tr'         => array('background'),
			'th'         => array('background'),
			'td'         => array('background'),
			'bgsound'    => array('src'),
			'blockquote' => array('cite'),
			'del'        => array('cite'),
			'embed'      => array('src'),
			'fig'        => array('src', 'imagemap'),
			'ilayer'     => array('src'),
			'ins'        => array('cite'),
			'note'       => array('src'),
			'overlay'    => array('src', 'imagemap'),
			'q'          => array('cite'),
			'ul'         => array('src')
		);
		
		// Special treatment for inline scripts
		$matches = array();
		preg_match_all('#<\s*script[^>]*?>(.*?)<\s*/\s*script\s*>#si', $html, $matches);
		if (isset($matches[0]) && sizeof($matches[0]) > 0) {
			for ($i = 0, $j = sizeof($matches[0]); $i < $j; $i++) {
				
				// Il s'agit d'un lien, il sera traité plus tard
				if (empty($matches[1][$i])) {
					continue;
				}
				
				if (!$this->broadcastEvent('beforeProxifyJavascriptInline', array($base, $matches[0][$i]))) {
					// Delete script
					$html = str_replace($matches[0][$i], '', $html);
				}
				
			}
		}
		
		// All tags
		$matches = array();
		preg_match_all("#<\s*([a-zA-Z\?-]+)([^>]+)>#S", $html, $matches);
		
		// Fetch tags
		for ($i = 0, $count_i = count($matches[0]); $i < $count_i; ++$i) {
			
			// Get tag attributes
			$m = array();
			if (!preg_match_all("#([a-zA-Z\-\/]+)\s*(?:=\s*(?:\"([^\">]*)\"?|'([^'>]*)'?|([^'\"\s]*)))?#S", $matches[2][$i], $m, PREG_SET_ORDER)) {
				// No attributes to rewrite
				continue;
			}

			// Copy attributes in $attrs
			$attrs = array();
			for (
				$j = 0, $count_j = count($m);
				$j < $count_j;
				$attrs[strtolower($m[$j][1])] = (isset($m[$j][4]) ? $m[$j][4] : (isset($m[$j][3]) ? $m[$j][3] : (isset($m[$j][2]) ? $m[$j][2] : false))), ++$j
			);
		
			// Rewriting flag
			$rebuild = false;
		
			// Rewrite inline styles
			if (isset($attrs['style'])) {
				$rebuild = true;
				$attrs['style'] = $this->proxify_inline_css($base, $attrs['style']);
			}
		
			// Tag name
			$tag = strtolower($matches[1][$i]);
		
			// Skip tags not rewrited
			if (!isset($tags[$tag])) {
				continue;
			}
			
			// Extra HTML code to append
			$extra_html = '';
			
			// Event before
			$e = array(&$tag, &$attrs);
			if (!$this->broadcastEvent('beforeProxifyHTMLTag', $e)) {
				continue;
			}
			else {
				$attrs = $e[1];
			}
			
			// Switch tag name
			switch ($tag) {
				
				case 'a':
					if (isset($attrs['href'])) {
						$rebuild = true;
						$attrs['href'] = $this->complete_url($base, $attrs['href']);
					}
					break;

				case 'img':
					if (isset($attrs['src'])) {
						$rebuild = true;
						$attrs['src'] = $this->complete_url($base, $attrs['src']);
					}
					if (isset($attrs['longdesc'])) {
						$rebuild = true;
						$attrs['longdesc'] = $this->complete_url($base, $attrs['longdesc']);
					}
					break;

				case 'form':
					if (isset($attrs['action'])) {
						$rebuild = true;
						if (trim($attrs['action']) === '') {
							$attrs['action'] = $_url_parts['path']; // TODO
						}
						// TODO C'est quoi ce truc ?! 
						/*if (!isset($attrs['method']) || strtolower(trim($attrs['method'])) === 'get') {
							$extra_html = '<input type="hidden" name="' . $_config['get_form_name'] . '" value="' . encode_url(complete_url($attrs['action'], false)) . '" />';
							$attrs['action'] = '';
							break;
						}*/
						$attrs['action'] = $this->complete_url($base, $attrs['action']);
					}
					break;

				case 'base':
					if (isset($attrs['href'])) {
						$rebuild = true;
						// On modifie la base
						$_base = PoxyII_HttpRequest::parse_url($attrs['href']);
						if (is_array($_base)) {
							$base = $_base;
						}
						unset($_base);
						// On réécrit l'URL
						$attrs['href'] = $this->complete_url($base, $attrs['href']);
					}
					break;

				case 'meta':
					// Strip meta
					if ($this->config['strip_meta'] && isset($attrs['name'])) {
						$html = str_replace($matches[0][$i], '', $html);
					}
					// Meta refresh
					else if (isset($attrs['http-equiv'], $attrs['content']) && preg_match('#\s*refresh\s*#i', $attrs['http-equiv'])) {
						$content = array();
						if (preg_match('#^(\s*[0-9]*\s*;\s*url=)(.*)#i', $attrs['content'], $content)) {
							$rebuild = true;
							$attrs['content'] =  $content[1] . $this->complete_url($base, trim($content[2], '"\''));
						}
						unset($content);
					}
					break;

				case 'head':
					if (isset($attrs['profile'])) {
						$rebuild = true;
						$tmp = explode(' ', $attrs['profile']);
						foreach ($tmp as &$t) {
							$t = $this->complete_url($base, $t);
						}
						$attrs['profile'] = implode(' ', $tmp);
					}
					break;

				case 'applet':
					/*
					// Note: ça n'a aucun effet non ?
					if (isset($attrs['codebase'])) {
						$rebuild = true;
						$temp = $_base;
						url_parse(complete_url(rtrim($attrs['codebase'], '/') . '/', false), $_base);
						unset($attrs['codebase']);
					} */
					if (isset($attrs['code']) && strpos($attrs['code'], '/') !== false) {
						$rebuild = true;
						$attrs['code'] = $this->complete_url($base, $attrs['code']);
					}
					if (isset($attrs['object'])) {
						$rebuild = true;
						$attrs['object'] = $this->complete_url($base, $attrs['object']);
					}
					if (isset($attrs['archive'])) {
						$rebuild = true;
						$tmp = preg_split('#\s*,\s*#', $attrs['archive']);
						foreach ($tmp as &$t) {
							$t = $this->complete_url($base, $t);
						}
						$attrs['archive'] = implode(' ', $tmp);
					}
					break;

				case 'object':
					if (isset($attrs['usemap'])) {
						$rebuild = true;
						$attrs['usemap'] = $this->complete_url($base, $attrs['usemap']);
					}
					/*
					// Note: ça n'a aucun effet non ? 
					if (isset($attrs['codebase'])) {
						$rebuild = true;
						$temp = $_base;
						url_parse(complete_url(rtrim($attrs['codebase'], '/') . '/', false), $_base);
						unset($attrs['codebase']);
					}*/
					if (isset($attrs['data'])) {
						$rebuild = true;
						$attrs['data'] = $this->complete_url($base, $attrs['data']);
					}
					if (isset($attrs['classid']) && !preg_match('#^clsid:#i', $attrs['classid'])) {
						$rebuild = true;
						$attrs['classid'] = $this->complete_url($base, $attrs['classid']);
					}
					if (isset($attrs['archive'])) {
						$rebuild = true;
						$tmp = explode(' ', $attrs['archive']);
						foreach ($tmp as &$t) {
							$t = $this->complete_url($base, $t);
						}
						$attrs['archive'] = implode(' ', $tmp);
					}
					break;

				case 'param':
					if (isset($attrs['valuetype'], $attrs['value']) && strtolower($attrs['valuetype']) == 'ref' && preg_match('#^[\w.+-]+://#', $attrs['value'])) {
						$rebuild = true;
						$attrs['value'] = $this->complete_url($base, $attrs['value']);
					}
					break;

				case 'frame':
				case 'iframe':
					if (isset($attrs['src'])) {
						$rebuild = true;
						$attrs['src'] = $this->complete_url($base, $attrs['src']);
					}
					if (isset($attrs['longdesc'])) {
						$rebuild = true;
						$attrs['longdesc'] = $this->complete_url($base, $attrs['longdesc']);
					}
					break;
					
				case 'script' :
					if (isset($attrs['src'])) {
						if (!$this->broadcastEvent('beforeProxifyJavascriptLink', array($base, &$attrs))) {
							// Delete script
							$html = str_replace($matches[0][$i], '', $html);
						}
						$rebuild = true;
						$attrs['src'] = $this->complete_url($base, $attrs['src']);
					}
					else {
						// Inline blocks are handled before
					}
					break;

				default:
					foreach ($tags[$tag] as $attr) {
						if (isset($attrs[$attr])) {
							$rebuild = true;
							$attrs[$attr] = $this->complete_url($base, $attrs[$attr]);
						}
					}
					break;

			}
			
			// Refactoring
			if ($rebuild) {
				
				$new_tag = "<$tag";
				
				foreach ($attrs as $name => $value) {
					$delim = strpos($value, '"') && !strpos($value, "'") ? "'" : '"';
					$new_tag .= ' ' . $name . ($value !== false ? '=' . $delim . $value . $delim : '');
				}
		
				$html = str_replace($matches[0][$i], $new_tag . '>' . $extra_html, $html);
			}
		}
		
		
		return $html;
		
	}
	
	/**
	 * Rewrite url() links in a CSS string.
	 *
	 * @param string[] $base
	 * @param string $css
	 * @return string
	 */
	public function proxify_inline_css($base, $css) {
	
		// Search for CSS links
		$matches = array();
		preg_match_all('#url\s*\(\s*(([^)]*(\\\))*[^)]*)(\)|$)?#i', $css, $matches, PREG_SET_ORDER);
	
		// Fetch links
		for ($i = 0, $count = count($matches); $i < $count; ++$i) {
			// Rewrite the link
			$css = str_replace(
					$matches[$i][0],
					'url(' . $this->proxify_css_url($base, $matches[$i][1]) . ')',
					$css
			);
		}
	
		// Return CSS string
		return $css;
	
	}
	
	/**
	 * Rewrite an URL to a CSS file.
	 *
	 * @param string[] $base
	 * @param string $url
	 * @return string
	 */
	public function proxify_css_url($base, $url) {
		$url   = trim($url);
		$delim = strpos($url, '"') === 0 ? '"' : (strpos($url, "'") === 0 ? "'" : '');
		return $delim . preg_replace('#([\(\),\s\'"\\\])#', '\\$1', $this->complete_url($base, trim(preg_replace('#\\\(.)#', '$1', trim($url, $delim))))) . $delim;
	}
	
	/**
	 * Rewrite @import and url() links in a CSS string.
	 *
	 * @param string[] $base
	 * @param string $css
	 * @return string
	 */
	public function proxify_css($base, $css) {
	
		// Rewrite url() links
		$css = $this->proxify_inline_css($base, $css);
	
		// Search for @import links
		$matches = array();
		preg_match_all("#@import\s*(?:\"([^\">]*)\"?|'([^'>]*)'?)([^;]*)(;|$)#i", $css, $matches, PREG_SET_ORDER);
	
		// Fetch links
		for ($i = 0, $count = count($matches); $i < $count; ++$i) {
	
			$delim = '"';
			$url   = $matches[$i][2];
	
			if (isset($matches[$i][3])) {
				$delim = "'";
				$url = $matches[$i][3];
			}
	
			// Rewrite links
			$css = str_replace(
					$matches[$i][0],
					'@import ' . $delim . $this->proxify_css_url($base, $matches[$i][1]) . $delim . (isset($matches[$i][4]) ? $matches[$i][4] : ''),
					$css
			);
			 
		}
		 
		// Return CSS string
		return $css;
		 
	}

	/**
	 * DOCTODO
	 * 
	 * @param string[] $base
	 * @param string $url
	 * @return string
	 */
	public function complete_url($base, $url) {

		// Clean the URL
		$url = trim($url);
	
		// Empty URL
		if (empty($url)) {
			return '';
		}
		
		// Position of the hash (#)
		$hash_pos = strrpos($url, '#');
		
		// Hash fragment
		$fragment = $hash_pos !== false ? substr($url, $hash_pos) : '';
		
		// Position of sheme separator
		$sep_pos  = strpos($url, '://');
		
		// Enable proxify process
		$proxify = true;
	
		if ($sep_pos === false || $sep_pos > 5) {
			
			switch ($url{0}) {
				
				case '/':
					$url = substr($url, 0, 2) === '//' ? $base['scheme'] . ':' . $url : $base['scheme'] . '://' . $base['host'] . $base['port_ext'] . $url;
					break;

				case '?':
					$url = $base['base'] . '/' . $base['file'] . $url;
					break;

				case '#':
					$proxify = false;
					break;

				case 'm':
					if (strtolower(substr($url, 0, 7)) == 'mailto:') {
						$proxify = false;
						break;
					}
					
				case 'j':
					if (strtolower(substr($url, 0, 11)) == 'javascript:') {
						if ($this->config['remove_scripts'] || !$this->broadcastEvent('beforeProxifyJavascriptHref', array($base, &$url))) {
							$url = 'javascript:;';
						}
						// TODO Broadcaster un event beforeProxifyJavascriptInline
						$proxify = false;
						break;
					}

				default:
					$url = $base['base'] . '/' . $url;
			}
		}
		
		if (!$proxify) {
			return $url;
		}
	
		return
			  $this->config['script_url']
			. '?'
			. $this->config['url_var_name']
			. '='
			. $this->encode_url($url)
			. $fragment;

	}
	
	/**
	 * Encoder une URL en fonction de la configuration.
	 *
	 * @param string $url
	 * @return string
	 */
	public function encode_url($url) {
		
		// Rotate 13
		if ($this->config['rotate13']) {
			return rawurlencode(str_rot13($url));
		}
		
		// Base 64
		else if ($this->config['base64_encode']) {
			return rawurlencode(base64_encode($url));
		}
		
		// Default
		return rawurlencode($url);
		
	}
	
	/**
	 * Décoder une URL en fonction de la configuration.
	 * 
	 * @param string $url
	 * @return string
	 */
	public function decode_url($url) {
		
		// Rotate 13
		if ($this->config['rotate13']) {
			return str_replace(array('&amp;', '&#38;'), '&', str_rot13(rawurldecode($url)));
		}
		
		// Base 64
		else if ($this->config['base64_encode']) {
			return str_replace(array('&amp;', '&#38;'), '&', base64_decode(rawurldecode($url)));
		}
		
		// Default
		return str_replace(array('&amp;', '&#38;'), '&', rawurldecode($url));
		
	}

	/**
	 * Subscribe to an event.
     *
     * When the event is triggered, we'll call all the specified callbacks.
     * It is possible to control the order of the callbacks through the
     * priority argument.
     *
     * This is for example used to make sure that the authentication plugin
     * is triggered before anything else. If it's not needed to change this
     * number, it is recommended to ommit.
	 *
	 * @param string $eventName
	 * @param callback $callback
	 * @param int $priority Priority order, 100 by default. Lower is higher.
	 * @return void
	 */
	public function subscribeEvent($eventName, $callback, $priority = 100) {
	
		// Create subscribers array
		if (!isset($this->eventSubscriptions[$eventName])) {
			$this->eventSubscriptions[$eventName] = array();
		}
	
		// Define the priorirty
		while (isset($this->eventSubscriptions[$eventName][$priority])) {
			$priority++;
		}
	
		// Register the callback
		$this->eventSubscriptions[$eventName][$priority] = $callback;
	
		// Sort by priority
		ksort($this->eventSubscriptions[$eventName]);
	
	}
	
	/**
	 * Broadcasts an event
     *
     * This method will call all subscribers. If one of the subscribers returns
     * false, the process stops.
     *
     * The arguments parameter will be sent to all subscribers
	 *
	 * @param string $eventName
	 * @param mixed[] $arguments
	 * @return boolean
	 */
	public function broadcastEvent($eventName, array $arguments = array()) {
	
		//echo "[$eventName]<br>";
		
		// Check if listeners are bound to this event
		if (isset($this->eventSubscriptions[$eventName])) {

			// Fetch listeners by priority
			foreach($this->eventSubscriptions[$eventName] as $subscriber) {
	
				// Run callback
				$result = call_user_func_array($subscriber, $arguments);
	
				// Prevent propagation
				if ($result === false) {
					return false;
				}
	
			}
	
		}
	
		// Indicate the event was broadcasted
		return true;
	
	}
	
	/**
	 * Return all listeners bound to events matched by the pattern.
	 * 
	 * @param string $pattern
	 * @return callback[]
	 */
	public function searchSubscribers($pattern) {
		$r = array();
		foreach ($this->eventSubscriptions as $eventName => $listeners) {
			if (fnmatch($pattern, $eventName)) {
				foreach ($listeners as $callback) {
					$r[] = array($eventName, $callback);
				}
			}
		}
		return $r;
	}

}

/**
 * Base exception for Poxy2.
 * 
 * @package    evolya.poxy2
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Exception extends Exception { }

/**
 * Interface for PoxyII plugins.
 * 
 * @package    evolya.poxy2
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
interface PoxyII_Plugin {
	
	/**
	 * This initializes the plugin.
     *
     * This function is called by PoxyII, after addPlugin is called.
     *
     * This method should set up the requires event subscriptions.
	 * 
	 * @param PoxyII $poxy
	 * @return void
	 */
	public function init(PoxyII $poxy);
	
	/**
	 * Returns a plugin name.
     *
     * Using this name other plugins will be able to access other plugins
     * using PoxyII::getPluginByName
	 * 
	 * @return string
	 */
	public function getPluginName();
	
}

?>