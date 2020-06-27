#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	$options = getopt('h', array('task:', 'help', 'list', 'run', 'force'));
	if (isset($options['h']) || isset($options['help']) || (!isset($options['task']) && !isset($options['list']))) {
		echo 'Task Runner.', "\n";
		echo '', "\n";
		echo 'Usage: ', $argv[0], ' [-h|--help] <--list|--task <TaskId> [--run]>', "\n";
		echo '', "\n";
		exit(1);
	}

	if (isset($options['list'])) {
		foreach ($config['tasks'] as $taskid => $task) {
			if (isset($task['disabled']) && parseBool($task['disabled'])) { echo '[Disabled] '; }
			echo 'Id: ', $taskid, ' => Task: ', $task['name'], "\n";

		}

		exit(0);
	}

	if (isset($options['task'])) {
		if (isset($options['run'])) {
			if (isset($options['force'])) { define('FORCE_ENABLED', true); }
			runTask($options['task']);
		} else {
			showTask($options['task']);
		}
	}
