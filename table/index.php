<?php

function current_url()
{
	return '';
}

function get($key, $default = NULL)
{
	if(isset($_GET[$key]))
	{
		return $_GET[$key];
	}

	return $default;
}


require('Table.php');

$data = array();
for ($x = 1; $x <= 10; $x++)
{
	$data[] = (object) array(
		'id' => $x,
		'username' => 'User '. $x,
		'email' => $x . '@email.com',
		'created' => time() + $x,
		'ignore' => 'nothing'
	);
}

$_GET['value'] = 'something';
$_GET['search'] = 'my cool word!';


$table = new Table($data);

$table->column('<input type="checkbox" name="checkall" id="checkall" />', '', function($row)
{
	return '<input type="checkbox" name="ids[]" value="' . $row->id . '" />';
});

$table->column('User ID', 'id', function($row)
{
	return $row->id;
});

$table->column('Username', 'username', function($row)
{
	return $row->username;
});

$table->column('Email', 'email');

print $table->render();
