<?php

/**
 * AdBlock plugin
 *
 *  Content-filtering and an open-source ad blocking extension.
 *  Uses EasyList's data.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 * @see			https://easylist-downloads.adblockplus.org/easylist.txt
 */
class PoxyII_Plugin_AbBlock implements PoxyII_Plugin {
	
	/**
	 * @var string
	 */
	const URL = 'https://easylist-downloads.adblockplus.org/easylist.txt';
	
	/**
	 * @var PoxyII
	 */
	protected $poxy;
	
	/**
	 * DOCTODO
	 */
	protected $list;

	/**
	 * DOCTODO
	 */
	public function __construct(array $list) {
		$this->list = array(
			'url_params' => array()
		);
		foreach ($list as $item) {
			$c = $item{0};
			if ($c === '&') {
				$this->list['url_params'][] = $item;
				continue;
			}
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::init()
	 */
	public function init(PoxyII $poxy) {
		
		// Save poxy instance
		$this->poxy = $poxy;
		
		// Bind events
		$poxy->subscribeEvent('afterProxify', array($this, 'afterProxify'));
		
	}
	
	/**
	 * @param PoxyII_HttpResponse $response
	 * @return void
	 */
	public function afterProxify(PoxyII_HttpResponse $response) {
		
	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'adblock';
	}

}

?>