<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2008 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the order addresses
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



class tx_ttproducts_orderaddress_view extends tx_ttproducts_table_base_view {
	var $dataArray; // array of read in frontend users
	var $table;		 // object of the type tx_table_db
	var $fields = array();
	var $tableconf;
	var $piVar = 'fe';
	var $marker = 'FEUSER';
	var $image;


	public function getWrappedSubpartArray(
		$viewTagArray,
		$bUseBackPid,
		&$subpartArray,
		&$wrappedSubpartArray
	) {
		$marker = 'FE_GROUP';
		$markerLogin = 'LOGIN';
		$markerNologin = 'NOLOGIN';
		foreach ($viewTagArray as $tag => $value) {
			if (strpos($tag, $marker . '_') === 0) {
				$tagPart1 = substr($tag, strlen($marker . '_'));
				$offset = strpos($tagPart1, '_TEMPLATE');
				if ($offset > 0) {
					$groupNumber = substr($tagPart1, 0, $offset);

					if (tx_div2007_core::testInt($groupNumber)) {
						if (t3lib_div::inList($GLOBALS['TSFE']->gr_list, $groupNumber)) {
							$wrappedSubpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = array('', '');
						} else {
							$subpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = '';
						}
					}
				}
			} else if (strpos($tag, $markerLogin . '_') === 0) {
                if (
                    $GLOBALS['TSFE']->loginUser &&
                    isset($GLOBALS['TSFE']->fe_user->user) &&
                    is_array($GLOBALS['TSFE']->fe_user->user) &&
                    isset($GLOBALS['TSFE']->fe_user->user['uid'])
                ) {
					$wrappedSubpartArray['###LOGIN_TEMPLATE###'] = array('', '');
				} else {
					$subpartArray['###LOGIN_TEMPLATE###'] = '';
				}
			} else if (strpos($tag, $markerNologin . '_') === 0) {
                if (
                    isset($GLOBALS['TSFE']->fe_user->user) &&
                    is_array($GLOBALS['TSFE']->fe_user->user) &&
                    isset($GLOBALS['TSFE']->fe_user->user['uid'])
                ) {
					$subpartArray['###NOLOGIN_TEMPLATE###'] = '';
				} else {
					$wrappedSubpartArray['###NOLOGIN_TEMPLATE###'] = array('', '');
				}
			}
		}

		if (
			isset($viewTagArray['FE_CONDITION1_TRUE_TEMPLATE']) ||
			isset($viewTagArray['FE_CONDITION1_FALSE_TEMPLATE'])
		) {
			if ($this->getModelObj()->getCondition() || !$this->getModelObj()->getConditionRecord()) {
				$wrappedSubpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = array('', '');
				$subpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = '';
			} else {
				$wrappedSubpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = array('', '');
				$subpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = '';
			}
		}
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	function getAddressMarkerArray ($row, &$markerArray, $bSelect, $type)	{
		global $TCA;

		$fieldOutputArray = array();
		$modelObj = $this->getModelObj();
		$selectInfoFields = $modelObj->getSelectInfoFields();

		if ($bSelect)	{
			t3lib_div::requireOnce (PATH_BE_ttproducts . 'lib/class.tx_ttproducts_form_div.php');
			foreach ($selectInfoFields as $field) {
				$tablename = $modelObj->getTCATableFromField($field);

				$fieldOutputArray[$field] =

					tx_ttproducts_form_div::createSelect(
						$this->langObj,
						$TCA[$tablename]['columns'][$field]['config']['items'],
						'recs['.$type.'][' . $field . ']',
						(is_array($row) ? $row[$field] : ''),
						TRUE,
						TRUE,
						array(),
						'select',
						array('id' => 'field_' . $type . '_' . $field) /* Add ID for field to be able to use labels. */
					);
			}
		} else {
			foreach ($selectInfoFields as $field) {
				$tablename = $modelObj->getTCATableFromField($field);
				$itemConfig = $TCA[$tablename]['columns'][$field]['config']['items'];

				if ($row[$field] != '' && isset($itemConfig) && is_array($itemConfig)) {

					$tcaValue = '';
					foreach ($itemConfig as $subItemConfig) {
						if (isset($subItemConfig) && is_array($subItemConfig) && $subItemConfig['1'] == $row[$field]) {
							$tcaValue = $subItemConfig['0'];
							break;
						}
					}

					$tmp = tx_div2007_alpha5::sL_fh002($tcaValue);
					$fieldOutputArray[$field] = htmlspecialchars(tx_div2007_alpha5::getLL_fh003($this->langObj, $tmp));
				} else {
					$fieldOutputArray[$field] = '';
				}
			}
		}

		foreach ($selectInfoFields as $field) {
			$markerkey = '###' . ($type == 'personinfo' ? 'PERSON' : 'DELIVERY') . '_'. strtoupper($field) . '###';
			$markerArray[$markerkey] = $fieldOutputArray[$field];
		}
	} // getRowMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_orderaddress_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_orderaddress_view.php']);
}


?>
