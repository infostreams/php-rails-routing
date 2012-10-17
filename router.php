<?php
/**
 * Rails like routing for PHP
 *
 * Based on http://blog.sosedoff.com/2009/09/20/rails-like-php-url-router/
 * but extended in significant ways:
 *
 * 1. Can now be deployed in a subdirectory, not just the domain root
 * 2. Will now call the indicated controller & action. Named arguments are
 *    converted to similarly method arguments, i.e. if you specify :id in the
 *    URL mapping, the value of that parameter will be provided to the method's
 *    '$id' parameter, if present.
 * 3. Will now allow URL mappings that contain a '?' - useful for mapping JSONP urls
 * 4. Should now correctly deal with spaces (%20) and other stuff in the URL
 *
 * @version 2.0
 * @author Dan Sosedoff <http://twitter.com/dan_sosedoff>
 * @author E. Akerboom <github@infostreams.net>
 */
define('ROUTER_DEFAULT_CONTROLLER', 'home');
define('ROUTER_DEFAULT_ACTION', 'index');

class Router {
	public $request_uri;
	public $routes;
	public $controller, $controller_name;
	public $action, $id;
	public $params;
	public $route_found = false;

	public function __construct() {
		$request = $this->get_request();

		$this->request_uri = $request;
		$this->routes = array();
	}

	public function get_request() {
		$request_uri = rtrim($_SERVER["REQUEST_URI"], '/');
		
		// find out the absolute path to this script
		$here = realpath(rtrim(dirname($_SERVER["SCRIPT_FILENAME"]), '/'));
		$here = str_replace("\\", "/", $here . "/");

		// find out the absolute path to the document root
		$document_root = str_replace("\\", "/", realpath($_SERVER["DOCUMENT_ROOT"]) . "/");

		// let's see if we can return a path that is expressed *relative* to the script
		// (i.e. if this script is in '/sites/something/router.php', and we are
		// requesting /sites/something/here/is/my/path.png, then this function will 
		// return 'here/is/my/path.png')
		if (strpos($here, $document_root) !== false) {
			$relative_path = rtrim("/" . str_replace($document_root, "", $here), '/');
			$path_route = urldecode(str_replace($relative_path, "", $request_uri));
			return trim($path_route, '/');
		}

		// nope - we couldn't get the relative path... too bad! Return the absolute path
		// instead.
		return urldecode($request_uri);
	}

	public function map($rule, $target = array(), $conditions = array()) {
		if (is_string($target)) {
			// handle the shorthand notation "controller::action"
			list($controller, $action) = explode('::', $target);
			$target = array('controller'=>$controller, 'action'=>$action);
		}
		
		$this->routes[$rule] = new Route($rule, $this->request_uri, $target, $conditions);
	}

	public function default_routes() {
		$this->map(':controller');
		$this->map(':controller/:action');
		$this->map(':controller/:action/:id');
	}

	private function set_route($route) {
		$this->route_found = true;
		$params = $route->params;
		$this->controller = $params['controller']; unset($params['controller']);
		$this->action = $params['action']; unset($params['action']);
		$this->id = $params['id'];
		$this->params = array_merge($params, $_GET);

		if (empty($this->controller)) {
			$this->controller = ROUTER_DEFAULT_CONTROLLER;
		}
		if (empty($this->action)) {
			$this->action = ROUTER_DEFAULT_ACTION;
		}
		if (empty($this->id)) {
			$this->id = null;
		}

		// determine controller name
		$this->controller_name = implode(array_map('ucfirst', explode('_', $this->controller . "_controller")));
	}

	public function match_routes() {
		foreach ($this->routes as $route) {
			if ($route->is_matched) {
				$this->set_route($route);
				break;
			}
		}
	}

	public function run() {
		$this->match_routes();

		if ($this->route_found) {
			// we found a route!
			if (class_exists($this->controller_name)) {
				// ... the controller exists
				$controller = new $this->controller_name();
				if (method_exists($controller, $this->action)) {
					// ... and the action as well! Now, we have to figure out
					//     how we need to call this method:

					// iterate this method's parameters and compare them with the parameter names
					// we defined in the route. Then, reassemble the values from the URL and put
					// them in the same order as method's argument list.
					$m = new ReflectionMethod($controller, $this->action);
					$params = $m->getParameters();
					$args = array();
					foreach ($params as $i=>$p) {
						if (isset($this->params[$p->name])) {
							$args[$i] = urldecode($this->params[$p->name]);
						} else {
							// we couldn't find this parameter in the URL! Set it to 'null' to indicate this.
							$args[$i] = null;
						}
					}

					// Finally, we call the function with the resulting list of arguments
					call_user_func_array(array($controller, $this->action), $args);
				} else {
					$this->error(404, "Action " . $this->controller_name . "." . $this->action . "() not found");
				}
			} else {
				$this->error(404, "No such controller: " . $this->controller_name);
			}
		} else {
			$this->error(404, "Page not found");
		}
	}

	protected function error($nr, $message) {
		$http_codes = array(
			404=>'Not Found',
			500=>'Internal Server Error',
			// we don't need the rest anyway ;-)
		);

		header($_SERVER['SERVER_PROTOCOL'] . " $nr {$http_codes[$nr]}");
		echo "
		<style type='text/css'>
			.routing-error { font-family:helvetica,arial,sans; border-radius:10px; border:1px solid #ccc; background:#efefef; padding:20px; }
			.routing-error h1 { padding:0px; margin:0px 0px 20px; line-height:1; }
			.routing-error p { color:#444; padding:0px; margin:0px; }
		</style>
		<div class='error routing-error'>
			<h1>Error $nr</h1>
			<p>$message</p>
		</div>";
		exit;
	}
}

class Route {
	public $is_matched = false;
	public $params;
	public $url;
	private $conditions;

	function __construct($url, $request_uri, $target, $conditions) {
		$this->url = $url;
		$this->params = array();
		$this->conditions = $conditions;
		$p_names = array();
		$p_values = array();

		// extract pattern names (catches :controller, :action, :id, etc)
		preg_match_all('@:([\w]+)@', $url, $p_names, PREG_PATTERN_ORDER);
		$p_names = $p_names[0];

		// make a version of the request with and without the '?x=y&z=a&...' part
		$pos = strpos($request_uri, '?');
		if ($pos) {
			$request_uri_without = substr($request_uri, 0, $pos);
		} else {
			$request_uri_without = $request_uri;
		}

		foreach (array($request_uri, $request_uri_without) as $request) {
			$url_regex = preg_replace_callback('@:[\w]+@', array($this, 'regex_url'), $url);
			$url_regex .= '/?';

			if (preg_match('@^' . $url_regex . '$@', $request, $p_values)) {
				array_shift($p_values);
				foreach ($p_names as $index=>$value) {
					$this->params[substr($value, 1)] = urldecode($p_values[$index]);
				}
				foreach ($target as $key=>$value) {
					$this->params[$key] = $value;
				}
				$this->is_matched = true;
				break;
			}
		}

		unset($p_names);
		unset($p_values);
	}

	function regex_url($matches) {
		$key = str_replace(':', '', $matches[0]);
		if (array_key_exists($key, $this->conditions)) {
			return '(' . $this->conditions[$key] . ')';
		} else {
			return '([a-zA-Z0-9_\+\-%]+)';
		}
	}
}
?>