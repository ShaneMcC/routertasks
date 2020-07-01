<?php
	$task = $config['tasks'][$activeTask];

	echo '<h2>Task :: ', htmlspecialchars($config['tasks'][$activeTask]['name']), '</h2>';
	echo 'Please check and confirm that this is the task that you want to run, then press the run button, else press cancel.';

	define('NOFORM', true);

	showTask($activeTask, true);
?>

	<script>hljs.initHighlightingOnLoad();</script>
	<style>

	</style>

	<form method="POST" action="<?=getBasePath()?>">
		<input type="hidden" name="run">
		<input type="hidden" name="taskid" value="<?=$activeTask?>">
		<button class="btn btn-success" type="submit" name="submit">Run</button>
	</form>
	<br>
	<form method="POST">
		<button class="btn btn-danger" type="submit" name="submit">Cancel</button>
	</form>
