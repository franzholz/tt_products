<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Franz Holzinger (franz@ttproducts.de)
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
 * functions for digital medias
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_field_media extends tx_ttproducts_field_base {

	public function getDirname (&$imageRow)	{

		if($imageRow['file_mime_type'] == 'image' && isset($imageRow['file_path']))	{
			$dirname = $imageRow['file_path'];
		} else {
			$dirname = ($this->conf['defaultImageDir'] ? $this->conf['defaultImageDir'] : ( $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'] ? $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['imageFolder'] . '/' : 'uploads/pics/'));
		}

		return $dirname;
	}
	
	public function getFileArray (
		$tablename,
		$imageRow,
		$imageField,
		$fal = true
	) {
		$fileArray = array();

		if (
			strpos($imageField, '_uid') &&
			isset($imageRow[$imageField])
		) {
			$theTablename = $tablename;
			$where_clause = '1=1';
			$skip = false;
			$sysfileRowArray = array();

			if (
				isset($imageRow['ext']) &&
				is_array($imageRow['ext']) &&
				isset($imageRow['ext']['tt_products_articles']) &&
				is_array($imageRow['ext']['tt_products_articles']) &&
				!empty($imageRow['ext']['tt_products_articles'])
			) {
				$uidArray = array();
				$theTablename = 'tt_products_articles';
				foreach ($imageRow['ext']['tt_products_articles'] as $key => $row) {
					if (isset($row['uid']) && $row['uid']) {
						$uidArray[] = intval($row['uid']);
					}
				}

				if (count($uidArray)) {
					$where_clause = 'uid_foreign IN (' . implode(',', $uidArray) . ') AND tablenames="' . $theTablename . '" AND fieldname="' . $imageField . '"' ;
					$where_clause .= \JambageCom\Div2007\Utility\TableUtility::enableFields('sys_file_reference');
					$sysfileRowArray =
						$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'*',
							'sys_file_reference',
							$where_clause,
							'',
							'sorting_foreign',
							'',
							'uid_local'
						);
				} else {
					$skip = true;
				}
			}

			if (
				// if the article has no image then use the image of the product if present
				empty($sysfileRowArray)
			) {
				if ($imageRow[$imageField]) {
					$theTablename = $tablename;
					$where_clause = 'uid_foreign=' . intval($imageRow['uid']) . ' AND tablenames="' . $theTablename . '" AND fieldname="' . $imageField . '"' ;
					$where_clause .= \JambageCom\Div2007\Utility\TableUtility::enableFields('sys_file_reference');
					$sysfileRowArray =
						$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
							'*',
							'sys_file_reference',
							$where_clause,
							'',
							'sorting_foreign',
							'',
							'uid_local'
						);
					$skip = false;
				} else {
					$skip = true;
				}
			}

			if (
				!$skip &&
				!empty($sysfileRowArray)
			) {
				$storageRepository = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\StorageRepository');
				$storage = $storageRepository->findByUid(1);

				foreach($sysfileRowArray as $fileUid => $sysfileRow) {
					$resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
					$fileObj = $resourceFactory->getFileReferenceObject($sysfileRow['uid']);
					$fileInfo = $storage->getFileInfo($fileObj);
					if ($fal) {
						$fileArray[$sysfileRow['uid']] = array_merge($fileInfo, $sysfileRow);
						$keepFields = ['title', 'description', 'alternative'];
						foreach ($keepFields as $keepField) {
                            if ($sysfileRow[$keepField] == '' && $fileInfo[$keepField] != '') {
                                $fileArray[$sysfileRow['uid']][$keepField] = $fileInfo[$keepField];
                            }
						}
					} else {
						$fileArray[] = 'fileadmin' . $fileInfo['identifier'];
					}
				}
			}
		} else {
			$fileArray = ($imageRow[$imageField] ? explode(',', $imageRow[$imageField]) : array());
			$tmp = count($fileArray);
			if (
				!$tmp &&
				isset($imageRow['file_mime_type']) &&
				$imageRow['file_mime_type'] == 'image'
			) {
				$fileArray = array($imageRow['file_name']);
			}
		}

		return $fileArray;
	}

	public function getMediaNum (
		$functablename,
		$fieldname,
		$theCode
	) {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tableConf = $cnf->getTableConf($functablename, $theCode);

		// example: plugin.tt_products.conf.tt_products.ALL.limitImage = 10
		$mediaNum = $tableConf['limitImage'];

		if (!$mediaNum)	{
			$codeTypeArray = array(	// Todo: make this configurable
				'list' => array('real' => array('SEARCH', 'MEMO'), 'part' => array('LIST', 'MENU'), 'num' => $this->conf['limitImage']),
				'basket' => array('real' => array('OVERVIEW', 'BASKET', 'FINALIZE', 'INFO', 'PAYMENT', 'TRACKING', 'BILL', 'DELIVERY', 'EMAIL'),
				'part' => array() , 'num' => 1),
				'single' => array('real' => array(), 'part' => array('SINGLE'), 'num' => $this->conf['limitImageSingle'])
			);

			foreach ($codeTypeArray as $type => $codeArray)	{
				$realArray = $codeArray['real'];
				if (count ($realArray))	{
					if (in_array($theCode, $realArray))	{
						$mediaNum = $codeArray['num'];
						break;
					}
				}
				$partArray = $codeArray['part'];
				if (is_array($partArray) && count($partArray))	{
					foreach ($partArray as $k => $part)	{
						if (strpos($theCode, $part) !== false)	{
							$mediaNum = $codeArray['num'];
							break;
						}
					}
				}
			}
		}

		return $mediaNum;
	}
}

