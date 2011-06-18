<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\extensions\adapter\net\mailer;

use lithium\core\Libraries;
use lithium\util\String;
use sli_mailer\net\mailer\Headers;

/**
 * The `Slicedup` Mailer adapter implements standard email message handling,
 * including html, text & multipart messages as well as file attachments.
 * It currently supports only basic transport via PHP's `mail` function.
 */
class Slicedup extends \sli_mailer\net\mailer\Adapter {

	protected $_config = array(
		'xMailer' => 'Slicedup Mailer',
		'date' => true,
		'messageId' => true
	);

	/**
	 * Mail boundary
	 *
	 * @see Basic::__construct()
	 * @var string
	 */
	protected $_boundary = '';

	/**
	 * Encoding
	 *
	 * @var string
	 */
	protected $_charset = 'utf-8';

	/**
	 * Transport config
	 *
	 * @var array
	 */
	protected $_transport = array(
		'adapter' => 'Mail'
	);

	/**
	 * Libraries::locate() compatible path to transports for this class.
	 *
	 * @var string Dot-delimited path.
	 */
	protected $_transports = 'adapter.net.mailer.slicedup.transport';

	/**
	 * Transport Instance
	 *
	 * @var \sli_mailer\net\mailer\Transport
	 */
	protected $_transportInstance;

	public function __construct(array $config = array()) {
		$this->_boundary = md5(uniqid(time()));
		$config += $this->_config;
		return parent::__construct($config);
	}

	/**
	 * Set file attachments for mail message
	 *
	 * @param object $message
	 * @param array $params
	 * @return closure
	 */
	public function attachments($message, $params) {
		$adapter = $this;
		return function($mailer, $params, $chain) use ($adapter) {
			extract($params);
			if (!empty($args['attachments'])) {
				$attached = array();
				foreach ((array) $args['attachments'] as $file) {
					if ($attach = $adapter->invokeMethod('_attach', array($file))) {
						$attached[] = $attach;
					}
				}
				$message->set(array('attachments' => $attached));
			}
		};
	}

	/**
	 * Send mail message
	 *
	 * @param object $message
	 */
	public function send($message, array $options = array()) {
		$transport = $this->_transport();
		$headers = $this->_headers($message);
		$content = $this->_compose($message);
		$charset = $this->charset($message);
		$adapter = $this;
		$send = compact('headers', 'content', 'charset', 'adapter', 'message');
		return function($mailer, $params, $chain) use ($transport, $send) {
			return $transport->send($send, $params['args']['options']);
		};
	}

	protected function _transport() {
		if (!isset($this->_transportInstance)) {
			$path = $this->_transports;
			$options = $this->_transport;
			$adapter = $options['adapter'];
			unset($options['adapter']);
			$this->_transportInstance = Libraries::instance($path, $adapter, $options);
		}
		return $this->_transportInstance;
	}

	protected function _compose($message) {
		$html = $message->get('html');
		$text = $message->get('text');
		$attachments = $message->get('attachments');
		$msg = array();

		if ($html && $text) {
			if (!empty($attachments)) {
				$msg[] = '--' . $this->_boundary;
				$msg[] = 'Content-Type: multipart/alternative; boundary="alt-' . $this->_boundary . '"';
				$msg[] = '';
			}
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/plain; charset=' . $this->_charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$text = str_replace(array("\r\n", "\r"), "\n", $text);
			$text = explode("\n", $text);
			$msg = array_merge($msg, $text);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary;
			$msg[] = 'Content-Type: text/html; charset=' . $this->_charset;
			$msg[] = 'Content-Transfer-Encoding: 7bit';
			$msg[] = '';

			$html = str_replace(array("\r\n", "\r"), "\n", $html);
			$html = explode("\n", $html);
			$msg = array_merge($msg, $html);

			$msg[] = '';
			$msg[] = '--alt-' . $this->_boundary . '--';
			$msg[] = '';

		} else {
			if(!empty($attachments)) {
				if ($html) {
					$msg[] = '';
					$msg[] = '--' . $this->_boundary;
					$msg[] = 'Content-Type: text/html; charset=' . $this->_charset;
					$msg[] = 'Content-Transfer-Encoding: 7bit';
					$msg[] = '';
				} else {
					$msg[] = '--' . $this->_boundary;
					$msg[] = 'Content-Type: text/plain; charset=' . $this->_charset;
					$msg[] = 'Content-Transfer-Encoding: 7bit';
					$msg[] = '';
				}
			}
			$content = $html ?: $text;
			$content = str_replace(array("\r\n", "\r"), "\n", $content);
			$content = explode("\n", $content);
			$msg = array_merge($msg, $content);
		}

		if(!empty($attachments)) {
			foreach ($attachments as $attachment) {
				$msg = array_merge($msg, $attachment);
			}
			$msg[] = '';
			$msg[] = '--' . $this->_boundary . '--';
			$msg[] = '';
		}
		if (!$eol = $message->get('eol')) {
			$eol = PHP_EOL;
		}
		return implode($eol, $msg);
	}

