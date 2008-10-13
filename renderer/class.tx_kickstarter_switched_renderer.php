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
 * @author  Niels Fröhling <niels@frohling.biz>
 */

require_once(t3lib_extMgm::extPath('kickstarter__mvc_ex').'renderer/class.tx_kickstarter_renderer_base.php');

class tx_kickstarter_switched_renderer extends tx_kickstarter_renderer_base {

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
plugin.'.$cN.'_mvc'.$k.'.configurations {
	templatePath = EXT:'.$extKey.'/templates/

	pathToTemplateDirectory = EXT:'.$extKey.'/templates/
	pathToLanguageFile = EXT:'.$extKey.'/locallang.xml
}

includeLibs.tx_div = EXT:div/class.tx_div.php
includeLibs.tx_lib_switch = EXT:lib/class.tx_lib_switch.php';

		$controllers = $this->pObj->wizard->wizArray['mvccontroller'];
		if(!is_array($controllers))
			$controllers = array();

		foreach($controllers as $kk => $contr) {
			if($contr[plugin] != $k)
				continue;

			/* we can't put unnamed controllers and
			 * we don't want to expose 'aux' to TS
			 */
            		$contr_title = $this->generateName($contr['title'],0,0,$contr[freename]);
			if(!trim($contr_title) || ($contr_title == 'aux'))
				continue;

			/* we define standard validation-rules based upon our knowledge from the model */
			$contr_rules = '';
            		if (count($this->pObj->pRules[$contr_title.'_form']) > 0) {
            			$idx = 10; foreach ($this->pObj->pRules[$contr_title.'_form'] as $rule_block) {
            				$rule_block = "\t\t" . $idx . " {\n\t\t\t" . implode("\n\t\t\t", explode("\n", $rule_block)) . "\n\t\t}";
					$contr_rules .= $rule_block . "\n";
            				$idx += 10;
            			}
            		}

			/* we show some customizations for the destination */
			$contr_dests = '';
            		if (count($this->pObj->pRules[$contr_title.'_dests']) > 0) {
            			$contr_dests = "\n#\tconfigurations." . implode(" = \n#\tconfigurations.", $this->pObj->pRules[$contr_title.'_dests']) . " = \n";
            		}

			/* define the presets */
			$contr_pres = '';
            		if (count($this->pObj->pPresets[$contr_title]) > 0) {
            			$pre = array();
				foreach($this->pObj->pPresets[$contr_title] as $act => $list)
            				$pre[] = strtolower($act) . ' = ' . implode(',', $list);

            			$contr_pres = "\n\tconfigurations.presets {\n\t\t" . implode("\n\t\t", $pre) . "\n\t}\n";
            		}

			$c[] = '    '.$contr_title.' = '.($contr[plus_user_obj]?'USER_INT':'USER').'
    '.$contr_title.' {
	userFunc = '.$cN.'_controller_'.$contr_title.'->main
#	setupPath = plugin.'.$cN.'_mvc'.$k.'.configurations.
	configurations < plugin.'.$cN.'_mvc'.$k.'.configurations
	configurations.defaultAction = '.$this->getDefaultAction($kk).'
'.$contr_dests
 .$contr_pres.'
	configurations.validationRules {
'.$contr_rules.'	}
    }';
			$incls[] = 'includeLibs.'.$cN.'_controller_'.$contr_title.' = '.
				'EXT:'.$extKey.'/controllers/class.'.$cN.'_controller_'.$contr_title.'.php';
		}
		$lines = array_merge($lines, $incls);

