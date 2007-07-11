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

class tx_kickstarter_renderer_base {

	var $pObj;

	function tx_kickstarter_renderer_base($pObj = 0) {
		$this->pObj = $pObj;
	}

	function setParent($pObj) {
		$this->pObj = $pObj;
	}

	function generateName($name, $alternative, $prefix, $override) {
		if(!empty($override))
			return $override;
		return ($prefix?$prefix:'').(strlen($name)?$name:$alternative);
	}

    /**
     * Generates the class.tx_*_model_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateModels($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

		$models = $this->pObj->wizard->wizArray['mvcmodel'];
		if(!is_array($models)) $models = array();
		foreach($models as $model) {
            $tablename = $this->generateName(
				$this->pObj->wizard->wizArray['tables'][$model['table']]['tablename'],
                $model['table'],
                0,
                $model[freename]
            );
			$real_tableName = $this->pObj->wizard->wizArray['tables'][$model['table']]['tablename'] ? 
				$this->pObj->returnName($extKey,'tables',$this->pObj->wizard->wizArray['tables'][$model['table']]['tablename']) : 
				NULL;
			$real_tableName = $this->generateName(
                $real_tableName,
                $model['table'],
                0,
                $model[freename]
            );
			if(!trim($tablename)) continue;

			$indexContent = '
class '.$cN.'_model_'.$tablename.' extends tx_lib_object {

        function '.$cN.'_model_'.$tablename.'($parameters = null) {
                parent::tx_lib_object();
        }

        function load($parameters = null) {

                // fix settings
                $fields = \'*\';
                $tables = \''.$real_tableName.'\';
                $groupBy = null;
                $orderBy = \'sorting\';
                $where = \'hidden = 0 AND deleted = 0 \';

                // variable settings
                if($parameters) {
					// do query modifications according to incoming parameters here.
                }

                // query
                $result = $GLOBALS[\'TYPO3_DB\']->exec_SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
                if($result) {
                        while($row = $GLOBALS[\'TYPO3_DB\']->sql_fetch_assoc($result)) {
                                $entry = new tx_lib_object($row);
                                $this->append($entry);
                        }
                }
        }
}
';
			$this->pObj->addFileToFileArray('models/class.'.$cN.'_model_'.$tablename.'.php', 
				$this->pObj->PHPclassFile(
					$extKey,
					'models/class.'.$cN.'_model_'.$tablename.'.php',
					$indexContent,
					'Class that implements the model for table '.$real_tableName.'.'
				)
			);
		}
	}

	function checkForAjax() {
		$ajaxed = array();

        $actions = $this->pObj->wizard->wizArray['mvcaction'];
        if(!is_array($actions)) return array();
		foreach($actions as $action) {
            $action_title = $this->generateName($action[title],0,0,$action[freename]);
			if(!trim($action_title)) continue;

			if($action['plus_ajax'])	$ajaxed[] = '\''.$action_title.'Action\'';
		}
		if(count($ajaxed) > 0)
			$this->wizard->EM_CONF_presets['dependencies'][] = 'xajax';
		return $ajaxed;
	}

	function getXajaxCode() {
		return '
	function _runXajax() {
		// We need the xajax extension
		if(!t3lib_extMgm::isLoaded(\'xajax\')) {
			die(\'The extension xaJax (xajax) is required!\');
		}
		tx_div::load(\'tx_xajax\');
	
		// prepare the action uri
		$link = tx_div::makeInstance(\'tx_lib_link\');
		$link->noHash();
		$destination = $GLOBALS[\'TSFE\']->id,$this->configurations->get(\'ajaxPageType\');
		$link->destination($destination);
		$link->designator($this->getDesignator());
		$url = $link->makeUrl();
	
		// build xajax
		$xajax = tx_div::makeInstance(\'tx_xajax\');
		$xajax->setRequestURI($url);
		$xajax->setWrapperPrefix($this->getDesignator());
		$xajax->statusMessagesOn();
		$xajax->debugOff();
		$xajax->waitCursorOff();
		foreach($this->targetControllers as $target) {
			$xajax->registerFunction( array($target, &$this, $target));
		}
		$xajax->processRequests();
		$GLOBALS[\'TSFE\']->additionalHeaderData[$this->getClassName()]
			= $xajax->getJavascript(t3lib_extMgm::siteRelPath(\'xajax\'));
	}
';
	}

	function getXajaxPage($type, $classname) {
		return '
		# The ajax response
ajaxResponse = PAGE
ajaxResponse.typeNum = '.$type.'
ajaxResponse.config.disableAllHeaderCode = true
ajaxResponse.50 = USER_INT
ajaxResponse.50 {
	userFunc = '.$classname.'_controller->main
	setupPath = plugin.'.$classname.'.configurations.
}
';
	}

	function getXajaxPageSwitch($type, $actions) {
		$i = 10;
		$lines = '
		# The ajax response
ajaxResponse = PAGE
ajaxResponse.typeNum = '.$type.'
ajaxResponse.config.disableAllHeaderCode = true
';
		foreach($actions as $a) {
			$lines .= 'ajaxResponse.'.$i.' = USER_INT
ajaxResponse.'.$i.' {
	userFunc = '.$classname.'_controller->main
	setupPath = plugin.'.$classname.'.configurations.
}
';
			$i += 10;
		}
		return $lines;
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_renderer_base.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_renderer_base.php']);
}

?>
