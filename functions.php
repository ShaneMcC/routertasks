<?php
	require_once(__DIR__ . '/vendor/autoload.php');
	require_once(__DIR__ . '/config.php');

    use shanemcc\PhpRouter\Implementations\HasCanary;
    use shanemcc\PhpRouter\Implementations\CiscoSwitch;
    use shanemcc\PhpRouter\Sockets\OpenSSHShellSocket;
    use shanemcc\PhpRouter\Sockets\SSHSocket;

	$dbConn = null;
	if ($config['database']['server'] != null) {
		$dbConn = new mysqli($config['database']['server'], $config['database']['username'],$config['database']['password'], $config['database']['database']);

		if ($dbConn->connect_error) {
			$dbConn = null;
		}
	}

	session_start();

	function getBasePathDir() {
		global $_BASEPATHDIR;
		$result = isset($_BASEPATHDIR) ? $_BASEPATHDIR : dirname($_SERVER['SCRIPT_FILENAME']);
		return preg_replace('#^/+#', '/', $result . '/');
	}

	function setBasePathDir($dir) {
		global $_BASEPATHDIR;
		$_BASEPATHDIR = $dir;
	}

	function getBasePath() {
		global $_BASEPATHDIR;

		$basepath = getBasePathDir();
		$basepath = preg_replace('#^' . preg_quote($_SERVER['DOCUMENT_ROOT']) . '#', '/', $basepath);
		$basepath = preg_replace('#^/+#', '/', $basepath);

		return $basepath;
	}

	function getDeviceFromAlias($deviceName) {
		global $config, $__DEVICE_ALIASES;

		// Return name if known already.
		if (isset($config['routers'][$deviceName])) { return $deviceName; }
		if (isset($__DEVICE_ALIASES[$deviceName])) { return $__DEVICE_ALIASES[$deviceName]; }

		// Build aliases list if we can't find it already.
		foreach ($config['routers'] as $name => $dev) {
			if (isset($dev['aliases'])) {
				foreach ($dev['aliases'] as $alias) {
					if (!isset($__DEVICE_ALIASES[$alias])) {
						$__DEVICE_ALIASES[$alias] = $name;
					}
				}
			}
		}

		// Try again.
		if (isset($__DEVICE_ALIASES[$deviceName])) { return $__DEVICE_ALIASES[$deviceName]; }

		return FALSE;
	}

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

		$device = null;
		if (isset($config['routers'][$dev]['type'])) {
			$ns = 'shanemcc\\PhpRouter\\Implementations\\';
			$devType = $ns.$config['routers'][$dev]['type'];

			if (class_exists($devType)) {
				$class = new ReflectionClass($devType);

				if ($class->isSubclassOf(new ReflectionClass($ns.'NetworkDevice'))) {
					$device = new $devType('', '', '', $sock);
				}
			}
		} else {
			$device = new CiscoSwitch('', '', '', $sock);
		}

		return $device;
	}

	function getCanary($device) {
		if ($device instanceof HasCanary) { return $device->getCanary(); }
		return FALSE;
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
			$relName = preg_replace('/^' . preg_quote($config['taskdir'], '/') . '/', '', $file);

			$taskId = crc32($file);
			$config['tasks'][$taskId] = spyc_load_file($file);
			$config['tasks'][$taskId]['id'] = $taskId;
			$config['tasks'][$taskId]['file'] = $file;

			if (!isset($config['tasks'][$taskId]['name'])) {
				$config['tasks'][$taskId]['name'] = preg_replace('#\.ya?ml$#', '', $relName);
			}
			if (!isset($config['tasks'][$taskId]['slug'])) {
				$config['tasks'][$taskId]['slug'] = preg_replace('#\.ya?ml$#', '', $relName);
			}
		}

		uksort($config['tasks'], function ($a, $b) use ($config) {
			$a = $config['tasks'][$a];
			$b = $config['tasks'][$b];

			$aName = isset($a['slug']) ? $a['slug'] : $a['id'];
			$bName = isset($b['slug']) ? $b['slug'] : $b['id'];

			return strcmp($aName, $bName);
		});
	}

	function getConnectedDevice($deviceName) {
		global $config, $__DEVICES;

		$deviceName = getDeviceFromAlias($deviceName);

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

		if ($html) { echo '<strong>'; }
		echo 'Beginning pre-flight checks for task...', "\n";
		if ($html) { echo '</strong><pre>'; }
		$task = $config['tasks'][$taskid];

		// Check task is not disabled.
		if (isset($task['disabled']) && parseBool($task['disabled'])) {
			echo 'Task is disabled';

			if (defined('FORCE_ENABLED')) {
				echo ', force-running.', "\n";
			} else {
				echo ', aborting.', "\n";
				if ($html) { echo '</pre>'; }
				return FALSE;
			}
		}

		// Check that we can get a lock on the lockfile...
		if (!empty($config['lockfile'])) {
			$needLock = true;

			if (isset($task['lock']) && !parseBool($task['lock'])) {
				$needLock = false;
				echo 'Lock not required.', "\n";
			} else if (isset($task['nolock']) && parseBool($task['nolock'])) {
				$needLock = false;
				echo 'Lock not required.', "\n";
			}

			if ($needLock) {
				echo 'Trying to get lock on: ', $config['lockfile'], ' for up to ', $config['locktimeout'], ' seconds.', "\n";

				$fp = fopen($config['lockfile'], "w");
				$count = 0;
				while (true) {
					if (flock($fp, LOCK_EX | LOCK_NB)) {
						if ($count > 0) {
							echo "\n";
						}
						echo 'Got lock after ', $count, ' seconds.', "\n";
						break;
					} else {
						echo '.';
						flush();
						if (++$count >= $config['locktimeout']) {
							echo "\n";
							echo 'Could not get lock on ', $config['lockfile'], ' after ', $count, ' seconds.', "\n";
							return FALSE;
						}
						sleep(1);
					}
				}
			}
		}

		// Check that all routers needed are available and that we support
		// them.
		$checkedRouters = [];
		foreach ($task['steps'] as $stepid => $step) {
			if (isset($step['skip']) && parseBool($step['skip'])) { continue; }

			if (isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					if (isset($checkedRouters[$router])) { continue; }

					echo 'Step ', ($stepid + 1), ' requires ', $router, "\n";

					$dev = getConnectedDevice($router);
					if ($dev == FALSE) {
						if ($html) { echo '</pre>'; }
						return FALSE;
					}

					if (getCanary($dev) === FALSE) {
						echo 'Unable to support ', $router, ' - unable to obtain canary.', "\n";
						if ($html) { echo '</pre>'; }
						return FALSE;
					}

					$checkedRouters[$router] = true;
				}
			}
		}

		// Good to go.
		if ($html) { echo '</pre>'; }
		return true;
	}

	function runTask($taskid, $html = false) {
		global $config;

		set_time_limit(0);

		if (!checkTask($taskid, $html)) { return FALSE; }

		$finalResult = TRUE;
		$task = $config['tasks'][$taskid];

		doLog('Running task: ' . $task['name']);

		if ($html) { echo '<h2>'; }
		echo 'Running task: ', $task['name'], "\n";
		if ($html) { echo '</h2>'; }

		$stepCount = count($task['steps']);

		$stop = FALSE;

		for ($s = 0; $s < $stepCount; $s++) {
			if ($stop) { break; }

			$step = $task['steps'][$s];
			if ($html) {
				echo '<h3>';
				echo 'Step: ', ($s+1), ' / ', $stepCount, ' [', htmlspecialchars($step['name']), ']', "\n";
				echo '</h3>';
			} else {
				echo '==[ Begin Step: ', ($s+1), ' / ', $stepCount, ' ]=[ ', $step['name'], ' ]==========', "\n";
			}

			$skip = false;
			if (isset($step['skip']) && parseBool($step['skip'])) {
				echo 'Skipping step.', "\n";
				$skip = true;
			}

			if (!$skip && isset($step['wait'])) {
				echo 'Waiting ', $step['wait'], ' seconds before continuing.', "\n";
				if ($html) { echo '<br>'; }
				sleep($step['wait']);
			}

			if (!$skip && isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					if ($stop) { break; }
					if ($html) {
						echo '<h4>';
						echo 'Router: ', $router, "\n";
						echo '</h4>';
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

							if ($html) { echo '<strong>'; }
							echo '!!! Command: ', $command, "\n";
							if ($html) { echo '</strong>'; }

							$canary = getCanary($dev);
							$dev->writeln($canary);
							$dev->getStreamData($canary . "\n");

							$dev->writeln($command);
							$dev->writeln($canary);
							$output = $dev->getStreamData($canary . "\n") . "\n";
							$allOutput .= $output . "\n";

							if (!isset($step['silent']) || !parseBool($step['silent'])) {
								echo ($html) ? htmlspecialchars($output) : $output;
							}
							flush();
						}
						if ($html) { echo '</pre>'; }

						if (isset($step['validate'])) {
							foreach ($step['validate'] as $validate) {
								if ($stop) { break; }

								if (isset($validate['name'])) {
									if ($html) {
										echo '<h5>';
										echo 'Validate: ', $validate['name'], "\n";
										echo '</h5>';
									} else {
										echo '??? Validate: ', $validate['name'], "\n";
									}
								}

								$result = FALSE;
								if (isset($validate['match'])) {
									if ($html) {
										echo '<strong>Match:</strong> <code>', htmlspecialchars($validate['match']), '</code><br>';
									} else {
										echo 'Match:', $validate['match'], "\n";
									}
									$result = preg_match($validate['match'], $output);
									if (isset($validate['inverse']) && parseBool($validate['inverse'])) { $result = !$result; }
								} else if (isset($validate['matchline'])) {
									if ($html) {
										echo '<strong>Match Line:</strong> <code>', htmlspecialchars($validate['matchline']), '</code><br>';
									} else {
										echo 'Match Line:', $validate['matchline'], "\n";
									}
									$result = FALSE;
									foreach (explode("\n", $output) as $line) {
										$res = preg_match($validate['matchline'], $line);

										if (isset($validate['inverse']) && parseBool($validate['inverse'])) { $res = !$res; }
										$result |= $res;
									}
								}

								if ($html) { echo '<span class="', ($result ? 'yes' : 'no'), '"><strong>'; }
								echo 'Validation ', ($result ? 'Succeeded' : 'Failed'), "\n";
								if ($html) { echo '</strong></span><br>'; }
								if (!$result && isset($validate['stop']) && parseBool($validate['stop'])) {
									echo 'Stopping further execution due to failed validation.', "\n";
									if ($html) { echo '<br>'; }
									$stop = TRUE;
									$finalResult = FALSE;
								}
								if ($html) { echo '<br>'; }
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

		foreach ($task['steps'] as $stepid => $step) {
			if (isset($step['skip']) && parseBool($step['skip'])) { continue; }

			if (isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					$dev = getConnectedDevice($router);
					if ($dev != FALSE) {
						$dev->disconnect();
					}
				}
			}
		}

		return $finalResult;
	}

	function showTaskOuput($taskOutput, $html = false) {
		if (!$html) {
			echo $taskOutput;
			return;
		}

		// TODO: This is a bit horrible.
		$inPre = false;
		$inValidate = false;
		foreach (explode("\n", $taskOutput) as $line) {
			if (preg_match('#^==\[ End (.*) \]=\[ (.*) \]==+$#', $line)) {
				if ($inPre) { echo '</pre>'; }
				$inPre = false;
				echo '<hr>';
			}

			if (preg_match('#^Running task:*#', $line)) {
				$inPre = false;
				echo '</pre>';
				echo '<h2>', htmlspecialchars($line), '</h2>';
			}

			if (preg_match('#^Beginning pre-flight checks.*#', $line)) {
				echo '<strong>', htmlspecialchars($line), '</strong>';
				echo '<pre>';
				$inPre = true;
				continue;
			}

			if (preg_match('#^==\[ Begin (.*) \]=\[ (.*) \]==+$#', $line, $m)) {
				echo '<h3>';
				echo htmlspecialchars($m[1]), ' [', htmlspecialchars($m[2]), ']', "\n";
				echo '</h3>';
			}

			if (preg_match('#^\#\#\# Router: (.*)#', $line, $m)) {
				if ($inPre) { echo '</pre>'; }

				echo '<h4>';
				echo 'Router: ', htmlspecialchars($m[1]), "\n";
				echo '</h4>';

				echo '<pre>';
				$inPre = true;
				continue;
			}

			if (preg_match('#^!!! Command: (.*)#', $line, $m)) {
				echo '<strong>', htmlspecialchars($line), '</strong>', "\n";
				continue;
			}

			if ($inValidate) {
				if (preg_match('#^(.*):(.*)#', $line, $m)) {
					echo '<strong>', htmlspecialchars($m[1]), ':</strong> <code>', htmlspecialchars($m[2]), '</code><br>';
				}

				if (preg_match('#^Validation (.*)#', $line, $m)) {
					$inValidate = false;

					echo '<span class="', ($m[1] == 'Succeeded' ? 'yes' : 'no'), '"><strong>';
					echo htmlspecialchars($line), "\n";
					echo '</strong></span><br>';
					echo '<br>';
				}
			} else if (preg_match('#^\?\?\? Validate: (.*)#', $line, $m)) {
				if ($inPre) { echo '</pre>'; }
				$inPre = false;

				echo '<h5>';
				echo 'Validate: ', htmlspecialchars($m[1]), "\n";
				echo '</h5>';
				$inValidate = true;
			}

			if ($inPre) {
				echo htmlspecialchars($line), "\n";
			} else if (preg_match('#^(Skipping step|Waiting.*seconds|Stopping further)#', $line)) {
				echo htmlspecialchars($line), "\n";
				echo '<br>';
			}
		}

		echo '</pre>';
	}

	function showTask($taskid, $html = false) {
		global $config;

		// Check that task exists.
		if (!isset($config['tasks'][$taskid])) {
			echo 'Task not found.', "\n";
			return false;
		}

		$task = $config['tasks'][$taskid];

		if (isset($task['message'])) {
			if (!is_array($task['message']) || isset($task['message']['text'])) { $task['message'] = [$task['message']]; }

			foreach ($task['message'] as $message) {
				if (!is_array($message)) { $message = ['text' => $message]; }

				if ($html) {
					$alertType = isset($message['type']) ? $message['type'] : 'info';
					echo '<div class="alert alert-', $alertType, '" role="alert">';
				} else {
					$alertChar = '-';
					if (isset($message['type'])) {
						switch (strtolower($message['type'])) {
							case 'danger': $alertChar = '@'; break;
							case 'warning': $alertChar = '!'; break;
							case 'info': $alertChar = '='; break;
							default: break;
						}
					}
					echo str_repeat($alertChar, 60), "\n", $alertChar, ' ';
				}

				if (isset($message['title'])) {
					if ($html) { echo '<h5 class="alert-heading"><strong>'; }
					echo $message['title'], "\n";
					if ($html) { echo '</strong></h5>'; } else { echo str_repeat($alertChar, 30), "\n", $alertChar, ' '; }
				}

				echo $message['text'], "\n";
				if ($html) { echo '</div>'; } else { echo str_repeat($alertChar, 60), "\n\n"; }
			}
		}

		if ($html) { echo '<pre class="hljs"><code class="yaml">'; }
		echo htmlspecialchars(file_get_contents($task['file']));
		if ($html) { echo '</code></pre>'; }

		// Check that all routers needed are available and that we support
		// them.
		$checkedRouters = [];
		foreach ($task['steps'] as $stepid => $step) {
			if (isset($step['skip']) && parseBool($step['skip'])) { continue; }

			if (isset($step['routers'])) {
				foreach ($step['routers'] as $router) {
					if (isset($checkedRouters[$router])) { continue; }

					$dev = getDeviceObject(getDeviceFromAlias($router));
					if ($dev == FALSE) {
						if ($html) { echo '<strong>Warning:<strong> '; } else { echo '**Warning:** '; }
						echo 'Unknown router: ', $router, "\n";
						if ($html) { echo '<br>'; }
					}

					if (getCanary($dev) === FALSE) {
						if ($html) { echo '<strong>Warning:<strong> '; } else { echo '**Warning:** '; }
						echo 'Unsupported router: ', $router, "\n";
						if ($html) { echo '<br>'; }
					}

					$checkedRouters[$router] = true;
				}
			}
		}
	}

	function isLoggedInAdmin() {
		return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
	}

	loadTasks();
