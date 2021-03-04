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

	if (!isset($config['tasks']) || empty($config['tasks'])) {
		echo 'No tasks found.';
		require_once(dirname(__FILE__) . '/../footer.php');
		die();
	}

	$showForm = true;

	if (isset($_REQUEST['submit'])) {
		$showForm = false;

		$taskId = isset($_REQUEST['taskid']) ? $_REQUEST['taskid'] : FALSE;
		$reason = isset($_REQUEST['reason']) ? $_REQUEST['reason'] : FALSE;
		$date = isset($_REQUEST['date']) ? $_REQUEST['date'] : FALSE;
		$time = isset($_REQUEST['time']) ? $_REQUEST['time'] : FALSE;
		$scheduledTime = 0;
		$now = time();
		$status = 'scheduled';
		$user = isset($_SESSION['adminName']) ? $_SESSION['adminName'] : 'Admin';

		$err = false;
		if ($taskId == FALSE) { echo '<strong>Error:</strong> You must select a task.<br>'; $err = true; }
		if ($reason == FALSE) { echo '<strong>Error:</strong> You must give a reason.<br>'; $err = true; }
		if ($date == FALSE) { echo '<strong>Error:</strong> You must select a date.<br>'; $err = true; }
		if ($time == FALSE) { echo '<strong>Error:</strong> You must select a time.<br>'; $err = true; }

		if ($date != FALSE && $time != FALSE) {
			$scheduledTime = strtotime($date . ' ' . $time . ' UTC');
			if (($scheduledTime - time()) < 0) {
				echo '<strong>Error:</strong> Date/Time must be in the future.<br>';
				$err = true;
			}
		}

		if (!$err) {
			$stmt = $dbConn->prepare("INSERT INTO `scheduled` (taskId, reason, status, addedBy, addedAt, scheduledFor) VALUES (?, ?, ?, ?, ?, ?)");
			$stmt->bind_param("ssssdd", $taskId, $reason, $status, $user, $now, $scheduledTime);
			if (!$stmt->execute()) {
				echo '<strong>Error:</strong> There was an error scheduling this task.<br>';
				$err = true;
			} else {
				echo '<strong>Success:</strong> Task has been scheduled for ' . date('r', $scheduledTime) . '<br>';
			}
			$stmt->close();
		} else {
			$showForm = true;
		}
	}

	?>


	<?php if ($showForm) { ?>
	<div class="row justify-content-md-center">
		<div class="col-6">
			<div class="box">
				<form method="POST" action="<?=getBasePath()?>scheduled/new.php">
					<div class="header">
						<h2>Schedule Task</h2>
					</div>

					<div class="body">
						<p>
							Please choose a task to schedule below.
						</p>
						<fieldset>
							Task:<br>
							<select required id="taskid" name="taskid">
								<option selected disabled value="">Please select a task...</option>
								<?php foreach ($config['tasks'] as $taskid => $task) { ?>
									<?php if (isset($task['disabled']) && parseBool($task['disabled'])) { continue; } ?>

										<?php $selected = (isset($_REQUEST['taskid']) !== FALSE && $taskid == $_REQUEST['taskid'] ? 'selected' : ''); ?>
										<option <?=$selected?> value="<?=$taskid?>">
										<?=htmlspecialchars($task['name'])?>
										<?php if (isset($task['hidden']) && parseBool($task['hidden'])) { echo ' {Hidden}'; }; ?>
									</option>
								<?php } ?>
							</select>
						</fieldset>
						<br>
						<fieldset>
							Reason:<br>
							<textarea name="reason" required style="width: 100%"><?=htmlspecialchars(isset($_REQUEST['reason']) ? $_REQUEST['reason'] : '')?></textarea>
						</fieldset>
						<br>
						<fieldset>
							Date/Time (UTC):<br>
							<input name="date" required type="date" value="<?=htmlspecialchars(isset($_REQUEST['date']) ? $_REQUEST['date'] : '')?>">
							<input name="time" required type="time" value="<?=htmlspecialchars(isset($_REQUEST['time']) ? $_REQUEST['time'] : '')?>">
						</fieldset>
					</div>

					<div class="box-footer">
						<div class="form-actions">
							<div class="control-group">
								<div class="controls">
									<button class="btn btn-primary" type="submit" name="submit">Schedule</button>
								</div>
							</div>
						</div>
					</div>

				</form>
			</div>
		</div>
	</div>

	<br><br>

	<link href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.8.3/chosen.jquery.js"></script>
	<script>
		$('#taskid').chosen();
	</script>
	<?php } ?>

	<a class="btn btn-primary" href="<?=getBasePath()?>scheduled/">Back to scheduled tasks list.</a>

	<?php
	require_once(dirname(__FILE__) . '/../footer.php');
