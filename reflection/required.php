<?php PHP_SAPI === 'cli' OR die();

/**
 * Simply include this file to see all the requirements of any project
 * For saftey, this only works when included over the CLI
 */

$time = microtime(TRUE);

if(empty($argv[1])) {
	die("Usage: " . color(basename(__FILE__) . " [project_dir]", 'green') . "\n");
}

$directory = rtrim($argv[1], '/') . '/';

if($directory{0} !== '/') {
	$directory = __DIR__ . '/' . $directory;
}

if( ! is_dir($directory)) {
	die("Directory \"". color($directory, 'red') . "\" not found\n");
}

/**
 * Color string output for the CLI using standard color codes.
 *
 * @param string $text to color
 * @param string $color of text
 * @param string $bold True to bold the text
 */
function color($text, $color, $bold = FALSE)
{
	$colors = array_flip(array(
		30 => 'gray', 'red', 'green', 'yellow', 'blue', 'purple', 'cyan', 'white', 'black'
	));

	return "\033[" . ($bold ? '1' : '0') . ';' . $colors[$color] . "m$text\033[0m";
}

$extensions = get_loaded_extensions();
$extension_classes = array_flip(get_declared_classes());

// These are not included
$extension_classes['stdClass'] = TRUE;
$extension_classes['self'] = TRUE;
$extension_classes['static'] = TRUE;

$defined_classes = array();
$used_classes = array();
$variable_classes = array();
$functions = array();

foreach($extensions as $extension) {
	if($extension_functions = get_extension_funcs($extension)) {
		foreach($extension_functions as $function) {
			$functions[$function] = $extension;
		}
	}
}

$var = xdebug_get_headers();

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
	$directory, 
	FilesystemIterator::SKIP_DOTS | FilesystemIterator::KEY_AS_PATHNAME
));

$required = array();
$counter = 0;
foreach($it as $file => $fileinfo)
{
	if($fileinfo->getExtension() == 'php') {

		if(($counter++) % 30 === 0) {
			print "Processed $counter PHP files\n";
		}

		//print $file . "\n";

		$text = file_get_contents($file);
		$tokens = token_get_all($text);

		foreach($tokens as $i => $token) {

			if(is_array($token)) {

				if($token[0] === T_FUNCTION OR $token[0] === T_STRING) {

					if(isset($functions[$token[1]])) {
						$required[$functions[$token[1]]] = TRUE;
					}
				}

				if(isset($tokens[$i - 2][0], $tokens[$i - 1][0])) {

					if ($tokens[$i - 2][0] == T_NEW AND $tokens[$i - 1][0] == T_WHITESPACE) {

						// new [class]()
						if($tokens[$i][0] == T_STRING) {

							if(empty($extension_classes[$token[1]])) {
								$used_classes[$token[1]] = TRUE;
							}
						
						// new $variable()
						} elseif($tokens[$i][0] == T_VARIABLE) {

							// @todo, this is really broken. However, do best to look for the assignment
							if(preg_match('~\$var\s*=\s*([\'"])((?:(?!\1).)*)\1~', $text, $match)) {
								if(empty($extension_classes[$match[2]])) {
									$used_classes[$match[2]] = TRUE;
								}
							} elseif($token[1] !== '$this') {
								$variable_classes[$token[1]] = TRUE;
							}
						}
					}

					// class [class] {...}
					if ($tokens[$i - 2][0] == T_CLASS OR $tokens[$i - 2][0] == T_ABSTRACT) {

						if($tokens[$i - 1][0] == T_WHITESPACE AND $token[0] == T_STRING) {
							if(empty($extension_classes[$token[1]])) {
								$defined_classes[$token[1]] = TRUE;
							}
						}
					}

					// class A extends [class]
					if ($tokens[$i - 2][0] == T_EXTENDS AND $tokens[$i - 1][0] == T_WHITESPACE) {
						if($tokens[$i][0] == T_STRING) {
							if(empty($extension_classes[$token[1]])) {
								$defined_classes[$token[1]] = TRUE;
							}
						}
					}
				}
			}
		}
	}
}

$required = array_keys($required);

print "$counter files processed\n\n";
print "Used PHP Extensions: " . color(join(', ', $required), 'blue') . "\n\n";

$diff = array_diff($required, $extensions);

if($diff) {
	print "You need to install: " . color(join(', ', $diff), 'yellow') . "\n\n";
}

$classes = array_diff_key($used_classes, $defined_classes);

if($classes) {
	print "Missing classes: " . color(join(', ', array_keys($classes)), 'yellow') . "\n\n";
}

if($variable_classes) {
	print "Unknown class variables: " . color(join(', ', array_keys($variable_classes)), 'yellow') . "\n\n";
}

print round(microtime(TRUE) - $time, 2) . " seconds\n";