<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
 * order functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


use JambageCom\Div2007\Utility\FrontendUtility;



class tx_ttproducts_order_view extends tx_ttproducts_table_base_view {
	public $marker = 'ORDER';


	protected function init2 (
		$bValidUpdateCode,
		$theCode,
		&$pid_list,
		$recursive,
		$orderObj,
		$cnf,
		&$itemTable,
		&$functablename,
		&$tableconf,
		&$feusers_uid,
		&$validFeUser,
		&$pid,
		&$markerArray,
		&$orderMarker,
		&$feuserMarker,
		&$piVars,
		&$prefix
	) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');

		$pidListObj->applyRecursive($recursive, $pid_list, true);
		$pidListObj->setPageArray();
		$feusers_uid = 0;

		if (!$bValidUpdateCode) {
			$feusers_uid = tx_div2007::getFrontEndUser('uid');
		}

		$functablename = $orderObj->getFuncTablename();

		$tableconf = $cnf->getTableConf($functablename, $theCode);
		$validFeUser = false;
		if ($theCode == 'DOWNLOAD') {
			$downloadAuthorization = $cnf->getDownloadConf('authorization');
			if (
				$downloadAuthorization == 'FE' &&
				$feusers_uid > 0
			) {
				$validFeUser = true;
			}
		}

		$orderMarker = $this->getMarker();
		$feusersViewObj = $tablesObj->get('fe_users', true);
		$feuserMarker = $feusersViewObj->getMarker();
		$productFunctablename = 'tt_products';
		$itemTable = $tablesObj->get($productFunctablename); // order
		$piVars = tx_ttproducts_model_control::getPiVars();
		$prefix = tx_ttproducts_model_control::getPrefixId();

