<?php if (isset($config['tasks']) && !empty($config['tasks'])) { ?>
		<div class="row justify-content-md-center"">
			<div class="col-6">
				<div class="box">
					<form method="POST" action="<?=getBasePath()?>">
						<div class="header">
							<h2>Choose Task</h2>
						</div>

						<div class="body">
							<p>
								Please choose a task to run below, you will be given a chance to review the contents of the task before running it.
							</p>
							<fieldset>
								<select id="taskid" name="taskid">
									<?php foreach ($config['tasks'] as $taskid => $task) { ?>
										<?php if (isset($task['disabled']) && parseBool($task['disabled'])) { continue; } ?>
										<?php $selected = ($activeTask !== FALSE && $taskid == $activeTask ? 'selected' : ''); ?>
										<option <?=$selected?> value="<?=$taskid?>"><?=htmlspecialchars($task['name'])?></option>
									<?php } ?>
								</select>
							</fieldset>
						</div>

						<div class="box-footer">
							<div class="form-actions">
								<div class="control-group">
									<div class="controls">
										<button class="btn btn-primary" type="submit" name="submit">Run Task</button>
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
