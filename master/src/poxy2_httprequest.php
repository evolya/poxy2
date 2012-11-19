<?php

/**
 * DOCTODO
 *
 * @package    evolya.poxy2
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_HttpRequest {

	/**
	 * @var mixed[]
	 */
	protected $config = array(
		'income'			=> false, // boolean
		'method'			=> 'GET', // string
		'url'				=> null, // string
		'parts'				=> null, // array
		'host'				=> null, // string
		'https'				=> null, // boolean
		'script_base'		=> null, // string
		'_GET'				=> null, // array
		'_POST'				=> null, // array
		'_COOKIE'			=> null, // array
		'referer'			=> null, // string
		'user_agent'		=> 'PoxyII', // string
		'accept'			=> '*/*;q=0.1', // string
		'accept-charset'	=> 'ISO-8859-1,utf-8;q=0.7,*;q=0.3', // string
		'accept-encoding'	=> 'deflate', // string
		'accept-language'	=> 'en-US;q=0.6,en;q=0.4,*;q=0.3', // string
		'cache-control'		=> 'no-cache', // string
		'connection'		=> 'close', // string (close or keep-alive)
		'pragma'			=> 'no-cache', // string
		'server_addr'		=> null, // string
		'ttl'				=> 30 // int
	);

	/**
	 * Constructor.
	 * 
	 * Use $clone parameter to clone a request.
	 * 
	 * @param PoxyII_HttpRequest $clone
	 */
	public function __construct(PoxyII_HttpRequest $clone = null) {
		if ($clone) {
			$this->config = $clone->config;
		}
	}

	/**
	 * Configuration getter.
	 * 
	 * @param string $name
	 * @return mixed|null
	 */
	public function & __get($name) {
		return $this->config[$name];
	}

	/**
	 * Configuration setter.
	 * 
	 * @param string $name
	 * @param mixed $value
	 * @return void
	 */
	public function __set($name, $value) {
		$this->config[$name] = $value;
	}

	/**
	 * Test is a configuration property exists.
	 * 
	 * @param string $name
	 * @return boolean
	 */
	public function __isset($name) {
		return array_key_exists($name, $this->config) && $this->config[$name] !== null;
	}

	/**
	 * Change request URL.
	 * 
	 * @param string $url
	 */	
	public function setURL($url) {
		if (strpos($url, '://') === false) {
			$url = 'http://' . $url;
		}
		$this->config['url']	= $url;
		$this->config['parts']	= self::parse_url($url);
		$this->config['host']	= is_array($this->config['parts']) ? $this->config['parts']['host'] : null;
		$this->config['https']	= is_array($this->config['parts']) ? $this->config['parts']['scheme'] == 'https' : false;
	}

	/**
	 * Execute the request.
	 *
	 * @param PoxyII $poxy
	 * @param PoxyII_HttpResponse $response The response to modifiy, or null to create a new one.
	 * @return PoxyII_HttpResponse
	 * @throws PoxyII_Exception
	 */
	public function execute(PoxyII $poxy, PoxyII_HttpResponse $response = null) {

		// Check URL validity
		if (!is_array($this->config['parts'])) {
			throw new PoxyII_Exception("Invalid URL");
		}

		// Create the response, if not given
		if (!$response) {
			$response = new PoxyII_HttpResponse();
		}
		
		// Set response URL
		$response->url = $this->config['parts'];

		// Fetch for redirections
		do {

			// Redirection flag
			$retry = false;
				
			// Scheme
			$scheme = 'tcp://';
			
			// TLS (SSL)
			if ($this->config['parts']['scheme'] === 'https') {
				if (extension_loaded('openssl') && version_compare(PHP_VERSION, '4.3.0', '>=')) {
					$scheme = 'ssl://';
				}
				else {
					$response->code = 503;
					$response->status = 'TLS Unavailable';
					return $response;
				}
			}
				
			// Error vars
			$errno = -1;
			$error = "";
			
			// Debug
			if ($poxy->getConfig('debug')) {
				echo "[fsockopen(" . $scheme . $this->config['parts']['host'] . ':' . $this->config['parts']['port'] . ")]\n";
			}
			
			// Open the socket
			$socket = @fsockopen(
				// TODO Handle IPV6 cf. http://fr2.php.net/manual/fr/function.fsockopen.php
				$scheme . $this->config['parts']['host'],	// URL
				$this->config['parts']['port'],				// Port
				$errno,										// Error number
				$error,										// Error string
				$this->config['ttl']						// TTL (seconds)
			);

			// Catch socket errors
			if ($socket === false) {
				$response->code = 502;
				$response->status = "Proxy Error ($error)";
				return $response;
			}

			// Headers array
			$headers = array();
				
			// Query header
			$headers[] = $this->config['method'] . ' ' . $this->config['parts']['path'];
				
			// Append query string
			if (isset($this->config['parts']['query'])) {

				$headers[0] .= '?';

				$query = preg_split(
					'#([&;])#',
					$this->config['parts']['query'],
					-1,
					PREG_SPLIT_DELIM_CAPTURE
				);

				for (
					$i = 0, $count = count($query);
					$i < $count;
					$headers[0] .= implode('=', array_map('urlencode', array_map('urldecode', explode('=', $query[$i])))) . (isset($query[++$i]) ? $query[$i] : ''), $i++
				);

			}

			// Append last token of query string
			$headers[0] .= ' HTTP/1.0';
				
			// User-Agent
			$headers[] = 'User-Agent: ' . $this->config['user_agent'];
				
			// Accept
			$headers[] = 'Accept: ' . $this->config['accept'];
			
			// Accept-Charset
			$headers[] = 'Accept-Charset: ' . $this->config['accept-charset'];
			
			// Accept-Encoding
			$headers[] = 'Accept-Encoding: ' . $this->config['accept-encoding'];
			
			// Accept-Language
			$headers[] = 'Accept-Language: ' . $this->config['accept-language'];
			
			// Cache-Control
			$headers[] = 'Cache-Control: ' . $this->config['cache-control'];
			
			// Connection
			$headers[] = 'Connection: ' . $this->config['connection'];
			
			// Pragma
			$headers[] = 'Pragma: ' . $this->config['pragma'];
			
			// Host
			$headers[] = 'Host: ' . $this->config['host'];

			// Referer
			if (is_string($this->config['referer'])) {
				$matches = array();
				$pattern = '#^\Q' . $this->config['url'] . '?' . $poxy->getConfig('url_var_name') . '=\E([^&]+)#';
				if (preg_match($pattern, $this->config['referer'], $matches)) {
					$headers[] = 'Referer: ' . $poxy->decode_url($matches[1]);
				}
			}

			// Cookies
			if (!empty($this->config['_COOKIE'])) {

				// Cookies headers
				$cookie  = '';

				// Auth credentials
				$authCreds = array();
					
				// Fetch cookies
				foreach ($this->config['_COOKIE'] as $cookie_id => $cookie_content) {

					// Cookies ID & content
					$cookie_id      = explode(';', rawurldecode($cookie_id));
					$cookie_content = explode(';', rawurldecode($cookie_content));

					if ($cookie_id[0] === 'COOKIE') {

						// Stupid PHP can't have dots in var names
						$cookie_id[3] = str_replace('_', '.', $cookie_id[3]);
						
						if (count($cookie_id) < 4 || ($cookie_content[1] == 'secure' && $this->config['parts']['scheme'] != 'https')) {
							continue;
						}
					
						if (
								(preg_match('#\Q' . $cookie_id[3] . '\E$#i', $this->config['parts']['host']) || strtolower($cookie_id[3]) == strtolower('.' . $_url_parts['host']))
								&& preg_match('#^\Q' . $cookie_id[2] . '\E#', $this->config['parts']['path'])
						) {
							$cookie .= ($cookie != '' ? '; ' : '') . (empty($cookie_id[1]) ? '' : $cookie_id[1] . '=') . $cookie_content[0];
						}
					}
					else if ($cookie_id[0] === 'AUTH' && count($cookie_id) === 3) {

						$cookie_id[2] = str_replace('_', '.', $cookie_id[2]);
					
						if ($_url_parts['host'] . ':' . $_url_parts['port'] === $cookie_id[2]) 	{
							$_auth_creds[$cookie_id[1]] = $cookie_content[0];
						}

					}
					
				}
				
				if ($cookie != '') {
					$headers[] = "Cookie: $cookie";
				}
				
				// Cleanup
				unset($cookie);

			}
			
			// TODO Auth credential
		    /*if (isset($_url_parts['user'], $_url_parts['pass']))
		    {
		        $_basic_auth_header = base64_encode($_url_parts['user'] . ':' . $_url_parts['pass']);
		    }
		    if (!empty($_basic_auth_header))
		    {
		        $_set_cookie[] = add_cookie("AUTH;{$_basic_auth_realm};{$_url_parts['host']}:{$_url_parts['port']}", $_basic_auth_header);
		        $_request_headers .= "Authorization: Basic {$_basic_auth_header}\r\n";
		    }
		    else if (!empty($_basic_auth_realm) && isset($_auth_creds[$_basic_auth_realm]))
		    {
		        $_request_headers  .= "Authorization: Basic {$_auth_creds[$_basic_auth_realm]}\r\n";
		    }
		    else if (list($_basic_auth_realm, $_basic_auth_header) = each($_auth_creds))
		    {
		        $_request_headers .= "Authorization: Basic {$_basic_auth_header}\r\n";
		    }*/
				
			// POST method
			if ($this->config['method'] === 'POST') {
				
				// Prepare body string
				$_post_body = '';

				// File upload
				// TODO Disable upload using poxy configuration
				if (!empty($_FILES) && ini_get('file_uploads')) {
					
					// Create a data section boundary
					$_data_boundary = '----' . md5(uniqid(rand(), true));
					
					// Fetch POST data
					// TODO Use $this->config['POST'] instead
					foreach (self::set_post_vars($_POST) as $key => $value) {
						$_post_body .= "--{$_data_boundary}\r\n";
						$_post_body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
						// TODO Le urldecode() uniquement si le magic_gpc est activé ?
						$_post_body .= urldecode($value) . "\r\n";
					}
					
					// Fetch FILES data
					// TODO Use $this->config['FILES'] instead
					foreach (self::set_post_files($_FILES) as $key => $file_info) {
						
						// Prepare section
						$_post_body .= "--{$_data_boundary}\r\n";
						$_post_body .= "Content-Disposition: form-data; name=\"$key\"; filename=\"{$file_info['name']}\"\r\n";
						$_post_body .= 'Content-Type: ' . (empty($file_info['type']) ? 'application/octet-stream' : $file_info['type']) . "\r\n\r\n";
					
						// Append file contents
						// TODO Throw an exception if fopen failed
						if (is_readable($file_info['tmp_name'])) {
							$handle = fopen($file_info['tmp_name'], 'rb');
							$_post_body .= fread($handle, filesize($file_info['tmp_name']));
							fclose($handle);
						}
					
						// Close section
						$_post_body .= "\r\n";

					}
					
					// Append data to headers
					$_post_body .= "--{$_data_boundary}--\r\n";
					$headers[] = "Content-Type: multipart/form-data; boundary={$_data_boundary}\r\n";
					$headers[] = "Content-Length: " . strlen($_post_body) . "\r\n\r\n";
					$headers[] = $_post_body;
					$headers[] = "\r\n";

				}
				
				// Submit post data
				else {
					
					// Fetch POST data
					// TODO Use $this->config['POST'] instead
					foreach (self::set_post_vars($_POST) as $key => $value) {
						$_post_body .= !empty($_post_body) ? '&' : '';
						$_post_body .= $key . '=' . $value;
					}

					$headers[] = "Content-Type: application/x-www-form-urlencoded\r\n";
					$headers[] = "Content-Length: " . strlen($_post_body) . "\r\n\r\n";
					$headers[] = $_post_body;
					$headers[] = "\r\n";

				}

				// Cleanup
				unset($_post_body);

			}
			
			// GET method
			else {
				$headers[] = "\r\n";
			}

			// Debug
			if ($poxy->getConfig('debug')) {
				echo "Headers = " . print_r($headers, true) . "\n";
			}
				
			// Envoi des données au socket (R:FW)
			if (fwrite($socket, implode("\r\n", $headers)) === false) {
				// TODO Handle exceptions
				return $response;
			}
			
			// Read first segment of response data
			$line = fgets($socket, 8192);
				
			// Explode headers
			while (strspn($line, "\r\n") !== strlen($line)) {
				if (strpos($line, ':') !== false) {
					list($name, $value) = explode(':', $line, 2);
					$xname = trim(strtolower($name));
					if (!isset($response->headers[$xname])) {
						$response->headers[$xname] = array();
					}
					$response->headers[$xname][] = array($name, trim($value));
				}
				else {
					$response->headers[] = array('', trim($line));
				}
				$line = fgets($socket, 8192);
			}
			unset($line, $xname);
			
			// Catch invalid headers
			if (!isset($response->headers[0]) || substr($response->headers[0][1], 0, 7) != 'HTTP/1.') {
				$response->code = 417;
				$response->status = 'Expectation failed';
				return $response;
			}

			// Read the HTTP protocol version, return code and return status
			list($http_version, $response_code, $response_status) = explode(' ', $response->headers[0][1], 3);
			unset($response->headers[0]);
			
			// Redirection
			if (isset($response->headers['location'])) {
				if ($poxy->getConfig('debug')) {
					echo "Redirect: {$response->headers['location'][0][1]}\n";
				}
				$response->headers['location'] = array(array(
					'Location',
					$poxy->complete_url($this->config['parts'], $response->headers['location'][0][1])
				));
			}

			// Save the result status in the response object
			$response->code = intval($response_code);
			$response->status = "$response_status";

			// Header: Content-Type
			if (isset($response->headers['content-type'])) {
				list($content_type, ) = explode(';', str_replace(' ', '', strtolower($response->headers['content-type'][0][1])), 2);
				$response->content_type = $content_type;
				unset($response->headers['content-type'], $content_type);
			}
			
			// Header: Content-Length
			if (isset($response->headers['content-length'])) {
				$response->content_length = intval($response->headers['content-length'][0]);
				unset($response->headers['content-length']);
			}

			// Header: Content-Disposition
			if (isset($response->headers['content-disposition'])) {
				$response->content_disposition = $response->headers['content-disposition'][0];
				unset($response->headers['content-disposition']);
			}
				
			// Header: Set-Cookie (copy and rewrite)
			if (isset($response->headers['set-cookie']) && $poxy->getConfig('accept_cookies')) {
				
				// Sauvegarde de l'ancienne timezone
				$timezone = date_default_timezone_get();
				
				// Timezone en GMT (pour les cookies)
				date_default_timezone_set('Etc/GMT');
				
				// On parcours les cookies
				foreach ($response->headers['set-cookie'] as $cookie) 	{
					
					list(, $cookie) = $cookie;
						
					// On sépare les données des cookies
					$name = $value = $expires = $path = $domain = $secure = $httponly = $expires_time = '';
					preg_match('#^\s*([^=;,\s]*)\s*=?\s*([^;]*)#',  $cookie, $match) && list(, $name, $value) = $match;
					preg_match('#;\s*expires\s*=\s*([^;]*)#i',      $cookie, $match) && list(, $expires)      = $match;
					preg_match('#;\s*path\s*=\s*([^;,\s]*)#i',      $cookie, $match) && list(, $path)         = $match;
					preg_match('#;\s*domain\s*=\s*([^;,\s]*)#i',    $cookie, $match) && list(, $domain)       = $match;
					preg_match('#;\s*(secure\b)#i',                 $cookie, $match) && list(, $secure)       = $match;
					preg_match('#;\s*(httponly\b)#i',               $cookie, $match) && list(, $httponly)     = $match;

					// Expiration (définie par le cookie)
					$expires_time = empty($expires) ? 0 : intval(@strtotime($expires));
						
					// Expiration forcée par la configuration
					$expires = ($poxy->getConfig('session_cookies') && !empty($expires) && time() - $expires_time < 0) ? 0 : $expires;

					// Path du cookie
					$path = empty($path) ? '/' : $path;
						
					// Si le domaine n'a pas été précisé par l'hôte distant, le cookie ne sera appliqué
					// que pour l'hôte de la requête.
					if (empty($domain)) {
						$domain = $this->config['parts']['host'];
					}
					else {
						// On recupère le domaine appliqué au cookie
						$domain = '.' . strtolower(str_replace('..', '.', trim($domain, '.')));
						// On vérifie que l'hôte corresponde bien
						if ((!preg_match('#\Q' . $domain . '\E$#i', $this->config['parts']['host'])
								&& $domain != '.' . $this->config['parts']['host']) || (substr_count($domain, '.') < 2 && $domain{0} == '.'))
						{
							// Si NON, on passe cette instruction
							continue;
						}
					}
						
					// XXX Je pige pas...
					/*					if (count($_COOKIE) >= 15 && time()-$expires_time <= 0)
						$_set_cookie[] = add_cookie(current($_COOKIE), '', 1);*/

					// On regarde si le cookie doit être ignoré
					if (in_array($name, $poxy->getConfig('remove_cookies'))) {
						continue;
					}
					
					// On ajoute le cookie à la réponse
					$response->cookies[] = array(
						'name' => $name,
						'value' => $value,
						'expires' => $expires,
						'path' => $path,
						'domain' => $domain,
						'secure' => ($secure == 'secure'),
						'httponly' => ($httponly == 'httponly')
					);
						
				}

				// Restauration de la timezone
				date_default_timezone_set($timezone);

			}
			unset($response->headers['set-cookie']);
			
			// Header: P3P (rewrite)
			// @see http://www.w3.org/P3P/ Platform for Privacy Preferences Project
			if (isset($response->headers['p3p']) && preg_match('#policyref\s*=\s*[\'"]?([^\'"\s]*)[\'"]?#i', $response->headers['p3p'][0], $matches)) {
				$response->headers['p3p'][0] = str_replace($matches[0], 'policyref="' . $poxy->complete_url($this->config['parts'], $matches[1]) . '"', $response->headers['p3p'][0]);
			}
			
			// Header: Refresh (rewrite)
			if (isset($response->headers['refresh']) && preg_match('#([0-9\s]*;\s*URL\s*=)\s*(\S*)#i', $response->headers['refresh'][0], $matches)) {
				$response->headers['refresh'][0] = $matches[1] . $poxy->complete_url($this->config['parts'], $matches[2]);
			}
			
			// Header: Location (rewrite)
			if (isset($response->headers['location'])) {
				$response->headers['location'][0] = $poxy->complete_url($this->config['parts'], $response->headers['location'][0][1]);
			}
			
			// Header: URI (rewrite)
			if (isset($response->headers['uri'])) {
				$response->headers['uri'][0] = $poxy->complete_url($this->config['parts'], $response->headers['uri'][0]);
			}
			
			// Header: Content-Location (rewrite)
			if (isset($response->headers['content-location'])) {
				$response->headers['content-location'][0] = $poxy->complete_url($this->config['parts'], $response->headers['content-location'][0]);
			}
			
			// Header: Connection (remove)
			if (isset($response->headers['connection'])) {
				unset($response->headers['connection']);
			}
			
			// Header: Keep-Alive (remove)
			if (isset($response->headers['keep-alive'])) {
				unset($response->headers['keep-alive']);
			}
			
			/*
			// TODO
			if ($_response_code == 401 && isset($_response_headers['www-authenticate']) && preg_match('#basic\s+(?:realm="(.*?)")?#i', $_response_headers['www-authenticate'][0], $matches))
			{
				if (isset($_auth_creds[$matches[1]]) && !$_quit)
				{
					$_basic_auth_realm  = $matches[1];
					$_basic_auth_header = '';
					$retry = $_quit = true;
				}
				else
				{
					show_report(array('which' => 'index', 'category' => 'auth', 'realm' => $matches[1]));
				}
			}*/
				
		}
		while ($retry);
		
		// Get body TODO C'est pas un peu foireux cette méthode ?
		$body = '';
		do {
			// Silenced to avoid the "normal" warning by a faulty SSL connection
			$data = @fread($socket, 8192);
			$body .= $data;
		}
		while (isset($data{0}));
		unset($data);
			
		// Close the socket
		fclose($socket);
			
		// Save body contents
		$response->body = $body;

		// Cleanup
		unset($body);

		// Return the response
		return $response;

	}

	/**
	 * Encode a cookie string.
	 * 
	 * @param string $name
	 * @param string $value
	 * @param int $expires timestamp seconds
	 * @todo Not used
	 */
	public static function cookie2str($name, $value, $expires = 0) {
		return rawurlencode(rawurlencode($name)) . '=' . rawurlencode(rawurlencode($value)) . (empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s \G\M\T', $expires)) . '; path=/; domain=.' . $GLOBALS['_http_host'];
	}

	/**
	 * Create a request object according to actual request.
	 * 
	 * @param PoxyII $poxy
	 * @return PoxyII_HttpRequest
	 */
	public static function createFromCurrentRequest() {

		// Create the request object
		$request = new PoxyII_HttpRequest();

		// Incoming
		$request->income = true;

		// Host
		$request->host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');

		// Https
		$request->https = (isset($_ENV['HTTPS']) && $_ENV['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == 443;

		// Script URL
		$request->url =
			'http'
			. ($request->https ? 's' : '')
			. '://'
			. $request->host
			. ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '')
			. $_SERVER['PHP_SELF'];

		// URL parts
		$request->parts = self::parse_url($request->url);

		// Script base
		$request->script_base = substr($request->url, 0, strrpos($request->url, '/') + 1);

		// Methode
		$request->method = $_SERVER['REQUEST_METHOD'];

		// Get/Post/Cookies
		if (get_magic_quotes_gpc()) {
			function _stripslashes($value) {
				return is_array($value) ? array_map('_stripslashes', $value) : (is_string($value) ? stripslashes($value) : $value);
			}
			$request->_GET     = _stripslashes($_GET);
			$request->_POST    = _stripslashes($_POST);
			$request->_COOKIE  = _stripslashes($_COOKIE);
		}
		else {
			$request->_GET     = $_GET;
			$request->_POST    = $_POST;
			$request->_COOKIE  = $_COOKIE;
		}

		// Referer
		$request->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

		// Query string
		$request->query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;

		// User Agent
		$request->user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

		// Accept
		$request->accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '*/*;q=0.1';
		
		// Server address
		$request->server_addr = $_SERVER['REMOTE_ADDR'];

		// Return the request object
		return $request;

	}

	/**
	 * Parse an URL.
	 *
	 * @param string $url
	 * @return string[]|null
	 */
	public static function parse_url($url) {

		// Parse using PHP build-in function
		$temp = @parse_url($url);

		// A valid URL, lets complete it
		if (!empty($temp)) {
				
			// Scheme
			if (!isset($temp['scheme'])) {
				$temp['scheme'] = 'http';
			}
			
			// Complete
			$temp['complete'] = $url;
			
			// Extension
			$temp['port_ext'] = '';

			// Base
			$temp['base'] = $temp['scheme'] . '://' . $temp['host'];

			// Port
			if (isset($temp['port'])) {
				$temp['base'] .= $temp['port_ext'] = ':' . $temp['port'];
			}
			else {
				$temp['port'] = $temp['scheme'] === 'https' ? 443 : 80;
			}

			// Path (décomposé)
			$temp['path'] = isset($temp['path']) ? explode('/', $temp['path']) : array();

			// Fetch path tokens
			$path = array();
			foreach ($temp['path'] as $dir) {
				if ($dir === '..') 	{
					array_pop($path);
				}
				else if ($dir !== '.') {
					$new_dir = '';
					for (
							$dir = rawurldecode($dir), $new_dir = '', $i = 0, $count_i = strlen($dir);
					$i < $count_i;
					$new_dir .= strspn($dir{$i}, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$-_.+!*\'(),?:@&;=') ? $dir{$i} : rawurlencode($dir{$i}), ++$i
					);
					$path[] = $new_dir;
				}
			}

			// Path (recomposed)
			$temp['path'] = str_replace('/%7E', '/~', '/' . ltrim(implode('/', $path), '/'));

			// File name
			$temp['file'] = substr($temp['path'], strrpos($temp['path'], '/') + 1);
				
			// Directory
			$temp['dir'] = substr($temp['path'], 0, strrpos($temp['path'], '/'));
				
			// Base
			$temp['base'] .= $temp['dir'];
				
			// Previous directory
			$temp['prev_dir'] = substr_count($temp['path'], '/') > 1 ? substr($temp['base'], 0, strrpos($temp['base'], '/') + 1) : $temp['base'] . '/';

			// Return data
			return $temp;
		}

		// Return an error
		return null;
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param string[] $array
	 * @param string|null $parent_key
	 * @return string[]
	 */
	public static function set_post_vars(array $array, $parent_key = null) {
		
		$temp = array();
	
		foreach ($array as $key => $value) {
			$key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
			if (is_array($value)) {
				$temp = array_merge($temp, self::set_post_vars($value, $key));
			}
			else {
				$temp[$key] = urlencode($value);
			}
		}

		return $temp;
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param string[] $array
	 * @param string|null $parent_key
	 * @return string[]
	 */
	public static function set_post_files(array $array, $parent_key = null) {
		
		$temp = array();
	
		foreach ($array as $key => $value) {
			$key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
			if (is_array($value)) {
				$temp = array_merge_recursive($temp, self::set_post_files($value, $key));
			}
			else if (preg_match('#^([^\[\]]+)\[(name|type|tmp_name)\]#', $key, $m)) {
				$temp[str_replace($m[0], $m[1], $key)][$m[2]] = $value;
			}
		}
	
		return $temp;
	}

	/**
	 * Display the request as a string.
	 * 
	 * @return string
	 */
	public function __toString() {
		$str = get_class($this) . '[';
		foreach ($this->config as $k => &$v) {
			if (is_array($v)) {
				$str .= "\n\t$k => [";
				$c = 0;
				foreach ($v as $f => $w) {
					$str .= ($c++ > 0 ? ', ' : '') . "$f=$w";
				}
				$str .= "]";
			}
			else {
				$str .= "\n\t$k => $v";
			}
		}
		$str .= "\n]";
		return $str;
	}

}

?>