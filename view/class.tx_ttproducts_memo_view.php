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
 * memo functions
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */
 
 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_memo_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pid_list;
	public $pid; // pid where to go
	public $useArticles;
	public $memoItems;


	public function init (
			$theCode,
			$pid_list,
			$conf,
			$useArticles
		) {
		$cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();

		$piVars = tx_ttproducts_model_control::getPiVars();

		$this->pid_list = $pid_list;
		$this->useArticles = $useArticles;
// 		$fe_user_uid = $GLOBALS['TSFE']->fe_user->user['uid'];

		$this->memoItems = array();

		if (
			tx_ttproducts_control_memo::bUseFeuser($conf) ||
			tx_ttproducts_control_memo::bUseSession($conf)
		) {
			$functablename = 'tt_products';
			if (strpos($theCode, 'DAM') !== false) {
				$functablename = 'tx_dam';
			}
			$this->memoItems = tx_ttproducts_control_memo::getMemoItems($functablename);
		}
	}


	/**
	 * Displays the memo
	 */
	public function printView (
		$templateCode,
		$theCode,
		$conf,
		$pid,
		&$errorCode
	) {
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$content = '';

		if (
			tx_ttproducts_control_memo::bUseFeuser($conf) ||
			tx_ttproducts_control_memo::bUseSession($conf)
		) {
			if ($this->memoItems) {

				// List all products:
				$listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
				$listView->init(
					$pid,
					array(),
					$this->pid_list,
					99
				);
				if ($theCode == 'MEMO') {
					$theTable = 'tt_products';
					$templateArea = 'MEMO_TEMPLATE';
				} else if ($theCode == 'MEMODAM') {
					$theTable = 'tx_dam';
					$templateArea = 'MEMODAM_TEMPLATE';
				} else if ($theCode == 'MEMODAMOVERVIEW') {
					$theTable = 'tx_dam';
					$templateArea = 'MEMODAM_OVERVIEW_TEMPLATE';
				} else {
					return 'error';
				}

				$content = $listView->printView(
					$templateCode,
					$theCode,
					$theTable,
					($this->memoItems ? implode(',', $this->memoItems) : array()),
					false,
					'',
					$errorCode,
					$templateArea,
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
					tx_ttproducts_control_basket::getBasketExtra(),
					tx_ttproducts_control_basket::getRecs(),
					array(),
					0
				);
			} else {
				$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
				$cObj = \JambageCom\Div2007\Utility\FrontendUtility::getContentObjectRenderer();

				$templateArea = 'MEMO_EMPTY';
				$content = tx_div2007_core::getSubpart($templateCode,$subpartmarkerObj->spMarker('###'.$templateArea.'###'));
				$content = $markerObj->replaceGlobalMarkers($content);
			}
		} else if (tx_ttproducts_control_memo::bIsAllowed('fe_users', $conf)) {
			$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');

			$templateArea = 'MEMO_NOT_LOGGED_IN';
// 			$templateAreaMarker = $subpartmarkerObj->spMarker('###'.$templateArea.'###');

			$content = tx_ttproducts_api::getErrorOut(
				$theCode,
				$templateCode,
				$subpartmarkerObj->spMarker('###' . $templateArea . $this->config['templateSuffix'] . '###'),
				$subpartmarkerObj->spMarker('###' . $templateArea . '###'),
				$errorCode
			) ;

			$content = $markerObj->replaceGlobalMarkers($content);
		}

		if (!$content && empty($errorCode)) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$errorCode[0] = 'no_subtemplate';
			$errorCode[1] = '###' . $templateArea . $templateObj->getTemplateSuffix() . '###';
			$errorCode[2] = $templateObj->getTemplateFile();
			$content = false;
		}
		return $content;
	}


	public function getFieldMarkerArray (
		$row,
		$markerKey,
		&$markerArray,
		$tagArray,
		&$bUseCheckBox
	)	{
		$fieldKey = 'FIELD_' . $markerKey . '_NAME';
		if (isset($tagArray[$fieldKey])) {
			$markerArray['###'.$fieldKey.'###'] = tx_ttproducts_model_control::getPrefixId() . '[memo][' . $row['uid'] . ']';
		}
		$fieldKey = 'FIELD_'.$markerKey.'_CHECK';

		if (isset($tagArray[$fieldKey])) {
			$bUseCheckBox = true;
			if (in_array($row['uid'], $this->memoItems)) {
				$value = 1;
			} else {
				$value = 0;
			}
			$checkString = ($value ? 'checked="checked"':'');
			$markerArray['###'.$fieldKey.'###'] = $checkString;
		} else {
			$bUseCheckBox = false;
		}
	}


	public function getHiddenFields (
		$uidArray,
		&$markerArray,
		$bUseCheckBox
	) {
		if ($bUseCheckBox) {
			$markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="' . tx_ttproducts_model_control::getPrefixId() . '[memo][uids]" value="' . implode(',',$uidArray) . '" />';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']);
}


