<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\cases\net\mailer;

use sli_mailer\net\Mailer;
use sli_mailer\tests\mocks\extensions\adapter\net\mailer\MockAdapter;

class AdapterTest extends \lithium\test\Unit {

	public function setUp() {
		Mailer::config(array(
			'default' => array(
				'adapter' => new MockAdapter()
			)
		));
	}

	public function testOverloadedMailerMethods() {
		$adapter = Mailer::adapter('default');
		$message = Mailer::create('default');
		$values = array(
			'to' => 'paul@testdomain.com',
			'cc' => 'paul@testdomain.com',
			'bcc' => 'paul@testdomain.com',
			'from' => 'paul@testdomain.com',
			'sender' => 'paul@testdomain.com',
			'replyTo' => 'paul@testdomain.com',
			'readReceipt' => 'paul@testdomain.com',
			'returnPath' => 'paul@testdomain.com',
			'subject' => 'Message Subject',
			'text' => 'Message text',
			'html' => '<p>Message Html<p>',
			'attachments' => array(__FILE__),
			'headers' => array('x-user-header' => 'header value')
		);
		foreach ($values as $method => $value) {
			$this->assertTrue(is_null($message->get($method)));
			$message->$method($value);
			$this->assertFalse(is_null($message->get($method)));
		}
		$this->assertTrue($message->send());
	}

	public function testCharset() {
		$adapter = Mailer::adapter('default');

		$message = Mailer::create('default');
		$expected = 'utf-8';
		$result = $adapter->charset();
		$this->assertEqual($expected, $result);
		$result = $adapter->charset($message);
		$this->assertEqual($expected, $result);
		$result = $message->get('charset');
		$this->assertNull($result);

		$message = Mailer::create('default', null, array('charset' => 'utf-7'));
		$expected = 'utf-7';
		$result = $adapter->charset($message);
		$this->assertEqual($expected, $result);
		$result = $message->get('charset');
		$this->assertEqual($expected, $result);
		$expected = 'utf-8';
		$result = $adapter->charset();
		$this->assertEqual($expected, $result);
	}


	public function testEncode() {
		$adapter = Mailer::adapter('default');

		$string = 'This is the súbject';
		$charsets = array('', 'ISO-8859-1', 'utf-8', 'utf-16');
		foreach ($charsets as $charset) {
			$encoded = $adapter->encode($string, $charset);
			$decoded = $adapter->encode($encoded, $charset, 'B', true);
			$this->assertEqual($string, $decoded);
		}
	}
}

?>