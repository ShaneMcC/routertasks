#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	$options = getopt('h', array('task:', 'help', 'list', 'run'));
	if (isset($options['h']) || isset($options['help']) || (!isset($options['task']) && !isset($options['list']))) {
		echo 'Task Runner.', "\n";
		echo '', "\n";
		echo 'Usage: ', $argv[0], ' [-h|--help] <--list|--task <TaskId> [--run]>', "\n";
		echo '', "\n";
		exit(1);
	}

	if (isset($options['list'])) {
		foreach ($config['tasks'] as $taskid => $task) {
			if (isset($task['disabled']) && parseBool($task['disabled'])) { continue; }
			echo 'Id: ', $taskid, ' => Task: ', $task['name'], "\n";
		}

		exit(0);
	}

	if (isset($options['task'])) {
		if (isset($options['run'])) {
			runTask($options['task']);
		} else {
			showTask($options['task']);
		}
	}
