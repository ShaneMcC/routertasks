# RouterTasks

Simple web-app to provide a web-ui for running pre-scripted tasks against a set of (cisco-only, currently) routers.

For example, you may want to allow a service desk to be able to move (specific) static routes around when requested by customers without requiring that all service desk staff have access to the routers.

## Deploying

This currently expects to run out of a directory on a server that has access to log into all the devices in question. The underlying logic to log into the devices and execute the commands is handled by [shanemcc/phprouter](https://github.com/shanemcc/phprouter).

`git clone` the repository into a directory, create and edit config.local.php and then point a webserver at the `/web` folder.

## Updating

You can update routertasks by pulling an updated copy of the repo (`git pull`) and then re-running `composer install`.

## Basic Usage

Initial configuration is performed in the `config.local.php` file to define the list of routers and how to log into them, eg something like this:

```php
<?php
        $routerOptions = ['user' => 'automation', 'pass' => 'automation1', 'enable' => 'enablepass'];

        $config['routers']['router1'] = $routerOptions;
        $config['routers']['router2'] = $routerOptions;

        $config['routers']['router3'] = $routerOptions;
        $config['routers']['router4'] = $routerOptions;
```

Tasks are then configured as yaml files within the `tasks/` directory.

An example task to list static routes on some devices:

```yaml
name: View Router Static Routes
lock: false
steps:
  - name: Show Current Static Routes
    routers:
      - router1
      - router2
    commands:
      - show run | inc (ip|ipv6) route
```

Tasks must have a `name` that will be shown when running the task. They can also optionally bypass the locking feature (eg if they don't change anything) with `lock: false` or can be disabled entirely with `disabled: true` to prevent them showing up as a runnable task.

After that you must define some steps to run.

Each step then has a `name` that will be shown when running the step, a set of `routers` that the step will be run on (in order) and a set of `commands` to run.

When running a task the task runner will by default attempt to get a lock on the configured lock file to ensure only 1 task at a time is ever running (unless a task is explicitly defined to not need the lock), in addition it will attempt to connect and log in to all devices used by any step before starting execution, to mitigate against half-completed executions caused by a device being unavailable.

## Advanced Usage

A more advanced task that moves some static routes around between devices could look something like this:

```yaml
name: Move 1.1.1.0/24 static route to router3 and router4
steps:
  - name: Check router exists on router1 and router2
    routers:
      - router1
      - router2
    commands:
      - show run | inc ip route
    silent: true
    validate:
      - name: Look for existing route
        matchline: "#^ip route 1.1.1.0 255.255.255.0 2.2.2.2$#i"
        stop: true

  - name: Remove route from router1 and router2
    routers:
      - router1
      - router2
    commands:
      - conf t
      - no ip route 1.1.1.0 255.255.255.0 2.2.2.2
      - end

  - name: Add route to router3 and router4
    routers:
      - router3
      - router4
    commands:
      - conf t
      - ip route 1.1.1.0 255.255.255.0 2.2.2.2
      - end

  - name: Save router configs
    routers:
      - router1
      - router2
      - router3
      - router4
    commands:
      - write mem
```

This task is slightly more involved, it access more routers and actually makes some changes.

We have added a `validate` section to the first step, this lets us compare the output of the previously run commands for a regex to check that the current state is what we expect.

This validate step will fail if the regex given does not match at least 1 line of the output. (You can also use `inverse: true` to change this to fail if the regex does match a line).

By default, a failed validation will not stop further execution unless `stop: true` is given as well.

## Pull Requests
Pull requests are appreciated and welcome.

## Comments, Questions, Bugs, Feature Requests etc.

Bugs and Feature Requests should be raised on the [issue tracker on github](https://github.com/ShaneMcC/routertasks/issues), and I'm happy to receive code pull requests via github.

I can be found idling on various different IRC Networks, but the best way to get in touch would be to message "Dataforce" on Quakenet, or drop me a mail (email address is in my [github profile](https://github.com/ShaneMcC))

## Screenshots

### Task Selection
![Task Selection](/screenshots/1-choose-task.png?raw=true "Task Selection")

### Task Confirmation
![Task Confirmation](/screenshots/2-view-task.png?raw=true "Task Confirmation")

### Running Task
![Running Task](/screenshots/3-running-task.png?raw=true "Running Task")

### Completed Task
![Completed Task](/screenshots/4-completed-task.png?raw=true "Completed Task")

### Failed Task
![Failed Task](/screenshots/5-failed-task.png?raw=true "Failed Task")
