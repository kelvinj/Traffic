<?php
/**
 * - add route
 * 	- route matches taking into account parent routes
 */
namespace Fu;

class Traffic {

	/**
	 * Registers the public functions in the global namespace.
	 *
	 */
	public function register_global_functions ($overrides = array()) {
		require_once __DIR__.'/Traffic/global_functions.php';
	}

	public function get () {
		if (self::request_method() !== 'GET' || self::_exited()) return;
		$args = self::_process_args(func_get_args());
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	public function post () {
		if (self::request_method() !== 'POST' || self::_exited()) return;
		$args = self::_process_args(func_get_args());
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	public function put () {
		if (self::request_method() !== 'PUT' || self::_exited()) return;
		$args = self::_process_args(func_get_args());
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	public function delete () {
		if (self::request_method() !== 'DELETE' || self::_exited()) return;
		$args = self::_process_args(func_get_args());
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	public function request () {
		if (!in_array(self::request_method(), array('GET', 'POST', 'PUT', 'DELETE')) || self::_exited()) return;
		$args = self::_process_args(func_get_args());
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	// used to construct nested… need to refactor following code, which is nice :)
	public function rel () {
		if (self::_exited()) return;
		$args = self::_process_args(func_get_args(), true); // is relative
		self::_route($args['path'], $args['callback'], $args['options']);
	}

	public function relative_to () {call_user_func_array('self::rel', func_get_args());}

	/**
	 * Sends a 404 and calls a callback function if provided
     */
	public function not_found ($callback=null) {
		if (self::_exited()) return;
		self::http_response(404);
		if (is_callable($callback)) {
			$r = $callback(array());
		}
		return self::_exit_routine();
	}

	/**
	 * stop execution and call a callback
	 */
	public function halt ($callback) {
		if (self::_exited()) return;
		if (is_callable($callback)) {
			$r = $callback(array());
		}
		return self::_exit_routine();
	}

	/**
	 * function acts as a getter and setter.
	 *
	 * Calling pass() will set the statis $pass variable to true
	 * the next call to pass(1) will return true, meaning to pass onto the next rule and set $pass to false
	 * subsequent calls to pass(1) will return false until pass() is called
	 */
	public function pass ($get=false) {
		static $pass=false;

		if ($get) {
			if ($pass) {
				$pass = false;
				return true;
			}
			else {
				return false;
			}
		}
		else {
			$pass = true;
			return -1;
		}
	}

	public function params ($set=null) {
		static $params=array();

		if (!is_null($set)) {
			$params = $set;
		}

		return $params;
	}

	/**
	 * Set and get an array of options, or set just one options to be used as defaults for each routing
	 * call.
	 */
	public function options ($set=null, $value=null) {
		static $options=array(
							'relative' => false, // set to true for a relative_to path
							'agent' => null, // string or regex to match against the user agent
							'exit_after_callback' => 1, // bool true=exit; false stops all other calls to traffic
						);

		if (!is_null($set)) {
			if (is_array($set)) {
				$options = array_merge($options, $set);
			}
			elseif (is_string($set)) {
				$options[$set] = $value;
			}
		}

		return $options;
	}


	public function request_method () {
		$rm = strtoupper($_SERVER['REQUEST_METHOD']);
		if ($rm == 'POST' && $_POST['_method']) {
			$rm = strtoupper($_POST['_method']);
		}

		return $rm;
	}

	public function request_uri ($set=null) {
		static $rq;

		if (!is_null($set)) {
			$rq = rtrim($set, '/');
			if (!$rq) $rq == '/';
			return;
		}

		if (!$rq) {
			if (isset($_SERVER['PATH_INFO'])) {
				$rq = $_SERVER['PATH_INFO'];
			}
			else {
				$rq = ($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
			}

			$rq = rtrim($rq, '/');
			if (!$rq) $rq == '/';
		}

		return $rq;
	}

	/**
	 * Given an extension, will attempt to output a content type.
	 *
	 * @return true on success, false on failure
	 */
	public function content_type ($ext) {
		$ext = strtolower($ext);

		$types = self::_mime_types();
		if (array_key_exists($ext, $types)) {
			$mtype = $types[$ext];
			if (!headers_sent()) {
				header("Content-Type: $mtype");
				return true;
			}
		}

		return false;
	}

	/**
	 * Given an extension, will attempt to output a content type.
	 *
	 * @return true on success, false on failure
	 */
	public function http_response ($code) {
		$codes = self::_http_response_codes();
		if (array_key_exists($code, $codes)) {
			$message = $codes[$code];
			if (!headers_sent()) {
				header("HTTP/1.1 $code $message");
				return true;
			}
		}

		return false;
	}

	/**
	 * Used in testing to return a previsouly existed request into an unexited one.
	 */
	public function unexit () {
		self::_exited(false);
	}

	/**
	 * PRIVATE FUNCTIONS
	 */

	/**
	 * Has the current process already exited using, e.g. if the exit_after_callbak options is false
	 * calls to Traffic will be ignored.
	 */
	private function _exited ($v=null) {
		static $e = false;

		if (!is_null($v)) $e = $v;

		return $e;
	}

	/**
	 * Processes arguments passed to get, post …etc to determine which argument is the callback,
	 * options… etc.
	 */
	private function _process_args ($a, $relative = false) {
		$r = array('path' => '/',
				   'callback' => function(){},
				   'options' => self::options()
				   );

		foreach ($a as $k => $v) {
			switch (true) {
				case is_string($v):
					$r['path'] = $v;
					break;

				case is_callable($v):
					$r['callback'] = $v;
					break;

				case is_array($v):
					$r['options'] = array_merge($r['options'], $v);
					break;
			}
		}

		if ($relative) {
			$r['options']['_rel'] = true; // so we know that it was an internal _rel call
			$r['options']['relative'] = true;
		}
		else {
			$r['options']['_rel'] = false;
		}

		if (substr($r['path'], -2) == '/*' && !$relative) {
			$r['path'] = rtrim($r['path'], '/*');
			$r['options']['relative'] = true;
		}

		return $r;
	}

	/**
	 * Run the route to determine if it matches against the current request.
	 */
	private function _route ($path, $callback, $options) {
		static $routes;

		if (self::_exited() || !$callback) return;

		// trim / off the path
		$path = trim($path, '/');

		$routes[] = array(
			'path' => $path,
			'callback' => $callback,
			'options' => $options
		);

		$route = self::_build_route($routes);
		$matches = self::_pattern_match($route);

		if ($matches !== false) {
			// any other requisites?
			if (isset($options['agent'])) {
				$agent_found = false;
				if (self::_is_regex($options['agent'])) {
					if (preg_match($options['agent'], $_SERVER['HTTP_USER_AGENT'])) {
						$agent_found = true;
					}
				}
				elseif (stristr($_SERVER['HTTP_USER_AGENT'], $options['agent']) !== false) {
					$agent_found = true;
				}

				if (!$agent_found) {
					array_pop($routes);
					return;
				}
			}


			$params = array('splats' => array());
			foreach ($matches as $k => $v) {
				if (is_numeric($k)) continue;

				if (substr($k, 0, 5) == 'splat') {
					$params['splats'][] = $v[0];
					$params[] = $v[0];
				}
				else {
					// check for a regex rule in the options
					if ($options[":$k"]) {
						$regex = $options[":$k"];
						if (!self::_is_regex($regex)) {
							$regex = "/^$regex$/i";
						}

						if (!preg_match($regex, $v[0])) {
							array_pop($routes);
							return;
						}
					}

					$params[$k] = $v[0];
				}
			}

			self::params($params); // sets params

			if (is_callable($callback)) {
				$r = $callback($params);
			}

			$last_route = array_pop($routes);
			$skip_exit = !!(self::pass(true) || $r == -1);

			if (!$skip_exit && $last_route['options']['_rel'] == false) {
				return self::_exit_routine(); // we found the deepest route, so let's kill it
			}
		}
		else {
			array_pop($routes);
		}
	}

	/**
	 * Check if the route contains something that will require a regular expression.
	 * If not, just do a simple string match
	 *
	 * @return mixed success: array of matches or empty array, failure: false
	 */
	private function _pattern_match ($route) {
		if (strpbrk($route, '*:()') !== false) { // found characters for converting to a regex
			$regex = self::_transform_route($route);
			if (preg_match_all($regex, self::request_uri(), $matches)) {
				return $matches;
			}
		}
		elseif (self::request_uri() == $route) {
			return array();
		}

		return false;
	}

	/**
	 * Successfully matched routes exit, but differently depending on options.
	 */
	private function _exit_routine () {
		$o = self::options();
		if ($o['exit_after_callback']) {
			exit; // stop further php execution
		}
		else {
			self::_exited(true);
		}
	}

	/**
	 * Takes an array of routes from nested calls to get()/post() and joins them into 1 route
	 */
	private function _build_route ($routes=array()) {
		$paths = array_map(function($v){
			if ($v['path'] == '/') {
				return null;
			}
			return $v['path'];
		}, $routes);
		$paths = array_filter($paths, function ($v){
			return !!$v;
		});

		$route = implode('/', $paths);
		$last_route = end($routes);

		if ($last_route['options']['relative'] && strlen($route) > 1 && substr($route, -1) != '*') { // last route is a relative route
			$route.= '/*';
		}

		if ($route && $route[0] != '*') {
			$route = "/$route";
		}

		return $route;
	}

	/**
	 * Transforms a route into a regex produced by _build_route into a regex for matching against
	 * request_uri()
	 */
	private function _transform_route ($route) {
		// make brackets into options regex
		$route = str_replace(')', ')?', $route);

		// escape full stops
		$route = str_replace('.', '\.', $route);

		if (substr($route, -2) == '/*') {
			$route = substr($route, 0, strlen($route)-2).'**';
		}

		// change wildcards
		$route = preg_replace_callback('/\*?\*/', function ($match) {
					static $i=0;
					if ($match[0] == '**') { // route should match the route or with a / and appended
						return sprintf('(/(?<splat%s>.*))?', $i++);
					}
					return sprintf('(?<splat%s>.*)', $i++);
				 }, $route);

		// change :labels
		$route = preg_replace_callback('/(:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/', function ($match) {
					static $used_names = array();

					$match = str_replace(':', '', $match[0]);
					if (array_key_exists($match, $used_names)) {
						return ":$match";
					}
					else {
						$used_names[$match] = true;
					}

					return sprintf('(?<%s>[^/]+)', $match);
				 }, $route);

		return "|^$route$|U";
	}

	private function _mime_types ($set=null, $value=null) {
		static $options=array(
			'html'=> 'text/html',
			'js'  => 'text/javascript',
			'css' => 'text/css',
			'gif' => 'image/gif',
			'jpg' => 'image/jpeg',
			'png' => 'image/png',
			'txt' => 'text/plain',
			'xml' => 'text/xml',
			'rss' => 'text/xml',
			'pdf' => 'application/pdf'
		);

		if (!is_null($set)) {
			if (is_array($set)) {
				$options = $set;
			}
			elseif (is_string($set)) {
				$options[$set] = $value;
			}
		}

		return $options;
	}

	private function _http_response_codes ($set=null, $value=null) {
		static $options=array(
			100 => 'Continue',
			101 => 'Switching Protocols',
			102 => 'Processing',

			200 => 'OK',
			201 => 'Created',
			202 => 'Accepted',
			203 => 'Non-Authoritative Information',
			204 => 'No Content',
			205 => 'Reset Content',
			206 => 'Partial Content',
			207 => 'Multi-Status',
			226 => 'IM Used',

			300 => 'Multiple Choices',
			301 => 'Moved Permanently',
			302 => 'Found',
			303 => 'See Other',
			304 => 'Not Modified',
			305 => 'Use Proxy',
			306 => 'Reserved',
			307 => 'Temporary Redirect',

			400 => 'Bad Request',
			401 => 'Unauthorized',
			402 => 'Payment Required',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			406 => 'Not Acceptable',
			407 => 'Proxy Authentication Required',
			408 => 'Request Timeout',
			409 => 'Conflict',
			410 => 'Gone',
			411 => 'Length Required',
			412 => 'Precondition Failed',
			413 => 'Request Entity Too Large',
			414 => 'Request-URI Too Long',
			415 => 'Unsupported Media Type',
			416 => 'Requested Range Not Satisfiable',
			417 => 'Expectation Failed',
			422 => 'Unprocessable Entity',
			423 => 'Locked',
			424 => 'Failed Dependency',
			426 => 'Upgrade Required',

			500 => 'Internal Server Error',
			501 => 'Not Implemented',
			502 => 'Bad Gateway',
			503 => 'Service Unavailable',
			504 => 'Gateway Timeout',
			505 => 'HTTP Version Not Supported',
			506 => 'Variant Also Negotiates',
			507 => 'Insufficient Storage',
			510 => 'Not Extended'
		);

		if (!is_null($set)) {
			if (is_array($set)) {
				$options = $set;
			}
			elseif (is_string($set)) {
				$options[$set] = $value;
			}
		}

		return $options;
	}

	private function _is_regex ($r) {
		if (preg_match('/^(?<d>.{1}).*(\k{d})[isxADSUXJu]{0,10}$/', $r)) {
			return true;
		}

		return false;
	}
}