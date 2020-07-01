#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	$options = getopt('h', array('task:', 'help', 'list', 'run', 'force', 'slug:'));
	if (isset($options['h']) || isset($options['help']) || (!isset($options['task']) && !isset($options['list']) && !isset($options['slug']))) {
		echo 'Task Runner.', "\n";
		echo '', "\n";
		echo 'Usage: ', $argv[0], ' [-h|--help] <--list|<--task <TaskId>|--slug <slug>> [--run]>', "\n";
		echo '', "\n";
		exit(1);
	}

	if (isset($options['list'])) {
		foreach ($config['tasks'] as $taskid => $task) {
			if (isset($task['disabled']) && parseBool($task['disabled'])) { echo '[Disabled] '; }
			if (isset($task['slug'])) {
				$slug = ' => Slug "' . $task['slug'] . '"';
			} else { $slug = ''; }

			echo 'Id: ', $taskid, $slug, ' => Task: ', $task['name'], "\n";

		}

		exit(0);
	}

	if (isset($options['slug'])) {
		unset($options['task']);
		foreach ($config['tasks'] as $taskid => $task) {
			if (isset($task['slug']) && strtolower($task['slug']) == strtolower($options['slug'])) {
				$options['task'] = $taskid;
				break;
			}
		}

		if (!isset($options['task'])) {
			echo 'Slug not found: ', $options['slug'], "\n";
			exit(1);
		}
	}

	if (isset($options['task'])) {
		if (isset($options['run'])) {
			if (isset($options['force'])) { define('FORCE_ENABLED', true); }
			runTask($options['task']);
		} else {
			showTask($options['task']);
		}
	}
