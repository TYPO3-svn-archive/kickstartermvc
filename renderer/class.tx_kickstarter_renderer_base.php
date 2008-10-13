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

require_once(t3lib_extMgm::extPath('kickstarter__mvc_ex').'dbinfos.php');

class tx_kickstarter_renderer_base {

	var $pObj;

	function tx_kickstarter_renderer_base($pObj = 0) {
		$this->pObj = $pObj;

		/* storage-spaces that transfer model-knowledge to corresponding
		 * setup-, controller-, etc.-generators
		 */
		if ($this->pObj && !$this->pObj->pTmpls)
			$this->pObj->pTmpls = array();
		if ($this->pObj && !$this->pObj->pRules)
			$this->pObj->pRules = array();
		if ($this->pObj && !$this->pObj->pPresets)
			$this->pObj->pPresets = array();
		if ($this->pObj && !$this->pObj->pCntrl)
			$this->pObj->pCntrl = array();
	}

	function setParent($pObj) {
		$this->pObj = $pObj;

		/* storage-spaces that transfer model-knowledge to corresponding
		 * setup-, controller-, etc.-generators
		 */
		if (!$this->pObj->pTmpls)
			$this->pObj->pTmpls = array();
		if (!$this->pObj->pRules)
			$this->pObj->pRules = array();
		if (!$this->pObj->pPresets)
			$this->pObj->pPresets = array();
		if (!$this->pObj->pCntrl)
			$this->pObj->pCntrl = array();
	}

	function generateName($name, $alternative, $prefix, $override) {
		if(!empty($override))
			return $override;
		return preg_replace('/\s+/i', '_', ($prefix?$prefix:'').(strlen($name)?$name:$alternative));
	}

	/**
	 * gets local lang key reference
	 *
	 * @param	array		$config: ...
	 * @param	string		$key: ...
	 * @param	string		label key
	 * @return	string		reference to label
	 */
	function getSplitLabels_substitution($config,$key,$LLkey) {
		/* we don't want placeholder-texts to appear in the front-end */
		$string = trim($config[$key]); if (!$string) $string = '&nbsp;';
		$this->pObj->wizard->ext_locallang['default'][$LLkey] = array($string);

		if (count($this->pObj->wizard->languages)) {
			reset($this->pObj->wizard->languages);
			while(list($lk,$lv)=each($this->pObj->wizard->languages)) {
				if (isset($this->pObj->wizard->selectedLanguages[$lk])) {
					/* here it would fall back to the default languge, so no adjustment needed */
					$string = trim($config[$key.'_'.$lk]);
					$this->pObj->wizard->ext_locallang[$lk][$LLkey] = array($string);
				}
			}
		}

		return '%%%'.$LLkey.'%%%';
	}

	/**
	 * Reformats the given string to fit into the comment block of
	 * a function. Does stripping and indenting of lines.
	 *
	 * @param	string	the comment to reformat
	 */
	function formatComment($comment, $depth = 3) {
		if(empty($comment)) return '';
		$tabs = str_repeat("\t", $depth);
		return
		"\n$tabs *\n".
			implode('',
				array_map(
					create_function('$a','return "'.$tabs.' * ".$a."\n";'),
					explode("\n", wordwrap(trim($comment),60))
				)
			).
		"$tabs *";
	}

	/**
	 * Finds and retreives information about a given table-name
	 *
	 * @param	string	the table to look for
	 */
	function retreiveTableInfos($tableid) {
		if (is_numeric($tableid))
			return $this->pObj->wizard->wizArray['tables'][$tableid];

		/* reverse-engineer the table if it's in the database */
		return $GLOBALS['KSRE'][$tableid];
	}

	/**
	 * Finds and retreives information about a given table-name
	 *
	 * @param	string	the table to look for
	 * @param	string	the attribute to look for
	 */
	function retreiveTableAttribute($tablename, $attr, $pid = FALSE) {
		foreach ($this->pObj->wizard->wizArray['tables'] as $table)
			if ($table['tablename'] == $tablename)
				break;

		if ($table['tablename'] != $tablename)
			$table = $GLOBALS['KSRE'][$tablename];
		if ($table['tablename'] != $tablename)
			return null;

		switch ($attr) {
			case 'sorting':
				return $table['sorting_field'] . ' ' . ($table['sorting_desc'] ? 'DESC' : 'ASC');

			case 'hidden':
				return ($table['add_hidden'] ? 'hidden = 0' : '1');
			case 'deleted':
				return ($table['add_deleted'] ? 'deleted = 0' : '1');
			case 'storage':
				return ($pid ? 'pid = ...' : '1');

			case 'filter':
				return
					($table['add_hidden'] ? 'hidden = 0' : '1') . ' AND ' .
					($table['add_deleted'] ? 'deleted = 0' : '1') . ' AND ' .
					($pid ? 'pid = ...' : '1');
		}

		return null;
	}

