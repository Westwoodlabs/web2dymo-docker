<?php
	
	spl_autoload_register(function($class_name) {
		$file = __DIR__.'/'.str_replace('\\', '/', $class_name).'.php';
		if (is_readable($file)) {
			include_once $file;
		}
	});
?>