<?php

/**
 * CookieMonster plugin
 *
 *  A cookie filtering extension.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_CookieMonster implements PoxyII_Plugin {

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
	 * Constructor.
	 * 
	 * @param string[] $whitelist A list of pattern to allow cookies.
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
		
		// Bind events 
		$poxy->subscribeEvent('afterCleanResponse', array($this, 'afterCleanResponse'));
		$poxy->subscribeEvent('browser:afterUIComponentsCreated', array($this, 'afterUIComponentsCreated'));

	}
	
	/**
	 * Create the CookieMonster buttton in browser top bar.
	 * 
	 * @param JavascriptPoxyBrowser $form
	 */
	public function afterUIComponentsCreated(JavascriptPoxyBrowser $browser) {
		
		// Get UI components
		$ui = $browser->getUIComponents();
		
		// Create button
		$ui->cookieMonster = $document->createElement('a');
		$ui->cookieMonster->className = 'poxy2-bt poxy2-bt-cookiemonster';
		
		// Create popup panel
		$ui->cookieMonsterPopup = $document->createElement('div');
		$ui->cookieMonsterPopup->className = 'poxy-bt-popup';
		
		// On click handler
		$ui->cookieMonster->onclick = function ($e) {
			$ui->cookieMonsterPopup->classList->toggle('visible');
		};
		
		// Assembly
		$ui->cookieMonster->appendChild($ui->cookieMonsterPopup);
		$ui->options->appendChild($ui->cookieMonster);
		
	}
	
	/**
	 * Clean cookies from a response. 
	 *  
	 * @param PoxyII_HttpResponse $response
	 */
	public function afterCleanResponse(PoxyII_HttpResponse $response) {
		
		// Fetch cookies
		foreach ($response->cookies as $k => &$info) {

			// This cookie is allowed
			if ($this->acceptCookie($info['name'], $info['domain'])) {
				continue;
			}
				
			// Event before
			if (!$this->poxy->broadcastEvent('beforeCookieBlocked', array(&$info, $response))) {
				continue;
			}
			
			// Save that the cookie was blocked
			$this->blocked[] = array(
				'cookie' => $info,
				'response' => $response
			);
			
			// Remove the cookie
			unset($response->cookies[$k]);

			// Event after
			$this->poxy->broadcastEvent('afterCookieBlocked', array(&$info, $response));

		}

	}
	
	/**
	 * Indicates whether a cookie should be accepted according to the white list.
	 *
	 * @param string $name
	 * @param string $domain
	 * @return boolean
	 */
	public function acceptCookie($name, $domain) {
		$name = "$name@$domain";
		foreach ($this->whitelist as $pattern) {
			if (fnmatch($pattern, $name)) {
				return true;
			}
		}
		return false;
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
	 * Returns a table containing information on blocked cookies.
	 * 
	 * @return mixed[][] 
	 */
	public function getBlockedCookies() {
		return $this->blocked;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'cookiemonster';
	}
	
}

?>