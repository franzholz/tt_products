<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use Psr\EventDispatcher\EventDispatcherInterface;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;

use JambageCom\TtProducts\Event\AddRelatedListEvent;

class tx_ttproducts_relatedlist_view implements SingletonInterface
{
    public $pidListObj;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function init($pid_list, $recursive): void
    {
        $this->pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
        $this->pidListObj->applyRecursive($recursive, $pid_list, true);
        $this->pidListObj->setPageArray();
    }

    public function getListUncachedMarkerArray(
        $uid,
        $conf,
        $funcTablename,
        $viewTagArray
    ) {
        $result = [];

        if (
            $funcTablename == 'tt_products' &&
            isset($conf['LISTRELATEDBYSYSTEMCATEGORY']) &&
            isset($conf['LISTRELATEDBYSYSTEMCATEGORY.']) &&
            isset($conf['LISTRELATEDBYSYSTEMCATEGORY.']['userFunc'])
        ) {
            if (isset($viewTagArray['PRODUCT_RELATED_SYSCAT'])) {
                $cObjectType = $conf['LISTRELATEDBYSYSTEMCATEGORY'];
                $relatedConf = $conf['LISTRELATEDBYSYSTEMCATEGORY.'];
                $relatedConf['ref'] = $uid;
                $relatedConf['code'] = 'LISTRELATEDBYSYSTEMCATEGORY';
                $relatedConf['userFunc'] = $conf['LISTRELATEDBYSYSTEMCATEGORY.']['userFunc'];
                $cObjectType = $conf['LISTRELATEDBYSYSTEMCATEGORY'];

                $cObj = FrontendUtility::getContentObjectRenderer([]);
                $output = $cObj->cObjGetSingle($cObjectType, $relatedConf);
                $result['###PRODUCT_RELATED_SYSCAT###'] = $output;
            }
        } else {
            if (isset($viewTagArray['PRODUCT_RELATED_SYSCAT'])) {
                $result['###PRODUCT_RELATED_SYSCAT###'] = '';
            }
        }

        return $result;
    }

