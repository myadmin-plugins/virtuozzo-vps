<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_virtuozzo define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Virtuozzo Vps',
	'description' => 'Allows selling of Virtuozzo Server and VPS License Types.  More info at https://www.netenberg.com/virtuozzo.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a virtuozzo license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-virtuozzo-vps',
	'repo' => 'https://github.com/detain/myadmin-virtuozzo-vps',
	'version' => '1.0.0',
	'type' => 'service',
	'hooks' => [
		/*'function.requirements' => ['Detain\MyAdminVirtuozzo\Plugin', 'Requirements'],
		'vps.settings' => ['Detain\MyAdminVirtuozzo\Plugin', 'Settings'],
		'vps.activate' => ['Detain\MyAdminVirtuozzo\Plugin', 'Activate'],
		'vps.change_ip' => ['Detain\MyAdminVirtuozzo\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVirtuozzo\Plugin', 'Menu'] */
	],
];
