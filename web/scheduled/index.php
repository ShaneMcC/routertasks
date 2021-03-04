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
	?>

	<a class="btn btn-primary" href="<?=getBasePath()?>/scheduled/new.php">Schedule New Task</a>
	<br><br>
	<table class="table table-sm table-striped table-bordered">
		<thead>
			<tr>
				<th width="100px">Scheduled ID</th>
				<th width="200px">Task</th>
				<th>Reason</th>
				<th>Status</th>
				<th width="200px">Actions</th>
			</tr>
		</thead>

		<tbody>
			<?php
				$result = $dbConn->query('SELECT * FROM scheduled ORDER BY `id` desc');
				if ($result) {
					if ($result->num_rows == 0) {
						echo '<tr>';
						echo '<td colspan=5>There are currently no scheduled tasks.</td>';
						echo '</tr>';
					} else {
						while ($row = $result->fetch_assoc()) {
							$taskid = $row['taskId'];
							$taskName = 'Unknown Task';
							if (isset($config['tasks'][$taskid])) {
								$taskName = $config['tasks'][$taskid]['name'];
							}

							$tableClass = '';

							if ($row['status'] == 'scheduled') { $tableClass = ''; }
							else if ($row['status'] == 'started') { $tableClass = 'table-primary'; }
							else if ($row['status'] == 'finished') { $tableClass = 'table-success'; }
							else if ($row['status'] == 'failed') { $tableClass = 'table-danger'; }
							else if ($row['status'] == 'cancelled') { $tableClass = 'table-warning'; }


							echo '<tr class="' . $tableClass . '">';
							echo '    <td>', $row['id'], '</td>';
							echo '    <td>', htmlspecialchars($taskName), '</td>';
							echo '    <td>', nl2br(htmlspecialchars($row['reason'])), '</td>';
							echo '    <td>';
							echo '        <strong>Status:</strong> ', $row['status'], '<br>';
							echo '        <strong>Added By:</strong> ', htmlspecialchars($row['addedBy']), '<br>';
							echo '        <strong>Added At:</strong> ', date('r', $row['addedAt']), '<br>';
							echo '        <strong>Scheduled For:</strong> ', date('r', $row['scheduledFor']), '<br>';
							if ($row['startedAt'] > 0) {
								echo '        <strong>Started At:</strong> ', date('r', $row['startedAt']), '<br>';
							}
							if ($row['finishedAt'] > 0) {
								echo '        <strong>Started At:</strong> ', date('r', $row['finishedAt']), '<br>';
							}
							echo '    </td>';
							echo '    <td>';
							echo '        <a class="btn btn-primary btn-sm" href="', getBasePath(), '/scheduled/view.php?id=', $row['id'], '">View Task</a>';
							if ($row['status'] == 'scheduled') {
								echo '        <a class="btn btn-danger btn-sm" href="', getBasePath(), '/scheduled/cancel.php?id=', $row['id'], '">Cancel Task</a>';
							}
							echo '    </td>';
							echo '</tr>';
						}
					}
					$result->close();
				}
			?>
		</tbody>
	</table>

	<?php
	require_once(dirname(__FILE__) . '/../footer.php');
