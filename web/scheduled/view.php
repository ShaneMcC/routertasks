<?php
	require_once(dirname(__FILE__) . '/../../functions.php');
	setBasePathDir(dirname(getBasePathDir()));
	require_once(dirname(__FILE__) . '/../header.php');

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

	?>

	<table class="table table-sm table-striped table-bordered">
		<tbody>
			<?php
				$taskid = $sTask['taskId'];
				$taskName = 'Unknown Task';
				if (isset($config['tasks'][$taskid])) {
					$taskName = $config['tasks'][$taskid]['name'];
				}

				$tableClass = '';
				if ($sTask['status'] == 'scheduled') { $tableClass = ''; }
				else if ($sTask['status'] == 'started') { $tableClass = 'table-primary'; }
				else if ($sTask['status'] == 'finished') { $tableClass = 'table-success'; }
				else if ($sTask['status'] == 'failed') { $tableClass = 'table-danger'; }
				else if ($sTask['status'] == 'cancelled') { $tableClass = 'table-warning'; }

				echo '<tr>';
				echo '    <th>Scheduled Task ID</th>';
				echo '    <td>', $sTask['id'], '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '    <th>Task</th>';
				echo '    <td>', htmlspecialchars($taskName), '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '    <th>Reason</th>';
				echo '    <td>', nl2br(htmlspecialchars($sTask['reason'])), '</td>';
				echo '</tr>';
				echo '<tr class="' . $tableClass . '">';
				echo '    <th>Status:</th><td> ', $sTask['status'], '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '    <th>Added By:</th><td> ', htmlspecialchars($sTask['addedBy']), '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '    <th>Added At:</th><td> ', date('r', $sTask['addedAt']), '</td>';
				echo '</tr>';
				echo '<tr>';
				echo '    <th>Scheduled For:</th><td> ', date('r', $sTask['scheduledFor']), '</td>';
				echo '</tr>';
				if ($sTask['startedAt'] > 0) {
					echo '<tr>';
					echo '    <th>Started At:</th><td> ', date('r', $sTask['startedAt']), '</td>';
					echo '</tr>';
				}
				if ($sTask['finishedAt'] > 0) {
					echo '<tr>';
					echo '    <th>Finished At:</th><td> ', date('r', $sTask['finishedAt']), '</td>';
					echo '</tr>';
				}
			?>
		</tbody>
	</table>


	<?php
	if (isLoggedInAdmin() && $sTask['status'] == 'scheduled') {
		echo '        <a class="btn btn-danger" href="', getBasePath(), 'scheduled/cancel.php?id=', $sTask['id'], '">Cancel Task</a>';
	}
	echo '        <a class="btn btn-primary" href="', getBasePath(), 'scheduled/">Back to scheduled tasks list.</a>';

	echo '<br><br>';

	if ($sTask['output'] == NULL) {
		showTask($taskid, true);
		echo '<script>hljs.initHighlightingOnLoad();</script>';
	} else {
		showTaskOuput($sTask['output'], true);
	}

	require_once(dirname(__FILE__) . '/../footer.php');
