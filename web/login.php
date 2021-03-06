<?php
	require_once(dirname(__FILE__) . '/../functions.php');

	$showForm = false;
	$message = '';

	if (isLoggedInAdmin()) {
		$message = 'You are already logged in.';
	} else if (isset($_POST) && !empty($_POST)) {
		if (checkLogin($_POST)) {
			loginSuccess($_POST);
			$message = 'Login successful.';
		} else {
			loginFailed($_POST);
			$message = 'Login failed.';

			$showForm = true;
		}
	} else {
		$showForm = true;
	}

	require_once(dirname(__FILE__) . '/header.php');

	echo $message;

	if ($showForm) { ?>
		<div class="row justify-content-md-center">
			<div class="col-6">
				<div class="box">
					<form method="POST" action="<?=getBasePath()?>login.php">
						<div class="header">
							<h2>Login</h2>
						</div>

						<div class="body">
							<p>
								Please enter your login details.
							</p>

							<fieldset>
								<div class="centered">
									<?php foreach (getLoginFields() as $fname => $f) { ?>
										<div class="control-group">
											<div class="controls">
												<div class="clearfix input-prepend">
													<?=ucfirst($fname)?>:
													<input type="<?=$f['type']?>" placeholder="<?=$fname?>" name="<?=$fname?>" value="">
												</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</fieldset>
						</div>

						<div class="box-footer">
							<div class="form-actions">
								<div class="control-group">
									<div class="controls">
										<button class="btn btn-primary" type="submit" name="submit">Login</button>
									</div>
								</div>
							</div>
						</div>

					</form>
				</div>
			</div>
		</div>
	<?php }

	require_once(dirname(__FILE__) . '/footer.php');
