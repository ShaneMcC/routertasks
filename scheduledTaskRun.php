#!/usr/bin/php
<?php
	require_once(dirname(__FILE__) . '/functions.php');

	if ($dbConn == null) {
		echo 'Scheduled tasks not enabled, or database not available.', "\n";
		die();
	}

	// Get the next task.
	$sTask = FALSE;
	$now = time();
	$result = $dbConn->query('SELECT * FROM scheduled WHERE `scheduledFor` <= ' . $now . ' AND `status` = "scheduled" ORDER BY `scheduledFor` asc, `id` asc LIMIT 1');
	if ($result) {
		if ($result->num_rows > 0) {
			$sTask = $result->fetch_assoc();
		}
		$result->close();
	}

	if ($sTask === FALSE) {
		echo 'No tasks scheduled.', "\n";
		die();
	}

	echo 'Next task to run: ';
	var_dump($sTask);
	$taskId = $sTask['id'];
	$now = time();
	$status = 'started';

	$stmt = $dbConn->prepare("UPDATE `scheduled` SET status = ?, startedAt = ? WHERE id = ?");
	$stmt->bind_param("ssd", $status, $now, $taskId);
	if (!$stmt->execute()) {
		echo 'Unable to start task.', "\n";
		die();
	}
	$stmt->close();

	echo 'Task started...', "\n";
	ob_start();
	// Run the task.
	$result = runTask($sTask['taskId']);
	$output = ob_get_flush();

	if (!$result && preg_match('#Could not get lock#', $output)) {
		// Reschedule this task.
		$now = 0;
		$status = 'scheduled';
		$stmt = $dbConn->prepare("UPDATE `scheduled` SET status = ?, startedAt = ? WHERE id = ?");
		$stmt->bind_param("ssd", $status, $now, $taskId);
		$stmt->execute();
		$stmt->close();

		echo 'Task rescheduled.', "\n";
		die();
	}

	$now = time();
	$status = $result ? 'finished' : 'failed';
	$stmt = $dbConn->prepare("UPDATE `scheduled` SET status = ?, finishedAt = ? WHERE id = ?");
	$stmt->bind_param("ssd", $status, $now, $taskId);
	$stmt->execute();
	$stmt->close();
	echo 'Task marked as: ', $status, "\n";

	// Do output separately incase it's too large, we still want to correctly
	// mark the task as finished.
	$stmt = $dbConn->prepare("UPDATE `scheduled` SET output = ? WHERE id = ?");
	$stmt->bind_param("sd", $output, $taskId);
	$stmt->execute();
	$stmt->close();
