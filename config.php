<?php
	// Enable logging
	$config['logging'] = true;
	$config['logfile'] = __DIR__ . '/logs.txt';

	// Lock file to ensure only 1 task can run at a time.
	$config['lockfile'] = __DIR__ . '/run.lock';
	$config['locktimeout'] = 10;

	// Location of tasks
	$config['taskdir'] = __DIR__ . '/tasks/';

	// This can be OpenSSH or SSH.
	$config['socket'] = 'OpenSSH';

	// List of routers to allow tasks to log in to.
	//
	// The array used as the value should contain:
	//   - user: Username to login with
	//   - pass: Password to login with
	//   - (Optional) params: Additional params to pass to openssh when logging in if using openssh socket.
	//   - (Optional) aliases: Array of aliases that a router may be known by.
	//
	//   Params can contain the following variables which will be replaced:
	//    - %%DEV%% - Device name
	$config['routers'] = [];

	// Default Password for admin functions.
	// This might not be used if checkLogin and getLoginFields are overridden
	// in config.local
	$config['adminPassword'] = 'password';

	// Database credentials for scheduled tasks feature.
	// Disabled if null.
	$config['database']['server'] = null;
	$config['database']['username'] = 'routertasks';
	$config['database']['password'] = 'routertasks';
	$config['database']['database'] = 'routertasks';

	// $config['routers']['router1'] = ['user' => 'admin', 'pass' => 'password', 'params' => '-o ProxyCommand="ssh bastion -W %%DEV%%:22"';
	// $config['routers']['router2'] = ['user' => 'admin', 'pass' => 'somepassword'];

	// Load in local config if it exists.
	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}

	if (!function_exists('getLoginFields')) {
		function getLoginFields() {
			return ['password' => ['type' => 'password']];
		}
	}

	if (!function_exists('checkLogin')) {
		function checkLogin($fields) {
			global $config;

			return isset($fields['password']) && ($fields['password'] == $config['adminPassword']);
		}
	}

	if (!function_exists('loginSuccess')) {
		function loginSuccess($fields) {
			$_SESSION['isAdmin'] = true;
			$_SESSION['adminName'] = 'admin';
		}
	}

	if (!function_exists('loginFailed')) {
		function loginFailed($fields) { }
	}

	if (!function_exists('logoutSuccess')) {
		function logoutSuccess() {
			unset($_SESSION['isAdmin']);
			unset($_SESSION['adminName']);
		}
	}
