<?php
	require_once(dirname(__FILE__) . '/../functions.php');

	if (isLoggedInAdmin()) {
		logoutSuccess();
		$message = 'You are now logged out.';
	} else {
		$message = 'You are not logged in.';
	}

	require_once(dirname(__FILE__) . '/header.php');

	echo $message;

	require_once(dirname(__FILE__) . '/footer.php');
