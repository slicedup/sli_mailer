<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\extensions\adapter\net\mailer\slicedup\transport;

class Mail extends \sli_mailer\net\mailer\Transport {

	public function send($params, array $options = array()) {
		extract($params);

		$to = !empty($headers['To']) ? $headers['To'] : '';
		$subject = !empty($headers['Subject']) ? $headers['Subject'] : '';
		unset($headers['To'], $headers['Subject']);
		$header = $headers->to('string');
		$send = !empty($options['send']) ? $options['send'] : null;

		if (!empty($options['debug'])) {
			return compact('to', 'subject', 'content', 'header', 'send');
		}

		if (empty($send) || ini_get('safe_mode')) {
			return mail($to, $subject, $content, $header);
		}
		return mail($to, $subject, $content, $header, $send);
	}
}

?>