<div align="center" id="top">
  <img src="./.github/app.gif" alt="Project RouterOS" />

  &#xa0;

  <!-- <a href="https://projectrouteros.netlify.app">Demo</a> -->
</div>

<h1 align="center">Project RouterOS</h1>

<p align="center">
  <img alt="Github top language" src="https://img.shields.io/github/languages/top/comitIdn/project-routeros?color=56BEB8">

  <img alt="Github language count" src="https://img.shields.io/github/languages/count/comitIdn/project-routeros?color=56BEB8">

  <img alt="Repository size" src="https://img.shields.io/github/repo-size/comitIdn/project-routeros?color=56BEB8">

  <img alt="License" src="https://img.shields.io/github/license/comitIdn/project-routeros?color=56BEB8">

  <!-- <img alt="Github issues" src="https://img.shields.io/github/issues/comitIdn/project-routeros?color=56BEB8" /> -->

  <!-- <img alt="Github forks" src="https://img.shields.io/github/forks/comitIdn/project-routeros?color=56BEB8" /> -->

  <!-- <img alt="Github stars" src="https://img.shields.io/github/stars/comitIdn/project-routeros?color=56BEB8" /> -->
</p>

<!-- Status -->

<!-- <h4 align="center"> 
	🚧  Project RouterOS 🚀 Under construction...  🚧
</h4> 

<hr> -->

<p align="center">
  <a href="#dart-about">About</a> &#xa0; | &#xa0;
  <a href="#sparkles-features">Features</a> &#xa0; | &#xa0;
  <a href="#rocket-technologies">Technologies</a> &#xa0; | &#xa0;
  <a href="#white_check_mark-requirements">Requirements</a> &#xa0; | &#xa0;
  <a href="#checkered_flag-starting">Starting</a> &#xa0; | &#xa0;
  <a href="#memo-license">License</a> &#xa0; | &#xa0;
  <a href="https://github.com/comitIdn" target="_blank">Author</a>
</p>

<br>

## :dart: About ##

The **Project RouterOS** is a PHP-based API wrapper to manage MikroTik devices using RouterOS. This project includes various functions to manage Hotspot, PPPoE, Firewall, DNS, and more.
The `RouterOSConnection.php` file provides an API for connecting to MikroTik devices and executing various commands like managing Hotspot, PPPoE users, and much more.
The `RouterOSAPI.php` file further extends these functionalities with additional tools and system commands.

## :sparkles: Features ##
The following key features are included in this project:
#### From `RouterOSConnection.php`:
- [x] **PPPoE Management**: Retrieve, add, update, and delete PPPoE users, servers, and profiles;\
- [x] **Hotspot Management**: Manage Hotspot users, servers, and profiles;\
- [x] **Firewall Management**: Retrieve and manage firewall rules;\
- [x] **DNS Management**: Retrieve DNS cache and settings;\
- [x] **Queue Management**: Retrieve and add queue configurations;\
- [x] **System Management**: Retrieve system resources, reboot the router, and manage system scripts;\
- [x] **Log Management**: Retrieve system logs;\
- [x] **Custom Command Execution**: Execute custom RouterOS commands.

#### From `RouterOSAPI.php`:
- [x] **Connection Management**: Easily connect to MikroTik RouterOS using IP, username, and password.
- [x] **Command Execution**: Execute RouterOS commands and retrieve results in a structured format.
- [x] **Data Parsing**: Automatically parse responses from RouterOS API into associative arrays for easy manipulation.
- [x] **Error Handling**: Comprehensive error handling to capture and respond to connection issues or invalid commands.
- [x] **Extensibility**: The library is designed to be extended and customized according to your specific needs.

