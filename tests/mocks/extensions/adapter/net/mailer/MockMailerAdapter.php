<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\mocks\extensions\adapter\net\mailer;

class MockMailerAdapter extends \sli_mailer\net\mailer\Adapter {

	public $call = array();

	protected $_charset = 'utf-7';

	/**
	 * Overloaded call to capture undeclared adapter methods, all of which
	 * simply set config vars to the message for use at send time
	 */
	public function __call($method, $params) {
		$this->call[] = $method;
		return parent::__call($method, $params);
	}

	public function create(array $config = array()) {
		$filters = parent::create();
		$filters[] = function($mailer, $params, $chain){
			$params['config']['view'] = array(
				'loader' => 'Simple',
				'renderer' => 'Simple'
			);
			$message = $chain->next($mailer, $params, $chain);
			return $message;
		};
		return $filters;
	}

	public function render() {
		return function($message, $params, $chain){
			return $chain->next($message, $params, $chain);
		};
	}

	/**
	 * Send
	 *
	 * @param oject $message
	 */
	public function send($message, array $options = array()) {
		return function() {
			return true;
		};
	}
}

?>