	protected function _headers($message) {
		$headers = array();
		$headerKeyMap = array(
			'to', 'cc', 'bcc', 'from', 'sender',
			'replyTo' => 'Reply-To',
			'readReceipt' => 'Disposition-Notification-To',
			'returnPath' => 'Return-Path',
			'subject', 'date',
			'messageId' => 'Message-ID',
			'xMailer' => 'X-Mailer',
			'mimeVersion' => 'Mime-Version'
		);
		$filters = array(
			'to' => '_formatAddressList',
			'cc', 'bcc',
			'from' => '_formatAddress',
			'sender', 'replyTo', 'readReceipt', 'returnPath',
			'subject' => 'encode'
		);
		if (!$eol = $message->get('eol')) {
			$eol = PHP_EOL;
		}
		$headers = new Headers(compact('eol'));

		foreach ($headerKeyMap as $key => $header) {
			if (is_int($key)) {
				$key = $header;
				$header = ucfirst($header);
			}
			$value = $message->get($key);
			if (isset($value)) {
				if (isset($filters[$key])) {
					$filter = $filters[$key];
					$filters[] = $key;
				}
				if (isset($filter) && in_array($key, $filters)) {
					$value = $this->$filter($value);
				}
				if (is_array($value)) {
					$value = implode(', ', $value);
				}
				$headers[$header] = $value;
			} elseif (isset($this->_config[$key])) {
				$headers[$header] = $this->_config[$key];
			}
		}

		if (isset($headers['Date']) && $headers['Date'] === true) {
			$headers['Date'] = date(DATE_RFC2822);
		}

		if (isset($headers['Message-ID']) && $headers['Message-ID'] === true) {
			$headers['Message-ID'] = $this->_messageId();
		}

		$html = $message->get('html', true);
		$text = $message->get('text', true);
		$headers['MIME-Version'] = '1.0';
		if ($message->get('attachments')) {
			$headers['Content-Type'] = 'multipart/mixed; boundary="' . $this->_boundary . '"';
		} elseif ($html && $text) {
			$headers['Content-Type'] = 'multipart/alternative; boundary="alt-' . $this->_boundary . '"';
		} elseif ($text) {
			$headers['Content-Type'] = 'text/plain; charset=' . $this->_charset;
		} elseif ($html) {
			$headers['Content-Type'] = 'text/html; charset=' . $this->_charset;
		}
		$headers['Content-Transfer-Encoding'] = '7bit';

		if ($userHeaders = $message->get('headers')) {
			foreach($userHeaders as $header => $value) {
				$headers[$header] = $value;
			}
		}

		return $headers;
	}

	protected function _attach($file) {
		$attachment = array();
		if (!is_array($file)) {
			$file = array('path' => $file);
		}
		if (!isset($file['mimetype'])) {
			$file['mimetype'] = 'application/octet-stream';
		}
		$encoded = $filename = null;
		if($handle = fopen($file['path'], 'rb')) {
			$data = fread($handle, filesize($file['path']));
			fclose($handle);
			if ($data !== false) {
				$encoded = chunk_split(base64_encode($data)) ;
				$filename = basename($file['path']);
			}
		}

		if ($encoded && $filename) {
			$attachment[] = '--' . $this->_boundary;
			$attachment[] = 'Content-Type: ' . $file['mimetype'];
			$attachment[] = 'Content-Transfer-Encoding: base64';
			if (empty($file['contentId'])) {
				$attachment[] = 'Content-Disposition: attachment; filename="' . $filename . '"';
			} else {
				$attachment[] = 'Content-ID: <' . $file['contentId'] . '>';
				$attachment[] = 'Content-Disposition: inline; filename="' . $filename . '"';
			}
			$attachment[] = '';
			$attachment[] = $encoded;
			$attachment[] = '';
			return $attachment;
		}
	}

	/**
	 * Format & encode email address
	 *
	 * @param mixed $address
	 * @return string email address
	 */
	protected function _formatAddress($address) {
		$formatted = $address['email'];
		if ($address['name']) {
			$name = $this->encode($address['name']);
			$formatted = "<{$name}> {$formatted}";
		}
		return $formatted;
	}

	/**
	 * Format & encode list of email addresses
	 *
	 * @param mixed $addresses
	 * @return array of email addresses
	 */
	protected function _formatAddressList($addresses) {
		$class = get_called_class();
		$formatted = array_map(array($this, '_formatAddress'), $addresses);
		return $formatted;
	}

	/**
	 * Create message id
	 *
	 * @return string
	 */
	protected function _messageId() {
		$uuid = String::uuid();
		return "<{$uuid}@{$_SERVER['HTTP_HOST']}>";
	}

	/**
	 *
	 * @param string $string
	 */
	protected function _wrap($string) {
		return $string;
	}
}

?>