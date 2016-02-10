<?php
/**
 * Parse a mustache template string using the given array.
 *
 * @param string $template The mustache template
 * @param array $data The data to inject into the template
 * @param boolean $silent True to throw exceptions when missing data
 * @return string
 */
class Mustache
{
	public function __construct($template, array $data = NULL, $silent = TRUE)
	{
		$this->template = $template;
		$this->data = $data ?: array();
		$this->silent = (bool) $silent;
	}

	public function set($key, $value)
	{
		if(is_scalar($key))
		{
			$key = array($key => $value);
		}

		foreach($key as $i => $value)
		{
			$this->data[$i] = $value;
		}
	}

	public function render()
	{
		$data = $this->data;
		$template = $this->template;
		$silent = $this->silent;
		$e = function($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };

		// If this looks like a filepath, try to load it
		if( ! preg_match('~[\n\{]~', $template) AND is_file($template))
		{
			$template = file_get_contents($template);
		}

		// Remove comments
		$template = preg_replace('~{{!.+?}}~', '', $template);

		// Lists, conditionals, and inverted sections
		preg_match_all('~{{ *([#^]) *(\w+) *}}(.*?){{ *\/ *\2 *}}~s', $template, $matches, PREG_SET_ORDER);

		// Get nested blocks taken care of
		foreach($matches as $set)
		{
			$var = $set[2];
			$str = $set[3];
			$value = '';

			if($set[1] == '^')// Inverted Section
			{
				$value = empty($data[$var]) ? $str : '';
			}
			elseif(empty($data[$var])) // Silent fail
			{
				if( ! $silent) throw new Exception("Missing mustache variable '$var'");
			}
			elseif(is_array($data[$var])) // List
			{
				foreach($data[$var] as $i => $row)
					$value .= new self($str, $row + $data + array('i' => $i + 1));
			}
			elseif($data[$var] instanceof Closure) // Callback
			{
				$value = $data[$var]($str, $data);
			}
			else
			{
				$value = trim($str);
			}

			$template = str_replace($set[0], $value, $template);
		}

		// Replace variables
		if(preg_match_all('~{{ *(\&)*(\w+) *}}~x', $template, $matches, PREG_SET_ORDER))
		{
			foreach($matches as $set)
			{
				$value = '';

				if(empty($data[$set[2]]))
				{
					if( ! $silent) throw new Exception("Missing mustache variable '{$set[2]}'");
				}
				else
				{
					if($data[$set[2]] instanceof Closure) // Callback
					{
						$value = $data[$set[2]]($set[0], $data);
					}
					else
					{
						$value = $set[1] ? $data[$set[2]] : $e($data[$set[2]]);
					}
				}

				$template = str_replace($set[0], $value, $template);
			}
		}

		// Some cleanup to remove extra whitespace
		return preg_replace("~\n[\s]*\n+~", "\n\n", $template);
	}

	public function __toString()
	{
		try {
			return $this->render();
		} catch(\Exception $e) {
			return '' . $e;
		}
	}
}