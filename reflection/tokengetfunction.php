<?php
//ini_set('memory_limit', '16M');

// Parse a file safely without including it looking for functions
// http://stackoverflow.com/questions/11532/how-can-i-find-unused-functions-in-a-php-project
// http://stackoverflow.com/a/2197870/99923
function get_defined_functions_in_file($source) {
	$tokens = token_get_all($source);

	$inClass = false;
	$bracesCount = 0;

	$functionSource = null;
	$functionName = null;
	$functions = array();
	$nextStringIsFunc = false;

	$className = null;
	$classSource = null;
	$classes = array();
	$nextStringIsClass = false;

	foreach($tokens as $token) {

		if($functionSource !== null) {
			$functionSource .= isset($token[1]) ? $token[1] : $token;
		}

		if($classSource !== null) {
			$classSource .= isset($token[1]) ? $token[1] : $token;
		}

		switch($token[0]) {
			case T_CLASS:
				$inClass = true;
				$classSource = 'class';
				$nextStringIsClass = true;
				break;
			case T_FUNCTION:
				if(!$inClass) {
					$functionSource = 'function';
					$nextStringIsFunc = true;
				}
				break;

			case T_STRING:
				if($nextStringIsFunc) {
					$nextStringIsFunc = false;
					$functionName = $token[1];
				}

				if($nextStringIsClass) {
					$nextStringIsClass = false;
					$className = $token[1];
				}

				break;

			// Anonymous functions
			case '(':
			case ';':
				$nextStringIsFunc = false;
				break;

			// Exclude Classes
			case '{':
				if($inClass) $bracesCount++;
				break;

			case '}':
				if($inClass) {
					$bracesCount--;
					if($bracesCount === 0) $inClass = false;
				}

				if($functionSource !== null) {

					// Anonymous functions do not have a name
					if( ! $functionName) {
						$functionName = count($functions);
					}

					$functions[$functionName] = $functionSource;
					//print $function ."\n-----\n";
					$functionSource = null;
					$functionName = null;
				}

				if($classSource !== null) {

					// Anonymous functions do not have a name
					if( ! $className) {
						die("No Class Name!?\n\n");
					}

					$classes[$className] = $classSource;
					$classSource = null;
					$className = null;
				}

				break;
		}
	}

	return array('functions' => $functions, 'classes' => $classes);
}



$source = file_get_contents('getfunction.samples.php');

print_r(get_defined_functions_in_file($source));