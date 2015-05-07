<?
error_reporting(E_ALL);

define ('DIRSEP','/');
//define ('BASE',str_replace( $_SERVER['DOCUMENT_ROOT'], "", dirname(realpath(__FILE__)) ) . DIRSEP);
define ('APPROOT',dirname(realpath(__FILE__)).DIRSEP.'..'.DIRSEP);
define ('BASE',dirname(realpath(__FILE__)) . DIRSEP);
define ('APPDIR','app'.DIRSEP);
define ('CONTROLLERDIR','controllers'.DIRSEP);
define ('MODELDIR','models'.DIRSEP);
define ('VIEWDIR','views'.DIRSEP);
define ('LAYOUTDIR','layouts'.DIRSEP);
define ('PUBLICROOT',APPROOT.'root'.DIRSEP);
//define ('UPLOADSROOT',PUBLICROOT.'uploads'.DIRSEP);

define ('DIE_THRESHOLD',E_ALL & ~E_NOTICE);
define ('ABSLOGDIR', APPROOT.'log'.DIRSEP);


define('INCDIR', dirname(__FILE__));


// EXHIBIT
define('EXH_LIBRARY', INCDIR.'/../root/uploads/img-library/');
define('EXH_CACHE', INCDIR.'/../root/uploads/img-cache/');

define ('UPLOADSROOT',EXH_LIBRARY);

define('IMG_STD_SIZE', 250);
define('IMG_THUMB_SIZE', 100);


set_include_path('.:'.INCDIR.':'.INCDIR.'/pear');

if (strstr($_SERVER['SERVER_NAME'], 'subsume') or strstr($_SERVER['SERVER_NAME'], 'localhost')) {
    define('DB_CONNECT_STR', 'mysql://root:@localhost/instajungle');
    define('UPLOADS_ROOT', INCDIR.'/../root/uploads/'); // need trailing slash
    define('WEBROOT', '/instajungle.com/root/'); // need trailing slash
	define('LOGFILE',ABSLOGDIR.'development.log');
} else {
    define('DB_CONNECT_STR', 'mysql://instajungle:evolution@202.177.212.199/instajungle');
    define('UPLOADS_ROOT', INCDIR.'/../root/uploads/'); // need trailing slash
    define('WEBROOT', '/'); // need trailing slash
	define('LOGFILE',ABSLOGDIR.'production.log');
}
