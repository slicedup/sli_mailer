<?php
/**
 * Slicedup: a fancy tag line here
 *
 * @copyright	Copyright 2011, Paul Webster / Slicedup (http://slicedup.org)
 * @license 	http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace sli_mailer\net\mailer;

use lithium\core\ConfigException;

/**
 * The `Message` class
 *
 */
class Message extends \lithium\core\Object {

	protected $_autoConfig = array(
		'name',
		'classes' => 'merge',
		'view' => 'merge',
		'render' => 'merge'
	);

	/**
	 * Map of auto config class vars to the relative class property.
	 * Properties are filtered from config array, and only read access is
	 * provided via the config getter
	 *
	 * @see sli_mailer\net\mailer\Message::_init()
	 * @see sli_mailer\net\mailer\Message::set()
	 * @see sli_mailer\net\mailer\Message::get()
	 * @var array
	 */
	protected $_protectedConfig = array();

	/**
	 * Adapter configuration name
	 *
	 * @var string
	 */
	protected $_name = '';

	/**
	 * Class dependencies.
	 *	- `mailer`: mailer class - note this is set by the creating mailer
	 *	- `view`: view class to use for rendering
	 *
	 * @var array
	 */
	protected $_classes = array(
		'mailer' => 'sli_mailer\net\Mailer',
		'view' => 'lithium\template\View'
	);


	/**
	 * View configuration for view class rendering
	 *
	 * @var array
	 */
	protected $_view = array(
		'paths'    => array(
			'template' => '{:library}/views/email/{:template}.{:type}.php',
			'layout'   => '{:library}/views/email/layouts/{:layout}.{:type}.php',
			'element'  => '{:library}/views/email/elements/{:template}.{:type}.php',
		)
	);

	/**
	 * View instance
	 *
	 * @var \lithium\template\View
	 */
	protected $_viewInstance;

	/**
	 * Render options
	 *
	 * @var array
	 */
	protected $_render = array(
		'as' => 'both',
		'template' => null,
		'layout' => null,
		'data' => array()
	);

	protected function _init() {
		parent::_init();
		foreach ($this->_autoConfig as $key => $flag) {
			if ($flag === 'merge') {
				$property = '_' . $key;
			} else {
				$key = $flag;
				$property = '_' . $flag;
			}
			unset($this->_config[$key]);
			$this->_protectedConfig[$key] = $property;
		}
	}

	public function __construct(array $config = array()) {
		parent::__construct($config);
		$name = isset($config['name']) ? $config['name'] : false;
		$mailer = $this->_classes['mailer'];
		if (!$name || !$mailer::adapter($name)) {
			$class = get_class($this);
			$message = "`$class` must have a valid Mailer config name passed";
			throw new ConfigException($message);
		}
	}

	/**
	 * Overloaded to pass through mailer calls
	 *
	 * @param string $method
	 * @param array $params
	 */
	public function __call($method, $params) {
		if (!($mailer = $this->_classes['mailer']) || !method_exists($mailer, $method)) {
			$message = "Unhandled mailer call `{$method}`.";
			throw new \BadMethodCallException($message);
		}
		array_unshift($params, $this);
		return $mailer::invokeMethod($method, $params);
	}

	/**
	 * Config setter
	 *
	 * @param array $config
	 */
	public function set(array $config) {
		$config = array_diff_key($config, $this->_protectedConfig);
		return $this->_config = $config + $this->_config;
	}

	/**
	 * Config getter
	 *
	 * @param string $config config key to look up
	 * @param boolean $set true only check if config key is set
	 */
	public function get($config = null, $set = false) {
		if ($config) {
			if (isset($this->_protectedConfig[$config])) {
				$property = $this->_protectedConfig[$config];
				return $this->$property;
			}
			if(array_key_exists($config, $this->_config)) {
				return $set ? true : $this->_config[$config];
			}
		} else {
			return $this->_config;
		}
	}

	/**
	 * Render mail message from view templates
	 *
	 * @param array $render render config
	 * @param array $data data for templates
	 */
	public function render($render = array(), array $data = array()) {
		if (!is_array($render)) {
			$render = array('template' => $render);
		}
		if ($data) {
			$render['data'] += $data;
		}
		$render += $this->_render;
		$view = $this->_view();
		$mailer = $this->_classes['mailer'];
		$adapter = $mailer::adapter($this->_name);
		$filters = array();
		if (method_exists($adapter, __FUNCTION__)) {
			$args = func_get_args();
			$filters[] = call_user_func_array(array($adapter, __FUNCTION__), $args);
		}
		$message = $this;
		$filter = function($self, $params, $chain) use($view, $mailer, $message) {
			$render = array('html', 'text');
			if ($params['as'] != 'both' && in_array($params['as'], $render)) {
				$render = array($params['as']);
			}
			$process = !empty($params['layout']) ? 'all' : 'template';
			foreach ($render as $type) {
				$content = $view->render($process, array(), compact('type') + $params);
				$message->$type($content);
			}
		};
		return $this->_filter(__METHOD__, $render, $filter, $filters);
	}

	/**
	 * Get/Instantiate vew instance for message rendering
	 */
	protected function _view() {
		if (!isset($this->_viewInstance)) {
			$this->_viewInstance = $this->_instance('view', $this->_view);
		}
		return $this->_viewInstance;
	}
}
?>