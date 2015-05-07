<?php
// realy simple.
// pattern is /controller/action/id

$params = $_REQUEST;

if (!isset($params['noroute'])) {

$url = str_replace(WEBROOT,'/',$_SERVER['PHP_SELF']);

$routes = array(
	'/'=>array('controller'=>'home','action'=>'index')
	// add more routes here
);

$state_vars = array();

/* DEPRECIATED - USE MODREWRITE
if (isset($routes[$url])) {
	$cont = $state_vars['url_controller'] = $params['controller'] = $routes[$url]['controller'];
	$act = $state_vars['url_action'] = $params['action'] = $routes[$url]['action'];
}
else {
	$a = explode('/',$url);
	if (!isset($a[0])) $a[0] = 'home';
	if (!isset($a[1])) $a[1] = 'index';
	$cont = $state_vars['url_controller'] = $params['controller'] = $a[0];
	$act = $state_vars['url_action'] = $params['action'] = $a[1];
}
*/
$cont = $state_vars['url_controller'] = $params['controller'];
$act = $state_vars['url_action'] = $params['action'];
// TODO add more pattern matching for routes
if (isset($args[2])) $params['id'] = $args[2];

// turn controller argument into name of class
$cont = ucfirst($cont).'Controller';
if (!class_exists($cont)) die("Controller $cont not found.");
	//throw new FatalException ("Error: Could not find controller class $cont");
$controller = new $cont();

// call action, get response
$result = call_user_func(array($controller,$act));
}
