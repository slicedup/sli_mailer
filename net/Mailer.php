<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\net;

use lithium\core\Libraries;
use lithium\util\Validator;

/**
 * The `Mailer` class for creating & sending e-mail.
 */
class Mailer extends \lithium\core\Adaptable {

	/**
	 * Stores configurations for mailer adapters
	 *
	 * @var object Collection of mailer configurations
	 */
	protected static $_configurations = array();

	/**
	 * Libraries::locate() compatible path to adapters for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected static $_adapters = 'adapter.net.mailer';

	/**
	 * Create new mail message
	 *
	 * @param string $name configuration to be used to create message
	 * @param array $options methods & args to be applied to message on create
	 * @param array $config message configuration
	 * @return object message instance
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method affect message creation
	 */
	public static function create($name, $options = array(), $config = array()) {
		$adapter = static::adapter($name);
		$config['name'] = $name;
		$config['classes']['mailer'] = get_called_class();
		$params = compact('options', 'config');
		$filter = function($self, $params){
			extract($params);
			if (!is_object($message)) {
				$message = Libraries::instance(null, $message, $config);
			}
			if (!empty($options)) {
				array_walk($options, function($value, $key) use($message) {
					$message->$key($value);
				});
			}
			return $message;
		};
		$filters = (array) $adapter->create($config);
		return static::_filter(__FUNCTION__, $params, $filter, $filters);
	}

	/**
	 * Set text content for mail message
	 *
	 * @param object $message
	 * @param string $text
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function text($message, $text) {
		return static::_call(__FUNCTION__, $message, compact('text'));
	}

	/**
	 * Set html content for mail message
	 *
	 * @param object $message
	 * @param string $html
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function html($message, $html) {
		return static::_call(__FUNCTION__, $message, compact('html'));
	}

	/**
	 * Set subject for mail message
	 *
	 * @param object $message
	 * @param string $subject
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function subject($message, $subject) {
		return static::_call(__FUNCTION__, $message, compact('subject'));
	}

	/**
	 * Set recipient addresses for mail message
	 *
	 * @param object $message
	 * @param mixed $to string email address, array of email addresses
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function to($message, $to) {
		$to = static::formatAddressList($to);
		return static::_call(__FUNCTION__, $message, compact('to'));
	}

	/**
	 * Set CC'd recipient addresses for mail message
	 *
	 * @param object $message
	 * @param mixed $cc string email address, array of email addresses
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function cc($message, $cc) {
		$cc = static::formatAddressList($cc);
		return static::_call(__FUNCTION__, $message, compact('cc'));
	}

	/**
	 * Set BCC'd recipient addresses for mail message
	 *
	 * @param object $message
	 * @param mixed $bcc string email address, array of email addresses
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function bcc($message, $bcc) {
		$bcc = static::formatAddressList($bcc);
		return static::_call(__FUNCTION__, $message, compact('bcc'));
	}

	/**
	 * Set from address for mail message
	 *
	 * @param object $message
	 * @param mixed $from email address
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function from($message, $from) {
		$from = static::formatAddress($from);
		return static::_call(__FUNCTION__, $message, compact('from'));
	}

	/**
	 * Set reply-to address for mail message
	 *
	 * @param object $message
	 * @param mixed $replyTo email address
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function replyTo($message, $replyTo) {
		$replyTo = static::formatAddress($replyTo);
		return static::_call(__FUNCTION__, $message, compact('replyTo'));
	}

	/**
	 * Set sender address for mail message (if different that `from`)
	 *
	 * @param object $message
	 * @param mixed $sender email address
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function sender($message, $sender) {
		$sender = static::formatAddress($sender);
		return static::_call(__FUNCTION__, $message, compact('sender'));
	}

	/**
	 * Set return path for mail message
	 *
	 * @param object $message
	 * @param mixed $returnPath email address
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function returnPath($message, $returnPath) {
		$returnPath = static::formatAddress($returnPath);
		return static::_call(__FUNCTION__, $message, compact('returnPath'));
	}

	/**
	 * Set return path for mail message
	 *
	 * @param object $message
	 * @param mixed $returnPath email address
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function readReceipt($message, $readReceipt) {
		$readReceipt = static::formatAddress($readReceipt);
		return static::_call(__FUNCTION__, $message, compact('readReceipt'));
	}

	/**
	 * Set additonal custom headers for mail message
	 *
	 * @param object $message
	 * @param array $headers `headerName` => `headerValue`
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function headers($message, $headers) {
		return static::_call(__FUNCTION__, $message, compact('headers'));
	}

	/**
	 * Set file attachments for mail message
	 *
	 * @param object $message
	 * @param mixed $attachments string filename, array filenames
	 * @return
	 * @filter this method may be filtered
	 * @adapter adapters may implement this method
	 */
	public static function attachments($message, $attachments) {
		return static::_call(__FUNCTION__, $message, compact('attachments'));
	}

	/**
	 * Send mail message
	 *
	 * @param object $message
	 * @return true message sent, falso on failure
	 * @filter this method may be filtered
	 * @adapter adapters must implement this method
	 */
	public static function send($message, array $options = array()) {
		return static::_call(__FUNCTION__, $message, compact('options'));
	}

	/**
	 * Format email address into an array of name and email parts
	 *
	 * @param mixed $address
	 * @return array email => address, name => alias
	 */
	public static function formatAddress($address, array $params = array()) {
		$params += array(
			'emailKey' => 0,
			'nameKey' => 1,
		);
		extract($params);
		$formatted = array();
		$email = $name = '';
		if (is_array($address)) {
			if (isset($address[$emailKey])) {
				$email = $address[$emailKey];
			}
			if (isset($address[$nameKey])) {
				$name = $address[$nameKey];
			}
			if($email && Validator::isEmail($email)) {
				$formatted = compact('email', 'name');
			}
		} elseif(Validator::isEmail($address)) {
			$email = $address;
			$formatted = compact('email', 'name');
		}
		return $formatted;
	}

	/**
	 * Format list of email addresses
	 *
	 * @param mixed $addresses
	 * @return array of email addresses
	 */
	public static function formatAddressList($addresses, array $params = array()) {
		$formatted = array();
		if (is_array($addresses)) {
			foreach ($addresses as $address) {
				if ($valid = static::formatAddress($address, $params)) {
					$formatted[] = $valid;
				}
			}
		} elseif($valid = static::formatAddress($addresses, $params)) {
			$formatted[] = $valid;
		}
		return $formatted;
	}

	/**
	 * Call adapter method
	 *
	 * @param string $method
	 * @param object $message
	 * @param array $params
	 * @return mixed
	 */
	protected static function _call($method, $message, $args = array()) {
		$filter = static::adapter($message->get('name'))->$method($message, $args);
		$params = compact('message', 'args');
		return static::_filter($method, $params, $filter);
	}
}

?>