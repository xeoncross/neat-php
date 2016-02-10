<?php

function template($string, $replace){
	$keys = array_map(function($k) {
		return '{'.$k.'}';
	}, array_keys($replace));
	$vals = array_values($replace);
	return str_replace($keys, $vals, $string);
}

echo template("Hello {name}, today is {weather}!", array(
	'name' => 'Dave',
	'weather' => 'sunny',
));