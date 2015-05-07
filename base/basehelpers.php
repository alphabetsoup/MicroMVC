<?
require_once('lib-phpextensions.php');
function getArgument($arg,$default) {
    global $params;
    return (isset($params[$arg])) ? $params[$arg] : $default;
}

function requireArgument($arg,$error = "Error: argument not found") {
    global $params;
    if (isset($params[$arg])) return $params[$arg];
    else {
        throw new Exception ($error);
    }
}
function getControllerName() {
    global $state_vars;
    return $state_vars['url_controller'];
}

function getActionName() {
    global $state_vars;
    return $state_vars['url_action'];
}
/*
function getControllerActionTemplate() {
    global $views;
    return getControllerName().DIR_SEP.$views[getActionName()];
}
function getLayoutFromViewDir($layoutname) {
    return LAYOUTDIR.$layoutname;
}
function getLayoutFromBaseDir() {
    return BASEDIR.APPDIR.VIEWDIR;
}
function getViewForController($template_name,$controller) {
    return $controller->getViewDir().$template_name;
}
*/
function getActionAsViewName () {
    global $views;
    return getActionName().'.html';
}
function getABSViewDir() {
    return APPROOT.APPDIR.VIEWDIR;
}

function getLayoutDir() {
    return LAYOUTDIR;
}

function getModelFor($table) {
	// naming convention galleries = Gallery
	$modelname = 'iDB'.ucfirst(str_replace(array("ies","s"),array("y",""),$table));
	return new $modelname();
}

function existsModelForTable($table) {
	return class_exists('iDB'.ucfirst(str_replace(array("ies","s"),array("y",""),$table)));
}

function unpluralise($str) { 
	return preg_replace("#s$#","",preg_replace("#ies$#","ys",$str));
}

function pluralise($str) {
	return preg_replace("#ys$#","ies",$str."s");
}



/********************
 * HTML Helpers
 ********************/
function selectOptions($table) {
	$model = getModelFor($table);
	$allmodels = $model->findAll();
}
