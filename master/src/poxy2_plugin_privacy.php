<?php

/**
 * Privacy plugin
 *
 *  DOCTODO
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_Privacy implements PoxyII_Plugin {

	/**
	 * @var string
	 */
	const URL = 'https://easylist-downloads.adblockplus.org/easyprivacy.txt';

	/**
	 * @var PoxyII
	 */
	protected $poxy;

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::init()
	 */
	public function init(PoxyII $poxy) {

		// Save poxy instance
		$this->poxy = $poxy;
		
		// Bind events

	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'privacy';
	}

}

?>