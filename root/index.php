<?

require_once('config.php');
require_once('base.php');
require_once('exceptions.php');


// Load controllers and models. For each controller, get any views.

$controllers = scandir(APPROOT.APPDIR.CONTROLLERDIR);
$models = scandir(APPROOT.APPDIR.MODELDIR);
$views = array();

foreach ($controllers as $c) if ($c[0] != '.') require_once(APPROOT.APPDIR.CONTROLLERDIR.$c);
foreach ($models as $m) if ($m[0] != '.') require_once(APPROOT.APPDIR.MODELDIR.$m);

foreach ($controllers as $c) {
	$v = str_replace('.php','',$c);
	if (file_exists(APPROOT.APPDIR.VIEWDIR.$v) && is_dir(APPROOT.APPDIR.VIEWDIR.$v)) $views[$v] = scandir(APPROOT.APPDIR.VIEWDIR.$v);
}

require_once('basehelpers.php');
require_once 'lib-dbsequence.php';
require('routes.php');
