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
 * bill and delivery functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;




class tx_ttproducts_billdelivery implements \TYPO3\CMS\Core\SingletonInterface {
    public $tableArray;
    public $price;		 // object for price functions
    public $typeArray = ['bill', 'delivery'];



    public function getTypeArray () {
        return $this->typeArray;
    }


    /**
     * get the relative filename of the bill or delivery file by the tracking code
     */
    public function getRelFilename ($tracking, $type, $fileExtension = 'html') {
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        $rc = $conf['outputFolder'] . '/' . $type . '/' . $tracking . '.' . $fileExtension;

        return $rc;
    }


    public function getMarkerArray (&$markerArray, $tracking, $type) {
        $markerprefix = strtoupper($type);
        $relfilename = $this->getRelFilename($tracking, $type);
        $markerArray['###' . $markerprefix . '_FILENAME###'] = $relfilename;
    }


    public function getFileAbsFileName ($type, $tracking, $fileExtension) {
        $relfilename = $this->getRelFilename($tracking, $type, $fileExtension);
        $filename = GeneralUtility::getFileAbsFileName($relfilename);
        return $filename;
    }


    public function writeFile ($filename, $content) {
        $theFile = fopen($filename, 'wb');
        fwrite($theFile, $content);
        fclose($theFile);
    }


    public function generateBill (
        $cObj,
        $templateCode,
        $mainMarkerArray,
        $itemArray,
        $calculatedArray,
        $orderArray,
        $basketExtra,
        $basketRecs,
        $type,
        $generationConf
    ) {
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        $productRowArray = []; // Todo: make this a parameter

        $typeCode = strtoupper($type);
        $result = false;
        $generationType = strtolower($generationConf['type'] ?? '');
        $billGeneratedFromHook = false;

        // Hook
            // Call all billing delivery hooks
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['billdelivery']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['billdelivery'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['billdelivery'] as $classRef) {
                $hookObj= GeneralUtility::makeInstance($classRef);

                if (method_exists($hookObj, 'generateBill')) {
                    $billGeneratedFromHook = $hookObj->generateBill(
                        $this,
                        $cObj,
                        $templateCode,
                        $mainMarkerArray,
                        $itemArray,
                        $calculatedArray,
                        $orderArray,
                        $basketExtra,
                        $basketRecs,
                        $type,
                        $generationConf,
                        $result
                    );
                }
                if ($billGeneratedFromHook) {
                    break;
                }
            }
        }

        if (!$billGeneratedFromHook && isset($orderArray['bill_no'])) {
            if ($generationType == 'pdf') {

                $absFileName =
                    $this->getFileAbsFileName(
                        $type,
                        str_replace('_', '-', strtolower($orderArray['bill_no'])) . '-' . $orderArray['tracking_code'],
                        'pdf'
                    );
                $className = 'tx_ttproducts_pdf_view';
                if (
                    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('fpdf') // use FPDF
                ) {
                    $className = 'tx_ttproducts_fpdf_view';
                }

                $pdfViewObj = GeneralUtility::makeInstance($className);
                $result = $pdfViewObj->generate(
                    $cObj,
                    $basketView,
                    $infoViewObj,
                    $templateCode,
                    $mainMarkerArray,
                    $itemArray,
                    $calculatedArray,
                    $orderArray,
                    $productRowArray,
                    $basketExtra,
                    $basketRecs,
                    $typeCode,
                    $generationConf,
                    $absFileName
                );
            } else if ($generationType == 'html') {
                $subpart = $typeCode . '_TEMPLATE';
                $content = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $typeCode,
                    $infoViewObj,
                    false,
                    true,
                    $calculatedArray,
                    true,
                    $subpart,
                    $mainMarkerArray,
                    '',
                    $itemArray,
                    $notOverwritePriceIfSet = false,
                    ['0' => $orderArray],
                    $productRowArray,
                    $basketExtra,
                    $basketRecs
                );

                if (
                    !isset($errorCode) ||
                    $errorCode[0] == ''
                ) {
                    $absFileName =
                        $this->getFileAbsFileName(
                            $type,
                            str_replace('_', '-', $orderArray['bill_no']) . '-' . $orderArray['tracking_code'],
                            'html'
                        );
                    $this->writeFile($absFileName, $content);
                    $result = $absFileName;
                }
            } else {
                $result = false;
            }
        }

        return $result;
    }


    /**
     * Bill,Delivery Generation from tracking code
     */
    public function getInformation (
        $theCode,
        $orderRow,
        $templateCode,
        $trackingCode,
        $type
    ) {
        /*
        Bill or delivery information display, which needs tracking code to be shown
        This is extension information to tracking at another page
        See Tracking for further information
        */
        $priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnfObj->getConf();

        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $productRowArray = []; // Todo: make this a parameter

        $globalMarkerArray = $markerObj->getGlobalMarkerArray();
        $orderObj = $tablesObj->get('sys_products_orders');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
// 		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');

            // initialize order data.
        $orderData = $orderObj->getOrderData($orderRow);
        $itemArray =
            $orderObj->getItemArray(
                $orderRow,
                $calculatedArray,
                $infoArray
            );
        $infoViewObj->init2($infoArray);

        $basketRec = \JambageCom\TtProducts\Api\BasketApi::getBasketRec($orderRow);
        $basketExtra =
            tx_ttproducts_control_basket::getBasketExtras(
                $tablesObj,
                $basketRec,
                $conf
            );

        if ($type == 'bill') {
            $subpartMarker='BILL_TEMPLATE';
        } else {
            $subpartMarker='DELIVERY_TEMPLATE';
        }

        $orderArray = [];

        $orderArray['tracking_code'] = $trackingCode;
        $orderArray['uid'] = $orderRow['uid'];
        $orderArray['crdate'] = $orderRow['crdate'];

        $content = $basketView->getView(
            $errorCode,
            $templateCode,
            $theCode,
            $infoViewObj,
            false,
            false,
            $calculatedArray,
            true,
            $subpartMarker,
            $globalMarkerArray,
            '',
            $itemArray,
            $notOverwritePriceIfSet = false,
            ['0' => $orderArray],
            $productRowArray,
            $basketExtra,
            $basketRec
        );

        return $content;
    }
}





