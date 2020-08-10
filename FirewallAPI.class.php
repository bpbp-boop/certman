<?php
// vim: set ai ts=4 sw=4 ft=php:
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2014 Schmooze Com Inc.
//
namespace FreePBX\modules\Certman;

/**
 * Implements a trivial API for use with Certman
 */
class FirewallAPI {

	/** Is firewall available on this machine? */
	private $fw = false;

	/** Firewall object */
	private $fwobj;

	public function __construct() {
		// Is firewall enabled and active?
		try {
			$this->fwobj = \FreePBX::Firewall();
			$this->fw = $this->fwobj->isEnabled();
		} catch (\Exception $e) {
			// Firewall not active, or not enabled, don't do anything
			return;
		}
	}

	/**
	 * Is firewall available on this machine?
	 *
	 * @return bool
	 */
	public function isAvailable() {
		return $this->fw;
	}
	
	/**
	 * getAdvancedSettings
	 *
	 * @return void
	 */
	public function getAdvancedSettings(){
		if($this->fw){
			return $this->fwobj->getConfig("advancedsettings");
		}
		return false;
	}
	public function setAdvancedSettings($adv){
		if($this->fw){
			$this->fwobj->setConfig("advancedsettings", $adv);
		}
	}
	
	public function enableLeRules(){
		if($this->fw){
			$this->fwobj->enableLeRules();
		}
	}

	/**
	 * disableLERules
	 *
	 * @return void
	 */
	public function disableLeRules(){
		if($this->fw){
			$this->fwobj->disableLeRules();
		}
	}

	/**
	 * LE_Rules_Status
	 *
	 * @param  string $status
	 * @return bool
	 */
	public function LE_Rules_Status($status = 'disabled'){
		$module_info = module_getinfo('firewall', MODULE_STATUS_ENABLED);

		if(!preg_match('/disabled$|enabled$/', $status) || !isset($module_info["firewall"])){
			/**
			 * Returns false if FW module is not installed or status not matched.
			 */
			return false;
		}

		if($this->fw){
			/**
			 * Setting up LE rules only if FW is enabled
			 */
			$this->fixeLeFilter($status);
			$i	= 0;
			$fw	= false;

			/**
			 * We are waiting Firewall up. 
			 * Set timeout at 10" max.
			 */
			while ($fw == false && $i < 10){
				$i++;
				$fw = $this->fwobj->getConfig("status");
				sleep(1);
			}
			sleep(2); 
			return $fw;			
		}
		return true;
	}
	
	/**
	 * fixeLeFilter
	 *
	 * @return void
	 */
	public function fixeLeFilter($status = 'disabled'){
		if($this->fw){
			$adv = $this->getAdvancedSettings();
			$adv["lefilter"] = $status;
			$this->fwobj->setConfig("advancedsettings", $adv);
			$this->fwobj->restartFirewall();
		}
	}
}