    public function getAddListArray(
        $theCode,
        $funcTablename,
        $marker,
        $uid,
        array $paramUidArray,
        $useArticles
    ) {
        $result = false;
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        switch ($funcTablename) {
            case 'tt_products':
                $result =
                    [
                        'articles' => [
                            'marker' => 'PRODUCT_RELATED_ARTICLES',
                            'template' => 'ITEM_LIST_RELATED_ARTICLES_TEMPLATE',
                            'require' => $useArticles,
                            'code' => 'LISTRELATEDARTICLES',
                            'additionalPages' => $conf['pidsRelatedArticles'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'tt_products_articles',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                        'accessories' => [
                            'marker' => 'PRODUCT_ACCESSORY_UID',
                            'template' => 'ITEM_LIST_ACCESSORY_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATEDACCESSORY',
                            'additionalPages' => $conf['pidsRelatedAccessories'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'tt_products',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                        'products' => [
                            'marker' => 'PRODUCT_RELATED_UID',
                            'template' => 'ITEM_LIST_RELATED_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATED',
                            'additionalPages' => $conf['pidsRelatedProducts'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'tt_products',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                        'productsbysystemcategory' => [
                            'marker' => 'PRODUCT_RELATED_SYSCAT',
                            'template' => 'ITEM_LIST_RELATED_BY_SYSTEMCATEGORY_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATEDBYSYSTEMCATEGORY',
                            'additionalPages' => $conf['pidsRelatedProducts'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'tt_products',
                            'callFunctableArray' => [],
                            'cached' => false,
                        ],
                        'complete_downloads' => [
                            'marker' => 'PRODUCT_COMPLETE_DOWNLOAD_UID',
                            'template' => 'ITEM_LIST_COMPLETE_DOWNLOAD_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATEDCOMPLETEDOWNLOAD',
                            'additionalPages' => $conf['pidsRelatedDownloads'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'sys_file_reference',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                        'all_downloads' => [
                            'marker' => 'PRODUCT_ALL_DOWNLOAD_UID',
                            'template' => 'ITEM_LIST_ALL_DOWNLOAD_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATEDALLDOWNLOAD',
                            'additionalPages' => $conf['pidsRelatedDownloads'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'sys_file_reference',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                        'partial_downloads' => [
                            'marker' => 'PRODUCT_PARTIAL_DOWNLOAD_UID',
                            'template' => 'ITEM_LIST_PARTIAL_DOWNLOAD_TEMPLATE',
                            'require' => true,
                            'code' => 'LISTRELATEDPARTIALDOWNLOAD',
                            'additionalPages' => $conf['pidsRelatedDownloads'] ?? '',
                            'mergeRow' => [],
                            'functablename' => 'sys_file_reference',
                            'callFunctableArray' => [],
                            'cached' => true,
                        ],
                    ];
                break;

            case 'tx_dam':
                if (ExtensionManagementUtility::isLoaded('dam')) {
                    if ($uid > 0) {
                        $damext = ['tx_dam' => [
                                ['uid' => $uid],
                            ],
                        ];
                        $extArray = ['ext' => $damext];
                    } else {
                        $extArray = [];
                    }
                    $result =
                        [
                            'products' => [
                                'marker' => 'DAM_PRODUCTS',
                                'template' => 'DAM_ITEM_LIST_TEMPLATE',
                                'require' => true,
                                'code' => $theCode,
                                'additionalPages' => false,
                                'mergeRow' => $extArray,
                                'functablename' => 'tt_products',
                                'callFunctableArray' => [$marker => 'tx_dam'],
                                'cached' => true,
                            ],
                        ];
                }
                break;
        }

        /** @var DoingThisAndThatEvent $event */
        $event = $this->eventDispatcher->dispatch(
            new AddRelatedListEvent(
                $result,
                $theCode,
                $funcTablename,
                $uid,
                $paramUidArray,
                $useArticles
            )
        );

        $eventResult = $event->getResult();

        //     debug ($slotResult, 'getAddListArray Pos 1 $slotResult');
        //         debug ('B');
        if (
            isset($eventResult) &&
            is_array($eventResult)
        ) {
            $result = $eventResult;
        }

        if (is_array($result)) {
            foreach ($result as $subtype => $funcArray) { // neu
                //         debug ($funcArray, '$funcArray');
                $tablename = $cnfObj->getTableName($funcArray['funcTablename']);
                if ($tablename == '' || !isset($GLOBALS['TCA'][$tablename]['columns'])) {
                    unset($result[$subtype]); // if the current TYPO3 version does not support the needed foreign table
                }
            }
        }

        return $result;
    }

    public function getListMarkerArray(
        $theCode,
        $templateCode,
        $viewTagArray,
        $funcTablename,
        $uid,
        array $paramUidArray,
        $parentProductRow,
        $notOverwritePriceIfSet,
        $multiOrderArray,
        $useArticles,
        $pageAsCategory,
        $pid,
        &$error_code
    ) {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $config = $cnfObj->getConfig();
        if (!in_array($uid, $paramUidArray)) {
            $paramUidArray[] = $uid;
        }

        $result = [];
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $itemViewObj = $tablesObj->get($funcTablename, true);
        $addListArray =
            $this->getAddListArray(
                $theCode,
                $funcTablename,
                $itemViewObj->getMarker(),
                $uid,
                $paramUidArray,
                $useArticles
            );

        if (is_array($addListArray)) {
            $listView = '';
            $itemObj = $itemViewObj->getModelObj();

            foreach ($addListArray as $subtype => $funcArray) {
                if (!$funcArray['cached']) {
                    continue;
                }

                if (isset($viewTagArray[$funcArray['marker']]) && $funcArray['require']) {
                    $relatedItemObj = $itemObj;
                    $parentFuncTablename = '';
                    if ($funcTablename != $funcArray['functablename']) {
                        $relatedItemObj = $tablesObj->get($funcArray['functablename'], false);
                        $parentFuncTablename = $funcArray['functablename'];
                    }
                    $tableConf = $relatedItemObj->getTableConf($funcArray['code']);
                    $orderBy = '';
                    if (isset($tableConf['orderBy'])) {
                        $orderBy = $tableConf['orderBy'];
                    }
                    $mergeRow = [];
                    $parentRows = [];
                    $relatedIds =
                        $itemObj->getRelated(
                            $parentFuncTablename,
                            $parentRows,
                            $multiOrderArray,
                            $uid,
                            $subtype,
                            $orderBy
                        );

                    if (count($relatedIds)) {
                        // List all products:

                        if (!is_object($listView)) {
                            $listView = GeneralUtility::makeInstance('tx_ttproducts_list_view');
                            $listView->init(
                                $pid,
                                $paramUidArray,
                                $tmp = $this->pidListObj->getPidlist(),
                                0
                            );
                        }
                        $callFunctableArray = $funcArray['callFunctableArray'];
                        $listPids = $funcArray['additionalPages'];
                        if ($listPids != '') {
                            $this->pidListObj->applyRecursive($config['recursive'], $listPids);
                        } else {
                            $listPids = $this->pidListObj->getPidlist();
                        }

                        $parentDataArray = [
                            'functablename' => $funcTablename,
                            'uid' => $uid,
                        ];

                        $productRowArray = [];
                        $bEditableVariants = true;

                        $tmpContent = $listView->printView(
                            $templateCode,
                            $funcArray['code'],
                            $funcArray['functablename'],
                            implode(',', $relatedIds),
                            $listPids,
                            '',
                            $error_code,
                            $funcArray['template'] . $config['templateSuffix'],
                            $pageAsCategory,
                            tx_ttproducts_control_basket::getBasketExtra(),
                            tx_ttproducts_control_basket::getRecs(),
                            $mergeRow,
                            1,
                            $callFunctableArray,
                            $parentDataArray,
                            $parentProductRow,
                            $parentFuncTablename,
                            $parentRows,
                            $notOverwritePriceIfSet,
                            $multiOrderArray,
                            $productRowArray,
                            $bEditableVariants
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
