<?php
	echo '<h1>Task Results</h1>';

	// Ensure output to browser is as-it-happens
	header('Content-Encoding: none');
	if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
	ini_set('output_buffering', 'off');
	ini_set('zlib.output_compression', false);
	while (@ob_end_flush());
	ini_set('implicit_flush', true);
	ob_implicit_flush(true);
	echo str_pad("",1048," "), "\n";

	$finalResult = runTask($_REQUEST['taskid'], true);

	echo '<script>';
	echo '$("nav.fixed-top").removeClass("bg-dark");';
	if ($finalResult) {
		echo '$("nav.fixed-top").addClass("bg-success");';
	} else {
		echo '$("nav.fixed-top").addClass("bg-danger");';
	}
	echo '</script>';
