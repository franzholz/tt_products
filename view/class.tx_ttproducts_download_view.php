<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2014 Franz Holzinger (franz@ttproducts.de)
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
 * functions for downloads
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_download_view extends tx_ttproducts_article_base_view {
	public $marker = 'DOWNLOAD';

	/**
	 * Generates a radio or selector box for download
	 */
	public function generateRadioSelect (
		$theCode,
		$row
	) {
	}


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	array		Returns a markerArray ready for substitution with information
	 * @access private
	 */
	public function getDownloadMarkerSubpartArrays (
		$templateCode,
		$conf,
		$rowArray,
		&$markerArray,
		&$subpartArray,
		&$wrappedSubpartArray,
		$parentMarker,
		$tagArray,
		$hiddenFields,
		$multiOrderArray,
		$productRowArray,
		$checkPriceZero = false
	) {
		$error = false;
		$cObj = GeneralUtility::makeInstance('TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer');
		$cObj->start(array());

		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$functablename = 'tt_products';
		$itemTableView = $tablesObj->get($functablename, true);
		$itemTable = $itemTableView->getModelObj();
		$orderObj = $tablesObj->get('sys_products_orders');
		$variantSeparator = $itemTable->getVariant()->getSplitSeparator();
		$t = array();

		if (isset($tagArray['DOWNLOAD_SINGLE'])) {
			$t['item'] = tx_div2007_core::getSubpart($templateCode, '###DOWNLOAD_SINGLE###');
		}

// 			<!-- ###DOWNLOAD_SINGLE### begin -->
// 				###DOWNLOAD_TITLE###
// 				###DOWNLOAD_NOTE###
// 				###DOWNLOAD_LINK###
// 				<br/>
// 			<!-- ###DOWNLOAD_SINGLE### end -->


		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$postVar = tx_ttproducts_control_command::getCommandVar();
		$downloadVar = tx_ttproducts_model_control::getPiVar($this->getModelObj()->getFuncTablename());
		$bAddonsEM = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('addons_em');
		tx_ttproducts_control_access::getVariables(
			$conf,
			$updateCode,
			$bIsAllowed,
			$bValidUpdateCode,
			$trackingCode
		);

		$subpartArray['###DOWNLOAD_SINGLE###'] = '';

		if (
			isset($rowArray) &&
			is_array($rowArray) &&
			count($rowArray)
		) {		
			foreach ($rowArray as $k => $row) {
				$content = '';
				$marker = $parentMarker . '_' . $this->getMarker() . '_' . strtoupper($row['marker']);

				$selectValueArray = array();
				$selectedKey = 0;
				$productUid = 0;

				if (
					isset($productRowArray) &&
					is_array($productRowArray) &&
					count($productRowArray)
				) {
					$productUid = $productRowArray['0']['uid'];

					foreach ($productRowArray as $productRow) {

						$selectValueArray = $itemTable->getEditVariant()->getVariantRowFromProductRow($productRow);
					}

					if (is_array($selectValueArray) && count($selectValueArray)) {
						sort($selectValueArray, SORT_STRING);
					}
				}

				$selectOut = '';
				$selectedDomain = '';
				$markerSelect = $marker . '_SELECT';

				if (is_array($selectValueArray) && count($selectValueArray) && isset($selectValueArray['edit_domain'])) {
					$piVars = tx_ttproducts_model_control::getPiVars();
					$domainVar = 'domain';
					$piVar = $domainVar;

					$tagName = tx_ttproducts_model_control::getPrefixId() . '[' . $piVar . '][' . $productUid . '][' . $row['uid'] . ']';

					if (
						isset($piVars[$piVar]) &&
						is_array($piVars[$piVar]) &&
						isset($piVars[$piVar][$productUid]) &&
						is_array($piVars[$piVar][$productUid])
					) {
						$selectedKey = $piVars[$piVar][$productUid][$row['uid']];
					}

					$selectedDomain = $selectValueArray[$selectedKey];

					if (isset($tagArray[$markerSelect])) {
						$selectOut = tx_ttproducts_form_div::createSelect(
							$languageObj,
							$selectValueArray,
							$tagName,
							$selectedKey,
							true,
							true,
							array(),
							'select',
							array('title' => 'Auswahl der Domäne', 'onchange' => 'submit();')
						);
					}
				}

				$markerArray['###HIDDENFIELDS###'] = $hiddenFields;

				if (isset($tagArray[$markerSelect])) {
					$markerArray['###' . $markerSelect . '###'] = $selectOut;
				}

// hier die Select Box für die Auswahl der Domäne zum Download

				if ($bAddonsEM) {
					$fileArray = $this->getModelObj()->getFileArray(
                        $orderObj,
						$row,
						$multiOrderArray,
						$checkPriceZero
					);
					if (empty($fileArray)) { // If there is no file to download, then skip the whole download object
						continue;
					}

                    if (
                        version_compare(TYPO3_version, '9.0.0', '>=')
                    ) {
                        $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
                    } else {
                        $path = PATH_site;
                    }
					$path = $path . $row['path'] . '/';

					// $directLink = TYPO3_SITE_SCRIPT;
					$paramArray = array();
					if ($bValidUpdateCode) {
						$paramArray['update_code'] = $updateCode;
					} else if ($trackingCode != '') {
						$paramArray['tracking'] = $trackingCode;
					}

					$prefixId = tx_ttproducts_model_control::getPrefixId();
					$paramArray[$prefixId . '[' . $downloadVar . ']'] = $row['uid'];

					if ($selectedDomain != '') {
						$paramArray[$prefixId . '[' . $domainVar . ']'] = $selectedDomain;
					}
					$orderUid = 0;

					if (
						isset($multiOrderArray) &&
						is_array($multiOrderArray) &&
						count($multiOrderArray)
					) {
						$currentOrderUid = 0;
						foreach($multiOrderArray as $orderRow) {
							if (
								$orderRow['edit_variants'] != '' &&
								strpos($orderRow['edit_variants'], 'domain:') !== false
							) {
// andere Varianten erlauben
								$editVariants =
									preg_split(
										'/[\h]*' . $variantSeparator . '[\h]*/',
										$orderRow['edit_variants'],
										-1,
										PREG_SPLIT_NO_EMPTY
									);

								if ($selectedDomain != '' && is_array($editVariants) && count($editVariants)) {
									$editVariantComparator = 'domain:' . $selectedDomain;
									foreach($editVariants as $editVariant) {

										if ($editVariant == $editVariantComparator) {
											$orderUid = $orderRow['uid'];
											break;
										}
									}
									if ($orderUid) {
										break;
									}
								}
							} else {
								$currentOrderUid = $orderRow['uid'];
							}
						}
						if (!$orderUid && $bValidUpdateCode) {
							$orderUid = $currentOrderUid;
						}
					}

					$orderPivar = tx_ttproducts_model_control::getPiVar('sys_products_orders');

					if ($orderUid) {
						$paramArray[$prefixId . '[' . $orderPivar . ']'] = $orderUid;
					}

                    $downloadImageFile = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath(PATH_BE_TTPRODUCTS . 'Resources/Public/Icons/system-extension-download.png');

					foreach ($fileArray as $k => $file) {
						if ($file != $path) {
							if (substr($file, -1) == '/') {
								// $extKey
								$paramArray[$postVar . '[download]'] = $k;

								$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
									$cObj,
									$GLOBALS['TSFE']->id,
									$paramArray
								);
								$foldername = basename($file);

								$content .= '<a href="' . htmlspecialchars($url) . '" title="' .
									$GLOBALS['TSFE']->sL(DIV2007_LANGUAGE_PATH . 'locallang_common.xml:download') . ' ' . $foldername . '">' .
								$foldername . '<img src="' . $downloadImageFile . '">' . '</a>';
							} else {
								$paramArray[$postVar . '[fal]'] = $k;
								$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
									$cObj,
									$GLOBALS['TSFE']->id,
									$paramArray
								);
								$filename = basename($file);

								$content .= '<a href="' . htmlspecialchars($url) . '" title="' .
									$GLOBALS['TSFE']->sL(DIV2007_LANGUAGE_PATH . 'locallang_common.xml:download') . ' ' . $filename . '">' . $filename . '<img src="' . $downloadImageFile . '">' . '</a>';
							}
						}
					}
				} else {
					$content = 'error: "addons_em" has not been installed';
					$error = true;
				}

				if (isset($tagArray[$marker])) {
					$markerArray['###' . $marker . '###'] = $content;
				} else if ($error) {
					$subpartArray['###DOWNLOAD_SINGLE###'] .= $content;
				} else if ($t['item'] != '') {
	// 			<!-- ###DOWNLOAD_SINGLE### begin -->
	// 				###DOWNLOAD_TITLE###
	// 				###DOWNLOAD_NOTE###
	// 				###DOWNLOAD_LINK###
	// 				<br/>
	// 			<!-- ###DOWNLOAD_SINGLE### end -->

					$downloadMarker = $this->getMarker();
					$markerLink = $downloadMarker . '_' . strtoupper('link');

					if (isset($tagArray[$markerLink])) {
						$markerArray['###' . $markerLink . '###'] = $content;
					}

					$fieldArray = array('author', 'edition', 'note', 'title');

					foreach ($fieldArray as $field) {
						$fieldMarker = $downloadMarker . '_' . strtoupper($field);

						if (isset($tagArray[$fieldMarker])) {
							$value = $row[$field];
							if ($field == 'note') {
								$value = ($this->conf['nl2brNote'] ? nl2br($value) : $value);

									// Extension CSS styled content
								if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('css_styled_content')) {
									$value =
										tx_div2007_alpha5::RTEcssText(
											$cObj,
											$value
										);
								} else if (is_array($this->conf['parseFunc.'])) {
									$value =
										$cObj->parseFunc(
											$value,
											$this->conf['parseFunc.']
										);
								}
							}
							$markerArray['###' . $fieldMarker . '###'] = $value;
						}
					}

					$out = tx_div2007_core::substituteMarkerArrayCached($t['item'], $markerArray);

					$subpartArray['###DOWNLOAD_SINGLE###'] .= $out;
				}
			}
		} else {
		// mothing
		}

		$this->setMarkersEmpty(
			$tagArray,
			array($parentMarker . '_' . $this->getMarker()),
			$markerArray
		);

		if (!isset($subpartArray['###DOWNLOAD_SINGLE###'])) {
			$wrappedSubpartArray['###DOWNLOAD_SINGLE###'] = array('', '');
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_download_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_download_view.php']);
}
