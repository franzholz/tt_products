<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2018 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for a connection to the payment gateways
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use JambageCom\Transactor\Api\PaymentApi;
use JambageCom\Transactor\Api\Start;
use JambageCom\Transactor\Api\Address;
use JambageCom\Transactor\Api\PaymentAp;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

class PaymentGatewayApi implements SingletonInterface
{
    final public const TRANSACTOR_EXTENSION = 'transactor';
    protected $handleScript = '';
    protected $handleLib = '';
    protected $handleLibConf = [];
    protected $useNewTransactor = false;
    protected $useOldTransactor = false;
    private bool $needsInit = true;

    public function init(array $basketExtra): void
    {
        if (!$this->needsInit) {
            return;
        }
        $handleLib = '';
        if (
            isset($basketExtra['payment.'])
        ) {
            if (isset($basketExtra['payment.']['handleScript'])) {
                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $handleScript = $sanitizer->sanitize($basketExtra['payment.']['handleScript']);
                $this->setHandleScript($handleScript);
            } elseif (
                isset($basketExtra['payment.']['handleLib']) &&
                isset($basketExtra['payment.']['handleLib.'])
            ) {
                $handleLib = $basketExtra['payment.']['handleLib'];
                $this->setHandleLib($handleLib);
                $this->setHandleLibConf($basketExtra['payment.']['handleLib.']);
            }
        }

        if (
            strpos($handleLib, (string) static::TRANSACTOR_EXTENSION) !== false &&
            ExtensionManagementUtility::isLoaded($handleLib)
        ) {
            $this->setUseNewTransactor(true);
        }
        $this->needsInit = false;
    }

    public function setHandleScript($handleScript): void
    {
        $this->handleScript = $handleScript;
    }

    public function getHandleScript()
    {
        return $this->handleScript;
    }

    public function setHandleLib($handleLib): void
    {
        $this->handleLib = $handleLib;
    }

    public function getHandleLib()
    {
        return $this->handleLib;
    }

    public function setHandleLibConf($handleLibConf): void
    {
        $this->handleLibConf = $handleLibConf;
    }

    public function getHandleLibConf()
    {
        return $this->handleLibConf;
    }

    public function setUseNewTransactor($useNewTransactor): void
    {
        $this->useNewTransactor = $useNewTransactor;
    }

    public function getUseNewTransactor()
    {
        return $this->useNewTransactor;
    }

    // Has any payment gateway parameter been detected upon which some action must be taken?
    public function readActionParameters(
    ) {
        $result = false;

        if (
            $this->getUseNewTransactor()
        ) {
            $callingClassName = Start::class;

            if (
                class_exists($callingClassName) &&
                method_exists($callingClassName, 'readActionParameters')
            ) {
                $errorMessage = '';
                $parameters = [
                    &$errorMessage,
                    ControlApi::getCObj(),
                    $this->getHandleLibConf(),
                ];
                $result = call_user_func_array(
                    $callingClassName . '::readActionParameters',
                    $parameters
                );
            }
        }

        return $result;
    }

    // Javasript e.g. to return to the main window if a popup window redirects back to the shop. The shop output should not be shown in the popup window.
    public function addMainWindowJavascript(
        $itemArray
    ): void {
        if (
            $this->getUseNewTransactor() &&
            !empty($itemArray) &&
            !empty(array_filter($itemArray))
        ) {
            $callingClassName = Start::class;

            if (
                class_exists($callingClassName) &&
                method_exists($callingClassName, 'addMainWindowJavascript')
            ) {
                $errorMessage = '';
                $parameters = [
                    &$errorMessage,
                    $this->getHandleLibConf(),
                ];
                call_user_func_array(
                    $callingClassName . '::addMainWindowJavascript',
                    $parameters
                );
            }
        }
    }

    public function doDataCollectionPayment(
        &$errorMessage,
        &$addressModel,
        $languageObj,
        $conf,
        array $itemArray,
        $orderUid,
        $orderNumber, // text string of the order number
        $returnUrl,
        $cancelUrl
    ) {
        $variantFields = \tx_ttproducts_control_product::getAllVariantFields();
        $result = false;

        // TODO:
        if (
            $this->getUseNewTransactor() &&
            !empty(array_filter($itemArray))
        ) {
            $callingClassName = Start::class;

            if (
                class_exists($callingClassName) &&
                method_exists($callingClassName, 'renderDataEntry') &&
                class_exists(Address::class)
            ) {
                $paymentConf = [];
                if (isset($conf['_LOCAL_LANG.'])) {
                    $paymentConf['_LOCAL_LANG.'] = $conf['_LOCAL_LANG.'];
                }

                $paymentBasket =
                     PaymentApi::convertToTransactorBasket($itemArray, $variantFields);
                $parameters = [
                    $languageObj,
                    '',
                    $paymentConf,
                    false,
                ];
                call_user_func_array(
                    $callingClassName . '::init',
                    $parameters
                );
                $extraData = [
                    'return_url' => rtrim($returnUrl, '/'),
                    'cancel_url' => rtrim($cancelUrl, '/'),
                ];
                $parameters = [
                    &$errorMessage,
                    &$addressModel,
                    $this->getHandleLibConf(),
                    TT_PRODUCTS_EXT,
                    $paymentBasket,
                    $orderUid,
                    $orderNumber, // text string of the order number
                    $conf['currency'] ?? '',
                    $extraData,
                ];
                $content = call_user_func_array(
                    $callingClassName . '::renderDataEntry',
                    $parameters
                );
                $result = $content;
            }
        }

        return $result;
    }
}

