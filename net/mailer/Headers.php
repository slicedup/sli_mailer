<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\net\mailer;

class Headers extends \lithium\util\Collection {

	protected $_config = array(
		'eol' => PHP_EOL
	);

	protected static $_formats = array(
		'array' => 'sli_mailer\net\mailer\Headers::toArray',
		'string' => 'sli_mailer\net\mailer\Headers::toString'
	);

	public function toString($headers) {
		foreach ($headers as $key => $value) {
			if (!empty($value)) {
				$out[] = $key . ': ' . $value;
			}
		}
		return implode($this->_config['eol'], $out);
	}
}

?>