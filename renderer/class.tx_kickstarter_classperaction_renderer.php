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

class tx_kickstarter_classperaction_renderer extends tx_kickstarter_renderer_base {

    /**
     * Generates the setup.txt
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateSetup($extKey, $k) {
		$lines = array();
		$incls = array();
		$acts  = array();

		$cN = $this->pObj->returnName($extKey,'class','');

		$lines[] = '
# Common configuration
plugin.'.$cN.'.configurations {
  templatePath = EXT:'.$extKey.'/templates/
}

includeLibs.tx_div = EXT:div/class.tx_div.php
includeLibs.tx_lib_switch = EXT:lib/class.tx_lib_switch.php';

        $actions = $this->pObj->wizard->wizArray['mvcaction'];
        if(!is_array($actions)) $actions = array();
		foreach($actions as $action) {
			if($action[plugin] != $k) continue;
            $action_title = $this->generateName($action['title'],0,0,$action[freename]);
			if(!trim($action_title)) continue;

			$acts[] = '    '.$action_title.' = '.($action[plus_user_obj]?'USER_INT':'USER').'
    '.$action_title.' {
       userFunc = '.$cN.'_controller_'.$action_title.'->main
       setupPath = plugin.'.$cN.'.configurations.
    }';
			$incls[] = 'includeLibs.'.$cN.'_controller_'.$action_title.' = '.
				'EXT:'.$extKey.'/controllers/class.'.$cN.'_controller_'.$action_title.'.php';
		}
		$lines = array_merge($lines, $incls);

		$lines[] = '
# The controller switch
plugin.'.$cN.'.controllerSwitch = USER
plugin.'.$cN.'.controllerSwitch {
    userFunc = tx_lib_switch->main
';

		$lines = array_merge($lines, $acts);
		$lines[] = '}
tt_content.list.20.'.$extKey.'_mvc'.$k.' =< plugin.'.$cN.'.controllerSwitch
';

		$ajaxed = $this->checkForAjax($k);
		if(count( $ajaxed )) {
			$lines[] = $this->getXajaxPageSwitch('110124', $ajaxed);
		}

		$this->pObj->addFileToFileArray('configurations/mvc'.$k.'/setup.txt', implode("\n", $lines));
	}

    /**
     * Generates the class.tx_*_configuration.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateConfigClass($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

		$indexContent = '
tx_div::load(\'tx_lib_configurations\');

class '.$cN.'_configurations extends tx_lib_configurations {
        var $setupPath = \'plugin.'.$cN.'.configurations.\';
}';

		$this->pObj->addFileToFileArray('configurations/class.'.$cN.'_configuration.php', 
			$this->pObj->PHPclassFile(
					$extKey,
					'configurations/class.'.$cN.'_configuration.php',
					$indexContent,
					'Class that handles TypoScript configuration.'
			)
		);
	}

    /**
     * Generates the class.tx_*_controller_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateActions($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

        $actions = $this->pObj->wizard->wizArray['mvcaction'];
        if(!is_array($actions)) $actions = array();
		foreach($actions as $action) {
			if($action[plugin] != $k) continue;
            $action_title = $this->generateName($action['title'],0,0,$action[freename]);
			if(!trim($action_title)) continue;

            $model = $this->generateName(
                    $this->pObj->wizard->wizArray['tables'][$action['model']][tablename],
                    $this->pObj->wizard->wizArray['mvcmodel'][$action['model']][title],
                    $cN.'_model_',
                    $this->pObj->wizard->wizArray['mvcmodel'][$action['model']][freename]
            );
			$view  = $this->generateName(
                    $this->pObj->wizard->wizArray['mvcview'][$action[view]][title],
                    0,
                    $cN.'_view_',
                    $this->pObj->wizard->wizArray['mvcview'][$action[view]][freename]
            );
			$template  = $this->generateName(
                    $this->pObj->wizard->wizArray['mvctemplate'][$action[template]][title],
                    0,
                    0,
                    $this->pObj->wizard->wizArray['mvctemplate'][$action[template]][freename]
            );

			$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller_'.$action_title.' extends tx_lib_controller {

    function '.$cN.'_controller_'.$action_title.'() {
        parent::tx_lib_controller();
	    $this->setDefaultDesignator(\''.$cN.'\');
    }

    function main() {'.
    	($action[plus_ajax]?'$response = tx_div::makeInstance(\'tx_xajax_response\');':'').'
        $modelClassName = tx_div::makeInstanceClassName(\''.$model.'\');
        $viewClassName = tx_div::makeInstanceClassName(\''.$view.'\');
        $entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');
        $view = new $viewClassName($this);
        $model = new $modelClassName($this);
        $model->load($this->parameters);
        for($model->rewind(); $model->valid(); $model->next()) {
            $entry = new $entryClassName($model->current(), $this);
            $view->append($entry);
        }
        $view->setTemplatePath($this->configurations->get(\'templatePath\'));
        $view->render($this->configurations->get(\''.$template.'\'));
		$translator = new $translatorClassName($this, $view);
		$out = $translator->translateContent();';
        	if($action[plus_ajax]) {
				$indexContent .= '
        $response->addAssign(\'###EDIT: choose container to update here!###\', \'innerHTML\', $out);
        return $response;';
			}
			else {
				$indexContent .= '
        return $out;';
			}
			$indexContent .= '
    }
}';

			$this->pObj->addFileToFileArray('controllers/class.'.$cN.'_controller_'.$action_title.'.php', 
				$this->pObj->PHPclassFile(
					$extKey,
					'controllers/class.'.$cN.'_controller_'.$action_title.'.php',
					$indexContent,
					'Class that implements the controller for action "'.$action_title.'".'.
                    	$this->formatComment($action[description])
				)
			);
		}
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_classperaction_renderer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_classperaction_renderer.php']);
}

?>
