<?php

# Simple Mustache template engine
function render($tmpl, $params) {
	$value = null;
	return preg_replace_callback(
		'/\{\{\/([\w]+)\}\}/',
		function($m) use($params, $value) {

			if (isset($params[$m[1]])) {
				$value = $params[$m[1]];
			}

				return htmlspecialchars($params[$m[1]]);
			} else if (isset($params[$m[2]])) {
				return $params[$m[2]];
			} else {
				return $m[0];
			}
		},
		$tmpl);
}