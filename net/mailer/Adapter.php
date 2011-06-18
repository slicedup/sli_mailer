<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\net\mailer;

abstract class Adapter extends \lithium\core\Object {

	protected $_autoConfig = array('charset', 'transport' => 'merge');

	/**
	 * Class dependencies.
	 *	- `mailer`: mailer class - note this is set by the creating mailer
	 *	- `message`: default mail message class
	 *
	 * @var array
	 */
	protected $_classes = array(
		'mailer' => 'sli_mailer\net\Mailer',
		'message' => 'sli_mailer\net\mailer\Message'
	);

	/**
	 * Encoding
	 *
	 * @var string
	 */
	protected $_charset = '';

	/**
	 * Transport config
	 *
	 * @var array
	 */
	protected $_transport = array();

	/**
	 * To be overidden in adapters
	 *
	 * @param unknown_type $message
	 */
	abstract public function send($message, array $options = array());

	/**
	 * Overloaded call to capture undeclared adapter methods, all of which
	 * simply set config vars to the message. Overide actual methods in
	 * adapter.
	 *
	 * @param string $method
	 * @param array $params
	 * @return closure
	 */
	public function __call($method, $params) {
		return function($mailer, $params, $chain) {
			$message = $params['message'];
			return $message->set($params['args']);
		};
	}

	/**
	 * Create
	 *
	 * @param array $config
	 * @return array
	 */
	public function create(array $config = array()) {
		$message = $this->_classes['message'];
		return array(function($mailer, $params, $chain) use($message) {
			$params['message'] = $message;
			return $chain->next($mailer, $params, $chain);
		});
	}

	/**
	 * Encode a string with the given charset
	 *
	 * @param string $string
	 * @param string $charset
	 * @return string
	 */
	public function encode($string, $charset = null, $transferEncoding = 'B', $decode = false) {
		if (!$charset) {
			$charset = $this->charset();
		}
		$internalEncoding = function_exists('mb_internal_encoding');
		if ($internalEncoding) {
			$encoding = mb_internal_encoding();
			mb_internal_encoding($charset);
		}
		if ($decode) {
			$encoded = mb_decode_mimeheader($string);
		} else {
			$encoded = mb_encode_mimeheader($string, $charset, $transferEncoding);
		}
		if ($internalEncoding) {
			mb_internal_encoding($encoding);
		}
		return $encoded;
	}

	/**
	 * Obtain charset from context.
	 *
	 * If context is a message instance try to obtain from message config, then
	 * from adapter.
	 *
	 * @param mixed $context, object message, object adapter
	 * @param string $default
	 * @return string
	 */
	public function charset($context = null, $default = 'utf-8') {
		$charset = $this->_charset;
		if ($context instanceOf $this->_classes['message']) {
			if($_charset = $context->get('charset')) {
				$charset = $_charset;
			}
		}
		return $charset ?: $default;
	}
}

?>