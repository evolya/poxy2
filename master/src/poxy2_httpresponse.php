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
class PoxyII_HttpResponse {

	/**
	 * Return code
	 * @var int
	 */
	public $code = 500;
	
	/**
	 * Return status
	 * @var string
	 */
	public $status = 'Internal Server Error';
	
	/**
	 * Redirection location
	 * @var string
	 */
	public $location = null;
	
	/**
	 * DOCTODO
	 * @var string[]
	 */
	public $url = array();
	
	/**
	 * Host parts
	 * @var string[]
	 */
	public $host = null;
	
	/**
	 * Content type
	 * @var string
	 */
	public $content_type = null;
	
	/**
	 * Content length
	 * @var int
	 */
	public $content_length = null;
	
	/**
	 * Content disposition
	 * @var string
	 */
	public $content_disposition = null;
	
	/**
	 * Headers
	 * @var string[][]
	 */
	public $headers = array();
	
	/**
	 * Cookies
	 * @var mixed[][]
	 */
	public $cookies = array();
	
	/**
	 * Contents
	 * @var string
	 */
	public $body = null;
	
	/**
	 * TRUE if the response has been restored from cache
	 * @var boolean
	 */
	public $cached = false;
	
	/**
	 * Clone a response object.
	 * 
	 * @param PoxyII_HttpResponse $response
	 */
	public function copy(PoxyII_HttpResponse $response) {
		$this->code = $response->code;
		$this->status = $response->status;
		$this->location = $response->location;
		$this->url = $response->url;
		$this->host = $response->host;
		$this->content_type = $response->content_type;
		$this->content_length = $response->content_length;
		$this->content_disposition = $response->content_disposition;
		$this->headers = $response->headers;
		$this->cookies = $response->cookies;
		$this->body = $response->body;
		$this->cached = false;
	}

	/**
	 * Header getter.
	 * 
	 * @param string $name
	 * @return string|null
	 */
	public function __get($name) {
		return $this->headers[$name] ? $this->headers[$name][0][1] : null;
	}
	
	/**
	 * Execute the response.
	 * 
	 * @param boolean $gzip
	 * @throws PoxyII_Exception
	 * @return void
	 */
	public function execute($gzip = false) {

		// Error is headers was allready sent
		if (headers_sent()) {
			throw new PoxyII_Exception('Headers allready sent');
		}
		
		// Response status
		header('HTTP/1.0 ' . $this->code . ' ' . $this->status, true, $this->code);

		// Redirection
		if ($this->location !== null) {
			header('location: ' . $this->location);
			return true;
		}

		// Enable GZIP compression 
		if ($gzip) {
			ob_start('ob_gzhandler');
			header('content-encoding: gzip');
		}
		else {
			unset($this->headers['content-encoding']);
		}
		
		// Update content length
		$this->content_length = strlen($this->body);
		
		// Send content length header
		//header('content-length: $this->content_length'); // XTODO ça fait bugger ça...
		
		// Send content type header
		if ($this->content_type) {
			header('content-type: ' . $this->content_type);
		}
		
		// Send content disposition header
		if ($this->content_disposition) {
			header('content-disposition: ' . $this->content_disposition);
		}

		// Clean empty headers
		$headers = array_filter($this->headers);

		// Send headers
		foreach ($headers as $name => &$values) {
			foreach ($values as &$value) {
				header($value[0] . ': ' . $value[1], false);
			}
		}
		
		// Send cookies
		foreach ($this->cookies as $cookie) {

			setcookie(
				$cookie['name'],		// string $name
				$cookie['value'],		// string $value
				$cookie['expires'],		// int $expires
				$cookie['path'],		// string $path
				$this->host,			// string $domain On force le domaine
				$cookie['secure'],		// boolean $secure
				$cookie['httponly']		// boolean $httponly
			);
		}

		// Send body contents
		if ($this->body !== null) {
			echo $this->body;
		}
		
		// If the body is empty, and a failure was detected, a "standard" message is displayed 
		else if ($this->code < 200 || $this->code >= 300) {
			$code = $this->code === 0 ? '0' : $this->code;
			$title = htmlspecialchars(self::status_name($this->code));
			$msg = htmlspecialchars($this->status);
			echo "<html><head><title>$code $title</title></head><body><h1>$code $title</h1><p>$msg</p></body>";
		}
		
	}
	