		$lines[] = '
# The controller switch
plugin.'.$cN.'.controllerSwitch = USER_INT
plugin.'.$cN.'.controllerSwitch {
    userFunc = tx_lib_switch->main
';

		$lines = array_merge($lines, $c);
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
        var $setupPath = \'plugin.'.$cN.'_mvc'.$k.'.configurations.\';
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
     * Generates the flexform for this plugin
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin
     */
	function generateFlexform($extKey, $k) {
		$flexform = t3lib_div::getUrl(t3lib_extMgm::extPath('kickstarter__mvc_ex').'templates/template_flexform_switched.xml');
		$flexform = str_replace('###CLABEL###', $this->pObj->getSplitLabels_reference(array('title'=>'Select subcontroller'),'title','flexform.controllerSelection'), $flexform);
		$flexform = str_replace('###DLABEL###', $this->pObj->getSplitLabels_reference(array('title'=>'Custom default action'),'title','flexform.defaultActionSelection'), $flexform);
		$flexform = str_replace('###ALABEL###', $this->pObj->getSplitLabels_reference(array('title'=>'Forced action'),'title','flexform.actionSelection'), $flexform);
		$tmpc = '';
		$tmpa = '';
		$pres = array();

		$controllers = $this->pObj->wizard->wizArray['mvccontroller'];
        	if(!is_array($controllers))
        		$controllers = array();

		$i = 1;
		foreach($controllers as $kk => $contr) {
			if($contr[plugin] != $k)
				continue;

			/* we can't put unnamed controllers and
			 * we don't want to expose 'aux' to TS
			 */
            		$contr_title = $this->generateName($contr['title'],0,0,$contr[freename]);
			if(!trim($contr_title) || ($contr_title == 'aux'))
				continue;

			$label = $this->pObj->getSplitLabels_reference(array('title'=>$contr[title]),'title','flexform.controllerSelection.'.$contr_title);
			$tmpc .= '
									<numIndex index="'.($i++*10).'" type="array">
										<numIndex index="0">'.$label.'</numIndex>
										<numIndex index="1">'.$contr_title.'</numIndex>
									</numIndex>';
		}

//		$actions = array(
//			'',
//			'results',
//			'list',
//			'show',
//			'display',
//			'render',
//			'maintain',
//			'maintainlisted',
//			'trigger',
//			'forget',
//			'remember'
//		)

	        $actions = $this->pObj->wizard->wizArray['mvcaction'];
	        if(!is_array($actions))
	        	$actions = array();

		$tmpa .= '
									<numIndex index="0" type="array">
										<numIndex index="0">-</numIndex>
										<numIndex index="1"></numIndex>
									</numIndex>';

		$i = 1;
		foreach($actions as $action) {
	    		$action_title  = $this->generateName($action[title ], 0, 0, $action[freename]);
    			$action_preset = $action[preset];
			if (!trim($action_title) || ($action_title == 'aux_render'))
				continue;

			/* we can't put unnamed controllers and
			 * we don't want to expose 'aux' to TS
			 */
			$contr = $controllers[$action['controller']];
            		$contr_title = $this->generateName($contr['title'],0,0,$contr[freename]);
			if (!trim($contr_title) || ($contr_title == 'aux'))
				continue;

			/* it's only necesary to display the actions for the available controller, not for all existing */
			if ($contr['plugin'] != $k)
				continue;

			       $table  = $this->pObj->wizard->wizArray['tables'][
		        $action[table] = $this->pObj->wizard->wizArray['mvcmodel'][$action[model]][table]];

			$label = $this->pObj->getSplitLabels_reference(array('title'=>$controllers[$action[controller]][title] . ' -> ' . $action[title]),'title','flexform.actionSelection.'.$contr_title.'.'.$action_title);
			$tmpa .= '
									<numIndex index="'.($i++*10).'" type="array">
										<numIndex index="0">'.$label.'</numIndex>
										<numIndex index="1">'.$action_title.'</numIndex>
									</numIndex>';

	            	if (count($this->pObj->pPresets[$table]) > 0) {
				foreach($this->pObj->pPresets[$table] as $act => $list)
					$pres[] = $controllers[$action[controller]][title].' -> '.$action[title].' -> '.$act;
			}
		}

		$i = 1;
            	if (count($pres) > 0) {
            		$tmpp =
'					<preset>
						<TCEforms>
							<label>###PLABEL###</label>
							<config>
								<type>select</type>
								<items type="array">###PITEMS###
								</items>
							</config>
						</TCEforms>
					</preset>';

			foreach($pres as $act) {
				$label = $this->pObj->getSplitLabels_reference(array('title'=>$act),'title','flexform.presetSelection.'.strtolower($act));
				$tmps .= '
										<numIndex index="'.($i++*10).'" type="array">
											<numIndex index="0">'.$label.'</numIndex>
											<numIndex index="1">'.strtolower($act).'</numIndex>
										</numIndex>';
			}

			$tmpp = str_replace('###PLABEL###', $this->pObj->getSplitLabels_reference(array('title'=>'Controller preset'),'title','flexform.presetSelection'), $tmpp);
			$tmpp = str_replace('###PITEMS###', $tmps, $tmpp);
		}

		$this->pObj->addFileToFileArray(
			'configurations/mvc'.$k.'/flexform.xml',
			str_replace('###CITEMS###', $tmpc,
			str_replace('###DITEMS###', $tmpa,
			str_replace('###AITEMS###', $tmpa,
			str_replace('###PRESETS###', $tmpp, $flexform))))
		);
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/renderer/class.tx_kickstarter_switched_renderer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/renderer/class.tx_kickstarter_switched_renderer.php']);
}

?>