## :rocket: Technologies ##
The following technologies are used in this project:
- [PHP](https://www.php.net/)
- [comitidn/routeros-api](https://github.com/comitIdn/RouterOS-API)

## :white_check_mark: Requirements ##
Before starting :checkered_flag:, you need to have [Git](https://git-scm.com) and [Composer](https://getcomposer.org) installed.

## :checkered_flag: Starting ##
### Installation:

```bash
# Clone the project
$ git clone https://github.com/comitIdn/project-routeros

# Access the project directory
$ cd project-routeros

# Install dependencies
$ composer install
```

### Usage:

Once installed, you can use the classes provided in `RouterOSConnection.php` and `RouterOSAPI.php` to interact with MikroTik devices.

### Example:

Here’s an example of how to use `RouterOSConnection.php` to interact with MikroTik:

```php
require 'vendor/autoload.php';
use App\RouterOSConnection;

// Initialize the connection
$routerOS = new RouterOSConnection('host', 'username', 'password');

// Get all PPPoE users
$pppoeUsers = $routerOS->getPppoeUsers();
print_r($pppoeUsers);

// Add a new PPPoE user
$newPppoeUser = $routerOS->addPppoeUser('username1', 'password123', 'pppoe', 'default');
print_r($newPppoeUser);

// Reboot the router
$reboot = $routerOS->rebootRouter();
print_r($reboot);
```

### Available Methods:

#### From `RouterOSConnection.php`:

1. **PPPoE Management**:
```php
getPppoeUsers(); // Retrieve all PPPoE users
getPppoeUserDetails($name); // Get details of a specific PPPoE user by name
addPppoeUser($name, $password, $service, $profile, $comment); // Add a new PPPoE user
setPppoeUser($name, $password, $profile, $comment); // Update PPPoE user details
removePppoeUser($name); // Remove a PPPoE user
getActivePppoeUsers(); // Get all active PPPoE users
removeActivePppoeUser($id); // Remove an active PPPoE user by session ID
```

2. **Hotspot Management**:
```php
getHotspotUsers(); // Retrieve all Hotspot users
addHotspotUser($name, $password, $profile, $limitUptime, $comment); // Add a new Hotspot user
setHotspotUser($name, $password, $limitUptime, $comment, $profile); // Update Hotspot user details
removeHotspotUser($name); // Remove a Hotspot user
getActiveHotspotUsers(); // Get all active Hotspot users
removeActiveHotspotUser($id); // Remove an active Hotspot user by session ID
```

3. **Firewall Management**:
```php
getFirewallFilterRules(); // Get all firewall filter rules
getFirewallNatRules(); // Get all firewall NAT rules
getFirewallMangleRules(); // Get all firewall mangle rules
getFirewallRawRules(); // Get all firewall raw rules
```

4. **DNS Management**:
```php
getDnsCache(); // Get all DNS cache entries
getDnsSettings(); // Get DNS settings
```

5. **System Management**:
```php
getSystemResources(); // Get system resources (CPU, memory, uptime, etc.)
rebootRouter(); // Reboot the router
```

6. **Queue Management**:
```php
getQueues(); // Get all queues
addQueue($name, $target, $maxLimit); // Add a queue
```

7. **Tools**:
```php
ping($address); // Ping a specific IP address
getNetwatch(); // Get active Netwatch entries
```

8. **Log Management**:
```php
getLogs(); // Retrieve system logs
```

9. **Custom Command Execution**:
```php
customCommand($command); // Execute custom RouterOS commands
```

#### From `RouterOSAPI.php`:

1. **System Script Management**:
```php
addSystemScript($name, $source); // Add a new system script
removeSystemScript($name); // Remove a system script
getSystemScripts(); // Get all system scripts
getSystemScriptByName($name); // Get specific script details by name
```

2. **Scheduler Management**:
```php
addScheduler($name, $onEvent, $startDate, $startTime, $interval); // Add a new scheduler
removeScheduler($name); // Remove a scheduler
```

3. **Firewall Management (Extended)**:
```php
getFirewallFilterRules(); // Get all firewall filter rules
getFirewallNatRules(); // Get all firewall NAT rules
getFirewallMangleRules(); // Get all firewall mangle rules
getFirewallRawRules(); // Get all firewall raw rules
```

4. **Tools (Extended)**:
```php
ping($address); // Ping a specific IP address
getNetwatch(); // Get active Netwatch entries
```
:memo: License
This project is licensed under the MIT License. For more details, see the LICENSE file.

Made with :heart: by <a href="https://github.com/comitIdn" target="_blank">Bang AL</a>


<a href="#top">Back to top</a># project-routeros
# project-routeros