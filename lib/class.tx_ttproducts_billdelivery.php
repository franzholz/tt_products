<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Franz Holzinger (franz@ttproducts.de)
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
	public $cObj;
	public $conf;		  // original configuration
	public $config;		// updated configuration
	public $tableArray;
	public $price;		 // object for price functions
	public $typeArray = array('bill','delivery');


	/**
	 * Initialized the basket, setting the deliveryInfo if a users is logged in
	 * $basketObj is the TYPO3 default shopping basket array from ses-data
	 *
	 * @param		string	  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function init ($cObj) {
		$this->cObj = $cObj;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;
	}


	public function getTypeArray ()	{
		return $this->typeArray;
	}


	/**
	 * get the relative filename of the bill or delivery file by the tracking code
	 */
	public function getRelFilename ($tracking, $type, $fileExtension='html')	{
		$rc = $this->conf['outputFolder'] . '/' . $type . '/' . $tracking . '.' . $fileExtension;

		return $rc;
	}


	public function getMarkerArray (&$markerArray, $tracking, $type)	{
		$markerprefix = strtoupper($type);
		$relfilename = $this->getRelFilename($tracking, $type);
		$markerArray['###'.$markerprefix.'_FILENAME###'] = $relfilename;
	}


	public function getFileAbsFileName ($type, $tracking, $fileExtension) {
		$relfilename = $this->getRelFilename($tracking, $type, $fileExtension);
		$filename = GeneralUtility::getFileAbsFileName($relfilename);
		return $filename;
	}


	public function writeFile ($filename, $content)	{
		$theFile = fopen($filename, 'wb');
		fwrite($theFile, $content);
		fclose($theFile);
	}


    public function generateBill (&$errorCode, $templateCode, $mainMarkerArray, $basketExtra, $type, $generationConf) {
		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

		$typeCode = strtoupper($type);
        $generationType = strtolower($generationConf['type']);
		$result = false;

        // Hook
            // Call all billing delivery hooks
        if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['billdelivery'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['billdelivery'] as $classRef) {
                $hookObj= GeneralUtility::makeInstance($classRef);

                if (method_exists($hookObj, 'generateBill')) {
                    $billGeneratedFromHook = $hookObj->generateBill(
                        $this,
                        $this->cObj,
                        $templateCode,
                        $mainMarkerArray,
                        $basketObj->getItemArray(),
                        $basketObj->getCalculatedArray(),
                        $basketObj->order,
                        $basketExtra,
                        $basketObj->recs,
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

        if (!$billGeneratedFromHook) {

            if ($generationType == 'pdf') {
                $pdfViewObj = GeneralUtility::makeInstance('tx_ttproducts_pdf_view');

                $subpart = $typeCode . '_PDF_HEADER_TEMPLATE';
                $header = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $typeCode,
                    $infoViewObj,
                    false,
                    true,
                    $basketObj->getCalculatedArray(),
                    false,
                    $subpart,
                    $mainMarkerArray,
                    '',
                    $basketObj->getItemArray(),
                    array(),
                    $basketExtra
                );
                $subpart = $typeCode . '_PDF_TEMPLATE';
                $body = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $typeCode,
                    $infoViewObj,
                    false,
                    true,
                    $basketObj->getCalculatedArray(),
                    false,
                    $subpart,
                    $mainMarkerArray,
                    '',
                    $basketObj->getItemArray(),
                    array(),
                    $basketExtra
                );

                $subpart = $typeCode . '_PDF_FOOTER_TEMPLATE';
                $footer = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $typeCode,
                    $infoViewObj,
                    false,
                    true,
                    $basketObj->getCalculatedArray(),
                    false,
                    $subpart,
                    $mainMarkerArray,
                    '',
                    $basketObj->getItemArray(),
                    array(),
                    $basketExtra
                );
                $absFileName = $this->getFileAbsFileName($type, $basketObj->order['orderTrackingNo'], 'pdf');

                $result = $pdfViewObj->generate(
                    $this->cObj,
                    $header,
                    $body,
                    $footer,
                    $absFileName
                );
            } else {
                $subpart = $typeCode . '_TEMPLATE';
                $errorCode = [];
                $content = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $typeCode,
                    $infoViewObj,
                    false,
                    true,
                    $basketObj->getCalculatedArray(),
                    true,
                    $subpart,
                    $mainMarkerArray,
                    '',
                    $basketObj->getItemArray(),
                    array(),
                    $basketExtra
                );

                if (!isset($errorCode) || $errorCode[0] == '') {
                    $absFileName = $this->getFileAbsFileName($type, $basketObj->order['orderTrackingNo'], 'html');
                    $this->writeFile($absFileName, $content);
                    $result = $absFileName;
                }
            }
        }
        return $result;
    }


	/**
	 * Bill,Delivery Generation from tracking code
	 */
	public function getInformation ($theCode, $orderRow, $templateCode, $trackingCode, $type)	{
		/*
		Bill or delivery information display, which needs tracking code to be shown
		This is extension information to tracking at another page
		See Tracking for further information
		*/
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

 		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config');
		$conf = $cnfObj->getConf();
		$globalMarkerArray = $markerObj->getGlobalMarkerArray();
		$orderObj = $tablesObj->get('sys_products_orders');
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');

			// initialize order data.
		$orderData = unserialize($orderRow['orderData']);
// 		$markerArray = array();
// 		$subpartArray = array();
// 		$wrappedSubpartArray = array();

		$itemArray = $orderObj->getItemArray($orderRow, $calculatedArray, $infoArray);
		$infoViewObj->init2($infoArray);

		$basketRec = $paymentshippingObj->getBasketRec($orderRow);
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

		$orderArray = array();

		$orderArray['orderTrackingNo'] = $trackingCode;
		$orderArray['orderUid'] = $orderRow['uid'];
		$orderArray['orderDate'] = $orderRow['crdate'];

		$content = $basketView->getView(
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
			$orderArray,
			$basketExtra
		);

		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_billdelivery.php']);
}



