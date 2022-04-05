<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
*  All rights reserved
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
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the creditpoints field view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_field_creditpoints_view extends tx_ttproducts_field_base_view {
	protected $addedFieldArray = array('creditpoints_missing', 'creditpoints_remaining');

	public function modifyItemSubpartRow ($fieldname, $row, &$addedFieldArray) {
		$modelObj = $this->getModelObj();
		$rc = $row;
		$creditpointsMissing = 0;
		$creditpointsRemaining = 0;
		$modelObj->getMissingCreditpoints($fieldname, $row, $creditpointsMissing, $creditpointsRemaining);
		$rc['creditpoints_missing'] = $creditpointsMissing;
		$rc['creditpoints_remaining'] = $creditpointsRemaining;
		$addedFieldArray = array_merge($addedFieldArray, $this->addedFieldArray);
		return $rc;
	}

	public function getRowMarkerArray (
		$functablename,
		$fieldname,
		$row,
		$markerKey,
		&$markerArray,
		$fieldMarkerArray,
		$tagArray,
		$theCode,
		$id,
		$basketExtra,
		$basketRecs,
		&$bSkip,
		$bHtml = true,
		$charset = '',
		$prefix = '',
		$suffix = '',
		$imageNum = 0,
		$imageRenderObj = '',
		$linkWrap = false,
		$bEnableTaxZero = false
	) {
		$modifiedRow = array();
		$value =
			$this->getModelObj()->getFieldValue(
				$dummy,
				$row,
				$fieldname,
				$basketExtra,
				$basketRecs,
				$dummy
			);

		$value = number_format($value,'0');
		$modifiedRow[$fieldname] = $value;
		foreach ($this->addedFieldArray as $addedField) {
			$value =
				$this->getModelObj()->getFieldValue(
					$dummy,
					$row,
					$addedField,
					$basketExtra,
					$basketRecs,
					$dummy
				);
			$modifiedRow[$addedField] = number_format($value,'0');
		}
		return $modifiedRow;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_creditpoints_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/field/class.tx_ttproducts_field_creditpoints_view.php']);
}


