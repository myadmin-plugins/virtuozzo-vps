<?php

namespace Detain\MyAdminVirtuozzo;

use Detain\Virtuozzo\Virtuozzo;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVirtuozzo
 */
class Plugin {

	public static $name = 'Virtuozzo VPS';
	public static $description = 'Allows selling of Virtuozzo VPS Types.  Virtuozzo is an operating system-level server virtualization solution designed to centralize server management and consolidate workloads, which reduces overhead by reducing the number of physical servers required. Organizations use Virtuozzo for server consolidation, disaster recovery, and server workload agility. Virtuozzo does not generate a virtual machine that resides on a host OS so that users can run multiple operating systems. Instead it creates isolated virtual private servers (VPSs) on a single physical server. For instance, the software can run multiple Linux VPSs, but not Linux and Windows at the same time on the same server. Each VPS performs exactly like a stand-alone server and can be rebooted independently.  More info at https://virtuozzo.com/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['type'] == get_service_define('VIRTUOZZO')) {
			myadmin_log(self::$module, 'info', 'Virtuozzo Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		if ($event['type'] == get_service_define('VIRTUOZZO')) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if ($event['type'] == get_service_define('VIRTUOZZO')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$virtuozzo = new Virtuozzo(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $virtuozzo->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'Virtuozzo editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link(self::$module, 'choice=none.reusable_virtuozzo', 'icons/database_warning_48.png', 'ReUsable Virtuozzo Licenses');
			$menu->add_link(self::$module, 'choice=none.virtuozzo_list', 'icons/database_warning_48.png', 'Virtuozzo Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.virtuozzo_licenses_list', 'whm/createacct.gif', 'List all Virtuozzo Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('crud_virtuozzo_list', '/../vendor/detain/crud/src/crud/crud_virtuozzo_list.php');
		$loader->add_requirement('crud_reusable_virtuozzo', '/../vendor/detain/crud/src/crud/crud_reusable_virtuozzo.php');
		$loader->add_requirement('get_virtuozzo_licenses', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('virtuozzo_licenses_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_licenses_list.php');
		$loader->add_requirement('virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_list.php');
		$loader->add_requirement('get_available_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('activate_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/reusable_virtuozzo.php');
		$loader->add_requirement('class.Virtuozzo', '/../vendor/detain/virtuozzo-vps/src/Virtuozzo.php');
		$loader->add_requirement('vps_add_virtuozzo', '/vps/addons/vps_add_virtuozzo.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_virtuozzo_cost', 'Virtuozzo VPS Cost Per Slice:', 'OpenVZ VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_VIRTUOZZO_COST'));
		$settings->add_text_setting(self::$module, 'Slice Costs', 'vps_slice_ssd_virtuozzo_cost', 'SSD Virtuozzo VPS Cost Per Slice:', 'SSD OpenVZ VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_SSD_VIRTUOZZO_COST'));
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_virtuozzo_server', 'Virtuozzo NJ Server', NEW_VPS_VIRTUOZZO_SERVER, 12, 1);
		$settings->add_select_master(self::$module, 'Default Servers', self::$module, 'new_vps_ssd_virtuozzo_server', 'SSD Virtuozzo NJ Server', NEW_VPS_SSD_VIRTUOZZO_SERVER, 13, 1);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_virtuozzo', 'Out Of Stock Virtuozzo Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_VIRTUOZZO'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, 'Out of Stock', 'outofstock_ssd_virtuozzo', 'Out Of Stock SSD Virtuozzo Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_SSD_VIRTUOZZO'), ['0', '1'], ['No', 'Yes']);
	}

}
