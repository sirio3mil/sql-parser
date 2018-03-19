<?php

namespace Utilities;

class Clock {

	public static function getTime(): float
	{
		list($seconds, $milliseconds) = explode(" ", microtime());
		return ((float)$seconds + (float)$milliseconds);
	}
	
	public static function difference(float $time1, float $time2): float
	{
		return ($time2 > $time1)?($time2 - $time1):($time1 - $time2);
	}
	
	public static function GetFormattedDuration(float $difference): string
	{
	    $milliseconds = floor(($difference - floor($difference)) * 1000);
	    $datetime = new \DateTime();
	    $datetime->setTimestamp(floor($difference));
	    return $datetime->format("H:i:s") . "." . $milliseconds;
	}
	
	public static function CalculateScriptDuration(): float
	{
	    return microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];
	}
}