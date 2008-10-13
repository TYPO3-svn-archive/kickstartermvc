<?php
/***************************************************************
*  Copyright notice
*
*  (c)  2008 Niels Fröhling (niels@frohling.biz)  All rights reserved
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
 * @author  Niels Fröhling <niels@frohling.biz>
 */

$GLOBALS['KSRE'] = array(
	/* -------------------------------------------------------------------------------------- */
	'tx_dam' => array(
		'tablename' => 'tx_dam',
		'title' => 'DAM Media',
		'intro' => '',
		'localization' => 1,
		'versioning' => 1,
		'add_hidden' => 1,
		'add_deleted' => 1,
		'add_starttime' => 1,
		'add_endtime' => 1,
	//	'sorting_field' => 'sorting',
		'header_field' => 'title',
		'fields' => array(
//			'fieldname'
//			'title'
//			'help'
//			'type'
//			'conf_required'
//			'conf_rel_type'
//			'conf_rel_table'
//			'conf_relations'
//			'conf_relations_selsize'
//			'conf_custom_table_name'
//			'conf_select_items'
//			'conf_select_item_'.$num
//			'conf_size'
//			'conf_max'
//			'conf_rows'
//			'conf_cols'
//			'conf_eval'
		/*	array(
				'fieldname' => 'media_type',
				'title' => 'Mediatype',
				'type' => 'select',
				'conf_select_items' => 13,
				'conf_select_item1' => 'LLL:EXT:dam/locallang_db.xml:media_type.text',
				'conf_select_item2' => 'LLL:EXT:dam/locallang_db.xml:media_type.image',
				'conf_select_item3' => 'LLL:EXT:dam/locallang_db.xml:media_type.audio',
				'conf_select_item4' => 'LLL:EXT:dam/locallang_db.xml:media_type.video',
				'conf_select_item9' => 'LLL:EXT:dam/locallang_db.xml:media_type.dataset',
				'conf_select_item5' => 'LLL:EXT:dam/locallang_db.xml:media_type.interactive',
				'conf_select_item11' => 'LLL:EXT:dam/locallang_db.xml:media_type.software',
				'conf_select_item8' => 'LLL:EXT:dam/locallang_db.xml:media_type.model',
				'conf_select_item7' => 'LLL:EXT:dam/locallang_db.xml:media_type.font',
				'conf_select_item10' => 'LLL:EXT:dam/locallang_db.xml:media_type.collection',
				'conf_select_item6' => 'LLL:EXT:dam/locallang_db.xml:media_type.service',
				'conf_select_item12' => 'LLL:EXT:dam/locallang_db.xml:media_type.application',
				'conf_select_item0' => 'LLL:EXT:dam/locallang_db.xml:media_type.undefined',
			),	*/
			array(
				'fieldname' => 'title',
				'title' => 'Title',
				'type' => 'input',
			),
			array(
				'fieldname' => 'category',
				'title' => 'Category',
				'type' => 'rel',
				'conf_rel_type' => 'select_storage',
				'conf_rel_table' => 'tx_dam_cat',
				'conf_relations' => 1000,
				'conf_relations_selsize' => 1000,
			),

			/* ---------------------------------------------------------------------- */
			array(
				'fieldname' => 'keywords',
				'title' => 'Keywords',
				'type' => 'textarea',
			),
			array(
				'fieldname' => 'caption',
				'title' => 'Caption',
				'type' => 'textarea',
			),
			array(
				'fieldname' => 'description',
				'title' => 'Description',
				'type' => 'textarea',
			),
			array(
				'fieldname' => 'meta',
				'title' => 'Informations',
				'type' => 'textarea',
			),

			/* ---------------------------------------------------------------------- */
			array(
				'fieldname' => 'creator',
				'title' => 'Creator',
				'type' => 'input',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'publisher',
				'title' => 'Publisher',
				'type' => 'input',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'copyright',
				'title' => 'Copyright',
				'type' => 'input',
				'conf_automatic' => 1
			),

			/* ---------------------------------------------------------------------- */
			array(
				'fieldname' => 'file_size',
				'title' => 'File size',
				'type' => 'input+',
				'conf_eval' => 'int',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'height_unit',
				'title' => 'Unit',
				'type' => 'input',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'hres',
				'title' => 'Width (Units)',
				'type' => 'input+',
				'conf_eval' => 'double2',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'vres',
				'title' => 'Height (Units)',
				'type' => 'input+',
				'conf_eval' => 'double2',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'width',
				'title' => 'Width (Units)',
				'type' => 'input+',
				'conf_eval' => 'double2',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'height',
				'title' => 'Height (Units)',
				'type' => 'input+',
				'conf_eval' => 'double2',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'hpixels',
				'title' => 'Width (Pixels)',
				'type' => 'input+',
				'conf_eval' => 'int',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'vpixels',
				'title' => 'Height (Pixels)',
				'type' => 'input+',
				'conf_eval' => 'int',
				'conf_automatic' => 1
			),
			array(
				'fieldname' => 'color_space',
				'title' => 'Colorspace',
				'type' => 'input',
				'conf_automatic' => 1
			),
		)
	),
	/* -------------------------------------------------------------------------------------- */
	'tx_dam_cat' => array(
		'tablename' => 'tx_dam_cat',
		'title' => 'DAM Category',
		'intro' => '',
		'add_hidden' => 1,
		'header_field' => 'title',
		'fields' => array(
			array(
				'fieldname' => 'title',
				'title' => 'Title',
				'type' => 'input',
			),
			array(
				'fieldname' => 'nav_title',
				'title' => 'Navtitle',
				'type' => 'textarea',
			),
			array(
				'fieldname' => 'subtitle',
				'title' => 'Subtitle',
				'type' => 'textarea',
			),

			/* ---------------------------------------------------------------------- */
			array(
				'fieldname' => 'keywords',
				'title' => 'Keywords',
				'type' => 'textarea',
			),
			array(
				'fieldname' => 'description',
				'title' => 'Description',
				'type' => 'textarea',
			),
		)
	),
);
?>