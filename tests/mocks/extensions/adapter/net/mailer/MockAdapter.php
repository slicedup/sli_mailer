<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\mocks\extensions\adapter\net\mailer;

class MockAdapter extends \sli_mailer\net\mailer\Adapter {

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