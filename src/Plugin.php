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
			self::$module.'.queue_backup' => [__CLASS__, 'getQueueBackup'],
			self::$module.'.queue_restore' => [__CLASS__, 'getQueueRestore'],
			self::$module.'.queue_install_cpanel' => [__CLASS__, 'getQueueInstallCpanel'],
			self::$module.'.queue_enable' => [__CLASS__, 'getQueueEnable'],
			self::$module.'.queue_destroy' => [__CLASS__, 'getQueueDestroy'],
			self::$module.'.queue_delete' => [__CLASS__, 'getQueueDelete'],
			self::$module.'.queue_reinstall_os' => [__CLASS__, 'getQueueReinstallOs'],
			self::$module.'.queue_update_hdsize' => [__CLASS__, 'getQueueUpdateHdsize'],
			self::$module.'.queue_enable_quota' => [__CLASS__, 'getQueueEnableQuota'],
			self::$module.'.queue_disable_quota' => [__CLASS__, 'getQueueDisableQuota'],
			self::$module.'.queue_start' => [__CLASS__, 'getQueueStart'],
			self::$module.'.queue_stop' => [__CLASS__, 'getQueueStop'],
			self::$module.'.queue_restart' => [__CLASS__, 'getQueueRestart'],
			self::$module.'.queue_setup_vnc' => [__CLASS__, 'getQueueSetupVnc'],
			self::$module.'.queue_reset_password' => [__CLASS__, 'getQueueResetPassword'],
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
			$menu->add_link(self::$module, 'choice=none.reusable_virtuozzo', 'images/icons/database_warning_48.png', 'ReUsable Virtuozzo Licenses');
			$menu->add_link(self::$module, 'choice=none.virtuozzo_list', 'images/icons/database_warning_48.png', 'Virtuozzo Licenses Breakdown');
			$menu->add_link(self::$module.'api', 'choice=none.virtuozzo_licenses_list', '/images/whm/createacct.gif', 'List all Virtuozzo Licenses');
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('crud_virtuozzo_list', '/../vendor/detain/crud/src/crud/crud_virtuozzo_list.php');
		$loader->add_page_requirement('crud_reusable_virtuozzo', '/../vendor/detain/crud/src/crud/crud_reusable_virtuozzo.php');
		$loader->add_requirement('get_virtuozzo_licenses', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_page_requirement('virtuozzo_licenses_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_licenses_list.php');
		$loader->add_page_requirement('virtuozzo_list', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo_list.php');
		$loader->add_requirement('get_available_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('activate_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_requirement('get_reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/virtuozzo.inc.php');
		$loader->add_page_requirement('reusable_virtuozzo', '/../vendor/detain/myadmin-virtuozzo-vps/src/reusable_virtuozzo.php');
		$loader->add_requirement('class.Virtuozzo', '/../vendor/detain/virtuozzo-vps/src/Virtuozzo.php');
		$loader->add_page_requirement('vps_add_virtuozzo', '/vps/addons/vps_add_virtuozzo.php');
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

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueBackup(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Backup', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/backup.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestore(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restore', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restore.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueInstallCpanel(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Install CPanel', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/install_cpanel.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnable(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDestroy(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Destroy', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/destroy.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDelete(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Delete', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/delete.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueReinstallOsupdateHdsize(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reinstall Osupdate Hdsize', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reinstall_osupdate_hdsize.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueEnableQuota(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Enable Quota', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/enable_quota.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueDisableQuota(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Disable Quota', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/disable_quota.sh.tpl');
			$event->stopPropagation();
		}
	}


	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Start', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/start.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueStop(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Stop', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/stop.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueRestart(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Restart', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/restart.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueSetupVnc(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Setup Vnc', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/setup_vnc.sh.tpl');
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueueResetPassword(GenericEvent $event) {
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			myadmin_log(self::$module, 'info', self::$name.' Queue Reset Password', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$smarty = new \TFSmarty();
			$smarty->assign([
				'vps_id' => $serviceClass->getId(),
				'vps_vzid' => is_numeric($serviceClass->getVzid()) ? (in_array($event['type'], [get_service_define('KVM_WINDOWS'), get_service_define('CLOUD_KVM_WINDOWS')]) ? 'windows'.$serviceClass->getVzid() : 'linux'.$serviceClass->getVzid()) : $serviceClass->getVzid(),
				'email' => $GLOBALS['tf']->accounts->cross_reference($serviceClass->getCustid()),
				'domain' => DOMAIN,
				'param1' => $event['param1']
			]);
			echo $smarty->fetch(__DIR__.'/../templates/reset_password.sh.tpl');
			$event->stopPropagation();
		}
	}


}
