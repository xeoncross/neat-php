<?php

class A
{
	static $static = NULL;
	//static $static = array('of','values');

	static function s()
	{
		for($x=0;$x<1000000;++$x) $v = static::$static;
	}
	
	function t()
	{
		for($x=0;$x<1000000;++$x) $v = $this::$static;
	}
		
	function tt()
	{
		$t=$this;
		for($x=0;$x<1000000;++$x) $v = $t::$static;
	}
}

$a = new A();
A::s(); // Warm-up


$start = microtime(TRUE);
$a->tt();
print (microtime(TRUE)-$start). ' seconds ($a->tt())<br />';

$start = microtime(TRUE);
$a->t();
print (microtime(TRUE)-$start). ' seconds ($a->t())<br />';

$start = microtime(TRUE);
A::s();
print (microtime(TRUE)-$start). ' seconds (A::s())<br />';

$start = microtime(TRUE);
$a::s();
print (microtime(TRUE)-$start). ' seconds ($a::s())<br />';
