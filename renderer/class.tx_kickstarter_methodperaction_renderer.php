<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2007 Christian Welzel (gawain@camlann.de)  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * @author  Christian Welzel <gawain@camlann.de>
 */

require_once(t3lib_extMgm::extPath('kickstarter__mvc').'renderer/class.tx_kickstarter_renderer_base.php');

class tx_kickstarter_methodperaction_renderer extends tx_kickstarter_renderer_base {

    /**
     * Generates the setup.txt
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateSetup($extKey, $k) {
		$lines = array();

		$cN = $this->pObj->returnName($extKey,'class','');

		$lines[] = '
includeLibs.tx_div = EXT:div/class.tx_div.php

# Common configuration
plugin.'.$cN.'_mvc'.$k.'.configuration {
  templatePath = EXT:'.$extKey.'/templates/
  entryClassName =
  ajaxPageType = 110124
}
';

		$controllers = $this->pObj->wizard->wizArray['mvccontroller'];

		foreach($controllers as $kk => $contr) {
			if($contr[plugin] != $k) continue;
			$contr_name = $this->generateName($contr[title], 0, 0, $contr[freename]);

			$ajaxed = $this->checkForAjax($kk);

			$lines[] = '
includeLibs.'.$cN.'_controller_'.$contr_name.' = EXT:'.$extKey.'/controllers/class.'.$cN.'_controller_'.$contr_name.'.php

plugin.'.$cN.'.controller_'.$contr_name.' = '.($contr[plus_user_obj]?'USER_INT':'USER').'
plugin.'.$cN.'.controller_'.$contr_name.' < plugin.'.$cN.'_mvc'.$k.'.configuration
plugin.'.$cN.'.controller_'.$contr_name.' {
  userFunc = '.$cN.'_controller_'.$contr_name.'->main
  defaultAction = '.$this->getDefaultAction($kk).'
}

tt_content.list.20.'.$extKey.'_'.$contr_name.' =< plugin.'.$cN.'.controller_'.$contr_name;
		}

		if(count($ajaxed)) {
			$lines[] = $this->getXajaxPage('110124', $cN);
		}
			
		$this->pObj->addFileToFileArray('configurations/mvc'.$k.'/setup.txt', implode("\n", $lines));
	}

    /**
     * Generates the class.tx_*_controller_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateControllers($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

		$controllers = $this->pObj->wizard->wizArray['mvccontroller'];

		foreach($controllers as $kk => $contr) {
			if($contr[plugin] != $k) continue;
			$contr_name = $this->generateName($contr[title], 0, 0, $contr[freename]);

			$ajaxed = $this->checkForAjax($kk);

			$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller_'.$contr_name.' extends tx_lib_controller {

	var $targetControllers = array('.implode(',', $ajaxed).');

    function '.$cN.'_controller_'.$contr_name.'() {
        parent::tx_lib_controller();
        $this->setDefaultDesignator(\''.$cN.'\');
    }

';

			if(count($ajaxed)) {
				$indexContent .= '
	function doPreActionProcessings() {
    	$this->_runXajax();
	};
';
			}

	        $actions = $this->pObj->wizard->wizArray['mvcaction'];
	        if(!is_array($actions)) $actions = array();
			foreach($actions as $action) {
				if($action[controller] != $kk) continue;
				$indexContent .= $this->generateAction($action, $cN);
			}

			if(count($ajaxed)) {
				$indexContent .= $this->getXajaxCode();
			}

			$indexContent .= '}'."\n";

			$this->pObj->addFileToFileArray('controllers/class.'.$cN.'_controller_'.$contr_name.'.php', 
				$this->pObj->PHPclassFile(
					$extKey,
					'controllers/class.'.$cN.'_controller_'.$contr_name.'.php',
					$indexContent,
					'Class that implements the controller "'.$contr_name.'" for '.$cN.'.'.
						$this->formatComment($contr[description], 3)
				)
			);
		} // foreach
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_methodperaction_renderer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_methodperaction_renderer.php']);
}

?>
