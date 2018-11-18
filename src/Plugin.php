<?php

namespace Detain\MyAdminVirtuozzo;

use Detain\Virtuozzo\Virtuozzo;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminVirtuozzo
 */
class Plugin
{
	public static $name = 'Virtuozzo VPS';
	public static $description = 'Allows selling of Virtuozzo VPS Types.  Virtuozzo is an operating system-level server virtualization solution designed to centralize server management and consolidate workloads, which reduces overhead by reducing the number of physical servers required. Organizations use Virtuozzo for server consolidation, disaster recovery, and server workload agility. Virtuozzo does not generate a virtual machine that resides on a host OS so that users can run multiple operating systems. Instead it creates isolated virtual private servers (VPSs) on a single physical server. For instance, the software can run multiple Linux VPSs, but not Linux and Windows at the same time on the same server. Each VPS performs exactly like a stand-alone server and can be rebooted independently.  More info at https://virtuozzo.com/';
	public static $help = '';
	public static $module = 'vps';
	public static $type = 'service';

	/**
	 * Plugin constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			//self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.queue' => [__CLASS__, 'getQueue'],
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event)
	{
		$serviceClass = $event->getSubject();
		if ($event['type'] == get_service_define('VIRTUOZZO')) {
			myadmin_log(self::$module, 'info', 'Virtuozzo Activation', __LINE__, __FILE__);
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event)
	{
		if ($event['type'] == get_service_define('VIRTUOZZO')) {
			myadmin_log(self::$module, 'info', self::$name.' Deactivation', __LINE__, __FILE__);
			$serviceClass = $event->getSubject();
			$GLOBALS['tf']->history->add(self::$module.'queue', $serviceClass->getId(), 'delete', '', $serviceClass->getCustid());
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
    public static function getSettings(GenericEvent $event)
    {
        /**
         * @var \MyAdmin\Settings $settings
         **/
        $settings = $event->getSubject();
		$settings->add_text_setting(self::$module, _('Slice Costs'), 'vps_slice_virtuozzo_cost', _('Virtuozzo VPS Cost Per Slice'), _('OpenVZ VPS will cost this much for 1 slice.'), $settings->get_setting('VPS_SLICE_VIRTUOZZO_COST'));
		$settings->add_text_setting(self::$module, _('Slice Costs'), 'vps_slice_ssd_virtuozzo_cost', _('SSD Virtuozzo VPS Cost Per Slice'), _('SSD OpenVZ VPS will cost this much for 1 slice.'), $settings->get_setting('VPS_SLICE_SSD_VIRTUOZZO_COST'));
		$settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_vps_virtuozzo_server', _('Virtuozzo NJ Server'), NEW_VPS_VIRTUOZZO_SERVER, 12, 1);
		$settings->add_select_master(_(self::$module), _('Default Servers'), self::$module, 'new_vps_ssd_virtuozzo_server', _('SSD Virtuozzo NJ Server'), NEW_VPS_SSD_VIRTUOZZO_SERVER, 13, 1);
		$settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_virtuozzo', _('Out Of Stock Virtuozzo Secaucus'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_VIRTUOZZO'), ['0', '1'], ['No', 'Yes']);
		$settings->add_dropdown_setting(self::$module, _('Out of Stock'), 'outofstock_ssd_virtuozzo', _('Out Of Stock SSD Virtuozzo Secaucus'), _('Enable/Disable Sales Of This Type'), $settings->get_setting('OUTOFSTOCK_SSD_VIRTUOZZO'), ['0', '1'], ['No', 'Yes']);
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getQueue(GenericEvent $event)
	{
		if (in_array($event['type'], [get_service_define('VIRTUOZZO'), get_service_define('SSD_VIRTUOZZO')])) {
			$vps = $event->getSubject();
			myadmin_log(self::$module, 'info', self::$name.' Queue '.ucwords(str_replace('_', ' ', $vps['action'])).' for VPS '.$vps['vps_hostname'].'(#'.$vps['vps_id'].'/'.$vps['vps_vzid'].')', __LINE__, __FILE__);
			$server_info = $vps['server_info'];
			if (!file_exists(__DIR__.'/../templates/'.$vps['action'].'.sh.tpl')) {
				myadmin_log(self::$module, 'error', 'Call '.$vps['action'].' for VPS '.$vps['vps_hostname'].'(#'.$vps['vps_id'].'/'.$vps['vps_vzid'].') Does not Exist for '.self::$name, __LINE__, __FILE__);
			} else {
				$smarty = new \TFSmarty();
				$smarty->assign($vps);
				$event['output'] = $event['output'] . $smarty->fetch(__DIR__.'/../templates/'.$vps['action'].'.sh.tpl');
			}
			$event->stopPropagation();
		}
	}
}
