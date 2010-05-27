<?php
// php run.php

require __DIR__.'/pecs/lib/pecs.php';
require __DIR__.'/../lib/Fu/Traffic.php';

set_error_handler('\pecs\errorToException', E_WARNING);

// include the tests
require __DIR__.'/tests/rest.php';
require __DIR__.'/tests/paths.php';
require __DIR__.'/tests/rules.php';

// run 'em
\pecs\run();

use Fu\Traffic as t;

function reset_request (){
	t::options('exit_after_callback', false);
	t::unexit();
};

function mimick_request ($path, $method = 'GET', $use_method_hack=false) {
	t::request_uri(false);
	$_SERVER['PATH_INFO'] = $path;

	if ($use_method_hack){
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$_POST['_method'] = $method;
	}
	else {
		$_SERVER['REQUEST_METHOD'] = $method;
		unset($_POST['_method']);
	}
}

function gather_info ($f) {
	ob_start();
	$f();
	return ob_get_clean();
}