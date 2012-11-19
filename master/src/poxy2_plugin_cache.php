<?php

/**
 * Cache plugin
 *
 *  A caching system for Poxy.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_Cache implements PoxyII_Plugin {

	/**
	 * @var PoxyII
	 */
	protected $poxy;

	/**
	 * Path to cache directory.
	 * @var string
	 */
	public $dir;
	
	/**
	 * Constructor.
	 * 
	 * @param string $path Path to cache directory
	 * @throws PoxyII_Exception If the directory is not valid
	 */
	public function __construct($path) {
		if (!is_dir($path)) {
			throw new PoxyII_Exception("Invalid cache directory: $path");
		}
		$this->dir = $path;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::init()
	 */
	public function init(PoxyII $poxy) {
		
		// Save the poxy instance
		$this->poxy = $poxy;
		
		// Bind events
		$poxy->subscribeEvent('beforeExecuteRequest', array($this, 'beforeExecuteRequest'));
		$poxy->subscribeEvent('beforeProxify', array($this, 'beforeProxify'));
		$poxy->subscribeEvent('afterExecuteResponse', array($this, 'afterExecuteResponse'));
		
	}
	
	/**
	 * Return TRUE if the url is cached.
	 * 
	 * @param string $url
	 * @return boolean
	 */
	public function cacheExists($url) {
		return file_exists($this->cacheFile($url));
	}
	
	/**
	 * Return the path to the cache file of the given URL.
	 * 
	 * @param string $url
	 * @return string
	 */
	public function cacheFile($url) {
		return $this->dir . '/poxy.' . md5(serialize($this->poxy->getConfig()) . $url);
	}
	
	/**
	 * Cache reading.
	 * 
	 * @param PoxyII_HttpRequest $request
	 * @param PoxyII_HttpResponse $response
	 * @return boolean
	 */
	public function beforeExecuteRequest(PoxyII_HttpRequest $request, PoxyII_HttpResponse $response) {
		
		// Cache doesn't exists
		if (!$this->cacheExists($request->parts['complete'])) {
			return;
		}

		// Read cache
		$cache = @file_get_contents($this->cacheFile($request->parts['complete']));

		// Empty of invalid cache
		if ($cache) {
			return;
		}
		
		// Unzerialize the cached data
		$cache = @unserialize($cache);

		// 
		if ($cache instanceof PoxyII_HttpResponse) {
			
			// Save the data of the response
			$response->copy($cache);
			
			// Mark the response as cached
			$response->cached = true;
			
			// Prevent request execution
			return false;
			
		}

	}
	
	/**
	 * Disable proxiy for restored responses.
	 * 
	 * @param PoxyII_HttpResponse $response
	 * @return boolean
	 */
	public function beforeProxify(PoxyII_HttpResponse $response) {
		if ($response->cached) {
			// Prevent proxify is the response is cached
			return false;
		}
	}
	
	/**
	 * Save the response in cache.
	 * 
	 * @param PoxyII_HttpResponse $response
	 */
	public function afterExecuteResponse(PoxyII_HttpResponse $response) {
		if (!empty($response->url) && !$response->cached) {
			// Save cache
			@file_put_contents(
				$this->cacheFile($response->url['complete']),
				serialize($response)
			);
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'cache';
	}

}

?>