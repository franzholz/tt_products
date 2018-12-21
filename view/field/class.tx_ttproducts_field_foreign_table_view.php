<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2008 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License or
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
 * Part of the tt_products (Shop System) extension.
 *
 * foreign table view functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_field_foreign_table_view extends tx_ttproducts_field_base_view {

	public function getItemSubpartArrays (&$templateCode, $functablename, &$row, $fieldname, &$subpartArray, &$wrappedSubpartArray, &$tagArray, $theCode='', $id='1')	{
		global $TCA;

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$itemTableObj = $tablesObj->get($functablename, FALSE);
		$tablename = $itemTableObj->getTablename();
	}


	public function getRowMarkerArray ($functablename, $fieldname, &$row, $markerKey, &$markerArray, $tagArray, $theCode, $id, &$bSkip, $bHtml=TRUE, $charset='', $prefix='', $suffix='', $imageRenderObj='')	{
		global $TCA;

		$tablesObj = t3lib_div::makeInstance('tx_ttproducts_tables');
		$itemTableObj = $tablesObj->get($functablename, FALSE);
		$tablename = $itemTableObj->getTablename();
		$foreigntablename = '';
		$rowMarkerArray = array();
		if ($TCA[$tablename]['columns'][$fieldname]['config']['type'] == 'group')	{
			$foreigntablename = $TCA[$tablename]['columns'][$fieldname]['config']['allowed'];
			$foreignTableViewObj = $tablesObj->get($foreigntablename,TRUE);
			if (!$row[$fieldname])	{
				$foreignMarker = $foreignTableViewObj->getMarker();

				foreach ($tagArray as $theTag => $v)	{
					if (strpos($theTag,$foreignMarker) === 0)	{
						$rowMarkerArray['###'.$theTag.'###'] = '';
					}
				}
			}
		}

		if ($foreigntablename != '' && $row[$fieldname] > 0)	{
/*			$tableClass = $tablesObj->getTableClass ($foreigntablename, TRUE);
			$foreignTableViewObj = t3lib_div::makeInstance($tableClass);*/

			$foreignTableObj = $foreignTableViewObj->getModelObj();
			if ($TCA[$tablename]['columns'][$fieldname]['config']['internal_type'] == 'db')	{
				$foreignRow = $foreignTableObj->get($row[$fieldname]);
				$foreignTableViewObj->getRowMarkerArray (
					$foreignRow,
					'',
					$rowMarkerArray,
					$tmp=array(),
					$tmp=array(),
					$tagArray,
					$theCode,
					$bHtml,
					$charset,
					0,
					$imageRenderObj,
					$id,
					$prefix,
					$suffix,
					''
				);
			}
		//
		}
		$markerArray = array_merge ($markerArray, $rowMarkerArray);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_foreign_table_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_foreign_table_view.php']);
}


?>