	/**
	 * Generates the class.tx_*_model_*.php
	 *
	 * @param       string           $extKey: current extension key
	 * @param       integer          $k: current number of plugin
	 */
	function generateGeneric($extKey, $k) {
		$cN = $this->pObj->returnName($extKey,'class','');

		/* which 'extends'ions to generate for the views */
		$exts = array();

        	$views = $this->pObj->wizard->wizArray['mvcview'];
        	if(is_array($views))
        		foreach ($views as $view)
				$exts[$this->pObj->viewEngines[$view['inherit']]] = TRUE;

/* ################################################################################################### */
		foreach ($exts as $inherit => $bool) {
			$indexContent = '

tx_div::load(\'tx_lib_'.$inherit.'\');

// -------------------------------------------------------------------------------------
// Session (belongs to tx_lib_object which we can\'t extend)
// isn\'t connected to GLOBALS[TSFE]->fe_user, because these MVCs are not restricted
// to being logged in
// -------------------------------------------------------------------------------------

/**
 * Stores this singleton under the key "key" into the current session.
 *
 * @param	mixed		the key
 * @param	mixed		the singleton
 * @return	void
 */
function storeSingletonToSession($key, $val) {
	session_start();
	$_SESSION[$key] = serialize($val);

	/* expose the variable to TS, don\'t know a better approach yet */
	$GLOBALS["TSFE"]->fe_user->setKey(\'ses\', $key, $val);
	$GLOBALS["TSFE"]->fe_user->storeSessionData();
}

/**
 * Retrieves singleton from the current session. The singleton is accessed by "key".
 *
 * @param	mixed		the key
 * @return	mixed
 */
function fetchSingletonFromSession($key) {
	session_start();
	if (isset($_SESSION[$key]))
		return unserialize($_SESSION[$key]);
	return null;
}

/**
 * Retrieves singleton from the current session, and removes it. The singleton is accessed by "key".
 *
 * @param	mixed		the key
 * @return	mixed
 */
function takeSingletonFromSession($key) {
	session_start();
	$ret = unserialize($_SESSION[$key]);
	unset($_SESSION[$key]);

	/* expose the variable to TS, don\'t know a better approach yet */
	$GLOBALS["TSFE"]->fe_user->setKey(\'ses\', $key, null);
	$GLOBALS["TSFE"]->fe_user->storeSessionData();

	return $ret;
}

/**
 * Removes singleton from the current session. The singleton is accessed by "key".
 *
 * @param	mixed		the key
 * @return	void
 */
function removeSingletonFromSession($key) {
	session_start();
	unset($_SESSION[$key]);

	/* expose the variable to TS, don\'t know a better approach yet */
	$GLOBALS["TSFE"]->fe_user->setKey(\'ses\', $key, null);
	$GLOBALS["TSFE"]->fe_user->storeSessionData();
}

/**
 * Checks for singleton in the current session. The singleton is accessed by "key".
 *
 * @param	mixed		the key
 * @return	void
 */
function hasSingletonInSession($key) {
	session_start();
	if (isset($_SESSION[$key]))
		return TRUE;
	return FALSE;
}

/**
 * UserFunc for TS which checks if there is a valid $key available.
 * This function form part of the dependency-transportation.
 * The singleton is accessed by "key".
 *
 * @param	mixed		the key
 * @return	void
 */
function user_hasNoValidData($key) {
	session_start();
	if (isset($_SESSION[$key]) && (unserialize($_SESSION[$key]) > 0))
		return FALSE;
	if (isset($_GET[$key]) && (($_GET[$key][uid]) > 0))
		return FALSE;
	return TRUE;
}

class '.$cN.'_'.$inherit.'Ex extends tx_lib_'.$inherit.' {

	// -------------------------------------------------------------------------------------
	// ErrorList
	// -------------------------------------------------------------------------------------

	var $errs;

	function remapErrors() {
		$errs = $this->get(\'_errorList\');
		$this->errs = array();

		/* just make consecusive access a bit faster */
		if (count($errs))
			foreach ($errs as $err)
				$this->errs[$err[\'field\']] = $err;
	}

	function hasErrors() {
		return count($errs);
	}

	function hasError($field) {
		return isset($this->errs[$field]);
	}

	function getError($field) {
		return $this->errs[$field][\'message\'];
	}

}';

			$this->pObj->addFileToFileArray('class.'.$cN.'_'.$inherit.'Ex.php',
				$this->pObj->PHPclassFile(
					$extKey,
					'class.'.$cN.'_'.$inherit.'Ex.php',
					$indexContent,
					'Base-Class that implements extended functionality for '.$inherit
				)
			);
		}

/* ################################################################################################### */
		$indexContent = '

tx_div::load(\'tx_lib_validator\');

class '.$cN.'_validatorEx extends tx_lib_validator {

	/**
	 * Check assignment of a key (belongs to tx_lib_object, which we can\'t extend)
	 *
	 * It\'s just a convenient way to use the offsetExists() function from tx_lib_spl_arrayObject.
	 *
	 * @param	mixed		key
	 * @return	void
	 * @see		tx_lib_spl_arrayObject::offsetExists()
	 */
	function hasset($key){
		return $this->offsetExists($key);
	}

	/**
	 * Do the actual validation.
	 *
	 * @return	void
	 * @access	private
	 */
	function _validateByRules() {
		foreach($this->controller->configurations->get($this->pathToRules) as $rule) {
			/* allow partial and/or checkboxes */
			if ($this->hasset($rule[\'field\'])) {
				/* checkbox-arrays */
				$fl = $this->get($rule[\'field\']);
				if (is_array($fl))
					$fl = implode(\'\', $fl);

				if (!preg_match($rule[\'pattern\'], $fl)) {
					$this->errors[] = array_merge(array(\'.type\' => \'rule\'), $rule);
				}
			}
		}
	}

}';

		$this->pObj->addFileToFileArray('class.'.$cN.'_validatorEx.php',
			$this->pObj->PHPclassFile(
				$extKey,
				'class.'.$cN.'_validatorEx.php',
				$indexContent,
				'Slightly enhanced validator-class'
			)
		);

		$this->pObj->wizard->wizArray['mvcmodel'][] = array(
			'table' => 'aux',
			'title' => 'Auxiliar',
			'freename' => 'aux',
			'description' => 'A general MVC-model for fetching (read-only!) table-based variable input-choices'
		);

		$this->pObj->wizard->wizArray['mvcview'][] = array(
			'title' => 'Auxiliar - Element',
			'freename' => 'aux_display',
			'inherit' => 0,
			'description' => 'A general MVC-view for displaying table-based variable input-choices'
		);

		$this->pObj->wizard->wizArray['mvccontroller'][] = array(
			'title' => 'Auxiliar',
			'freename' => 'aux',
			'plugin' => $k,
			'description' => 'A general MVC-controller for creating table-based variable input-choices'
		);

		$this->pObj->wizard->wizArray['mvcaction'][] = array(
			'title' => 'Render Auxiliar',
			'freename' => 'aux_render',
			'controller' => count($this->pObj->wizard->wizArray['mvccontroller']),
			'description' => 'Display parametrized render of an element for a table-based variable input-choice'
		);

		$this->pObj->wizard->wizArray['mvctemplate'][] = array(
			'title' => 'Auxiliar - Select',
			'freename' => 'aux_select',
			'inherit' => 0,
			'description' => 'Select-field for table-based variable input-choices',

			'fill_model' => 'aux',
			'fill_type' => 'select'
		);

		$this->pObj->wizard->wizArray['mvctemplate'][] = array(
			'title' => 'Auxiliar - Checkboxes',
			'freename' => 'aux_checkbox',
			'inherit' => 0,
			'description' => 'Checkbox-array for table-based variable input-choices',

			'fill_model' => 'aux',
			'fill_type' => 'checkbox'
		);

		$this->pObj->wizard->wizArray['mvctemplate'][] = array(
			'title' => 'Auxiliar - Radiobuttons',
			'freename' => 'aux_radio',
			'inherit' => 0,
			'description' => 'Radiobutton-fields for table-based variable input-choices',

			'fill_model' => 'aux',
			'fill_type' => 'radio'
		);
	}

	function cleanupGeneric($extKey, $k) {
		$clean = array(
			'mvcmodel',
			'mvcview',
			'mvccontroller',
			'mvcaction',
			'mvctemplate'
		);

		$remove = array(
			'aux',
			'aux_display',
			'aux_render',
			'aux_select',
			'aux_checkbox',
			'aux_radio'
		);

		foreach ($clean as $c)
		foreach ($remove as $r)
		foreach ($this->pObj->wizard->wizArray[$c] as $n => $e) {
			if ($e['freename'] == $r)
				unset($this->pObj->wizard->wizArray[$c][$n]);
			//	echo 'unset('.'$this->pObj->wizard->wizArray['.$c.']['.$n.'])<br />';
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

		$models = $this->pObj->wizard->wizArray['mvcmodel'];
		if(!is_array($models))
			$models = array();

		foreach($models as $model) {
			$table = $this->retreiveTableInfos($model['table']);
            		$tablename = $this->generateName(
			    $table['tablename'],
            		    $model['table'],
            		    0,
            		    $model['freename']
            		);

			$real_tableName =
				$table['tablename'] /* ?
				$this->pObj->returnName($extKey,'tables',$table['tablename']) :
				NULL;

			$real_tableName = $this->generateName(
            		    $real_tableName,
            		    $model['table'],
            		    0,
            		    $model[freename]
            		) */ ;

			if(!trim($tablename))
				continue;

			/* assemble a list of fields this model covers */
			$fnames = array();
			$fdepes = array();
			$farrys = array();
			if (count($table['fields']))
				foreach ($table['fields'] as $key => $field) {
					$fnames[] = $field['fieldname'];
					if (($field['type'] == 'rel') &&
					    ($field['conf_rel_type'] == 'select_cur') &&
					    ($field['conf_relations'] == 1) &&
					    ($field['conf_relations_selsize'] == 1))
					$fdepes[] = $field['fieldname'];
					if (($field['type'] == 'rel') &&
					    ($field['conf_rel_type'] == 'select_storage') &&
					    ($field['conf_relations'] > 1) &&
					    ($field['conf_relations_selsize'] > 1))
					$farrys[] = $field['fieldname'];
				}
			/* implicit fields through settings */
			if ($table['add_hidden' ]) $fnames[] = 'hidden' ;
			if ($table['add_deleted']) $fnames[] = 'deleted';

/* ################################################################################################### */
			$indexContent = '
class '.$cN.'_model_'.$tablename.' extends tx_lib_object {

	var $fields_available;
	var $fields_dependent;
	var $fields_as_arrays;

        function '.$cN.'_model_'.$tablename.'($controller = null, $parameter = null) {
        	/* these fields are available in the database-table for that this
        	 * model has been generated, by default the routines cross-check
        	 * against these fields
        	 */
		$this->fields_available = explode(\',\', \''.implode(',',$fnames).'\');
		$this->fields_dependent = explode(\',\', \''.implode(',',$fdepes).'\');
		$this->fields_as_arrays = explode(\',\', \''.implode(',',$farrys).'\');

                parent::tx_lib_object($controller, $parameter);
        }

	/* the following functions handle only multiple entries, where the
	 * model itself is the iterator for multiple data-containers
	 */
        function load($parameters = null) {

                // fix settings
                $fields  = \'*\';
                $tables  = \''.$cN.'_'.$real_tableName.'\';
                $groupBy = null;
                $orderBy = \''.$table['sorting_field'].' '.($table['sorting_desc']?'DESC':'ASC').'\';
                $where   = \''.($table['add_hidden']?'hidden = 0':'1').' AND '.($table['add_deleted']?'deleted = 0':'1').' \';

                /* interprete the parameters for fetching results */
                if ($parameters) {
			/* do query modifications according to incoming parameters here. */
			if (($uid = $parameters->get(\'uid\')) > 0)
				$where .= \'AND uid = \' . $uid;
			/* otherwise try to match dependencies */
			else
				foreach($this->fields_dependent as $field)
					if (($did = $parameters->get($field)))
						$where .= \'AND \' . $field . \' = \' . $did;

			/* if you want to make the dependencies a requirement you have to
			 * adjust above code a bit, by default they aren\'t binding
			 *
			 *		else
			 *			return;
			 */
                }

                // query
	//	$reslt = $GLOBALS[\'TYPO3_DB\']->exec_SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
                $query = $GLOBALS[\'TYPO3_DB\']->SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
	//	print_r(\'<h3>Try to load:</h3> \' . $query . \'<br />\');
		$reslt = $GLOBALS[\'TYPO3_DB\']->sql(TYPO3_db, $query);
                if($reslt) {
                        while($row = $GLOBALS[\'TYPO3_DB\']->sql_fetch_assoc($reslt)) {
                        	/* convert array-blobs to real arrays */
				foreach($this->fields_as_arrays as $field)
					if ($row[$field])
						$row[$field] = explode(\',\', ltrim($row[$field], \',\'));

                        	/* append result-row */
                                $entry = new tx_lib_object($row);
                                $this->append($entry);
                        }
                }
        }

	/* the following functions handle only single entries, where the
	 * model itself is the data-container
	 */
	function insert($parameters = null) {
		$insertArray = array();
		$insertArray[\'tstamp\'] =
		$insertArray[\'crdate\'] = time();
		$insertArray[\'pid\'] = (integer) array_pop(tx_div::toListArray($this->controller->configurations[\'tt_content.pages\']));

		/* cross-check available fields for not inserting non-existant fields */
		foreach($parameters as $key => $value) {
			if (in_array($key, $this->fields_available)) {
                        	/* convert real arrays to array-blobs */
				if (in_array($key, $this->fields_as_arrays))
					$value = ltrim(implode(\',\', $value), \',\');

				$insertArray[$key] = htmlspecialchars($value);
			}
		}

		/* automatic feuser-assignment if not explicitly set */
		if (in_array(\'feuser\', $this->fields_available)) {
			if (!$insertArray[\'feuser\'] && $GLOBALS[\'TSFE\']->fe_user)
				$insertArray[\'feuser\'] = $GLOBALS[\'TSFE\']->fe_user->user[\'uid\'];
		}

	//	$reslt = $GLOBALS[\'TYPO3_DB\']->exec_INSERTquery(\''.$cN.'_'.$real_tableName.'\', $insertArray);
		$query = $GLOBALS[\'TYPO3_DB\']->INSERTquery(\''.$cN.'_'.$real_tableName.'\', $insertArray);
	//	print_r(\'<h3>Try to insert:</h3> \' . $query . \'<br />\');
		$reslt = $GLOBALS[\'TYPO3_DB\']->sql(TYPO3_db, $query);
		if($reslt) {
			$uid = $GLOBALS[\'TYPO3_DB\']->sql_insert_id();

			/* indicate that from now on we are editing instead of creating */
			$parameters->set(\'uid\', $uid);
			$this->set(\'uid\', $uid);
		}
	}

	function update($parameters = null) {
		$updateArray = array();
		$updateArray[\'tstamp\'] = time();

		/* cross-check available fields for not inserting non-existant fields */
		foreach($parameters as $key => $value) {
			if (in_array($key, $this->fields_available)) {
                        	/* convert real arrays to array-blobs */
				if (in_array($key, $this->fields_as_arrays))
					$value = ltrim(implode(\',\', $value), \',\');

				$updateArray[$key] = htmlspecialchars($value);
			}
		}

	//	$reslt = $GLOBALS[\'TYPO3_DB\']->exec_UPDATEquery(\''.$cN.'_'.$real_tableName.'\', \'uid = \' . $parameters->get(\'uid\'), $updateArray);
		$query = $GLOBALS[\'TYPO3_DB\']->UPDATEquery(\''.$cN.'_'.$real_tableName.'\', \'uid = \' . $parameters->get(\'uid\'), $updateArray);
	//	print_r(\'<h3>Try to update:</h3> \' . $query . \'<br />\');
		$reslt = $GLOBALS[\'TYPO3_DB\']->sql(TYPO3_db, $query);
		if($reslt) {
		}
	}

	function remove($uid, $remove = FALSE, $kill = FALSE) {' . ($table['add_hidden'] ? '
		if (!$remove)
			return $this->update(new tx_lib_object(array(\'uid\' => $uid, \'hidden\' => 1)));' : '') . ($table['add_deleted'] ? '
		if (!$kill)
			return $this->update(new tx_lib_object(array(\'uid\' => $uid, \'deleted\' => 1)));' : '') . '

	//	$reslt = $GLOBALS[\'TYPO3_DB\']->exec_DELETEquery(\''.$cN.'_'.$real_tableName.'\', \'uid = \' . $uid);
		$query = $GLOBALS[\'TYPO3_DB\']->DELETEquery(\''.$cN.'_'.$real_tableName.'\', \'uid = \' . $uid);
	//	print_r(\'<h3>Try to delete:</h3> \' . $query . \'<br />\');
		$reslt = $GLOBALS[\'TYPO3_DB\']->sql(TYPO3_db, $query);
		if($reslt) {
		}
	}
}
';
/* --------------------------------------------------------------------------------------------------- */

			if ($tablename == 'aux')
			$indexContent = '
class '.$cN.'_model_'.$tablename.' extends tx_lib_object {

        function '.$cN.'_model_'.$tablename.'($controller = null, $parameter = null) {
                parent::tx_lib_object($controller, $parameter);
        }

        function load($parameters = null) {

		// do query modifications according to incoming parameters here.
                $fields   = $parameters[\'field\'] . \',\' . $parameters[\'uid\'];
                $tables   = $parameters[\'table\'];
                $groupBy  = null;
                $orderBy  = (($parameters[\'order\'  ] != \'\') ? \' \' . $parameters[\'order\'    ] : null);
                $where    = \'1\';
                $where   .= (($parameters[\'hidden\' ] != \'\') ? \' AND hidden  = \' . $parameters[\'hidden\' ] : \'\');
                $where   .= (($parameters[\'deleted\'] != \'\') ? \' AND deleted = \' . $parameters[\'deleted\'] : \'\');
                $where   .= (($parameters[\'storage\'] != \'\') ? \' AND pid     = \' . $parameters[\'storage\'] : \'\');
                $where   .= (($parameters[\'filter\' ] != \'\') ? \' AND \' . $parameters[\'filter\'] : \'\');

                // query
        //      $result = $GLOBALS[\'TYPO3_DB\']->exec_SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
                $query = $GLOBALS[\'TYPO3_DB\']->SELECTquery($fields, $tables, $where, $groupBy, $orderBy);
	//	print_r(\'<h3>Try to load:</h3> \' . $query . \'<br />\');
		$reslt = $GLOBALS[\'TYPO3_DB\']->sql(TYPO3_db, $query);
                if($reslt) {
                        while($row = $GLOBALS[\'TYPO3_DB\']->sql_fetch_assoc($reslt)) {
                                $entry = new tx_lib_object($row);
                                $this->append($entry);
                        }
                }
        }
}
';

			$this->pObj->addFileToFileArray(
				'models/class.'.$cN.'_model_'.$tablename.'.php',
				$this->pObj->PHPclassFile(
					$extKey,
					'models/class.'.$cN.'_model_'.$tablename.'.php',
					$indexContent,
					'Class that implements the model for table '.$real_tableName.'.'.
						$this->formatComment($model[description])
				)
			);

			/* following we generate several templates that reflect common operations:
			 *
			 * + display
			 * + form	this is an identical form to the BE-entry, omiting internal
			 *		relations though
			 * + list	this is an identical table to the BE-list, offering edit/delete/...
			 *		and some bulk operations
			 * + listedform	this is a bulk-form, presenting the list filled directly with the
			 *		forms, supports JS add/edit/delete
			 * + select	generated a html-select for the entries referenced, utilizes the
			 *		aux-model
			 * + checkbox	-||- (just as a checkbox-array)
			 * + radio	-||- (just as a radio-selector)
			 *
			 * based on the postfix of the templates, these generated forms are going
			 * to be placed inside the template-file, for example 'table_form'
			 */

/* ################################################################################################### */
			$fieldValidn = array();
			$formContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$link = tx_div::makeInstance(\'tx_lib_link\');
	$link->designator($this->getDesignator());
	$link->noHash();
'.
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
	/* presets allow form-content to be grouped under a topic
	 * by default the preset is used to filter which fields are shown
	 * you may change this to re-oder fields, or give it a sub-heading
	 */
	$fields = $this->get(\'fields\');
':'').'
	/* remap errors for associative access, we display errors
	 * directly where they appear, setting classes when appropriate
	 * also
	 */
	$this->remapErrors();

	/* prepare for an undefined amount of identical forms;
	 * names are inside the form-namespace, ids are inside the
	 * document-namespace, so we randomize per form
	 */
	$rand = rand(); ?>
';

			$table_name = $table['tablename'];
			$table_title = $this->getSplitLabels_substitution($table, 'title', $table_name . '.title');
			$table_intro = $this->getSplitLabels_substitution($table, 'intro', $table_name . '.intro');

			$rtrn_subst = $this->getSplitLabels_substitution(array('title' => 'Return'), 'title', 'form.return');
			$abrt_subst = $this->getSplitLabels_substitution(array('title' => 'Abort'), 'title', 'form.abort');
			$undo_subst = $this->getSplitLabels_substitution(array('title' => 'Undo'), 'title', 'form.undo');
			$save_subst = $this->getSplitLabels_substitution(array('title' => 'Save'), 'title', 'form.save');
			$srtr_subst = $this->getSplitLabels_substitution(array('title' => 'Save & Return'), 'title', 'form.save_n_return');

			$formContent .= '
	<p class="intro">' . $table_intro . '</p>

	<form class="<?php echo $this->getDesignator(); ?>" method="post" action="" >
	<input name="<?php echo $this->getDesignator(); ?>[uid]" value="<?php echo $this->get(\'uid\'); ?>" type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[action]" value="<?php echo ($this->get(\'uid\') ? \'edit\' : \'create\'); ?>" type="hidden" />
	<!-- as submits can\'t distinguish between it\'s title and it\'s conceptual function, here we have an alias-array connecting language-dependent title and function -->
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$undo_subst.']" value="undo" type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$save_subst.']" value="edit" type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$srtr_subst.']" value="andreturn" type="hidden" />
	<dl>
';
			$select_error = $this->getSplitLabels_substitution(array('title' => 'Select a entry'), 'title', 'form.error.select');
			$integer_error = $this->getSplitLabels_substitution(array('title' => 'Put a natural number'), 'title', 'form.error.integer');
			$float_error = $this->getSplitLabels_substitution(array('title' => 'Put a number'), 'title', 'form.error.float');
			$content_error = $this->getSplitLabels_substitution(array('title' => 'Write something'), 'title', 'form.error.content');
			$date_error = $this->getSplitLabels_substitution(array('title' => 'Put a date'), 'title', 'form.error.date');

			if (count($table['fields']))
				foreach ($table['fields'] as $key => $field) {
				/*	if ($field['conf_automatic'])
						continue;	*/

					/* this is our field to be put */
					$field_name = $field['fieldname'];

					/* we create a regular language dependent substition-string, as well as one for the help */
					$field_subst = $this->getSplitLabels_substitution($field, 'title', $table_name . '.' . $field_name);
					$field_help = $this->getSplitLabels_substitution($field, 'help', $table_name . '.' . $field_name . '.help');

					/* the id-fields need to be unique in document-name-space */
					$field_id = /*$tablename*/ '<?php echo $rand; ?>' . '_' . $field['fieldname'];
					$field_idi = /*$tablename*/ '\' . $rand . \'' . '_' . $field['fieldname'];
					/* the name-field need to be in designator-name-space */
					$field_arr = /*$tablename*/ '<?php echo $this->getDesignator(); ?>' . '[' . $field['fieldname'] . ']';
					$field_arri = /*$tablename*/ '\' . $this->getDesignator() . \'' . '[' . $field['fieldname'] . ']';
					/* match empty string too, if it's not a requirement */
					$field_opt = (($field['conf_required'] || (($field['type'] == 'rel') && ($field['conf_rel_type'] == 'select_storage'))) ? '' : '$^|');

					$fieldContent =
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
<?php	if (!count($fields) || in_array(\''.$field_name.'\', $fields)) { ?>
':'').'		<dt class="' . (!$field_opt ? 'req' : 'opt') . '<?php echo $this->hasError(\''.$field_name.'\') ? \' err\' : \'\'; ?>"><label for="'.$field_id.'">'.$field_subst.':</label></dt>
		<dd class="help">'.$field_help.'</dd>
		<dd class="error"><?php echo $this->getError(\''.$field_name.'\'); ?></dd>
		<dd class="' . (!$field_opt ? 'req' : 'opt') . '<?php echo $this->hasError(\''.$field_name.'\') ? \' err\' : \'\'; ?>">';
					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'group':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render group-view of: ' . $field['conf_custom_table_name'] . ' -->'; }
									else {
										$fieldContent = ''; $formContent .= '
		<!-- implicit group-transport: ' . $field['conf_rel_table'] . ' -->'; }
									break;
								case 'select':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_custom_table_name'] . ' -->
			<?php
				$aux = new '.$cN.'_controller_aux($this->controller, $this->controller->configurations);
				$aux->configurations($this->controller->configurations);
				$aux->parameters = array(
					/* model parameters */
					\'table\'     => \'' . $field['conf_custom_table_name'] . '\',
					\'uid\'       => \'uid\',
					\'field\'     => \'cn_official_name_en\',
					\'order\'     => \'cn_official_name_en ASC\',
					/* template-selection */
					\'form\'      => \'' . ($field['conf_relations'] <= 1 ? ($field['conf_relations_selsize'] <= 1 ? 'select' : 'radio') : 'checkbox') . '\',
					/* template-parameters */
					\'id\'        => \'' . $field_idi . '\',
					\'name\'      => \'' . $field_arri . '\',' . ($field['conf_relations'] <= 1 ? '
					\'value\'     => $this->get(\''.$field_name.'\')' : '
					\'values\'    => $this->get(\''.$field_name.'\')') . '
				);

				echo $aux->renderAction();
			?>'; }
									else {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_rel_table'] . ' -->'; }
									break;
								case 'select_storage':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[1-9][0-9]*/\nmessage = " . $select_error;
									$fieldContent .= '
			<!-- render storage-view of: ' . $field['conf_rel_table'] . ' -->
			<?php
				$aux = new '.$cN.'_controller_aux($this->controller, $this->controller->configurations);
				$aux->configurations($this->controller->configurations);
				$aux->parameters = array(
					/* model parameters */
					\'table\'     => \'' . $field['conf_rel_table'] . '\',
					\'uid\'       => \'uid\',
					\'field\'     => \'title\',
					\'order\'     => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'sorting') . '\',
					\'filter\'    => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'filter', TRUE) . '\',
					/* template-selection */
					\'form\'      => \'' . ($field['conf_relations'] <= 1 ? ($field['conf_relations_selsize'] <= 1 ? 'select' : 'radio') : 'checkbox') . '\',
					/* template-parameters */
					\'id\'        => \'' . $field_idi . '\',
					\'name\'      => \'' . $field_arri . '\',' . ($field['conf_relations'] <= 1 ? '
					\'value\'     => $this->get(\''.$field_name.'\')' : '
					\'values\'    => $this->get(\''.$field_name.'\')') . '
				);

				echo $aux->renderAction();
			?>';
									break;
								case 'select_cur':
									$fieldContent = ''; $formContent .= '
		<!-- implicit current-transport: ' . $field['conf_rel_table'] . ' -->
';
									break;
							}
							break;
						case 'radio':
							$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[1-9][0-9]*/\nmessage = " . $select_error;
							for ($cb = 0; $cb < $field['conf_select_items']; $cb++) {
								$choice_subst = $this->getSplitLabels_substitution($field, 'conf_select_item_'.$cb, $table_name . '.' . $field_name . '.choice' . $cb);
								$fieldContent .= '
			<input id="'.$field_id.'['.$cb.']" name="'.$field_arr.'" value="'.$field['conf_select_itemvalue_'.$cb].'" checked="<?php $this->printAsForm(\''.$field_name.'\'); ?>" type="radio" /> '.$choice_subst.''; }
							break;
						case 'check':
							/* the only case of making a single checkbox a requirement
							 * would be a forced agreement, generaly this isn't the case
							 */
						//	$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[1-9][0-9]*/\nmessage = " . $select_error;
							$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'"<?php echo $this->get(\''.$field_name.'\') == 1 ? \' checked="checked"\' : \'\'; ?> value="1" type="checkbox" />';
							break;
						case 'check_4':
							break;
						case 'check_10':
							break;
						case 'input':
							$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt.".+/\nmessage = " . $content_error;
							$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" />';
							break;
						case 'input+':
							switch ($field['conf_eval']) {
								case 'date':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt.".+/\nmessage = " . $date_error;
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date" />';
									break;
								case 'time':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+:[0-9]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time" />';
									break;
								case 'timesec':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time seconds" />';
									break;
								case 'datetime':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date time" />';
									break;
								case 'year':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9][0-9][0-9][0-9]/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date year" />';
									break;
								case 'int':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+/\nmessage = " . $integer_error;
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer" />';
									break;
								case 'int+':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+/\nmessage = " . $integer_error;
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer unsigned" />';
									break;
								case 'double2':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[0-9]+\\.[0-9]+/\nmessage = " . $float_error;
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsFloat(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="float" />';
									break;
								case 'alphanum':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[a-zA-Z0-9]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string alphanum" />';
									break;
								case 'upper':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[A-Z]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string upper" />';
									break;
								case 'lower':
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt."[a-z]+/\nmessage = Error";
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string lower" />';
									break;
								default:
									$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt.".+/\nmessage = " . $content_error;
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string" />';
									break;
							}
							break;
						case 'textarea':
							$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt.".+/\nmessage = " . $content_error;
							$fieldContent .= '
			<textarea id="'.$field_id.'" name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $this->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						case 'textarea_rte':
							$fieldValidn[] = 'field = '.$field_name."\npattern = /".$field_opt.".+/\nmessage = " . $content_error;
							$fieldContent .= '
			<textarea id="'.$field_id.'" name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $this->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						default:
							break;
					}

					if ($fieldContent)
						$fieldContent .= '
		</dd>
'.
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
<?php	} ?>
':'');
					$formContent .= $fieldContent;
				}

			$formContent .= '
	</dl>

	<div class="actions">
<?php	$ret = $this->controller->selectDestination(\'list\');
	$ths = $this->getDestination();
	if ($ret != $ths) {
		$link->destination($ret);
		$link->label(\'<input value="\' . ($this->get(\'uid\') ? \''.$rtrn_subst.'\' : \''.$abrt_subst.'\') . \'" type="button" />\', TRUE); ?>
		<?php echo $link->makeTag(); ?>
<?php	}
	if ($this->get(\'uid\') && $this->hasErrors()) { ?>
		<input name="<?php echo $this->getDesignator(); ?>[todo]" value="'.$undo_subst.'" type="submit" />
<?php	} ?>
		<input name="<?php echo $this->getDesignator(); ?>[todo]" value="'.$save_subst.'" type="submit" />
<?php	if ($ret != $ths) { ?>
		<input name="<?php echo $this->getDesignator(); ?>[todo]" value="'.$srtr_subst.'" type="submit" />
<?php	} ?>
	</div>
	</form>
';
			$this->pObj->pTmpls[$tablename.'_form'] = $formContent;
			$this->pObj->pRules[$tablename.'_form'] = $fieldValidn;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$showContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$link = tx_div::makeInstance(\'tx_lib_link\');
	$link->designator($this->getDesignator());
	$link->noHash();
'.
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
	/* presets allow form-content to be grouped under a topic
	 * by default the preset is used to filter which fields are shown
	 * you may change this to re-oder fields, or give it a sub-heading
	 */
	$fields = $this->get(\'fields\');
':'').'
	/* remap errors for associative access, we display errors
	 * directly where they appear, setting classes when appropriate
	 * also
	 */
	$this->remapErrors();

	/* prepare for an undefined amount of identical forms;
	 * names are inside the form-namespace, ids are inside the
	 * document-namespace, so we randomize per form
	 */
	$rand = rand(); ?>
';

			$table_name = $table['tablename'];
			$table_title = $this->getSplitLabels_substitution($table, 'title', $table_name . '.title');
			$table_intro = $this->getSplitLabels_substitution($table, 'intro', $table_name . '.intro');

			$rtrn_subst = $this->getSplitLabels_substitution(array('title' => 'Return'), 'title', 'form.return');

			$showContent .= '
	<p class="intro">' . $table_intro . '</p>

	<dl>
';
			if (count($table['fields']))
				foreach ($table['fields'] as $key => $field) {
					/* this is our field to be put */
					$field_name = $field['fieldname'];

					/* we create a regular language dependent substition-string, as well as one for the help */
					$field_subst = $this->getSplitLabels_substitution($field, 'title', $table_name . '.' . $field_name);
					$field_help = $this->getSplitLabels_substitution($field, 'help', $table_name . '.' . $field_name . '.help');

					/* the id-fields need to be unique in document-name-space */
					$field_id = /*$tablename*/ '<?php echo $rand; ?>' . '_' . $field['fieldname'];
					$field_idi = /*$tablename*/ '\' . $rand . \'' . '_' . $field['fieldname'];
					/* the name-field need to be in designator-name-space */
					$field_arr = /*$tablename*/ '<?php echo $this->getDesignator(); ?>' . '[' . $field['fieldname'] . ']';
					$field_arri = /*$tablename*/ '\' . $this->getDesignator() . \'' . '[' . $field['fieldname'] . ']';
					/* match empty string too, if it's not a requirement */
					$field_opt = (($field['conf_required'] || (($field['type'] == 'rel') && ($field['conf_rel_type'] == 'select_storage'))) ? '' : '$^|');

					$fieldContent =
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
<?php	if (!count($fields) || in_array(\''.$field_name.'\', $fields)) { ?>
':'').'		<dt class="' . (!$field_opt ? 'req' : 'opt') . '<?php echo $this->hasError(\''.$field_name.'\') ? \' err\' : \'\'; ?>"><label for="'.$field_id.'">'.$field_subst.':</label></dt>
		<dd class="' . (!$field_opt ? 'req' : 'opt') . '<?php echo $this->hasError(\''.$field_name.'\') ? \' err\' : \'\'; ?>">';
					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'group':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render group-view of: ' . $field['conf_custom_table_name'] . ' -->'; }
									else {
										$fieldContent = ''; $showContent .= '
		<!-- implicit group-transport: ' . $field['conf_rel_table'] . ' -->'; }
									break;
								case 'select':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_custom_table_name'] . ' -->
			<?php
				$aux = new '.$cN.'_controller_aux($this->controller, $this->controller->configurations);
				$aux->configurations($this->controller->configurations);
				$aux->parameters = array(
					/* model parameters */
					\'table\'     => \'' . $field['conf_custom_table_name'] . '\',
					\'uid\'       => \'uid\',
					\'field\'     => \'cn_official_name_en\',
					\'order\'     => \'cn_official_name_en ASC\',
					/* template-selection */
					\'form\'      => \'' . ($field['conf_relations'] <= 1 ? ($field['conf_relations_selsize'] <= 1 ? 'select' : 'radio') : 'checkbox') . '\',
					/* template-parameters */
					\'id\'        => \'' . $field_idi . '\',
					\'name\'      => \'' . $field_arri . '\',' . ($field['conf_relations'] <= 1 ? '
					\'value\'     => $this->get(\''.$field_name.'\')' : '
					\'values\'    => $this->get(\''.$field_name.'\')') . '
				);

				echo $aux->renderAction();
			?>'; }
									else {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_rel_table'] . ' -->'; }
									break;
								case 'select_storage':
									$fieldContent .= '
			<!-- render storage-view of: ' . $field['conf_rel_table'] . ' -->
			<?php
				$aux = new '.$cN.'_controller_aux($this->controller, $this->controller->configurations);
				$aux->configurations($this->controller->configurations);
				$aux->parameters = array(
					/* model parameters */
					\'table\'     => \'' . $field['conf_rel_table'] . '\',
					\'uid\'       => \'uid\',
					\'field\'     => \'title\',
					\'order\'     => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'sorting') . '\',
					\'filter\'    => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'filter', TRUE) . '\',
					/* template-selection */
					\'form\'      => \'' . ($field['conf_relations'] <= 1 ? ($field['conf_relations_selsize'] <= 1 ? 'select' : 'radio') : 'checkbox') . '\',
					/* template-parameters */
					\'id\'        => \'' . $field_idi . '\',
					\'name\'      => \'' . $field_arri . '\',' . ($field['conf_relations'] <= 1 ? '
					\'value\'     => $this->get(\''.$field_name.'\')' : '
					\'values\'    => $this->get(\''.$field_name.'\')') . '
				);

				echo $aux->renderAction();
			?>';
									break;
								case 'select_cur':
									$fieldContent = ''; $showContent .= '
		<!-- implicit current-transport: ' . $field['conf_rel_table'] . ' -->
';
									break;
							}
							break;
						case 'radio':
							for ($cb = 0; $cb < $field['conf_select_items']; $cb++) {
								$choice_subst = $this->getSplitLabels_substitution($field, 'conf_select_item_'.$cb, $table_name . '.' . $field_name . '.choice' . $cb);
								$fieldContent .= '
			<input id="'.$field_id.'['.$cb.']" name="'.$field_arr.'" value="'.$field['conf_select_itemvalue_'.$cb].'" checked="<?php $this->printAsForm(\''.$field_name.'\'); ?>" type="radio" /> '.$choice_subst.''; }
							break;
						case 'check':
							/* the only case of making a single checkbox a requirement
							 * would be a forced agreement, generaly this isn't the case
							 */
							$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'"<?php echo $this->get(\''.$field_name.'\') == 1 ? \' checked="checked"\' : \'\'; ?> value="1" type="checkbox" />';
							break;
						case 'check_4':
							break;
						case 'check_10':
							break;
						case 'input':
							$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" />';
							break;
						case 'input+':
							switch ($field['conf_eval']) {
								case 'date':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date" />';
									break;
								case 'time':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time" />';
									break;
								case 'timesec':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time seconds" />';
									break;
								case 'datetime':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date time" />';
									break;
								case 'year':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date year" />';
									break;
								case 'int':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer" />';
									break;
								case 'int+':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer unsigned" />';
									break;
								case 'double2':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsFloat(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="float" />';
									break;
								case 'alphanum':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string alphanum" />';
									break;
								case 'upper':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string upper" />';
									break;
								case 'lower':
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string lower" />';
									break;
								default:
									$fieldContent .= '
			<input id="'.$field_id.'" name="'.$field_arr.'" value="<?php $this->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string" />';
									break;
							}
							break;
						case 'textarea':
							$fieldContent .= '
			<textarea id="'.$field_id.'" name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $this->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						case 'textarea_rte':
							$fieldContent .= '
			<textarea id="'.$field_id.'" name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $this->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						default:
							break;
					}

					if ($fieldContent)
						$fieldContent .= '
		</dd>
'.
(($model['plus_seg'] && ($model['segmentnum'] > 0)) ? '
<?php	} ?>
':'');
					$showContent .= $fieldContent;
				}

			$showContent .= '
	</dl>

	<div class="actions">
<?php	$ret = $this->controller->selectDestination(\'render\');
	$ths = $this->getDestination();
	if ($ret != $ths) {
		$link->destination($ret);
		$link->label(\'<input value="\' . ($this->get(\'uid\') ? \''.$rtrn_subst.'\' : \''.$abrt_subst.'\') . \'" type="button" />\', TRUE); ?>
		<?php echo $link->makeTag(); ?>
<?php	}
	</div>
';
			$this->pObj->pTmpls[$tablename.'_show'] = $showContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$listedformContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	/* prepare for an undefined amount of identical forms;
	 * names are inside the form-name-space, ids are inside the
	 * document-name-space, so we randomize per form
	 */
	$rand = rand(); ?>
';

			$undo_subst = $this->getSplitLabels_substitution(array('title' => 'Undo'), 'title', 'list.undo');
			$save_subst = $this->getSplitLabels_substitution(array('title' => 'Save'), 'title', 'list.save');

			$emptyx_subst = $this->getSplitLabels_substitution(array('title' => 'The list is empty, use "create" to add new entries'), 'title', 'list.empty');
			$create_subst = $this->getSplitLabels_substitution(array('title' => 'Create'), 'title', 'list.create');
			$delete_subst = $this->getSplitLabels_substitution(array('title' => 'Delete'), 'title', 'list.remove');
			$deleta_subst = $this->getSplitLabels_substitution(array('title' => 'Delete marked'), 'title', 'list.removemarked');

			$fieldNum = 0;
			$fieldContent = '';
			$helpContent = '';

			if (count($table['fields']))
				foreach ($table['fields'] as $field) {
					/* this is our field to be put */
					$field_name = $field['fieldname'];

					/* we create a regular language dependent substition-string, as well as one for the help */
					$field_subst = $this->getSplitLabels_substitution($field, 'title', $table_name . '.' . $field_name);
					$field_help = $this->getSplitLabels_substitution($field, 'help', $table_name . '.' . $field_name . '.help');

					/* match empty string too, if it's not a requirement */
					$field_opt = (($field['conf_required'] || (($field['type'] == 'rel') && ($field['conf_rel_type'] == 'select_storage'))) ? '' : '$^|');

					$fieldNum++;
					$fieldContent .= '
		<th class="' . (!$field_opt ? 'req' : 'opt') . '">'.$field_subst.':</th>';
					$helpContent .= '
		<td>'.$field_help.'</td>';

					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'group':
									if ($field['conf_rel_table'] != '_CUSTOM') {}
									else {
										$fieldNum--;
										$fieldContent = '
		<!-- implicit group-transport: ' . $field['conf_rel_table'] . ' -->';
										$helpContent = ''; }
									break;
								case 'select_cur':
									$fieldNum--;
									$fieldContent = '
		<!-- implicit current-transport: ' . $field['conf_rel_table'] . ' -->';
									$helpContent = '';
									break;
							}
							break;
					}
				}

			$listedformContent .= '
	<p class="intro">' . $table_intro . '</p>

	<form class="<?php echo $this->getDesignator(); ?>" method="post" action="" >
	<input name="<?php echo $this->getDesignator(); ?>[action]" value="sync" type="hidden" />
	<!-- as submits can\'t distinguish between it\'s title and it\'s conceptual function, here we have an alias-array connecting language-dependent title and function -->
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$save_subst.  ']" value="edit"   type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$undo_subst.  ']" value="undo"   type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$create_subst.']" value="create" type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$delete_subst.']" value="remove" type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$deleta_subst.']" value="remova" type="hidden" />
        <table>
        <caption>
        	' . $table_title . '
        </caption>
	<colgroup>
		<col width="12" align="center" />
';
			if (count($table['fields']))
				foreach ($table['fields'] as $field) {
					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'select_storage':
			$listedformContent .= '
		<col width="100" align="left" />';
									break;
								default:
									break;
							}
							break;
						case 'input+':
							switch ($field['conf_eval']) {
								case 'int':
								case 'int+':
			$listedformContent .= '
		<col width="80" align="left" />';
									break;
								case 'double2':
			$listedformContent .= '
		<col width="100" align="left" />';
									break;
								default:
			$listedformContent .= '
		<col width="1000*" align="left" />';
									break;
							}
							break;
						default:
			$listedformContent .= '
		<col width="1000*" align="left" />';
							break;
					}
				}

			$listedformContent .= '
		<col width="24" align="center"/>
	</colgroup>
        <thead>
        <tr>
		<th></th>';
			$listedformContent .= $fieldContent . '
		<th></th>
        </tr>
        <tr class="help">
		<td></td>';
			$listedformContent .= $helpContent . '
		<td></td>
        </tr>
        </thead>
        <tfoot>
        <tr>
		<td colspan="' . ($fieldNum + 2) /* the '*' does not work :-( */ . '">
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $create_subst . '" type="submit" />
<?php if ($this->count() > 0) { ?>
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $deleta_subst . '" type="submit" />
<?php } ?>
		</td>
        </tr>
        </tfoot>
<?php if($this->count() > 0) { ?>
        <tbody>
<?php foreach($this as $key => $entry) {
	/* remap errors for associative access, we display errors
	 * directly where they appear, setting classes when appropriate
	 * also
	 */
	$entry->remapErrors();

	/* if we want to operate on virtually existing entries we
	 * need an identifier, in this case we assign negative uids
	 * which are soly used for identification purposes
	 */
	if (!$entry->get(\'uid\'))
		$entry->set(\'uid\', -1 - abs(rand()))
?>
        <tr<?php echo ($entry->get(\'deleted\') ? \' style="display: none;"\' :
        	      ($entry->get(\'hidden\' ) ? \' style="text-decoration: line-through;"\' : \'\')); ?>>
		<td>
			<input name="<?php echo $this->getDesignator(); ?>[<?php echo $key; ?>][uid]" value="<?php echo $entry->get(\'uid\'); ?>" type="hidden" />
			<input name="<?php echo $this->getDesignator(); ?>[<?php echo $key; ?>][apply]" value="<?php echo $entry->get(\'uid\'); ?>" type="checkbox" />
		</td>
 ';

			if (count($table['fields']))
				foreach ($table['fields'] as $field) {
					/* this is our field to be put */
					$field_name = $field['fieldname'];

					/* the name-field need to be in designator-name-space */
					$field_arr = /*$tablename*/ '<?php echo $this->getDesignator(); ?>[<?php echo $key; ?>]' . '[' . $field['fieldname'] . ']';
					$field_arri = /*$tablename*/ '\' . $this->getDesignator() . \'[\'. $key .\']' . '[' . $field['fieldname'] . ']';
					/* match empty string too, if it's not a requirement */
					$field_opt = (($field['conf_required'] || (($field['type'] == 'rel') && ($field['conf_rel_type'] == 'select_storage'))) ? '' : '$^|');

					$fieldContent = '
		<td class="' . (!$field_opt ? 'req' : 'opt') . '<?php echo $entry->hasError(\''.$field_name.'\') ? \' err\' : \'\'; ?>">';
					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'group':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render group-view of: ' . $field['conf_custom_table_name'] . ' -->'; }
									else {
										$fieldContent = ''; $listedformContent .= '
		<!-- implicit group-transport: ' . $field['conf_rel_table'] . ' -->
'; }
									break;
								case 'select':
									if ($field['conf_rel_table'] == '_CUSTOM') {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_custom_table_name'] . ' -->'; }
									else {
										$fieldContent .= '
			<!-- render select-view of: ' . $field['conf_rel_table'] . ' -->'; }
									break;
								case 'select_storage':
									$fieldContent .= '
			<!-- render storage-view of: ' . $field['conf_rel_table'] . ' -->
			<?php
				$aux = new '.$cN.'_controller_aux($this->controller, $this->controller->configurations);
				$aux->configurations($this->controller->configurations);
				$aux->parameters = array(
					/* model parameters */
					\'table\'     => \'' . $field['conf_rel_table'] . '\',
					\'uid\'       => \'uid\',
					\'field\'     => \'title\',
					\'order\'     => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'sorting') . '\',
					\'filter\'    => \'' . $this->retreiveTableAttribute($field['conf_rel_table'], 'filter', TRUE) . '\',
					/* template-selection */
					\'form\'      => \'' . ($field['conf_relations'] <= 1 ? ($field['conf_relations_selsize'] <= 1 ? 'select' : 'radio') : 'checkbox') . '\',
					/* template-parameters */
					\'id\'        => \'' . rand() . '\',
					\'name\'      => \'' . $field_arri . '\',' . ($field['conf_relations'] <= 1 ? '
					\'value\'     => $entry->get(\''.$field_name.'\')' : '
					\'values\'    => $entry->get(\''.$field_name.'\')') . '
				);

				echo $aux->renderAction();
			?>';
									break;
								case 'select_cur':
									$fieldContent = ''; $listedformContent .= '
		<!-- implicit current-transport: ' . $field['conf_rel_table'] . ' -->
';
									break;
							}
							break;
						case 'radio':
							for ($cb = 0; $cb < $field['conf_select_items']; $cb++) $fieldContent .= '
			<input name="'.$field_arr.'" value="'.$field['conf_select_itemvalue_'.$cb].'" checked="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" type="radio" /> %%%'.$field['conf_select_item_'.$cb].'%%%';
							break;
						case 'check':
							$fieldContent .= '
			<input name="'.$field_arr.'"<?php echo $entry->get(\''.$field_name.'\') == 1 ? \' checked="checked"\' : \'\'; ?> value="1" type="checkbox" />';
							break;
						case 'check_4':
							break;
						case 'check_10':
							break;
						case 'input':
							$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" />';
							break;
						case 'input+':
							switch ($field['conf_eval']) {
								case 'date':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date" />';
									break;
								case 'time':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time" />';
									break;
								case 'timesec':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsTime(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="time seconds" />';
									break;
								case 'datetime':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date time" />';
									break;
								case 'year':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsDate(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="date year" />';
									break;
								case 'int':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer" />';
									break;
								case 'int+':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsInteger(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="integer unsigned" />';
									break;
								case 'double2':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsFloat(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="float" />';
									break;
								case 'alphanum':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string alphanum" />';
									break;
								case 'upper':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string upper" />';
									break;
								case 'lower':
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string lower" />';
									break;
								default:
									$fieldContent .= '
			<input name="'.$field_arr.'" value="<?php $entry->printAsForm(\''.$field_name.'\'); ?>" size="'.$field['conf_size'].'" maxlength="'.$field['conf_max'].'" type="text" class="string" />';
									break;
							}
							break;
						case 'textarea':
							$fieldContent .= '
			<textarea name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $entry->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						case 'textarea_rte':
							$fieldContent .= '
			<textarea name="'.$field_arr.'" cols="'.$field['conf_cols'].'" rows="'.$field['conf_rows'].'"><?php $entry->printAsForm(\''.$field_name.'\'); ?></textarea>';
							break;
						default:
							break;
					}

					if ($fieldContent)
						$fieldContent .= '
		</td>
';
					$listedformContent .= $fieldContent;
				}

			$listedformContent .= '
		<td>
			<!-- as submits can\'t distinguish between it\'s title and it\'s conceptual function, this is a real hard case (submit for all, select out a function for a single entry) -->
			<input name="<?php echo $this->getDesignator(); ?>[<?php echo $key; ?>][todo]" value="' . $delete_subst . '" type="image"'.str_replace('../', '', t3lib_iconWorks::skinImg('typo3/'.$GLOBALS['BACK_PATH'],'gfx/garbage.gif','width="18" height="16"')).' alt="Delete" />
		</td>
	</tr>
<?php } ?>
        </tbody>
<?php } else { ?>
        <tbody>
        <tr>
		<td colspan="' . ($fieldNum + 2) /* the '*' does not work :-( */ . '" class="note">
			' . $emptyx_subst . '
		</td>
        </tr>
        </tbody>
<?php } ?>
	</table>

	<div class="actions">
		<input name="<?php echo $this->getDesignator(); ?>[todo]" value="'.$undo_subst.'" type="submit" />
		<input name="<?php echo $this->getDesignator(); ?>[todo]" value="'.$save_subst.'" type="submit" />
	</div>
	</form>
';
			$this->pObj->pTmpls[$tablename.'_listedform'] = $listedformContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$listContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$link = tx_div::makeInstance(\'tx_lib_link\');
	$link->designator($this->getDesignator());
	$link->noHash();
?>
';
			if ($table['header_field']) {
				foreach ($table['fields'] as $field)
					if ($field['fieldname'] == $table['header_field']) {
						$header = $field;
						break;
					}

				$header_name  = $table['header_field'];
				$header_subst = $this->getSplitLabels_substitution($header, 'title', 'list.' . $header_name);
			}
			if ($table['add_hidden']) {
				$hide_subst   = $this->getSplitLabels_substitution(array('title' => 'Hide'), 'title', 'list.hide');
				$hida_subst   = $this->getSplitLabels_substitution(array('title' => 'Hide marked'), 'title', 'list.hidemarked');
			}
			$crdate_subst = $this->getSplitLabels_substitution(array('title' => 'Date'), 'title', 'list.crdate');

			$listContent .= '

	<form class="<?php echo $this->getDesignator(); ?>" method="post" action="">
	<input name="<?php echo $this->getDesignator(); ?>[action]" value="apply" type="hidden" />
	<!-- as submits can\'t distinguish between it\'s title and it\'s conceptual function, here we have an alias-array connecting language-dependent title and function -->
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$hida_subst  .']" value="hida"   type="hidden" />
	<input name="<?php echo $this->getDesignator(); ?>[alias]['.$deleta_subst.']" value="remova" type="hidden" />
        <table>
        <caption>
        	' . $table_title . '
        </caption>
	<colgroup>
		<col width="12"  align="center" />
		<col width="120" align="center" />
		<col width="*"   align="left"   />
		<col width="24"  align="center" />
	</colgroup>
        <thead>
        <tr>
		<th></th>
		<th>' . $crdate_subst . '</th>
		<th>' . $header_subst . '</th>
		<th></th>
        </tr>
        </thead>
';
/* ................................................................................................... */
			if (0) $listContent .= '
        <tfoot>
        <tr>
		<td colspan="4">
<?php	$link->destination($this->controller->selectDestination(\'maintain\'));
	$link->label(\'<input value="' . $create_subst . '" type="button" />\', TRUE);
	$link->parameters(array(\'action\' => \'list\', \'todo\' => \'create\', \'backPID\' => $this->getDestination())); ?>
			<?php echo $link->makeTag(); ?>
<?php if ($this->isNotEmpty()) { ?>' .
			($table['add_hidden'] ? '
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $hida_subst . '"   type="submit" />' : '') . '
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $deleta_subst . '" type="submit" />
<?php } ?>
		</td>
        </tr>
        </tfoot>
<?php if($this->isNotEmpty()) { ?>
        <tbody>
<?php for($this->rewind(); $this->valid(); $this->next()) {
     $entry = $this->current();
?>
';
/* ................................................................................................... */
			if (1) $listContent .= '
        <tfoot>
        <tr>
		<td colspan="4">
<?php	$link->destination($this->controller->selectDestination(\'maintain\'));
	$link->label(\'<input value="' . $create_subst . '" type="button" />\', TRUE);
	$link->parameters(array(\'action\' => \'maintain\', \'backPID\' => $this->getDestination())); ?>
			<?php echo $link->makeTag(); ?>
<?php if ($this->count() > 0) { ?>' .
			($table['add_hidden'] ? '
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $hida_subst . '"   type="submit" />' : '') . '
			<input name="<?php echo $this->getDesignator(); ?>[todo]" value="' . $deleta_subst . '" type="submit" />
<?php } ?>
		</td>
        </tr>
        </tfoot>
<?php if($this->count() > 0) { ?>
        <tbody>
<?php foreach($this as $key => $entry) {
	$ret = $this->controller->selectDestination(\'list\');
	$ths = $this->getDestination(); ?>
';
/* ................................................................................................... */
				$listContent .= '
        <tr<?php echo ($entry->get(\'deleted\') ? \' style="display: none;"\' :
        	      ($entry->get(\'hidden\' ) ? \' style="text-decoration: line-through;"\' : \'\')); ?>>
		<td><input name="<?php echo $this->getDesignator(); ?>[toapply][]" value="<?php echo $entry->get(\'uid\'); ?>" type="checkbox" /></td>
		<td><span class="date"><?php echo date(\'Y M d\', $entry->get(\'crdate\')); ?></span></td>
		<td>
<?php	$link->destination($this->controller->selectDestination(\'maintain\'));
	$link->label(\'<img'.str_replace('../', '', t3lib_iconWorks::skinImg('typo3/'.$GLOBALS['BACK_PATH'],'gfx/edit2.gif','width="18" height="16"')).' alt="Editar" /> \'.$entry->get(\'' . $header_name . '\'), TRUE);
	$link->parameters(array(\'action\' => \'maintain\', \'backPID\' => $ths, \'uid\' => $entry->get(\'uid\'))); ?>
			<?php echo $link->makeTag(); ?>
		</td>
		<td nowrap="nowrap">' . ($table['add_hidden'] ? '
<?php	$link->destination($ths);
	$link->label(\'<img'.str_replace('../', '', t3lib_iconWorks::skinImg('typo3/'.$GLOBALS['BACK_PATH'],'gfx/button_hide.gif','width="18" height="16"')).' alt="Hide" />\', TRUE);
	$link->parameters(array(\'action\' => \'hide\', \'uid\' => $entry->get(\'uid\')));
	$link->attributes(array(\'onclick\' => \'/* redirect to iframe-target (doing the action in the background), set row-class, set hide to unhide */\')); ?>
			<?php echo $link->makeTag(); ?>' : '') . '
<?php	$link->destination($ths);
	$link->label(\'<img'.str_replace('../', '', t3lib_iconWorks::skinImg('typo3/'.$GLOBALS['BACK_PATH'],'gfx/garbage.gif','width="18" height="16"')).' alt="Delete" />\', TRUE);
	$link->parameters(array(\'action\' => \'remove\', \'uid\' => $entry->get(\'uid\')));
	$link->attributes(array(\'onclick\' => \'/* redirect to iframe-target (doing the action in the background), remove the row */\')); ?>
			<?php echo $link->makeTag(); ?>
		</td>
        </tr>
';
/* ................................................................................................... */
			if (1) $listContent .= '
<?php } ?>
        </tbody>
<?php } else { ?>
        <tbody>
        <tr>
		<td colspan="4" class="note">
			' . $emptyx_subst . '
		</td>
        </tr>
        </tbody>
<?php } ?>
        </table>
        </form>
';
/* ................................................................................................... */
			if (0) $listContent .= '
<?php } ?>
        </tbody>
<?php } else { ?>
        <tbody>
        <tr>
		<td colspan="4" class="note">
			' . $emptyx_subst . '
		</td>
        </tr>
        </tbody>
<?php } ?>
        </table>
        </form>
';

			$this->pObj->pTmpls[$tablename.'_list'] = $listContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$renderContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$link = tx_div::makeInstance(\'tx_lib_link\');
	$link->designator($this->getDesignator());
	$link->noHash();
?>
';
			if ($table['header_field']) {
				foreach ($table['fields'] as $field)
					if ($field['fieldname'] == $table['header_field']) {
						$header = $field;
						break;
					}

				$header_name  = $table['header_field'];
				$header_subst = $this->getSplitLabels_substitution($header, 'title', 'render.' . $header_name);
			}

			$renderContent .= '

        <table>
        <caption>
        	' . $table_title . '
        </caption>
	<colgroup>
		<col width="*" align="left"   />
	</colgroup>
        <thead>
        <tr>
		<th>' . $header_subst . '</th>
        </tr>
        </thead>
';
/* ................................................................................................... */
			if (0) $listContent .= '
<?php if($this->isNotEmpty()) { ?>
        <tbody>
<?php for($this->rewind(); $this->valid(); $this->next()) {
     $entry = $this->current();
?>
';
/* ................................................................................................... */
			if (1) $listContent .= '
<?php if($this->count() > 0) { ?>
        <tbody>
<?php foreach($this as $key => $entry) {
	$ret = $this->controller->selectDestination(\'render\');
	$ths = $this->getDestination(); ?>
';
/* ................................................................................................... */
				$renderContent .= '
        <tr>
		<td>
<?php	$link->destination($this->controller->selectDestination(\'show\'));
	$link->label(\'<img'.str_replace('../', '', t3lib_iconWorks::skinImg('typo3/'.$GLOBALS['BACK_PATH'],'gfx/zoom2.gif','width="18" height="16"')).' alt="Ver" /> \'.$entry->get(\'' . $header_name . '\'), TRUE);
	$link->parameters(array(\'action\' => \'show\', \'backPID\' => $ths, \'uid\' => $entry->get(\'uid\'))); ?>
			<?php echo $link->makeTag(); ?>
		</td>
        </tr>
';
/* ................................................................................................... */
			if (1) $renderContent .= '
<?php } ?>
        </tbody>
<?php } else { ?>
        <tbody>
        <tr>
		<td class="note">
			' . $emptyx_subst . '
		</td>
        </tr>
        </tbody>
<?php } ?>
        </table>
        </form>
';
/* ................................................................................................... */
			if (0) $renderContent .= '
<?php } ?>
        </tbody>
<?php } else { ?>
        <tbody>
        <tr>
		<td class="note">
			' . $emptyx_subst . '
		</td>
        </tr>
        </tbody>
<?php } ?>
        </table>
        </form>
';

			$this->pObj->pTmpls[$tablename.'_render'] = $renderContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$selectContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$select_id    = $this->controller->parameters[\'id\'];
	$select_field = $this->controller->parameters[\'field\'];
	$select_name  = $this->controller->parameters[\'name\'];
	$select_value = $this->controller->parameters[\'value\'];
?>

';
/* ................................................................................................... */
			if (0) $selectContent .= '
<?php if($this->isNotEmpty()) { ?>
        <select
        	id="<?php echo $select_id; ?>"
        	name="<?php echo $select_name; ?>">
<?php } ?>
<?php for($this->rewind(); $this->valid(); $this->next()) {
     $entry = $this->current();
?>
';
/* ................................................................................................... */
			if (1) $selectContent .= '
<?php if($this->count() > 0) { ?>
        <select
        	id="<?php echo $select_id; ?>"
        	name="<?php echo $select_name; ?>">
<?php foreach($this as $key => $entry) {
?>
';
/* ................................................................................................... */
			$selectContent .= '
        <option
        	value="<?php echo $entry->get(\'uid\'); ?>"
        	<?php echo ($select_value == $entry->get(\'uid\') ? \'selected="selected"\' : \'\'); ?>>
        	<?php $entry->printAsForm($select_field); ?>
        </option>
';
/* ................................................................................................... */
			if (1) $selectContent .= '
<?php } ?>
        </select>
<?php } ?>';
/* ................................................................................................... */
			if (0) $selectContent .= '
<?php } ?>
<?php if($this->isNotEmpty()) { ?>
        </select>
<?php } ?>';

			$this->pObj->pTmpls[$tablename.'_select'] = $selectContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$radioContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$radio_id    = $this->controller->parameters[\'id\'];
	$radio_field = $this->controller->parameters[\'field\'];
	$radio_name  = $this->controller->parameters[\'name\'];
	$radio_value = $this->controller->parameters[\'value\'];
?>

';
/* ................................................................................................... */
			if (0) $radioContent .= '
<?php if($this->isNotEmpty()) { ?>
        <div>
<?php } ?>
<?php $idx = 0; for($this->rewind(); $this->valid(); $this->next()) {
     $entry = $this->current();
?>
';
/* ................................................................................................... */
			if (1) $radioContent .= '
<?php if($this->count() > 0) { ?>
        <div>
<?php $idx = 0; foreach($this as $key => $entry) {
?>
';
/* ................................................................................................... */
			$radioContent .= '
        <div>
        <input	type="radio"
        	id="<?php echo $radio_id . \'[\' . $idx . \']\'; ?>"
        	name="<?php echo $radio_name; ?>"
        	value="<?php echo $entry->get(\'uid\'); ?>"
        	<?php echo ($radio_value == $entry->get(\'uid\') ? \'checked="checked"\' : \'\'); ?> />
        <label	for="<?php echo $radio_id . \'[\' . $idx . \']\'; ?>">
        	<?php $entry->printAsForm($radio_field); ?></label>
        </div>
';
/* ................................................................................................... */
			if (1) $radioContent .= '
<?php $idx++; } ?>
        </div>
<?php } ?>';
/* ................................................................................................... */
			if (0) $radioContent .= '
<?php $idx++; } ?>
<?php if($this->isNotEmpty()) { ?>
        </div>
<?php } ?>';

			$this->pObj->pTmpls[$tablename.'_radio'] = $radioContent;
/* --------------------------------------------------------------------------------------------------- */

/* ################################################################################################### */
			$checkboxContent = '<?php
/**
 * Example template for phpTemplateEngineEx.
 *
 * Edit this template to match your needs.
 * $entry is of type tx_lib_object and represents a single data row.
 */
	if (!defined (\'TYPO3_MODE\'))
		die (\'Access denied.\');

	$cbox_id     = $this->controller->parameters[\'id\'];
	$cbox_field  = $this->controller->parameters[\'field\'];
	$cbox_name   = $this->controller->parameters[\'name\'];
	$cbox_values = $this->controller->parameters[\'values\'];
?>

        <input	type="hidden"
        	name="<?php echo $cbox_name . \'[]\'; ?>"
        	value="" />
';
/* ................................................................................................... */
			if (0) $checkboxContent .= '
<?php if($this->isNotEmpty()) { ?>
        <div>
<?php } ?>
<?php for($this->rewind(); $this->valid(); $this->next()) {
     $entry = $this->current();
?>
';
/* ................................................................................................... */
			if (1) $checkboxContent .= '
<?php if($this->count() > 0) { ?>
        <div>
<?php foreach($this as $key => $entry) {
?>
';
/* ................................................................................................... */
			$checkboxContent .= '
        <div>
        <input	type="checkbox"
        	id="<?php echo $cbox_id . \'[\' . $entry->get(\'uid\') . \']\'; ?>"
        	name="<?php echo $cbox_name . \'[]\'; ?>"
        	value="<?php echo $entry->get(\'uid\'); ?>"
        	<?php echo (count($cbox_values) && in_array($entry->get(\'uid\'), $cbox_values) ? \'checked="checked"\' : \'\'); ?> />
        <label	for="<?php echo $cbox_id . \'[\' . $entry->get(\'uid\') . \']\'; ?>">
        	<?php $entry->printAsForm($cbox_field); ?></label>
        </div>
';
/* ................................................................................................... */
			if (1) $checkboxContent .= '
<?php } ?>
        </div>
<?php } ?>';
/* ................................................................................................... */
			if (0) $checkboxContent .= '
<?php } ?>
<?php if($this->isNotEmpty()) { ?>
        </div>
<?php } ?>';

			$this->pObj->pTmpls[$tablename.'_checkbox'] = $checkboxContent;
/* --------------------------------------------------------------------------------------------------- */

			/* presets are specific collections of fields bundled under a topic */
			if ($model['plus_seg'] && ($model['segmentnum'] > 0)) {
				$presets = array();
				for ($sp = 0; $sp < $model['segmentnum']; $sp++)
					$presets[$model['segmentnames'][$sp]] = $model['segment'][$sp];

				$this->pObj->pPresets[$tablename] = $presets;
			}

//			$this->pObj->addFileToFileArray(
//				'models/class.'.$cN.'_model_'.$tablename.'.tmpl',
//				$this->pObj->pTmpls[$tablename.'_form']
//			);
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

        	$templates = $this->pObj->wizard->wizArray['mvctemplate'];
        	if(!is_array($templates))
        		$templates = array();

		foreach($templates as $template) {
            		$template_title = $this->generateName($template[title], 0, 0, $template[freename]);
			if(!trim($template_title))
				continue;

			switch($this->pObj->viewEngines[$template[inherit]]) {
				case 'smartyView':
					$tempfile = 'smartyViewTemplate.txt';
					break;
				default:
					if (($tmpl = $this->pObj->pTmpls[$template[fill_model].'_'.$template[fill_type]]))
						$indexContent = $tmpl;
					else
						$indexContent = '<?php ?>';
					break;
			}

			$this->pObj->addFileToFileArray(
				'templates/'.$template_title.'.php',
				$indexContent
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

        	$views = $this->pObj->wizard->wizArray['mvcview'];
        	if(!is_array($views))
        		$views = array();

		foreach($views as $view) {
           		$view_title = $this->generateName($view[title],0,0,$view[freename]);
			if(!trim($view_title))
				continue;

/* ################################################################################################### */
			$indexContent = '
tx_div::load(\''.$cN.'_'.$this->pObj->viewEngines[$view['inherit']].'Ex\');

class '.$cN.'_view_'.$view_title.' extends '.$cN.'_'.$this->pObj->viewEngines[$view['inherit']].'Ex {

}';

			$this->pObj->addFileToFileArray('views/class.'.$cN.'_view_'.$view_title.'.php',
				$this->pObj->PHPclassFile(
					$extKey,
					'views/class.'.$cN.'_view_'.$view_title.'.php',
					$indexContent,
					'Class that implements the view for '.$view_title.'.'.
						$this->formatComment($view[description])
				)
			);
		}
	}

    /**
     * Generates the action.
     *
     * @param       array            the action configuration array
     * @param       string			 the current classname
     */
    function generateAction($action, $cN) {
    	$action_title  = $this->generateName($action[title ], 0, 0, $action[freename]);
    	$action_preset = $action[preset];
	if(!trim($action_title))
		return;

        $action[table] = $this->pObj->wizard->wizArray['mvcmodel'][$action[model]][table];
	       $table  = $this->retreiveTableInfos($action[table]);

        $model = $this->generateName(
              $this->pObj->wizard->wizArray['mvcmodel'][$action[table]][table],
              $this->pObj->wizard->wizArray['mvcmodel'][$action[model]][title],
              $cN.'_model_',
              $this->pObj->wizard->wizArray['mvcmodel'][$action[model]][freename]
        );

	$view = $this->generateName(
              $this->pObj->wizard->wizArray['mvcview'][$action[view]][title],
              0,
              $cN.'_view_',
              $this->pObj->wizard->wizArray['mvcview'][$action[view]][freename]
        );

	$template = $this->generateName(
              $this->pObj->wizard->wizArray['mvctemplate'][$action[template]][title],
              0,
              0,
              $this->pObj->wizard->wizArray['mvctemplate'][$action[template]][freename]
        );

/* ################################################################################################### */
	/* every action can have it's own related model, so we can't generate a
	 * generic function as pre-process
	 */
	$prepareContent = '';

        	{
			if (count($table['fields']))
				foreach ($table['fields'] as $key => $field) {
					$field_name = $field['fieldname'];
					switch ($field['type']) {
						case 'rel':
							switch ($field['conf_rel_type']) {
								case 'select_cur':
		$prepareContent .= '
		/* implicit current-transport: ' . $field['conf_rel_table'] . ' */
		if (!$this->parameters->offsetExists(\''.$field_name.'\')) {
			$dep = fetchSingletonFromSession(\''.str_replace($cN.'_','',$field['conf_rel_table']).'\');'.($field['conf_rel_table']!='fe_users'?' if ($dep <= 0) return "";':'').' $this->parameters->set(\''.$field_name.'\', $dep); }';
									break;
							}
							break;
					}
				}
		}

	if ($prepareContent != '')
		$prepareContent = '
		/* check for implicit relations between this and other models
		 * the favoured behaviour is to \'activate\' an entry through
		 * navigating to it, then relate relations to that activated
		 * entry automatically (you still can overwrite the parameter
		 * though)
		 * this means that for implicit relations based on tables you
		 * do not have a MVC for, you have to provide those manually
		 */' . $prepareContent;

/* ################################################################################################### */
	if ($action_preset != 'list' &&			// list + create/edit/remove controls
	    $action_preset != 'render' &&		// list + show controls
	    $action_preset != 'search' &&		// list
	    $action_preset != 'maintainlisted' &&	// bulk   create/edit controls
	    $action_preset != 'maintain' &&		//        create/edit controls
	    $action_preset != 'show' &&			//        show controls
	    $action_preset != 'display' &&
	    $action_preset != 'trigger')
		$indexContent .= '
	function '.$action_title.'Action() {
		return \'\';
	}
';
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'display')
		$indexContent .= '
	function '.$action_title.'Action() {
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_'.$view.'\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\''.$cN.'_phpTemplateEngineEx\');
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\''.$template.'\');

		/* translate to destination */
		$translator = new $translatorClassName($this, $view);
		$out = $translator->translateContent();

		return $out;
	}
';
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'trigger')
		$indexContent .= '
	function '.$action_title.'Action() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
		'.$prepareContent.'
		/* implicit current-transport: ' . $table[tablename] . ' */
		if (!$this->parameters->offsetExists(\'uid\'))
			$this->parameters->set(\'uid\', fetchSingletonFromSession(\''.$table[tablename].'\'));

		/* model & view implement iterators! */
		if (!$io) {
			/* \'trigger\' adresses specifically only one element
			 * if the specification is absent we can\'t load
			 */
			if ($this->parameters->get(\'uid\') > 0) {
				/* the load-function returns (possibly multiple) results
				 * in the model-iterator
				 */
				$model = new $modelClassName($this);
				$model->update($this->parameters);
			}
		}

		return \'\';
	}
';
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'list' ||
	    $action_preset == 'maintainlisted') {
	    	if (!isset($this->pObj->pCntrl[$action['controller']]['remove'])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function hideAction() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');

		if (($uid = $this->parameters->get(\'uid\'))) {
			$this->parameters->offsetUnset(\'uid\');

			$model = new $modelClassName($this);
			$model->remove($uid);
		}

		return $this->' . $action_title . 'Action();
	}

	function removeAction() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');

		if (($uid = $this->parameters->get(\'uid\'))) {
			$this->parameters->offsetUnset(\'uid\');

			$model = new $modelClassName($this);
			$model->remove($uid, TRUE);
		}

		return $this->' . $action_title . 'Action();
	}

	function deleteAction() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');

		if (($uid = $this->parameters->get(\'uid\'))) {
			$this->parameters->offsetUnset(\'uid\');

			$model = new $modelClassName($this);
			$model->remove($uid, TRUE, TRUE);
		}

		return $this->' . $action_title . 'Action();
	}
';		} $this->pObj->pCntrl[$action['controller']]['remove'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'list' ||
	    $action_preset == 'search' ||
	    $action_preset == 'render') {
	    	if (!isset($this->pObj->pCntrl[$action['controller']][$action_title])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function '.$action_title.'Action() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_'.$view.'\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\''.$cN.'_phpTemplateEngineEx\');
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');
		'.$prepareContent.'

		/* model & view implement iterators! */
		$model = new $modelClassName($this);
		$model->load($this->parameters);

		/* step through the model-results */
		$view = new $viewClassName($this);
	//	for($model->rewind(); $model->valid(); $model->next()) {
	//		$entry = new $entryClassName($model->current(), $this);
		foreach($model as $key => $value) {
			/* convert the associative array into a template-based iterator */
			$entry = new $entryClassName($this, $value);
			$view->append($entry);
		}

		/* the nature of the list is to present a choice, an untaken
		 * choice wipes out the remembered id of this type
		 */
		removeSingletonFromSession(\''.$table[tablename].'\');

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\''.$template.'\');

		/* translate to destination */
		$translator = new $translatorClassName($this, $view);
		$out = $translator->translateContent();

		return $out;
	}
';		} $this->pObj->pCntrl[$action['controller']][$action_title] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintain') {
	    	if (!isset($this->pObj->pCntrl[$action['controller']]['create'])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function createAction() {
		'.$prepareContent.'

		/* the validator loads from the session and then overlays with
		 * the data transfered through GP
		 * is implemented carefull, all the actions and navigations
		 * done previously, have filled up the session with the necessary
		 * relation-ids allready
		 */
		$validator = $this->getValidator();
		if ($validator->ok()) {
			$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
			$linkClassName = tx_div::makeInstanceClassName(\'tx_lib_link\');

			// Paranoid? Insert a last validity check here.
			$model = new $modelClassName($this, $validator);
			$model->insert($this->parameters); // Finally store it.

			// Redirect. Always a good idea to prevent double entries by reload.
			$link = new $linkClassName();
			$link->designator($this->getDesignator());

			/* \'andreturn\' brings the view back to the list (or optionaly
			 * to any backPID given through TS, GET or POST)
			 */
			if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'andreturn\') {
				$link->destination($this->selectDestination(\'list\'));
			}
			/* otherwise we stay here, but we redirect anway to prevent
			 * the create-again-on-reload reaction-chain
			 */
			else {
				$link->destination($this->getDestination());
				$link->parameters(array(\'uid\' => $model->get(\'uid\')));
			}

			$link->noHash();
			$link->redirect();

			return;
		}

		return $this->maintainAction($validator);
	}
';		} $this->pObj->pCntrl[$action['controller']]['create'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintain') {
	    	if (!isset($this->pObj->pCntrl[$action['controller']]['edit'])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function editAction() {
		/* revert any changes made and go back to the last valid saved state
		 * which means simply abandoning the data in the session and let
		 * the model reload the data from scratch
		 */
		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'undo\')
			return $this->maintainAction();

		/* pipe the parameters through the validator, validate according
		 * to the verification-rules (the data is is saved in the session
		 * in addition for enabling persistant editing)
		 * if there is no error, the piped data will be used to update
		 * the model
		 */
		$validator = $this->getValidator(); // Load the data from the session.
		if ($validator->ok()) {
			$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');

			// Paranoid? Insert a last validity check here.
			$model = new $modelClassName($this, $validator);
			$model->update($this->parameters); // Finally store it.

			/* \'andreturn\' brings the view back to the list (or optionaly
			 * to any backPID given through TS, GET or POST)
			 */
			if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'andreturn\') {
				$linkClassName = tx_div::makeInstanceClassName(\'tx_lib_link\');

				$link = new $linkClassName();
				$link->designator($this->getDesignator());
				$link->destination($this->selectDestination(\'list\'));
				$link->noHash();
				$link->redirect();
			}
		}

		return $this->maintainAction($validator);
	}
';		} $this->pObj->pCntrl[$action['controller']]['edit'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'list') {
		if (!isset($this->pObj->pCntrl[$action['controller']]['apply'])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function applyAction() {
		/* detect if we have some batch-operations to do, the specific function
		 * to apply to each of the batched entries is variable
		 */
		if (count($this->parameters->get(\'toapply\'))) {
			$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
			$model = new $modelClassName($this);

			/* switch for the function to apply to all entries, here all the checkboxes
			 * indicating participation in the batch-action are concatenated in a
			 * common array
			 */
			if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'hida\') {
				foreach ($this->parameters->get(\'toapply\') as $uid)
					$model->remove($uid);
			}

			if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'remova\') {
				foreach ($this->parameters->get(\'toapply\') as $uid)
					$model->remove($uid, TRUE);
			}

			if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'deleta\') {
				foreach ($this->parameters->get(\'toapply\') as $uid)
					$model->remove($uid, TRUE, TRUE);
			}
		}

		return $this->listAction();
	}
';		} $this->pObj->pCntrl[$action['controller']]['apply'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintainlisted') {
		if (!isset($this->pObj->pCntrl[$action['controller']]['sync'])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function syncAction() {
		'.$prepareContent.'

		/* Load the data from the session, overlay it with the new entries
		 * and validate that
		 */
		$validators = $this->getValidators();

		/* detect if we have some batch-operations to do, the specific function
		 * to apply to each of the batched entries is variable
		 */
		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'hida\') {
			/* switch for the function to apply to all entries, here all the checkboxes
			 * indicating participation in the batch-action are part of the general
			 * structure of the entry, as the values of the entry will be send in its
			 * entirety (you may then change values + hide the entry and still receive
			 * validation-notes)
			 */
			foreach ($validators as $idx => $obj) {
				if (is_int($idx) && ($obj->get(\'apply\') == $obj->get(\'uid\'))) {
					/* hidden virtual entries are still entries supose to
					 * be flushed to the database!
					 */
					{
						$obj->set(\'hidden\', 1);
						$validators->set($idx, $obj);
					}
				}
			}
		}

		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'remova\') {
			foreach ($validators as $idx => $obj) {
				if (is_int($idx) && ($obj->get(\'apply\') == $obj->get(\'uid\'))) {
					/* make a difference between virtual entries (those that
					 * havnn\'t been flushed to database) and real ones
					 */
					if ($obj->get(\'uid\') > 0) {
						$obj->set(\'deleted\', 1);
						$validators->set($idx, $obj);
					}
					else {
						$validators->offsetUnset($idx);
					}
				}
			}
		}

		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'deleta\') {
			foreach ($validators as $idx => $obj) {
				if (is_int($idx) && ($obj->get(\'apply\') == $obj->get(\'uid\'))) {
					/* make a difference between virtual entries (those that
					 * havnn\'t been flushed to database) and real ones
					 */
					if ($obj->get(\'uid\') > 0) {
						$obj->set(\'deleted\', 2);
						$validators->set($idx, $obj);
					}
					else {
						$validators->offsetUnset($idx);
					}
				}
			}
		}

		/* detect if we have some singular-operations to do, the specific function
		 * to apply to the targeted entry is variable
		 */
		foreach ($validators as $idx => $obj) {
			if (is_int($idx)) {
				/* switch for the function to apply to the targeted entry, as we
				 * can\'t depend on JS and the trigger is a "submit" (you may
				 * then change values + hide the entry and still receive
				 * validation-notes)
				 */
				if ($this->parameters[\'alias\'][$obj->get(\'todo\')] == \'hide\') {
					/* hidden virtual entries are still entries supose to
					 * be flushed to the database!
					 */
					{
						$obj->set(\'hidden\', 1);
						$validators->set($idx, $obj);
					}
				}

				if ($this->parameters[\'alias\'][$obj->get(\'todo\')] == \'remove\') {
					/* make a difference between virtual entries (those that
					 * havnn\'t been flushed to database) and real ones
					 */
					if ($obj->get(\'uid\') > 0) {
						$obj->set(\'deleted\', 1);
						$validators->set($idx, $obj);
					}
					else {
						$validators->offsetUnset($idx);
					}
				}

				if ($this->parameters[\'alias\'][$obj->get(\'todo\')] == \'delete\') {
					/* make a difference between virtual entries (those that
					 * havnn\'t been flushed to database) and real ones
					 */
					if ($obj->get(\'uid\') > 0) {
						$obj->set(\'deleted\', 2);
						$validators->set($idx, $obj);
					}
					else {
						$validators->offsetUnset($idx);
					}
				}
			}
		}

		/* detect if we have some whole-operations to do, the specific function
		 * to apply to the whole dataset is variable
		 */
		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'edit\') {
			$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');

			foreach($validators as $idx => $obj) {
				if (is_int($idx)) {
					// Paranoid? Insert a last validity check here.
					$model = new $modelClassName($this, $obj);
					$uid = $obj->get(\'uid\');

					if ($obj->ok()) {
						if (!$obj->get(\'deleted\')) {
							/* if it\'s a real uid, update the entry */
							if ($uid > 0)
								$model->update($obj);
							/* an entry having a virtual uid must be new */
							else
								$model->insert($obj);
						}
					}

					if ($uid > 0) {
						/* if removed/deleted, remove the entry */
						if ($obj->get(\'deleted\') == 1)
							$model->remove($uid, TRUE);
						if ($obj->get(\'deleted\') == 2)
							$model->remove($uid, TRUE, TRUE);
					}
				}
			}
		}

		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'create\') {
			/* add an empty entry to the end of the validated results */
			$validatorClassName = tx_div::makeInstanceClassName(\''.$cN.'_validatorEx\');
			$validator = new $validatorClassName($this);
			$validators->append($validator);
		}

		if ($this->parameters[\'alias\'][$this->parameters[\'todo\']] == \'undo\') {
			/* just throw it all away and reload the model */
			$validators = null;
		}

		return $this->maintainlistedAction($validators);
	}
';		} $this->pObj->pCntrl[$action['controller']]['sync'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintain') {
		if (!isset($this->pObj->pCntrl[$action['controller']][$action_title])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function '.$action_title.'Action($io = null) {'.
		($action[plus_ajax]?'
		$response = tx_div::makeInstance(\'tx_xajax_response\');':'').'
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_'.$view.'\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\''.$cN.'_phpTemplateEngineEx\');
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');
		'.$prepareContent.'
		/* implicit current-transport: ' . $table[tablename] . ' */
		if (!$this->parameters->offsetExists(\'uid\'))
			$this->parameters->set(\'uid\', fetchSingletonFromSession(\''.$table[tablename].'\'));

		/* model & view implement iterators! */
		if (!$io) {
			/* \'maintain\' adresses specifically only one element
			 * if the specification is absent we can\'t load
			 */
			if ($this->parameters->get(\'uid\') > 0) {
				/* the load-function returns (possibly multiple) results
				 * in the model-iterator
				 */
				$model = new $modelClassName($this);
				$model->load($this->parameters);

				/* look out the first result */
				$model->rewind();
				if (($remember = $model->valid()))
					$io = $model->current();
			}
		}

		/* convert the associative array into a template-based iterator */
		if (!$io)
			$view = new $viewClassName($this);
		/* look out existing data */
		else
			$view = new $viewClassName($this, $io);
'.
(($this->pObj->wizard->wizArray['mvcmodel'][$action[model]]['plus_seg'] &&
 ($this->pObj->wizard->wizArray['mvcmodel'][$action[model]]['segmentnum'] > 0)) ? '
		/* presets can be used in a number of ways, by default
		 * it is used as a filter
		 */
		if (($presets = $this->configurations->get(\'presets.\')))
		if (($preset  = $this->configurations->get(\'preset\')))
			$view->set(\'fields\', explode(\',\',$presets[$preset]));
':'').'
		/* the "store to session" saves the current base of datas
		 * into the session, so it will survive successive page-
		 * refreshes
		 * the data available is actually a plain copy from the
		 * data loaded through the model, which generally is the
		 * whole data available for an entry including it\'s uid
		 * this has several advantages, the views are able to
		 * split the model-data into several chunks without loosing
		 * the ability to verify the model-data
		 * that is if the view only shows field1, and field2-10
		 * are in the session and thus original copies from the
		 * database, the validator still is going to check field1-10
		 * but field2-10 couldn\'t have been invalidated, leaving
		 * only field1 as a possible hostile field
		 * the other advantage is that it\'s possible omit the
		 * transfer of sensible information or generically
		 * calculated information though the http-channel, as
		 * you assign a custom deterministic identifier to the
		 * session you still can find the corresponding data, and
		 * your protected information
		 */
		// !!!! Store the data into the session. Again the classname is a possible ID. !!!!!!
		$view->storeToSession($this->getClassName());

		/* remember the last maintained single entry of this kind */
		if ($remember)
			storeSingletonToSession(\''.$table[tablename].'\', $view->get(\'uid\'));

		/* in this moment "view" is a direct iterator for the
		 * data, you can operator on that data through set/get
		 * you have to take care that the template you use
		 * does access the view in the appropriate way, and
		 * does not iterate over "view" as if it\'s an array
		 * of data-containers
		 */

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\''.$template.'\');

		/* translate to destination */
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
';
		} $this->pObj->pCntrl[$action['controller']][$action_title] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintainlisted') {
		if (!isset($this->pObj->pCntrl[$action['controller']][$action_title])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function '.$action_title.'Action($io = null) {'.
		($action[plus_ajax]?'
		$response = tx_div::makeInstance(\'tx_xajax_response\');':'').'
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_'.$view.'\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\''.$cN.'_phpTemplateEngineEx\');
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');
		'.$prepareContent.'

		/* model & view implement iterators! */
		if (!$io) {
			/* the load-function returns (possibly multiple) results
			 * in the model-iterator
			 */
			$model = new $modelClassName($this);
			$model->load($this->parameters);

			/* look out all results */
			$io = $model;
		}

		/* step through the model-results */
		$view = new $viewClassName($this);
		if ($io)
	//		for($model->rewind(); $model->valid(); $model->next()) {
	//			$entry = new $entryClassName($model->current(), $this);
			foreach($io as $idx => $value) {
				/* convert the associative array into a template-based iterator */
				$entry = new $entryClassName($this, $value);
				// !!!! Store the data into the session. Again the classname is a possible ID. !!!!!!
				$entry->storeToSession($this->getClassName() . \'_\' . $idx);
				$view->append($entry);
			}

		/* in this moment "view" is an array of data-containers
		 * which you can iterate with foreach
		 * you have to take care that the template you use
		 * does access the view in the appropriate way, and
		 * does not access over "view" as if it\'s an assiciative
		 * array of data-values
		 */

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\''.$template.'\');

		/* translate to destination */
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
';		} $this->pObj->pCntrl[$action['controller']][$action_title] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'show') {
		if (!isset($this->pObj->pCntrl[$action['controller']][$action_title])) {
		$indexContent .= '
	/**
	 * Implementation of '.$action_title.'Action()'.
		$this->formatComment($action[description], 1).'
	 */
	function '.$action_title.'Action() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_'.$model.'\');
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_'.$view.'\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\''.$cN.'_phpTemplateEngineEx\');
		$translatorClassName = tx_div::makeInstanceClassName(\'tx_lib_translator\');
		'.$prepareContent.'

		/* model & view implement iterators! */
		$io = null; {
			/* the load-function returns (possibly multiple) results
			 * in the model-iterator
			 */
			$model = new $modelClassName($this);
			$model->load($this->parameters);

			/* look out the first result */
			$model->rewind();
			if (($remember = $model->valid()))
				$io = $model->current();
		}

		/* convert the associative array into a template-based iterator */
		if (!$io)
			$view = new $viewClassName($this);
		/* look out existing data */
		else
			$view = new $viewClassName($this, $io);

		/* remember the last navigated single entry of this kind */
		if ($remember)
			storeSingletonToSession(\''.$table[tablename].'\', $view->get(\'uid\'));

		/* in this moment "view" is a direct iterator for the
		 * data, you can operator on that data through set/get
		 * you have to take care that the template you use
		 * does access the view in the appropriate way, and
		 * does not iterate over "view" as if it\'s an array
		 * of data-containers
		 */

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\''.$template.'\');

		/* translate to destination */
		$translator = new $translatorClassName($this, $view);
		$out = $translator->translateContent();

		return $out;
	}
';		} $this->pObj->pCntrl[$action['controller']][$action_title] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */
	if ($action_preset == 'maintain' || $action_preset == 'list'   ||
	    $action_preset == 'show'     || $action_preset == 'render') {
		if (!isset($this->pObj->pCntrl[$action['controller']]['remfor'])) {
		$indexContent .= '

	function rememberAction() {
		/* remember the last maintained single entry of this kind */
		if ($this->parameters->offsetExists(\'uid\'))
			storeSingletonToSession(\''.$table[tablename].'\', $this->parameters->get(\'uid\'));

		return \'\';
	}

	function forgetAction() {
		/* forget the last maintained single entry of this kind */
		removeSingletonFromSession(\''.$table[tablename].'\');

		return \'\';
	}
';
		} $this->pObj->pCntrl[$action['controller']]['remfor'] = TRUE;
	}
/* --------------------------------------------------------------------------------------------------- */

	return $indexContent;
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
	function generateControllers($extKey, $k) {

		$cN = $this->pObj->returnName($extKey,'class','');
		$controllers = $this->pObj->wizard->wizArray['mvccontroller'];

		foreach($controllers as $kk => $contr) {
			if($contr[plugin] != $k)
				continue;
			$contr_name = $this->generateName($contr[title], 0, 0, $contr[freename]);

	        	$actions = $this->pObj->wizard->wizArray['mvcaction'];
	        	if(!is_array($actions))
	        		$actions = array();

			$ajaxed = $this->checkForAjax($kk);

/* ################################################################################################### */
			$indexContent = '
tx_div::load(\'tx_lib_controller\');
tx_div::load(\''.$cN.'_validatorEx\');

class '.$cN.'_controller_'.$contr_name.' extends tx_lib_controller {

	var $targetControllers = array('.implode(',', $ajaxed).');

	function '.$cN.'_controller_'.$contr_name.'($parameter1 = null, $parameter2 = null) {
		parent::tx_lib_controller($parameter1, $parameter2);

		/* if there are multiple plugins on the same page, you may receive name-space clashes
		 * for having controllers with the same name
		 */
		$this->setDefaultDesignator(\''.$contr_name.'\');

		/* if there are multiple controllers on the same page, you definitly receive name-space
		 * clashes for having all of them with the same name
		 */
	//	$this->setDefaultDesignator(\''.$cN.'\');

		/* by using the plugin-name as namespace, you may evade all of the cases mentioned above
		 */
	//	$this->setDefaultDesignator(\''.$cN.'['.$contr_name.']\');
	}

';

			if(count($ajaxed)) {
/* ################################################################################################### */
				$indexContent .= '
	function doPreActionProcessings() {
    		$this->_runXajax();
	};
';
			}

/* ################################################################################################### */
			$indexContent .= '

	function selectDestination($action) {
		/* each of the actions available, may point to a custom configured destination
		 * which can be given by TS
		 *';
		 	/* create all the PIDs that are really necesary */
			$this->pObj->pRules[$contr_name . '_dests'] = array();
			foreach($actions as $action) {
				if ($action[controller] != $kk)
					continue;

				/* for now we only cover pre-defined actions */
				$action_title = $this->generateName($action[title], 0, 0, $action[freename]);
				if (in_array($action_title, array('list','tree','hierarchy','render','maintain','show'))) {
					$this->pObj->pRules[$contr_name . '_dests'][] = $action_title . 'PID';
					$indexContent .= '
		 * - ' . $action_title . 'PID';
		 		}
			}

			$indexContent .= '
		 */

		/* "backPID" is runtime local explicit */
		$dest = $this->configurations->get(\'backPID\');
		/* "actionPID" is runtime global explicit */
		if (!$dest)
			$dest = $this->configurations->get($action . \'PID\');
		/* then we get global implicit (same page) */
		if (!$dest)
			$dest = $this->getDestination();

		return $dest;
	}

	function getValidator() {
		// finding classnames
		$validatorClassName = tx_div::makeInstanceClassName(\''.$cN.'_validatorEx\');

		/* the validator overlays an existing base of data
		 * loaded from a persistance storage (in this case just
		 * the session-container) normally containing a complete
		 * description of a model, with the values gathered
		 * from the last update to a form with all or a subset
		 * of the models data
		 * non-updated data should validate (as it validated
		 * on the corresponding past update), updated data
		 * may have become hostile
		 * more complex validators (like calculations that
		 * invalidate) require a bit of additional work
		 */

		// process
		$validator = new $validatorClassName($this);
		$validator->loadFromSession($this->getClassName());
		$validator->overwriteArray($this->parameters);
		$validator->useRules(\'validationRules.\');
		$validator->validate();

		return $validator;
	}

	function getValidators() {
		// finding classnames
		$validatorsClassName = tx_div::makeInstanceClassName(\'tx_lib_object\');
		$validatorClassName = tx_div::makeInstanceClassName(\''.$cN.'_validatorEx\');

		/* in this case the incoming data contains a bunch
		 * of serialized data. the validator first filters
		 * out all parameters supposly global to all entries
		 * then applies selective validation as before
		 */

		$validators = new $validatorsClassName($this);
		$validglobl = array();
		foreach ($this->parameters as $idx => $obj)
			if (!is_int($idx))
				$validglobl[$idx] = $obj;
		foreach ($this->parameters as $idx => $obj) {
			if (is_int($idx)) {
				// process
				$validator = new $validatorClassName($this);
				$validator->loadFromSession($this->getClassName() . \'_\' . $idx);
				$validator->overwriteArray($validglobl);
				$validator->overwriteArray($obj);
				$validator->useRules(\'validationRules.\');
				$validator->validate();

				$validators->append($validator);
			}
		}

		return $validators;
	}
';

			foreach($actions as $action) {
				if($action[controller] != $kk)
					continue;

				$indexContent .= $this->generateAction($action, $cN);
			}

			if(count($ajaxed)) {
				$indexContent .= $this->getXajaxCode();
			}

			$indexContent .= '}'."\n";
/* --------------------------------------------------------------------------------------------------- */

			if ($contr_name == 'aux')
			$indexContent = '
tx_div::load(\'tx_lib_controller\');

class '.$cN.'_controller_'.$contr_name.' extends tx_lib_controller {

	var $targetControllers = array('.implode(',', $ajaxed).');

	function '.$cN.'_controller_'.$contr_name.'($parameter1 = null, $parameter2 = null) {
		parent::tx_lib_controller($parameter1, $parameter2);
		$this->setDefaultDesignator(\''.$contr_name.'\');
	}

	function renderAction() {
		$modelClassName = tx_div::makeInstanceClassName(\''.$cN.'_model_aux\');
		$viewClassName = tx_div::makeInstanceClassName(\''.$cN.'_view_aux_display\');
	//	$entryClassName = tx_div::makeInstanceClassName($this->configurations->get(\'entryClassName\'));
		$entryClassName = tx_div::makeInstanceClassName(\'tx_lib_phpTemplateEngine\');

		/* model & view implement iterators! */
		$model = new $modelClassName($this);
		$view = new $viewClassName($this);

		/* step through the model-results */
		$model->load($this->parameters);
	//	for($model->rewind(); $model->valid(); $model->next()) {
	//		$entry = new $entryClassName($model->current(), $this);
		foreach($model as $key => $value) {
			$entry = new $entryClassName($this, $value);
			$view->append($entry);
		}

		/* render as view */
		$view->setPathToTemplateDirectory($this->configurations->get(\'templatePath\'));
		$view->render(\'aux_\' . $this->parameters[\'form\']);

		/* translation is done by the parent */
		return $view->get(\'_content\');
	}
}
';

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

    /**
     * Generates the flexform for this plugin
     *
     * @param       string           $extKey: current extension key
     * @param       integer          $k: current number of plugin
     */
	function generateFlexform($extKey, $k) {
		$this->pObj->addFileToFileArray(
			'configurations/mvc'.$k.'/flexform.xml',
			t3lib_div::getUrl(t3lib_extMgm::extPath('kickstarter__mvc_ex').'templates/template_flexform.xml')
		);
	}

	function checkForAjax($k) {
		$ajaxed = array();

        	$actions = $this->pObj->wizard->wizArray['mvcaction'];
        	if(!is_array($actions))
        		return array();

		foreach($actions as $action) {
			if($action[controller] != $k)
				continue;

			$action_title = $this->generateName($action[title],0,0,$action[freename]);
			if(!trim($action_title))
				continue;

			if($action['plus_ajax'])
				$ajaxed[] = '\''.$action_title.'Action\'';
		}

		if(count($ajaxed) > 0)
			$this->wizard->EM_CONF_presets['dependencies'][] = 'xajax';

		return $ajaxed;
	}

	function getXajaxCode() {
		return '
/* --------------------------------------------------------------------------------------------------- */
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
/* --------------------------------------------------------------------------------------------------- */
	}

	function getXajaxPage($type, $classname) {
/* --------------------------------------------------------------------------------------------------- */
		return '
		# The ajax response
ajaxResponse = PAGE
ajaxResponse.typeNum = '.$type.'
ajaxResponse.config.disableAllHeaderCode = true
ajaxResponse.50 = USER_INT
ajaxResponse.50 {
	userFunc = '.$classname.'_controller->main
}
';
/* --------------------------------------------------------------------------------------------------- */
	}

	function getXajaxPageSwitch($type, $actions) {
		$i = 10;
		$lines = '
		# The ajax response
ajaxResponse = PAGE
ajaxResponse.typeNum = '.$type.'
ajaxResponse.config.disableAllHeaderCode = true
ajaxResponse.50 = CASE
ajaxResponse.50 {
	key.data = GPvar:action
';
		foreach($actions as $a) {
			$lines .= '  '.trim($a,'\'').' = USER_INT
  '.trim($a,'\'').' {
	userFunc = '.$classname.'_controller->main
#	setupPath = plugin.'.$classname.'.configurations.
	configurations < plugin.'.$classname.'.configurations
  }
';
			$i += 10;
		}
		$lines .= '}
';
		return $lines;
	}

	function getDefaultAction($k) {
        	$actions = $this->pObj->wizard->wizArray['mvcaction'];
        	if(!is_array($actions))
        		return '';

		foreach($actions as $action) {
			if($action[controller] != $k)
				continue;

			$action_title = $this->generateName($action[title],0,0,$action[freename]);
			if(!trim($action_title))
				continue;

			if($action[defaction] == 1)
				return $action_title;
		}

		return '';
	}
}


// Include ux_class extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/renderer/class.tx_kickstarter_renderer_base.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/kickstarter__mvc_ex/renderer/class.tx_kickstarter_renderer_base.php']);
}

?>
