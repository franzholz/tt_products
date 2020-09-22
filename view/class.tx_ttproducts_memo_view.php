<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2010 Franz Holzinger (franz@ttproducts.de)
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
 * @author  Klaus Zierer <zierer@pz-systeme.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_memo_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $cObj;
	public $pid_list;
	public $pid; // pid where to go
	public $useArticles;
	public $memoItems;
	public $pibaseClass;
	public $conf;

	public function init (
			$pibaseClass,
			$theCode,
			&$pid_list,
			$conf,
			$useArticles
		) {
		$this->pibaseClass = $pibaseClass;
		$pibaseObj = GeneralUtility::makeInstance('' . $pibaseClass);
		$this->cObj = $pibaseObj->cObj;
		$this->conf = $conf;

		$this->pid_list = $pid_list;
		$this->useArticles = $useArticles;

		if (
			tx_ttproducts_control_memo::bUseFeuser($conf) ||
			tx_ttproducts_control_memo::bUseSession($conf)
		) {
			$functablename = 'tt_products';
			$this->memoItems = tx_ttproducts_control_memo::getMemoItems($functablename);
		}
	}


	/**
	 * Displays the memo
	 */
	public function printView ($theCode, &$templateCode, $pid, &$errorCode)	{
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$content = '';

		if (
			tx_ttproducts_control_memo::bUseFeuser($this->conf) ||
			tx_ttproducts_control_memo::bUseSession($this->conf)
		) {
			if ($this->memoItems)	{
				// List all products:
				$listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
				$listView->init (
					$this->pibaseClass,
					$pid,
					$this->useArticles,
					array(),
					$this->pid_list,
					99
				);
				if ($theCode == 'MEMO')	{
					$theTable = 'tt_products';
					$templateArea = 'MEMO_TEMPLATE';
				} else {
					return 'error';
				}

				$content = $listView->printView(
					$templateCode,
					$theCode,
					$theTable,
					($this->memoItems ? implode(',', $this->memoItems) : array()),
					false,
					$errorCode,
					$templateArea,
					$GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'],
					array()
				);
			} else {
				$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
				$subpartmarkerObj->init(
					$this->cObj
				);

				$templateArea = 'MEMO_EMPTY';
				$content = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker('###' . $templateArea . '###'));
				$content = $markerObj->replaceGlobalMarkers($content);
			}
		} else if (tx_ttproducts_control_memo::bIsAllowed('fe_users', $this->conf)) {
			include_once (PATH_BE_ttproducts.'marker/class.tx_ttproducts_subpartmarker.php');

			$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
			$subpartmarkerObj->init(
				$this->cObj
			);

			$templateArea = 'MEMO_NOT_LOGGED_IN';
			$templateAreaMarker = $subpartmarkerObj->spMarker('###'.$templateArea.'###');
			$content = tx_div2007_core::getSubpart($templateCode, $templateAreaMarker);
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
		&$row,
		$markerKey,
		&$markerArray,
		$tagArray,
		&$bUseCheckBox
	)	{
		$pibaseObj = GeneralUtility::makeInstance(''.$this->pibaseClass);
		$fieldKey = 'FIELD_'.$markerKey.'_NAME';
		if (isset($tagArray[$fieldKey]))	{
			$markerArray['###'.$fieldKey.'###'] = $pibaseObj->prefixId.'[memo]['.$row['uid'].']';
		}
		$fieldKey = 'FIELD_'.$markerKey.'_CHECK';

		if (isset($tagArray[$fieldKey]))	{
			$bUseCheckBox = true;
			if (in_array($row['uid'], $this->memoItems))	{
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
	)	{

		if ($bUseCheckBox)	{
			$pibaseObj = GeneralUtility::makeInstance(''.$this->pibaseClass);
			$markerArray['###HIDDENFIELDS###'] .= '<input type="hidden" name="' . $pibaseObj->prefixId . '[memo][uids]" value="' . implode(',',$uidArray) . '" />';
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_memo_view.php']);
}



