<?php
/**
 * Fetch an array value using a string.with.dot.notation
 */
function Value($data, $key, $default = null)
{
	foreach (explode('.', $key) as $value)
	{
		if(is_object($data)) $data = get_object_vars($data);

		if ( ! is_array($data) || ! array_key_exists($value, $data))
		{
			return $default;
		}

		$data = $data[$value];
	}

	return $data;
}



$myArray = array(
		'key1' => 'value1',
		'key2' => array(
				'subkey' => 'subkeyval'
		),
		'key3' => 'value3',
		'key4' => array(
				'subkey4' => array(
						'subsubkey4' => 'subsubkeyval4',
						'subsubkey5' => 'subsubkeyval5',
				),
				'subkey5' => 'subkeyval5'
		)
);

header('Content-Type: text/plain;');

var_dump(value($myArray, 'key4.subkey4.subsubkey4'));
var_dump(value($myArray, 'key4.other.subsubkey4'));
var_dump(value($myArray, null));
