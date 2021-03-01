<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/header.php');

	if (isLoggedInAdmin()) {
		unset($_SESSION['isAdmin']);

		echo 'You are now logged out.';
	} else {
		echo 'You are not logged in.';
	}

	require_once(dirname(__FILE__) . '/footer.php');
