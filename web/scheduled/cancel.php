<?php
	require_once(dirname(__FILE__) . '/../../functions.php');
	setBasePathDir(dirname(getBasePathDir()));
	require_once(dirname(__FILE__) . '/../header.php');

	if (!isLoggedInAdmin()) {
		echo 'Access Denied.';
		require_once(dirname(__FILE__) . '/../footer.php');
		die();
	}

	if ($dbConn == null) {
		echo 'Scheduled tasks not enabled, or database not available.';
		require_once(dirname(__FILE__) . '/../footer.php');
		die();
	}

	if (!isset($_REQUEST['id']) || !preg_match('#^[0-9]+$#', $_REQUEST['id'])) {
		echo 'A valid Scheduled Task ID is required.';
		require_once(dirname(__FILE__) . '/../footer.php');
		die();
	}

	$id = $_REQUEST['id'];
	$result = $dbConn->query('SELECT * FROM scheduled WHERE `id` = "' . $id . '"');
	if ($result) {
		if ($result->num_rows == 0) {
			echo 'A known Scheduled Task ID is required.';
			require_once(dirname(__FILE__) . '/../footer.php');
			die();
		} else {
			$sTask = $result->fetch_assoc();
		}

		$result->close();
	}

	if ($sTask['status'] == 'scheduled') {
		$taskId = $sTask['id'];
		$now = time();
		$status = 'cancelled';
		$user = isset($_SESSION['adminName']) ? $_SESSION['adminName'] : 'Admin';

		$newReason = $sTask['reason'] . "\n\n" . 'Cancelled by ' . $user . ' at ' . date('r', $now);

		$stmt = $dbConn->prepare("UPDATE `scheduled` SET status = ?, reason = ? WHERE id = ?");
		$stmt->bind_param("ssd", $status, $newReason, $taskId);
		if (!$stmt->execute()) {
			echo '<strong>Error:</strong> There was an error cancelling this task.<br>';
		} else {
			echo '<strong>Success:</strong> Task has been cancelled.<br>';
		}
		$stmt->close();

	} else {
		echo '<strong>Error:</strong> Only tasks that have not yet started can be cancelled.';
	}

	echo '        <a class="btn btn-primary" href="', getBasePath(), '/scheduled/">Back to scheduled tasks list.</a>';

	require_once(dirname(__FILE__) . '/../footer.php');
