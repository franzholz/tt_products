<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2009 Franz Holzinger (franz@ttproducts.de)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * related product list view functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_relatedlist_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $conf;
	public $config;
	public $pidListObj;
	public $cObj;


	public function init ($cObj, $pid_list, $recursive)	{
		$this->cObj = $cObj;

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
		$this->pidListObj->init($cObj);
		$this->pidListObj->applyRecursive($recursive, $pid_list, true);
		$this->pidListObj->setPageArray();
	}


	public function getQuantityMarkerArray (
		$theCode,
		$functablename,
		$marker,
		$itemArray,
		$useArticles,
		&$markerArray,
		$viewTagArray
	) {
		$addListArray = $this->getAddListArray (
			$theCode,
			$functablename,
			$marker,
			'',
			$this->useArticles
		);
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$itemObj = $tablesObj->get($functablename);

		$rowArray = array();
		$rowArray[$functablename] = $itemArray;

		foreach ($addListArray as $subtype => $funcArray)	{

			if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require'])	{
				$relatedIds = $itemObj->getRelated($uid, $subtype);

				if (is_array($relatedIds) && count($relatedIds))	{

					$quantitiyMarkerArray = array();
					tx_ttproducts_control_basketquantity::getQuantityMarkerArray (
						$relatedIds,
						$rowArray,
						$quantitiyMarkerArray
					);
					$markerArray = array_merge($markerArray, $quantitiyMarkerArray);
				}
			}
		}
	}


	public function getAddListArray (
		$theCode,
		$functablename,
		$marker,
		$uid,
		$useArticles
	)	{

		switch ($functablename)	{
			case 'tt_products':
				$result =
					array(
						'articles' => array(
							'marker' => 'PRODUCT_RELATED_ARTICLES',
							'template' => 'ITEM_LIST_RELATED_ARTICLES_TEMPLATE',
							'require' => $useArticles,
							'code' => 'LISTARTICLES',
							'additionalPages' => $this->conf['pidsRelatedArticles'],
							'mergeRow' => array(),
							'functablename' => 'tt_products_articles',
							'callFunctableArray' => array()
						),
						'accessories' => array(
							'marker' => 'PRODUCT_ACCESSORY_UID',
							'template' => 'ITEM_LIST_ACCESSORY_TEMPLATE',
							'require' => true,
							'code' => 'LISTACCESSORY',
							'additionalPages' => $this->conf['pidsRelatedAccessories'],
							'mergeRow' => array(),
							'functablename' => 'tt_products',
							'callFunctableArray' => array()
						),
						'products' => array(
							'marker' => 'PRODUCT_RELATED_UID',
							'template' => 'ITEM_LIST_RELATED_TEMPLATE',
							'require' => true,
							'code' => 'LISTRELATED',
							'additionalPages' => $this->conf['pidsRelatedProducts'],
							'mergeRow' => array(),
							'functablename' => 'tt_products',
							'callFunctableArray' => array()
						)
					);
				break;

			case 'tt_products_articles':
				$result =
					array(
						'accessories' => array(
							'marker' => 'ARTICLE_ACCESSORY_UID',
							'template' => 'ITEM_LIST_ACCESSORY_TEMPLATE',
							'require' => true,
							'code' => 'LISTACCESSORY',
							'additionalPages' => $this->conf['pidsRelatedAccessories'],
							'mergeRow' => array(),
							'functablename' => 'tt_products_articles',
							'callFunctableArray' => array()
						)
					);
				break;

		}
		return $result;
	}


	public function getListMarkerArray (
		$theCode,
		$pibaseClass,
		$templateCode,
		$markerArray,
		$viewTagArray,
		$functablename,
		$uid,
		$uidArray,
		$useArticles,
		$pageAsCategory,
		$pid,
		&$errorCode
	)	{
		$result = false;
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$itemViewObj = $tablesObj->get($functablename, true);
		$addListArray = $this->getAddListArray($theCode, $functablename, $itemViewObj->getMarker(), $uid, $useArticles);

		if (is_array($addListArray))	{
			$listView = '';
			$itemObj = $itemViewObj->getModelObj();

			foreach ($addListArray as $subtype => $funcArray)	{

				if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require'])	{
					$relatedIds = $itemObj->getRelated($uid, $subtype);

					if (is_array($relatedIds) && count($relatedIds))	{
						// List all products:
						if (!is_object($listView))	{

							$listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
							$listView->init (
								$pibaseClass,
								$pid,
								$useArticles,
								$uidArray,
								$tmp = $this->pidListObj->getPidlist(),
								0
							);
						}
						$callFunctableArray = $funcArray['callFunctableArray'];
						$listPids = $funcArray['additionalPages'];
						if ($listPids != '')	{
							$this->pidListObj->applyRecursive($this->config['recursive'], $listPids);
						} else {
							$listPids = $this->pidListObj->getPidlist();
						}
						$parentDataArray = array(
							'functablename' => $functablename,
							'uid' => $uid
						);
						$tmpContent = $listView->printView(
							$templateCode,
							$funcArray['code'],
							$funcArray['functablename'],
							implode(',', $relatedIds),
							$listPids,
							$errorCode,
							$funcArray['template'] . $this->config['templateSuffix'],
							$pageAsCategory,
							array(),
							1,
							$callFunctableArray,
							$parentDataArray
						);

						$result['###' . $funcArray['marker'] . '###'] = $tmpContent;
					} else {
						$result['###' . $funcArray['marker'] . '###'] = '';
					}
				} else {
					if (isset($viewTagArray[$funcArray['marker']])) {
						$result['###' . $funcArray['marker'] . '###'] = '';
					}
				}
			}
		}

		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_relatedlist_view.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_relatedlist_view.php']);
}


