<?php
	echo '<h2>Task</h2>';
	echo 'Please check and confirm that this is the task that you want to run, then press the run button, else press cancel.';

	define('NOFORM', true);

	showTask($_REQUEST['taskid'], true);
?>

	<form method="POST">
		<input type="hidden" name="run">
		<input type="hidden" name="taskid" value="<?=$_REQUEST['taskid']?>">
		<button class="btn btn-success" type="submit" name="submit">Run</button>
	</form>

	<form method="POST">
		<button class="btn btn-danger" type="submit" name="submit">Cancel</button>
	</form>
