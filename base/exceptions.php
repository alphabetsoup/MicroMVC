<?

class BaseException extends Exception {
    function __construct ($arg,$developer = false) {
        $this->message_str = $arg;
        parent::__construct($arg);
        if ($developer) developer_log($arg);
    }
}

// Exceptions by severity
class WarningException extends BaseException {
    function __construct ($arg) { parent::__construct($arg); }
}
class FatalException extends ErrorException {
    function __construct ($arg, $a = false, $b = false, $c = false, $d = false) {
        if ($a === false) parent::__construct($arg);
        else parent::__construct($arg,$a,$b,$c,$d);
        developer_log($arg);
    }
}
class NoPermissionException extends BaseException {
    function __construct ($arg) { parent::__construct($arg); }
}
class NoPermissionMinorException extends NoPermissionException { }
class NothingToDoException extends BaseException {
    function __construct ($arg) { parent::__construct($arg);}
}

function exception_error_handler($errno, $errstr, $errfile, $errline ) {
    if ($errno >= DIE_THRESHOLD) throw new FatalException($errstr, 0, $errno, $errfile, $errline);
}
// set our errorhandler to ErrorException for the calendar.
set_error_handler("exception_error_handler", E_ALL & ~E_NOTICE);

function developer_log($str) {
    $fh = fopen(LOGFILE,'a') or die ('Failed writing to log '.LOGFILE);
    fwrite($fh,date('Y-m-d H:i:s')." ".$str."\n");
    fclose($fh);
}
