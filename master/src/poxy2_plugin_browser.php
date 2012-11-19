<?php

/**
 * Web Browser plugin
 *
 *  A simple web browser using HTML+JavaScript+CSS.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_Browser implements PoxyII_Plugin {

	/**
	 * @var PoxyII
	 */
	protected $poxy;

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::init()
	 */
	public function init(PoxyII $poxy) {
		
		// Check required library
		if (!class_exists('PHP2JS')) {
			throw new PoxyII_Exception("Library 'PHP2JS' required by 'Browser' plugin");
		}
		
		// Save poxy instance
		$this->poxy = $poxy;
		
		// Bind events
		
	}
	
	/**
	 * DOCTODO
	 * 
	 * @param string $containerID
	 * @param boolean $includeStyles
	 * @param boolean $includeScripts
	 * @return string
	 */
	public function render($containerID = 'browser', $includeStyles = false, $includeScripts = false) {
		
		// Base directory
		$base = dirname(__FILE__);
		
		// Prepare output array
		$out = array();
		
		// Includes CSS styles
		if ($includeStyles) {
			$out[] = '<style type="text/css">';
			$out[] = file_get_contents("{$base}/poxy2_plugin_browser.css");
			$out[] = '</style>';
		}
		
		// Container
		$out[] = "<div id='{$containerID}'></div>";
		
		// Init scripts
		$out[] = '<script type="text/javascript">';
		
		// Includes JavaScripts
		if ($includeScripts) {
			$out[] = file_get_contents("{$base}/poxy2_plugin_browser.js");
		}
		
		// Javascript var name
		$var = 'browser' . rand(0, 9999);
		
		// Configuration object
		$config = array(
			'script_url'			=> $this->poxy->getConfig('script_url'),
			'url_var_name'			=> $this->poxy->getConfig('url_var_name'),
			'base64_encode'			=> $this->poxy->getConfig('base64_encode'),
			'rotate13'				=> $this->poxy->getConfig('rotate13')
		);
		$config = json_encode($config);
		
		// Initialization
		$out[] = '(function () {';
		$out[] = "\n\tvar {$var} = new PoxyBrowser(document.getElementById('{$containerID}'), {$config});";

		// Set up events
		foreach ($this->poxy->searchSubscribers('browser:*') as $data) {
			$eventName = substr($data[0], 8);
			$out[] = "\n\t{$var}.bind('{$eventName}', " . PHP2JS::translate($data[1], true) . ");";
		}
		
		// Starter
		$out[] = "\n\t{$var}.setup();\n";
		
		// Close scripts
		$out[] = '})();';
		$out[] = '</script>';
		
		// Return as a string
		return implode('', $out);
		
	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'browser';
	}

}

/**
 * DOCTODO
 */
interface JavascriptPoxyBrowser {
	
	public function getLocal($id);
	public function getConfig($key);
	
	public function setURL($url);
	public function getURL($url);
	
	public function setInfo($msg, $status = 'info');
	
	public function getUIComponents();
	
	public function getDecodedURL($url);
	
	public function bind($event, Closure $callback);
	public function unbind($event, Closure $callback = null);
	public function trigger($event, array $data = array());
	
	public function destroy();
	
}

?>