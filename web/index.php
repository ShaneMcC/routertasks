<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/header.php');

	if (isset($_REQUEST['taskid'])) {
		if (isset($_REQUEST['run'])) {
			include(dirname(__FILE__) . '/doTask.php');
		} else {
			include(dirname(__FILE__) . '/showTask.php');
		}
		echo '<br><br>';
	}

	if (!defined('NOFORM')) {
		include(dirname(__FILE__) . '/selectform.php');
	}

	require_once(dirname(__FILE__) . '/footer.php');
?>
