<?php


class A
{
	public $value = TRUE;
	public function call1($method, $params = NULL)
	{
		return $params;
	}
	
	public static function call2($method, $params = NULL)
	{
		return $params;
	}
}

//$a = new A;

$time = microtime(true);
$memory = memory_get_usage();
print ((memory_get_usage()-$memory). ' bytes<br>'. (microtime(TRUE)-$time).'seconds<br><br>');
for($x=0;$x<1000;++$x)
{
	$a = new A; $v = $a->call1($x);
}

print ((memory_get_usage()-$memory). ' bytes<br>'. (microtime(TRUE)-$time).'seconds<br><br>');
$time = microtime(true);
$memory = memory_get_usage();
for($x=0;$x<1000;++$x)
{
	$v = A::call2($x);
}

print ((memory_get_usage()-$memory). ' bytes<br>'. (microtime(TRUE)-$time).'seconds<br><br>');
$time = microtime(true);
$memory = memory_get_usage();
for($x=0;$x<1000;++$x)
{
	$a = new A; $v = call_user_func(array('A','call2'), $x);
}

print ((memory_get_usage()-$memory). ' bytes<br>'. (microtime(TRUE)-$time).'seconds<br><br>');
$time = microtime(true);
$memory = memory_get_usage();
for($x=0;$x<1000;++$x)
{
	$v = call_user_func('A::call2', $x);
}

