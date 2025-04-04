<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Hooks;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger (franz@ttproducts.de)
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
 * hook functions for the Transactor API extension
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\MarkerUtility;

use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use JambageCom\TtProducts\Controller\Base\Creator;

class TransactorListener
{
    public function execute(
        $pObj,
        $params
    ) {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);

        if (
            !isset($params['row']) ||
            !isset($params['row']['ext_key']) ||
            $params['row']['ext_key'] != TT_PRODUCTS_EXT
        ) {
            return false;
        }

        // neu Anfang
        // 		FrontendUtility::init();
        $callingClassName3 = Bootstrap::class;
        $bootStrap = call_user_func([$callingClassName3, 'getInstance']);
        $bootStrap->loadExtensionTables(true);

        if ($GLOBALS['LANG'] === null) {
            $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageService::class);
            $GLOBALS['LANG']->init('en');
        }

        $transactionRow = $params['row'];
        $testMode = $params['testmode'];
        $referenceId = $transactionRow['reference'];
        $conf = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.'] ?? null;

        $config = [];
        $config['LLkey'] = '';
        $errorCode = '';
        $cObj = FrontendUtility::getContentObjectRenderer(
            [],
            'tt_products'
        );

        $orderUid = 0;
        $orderTablename = 'sys_products_orders';

        if ($testMode) {
            $orderUid = $parameterApi->getPiVarValue($orderTablename); // keep this line for testing purposes
            $orderUid = intval($orderUid);
        } else {
            $orderUid = intval($transactionRow['orderuid']);
        }

        $where_clause = $orderTablename . '.uid=' . intval($orderUid);
        $where_clause .= ' AND ' . $orderTablename . '.deleted=0 AND ' . $orderTablename . '.hidden=1';
        $orderRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $orderTablename, $where_clause);
        $basketRec = $basketApi->getBasketRec($orderRow);
        $controlCreatorObj = GeneralUtility::makeInstance(Creator::class);
        $result =
            $controlCreatorObj->init(
                $conf,
                $config,
                '',
                $cObj,
                '',
                $errorCode,
                [],
                $basketRec
            );
        if (!$result) {
            return false;
        }

        $modelCreatorObj = GeneralUtility::makeInstance('tx_ttproducts_model_creator');
        $modelCreatorObj->init($conf, $config, $cObj);

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables'); // init ok
        $orderObj = $tablesObj->get('sys_products_orders');
        $orderUid = 0;

        if ($testMode) {
            $orderUid = $parameterApi->getPiVarValue('sys_products_orders'); // keep this line for testing purposes
            $orderUid = intval($orderUid);
        } else {
            $orderUid = intval($transactionRow['orderuid']);
        }

        if ($orderUid && $referenceId) {
            if (isset($orderRow) && is_array($orderRow) && $orderRow['hidden']) {
                $calculatedArray = [];
                $infoArray = [];

                $itemArray = $orderObj->getItemArray(
                    $orderRow,
                    $calculatedArray,
                    $infoArray
                );

                $infoObj = GeneralUtility::makeInstance('tx_ttproducts_info');
                $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
                $infoObj->init($infoArray, $conf['pdfInfoFields']);
                $infoViewObj->init(
                    $infoObj
                );
                $addressObj = $tablesObj->get('address', false);
                $addressArray = $addressObj->fetchAddressArray($itemArray);

                $mainMarkerArray = [];
                $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
                // neu Anfang FHO: Die Marker der Transactor PayPal auswerten:
                if (
                    isset($params['parameters']) &&
                    is_array($params['parameters']) &&
                    !empty($params['parameters'])
                ) {
                    $parameters = $params['parameters'];
                    foreach ($parameters as $key => $parameter) {
                        MarkerUtility::addMarkers(
                            $mainMarkerArray,
                            'TRANSACTOR',
                            '_',
                            $key,
                            $parameter
                        );
                    }
                }

                $feUserRecord = CustomerApi::getFeUserRecord();
                $basketExtra =
                    PaymentShippingHandling::getBasketExtras(
                        $conf,
                        $tablesObj,
                        $basketRec,
                        $feUserRecord,
                    );
                $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
                $templateFile = '';
                $errorCode = '';

                if ($conf['fe']) {
                    $templateCode =
                        $templateObj->get(
                            'FINALIZE',
                            $templateFile,
                            $errorCode
                        );
                }

                if ($templateCode != '' && $errorCode == '') {
                    $errorMessage = '';
                    $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
                    $basketView->init(
                        [],
                        $conf['useArtcles'],
                        $errorCode
                    );
                    $feUserRecord = $parameterApi->getRequest()->getAttribute('frontend.user')->user;
                    if ($errorCode == '') {
                        \tx_ttproducts_api::finalizeOrder(
                            $this,
                            $templateCode,
                            $mainMarkerArray,
                            $funcTablename = 'tt_products',
                            $orderUid,
                            $orderRow,
                            $feUserRecord,
                            $itemArray,
                            $calculatedArray,
                            $addressArray,
                            $basketExtra,
                            $basketRec,
                            '',
                            floatval(0),
                            false,
                            $errorMessage
                        );
                    }
                }
            } else {
                echo 'no order found';
            }
        }
    }
}
