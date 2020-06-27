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

	// List of routers to permit.
	//
	// These will be checked in turn until the IP is found.
	//
	// The array used as the value should contain:
	//   - user: Username to login with
	//   - pass: Password to login with
	//   - (Optional) params: Additional params to pass to openssh when logging in if using openssh socket.
	//
	//   Params can contain the following variables which will be replaced:
	//    - %%DEV%% - Device name
	$config['routers'] = array();

	// $config['routers']['router1'] = array('user' => 'admin', 'pass' => 'password', 'params' => '-o ProxyCommand="ssh bastion -W %%DEV%%:22"');
	// $config['routers']['router2'] = array('user' => 'admin', 'pass' => 'somepassword');

	// Load in local config if it exists.
	if (file_exists(dirname(__FILE__) . '/config.local.php')) {
		require_once(dirname(__FILE__) . '/config.local.php');
	}
