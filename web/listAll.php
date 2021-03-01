<?php
	require_once(dirname(__FILE__) . '/../functions.php');
	require_once(dirname(__FILE__) . '/header.php');

	if (!isLoggedInAdmin()) {
		echo 'Access Denied.';
		require_once(dirname(__FILE__) . '/footer.php');
		die();
	}

	$showDisabled = true;
	$showHidden = true;

	include(dirname(__FILE__) . '/selectform.php');

	?>

	<h2>Tasks with slugs</h2>
	<ul>
	<?php foreach ($config['tasks'] as $taskid => $task) { ?>
		<?php if (!isset($task['slug'])) { continue; } ?>
		<li>
			<a href="<?=getBasePath()?>?slug=<?=$task['slug']?>">
				[<?=htmlspecialchars($task['slug'])?>]
				<?=htmlspecialchars($task['name'])?>
				<?php if (isset($task['hidden']) && parseBool($task['hidden'])) { echo ' {Hidden}'; }; ?>
				<?php if (isset($task['disabled']) && parseBool($task['disabled'])) { echo ' {Disabled}'; }; ?>
			</a>
		</li>
	<?php } ?>
	</ul>

	<?php
	require_once(dirname(__FILE__) . '/footer.php');
