<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/header.php');

	$activeTask = FALSE;
	$runTask = FALSE;

	if (isset($_REQUEST['slug'])) {
		foreach ($config['tasks'] as $taskid => $task) {
			if (isset($task['disabled']) && parseBool($task['disabled'])) { continue; }
			if (isset($task['slug']) && strtolower($task['slug']) == strtolower($_REQUEST['slug'])) {
				$activeTask = $taskid;
				break;
			}
		}
	}

	if ($activeTask === FALSE && isset($_REQUEST['taskid'])) {
		$activeTask = isset($config['tasks'][$_REQUEST['taskid']]) ? $_REQUEST['taskid'] : FALSE;
		$runTask = $activeTask !== FALSE && isset($_REQUEST['run']);
	}


	if ($activeTask !== FALSE && isset($config['tasks'][$activeTask])) {
		if ($runTask) {
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
