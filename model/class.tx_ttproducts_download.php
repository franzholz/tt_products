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


class tx_ttproducts_download extends tx_ttproducts_article_base {
	public $relatedArray = array(); // array of related products
	public $marker = 'DOWNLOAD';
	public $type = 'download';
	public $piVar='download';
	public $articleArray = array();
	protected $tableAlias = 'download';


	public function getOrderedUid(
		$downloadUid,
		$falUid,
		$multiOrderArray
	) {
		$orderUid = 0;

		if (
			isset($multiOrderArray) &&
			is_array($multiOrderArray) &&
			count($multiOrderArray)
		) {
			foreach($multiOrderArray as $orderRow) {
				if (
					isset($orderRow['fal_variants']) &&
					$orderRow['fal_variants'] != ''
				) {
					$position = strpos($orderRow['fal_variants'], 'dl=' . $downloadUid . tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR);

					if ($position !== 0) {
						continue;
					}

					$position = strpos($orderRow['fal_variants'], tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR . 'fal=');
					$orderFalUid = substr($orderRow['fal_variants'], $position + strlen(tx_ttproducts_variant_int::EXTERNAL_QUANTITY_SEPARATOR . 'fal='));

					if (
						$orderFalUid = $falUid
					) {
						$orderUid = $orderRow['uid'];
						break;
					}
				}
			}
		}

		return $orderUid;
	}


	public function getOrderedDownloadFalArray (
        $orderObj,
        $downloadUid,
        $multiOrderArray
    ) {
		$downloadUid = intval($downloadUid);
		$orderedFalArray = array();
		if (
			$downloadUid &&
			isset($multiOrderArray) &&
			is_array($multiOrderArray) &&
			count($multiOrderArray)
		) {
			foreach($multiOrderArray as $orderRow) {
                $falUid =
                    $orderObj->getFal(
                        $tmp,
                        $downloadUid,
                        $orderRow
                    );
                if ($falUid) {
                    $orderedFalArray[] = $falUid;
                }
			}
		}

		return $orderedFalArray;
	}


	public function getFileArray (
        $orderObj,
		$row,
		$multiOrderArray,
		$checkPriceZero = false
	) {
		$fileArray = array();

		if (
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('filelist') &&
			$row['file_uid']
		) {
// mm Beziehung auswerten in Datensätze von file $fileUid
// tt_products_downloads_mm_sysfile (
// 	uid int(11) NOT NULL auto_increment,
// 	pid int(11) DEFAULT '0' NOT NULL,
// 	tstamp int(11) DEFAULT '0' NOT NULL,
// 	crdate int(11) DEFAULT '0' NOT NULL,
// 	deleted tinyint(4) DEFAULT '0' NOT NULL,
// 	hidden tinyint(4) DEFAULT '0' NOT NULL,
// 	uid_local int(11) DEFAULT '0' NOT NULL,
// 	uid_foreign int(11) DEFAULT '0' NOT NULL,
// 	sorting int(10) DEFAULT '0' NOT NULL,
// 	sorting_foreign
			$orderedFalArray =
				$this->getOrderedDownloadFalArray(
                    $orderObj,
					$row['uid'],
					$multiOrderArray
				);
			$orderedFalArray = array_unique($orderedFalArray);

			if (!empty($orderedFalArray)) {
				$orderedFalArray = $GLOBALS['TYPO3_DB']->cleanIntArray($orderedFalArray);

				$where_clause = 'uid IN (' . implode(',', $orderedFalArray) . ')';
				$where_clause .= ' AND uid_foreign=' . intval($row['uid']) . ' AND tablenames="tt_products_downloads" AND fieldname="file_uid"' ;
				$where_clause .= \JambageCom\Div2007\Utility\TableUtility::enableFields('sys_file_reference');
				$sysfileRowArray =
					$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
						'*',
						'sys_file_reference',
						$where_clause,
						'',
						'sorting',
						'',
						'uid_local'
					);
			}

			if (
				is_array($sysfileRowArray)
			) {
				$storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
				$storage = $storageRepository->findByUid(1);

				foreach($sysfileRowArray as $fileUid => $sysfileRow) {
					if (
						$checkPriceZero &&
						$sysfileRow['tx_ttproducts_price_enable'] && // check non free downloads against the order data
						$sysfileRow['tx_ttproducts_price'] > 0
					) {
						continue;
					}

					$resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
					$fileObj = $resourceFactory->getFileReferenceObject($sysfileRow['uid']);
					$fileInfo = $storage->getFileInfo($fileObj);

                    if (
                        version_compare(TYPO3_version, '9.0.0', '>=')
                    ) {
                        $fileArray[$sysfileRow['uid']] = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/' . 'fileadmin' . $fileInfo['identifier'];
                    } else {
                        $fileArray[$sysfileRow['uid']] = PATH_site . 'fileadmin' . $fileInfo['identifier'];
                    }
				}
			}
		} else if ($row['path'] != '') {
            if (
                version_compare(TYPO3_version, '9.0.0', '>=')
            ) {
                $path = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/'  . $row['path'] . '/';
            } else {
                $path = PATH_site . $row['path'] . '/';
            }

			$fileArray =
				\TYPO3\CMS\Core\Utility\GeneralUtility::getAllFilesAndFoldersInPath(
					$fileArray,
					$path,
					'',
					1,
					1,
					$GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging']
				);
			usort($fileArray, 'version_compare');

			$fileArray = array_reverse($fileArray, true);
		}

		return $fileArray;
	}


	public function getRelatedUidArray (
		$uids,
		$tagMarkerArray,
		$parenttable = 'tt_products'
	) {
		$resultArray = array();
		$downloadUidArray = array();
		$bAllowed = true;

		if ($parenttable == 'tt_products') {
			$uidArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $uids);
			$mmTable = 'tt_products_products_mm_downloads';

			foreach ($uidArray as $uid) {
				if (intval($uid) == $uid) {
					$where_clause = 'uid_local = ' . intval($uid);
					$rowArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', $mmTable, $where_clause);

					if (isset($rowArray) && is_array($rowArray)) {
						foreach ($rowArray as $row) {
							$downloadUidArray[] = $row['uid_foreign'];
						}
					}
				}
			}

			if (empty($downloadUidArray)) {
				$bAllowed = false;
			}
		}

		$where_clause = '';
		$tagWhere = '';

		if (is_array($tagMarkerArray) && ($tagMarkerArray)) {
			$newTagMarkerArray = array();
			foreach ($tagMarkerArray as $tagMarker) {
				if (strpos($tagMarker, '_') === false) {
					$newTagMarkerArray[] = $tagMarker;
				}
			}

			$tagMarkerArray = $newTagMarkerArray;
			$tagMarkerArray = $GLOBALS['TYPO3_DB']->fullQuoteArray(
				$tagMarkerArray,
				$this->getTableObj()->getName()
			);
			$tags = implode(',', $tagMarkerArray);
			$tagWhere = ' AND marker IN (' . $tags . ')';
		}

		if (is_array($downloadUidArray) && count($downloadUidArray)) {
			$where_clause = 'uid IN (' . implode(',', $downloadUidArray) . ')' .
				$tagWhere;
		}

		if ($bAllowed) {
			$resultArray = $this->get('', '', false, $where_clause);
		}

		return $resultArray;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_download.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_download.php']);
}

