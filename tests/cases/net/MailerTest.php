<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2010, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\tests\cases\net;

use sli_mailer\net\Mailer;
use sli_mailer\tests\mocks\extensions\adapter\net\mailer\MockMailerAdapter;
use sli_mailer\net\mailer\Message;

class MailerTest extends \lithium\test\Unit {

	public function setUp() {
		Mailer::config(array(
			'default' => array(
				'adapter' => new MockMailerAdapter()
			)
		));
	}

	public function testCreate(){
		$adapter = Mailer::adapter('default');
		$message = Mailer::create('default');
		$this->assertTrue($message instanceOf Message);
		$this->assertEqual($message->get('name'), 'default');

		$to = array('paul@testdomain.com');
		$render = array('template' => 'test', 'as' => 'text');
		$message = Mailer::create('default', compact('to'), compact('render'));

		$result = $message->get('to');
		$this->assertEqual($result[0]['email'], $to[0]);
		$this->assertEqual($result[0]['name'], '');
		$this->assertTrue(in_array('to', $adapter->call));

		$result = $message->get('render');
		$this->assertEqual($result['template'], $render['template']);
		$this->assertEqual($result['as'], $render['as']);
		$this->assertFalse(in_array('render', $adapter->call));
	}

	public function testAddressFormatters() {
		$message = Mailer::create('default');
		$expected = array('email' => 'paul@testdomain.com', 'name' => '');
		$result = Mailer::formatAddress($expected, array(
			'nameKey' => 'name',
			'emailKey' => 'email'
		));
		$this->assertEqual($expected, $result);
		$result = Mailer::formatAddress('paul@testdomain.com');
		$this->assertEqual($expected, $result);
		$result = Mailer::formatAddress(array_values($expected));
		$this->assertEqual($expected, $result);
		$result = Mailer::formatAddress(array('paul@testdomain.com'));
		$this->assertEqual($expected, $result);

		$expected['name'] = 'Paul';
		$result = Mailer::formatAddress($expected, array(
			'nameKey' => 'name',
			'emailKey' => 'email'
		));
		$this->assertEqual($expected, $result);
		$result = Mailer::formatAddress(array('paul@testdomain.com', 'Paul'));
		$this->assertEqual($expected, $result);
		$result = Mailer::formatAddress(array('name' => 'Paul', 'paul@testdomain.com'), array(
			'nameKey' => 'name'
		));
		$this->assertEqual($expected, $result);

		$expected = array($expected);
		$result = Mailer::formatAddressList($expected, array(
			'nameKey' => 'name',
			'emailKey' => 'email'
		));
		$this->assertEqual($expected, $result);

	}

	public function testMessageMethods() {
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
			Mailer::$method($message, $value);
			$this->assertTrue(in_array($method, $adapter->call));
			$this->assertFalse(is_null($message->get($method)));
		}
		$this->assertTrue(Mailer::send($message));
	}

	public function testFilteredMethods() {
		Mailer::applyFilter('create', function($self, $params, $chain){
			if (empty($params['options']['from'])) {
				$params['options']['from'] = array('paul@testdomain.com', 'Paul');
			}
			return $chain->next($self, $params, $chain);
		});

		Mailer::applyFilter('from', function($self, $params, $chain){
			$params['args']['from']['name'] .= ' - IT Department';
			return $chain->next($self, $params, $chain);
		});

		Mailer::applyFilter('send', function($self, $params, $chain){
			$send = $chain->next($self, $params, $chain);
			$message = $params['message'];
			$message->set(array('sent' => true));
		});

		$message = Mailer::create('default');

		$expected = 'Paul - IT Department';
		$from = $message->get('from');
		$result = $from['name'];
		$this->assertEqual($expected, $result);

		$message->from(array('paul@testdomain.com', 'Paul Webster'));
		$expected = 'Paul Webster - IT Department';
		$from = $message->get('from');
		$result = $from['name'];
		$this->assertEqual($expected, $result);

		$this->assertNull($message->get('sent'));
		$message->send();
		$this->assertTrue($message->get('sent'));
	}
}

?>