		$pid = $GLOBALS['TSFE']->id;
		$urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
		$addQueryString = '';
		$excludeList = '';
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$markerArray = $urlObj->addURLMarkers(
			$pid,
			$globalMarkerArray,
			$addQueryString,
			$excludeList,
			false,
			0
		);
	}


	static public function setFeuserMarker ($feuserMarker, $row, &$markerArray) {
		$markerArray['###' . $feuserMarker . '_NUMBER###'] = $row['uid'];
		$markerArray['###' . $feuserMarker . '_NAME###'] = $row['name'];
	}


	/** add the markers for uid, date and the tracking number which is stored in the basket recs */
	public function getBasketRecsMarkerArray (
		&$markerArray,
		$orderArray
	) {
        $cObj = FrontendUtility::getContentObjectRenderer();
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;

		if (
			isset($orderArray) &&
			is_array($orderArray) &&
			isset($orderArray['uid']) &&
			isset($orderArray['crdate']) &&
			isset($orderArray['tracking_code'])
		) {
				// order
			$orderObj = $this->getModelObj();

            $markerArray['###ORDER_UID###'] = $orderArray['uid'];
			$markerArray['###ORDER_ORDER_NO###'] = $orderObj->getNumber($orderArray['uid']);

            $markerArray['###ORDER_DATE###'] =
				$cObj->stdWrap(
					$orderArray['crdate'],
					$conf['orderDate_stdWrap.'] ?? ''
				);
			$markerArray['###ORDER_TRACKING_NO###'] = htmlspecialchars($orderArray['tracking_code']);
			$markerArray['###ORDER_BILL_NO###'] = $orderArray['bill_no'] ?? '';
		} else {
			$markerArray['###ORDER_UID###'] = '';
			$markerArray['###ORDER_ORDER_NO###'] = '';
			$markerArray['###ORDER_DATE###'] = '';
			$markerArray['###ORDER_TRACKING_NO###'] = '';
			$markerArray['###ORDER_BILL_NO###'] = '';
		}
	}


	public function getOrderMarkerSubpartArrays (
		$pibaseClass,
		$templateCode,
		$frameWork,
		$theCode,
		$pageAsCategory,
		$pid,
		$pid_list,
		$from,
		$where,
		$orderBy,
		&$markerArray,
		&$subpartArray,
		&$error_code
	) {
		if ($from == '') {
			$from = 'sys_products_orders';
		}
		$templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$cObj = FrontendUtility::getContentObjectRenderer();
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnf->conf;

        $res =
			$GLOBALS['TYPO3_DB']->exec_SELECTquery(
				'*',
				$from,
				$where . ($orderBy != '' ? ' ORDER BY ' . $orderBy : '')
			);
		$count = $GLOBALS['TYPO3_DB']->sql_num_rows($res);

		if ($count) {
			$orderObj = $this->getModelObj();
			$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

			// Fill marker arrays
			$tot_creditpoints_saved = 0;
			$tot_creditpoints_changed = 0;
			$tot_creditpoints_spended = 0;
			$tot_creditpoints_gifts = 0;
			$orderlistc = '';
			$this->orders = [];
			$orderitem = $templateService->getSubpart($frameWork, '###ORDER_ITEM###');
			$tablename = 'fe_users';

			while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$markerArray['###TRACKING_CODE###'] = $row['tracking_code'];
				$markerArray['###ORDER_DATE###'] = $cObj->stdWrap($row['crdate'], $conf['orderDate_stdWrap.'] ?? '');

				$markerArray['###ORDER_NUMBER###'] = $orderObj->getNumber($row['uid']);
				$markerArray['###ORDER_CREDITS###'] = $row['creditpoints_saved'] + $row['creditpoints_gifts'] - $row['creditpoints_spended'] - $row['creditpoints'];
				$markerArray['###ORDER_AMOUNT###'] = $priceViewObj->printPrice($priceViewObj->priceFormat($row['amount']));

				// total amount of saved creditpoints
				$tot_creditpoints_saved += $row['creditpoints_saved'];

				// total amount of changed creditpoints
				$tot_creditpoints_changed += $row['creditpoints'];

				// total amount of spended creditpoints
				$tot_creditpoints_spended += $row['creditpoints_spended'];

				// total amount of creditpoints from gifts
				$tot_creditpoints_gifts += $row['creditpoints_gifts'];
				$orderlistc .= $templateService->substituteMarkerArray($orderitem, $markerArray);
			}

			if (strpos($frameWork, '###CREDIT_POINTS_VOUCHER###') !== false) {

				$res2 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'username',
					$tablename,
					'tt_products_vouchercode=' .
						$GLOBALS['TYPO3_DB']->fullQuoteStr(
							$username, $tablename
						)
				);
				$num_rows = $GLOBALS['TYPO3_DB']->sql_num_rows($res2) * 5;
				$GLOBALS['TYPO3_DB']->sql_free_result($res2);
			} else {
				$num_rows = 0;
			}

			if (strpos($frameWork, '###CREDIT_POINTS_TOTAL###') !== false) {

				$res3 = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					'tt_products_creditpoints ',
					$tablename,
					'uid=' . intval($feusers_uid) . ' AND NOT deleted'
				);
				$totalcreditpoints = 0;

				if ($res3 !== false) {
					while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res3)) {
						$totalcreditpoints = $row['tt_products_creditpoints'];
					}
					$GLOBALS['TYPO3_DB']->sql_free_result($res3);
				}
			}

			$markerArray['###CREDIT_POINTS_SAVED###'] = number_format($tot_creditpoints_saved, 0);
			$markerArray['###CREDIT_POINTS_SPENT###'] = number_format($tot_creditpoints_spended, 0);
			$markerArray['###CREDIT_POINTS_CHANGED###'] = number_format($tot_creditpoints_changed, 0);
			$markerArray['###CREDIT_POINTS_USED###'] = number_format($tot_creditpoints_spended, 0) + number_format($tot_creditpoints_changed, 0);
			$markerArray['###CREDIT_POINTS_GIFTS###'] = number_format($tot_creditpoints_gifts, 0);
			$markerArray['###CREDIT_POINTS_TOTAL###'] = number_format($totalcreditpoints, 0);
			$markerArray['###CREDIT_POINTS_VOUCHER###'] = $num_rows;
			$markerArray['###CALC_DATE###'] = date('d M Y');
			$listFrameWork = $templateService->getSubpart($frameWork, '###ORDER_LIST###');

			$listSubpartArray = [];
			$listSubpartArray['###ORDER_ITEM###'] = $orderlistc;
			$listContent = $templateService->substituteMarkerArrayCached(
				$listFrameWork,
				$markerArray,
				$listSubpartArray
			);
			$subpartArray['###ORDER_LIST###'] = $listContent;
			$subpartArray['###ORDER_NOROWS###'] = '';
		} else {
			$subpartArray['###ORDER_LIST###'] = '';
			$wrappedSubpartArray['###ORDER_NOROWS###'] = '';
		}

		if ($res !== false) {
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
		}
	}


	public function getProductMarkerSubpartArrays (
		$pibaseClass,
		$templateCode,
		$frameWork,
		$subpartMarker,
		$theCode,
		$pageAsCategory,
		$pid,
		$pid_list,
		$from,
		$where,
		$orderBy,
		$whereProducts,
		$onlyProductsWithFalOrders,
		$hiddenFields,
		&$markerArray,
		&$subpartArray,
		&$error_code
	) {
		$content = '';
		$orderObj = $this->getModelObj(); // order

		$orderObj->getOrderedAndGainedProducts(
			$from,
			$where,
			$orderBy,
			$whereProducts,
			$onlyProductsWithFalOrders,
			$pid_list,
			$productRowArray,
			$multiOrderArray
		);

		if (
            is_array($productRowArray) &&
			count($productRowArray) &&
			strpos($frameWork, '###ORDER_PRODUCT_UID###') !== false
		) {
			$productUidArray = [];
			foreach ($productRowArray as $productRow) {
				$productUidArray[$productRow['uid']] = $productRow['uid'];
			}

			$allowedItems = implode(',', $productUidArray);
			$listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
			$listView->init(
				$pid,
				[],
				$pid_list,
				0
			);

            $notOverwritePriceIfSet = false;
			$content = $listView->printView(
				$templateCode,
				$theCode,
				'tt_products',
				$allowedItems,
				$pid_list,
				$hiddenFields,
				$error_code,
				$subpartMarker,
				$pageAsCategory,
				[],
				[],
				[],
				0,
				[],
				[],
				[],
				'',
				[],
				$notOverwritePriceIfSet,
				$multiOrderArray,
				$productRowArray,
				false
			);
		}

		$markerArray['###ORDER_PRODUCT_UID###'] = $content;
	}


	public function getFrameWork (
		$templateCode,
		$subPartMarker,
		$bValidUpdateCode,
		$trackingCode,
		$bNeedTrackingInfo,
		$feusers_uid,
		$validFeUser,
		&$error_code
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');

		if (
			$bValidUpdateCode ||
			$trackingCode ||
			$validFeUser
		) {
			// nothing
		} else if ($bNeedTrackingInfo) {
			$subPartMarker = 'TRACKING_ENTER_NUMBER';
		} elseif (!$feusers_uid) {
			$subPartMarker = 'MEMO_NOT_LOGGED_IN';
		} else {
			// nothing
		}

		$frameWork = $templateService->getSubpart(
			$templateCode,
			$subpartmarkerObj->spMarker('###' . $subPartMarker . '###')
		);

		if (!$frameWork) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$error_code[0] = 'no_subtemplate';
			$error_code[1] = '###' . $subPartMarker . '###';
			$error_code[2] = $templateObj->getTemplateFile();
		}

		return $frameWork;
	}


	public function processFeuserSelect (
		$piVars,
		$prefix,
		$tableconf,
		$pid_list,
		&$feusers_uid,
		&$hiddenFields
	) {
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$feusersObj = $tablesObj->get('fe_users', false);
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);

		$result = '';

		$orderBy = $tableconf['orderBy'];
		if ($orderBy == '') {
			$orderBy = 'uid';
		}

		$feuserRowArray = $feusersObj->get('', $pid_list . ',' . intval($feusersObj->getPid()));

		if (is_array($feuserRowArray) && count($feuserRowArray)) {
			$valueArray = [];
			$valueArray[] = '';

			foreach ($feuserRowArray as $uid => $row) {
				$valueArray[] = array($row['uid'] . ' - ' . $row['name'] . ' - ' . $row['city'], $uid);
			}

			$piVar = tx_ttproducts_model_control::getPiVar('orderaddress');

			$selectedKey = $piVars[$piVar] ?? 0;
			$type = 'select';
			$tagName = $prefix . '[' . $piVar . ']';
			$text = tx_ttproducts_form_div::createSelect(
				$languageObj,
				$valueArray,
				$tagName,
				$selectedKey,
				true,
				false,
				[],
				$type
			);
			$result = $text;
			$feusers_uid = $selectedKey;
			$hiddenFields .= tx_ttproducts_form_div::createTag(
				'input',
				$tagName,
				$selectedKey,
				'type="hidden"'
			);
		} else {
			$result = $languageObj->getLabel('no_feusers');
		}

		return $result;
	}


	public function printView (
		$pibaseClass,
		$templateCode,
		$theCode,
		$pid_list,
		$recursive,
		$pageAsCategory,
		$updateCode,
		$bIsAllowed,
		$bValidUpdateCode,
		$trackingCode,
		&$error_code
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$orderObj = $this->getModelObj(); // order
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);

		self::init2(
			$bValidUpdateCode,
			$theCode,
			$pid_list,
			$recursive,
			$orderObj,
			$cnf,
			$itemTable,
			$functablename,
			$tableconf,
			$feusers_uid,
			$validFeUser,
			$pid,
			$markerArray,
			$orderMarker,
			$feuserMarker,
			$piVars,
			$prefix
		);

		$idPrefix = str_replace('_', '-', $prefix);
		$fegroups_uid = 0;
		$fegroupMarker = 'FEGROUP';
		$viewType = 0;

		tx_ttproducts_admin_control_view::getSubpartArrays(
			$bIsAllowed,
			$bValidUpdateCode,
			$subpartArray,
			$wrappedSubpartArray
		);

		$hiddenFields = tx_ttproducts_admin_control_view::getHiddenField($updateCode);
		$markerArray['###ADMIN_HIDDENFIELDS###'] = $hiddenFields;
		$subPartMarker = 'ORDERS_LIST_TEMPLATE';
		$bNeedTrackingInfo = $bIsAllowed;
		if ($feusers_uid > 0 && !$bValidUpdateCode) {
			$bNeedTrackingInfo = false;
		}

		$frameWork = $this->getFrameWork(
			$templateCode,
			$subPartMarker,
			$bValidUpdateCode,
			$trackingCode,
			$bNeedTrackingInfo,
			$feusers_uid,
			$validFeUser,
			$error_code
		);

		if (!$frameWork) {
			$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
			$error_code[0] = 'no_subtemplate';
			$error_code[1] = '###' . $subPartMarker . '###';
			$error_code[2] = $templateObj->getTemplateFile();
			return '';
		}

		$bUnlock = false;

		if ($bIsAllowed) {
			$bUnlock = true;
		}

		if ($bUnlock && $bValidUpdateCode) {

			$markerKey = $feuserMarker . '_SELECT';

			if (strpos($frameWork, '###' . $markerKey . '###') !== false) {

				$markerArray['###' . $markerKey . '###'] =
					$this->processFeuserSelect(
						$piVars,
						$prefix,
						$tableconf,
						$pid_list,
						$feusers_uid,
						$hiddenFields
					);
			}

			$markerKey = $fegroupMarker . '_SELECT';

			if (strpos($frameWork, '###' . $markerKey . '###') !== false) {
				$pidArray = GeneralUtility::trimExplode(',', $pid_list);
				foreach ($pidArray as $k => $v) {
					$pidArray[$k] = intval($v);
				}
                $enableFields = \JambageCom\Div2007\Utility\TableUtility::enableFields('fe_groups');
                $where = 'pid IN (' . implode(',', $pidArray) . ')' . $enableFields;

				$feGroupArray =
					$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'uid,title',
						'fe_groups',
						$where
					);
				$valueArray = [];
				$valueArray[] = '';

				foreach ($feGroupArray as $uid => $row) {
					$valueArray[] = array($row['title'], $row['uid']);
				}

				$piVar = tx_ttproducts_model_control::getPiVar('fegroup');
				$selectedKey = $piVars[$piVar] ?? 0;
				$type = 'select';
				$tagName = $prefix . '[' . $piVar . ']';
				$text = tx_ttproducts_form_div::createSelect(
					$languageObj,
					$valueArray,
					$tagName,
					$selectedKey,
					true,
					false,
					[],
					$type
				);
				$markerArray['###' . $markerKey . '###'] = $text;
				$fegroups_uid = $selectedKey;
			}
		}

		$markerKey = 'VIEW_SELECT';

		if (strpos($frameWork, '###' . $markerKey . '###') !== false) {
			$valueArray = [];
			$valueArray['0'] = $languageObj->getLabel('orders_view_orders');
			$valueArray['1'] = $languageObj->getLabel('orders_view_products');

			$piVar = tx_ttproducts_model_control::getOrderViewVar();
			$selectedKey = $piVars[$piVar] ?? 0;
			$type = 'select';
			$text = tx_ttproducts_form_div::createSelect(
				$languageObj,
				$valueArray,
				$prefix . '[' . $piVar . ']',
				$selectedKey,
				true,
				false,
				[],
				$type
			);
			$markerArray['###' . $markerKey . '###'] = $text;
			$viewType = $selectedKey;

	// MESSAGE_VIEW

			$formConf = $cnf->getFormConf($theCode);

			if (
				isset($formConf['panel.']) &&
				is_array($formConf['panel.'])
			) {
				foreach ($formConf['panel.'] as $k1 => $panelConf) {
					if (
						isset($panelConf['layout']) &&
						isset($panelConf['marker']) &&
						isset($panelConf['elements.'])
					) {
						$layout = $panelConf['layout'];
						$panelMarker = strtoupper($panelConf['marker']);
						$elementMarkerArray = [];

						foreach ($panelConf['elements.'] as $k2 => $elementConf) {
							if (
								isset($elementConf['marker']) &&
								isset($elementConf['tag']) &&
								isset($elementConf['name']) &&
								isset($elementConf['label'])
							) {
								$marker = strtoupper($elementConf['marker']);

								$htmlElement = tx_ttproducts_form_div::createTag($elementConf['tag'], $elementConf['name'], $elementConf['label'], '', $elementConf['params']);
								$elementMarkerArray['###' . $marker . '###'] = $htmlElement;
							}
						}

						$panelContent =
							$templateService->substituteMarkerArrayCached(
								$layout,
								$elementMarkerArray
							);
						$markerArray['###' . $panelMarker . '###'] = $panelContent;
					}
				}
			}

			$tagArray = $markerObj->getAllMarkers($templateCode);

			foreach ($tagArray as $tag => $v) {
				if (($pos = strpos($tag, 'MESSAGE_VIEW')) === 0) {

					$markerViewType = substr($tag, strlen('MESSAGE_VIEW_'));

					if ($viewType == $markerViewType || $markerViewType == 'NULL' && $viewType == '') {
						$wrappedSubpartArray['###' . $tag . '###'] = '';
					} else {
						$subpartArray['###' . $tag . '###'] = '';
					}
				}
			}
		}

		$orderPiVar = tx_ttproducts_model_control::getPiVar('sys_products_orders');
		$fieldPiVarArray = array('crdate' => array('ge', 'le'));

		foreach ($fieldPiVarArray as $fieldPiVar => $piVarTypeArray) {
			foreach ($piVarTypeArray as $piVarType) {
				$markerkey = $orderMarker . '_' . strtoupper($fieldPiVar) . '_' . strtoupper($piVarType);

				if (
					strpos($frameWork, '###' . $markerkey . '_SELECT###') !== false
				) {
					$selectedKey = $piVars[$orderPiVar][$fieldPiVar][$piVarType] ?? 0;
					$type = 'input';
					$tagName = $prefix . '[' . $orderPiVar . ']' . '[' . $fieldPiVar . ']' . '[' . $piVarType . ']';

					$htmlTag = tx_ttproducts_form_div::createTag(
						$type,
						$tagName,
						$selectedKey,
						'id="' . $idPrefix . '-' . str_replace('_', '-', strtolower($markerkey)) . '" class="dateSelector"'
					);
					$markerArray['###' . $markerkey . '_SELECT###'] = $htmlTag;
				}
			}
		}

		if ($bUnlock || $feusers_uid) {

			$orderBy = $tableconf['orderBy'];
			if ($orderBy == '') {
				$orderBy = 'crdate';
			}

			if ($feusers_uid) {
				$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('name ', 'fe_users', 'uid=' . intval($feusers_uid));
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1);
				$GLOBALS['TYPO3_DB']->sql_free_result($res1);
				$this->setFeuserMarker ($feuserMarker, $row, $markerArray);

				$markerArray['###' . $fegroupMarker . '_TITLE###'] = '';
			}

			if ($fegroups_uid) {
				$res1 = $GLOBALS['TYPO3_DB']->exec_SELECTquery('title ', 'fe_groups', 'uid="' . intval($fegroups_uid) . '"');
				if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1)) {
					$fegroupName = $row['title'];
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res1);

				$markerArray['###' . $feuserMarker . '_NUMBER###'] = '';
				$markerArray['###' . $feuserMarker . '_NAME###'] = '';
				$markerArray['###' . $fegroupMarker . '_TITLE###'] = $fegroupName;
			}

			if ($feusers_uid || $fegroups_uid) {

				$where = '';

				if ($feusers_uid) {
					$where = 'feusers_uid = ' . intval($feusers_uid);
					$from = '';
				} else if ($fegroups_uid) {
					$orderAlias = $orderObj->getAlias();
					$from = $functablename . ' ' . $orderAlias . ' LEFT JOIN fe_users ON ' . $orderAlias . '.feusers_uid = fe_users.uid';
					$where = 'fe_users.usergroup = ' . $fegroups_uid;
				}
				$whereArray = $piVars[tx_ttproducts_model_control::getPiVar($functablename)] ?? '';

				if (is_array($whereArray)) {
					foreach ($whereArray as $field => $value) {
						if (is_array($value)) {
							foreach ($value as $comparator => $comparand) {
								$sqlOperator = '';
								$comparator = tx_ttproducts_sql::transformComparator($comparator);
								if ($comparand) {
									$tablename = $itemTable->getTableObj()->getName();
									$whereField =
										tx_ttproducts_sql::getWhere4Field(
											$tablename,
											$field,
											$comparator,
											$comparand
										);
									if ($whereField !== false) {

										$where .= $whereField;
									}
								}
							}
						} else {
							$where .= ' AND ' . $field . '=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($value, $itemTable->getTableObj()->getName());
						}
					}
				}

				$where .= $orderObj->getTableObj()->enableFields();
				$whereProducts = '';
				$where = $orderObj->getTableObj()->transformWhere($where);
				$orderBy = $orderObj->getTableObj()->transformOrderby($orderBy);

				switch ($viewType) {
					case 0:
						$this->getOrderMarkerSubpartArrays(
							$pibaseClass,
							$templateCode,
							$frameWork,
							$theCode,
							$pageAsCategory,
							$pid,
							$pid_list,
							$from,
							$where,
							$orderBy,
							$markerArray,
							$subpartArray,
							$error_code
						);
						$subpartArray['###ORDER_PRODUCT_LIST###'] = '';
						break;
					case 1:
						$this->getProductMarkerSubpartArrays(
							$pibaseClass,
							$templateCode,
							$frameWork,
							'ITEM_LIST_TEMPLATE',
							$theCode,
							$pageAsCategory,
							$pid,
							$pid_list,
							$from,
							$where,
							$orderBy,
							$whereProducts,
							false,
							$hiddenFields,
							$markerArray,
							$subpartArray,
							$error_code
						);
						$wrappedSubpartArray['###ORDER_PRODUCT_LIST###'] = '';
						$subpartArray['###ORDER_LIST###'] = '';
						$subpartArray['###ORDER_NOROWS###'] = '';
						break;
					default:
						break;
				}
			} else {
				$markerArray['###' . $feuserMarker . '_NUMBER###'] = 0;
				$markerArray['###' . $feuserMarker . '_NAME###'] = '';
				$markerArray['###' . $fegroupMarker . '_TITLE###'] = '';
				$subpartArray['###ORDER_LIST###'] = '';
				$subpartArray['###ORDER_NOROWS###'] = '';
				$subpartArray['###ORDER_PRODUCT_LIST###'] = '';
			}
		}
		$markerArray['###HIDDENFIELDS###'] = $hiddenFields;
		$content = $templateService->substituteMarkerArrayCached(
			$frameWork,
			$markerArray,
			$subpartArray,
			$wrappedSubpartArray
		);

		return $content;
	}


