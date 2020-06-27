<?php
	require_once(__DIR__ . '/vendor/autoload.php');
	require_once(__DIR__ . '/config.php');

	function getDeviceObject($dev) {
		global $config;

		if (!isset($config['routers'][$dev])) { return null; }

		// Connect to Router.
		if ($config['socket'] == 'OpenSSH') {
			$sock = new OpenSSHShellSocket($dev, $config['routers'][$dev]['user'], $config['routers'][$dev]['pass']);
			if (isset($config['routers'][$dev]['params'])) {
				$params = $config['routers'][$dev]['params'];
				$params = str_replace('%%DEV%%', $dev, $params);
				$sock->setParams($params);
			}
		} else {
			$sock = new SSHSocket($dev, $config['routers'][$dev]['user'], $config['routers'][$dev]['pass']);
		}

		$device = new CiscoSwitch('', '', '', $sock);
		return $device;
	}

	function doLog($data) {
		global $config, $__ID;

		if (!isset($__ID)) {
			$__ID = uniqid();
		}

		$source = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
		$date = date('r');

		if (isset($config['logging']) && $config['logging'] && isset($config['logfile'])) {
			@file_put_contents($config['logfile'], '[' . $date . '] ' . $source . ' ' . $__ID . ' ' . $data . "\n" , FILE_APPEND | LOCK_EX);
		}
	}

	function recursiveFindFiles($dir, $ext = 'php') {
		if (!file_exists($dir)) { return; }
		if (!is_array($ext)) { $ext = [$ext]; }

		$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS));
		foreach($it as $file) {
			if (in_array(pathinfo($file, PATHINFO_EXTENSION), $ext)) {
				yield $file;
			}
		}
	}

	function loadTasks() {
		global $config;

		$config['tasks'] = [];
		foreach (recursiveFindFiles($config['taskdir'], ['yaml', 'yml']) as $file) {
			$taskId = crc32($file);
			$config['tasks'][$taskId] = spyc_load_file($file);
			$config['tasks'][$taskId]['id'] = $taskId;
			$config['tasks'][$taskId]['file'] = $file;
		}
	}

	function getConnectedDevice($deviceName) {
		global $config, $__DEVICES;

		if (!isset($__DEVICES[$deviceName])) {
			$device = getDeviceObject($deviceName);

			if ($device == null) {
				echo 'Error connecting to ', $deviceName, ': Unknown device.', "\n\n";
				return FALSE;
			}

			try {
				$device->connect();

				$priv = trim($device->exec('show privilege'));
				if ($priv != 'Current privilege level is 15' && isset($config['routers'][$deviceName]['enable'])) {
					$device->enable($config['routers'][$deviceName]['enable']);
				}

				if (empty($priv)) {
					echo 'Error connecting to ', $deviceName, ': Unknown error.', "\n\n";
					return FALSE;
				}
			} catch (Exception $e) {
				echo 'Error connecting to ', $deviceName, ': ', $e->getMessage(), "\n\n";
				return FALSE;
			}

			$__DEVICES[$deviceName] = $device;
		}

		return $__DEVICES[$deviceName];
	}

	function parseBool($input) {
		$in = strtolower($input);
		return ($in === true || $in == 'true' || $in == '1' || $in == 'on' || $in == 'yes');
	}

	function checkTask($taskid, $html = false) {
		global $config;

		// Check that task exists.
		if (!isset($config['tasks'][$taskid])) {
			echo 'Task not found.', "\n";
			return false;
		}

		echo 'Beginning pre-flight checks for task...', "\n";
		if ($html) { echo '<br>'; }
		$task = $config['tasks'][$taskid];

		// Check task is not disabled.
		if (isset($task['disabled']) && parseBool($task['disabled'])) {
			echo 'Task is disabled';

			if (defined('FORCE_ENABLED')) {
				echo ', force-running.', "\n";
				if ($html) { echo '<br>'; }
			} else {
				echo ', aborting.', "\n";
				if ($html) { echo '<br>'; }
				return FALSE;
			}
		}

		// Check that we can get a lock on the lockfile...
		if (!empty($config['lockfile'])) {
			$needLock = true;

			if (isset($task['nolock']) && parseBool($task['nolock'])) {
				$needLock = false;
				echo 'Lock not required.', "\n";
			}

			if ($needLock) {
				echo 'Trying to get lock on: ', $config['lockfile'], ' for up to ', $config['locktimeout'], ' seconds.', "\n";
				if ($html) { echo '<br>'; }

				$fp = fopen($config['lockfile'], "w");
				$count = 0;
				while (true) {
					if (flock($fp, LOCK_EX | LOCK_NB)) {
						if ($count > 0) {
							echo "\n";
							if ($html) { echo '<br>'; }
						}
						echo 'Got lock after ', $count, ' seconds.', "\n";
						if ($html) { echo '<br>'; }
						break;
					} else {
						echo '.';
						flush();
						if (++$count >= $config['locktimeout']) {
							echo "\n";
							if ($html) { echo '<br>'; }
							echo 'Could not get lock on ', $config['lockfile'], ' after ', $count, ' seconds.', "\n";
							if ($html) { echo '<br>'; }
							return FALSE;
						}
						sleep(1);
					}
				}
			}
		}

		// Check that all routers needed are available.
		$checkedRouters = [];
		foreach ($task['steps'] as $stepid => $step) {
			if (isset($step['skip']) && parseBool($step['skip'])) { continue; }

			if (isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					if (isset($checkedRouters[$router])) { continue; }

					echo 'Step ', ($stepid + 1), ' requires ', $router, "\n";
					if ($html) { echo '<br>'; }

					$dev = getConnectedDevice($router);
					if ($dev == FALSE) {
						if ($html) { echo '<br>'; }
						return FALSE;
					}

					$checkedRouters[$router] = true;
				}
			}
		}

		// Good to go.
		return true;
	}


	function runTask($taskid, $html = false) {
		global $config;

		if (!checkTask($taskid, $html)) { return ''; }

		set_time_limit(0);
		$task = $config['tasks'][$taskid];

		doLog('Running task: ' . $task['name']);

		echo 'Running task: ', $task['name'], "\n";
		$stepCount = count($task['steps']);

		$canary = '! ' . md5(uniqid());

		$stop = false;

		for ($s = 0; $s < $stepCount; $s++) {
			if ($stop) { break; }

			$step = $task['steps'][$s];
			if ($html) {
				echo '<h2>';
				echo 'Step: ', ($s+1), ' / ', $stepCount, ' [ ', $step['name'], ' ]', "\n";
				echo '</h2>';
			} else {
				echo '==[ Begin Step: ', ($s+1), ' / ', $stepCount, ' ]=[ ', $step['name'], ' ]==========', "\n";
			}

			if (isset($step['skip']) && parseBool($step['skip'])) {
				echo 'Skipping step.', "\n";
			}

			if (isset($step['wait'])) {
				echo 'Waiting ', $step['wait'], ' seconds before continuing.', "\n";
				if ($html) { echo '<br>'; }
				sleep($step['wait']);
			}

			if (isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					if ($stop) { break; }
					if ($html) {
						echo '<h3>';
						echo 'Router: ', $router, "\n";
						echo '</h3>';
					} else {
						echo '### Router: ', $router, "\n";
					}

					$dev = getConnectedDevice($router);
					if ($dev == FALSE) { continue; }

					if (isset($step['commands'])) {
						if ($html) { echo '<pre>'; }
						$allOutput = '';
						foreach ($step['commands'] as $cid => $command) {
							if ($stop) { break; }

							echo '!!! Command: ', $command, "\n";
							$dev->writeln($command);
							$dev->writeln($canary);
							$output = $dev->getStreamData($canary . "\n") . "\n";
							$allOutput .= $output . "\n";

							if (!isset($step['silent']) || !parseBool($step['silent'])) {
								echo $output;
							}
							flush();
						}
						if ($html) { echo '</pre>'; }

						if (isset($step['validate'])) {
							foreach ($step['validate'] as $validate) {
								if ($stop) { break; }

								if (isset($validate['name'])) {
									if ($html) {
										echo '<strong>';
										echo 'Validate: ', $validate['name'], "\n";
										echo '</strong><br>';
									} else {
										echo '??? Validate: ', $validate['name'], "\n";
									}
								}

								$result = false;
								if (isset($validate['match'])) {
									$result = preg_match($validate['match'], $output);
									if (isset($validate['inverse']) && parseBool($validate['inverse'])) { $result = !$result; }
								}

								if (isset($validate['matchline'])) {
									$result = false;
									foreach (explode("\n", $output) as $line) {
										$res = preg_match($validate['matchline'], $line);

										if (isset($validate['inverse']) && parseBool($validate['inverse'])) { $res = !$res; }
										$result |= $res;
									}
								}

								echo 'Validation ', ($result ? 'Succeeded' : 'Failed'), "\n";
								if ($html) { echo '<br>'; }
								if (!$result && isset($validate['stop']) && parseBool($validate['stop'])) {
									echo 'Stopping further execution due to failed validation.', "\n";
									if ($html) { echo '<br>'; }
									$stop = true;
								}
							}
						}
					}

					echo "\n";
					flush();
				}
			}

			if ($html) {
				echo '<hr>';
			} else {
				echo '==[ End Step ', ($s+1), ' / ', $stepCount, ' ]=[ ', $step['name'], ' ]==========', "\n\n";
			}
			flush();
		}
	}

	function showTask($taskid, $html = false) {
		global $config;

		// Check that task exists.
		if (!isset($config['tasks'][$taskid])) {
			echo 'Task not found.', "\n";
			return false;
		}

		$task = $config['tasks'][$taskid];

		if ($html) { echo '<pre>'; }
		echo file_get_contents($task['file']);
		if ($html) { echo '</pre>'; }
	}

	loadTasks();
