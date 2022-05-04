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
 * category single view functions
 * may be used for the category, party/partner/address, dam category and pages table
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_cat_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;

	public $urlObj; // url functions
	public $pid; // PID where to go
	public $pidListObj;
	public $cOjb;

	public function init(
		&$pibase,
		$pid,
		$pid_list,
		$recursive
	) {
		$this->pibase = $pibase;
		$this->cObj = $pibase->cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->conf;
		$this->config = $cnf->config;

		$this->pid = $pid;
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');

		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();
	}


	// returns the single view
	public function printView (
		$templateCode,
		$functablename,
		$uid,
		$theCode,
		&$error_code,
		$templateSuffix = ''
	) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$tableViewObj = $tablesObj->get($functablename, true);
		$tableObj = $tableViewObj->getModelObj();
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$variantFieldArray = array();
		$piVars = tx_ttproducts_model_control::getPiVars();

		if ($this->config['displayCurrentRecord']) {
			$row = $this->cObj->data;
		} else if ($uid) {
			$pidField = ($functablename == 'pages' ? 'uid' : 'pid');
			$where = $pidField . ' IN (' . $this->pidListObj->getPidlist() . ')';
			$row = $tableObj->get($uid, 0, true, $where);
			$row = $tableObj->get($uid, 0, true, $where);
			$tableConf = $cnf->getTableConf($functablename, $theCode);
			$tableObj->clear();
			$tableObj->initCodeConf($theCode,$tableConf);
			$tableLangFields = $cnf->getTranslationFields($tableConf);
		}

		foreach ($tableLangFields as $type => $fieldArray) {
			if (is_array($fieldArray)) {
				foreach ($fieldArray as $field => $langfield) {
					$row[$field] = $row[$langfield];
				}
			}
		}

		if ($row) {
			// $this->uid = intval ($row['uid']); // store the uid for later usage here

			$markerArray = array();
			$subpartArray = array();
			$wrappedSubpartArray = array();
			$pageObj = $tablesObj->get('pages');

			if ($this->config['displayCurrentRecord']) {
				$subPartMarker = '###' . $tableViewObj->marker . '_SINGLE_DISPLAY_RECORDINSERT###';
			} else {
				$subPartMarker = '###' . $tableViewObj->marker . '_SINGLE_DISPLAY###';
			}

			// Add the template suffix
			$subPartMarker = substr($subPartMarker, 0, -3) . $templateSuffix . '###';
			$itemFrameWork = tx_div2007_core::getSubpart($templateCode, $subpartmarkerObj->spMarker($subPartMarker));
			if (!$itemFrameWork) {
				$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
				$error_code[0] = 'no_subtemplate';
				$error_code[1] = '###' . $subPartMarker . '###';
				$error_code[2] = $templateObj->getTemplateFile();
				return '';
			}

			$viewTagArray = $markerObj->getAllMarkers($itemFrameWork);
			$tablesObj->get('fe_users', true)->getWrappedSubpartArray(
				$viewTagArray,
				$bUseBackPid,
				$subpartArray,
				$wrappedSubpartArray
			);

			$itemFrameWork = tx_div2007_core::substituteMarkerArrayCached($itemFrameWork, $markerArray, $subpartArray, $wrappedSubpartArray);
			$markerFieldArray = array();
			$viewTagArray = array();
			$parentArray = array();
			$fieldsArray = $markerObj->getMarkerFields(
				$itemFrameWork,
				$tableObj->getTableObj()->tableFieldArray,
				$tableObj->getTableObj()->requiredFieldArray,
				$markerFieldArray,
				$tableViewObj->marker,
				$viewTagArray,
				$parentArray
			);

				// Fill marker arrays
			$backPID = $piVars['backPID'] ?? '';
			$backPID = ($backPID ? $backPID : GeneralUtility::_GP('backPID'));
			$basketPID = $this->conf['PIDbasket'];
			$pid = $backPID;

/*			$param = array($functablename => $variantFieldArray);
			$javaScriptObj->set('fetchdata', $param);*/
			$bUseBackPid = true;

			$addQueryString = array();
			$linkPid = $pid;
			if ($bUseBackPid && $backPID) {
				$linkPid = $backPID;
			}

			if (isset($viewTagArray['LINK_ITEM'])) {
				$queryString =
					$this->urlObj->getLinkParams(
						'',
						$addQueryString,
						true,
						$bUseBackPid,
						0,
						'product',
						$tableViewObj->piVar
					);
				$linkUrl = FrontendUtility::getTypoLink_URL(
					$this->cObj,
					$linkPid,
					$queryString,
					'',
					array(
						'useCacheHash' => true
					)
				);
				$wrappedSubpartArray['###LINK_ITEM###'] = array(
					'<a class="listlink" href="' . htmlspecialchars($linkUrl) . '">',
					'</a>'
				);

// 				$wrappedSubpartArray['###LINK_ITEM###'] = array(
// 						'<a href="'
// 						. htmlspecialchars(
// 							$this->pibase->pi_getPageLink(
// 								$linkPid,
// 								'',
// 								$this->urlObj->getLinkParams(
// 									'',
// 									$addQueryString,
// 									true,
// 									$bUseBackPid,
//									0,
// 									'product',
// 									$tableViewObj->piVar
// 								),
// 								array(
// 									'useCacheHash' => true
// 								)
// 							)
// 						)
// 						.'">',
// 					'</a>'
// 				);
			}
			if (isset($viewCatTagArray['LINK_CATEGORY'])) {
				$catListPid = $pageObj->getPID(
					$this->conf['PIDlistDisplay'],
					$this->conf['PIDlistDisplay.'],
					$row
				);
				$tableViewObj->getSubpartArrays(
					$this->urlObj,
					$row,
					$subpartArray,
					$wrappedSubpartArray,
					$viewTagArray,
					$catListPid,
					'LINK_CATEGORY'
				);
			}

			$viewParentCatTagArray = array();
			$tableViewObj->getParentMarkerArray (
				$parentArray,
				$row,
				$catParentArray,
				$uid,
				$row['pid'],
				$this->config['limitImage'],
				'listcatImage',
				$viewParentCatTagArray,
				array(),
				($functablename == 'pages'),
				$theCode,
				tx_ttproducts_control_basket::getBasketExtra(),
				tx_ttproducts_control_basket::getRecs(),
				1,
				''
			);

			if (isset($viewCatTagArray['LINK_PARENT1_CATEGORY'])) {
				$catRow = $tableObj->getParent($cat);
				$catListPid =
					$pageObj->getPID(
						$this->conf['PIDlistDisplay'],
						$this->conf['PIDlistDisplay.'],
						$catRow
					);
				$viewCatTable->getSubpartArrays(
					$this->urlObj,
					$catRow,
					$subpartArray,
					$wrappedSubpartArray,
					$viewTagArray,
					$catListPid,
					'LINK_PARENT1_CATEGORY'
				);
			}

			$pageAsCategory = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['pageAsCategory'];

			$tableViewObj->getMarkerArray(
				$markerArray,
				'',
				$uid,
				$row['pid'],
				10,
				'image',
				$viewTagArray,
				array(),
				$pageAsCategory,
				$theCode,
				tx_ttproducts_control_basket::getBasketExtra(),
				tx_ttproducts_control_basket::getRecs(),
				'',
				'',
				''
			);

			$subpartArray = array();
			$markerArray['###FORM_NAME###'] = $forminfoArray['###FORM_NAME###'];
			$addQueryString = array();
			if ($pid == $GLOBALS['TSFE']->id) {
				$addQueryString[$tableViewObj->getPivar()] = $uid;
			}

			$markerArray =
				$this->urlObj->addURLMarkers(
					$pid,
					$markerArray,
					$addQueryString
				); // Applied it here also...

			$queryPrevPrefix = '';
			$queryNextPrefix = '';
			$prevOrderby = '';
			$nextOrderby = '';

			if(is_array($tableConf) && isset($tableConf['orderBy']) && strpos($itemTableConf['orderBy'],',') === false) {
				$orderByField = $tableConf['orderBy'];
				$queryPrevPrefix = $orderByField . ' < ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row[$orderByField], $tableObj->getTableObj()->name);
				$queryNextPrefix = $orderByField . ' > ' . $GLOBALS['TYPO3_DB']->fullQuoteStr($row[$orderByField], $tableObj->getTableObj()->name);
				$prevOrderby = $orderByField . ' DESC';
				$nextOrderby = $orderByField . ' ASC';
			} else {
				$queryPrevPrefix = 'uid < ' . intval($uid);
				$queryNextPrefix = 'uid > ' . intval($uid);
				$prevOrderby = 'uid DESC';
				$nextOrderby = 'uid ASC';
			}

			$prevOrderby = $tableObj->getTableObj()->transformOrderby($prevOrderby);
			$nextOrderby = $tableObj->getTableObj()->transformOrderby($nextOrderby);

			$whereFilter = '';
			if (is_array($tableConf['filter.']) && is_array($tableConf['filter.']['regexp.'])) {
				if (is_array($tableConf['filter.']['regexp.']['field.'])) {
					foreach ($tableConf['filter.']['field.'] as $field => $value) {
						$whereFilter .= ' AND ' . $field . ' REGEXP ' . $GLOBALS['TYPO3_DB']->fullQuoteStr(quotemeta($value), $tableObj->getTableObj()->name);
					}
				}
			}

			$queryprev = '';
			$queryprev = $queryPrevPrefix . ' AND pid IN (' . $this->pidListObj->getPidlist() . ')' .  $tableObj->getTableObj()->enableFields();
			// $resprev = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $queryprev,'', $prevOrderby);
			$resprev = $tableObj->getTableObj()->exec_SELECTquery('*', $queryprev, '', $GLOBALS['TYPO3_DB']->stripOrderBy($prevOrderby));

			if ($rowprev = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resprev) ) {
				$addQueryString = array();
				$addQueryString[$tableViewObj->getPivar()] = $rowprev['uid'];

				if ($bUseBackPid) {
					$addQueryString['backPID'] = $backPID;
				}
				$queryString =
					$this->urlObj->getLinkParams(
						'',
						$addQueryString,
						true,
						$bUseBackPid,
						0,
						'product',
						''
					);
				$linkUrl = FrontendUtility::getTypoLink_URL(
					$this->cObj,
					$GLOBALS['TSFE']->id,
					$queryString,
					'',
					array(
						'useCacheHash' => true
					)
				);
				$wrappedSubpartArray['###LINK_PREV_SINGLE###'] = array(
					'<a href="' . htmlspecialchars($linkUrl) . '">',
					'</a>'
				);

// 				$wrappedSubpartArray['###LINK_PREV_SINGLE###']= array(
// 					'<a href="' .
// 					htmlspecialchars(
// 						$this->pibase->pi_getPageLink(
// 							$GLOBALS['TSFE']->id,
// 							'',
// 							$this->urlObj->getLinkParams(
// 								'',
// 								$addQueryString,
// 								true,
// 								$bUseBackPid,
// 								0,
// 								'product',
// 								''
// 							),
// 							array(
// 								'useCacheHash' => true
// 							)
// 						)
// 					) . '">',
// 					'</a>');
			} else	{
				$subpartArray['###LINK_PREV_SINGLE###']='';
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resprev);

			$querynext = $queryNextPrefix.' AND pid IN (' . $this->pidListObj->getPidlist() . ')' .  $wherestock . $tableObj->getTableObj()->enableFields();
			// $resnext = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products', $querynext, $nextOrderby);
			$resnext = $tableObj->getTableObj()->exec_SELECTquery('*', $querynext, '', $GLOBALS['TYPO3_DB']->stripOrderBy($nextOrderby));

			if ($rownext = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resnext)) {
				$addQueryString=array();
				$addQueryString[$this->type] = $rownext['uid'];
				$addQueryString['backPID'] = $backPID;
				if ($bUseBackPid) {
					$addQueryString['backPID'] = $backPID;
				} else if ($cat) {
					$addQueryString[$viewCatTable->getPivar()] = $linkCat;
				}

				$queryString =
					$this->urlObj->getLinkParams(
						'',
						$addQueryString,
						true,
						$bUseBackPid,
						0,
						'product',
						''
					);
				$linkUrl = FrontendUtility::getTypoLink_URL(
					$this->cObj,
					$GLOBALS['TSFE']->id,
					$queryString,
					'',
					array(
						'useCacheHash' => true
					)
				);
				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array(
					'<a href="' . htmlspecialchars($linkUrl) . '">',
					'</a>'
				);

// 				$wrappedSubpartArray['###LINK_NEXT_SINGLE###'] = array(
// 					'<a href="' .
// 						htmlspecialchars(
// 							$this->pibase->pi_getPageLink(
// 								$GLOBALS['TSFE']->id,
// 								'',
// 								$this->urlObj->getLinkParams(
// 									'',
// 									$addQueryString,
// 									true,
// 									$bUseBackPid,
// 									0,
// 									'product',
// 									''
// 								),
// 								array('useCacheHash' => true)
// 							)
// 						) . '">',
// 					'</a>');
			} else {
				$subpartArray['###LINK_NEXT_SINGLE###'] = '';
			}
			$GLOBALS['TYPO3_DB']->sql_free_result($resnext);

				// Substitute
			$content =
				tx_div2007_core::substituteMarkerArrayCached(
					$itemFrameWork,
					$markerArray,
					$subpartArray,
					$wrappedSubpartArray
				);
		} else {
			$error_code[0] = 'wrong_parameter';
			$error_code[1] = (($functablename == 'pages') ? 'page' : 'cat');
			$error_code[2] = intval($uid);
			$error_code[3] = $this->pidListObj->getPidlist();
		}
		return $content;
	} // print
}

