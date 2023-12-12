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
 * control function for the commands.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\ExtensionUtility;
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_control_command
{
    protected static $commandVar = 'cmd';

    public static function getCommandVar()
    {
        return self::$commandVar;
    }

    public static function getVariantVars($piVars)
    {
        $result = [];

        $paramsTableArray = tx_ttproducts_model_control::getParamsTableArray();
        if (isset($piVars) && is_array($piVars)) {
            foreach ($piVars as $piVar => $v) {
                if (!isset($paramsTableArray[$piVar])) {
                    $result[$piVar] = $v;
                }
            }
        }

        return $result;
    }

    public static function doProcessing(
        $theCode,
        $conf,
        $bIsAllowedBE,
        $bValidUpdateCode,
        $trackingCode,
        $pid_list,
        $recursive
    ) {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $bHasBeenOrdered = false;

        $pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
        $pidListObj->applyRecursive($recursive, $pid_list, true);
        $pidListObj->setPageArray();

        $postVar = self::getCommandVar();
        $cmdData = GeneralUtility::_GP($postVar);

        switch ($theCode) {
            case 'DOWNLOAD':
                if (
                    ExtensionManagementUtility::isLoaded('addons_em') &&
                    isset($cmdData['download']) ||
                    isset($cmdData['fal'])
                ) {
                    $falUid = intval($cmdData['fal']);
                    $downloadTable = $tablesObj->get('tt_products_downloads', false);
                    $downloadVar =
                        tx_ttproducts_model_control::getPiVar(
                            $downloadTable->getFuncTablename()
                        );
                    $piVars = tx_ttproducts_model_control::getPiVars();
                    $variantVars = self::getVariantVars($piVars);
                    $orderVar =
                        tx_ttproducts_model_control::getPiVar(
                            'sys_products_orders'
                        );
                    $orderUid = 0;

                    if ($trackingCode != '') {
                        $trackingArray = explode('-', $trackingCode);
                        $count = count($trackingArray);
                        if ($count >= 2) {
                            $orderUid = intval($trackingArray[$count - 2]);
                        }
                    } elseif (isset($piVars[$orderVar])) {
                        $orderUid = intval($piVars[$orderVar]);
                    }

                    $feusers_uid = $GLOBALS['TSFE']->fe_user->user['uid'];
                    $orderObj = $tablesObj->get('sys_products_orders'); // order
                    $orderObj->getDownloadWhereClauses(
                        $feusers_uid,
                        $trackingCode,
                        $whereOrders,
                        $whereProducts
                    );

                    $uid = intval($piVars[$downloadVar]);
                    $downloadAuthorization = $cnf->getDownloadConf('authorization');
                    $validFeUser = false;
                    if (
                        $downloadAuthorization == 'FE' &&
                        $feusers_uid > 0
                    ) {
                        $validFeUser = true;
                    }

                    if (
                        $feusers_uid &&
                        (
                            $orderUid &&
                            (
                                $trackingCode != '' ||
                                $bIsAllowedBE && $bValidUpdateCode
                            ) ||
                            $validFeUser
                        )
                    ) {
                        $from = '';
                        $orderObj->getOrderedAndGainedProducts(
                            $from,
                            $whereOrders,
                            '',
                            $whereProducts,
                            false,
                            $pid_list,
                            $productRowArray,
                            $multiOrderArray
                        );

                        if (
                            $validFeUser &&
                            !$orderUid
                        ) { // determine if an order exists
                            $orderUid =
                                $downloadTable->getOrderedUid(
                                    $uid,
                                    $falUid,
                                    $multiOrderArray
                                );
                        }

                        $orderRow = [];
                        if ($orderUid) {
                            $orderRow = $orderObj->get($orderUid);
                        }

                        $downloadArray = [];

                        if ($orderUid && is_array($productRowArray) && count($productRowArray)) {
                            $productUidArray = [];
                            foreach ($productRowArray as $productRow) {
                                $productUidArray[$productRow['uid']] = $productRow['uid'];
                            }
                            $downloadArray =
                                $downloadTable->getRelatedUidArray(
                                    implode(',', $productUidArray),
                                    $downloadTagArray,
                                    'tt_products'
                                );
                        }

                        foreach ($downloadArray as $downloadRow) {
                            if ($downloadRow['uid'] == $uid) {
                                $bHasBeenOrdered = true;
                                break;
                            }
                        }
                    }

                    if (
                        (
                            $bHasBeenOrdered ||
                            $bIsAllowedBE && $bValidUpdateCode
                        ) &&
                        isset($piVars[$downloadVar])
                    ) {
                        $row = $downloadTable->get($uid, '', false);
                        if (isset($row) && is_array($row)) {
                            if (isset($cmdData['fal'])) {
                                tx_ttproducts_api_download::fetchFal(intval($cmdData['fal']));
                            } else {
                                $fileArray =
                                    $downloadTable->getFileArray(
                                        $orderObj,
                                        $row,
                                        $multiOrderArray
                                    );
                                $filename = basename($row['path']);
                                $filenameDividerPos = strpos($filename, '-');
                                if ($filenameDividerPos !== false) {
                                    $extKey = substr($filename, 0, $filenameDividerPos);
                                } else {
                                    $extKey = $filename;
                                }
                                $path = $fileArray[$cmdData['download']] . $extKey . '/';
                                $extInfo = ExtensionUtility::getExtensionInfo($extKey, $path);

                                // Ausführen des Kommandos und EXIT
                                tx_addonsem_file_div::extBackup(
                                    $extKey,
                                    $path,
                                    $extInfo,
                                    $orderRow,
                                    $variantVars
                                );
                            }
                        }
                    } else {
                        if (!$feusers_uid) {
                            $message = $languageObj->getLabel('download_requires_felogin');
                            echo $message;
                        }

                        if (!isset($piVars[$downloadVar])) {
                            debug($piVars, 'download command: no parameter download has been set'); // keep this
                            $message = $languageObj->getLabel('error_download');
                            echo $message;
                        } elseif (!$bHasBeenOrdered) {
                            debug($bHasBeenOrdered, 'DOWNLOAD is not allowed because the product has not been ordered by the FE user with uid = ' . $feusers_uid . '. Therefore nothing happens here.'); // keep this
                        } elseif (!$feusers_uid) {
                            debug($piVars, 'Internal error. No FE User has been selected'); // keep this
                        }
                    }
                    exit;
                } else {
                    // nothing
                }
                break;
        }
    }
}
