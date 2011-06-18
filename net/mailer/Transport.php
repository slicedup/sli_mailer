<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\net\mailer;

abstract class Transport extends \lithium\core\Object {

	abstract public function send($params, array $options = array());
}

?>