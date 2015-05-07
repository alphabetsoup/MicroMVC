<?php

/**********************************
 * WORKAROUND for php < 5.3
 **********************************/
if(!function_exists('get_called_class')) {
function get_called_class($bt = false,$l = 1) {
	if (!$bt) $bt = debug_backtrace();
	if (!isset($bt[$l])) throw new FatalException("Cannot find called class -> stack level too deep.");
	if (!isset($bt[$l]['type'])) {
		throw new FatalException ('type not set');
	}
	else switch ($bt[$l]['type']) {
		case '::':
			$lines = file($bt[$l]['file']);
			$i = 0;
			$callerLine = '';
			do {
				$i++;
				$callerLine = $lines[$bt[$l]['line']-$i] . $callerLine;
			} while (strpos($callerLine,$bt[$l]['function']) === false);
			preg_match('/([a-zA-Z0-9\_]+)::'.$bt[$l]['function'].'/',
						$callerLine,
						$matches);
			if (!isset($matches[1])) {
				// must be an edge case.
				throw new FatalException ("Could not find caller class: originating method call is obscured.");
			}
			switch ($matches[1]) {
				case 'self':
				case 'parent':
					return get_called_class($bt,$l+1);
				default:
					return $matches[1];
			}
			// won't get here.
		case '->': switch ($bt[$l]['function']) {
				case '__get':
					// edge case -> get class of calling object
					if (!is_object($bt[$l]['object'])) throw new FatalException ("Edge case fail. __get called on non object.");
					return get_class($bt[$l]['object']);
				default: return $bt[$l]['class'];
			}

		default: throw new FatalException ("Unknown backtrace method type");
	}
}
}
if (!function_exists("quoted_printable_encode")) {
	/**
	* Process a string to fit the requirements of RFC2045 section 6.7. Note that
	* this works, but replaces more characters than the minimum set. For readability
	* the spaces and CRLF pairs aren't encoded though.
	*/
	/*function quoted_printable_encode($string) {
		$string = str_replace(array('%20', '%0D%0A', '%'), array(' ', "\r\n", '='), rawurlencode($string));
		$string = preg_replace('/[^\r\n]{72}[^=\r\n]{2}/', "$0=\r\n", $string);

		return $string;
	}*/
	/*function quoted_printable_encode($string) { 
	return preg_replace('/[^\r\n]{73}[^=\r\n]{2}/', "$0=\r\n", str_replace("%","=",str_replace("%20"," ",rawurlencode($string)))); 
	}*/
function quoted_printable_encode( $str, $chunkLen = 72 )
{
	$offset = 0;
	
	$str = strtr(rawurlencode($str), array('%' => '='));
	$len = strlen($str);
	$enc = '';
	
	while ( $offset < $len )
	{
		if ( $str{ $offset + $chunkLen - 1 } === '=' )
		{
			$line = substr($str, $offset, $chunkLen - 1);
			$offset += $chunkLen - 1;
		}
		elseif ( $str{ $offset + $chunkLen - 2 } === '=' )
		{
			$line = substr($str, $offset, $chunkLen - 2);
			$offset += $chunkLen - 2;
		}
		else 
		{
			$line = substr($str, $offset, $chunkLen);
			$offset += $chunkLen;
		}
		
		if ( $offset + $chunkLen < $len )
			$enc .= $line ."=\n";
		else 
			$enc .= $line;
	}
	
	return $enc;
}
}
