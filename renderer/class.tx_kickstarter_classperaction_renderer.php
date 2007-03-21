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

class tx_kickstarter_classperaction_renderer {

	var $pObj;

	function tx_kickstarter_classperaction_renderer($pObj = 0) {
		$this->pObj = $pObj;
	}

	function setParent($pObj) {
		$this->pObj = $pObj;
	}

    /**
     * Generates the setup.txt
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
	 * @param       bool             $static: include template as static template
     */
	function generateSetup($extKey, $k, $static) {
		$lines = array();
		$incls = array();
		$acts  = array();

		$cN = $this->pObj->returnName($extKey,'class','');
        $actions = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['actions'];

		$lines[] = '
# Common configuration
plugin.'.$cN.'.configurations {
  templatePath = EXT:'.$extKey.'/templates/
}

includeLibs.tx_div = EXT:div/class.tx_div.php
includeLibs.tx_lib_switch = EXT:lib/class.tx_lib_switch.php';

		foreach($actions as $action) {
			if(!trim($action['title'])) continue;
			$acts[] = '    '.$action['title'].' = '.($action[plus_user_obj]?'USER_INT':'USER').'
    '.$action['title'].' {
       userFunc = '.$cN.'_controller_'.$action[title].'->main
       setupPath = plugin.'.$cN.'.configurations.
    }';
			$incls[] = 'includeLibs.'.$cN.'_controller_'.$action[title].' = '.
				'EXT:'.$extKey.'/controllers/class.'.$cN.'_controller_'.$action[title].'.php';
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
tt_content.list.20.'.$extKey.' =< plugin.'.$cN.'.controllerSwitch';

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

        $actions = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['actions'];
		foreach($actions as $action) {
			if(!trim($action[title])) continue;

			$tablename = $this->pObj->wizard->wizArray['tables'][$action['model']]['tablename'];
			$model = $cN.'_model_'.$tablename;
			$views = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['views'];
			$view  = $cN.'_views_'.$views[$action[view]][title];
			$templates = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['templates'];
			$template  = $templates[$action[template]][title].'.php';

			$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller_'.$action[title].' extends tx_lib_controller {

		function main() {
                $model = tx_div::makeInstance(\''.$model.'\');
                $model->setConfigurations($this->configurations);
                $model->load($this->parameters);
                $resultList = $model->get(\'resultList\');
                $view = tx_div::makeInstance(\''.$view.'\');
                $view->set(\'entryList\', $resultList);
                $view->setController($this);
                $view->setTemplatePath($this->configurations->get(\'templatePath\'));
                return $view->render($this->configurations->get(\''.$template.'\'));
		}
}';

			$this->pObj->addFileToFileArray('controllers/class.'.$cN.'_controller_'.$action['title'].'.php', 
				$this->pObj->PHPclassFile(
					$extKey,
					'controllers/class.'.$cN.'_controller_'.$action['title'].'.php',
					$indexContent,
					'Class that implements the controller for action '.$action['title'].'.'
				)
			);
		}
	}

    /**
     * Generates the class.tx_*_model_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateModels($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

        $models = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['models'];
		if(!is_array($models)) return;

		foreach($models as $model) {
			$tablename = $this->pObj->wizard->wizArray['tables'][$model['title']]['tablename'];
			if(!trim($tablename)) continue;
			$real_tableName = $this->pObj->returnName($extKey,'tables',$tablename);

			$indexContent = '
class '.$cN.'_model_'.$tablename.' extends tx_lib_object {
        var $configurations;

        function load($parameters = null) {

                // fix settings
                $fields = \'*\';
                $tables = \''.$real_tableName.'s\';
                $groupBy = null;
                $orderBy = \'sorting\';
                $where = \'hidden = 0 AND deleted = 0 \';

                // variable settings
                if($parameters) {
					// do query modifications according to incoming parameters here.
                }

                // query
                $query = $GLOBALS[\'TYPO3_DB\']->SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
                $result = $GLOBALS[\'TYPO3_DB\']->sql_query($query);
                $list = tx_div::makeInstance(\'tx_lib_object\');
                if($result) {
                        while($row = $GLOBALS[\'TYPO3_DB\']->sql_fetch_assoc($result)) {
                                $entry = new tx_lib_object($row);
                                $list->append($entry);
                        }
                }
                $this->set(\'resultList\', $list);
        }

        function setConfigurations($configurations) {
                $this->configurations = $configurations;
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

    /**
     * Generates the class.tx_*_view_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateViews($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

        $views = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['views'];
		foreach($views as $view) {
			if(!trim($view[title])) continue;

			$indexContent = '
tx_div::load(\'tx_lib_'.$this->pObj->viewEngines[$view['inherit']].'\');

class '.$cN.'_views_'.$view[title].' extends tx_lib_'.$this->pObj->viewEngines[$view['inherit']].' {
}';

			$this->pObj->addFileToFileArray('views/class.'.$cN.'_view_'.$view['title'].'.php', 
				$this->pObj->PHPclassFile(
					$extKey,
					'views/class.'.$cN.'_view_'.$view['title'].'.php',
					$indexContent,
					'Class that implements the view for '.$view['title'].'.'
				)
			);
		}
	}

    /**
     * Generates the class.tx_*_template_*.php
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin 
     */
	function generateTemplates($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');

        $templates = $this->pObj->wizard->wizArray[$this->pObj->sectionID][$k]['templates'];
		foreach($templates as $template) {
			if(!trim($template[title])) continue;

			$indexContent = '
<?php $entryList = $this->get(\'entryList\'); ?>
<?php if($entryList->isNotEmpty()): ?>
        <ol>
<?php endif; ?>
<?php for($entryList->rewind(); $entryList->valid(); $entryList->next()): $entry = $entryList->current();       ?>
        <li>
			<h3>Insert HTML/Code to display elements here</h3>
        </li>
<?php endfor; ?>
<?php if($entryList->isNotEmpty()): ?>
        </ol>
<?php endif; ?>
';

			$this->pObj->addFileToFileArray('templates/'.$template['title'].'.php', $indexContent);
		}
	}

}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_classperaction_renderer.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc/renderer/class.tx_kickstarter_classperaction_renderer.php']);
}

?>
