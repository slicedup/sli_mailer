<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\cases\extensions\adapter\net\mailer;

use sli_mailer\net\Mailer;

class SlicedupTest extends \lithium\test\Unit {

	public function setUp() {
		Mailer::config(array(
			'default' => array(
				'adapter' => 'Slicedup'
			)
		));
	}

	public function test() {
		$message = Mailer::create('default', array(
			'to' => 'paul@testdomain.com'
		));
		$send = $message->send(array('debug' => true));
	}
}

?>