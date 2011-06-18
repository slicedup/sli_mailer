<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\cases\net\mailer;

use sli_mailer\net\Mailer;
use sli_mailer\tests\mocks\extensions\adapter\net\mailer\MockMailerAdapter;
use ReflectionClass;

class MessageTest extends \lithium\test\Unit {

	public function setUp() {
		Mailer::config(array(
			'default' => array(
				'adapter' => new MockMailerAdapter()
			)
		));
	}

	public function testConfig() {
		$message = Mailer::create('default');

		$reflection = new ReflectionClass($message);
		$protectedConfig = $reflection->getProperty('_protectedConfig');
		$protectedConfig->setAccessible(true);
		$protected = $protectedConfig->getValue($message);

		$expected = array('name', 'classes', 'view', 'render');
		$result = array_keys($protected);
		$this->assertEqual($expected, $result);

		foreach ($expected as $key) {
			$expected = $message->get($key);
			$message->set(array($key => 'this shouldnt work'));
			$result = $message->get($key);
			$this->assertEqual($expected, $result);
			$config = $message->get();
			$this->assertFalse(array_key_exists($key, $config));
		}

		$expected = 'this should work';
		$message->set(array('another' => $expected));
		$result = $message->get('another');
		$this->assertEqual($expected, $result);

		$message = Mailer::create('default', null, array(
			'name' => 'another',
			'render' => array('as' => 'text')
		));

		$expected = 'default';
		$result = $message->get('name');
		$this->assertEqual($expected, $result);

		$expected = 'text';
		$result = $message->get('render');
		$this->assertEqual($expected, $result['as']);
	}

	public function testMailerMethods() {
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
			$message->$method($value);
			$this->assertTrue(in_array($method, $adapter->call));
		}
		$this->assertTrue($message->send());
	}

	public function testRender() {
		$message = Mailer::create('default');
		$template = 'Hello {:name}';
		$data = array('name' => 'Paul');
		$message->render(compact('template', 'data'));
		$expected = 'Hello Paul';
		$result = $message->get('text');
		$this->assertEqual($expected, $result);
		$result = $message->get('html');
		$this->assertEqual($expected, $result);

		$template = '<p>Hello {:name}</p>';
		$as = 'html';
		$message->render(compact('template', 'data', 'as'));

		$result = $message->get('text');
		$this->assertEqual($expected, $result);

		$expected = '<p>Hello Paul</p>';
		$result = $message->get('html');
		$this->assertEqual($expected, $result);
	}
}

?>