// download
	public function printDownloadView (
		$pibaseClass,
		$templateCode,
		$theCode,
		$pid_list,
		$recursive,
		$pageAsCategory,
		$updateCode,
		$bIsAllowed,
		$bValidUpdateCode,
		$trackingCode,
        $onlyProductsWithFalOrders,
		&$error_code
	) {
        $templateService = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Service\MarkerBasedTemplateService::class);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$orderObj = $this->getModelObj();

		self::init2(
			$bValidUpdateCode,
			$theCode,
			$pid_list,
			$recursive,
			$orderObj,
			$cnf,
			$itemTable,
			$functablename,
			$tableconf,
			$feusers_uid,
			$validFeUser,
			$pid,
			$markerArray,
			$orderMarker,
			$feuserMarker,
			$piVars,
			$prefix
		);
		$subpartArray = [];
		$wrappedSubpartArray = [];

		tx_ttproducts_admin_control_view::getSubpartArrays(
			$bIsAllowed,
			$bValidUpdateCode,
			$subpartArray,
			$wrappedSubpartArray
		);
		$hiddenFields = tx_ttproducts_admin_control_view::getHiddenField($updateCode);
		$markerArray['###ADMIN_HIDDENFIELDS###'] = $hiddenFields;
		$markerArray['###TRACKING_NUMBER###'] =  $trackingCode;
		$subPartMarker = 'DOWNLOAD_LIST_TEMPLATE';

		$frameWork = $this->getFrameWork(
			$templateCode,
			$subPartMarker,
			$bValidUpdateCode,
			$trackingCode,
			true,
			$feusers_uid,
			$validFeUser,
			$error_code
		);

		if (!$frameWork) {
			return '';
		}

		$bUnlock = false;

		if ($bIsAllowed) {
			$bUnlock = true;
		}

		if ($bUnlock && $bValidUpdateCode) {

			$markerKey = $feuserMarker . '_SELECT';

			if (strpos($frameWork, '###' . $markerKey . '###') !== false) {

				$markerArray['###' . $markerKey . '###'] =
					$this->processFeuserSelect(
						$piVars,
						$prefix,
						$tableconf,
						$pid_list,
						$feusers_uid,
						$hiddenFields
					);
			}
		}

		if ($feusers_uid || $trackingCode != '') {

			$where = '';
			if ($feusers_uid) {
				$res1 =
					$GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'name ',
						'fe_users',
						'uid=' . intval($feusers_uid)
					);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res1);
				$GLOBALS['TYPO3_DB']->sql_free_result($res1);

				$this->setFeuserMarker(
					$feuserMarker,
					$row,
					$markerArray
				);
			}

			$from = '';
			$orderBy = 'sys_products_orders.uid DESC';

			$orderObj->getDownloadWhereClauses(
				$feusers_uid,
				$trackingCode,
				$where,
				$whereProducts
			);

			$this->getProductMarkerSubpartArrays(
				$pibaseClass,
				$templateCode,
				$frameWork,
				'ITEM_LIST_DOWNLOADS_TEMPLATE',
				$theCode,
				$pageAsCategory,
				$pid,
				$pid_list,
				$from,
				$where,
				$orderBy,
				$whereProducts,
				$onlyProductsWithFalOrders,
				$hiddenFields,
				$markerArray,
				$subpartArray,
				$error_code
			);

			$wrappedSubpartArray['###ORDER_PRODUCT_LIST###'] = '';
		} else {
			$markerArray['###' . $feuserMarker . '_NUMBER###'] = 0;
			$markerArray['###' . $feuserMarker . '_NAME###'] = '';
			$subpartArray['###ORDER_PRODUCT_LIST###'] = '';
		}

		$content = $templateService->substituteMarkerArrayCached(
			$frameWork,
			$markerArray,
			$subpartArray,
			$wrappedSubpartArray
		);

		return $content;
	}


#######################
	public function getSingleOrder ($row) {
        $result = '';
		$from = '';
		$where = '';
		$whereProducts = '';
		$uids = $row['uid'];
		$orderBy = '';
		$pid_list = '';
		$productRowArray = [];
		$multiOrderArray = [];

		$this->getModelObj()->getOrderedProducts(
			$from,
			$where,
			$uids,
			$orderBy,
			$whereProducts,
			false,
			$pid_list,
			$productRowArray,
			$multiOrderArray
		);

        foreach ($productRowArray as $key => $productRow) {
            $result .= '<br>' . $productRow['uid'] . ': ' . $productRow['title'] . ' - ' . $productRow['subtitle'] . ' n. ' . $productRow['itemnumber'] . ' -> ' . $multiOrderArray[$key]['quantity'];
        }

        return $result;
    }
}