	/**
	 * Proxify the response.
	 * 
	 * @param PoxyII $poxy
	 * @return void
	 * @event beforeProxify
	 * @event afterProxify
	 */
	public function proxify(PoxyII $poxy) {

		// CSS proxify
		if ($this->content_type === 'text/css') {
			
			// Event after
			if (!$poxy->broadcastEvent('beforeProxifyCSS', array($this))) {
				return;
			}
			
			// Proxify
			$this->body = $poxy->proxify_css($this->url, $this->body);
			
			// Event after
			$poxy->broadcastEvent('afterProxifyCSS', array($this));
			
		}

		// HTML proxify
		else {
			
			// Event after
			if (!$poxy->broadcastEvent('beforeProxifyHTML', array($this))) {
				return;
			}
			
			// Proxify
			$this->body = $poxy->proxify_html($this->url, $this->body);
			
			// Event after
			$poxy->broadcastEvent('afterProxifyHTML', array($this));
			
		}

	}

	/**
	 * Return the status of a result code.
	 * 
	 * @param int $code
	 * @return string
	 */
	public static function status_name($code) {
		switch ($code) {
			case 100: return 'Continue';
			case 101: return 'Switching Protocols';
			case 200: return 'OK';
			case 201: return 'Created';
			case 202: return 'Accepted';
			case 203: return 'Non-Authoritative Information';
			case 204: return 'No Content';
			case 205: return 'Reset Content';
			case 206: return 'Partial Content';
			case 300: return 'Multiple Choices';
			case 301: return 'Moved Permanently';
			case 302: return 'Moved Temporarily';
			case 303: return 'See Other';
			case 304: return 'Not Modified';
			case 305: return 'Use Proxy';
			case 400: return 'Bad Request';
			case 401: return 'Unauthorized';
			case 402: return 'Payment Required';
			case 403: return 'Forbidden';
			case 404: return 'Not Found';
			case 405: return 'Method Not Allowed';
			case 406: return 'Not Acceptable';
			case 407: return 'Proxy Authentication Required';
			case 408: return 'Request Time-out';
			case 409: return 'Conflict';
			case 410: return 'Gone';
			case 411: return 'Length Required';
			case 412: return 'Precondition Failed';
			case 413: return 'Request Entity Too Large';
			case 414: return 'Request-URI Too Large';
			case 415: return 'Unsupported Media Type';
			case 500: return 'Internal Server Error';
			case 501: return 'Not Implemented';
			case 502: return 'Bad Gateway';
			case 503: return 'Service Unavailable';
			case 504: return 'Gateway Time-out';
			case 505: return 'HTTP Version not supported';
		}
		return 'Unknown Error';
	}

	/**
	 * Display the response as a string.
	 * 
	 * @return string
	 */
	public function __toString() {
		$str = get_class($this) . "[\n\tcode => $this->code\n\tstatus => $this->status\n\tlocation => $this->location\n\theaders => [";
		foreach ($this->headers as $k => &$v) {
			if (is_array($v)) {
				foreach ($v as $w) {
					$str .= "\n\t\t{$w[0]} => {$w[1]}";
				}
			}
			else {
				$str .= "\n\t\t{$v[0]} => {$v[1]}";
			}
		}
		$str .= "\n\t]\n\tcookies => [";
		foreach ($this->cookies as $k => &$v) {
			$str .= "\n\t\t$k => " . implode(';', $v);
		}
		$str .= "\n\t]\n]";
		return $str;
	}

}

?>