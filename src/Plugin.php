<?php

namespace Detain\MyAdminVirtuozzo;

use Detain\Virtuozzo\Virtuozzo;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Virtuozzo Vps';
	public static $description = 'Allows selling of Virtuozzo Server and VPS License Types.  More info at https://www.netenberg.com/virtuozzo.php';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a virtuozzo license. Allow 10 minutes for activation.';
	public static $module = 'vps';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'vps.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			myadmin_log('licenses', 'info', 'Virtuozzo Activation', __LINE__, __FILE__);
			function_requirements('activate_virtuozzo');
			activate_virtuozzo($license->get_ip(), $event['field1']);
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_FANTASTICO) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$virtuozzo = new Virtuozzo(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $virtuozzo->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Virtuozzo editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module, 'choice=none.reusable_virtuozzo', 'icons/database_warning_48.png', 'ReUsable Virtuozzo Licenses');
			$menu->add_link($module, 'choice=none.virtuozzo_list', 'icons/database_warning_48.png', 'Virtuozzo Licenses Breakdown');
			$menu->add_link($module.'api', 'choice=none.virtuozzo_licenses_list', 'whm/createacct.gif', 'List all Virtuozzo Licenses');
		}
	}

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

	public static function getSettings(GenericEvent $event) {
		$module = 'vps';
		$settings = $event->getSubject();
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_virtuozzo_cost', 'Virtuozzo VPS Cost Per Slice:', 'OpenVZ VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_VIRTUOZZO_COST'));
		$settings->add_text_setting($module, 'Slice Costs', 'vps_slice_ssd_virtuozzo_cost', 'SSD Virtuozzo VPS Cost Per Slice:', 'SSD OpenVZ VPS will cost this much for 1 slice.', $settings->get_setting('VPS_SLICE_SSD_VIRTUOZZO_COST'));
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_virtuozzo_server', 'Virtuozzo NJ Server', NEW_VPS_VIRTUOZZO_SERVER, 12, 1);
		$settings->add_select_master($module, 'Default Servers', $module, 'new_vps_ssd_virtuozzo_server', 'SSD Virtuozzo NJ Server', NEW_VPS_SSD_VIRTUOZZO_SERVER, 13, 1);
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_virtuozzo', 'Out Of Stock Virtuozzo Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_VIRTUOZZO'), array('0', '1'), array('No', 'Yes',));
		$settings->add_dropdown_setting($module, 'Out of Stock', 'outofstock_ssd_virtuozzo', 'Out Of Stock SSD Virtuozzo Secaucus', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_SSD_VIRTUOZZO'), array('0', '1'), array('No', 'Yes',));
	}

}
