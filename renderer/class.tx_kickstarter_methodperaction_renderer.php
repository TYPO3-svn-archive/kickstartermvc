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
	 * @param       bool             $static: include template as static template
     */
	function generateSetup($extKey, $k, $static) {
		$lines = array();

		$cN = $this->pObj->returnName($extKey,'class','');
        $actions = $this->pObj->wizard->wizArray['mvcaction'];

		$ajaxed = $this->checkForAjax();

		$lines[] = '
includeLibs.tx_div = EXT:div/class.tx_div.php
includeLibs.'.$cN.'_controller = EXT:'.$extKey.'/controllers/class.'.$cN.'_controller.php

# Common configuration
plugin.'.$cN.'.controller = '.($actions[1][plus_user_obj]?'USER_INT':'USER').'
plugin.'.$cN.'.controller {
  userFunc = '.$cN.'_controller->main
  defaultAction = '.$this->generateName($actions[1][title],0,0,$actions[1][freename]).'
  templatePath = EXT:'.$extKey.'/templates/
  entryClassName =
  ajaxPageType = 110124
}';

		if(count($ajaxed)) {
			$lines[] = $this->getXajaxPage('110124', $cN);
		}

		$lines[] = '
tt_content.list.20.'.$extKey.' =< plugin.'.$cN.'.controller
';

		if(!$static)
			$this->pObj->addFileToFileArray('configurations/setup.txt', implode("\n", $lines));
		else
			$this->pObj->addFileToFileArray('ext_typoscript_setup.txt', implode("\n", $lines));
	}

    /**
     * Generates the class.tx_*_configuration.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateConfigClass($extKey, $k) {
	}

    /**
     * Generates the class.tx_*_controller_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateActions($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

		$ajaxed = $this->checkForAjax();

		$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller extends tx_lib_controller {

	var $targetControllers = array('.implode(',', $ajaxed).');

    function '.$cN.'_controller() {
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
            $action_title = $this->generateName($action[title],0,0,$action[freename]);
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

			$indexContent .= '
    function '.$action_title.'Action() {'.
    	($action[plus_ajax]?'$response = tx_div::makeInstance(\'tx_xajax_response\');':'').'
        $modelClassName = tx_div::makeInstanceClassName(\''.$model.'\');
        $viewClassName = tx_div::makeInstanceClassName(\''.$view.'\');
        $entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
        $view = tx_div::makeInstance($viewClassName);
        $model = tx_div::makeInstance($modelClassName);
        $model->load($this->parameters);
        for($model->rewind(); $model->valid(); $model->next()) {
            $entry = new $entryClassName($model->current(), $this);
            $view->append($entry);
        }
        $view->setController($this);
        $view->setTemplatePath($this->configurations->get(\'templatePath\'));
        $out = $view->render($this->configurations->get(\''.$template.'\'));';
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
';

		}
		if(count($ajaxed)) {
			$indexContent .= $this->getXajaxCode();
		}

		$indexContent .= '}'."\n";

		$this->pObj->addFileToFileArray('controllers/class.'.$cN.'_controller.php', 
			$this->pObj->PHPclassFile(
				$extKey,
				'controllers/class.'.$cN.'_controller.php',
				$indexContent,
				'Class that implements the controller for '.$cN.'.'
			)
		);
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_methodperaction_renderer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_methodperaction_renderer.php']);
}

?>
