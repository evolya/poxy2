<?php

/**
 * Search plugin
 *
 *  Add search feature to browser.
 *
 * @package    evolya.poxy2.plugins
 * @author     ted <contact@evolya.fr>
 * @copyright  Copyright (c) 2013 Evolya.fr
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       http://blog.evolya.fr/labo/fr/poxy2/
 */
class PoxyII_Plugin_Search implements PoxyII_Plugin {

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
		$poxy->subscribeEvent('browser:afterUIComponentsCreated', array($this, 'afterUIComponentsCreated'));

	}

	/**
	 * Create the search buttton in browser top bar.
	 * 
	 * @param JavascriptPoxyBrowser $form
	 */
	public function afterUIComponentsCreated(JavascriptPoxyBrowser $browser) {
		
		// Get UI components
		$ui = $browser->getUIComponents();
		
		// Create button
		$ui->searchButton = $document->createElement('a');
		$ui->searchButton->className = 'poxy2-bt poxy2-bt-search';
		$ui->searchButton->style->backgroundColor = 'blue';
		
		// Create popup panel
		$ui->searchPopup = $document->createElement('div');
		$ui->searchPopup->className = 'poxy-bt-popup';
		
		// On click handler
		$ui->searchButton->onclick = function ($e) {
			$ui->searchPopup->classList->toggle('visible');
		};
		
		// Assembly
		$ui->searchButton->appendChild($ui->searchPopup);
		$ui->options->appendChild($ui->searchButton);
		
	}

	/**
	 * (non-PHPdoc)
	 * @see PoxyII_Plugin::getPluginName()
	 */
	public function getPluginName() {
		return 'search';
	}

}

?>