<?php
//require('../../ehandler.php');
//require('../../benchmark.php');
$t = microtime(TRUE);
$m = memory_get_usage();

function dump()
{
	$string = '';
	foreach(func_get_args() as $value)
	{
		$string .= '<pre>' . ($value === NULL ? 'NULL' : (is_scalar($value) ? $value : print_r($value, TRUE))) . "</pre>\n";
	}
	return $string;
}

class Service
{
	protected $s = array();

	function __set($key, $callable)
	{
		$callable instanceof Observer AND $callable->attached($this);
		$this->s[strtolower($key)] = $callable;
	}

	function singleton($callable)
	{
		return function ($service) use ($callable)
		{
			static $object;
			return $object ?: ($object = $callable($service));
		};
	}

	function __get($key)
	{
		return $this->s[strtolower($key)];
	}

	function __isset($key)
	{
		return isset($this->s[strtolower($key)]);
	}

	function __unset($key)
	{
		unset($this->s[strtolower($key)]);
	}

	function __call($key, $arg)
	{
		return $this->s[strtolower($key)]($this, $arg);
	}

	public function observe($method, $params)
	{
		foreach($this->s as $k => $observer)
		{
			//print dump("Checking $k");
			if($observer instanceof Observer)
			{
				$params = $observer->observe($this, $method, $params);
			}
		}
		return $params;
	}
}

abstract class Observer
{
	public function __invoke($subject, $method, $params = NULL){}
	public function attached($subject){}
	public function detached($subject){}
}

$o = function($service, $value)
{
	$value = $service->observe('error', $value);
	//print dump(func_get_args());
	print $value;
};

class log extends Observer
{
	public function __construct() { print dump(__CLASS__ . ' loaded!'); }
	public function observe($service, $method, $params = NULL)
	{
		if($method == 'error')
		{
			print dump('logging error with message now...');
		}
	}
}


function s($kill = FALSE) {
	static$s;if($kill)$s=NULL;return$s?:($s=new Service);
}

s();
s()->o = $o;
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);


for ($i = 0; $i < 100; $i++)
{
	$key = "a$i";
	s()->$key = function($value)
	{
		static $other = 'something';
		return new log($value);
	};
}

print dump('Added 100 methods');
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);

for ($i = 0; $i < 100; $i++)
{
	$key = "a$i";
	unset(s()->$key);
}

print dump('Removed 100 methods');
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);

print dump(s(TRUE));
die();

s()->log = new Log();
s()->a = s()->singleton(function($a) { return new log; });
s()->b = function($a) { return $a; };
s()->c = function($a) { return $a; };
s()->a(); s()->a(); s()->a();// Create new log object

s()->o('hello world');
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);

for ($i = 0; $i < 100; $i++)
{
	$key = "a$i";
	s()->$key = function($value) { return $value; };
}

s()->o('hello world');


//print dump(s());
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);


s()->a = NULL;
s()->b = NULL;
s()->c = NULL;

//print dump(s());
print '<br>'.(memory_get_usage() - $m). '<br>'. (microtime(TRUE)-$t);
