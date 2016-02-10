<?php

require('../../../ehandler.php');
require('../../../benchmark.php');

require('Mustache.php');


$data = array(
	"header" => "Header Colors",
	"check" => 'Check',
	"items" => array(
		array("name" => "<b>red</b>", "first" => true, "url" => "#Red"),
		array("name" => "green", "link" => true, "url" => "#Green"),
		array("name" => "blue", "link" => true, "url" => "#Blue")
	),
	"closure" => function($string, $data)
	{
		print dump($data);
		return "<b>$string</b>";
	},
	'people' => array(
		array('name' => 'Bob'),
		array('name' => 'Mary'),
		array('name' => 'Sam')
	),
	'greeting' => function($string, $data)
	{
		//print $string . "<br>";
		return 'Hello, ' . $data['name'] . '!';
	},
	"empty" => false,
	"html" => '<b><i>HTML</i></b>'
);

for ($i = 0; $i < 1; $i++)
{
	$template = new Mustache('template.html', $data);
}

$layout = new Mustache('layout.html', array('template' => '' . $template));

print $layout;

