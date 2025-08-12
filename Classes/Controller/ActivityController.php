<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Franz Holzinger (franz@ttproducts.de)
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
 * class with functions to control all activities
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;
use JambageCom\Div2007\Utility\MarkerUtility;

use JambageCom\Transactor\Api\Address;
use JambageCom\Transactor\Api\Start;
use function JambageCom\Transactor\Api\getTransactorConf;

use JambageCom\TtProducts\Api\DatabaseTableApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\FeUserMarkerApi;
use JambageCom\TtProducts\Api\CustomerApi;
use JambageCom\TtProducts\Api\ActivityApi;
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\Api\PaymentGatewayApi;
use JambageCom\TtProducts\Api\PaymentShippingHandling;

class ActivityController implements SingletonInterface
{
    public $conf;
    public $config;
    public $urlObj; // url functions
    public $urlArray; // overridden url destinations
    public $useArticles;

    public static $nextActivity = [
        'basket' => 'info',
        'info' => 'payment',
        'payment' => 'finalize',
    ];
    public static $activityMap = [
        'basket' => 'products_basket',
        'info' => 'products_info',
        'payment' => 'products_payment',
        'finalize' => 'products_finalize',
    ];

    public function init(
        $useArticles,
        $basketExtra
    ): void {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->conf = $cnf->conf;
        $this->config = $cnf->config;
        $this->useArticles = $useArticles;

        $this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        // This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
        $this->urlArray = [];
        if (!empty($basketExtra['payment.']['handleURL'])) {
            $this->urlArray['form_url_thanks'] = $basketExtra['payment.']['handleURL'];
        }

        if (!empty($basketExtra['payment.']['handleTarget'])) {	// Alternative target
            $this->urlArray['form_url_target'] = $basketExtra['payment.']['handleTarget'];
        }
        $this->urlObj->setUrlArray($this->urlArray);
    } // init

    protected function getOrderData(&$orderUid, &$orderNumber, &$orderArray)
    {
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $orderUid = 0;
        $orderNumber = '';

        if (isset($orderArray['uid'])) {
            $orderUid = $orderArray['uid'];
        }

        if (!$orderUid && count($basketObj->itemArray)) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $orderUid = $orderObj->getUid();
            if ($orderUid) {
                $orderArray = $orderObj->getCurrentArray();
            } else {
                $orderUid = $orderObj->getBlankUid($orderArray);
                $orderObj->setUid($orderUid);
            }
        }

        $orderNumber =
        DatabaseTableApi::generateOrderNo(
            $orderUid,
            $this->conf['orderNumberPrefix'] ?? ''
        );
    }

    protected function processPayment(
        $orderUid,
        $orderNumber,
        $cardRow,
        $pidArray,
        $currentPaymentActivity,
        array $calculatedArray,
        $basketExtra,
        $basketRecs,
        $orderArray,
        $productRowArray,
        array $infoArray,
        array &$markerArray,
        &$finalize,
        &$finalVerify,
        &$paymentScript,
        &$errorCode,
        &$errorMessage
    ) {
        $content = '';
        $localTemplateCode = '';
        $paymentScript = false;
        $templateFilename = null;

        if ($orderUid) {
            $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
            $basketViewObj = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
            $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
            // $cObj = ControlApi::getCObj();
            $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
            $request = $parameterApi->getRequest();
            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
            $conf = $cnf->getConf();
            $languageObj = GeneralUtility::makeInstance(Localization::class);
            $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
            $gateway = GeneralUtility::makeInstance(PaymentGatewayApi::class);
            $request = $parameterApi->getRequest();
            $gateway->init(
                $request,
                $languageObj,
                $conf,
                $basketExtra
            );
            $handleScript = $gateway->getHandleScript();
            $handleLib = $gateway->getHandleLib();
            $variantFields = \tx_ttproducts_control_product::getAllVariantFields();

            if ($handleScript) {
                $paymentScript = true;
                $content = PaymentShippingHandling::includeHandleScript(
                    $finalize,
                    $localTemplateCode,
                    $errorMessage,
                    $handleScript,
                    $basketExtra,
                    $conf['paymentActivity'] ?? '',
                    $conf['TAXpercentage']
                );
            } elseif (
                $handleLib == 'transactor'
            ) {
                $paymentScript = true;
                $callingClassName = Start::class;

                if (
                    class_exists($callingClassName) &&
                    method_exists($callingClassName, 'init') &&
                    method_exists($callingClassName, 'includeHandleLib')
                ) {
                    $languageObj = GeneralUtility::makeInstance(Localization::class);
                    call_user_func($callingClassName . '::init', $languageObj, $request, $this->conf);
                    $gatewayStatus = '';
                    $addQueryString = [];
                    $excludeList = '';
                    $linkParams =
                    $this->urlObj->getLinkParams(
                        $excludeList,
                        $addQueryString,
                        true,
                        false
                    );

                    $parameters = [
                        &$finalize,
                        &$finalVerify,
                        &$gatewayStatus,
                        &$markerArray,
                        &$templateFilename,
                        &$localTemplateCode,
                        &$errorMessage,
                        $handleLib,
                        $basketExtra['payment.']['handleLib.'] ?? [],
                        TT_PRODUCTS_EXT,
                        $basketObj->getItemArray(),
                        $calculatedArray,
                        $basketObj->recs['delivery']['note'] ?? '',
                        $conf['paymentActivity'] ?? '',
                        $currentPaymentActivity,
                        $infoArray,
                        $pidArray,
                        $linkParams,
                        $orderArray['tracking_code'] ?? '',
                        $orderUid,
                        $orderNumber,
                        $conf['orderEmail_to'] ?? '',
                        $cardRow,
                        $variantFields,
                    ];
                    $content = call_user_func_array(
                        $callingClassName . '::render',
                        $parameters
                    );
                } else {
                    throw new \RuntimeException('Error in tt_products: The transactor API has been called but the necessary transactor class or its method do not exist.', 50009);
                }
            }

            if (
                !$errorMessage &&
                $content == '' &&
                !$finalize &&
                $localTemplateCode != ''
            ) {
                $content = $basketViewObj->getView(
                    $errorCode,
                    $localTemplateCode,
                    'PAYMENT',
                    $infoViewObj,
                    false,
                    false,
                    $calculatedArray,
                    true,
                    'TRANSACTOR_FORM_TEMPLATE',
                    $markerArray,
                    [],
                    [],
                    $templateFilename,
                    $basketObj->getItemArray(),
                    $notOverwritePriceIfSet = true,
                    ['0' => $orderArray],
                    $productRowArray,
                    $basketExtra,
                    $basketRecs,
                    $variantFields
                );
            }
        }

        return $content;
    } // processPayment

    public function getErrorLabel(
        $languageObj,
        $accountObj,
        $cardObj,
        $pidagb,
        $infoArray,
        $checkRequired,
        $checkAllowed,
        $cardRequired,
        $accountRequired,
        //         $giftRequired,
        $paymentErrorMsg
    ) {
        $label = '';
        $languageKey = '';
        $context = GeneralUtility::makeInstance(Context::class);
        $languageSubpath = '/Resources/Private/Language/';
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        if ($checkRequired || $checkAllowed) {
            $check = ($checkRequired ?: $checkAllowed);
            //             $check = ($check ? $check : $giftRequired);
            if (
                $checkAllowed == 'email'
            ) {
                if (
                    ExtensionManagementUtility::isLoaded('sr_feuser_register') ||
                    ExtensionManagementUtility::isLoaded('agency')
                ) {
                    $languageKey = 'evalErrors_email_email';
                } else {
                    $languageKey = 'invalid_email';
                }
            }

            if (ExtensionManagementUtility::isLoaded('agency')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $GLOBALS['TSFE']->sL('LLL:EXT:agency/pi/locallang.xml:' . $languageKey);
                $editPID = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.'][TT_PRODUCTS_EXT . '.']['editPID'] ?? 0;;

                if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && $editPID) {
                    $addParams = ['products_payment' => 1];
                    $addParams = $this->urlObj->getLinkParams('', $addParams, true);
                    $cObj = ControlApi::getCObj();

                    $agencyBackUrl =
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $GLOBALS['TSFE']->id,
                        $addParams,
                        '',
                        []
                    );

                    $agencyParams = ['agency[backURL]' => $agencyBackUrl];
                    $addParams =
                    $this->urlObj->getLinkParams(
                        '',
                        $agencyParams,
                        true
                    );
                    $markerArray['###FORM_URL_INFO###'] =
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $editPID,
                        $addParams
                    );
                }
            } elseif (ExtensionManagementUtility::isLoaded('sr_feuser_register')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $GLOBALS['TSFE']->sL('LLL:EXT:sr_feuser_register' . $languageSubpath . 'locallang.xlf:' . $languageKey);
                $editPID = $GLOBALS['TYPO3_REQUEST']->getAttribute('frontend.typoscript')->getSetupArray()['plugin.']['tx_srfeuserregister_pi1.']['editPID'] ?? 0;;

                if ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && $editPID) {
                    $cObj = ControlApi::getCObj();
                    $addParams = ['products_payment' => 1];
                    $addParams =
                    $this->urlObj->getLinkParams(
                        '',
                        $addParams,
                        true
                    );
                    $srfeuserBackUrl =
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $GLOBALS['TSFE']->id,
                        $addParams,
                        '',
                        []
                    );

                    $srfeuserParams = ['tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl];
                    $addParams = $this->urlObj->getLinkParams('', $srfeuserParams, true);
                    $markerArray['###FORM_URL_INFO###'] =
                    FrontendUtility::getTypoLink_URL(
                        $cObj,
                        $editPID,
                        $addParams
                    );
                }
            }

            if (!$label) {
                if ($languageKey) {
                    $label = $languageObj->getLabel($languageKey);
                } else {
                    $tmpArray = GeneralUtility::trimExplode('|', $languageObj->getLabel('missing'));
                    $label = $languageObj->getLabel('missing_' . $check);
                    if ($label) {
                        $label = $tmpArray[0] . ' ' . $label . ' ' . $tmpArray[1];
                    } else {
                        $label = 'field: ' . $check;
                    }
                }
            }
        } elseif (
            $pidagb &&
            empty($_REQUEST['recs']['personinfo']['agb']) &&
            empty($parameterApi->getGetParameter('products_payment')) &&
            empty($infoArray['billing']['agb'])
        ) {
            // so AGB has not been accepted
            $label = $languageObj->getLabel('accept_AGB');
            $addQueryString['agb'] = 0;
        } elseif ($cardRequired) {
            $label = '*' . $languageObj->getLabel($cardObj->getTablename() . '.' . $cardRequired) . '*';
        } elseif ($accountRequired) {
            $label = $languageObj->getLabel('check_missing_iban');
        } elseif ($paymentErrorMsg) {
            $label = $paymentErrorMsg;
        } else {
            $message = $languageObj->getLabel('internal_error');
            $messageArr = explode('|', $message);
            $label = $messageArr[0] . 'TTP_2' . $messageArr[1] . 'products_payment' . $messageArr[2];
        }

        return $label;
    }

    public function getContent(
        $templateCode,
        $templateFilename,
        array &$mainMarkerArray,
        array $calculatedArray,
        array $basketExtra,
        array $basketRecs,
        $orderUid,
        $orderNumber,
        $orderArray,
        $productRowArray,
        $theCode,
        $basket_tmpl,
        $isPayment,
        $activityArray,
        $currentPaymentActivity,
        $pidArray,
        array $infoArray,
        $checkBasket,
        $basketEmpty,
        $checkRequired,
        $checkAllowed,
        $cardRequired,
        $accountRequired,
        $checkEditVariants,
        $paymentErrorMsg,
        $pidagb,
        $cardObj,
        $cardRow,
        $accountObj,
        $infoObj,
        &$markerArray,
        &$errorCode,
        &$errorMessage,
        &$finalize,
        &$finalVerify
    ) {
        $bNeedsMinCheck = null;
        $shopCountryArray = [];
        $taxInfoArray = null;
        $nextActivity = [];
        $empty = '';
        $hiddenFields = '';
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $basketViewObj = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $cObj = ControlApi::getCObj();
        $content = '';
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();
        $paymentScript = false;
        $feUserRecord = CustomerApi::getFeUserRecord();
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);

        if ($checkBasket && !$basketEmpty) {
            $basketConf = $cnf->getBasketConf('minPrice'); // check the basket limits

            foreach ($activityArray as $activity => $valid) {
                $bNeedsMinCheck = false;
                if ($valid) {
                    $bNeedsMinCheck =
                    in_array(
                        $activity,
                        [
                            'products_info',
                             'products_payment',
                             'products_customized_payment',
                             'products_verify',
                             'products_finalize',
                             'unknown',
                        ]
                    );
                }

                if ($bNeedsMinCheck) {
                    break;
                }
            }

            if ($bNeedsMinCheck && isset($basketConf['type']) && $basketConf['type'] == 'price') {
                $value = $calculatedArray['priceTax'][$basketConf['collect']];

                if (
                    isset($value) &&
                    isset($basketConf['collect']) &&
                    $value < floatval($basketConf['value'])
                ) {
                    $basket_tmpl = 'BASKET_TEMPLATE_MINPRICE_ERROR';
                    $finalize = false;
                }
            }
        }

        $basketMarkerArray = [];
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);

        if ($checkBasket && $basketEmpty) {
            $contentEmpty = '';
            if (!empty($activityArray['products_overview'])) {
                $contentEmpty = $templateService->getSubpart(
                    $templateCode,
                    $subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY' . $config['templateSuffix'] . '###')
                );

                if (!$contentEmpty) {
                    $contentEmpty = $templateService->getSubpart(
                        $templateCode,
                        $subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY###')
                    );
                }
            } elseif (
                !empty($activityArray['products_basket']) ||
                !empty($activityArray['products_info']) ||
                !empty($activityArray['products_payment'])
            ) {
                $subpart = 'BASKET_TEMPLATE_EMPTY';
                $contentEmpty = \tx_ttproducts_api::getErrorOut(
                    $theCode,
                    $templateCode,
                    $subpartmarkerObj->spMarker('###' . $subpart . $config['templateSuffix'] . '###'),
                                                                $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                                                                $errorCode
                );
            } elseif (!empty($activityArray['products_finalize'])) {
                // Todo: Neuabsenden einer bereits abgesendeten Bestellung. Der Warenkorb ist schon gelöscht.
                if (!$orderArray) {
                    $contentEmpty = $languageObj->getLabel(
                        'order_already_finalized'
                    );
                }
            }

            if ($contentEmpty != '') {
                $viewTagArray = MarkerUtility::getTags($contentEmpty);
                $feuserSubpartArray = [];
                $feuserWrappedSubpartArray = [];
                $orderAddressObj = $tablesObj->get('fe_users', false);
                $feUserMarkerApi = GeneralUtility::makeInstance(FeUserMarkerApi::class);
                $feUserMarkerApi->getWrappedSubpartArray(
                    $orderAddressObj,
                    $viewTagArray,
                    $feUserRecord,
                    $feuserSubpartArray,
                    $feuserWrappedSubpartArray
                );
                $contentEmpty =
                    $markerObj->replaceGlobalMarkers(
                        $contentEmpty,
                        [],
                        $feuserSubpartArray,
                        $feuserWrappedSubpartArray
                    );
                $finalize = false;
            }
            $content .= $contentEmpty;
            $taxRateArray =
            $taxObj->getTaxRates(
                $shopCountryArray,
                $taxInfoArray,
                $basketObj->getUidArray(),
                $basketRecs
            );

            if (
                isset($taxRateArray) &&
                is_array($taxRateArray) &&
                isset($shopCountryArray) &&
                is_array($shopCountryArray) &&
                isset($shopCountryArray['country_code'])
            ) {
                $taxArray = $taxRateArray[$shopCountryArray['country_code']];
            } elseif (
                isset($taxRateArray) &&
                is_array($taxRateArray)
            ) {
                $taxArray = current($taxRateArray);
            } else {
                $taxArray = [];
            }

            $basketMarkerArray =
            $basketViewObj->getMarkerArray(
                $basketExtra,
                $calculatedArray,
                $taxArray
            );
            $markerArray = $basketMarkerArray;
        } elseif (
            empty($checkRequired) &&
            empty($checkAllowed) &&
            empty($cardRequired) &&
            empty($accountRequired) &&
            empty($paymentErrorMsg) &&
            (
                empty($pidagb) ||
                !empty($_REQUEST['recs']['personinfo']['agb']) ||
                ($isPayment && $parameterApi->getGetParameter('products_payment')) ||
                !empty($infoArray['billing']['agb'])
            )
        ) {
            if (
                $isPayment &&
                !$basketEmpty &&
                (
                    $conf['paymentActivity'] == 'payment' ||
                    $conf['paymentActivity'] == 'verify'
                )
            ) {
                $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
                $this->processPayment(
                    $orderUid,
                    $orderNumber,
                    $cardRow,
                    $pidArray,
                    $currentPaymentActivity,
                    $calculatedArray,
                    $basketExtra,
                    $basketRecs,
                    $orderArray,
                    $productRowArray,
                    $infoArray,
                    $mainMarkerArray,
                    $finalize,
                    $finalVerify,
                    $paymentScript,
                    $errorCode,
                    $errorMessage
                );
                if ($errorMessage != '') {
                    $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
                    $markerArray['###ERROR_DETAILS###'] = $errorMessage;
                }
            } else {
                $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
            }
            $paymentHTML = '';

            if (
                !$finalize &&
                $basket_tmpl != ''
            ) {
                $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

                if (is_array($activityArray)) {
                    $shortActivity = '';
                    $nextActivity = '';
                    foreach ($activityArray as $activity) {
                        $shortActivity = array_search($activity, static::$activityMap);
                        $nextActivity = static::$nextActivity[$activity] ?? '';
                        break;
                    }

                    if ($shortActivity) {
                        $xhtmlFix = HtmlUtility::getXhtmlFix();
                        $hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity][' . $shortActivity . ']" value="1"' . $xhtmlFix . '>';
                    }
                }
                $mainMarkerArray['###HIDDENFIELDS###'] = $hiddenFields;

                $nextUrl = FrontendUtility::getTypoLink_URL(
                    $cObj,
                    $conf['PID' . $nextActivity] ?? '',
                    []
                );
                $mainMarkerArray['###FORM_URL_NEXT_ACTIVITY###'] = $nextUrl;
                $paymentHTML = $basketViewObj->getView(
                    $errorCode,
                    $templateCode,
                    $theCode,
                    $infoViewObj,
                    $feUserRecord,
                    $activityArray['products_info'] ?? false,
                    false,
                    $calculatedArray,
                    true,
                    $basket_tmpl,
                    $mainMarkerArray,
                    [],
                    [],
                    $templateFilename,
                    $basketObj->getItemArray(),
                    $notOverwritePriceIfSet = true,
                    ['0' => $orderArray],
                    $productRowArray,
                    $basketExtra,
                    $basketRecs
                );

                $checkResult = false;
                if (empty($errorCode)) {
                    $checkResult = $basketObj->checkEditVariants();
                }

                if (
                    $checkEditVariants &&
                    isset($checkResult) &&
                    is_array($checkResult)
                ) {
                    $errorOut = '';
                    $errorRowArray = [];

                    foreach ($checkResult as $uid => $errorArray) {
                        $basketObj->removeEditVariants($checkResult);
                        $errorRow = $errorArray['rec'];
                        $errorRowArray[] = $errorRow;
                        $message = $languageObj->getLabel('error_edit_variant_range');
                        $messageArr = explode('|', $message);

                        if (isset($errorArray['error']) && is_array($errorArray['error'])) {
                            foreach ($errorArray['error'] as $field => $fieldErrorMessage) {
                                $errorMessage = $messageArr[0] . $errorRow[$field] . $messageArr[1] . $errorRow['title'] . $messageArr[2];
                                $errorOut .= $errorMessage . '<br>';
                                $errorOut .= $fieldErrorMessage . '<br>';
                            }
                        }
                    }
                    $paymentHTML .= $errorOut;
                }
                $content .= $paymentHTML;
            }

            if (
                $orderUid &&
                $paymentHTML != '' &&
                $paymentScript // Do not save a redundant payment HTML if there is no payment script at all
            ) {
                $basketExt = $basketApi->getBasketExt();

                $giftServiceArticleArray = [];
                if (isset($basketExt) && is_array($basketExt)) {
                    foreach ($basketExt as $tmpUid => $tmpSubArr) {
                        if (is_array($tmpSubArr)) {
                            foreach ($tmpSubArr as $tmpKey => $tmpSubSubArr) {
                                if (
                                    substr($tmpKey, -1) == '.' &&
                                    isset($tmpSubSubArr['additional']) &&
                                    is_array($tmpSubSubArr['additional'])
                                ) {
                                    $variant = substr($tmpKey, 0, -1);
                                    $row = $basketObj->get($tmpUid, $variant);
                                    if ($tmpSubSubArr['additional']['giftservice'] == 1) {
                                        $giftServiceArticleArray[] = $row['title'];
                                    }
                                }
                            }
                        }
                    }
                }

                $orderObj = $tablesObj->get('sys_products_orders');
                $orderObj->putData(
                    $orderUid,
                    $orderArray,
                    $basketObj->getItemArray(),
                    $paymentHTML,
                    0,
                    $basketExtra,
                    $feUserRecord,
                    $basketObj->getCalculatedArray(),
                    $infoObj,
                    $cardObj->getUid(),
                    $accountObj->getUid(),
                    $infoObj,
                    $giftServiceArticleArray,
                    $basketObj->recs['tt_products']['vouchercode'] ?? '',
                    0,
                    0,
                    false
                );
            }
        } elseif (
            $theCode != 'OVERVIEW' &&
            (
                $currentPaymentActivity != 'finalize' ||
                $finalize
            )
        ) {	// If not all required info-fields are filled in, this is shown instead:
            $infoArray['billing']['error'] = 1;
            $subpart = 'BASKET_REQUIRED_INFO_MISSING';
            $requiredOut = \tx_ttproducts_api::getErrorOut(
                $theCode,
                $templateCode,
                $subpartmarkerObj->spMarker(
                    '###' . $subpart . $config['templateSuffix'] . '###'
                ),
                $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                $errorCode
            );

            if (!$errorCode) {
                $content .=
                $markerObj->replaceGlobalMarkers($requiredOut);
            }

            $label = $this->getErrorLabel(
                $languageObj,
                $accountObj,
                $cardObj,
                $pidagb,
                $infoArray,
                $checkRequired,
                $checkAllowed,
                $cardRequired,
                $accountRequired,
                $paymentErrorMsg
            );
            $markerArray['###ERROR_DETAILS###'] = $label;
            $markerArray['###FORM_NAME###'] = 'ErrorForm';
            $finalize = false;
        }

        if (strpos($templateCode, '###ERROR_DETAILS###') !== false) {
            $tempContent =
            $templateService->getSubpart(
                $templateCode,
                $subpartmarkerObj->spMarker(
                    '###' . $basket_tmpl . $config['templateSuffix'] . '###'
                )
            );
            if (strpos($tempContent, '###ERROR_DETAILS###') !== false) {
                $errorMessage = ''; // the error message is part of the HTML template
            }
        }

        return $content;
    } // getContent

    public function processActivities(
        &$errorCode,
        &$errorMessage,
        array &$infoArray,
        $activityApi,
        \tx_ttproducts_basket $basketObj,
        array $basketExtra,
        array $basketRecs,
        array $basketExt,
        array $codes,
        array $addressArray
    ) {
        $theCode = null;
        $templateCode = null;
        $templateFilename = null;
        $activityArray = $activityApi->getFinalActivityArray();
        $activityVarsArray = $activityApi->getActivityVarsArray();
        $codeActivityArray = $activityApi->getCodeActivityArray();
        $subActivity = $activityApi->getSubActivity();
        $calculatedArray = $basketObj->getCalculatedSums();
        $itemArray = $basketObj->getItemArray();

        $empty = '';
        $content = '';
        $isPayment = false;
        $checkRequired = '';
        $cardRequired = '';
        $accountRequired = '';
        $paymentErrorMsg = '';
        $pidagb = '';
        $cardObj = null;
        $cardRow = [];
        $accountObj = null;

        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $request = $parameterApi->getRequest();
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $basketViewObj = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        $infoObj = GeneralUtility::makeInstance('tx_ttproducts_info');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $cObj = ControlApi::getCObj();
        $gateway = GeneralUtility::makeInstance(PaymentGatewayApi::class);
        $conf = $cnf->getConf();
        $config = $cnf->getConfig();
        $feUserRecord = CustomerApi::getFeUserRecord();
        $gatewayResult = false;
        $infoViewObj->init(
            $infoObj
        );

        if (
            count($codes) < 2 &&
            $codes[0] != 'OVERVIEW'
        ) { // no initialization here if it is only the OVERVIEW. Then this must not be initialized yet.
            $gateway->init(
                $request,
                $languageObj,
                $conf,
                $basketExtra
            );

            if (
                isset($basketExtra['payment.']['handleLib']) &&
                isset($basketExtra['payment.']['handleLib.']) &&
                $basketExtra['payment.']['handleLib'] == 'transactor'
            ) {
                $gatewayResult = $gateway->readActionParameters();
            }

            $gateway->readActionParameters();
        }
        $infoViewObj->init(
            $infoObj
        );

        $markerArray = [];
        $checkAllowed = false;
        $checkBasket = false;
        $basketEmpty = $basketObj->isEmpty();
        $checkEditVariants = false;
        $orderUid = false;
        $orderNumber = '';
        $gatewayDatacollection = '';

        $orderArray = \tx_ttproducts_control_basket::getStoredOrder();
        $productRowArray = []; // Todo: make this a parameter

        $markerArray['###ERROR_DETAILS###'] = '';

        $pidTypeArray = ['PIDthanks', 'PIDfinalize', 'PIDpayment', 'PIDbasket', 'PIDinfo'];
        $pidArray = [];
        foreach ($pidTypeArray as $pidType) {
            if (
                $conf[$pidType] &&
                MathUtility::canBeInterpretedAsInteger($conf[$pidType])
            ) {
                $pidArray[$pidType] = $conf[$pidType];
            } elseif ($pidType != 'PIDthanks') {
                $pidArray[$pidType] = $conf['PIDbasket'];
            }
        }

        $mainMarkerArray = [];
        $mainSubpartArray = [];
        $mainWrappedSubpartArray = [];

        $finalize = false; // no finalization must be called.
        $finalVerify = false;
        $systemLoginUser =
        CustomerApi::isSystemLoginUser(
            $conf
        );
        if (
            !empty($activityArray['products_info']) ||
            !empty($activityArray['products_payment']) ||
            !empty($activityArray['products_customized_payment']) ||
            !empty($activityArray['products_verify']) ||
            !empty($activityArray['products_finalize'])
        ) {
            $cardViewObj = $tablesObj->get('sys_products_cards', true);
            if (is_object($cardViewObj)) {
                $cardObj = $cardViewObj->getModelObj();
                $cardUid = $cardObj->getUid();
                $cardRow = $cardObj->getRow($cardUid);
                $cardViewObj->getMarkerArray(
                    $cardRow,
                    $mainMarkerArray,
                    $cardObj->getAllowedArray(),
                    $cardObj->getTablename()
                );
            }

            $isNewUser = $infoObj->isNewUser('billing');

            // get bank account info
            $accountViewObj = $tablesObj->get('sys_products_accounts', true);
            if (is_object($accountViewObj)) {
                $accountObj = $accountViewObj->getModelObj();
                $accountViewObj->getMarkerArray(
                    $accountObj->acArray,
                    $mainMarkerArray,
                    $accountObj->getIsAllowed()
                );
            }
        }

        foreach ($activityArray as $activity => $value) {
            $theCode = 'BASKET';
            $basket_tmpl = '';

            if ($value) {
                $currentPaymentActivity = array_search($activity, $activityVarsArray);
                $activityConf = $cnf->getBasketConf('activity', $currentPaymentActivity);
                $checkFields = '';
                if (isset($activityConf['check'])) {
                    $checkFields = $activityConf['check'];
                }

                if (
                    !empty($subActivity) &&
                    isset($activityConf['sub.']) &&
                    isset($activityConf['sub.'][$subActivity . '.']) &&
                    isset($activityConf['sub.'][$subActivity . '.']['check'])
                ) {
                    $checkFields = $activityConf['sub.'][$subActivity . '.']['check'];
                }

                if (!empty($checkFields)) {
                    $checkArray = GeneralUtility::trimExplode(',', $checkFields);

                    foreach ($checkArray as $checkType) {
                        switch ($checkType) {
                            case 'account':
                                $isNewUser = $infoObj->isNewUser('billing');
                                if (
                                    PaymentShippingHandling::useAccount(
                                        $basketExtra,
                                        $isNewUser
                                    )
                                ) {
                                    $accountRequired = $accountObj->checkRequired();
                                }
                                break;
                            case 'address':
                                $checkRequired =
                                $infoObj->checkRequired(
                                    'billing',
                                    $basketExtra,
                                    $systemLoginUser
                                );

                                if (!$checkRequired) {
                                    $checkRequired =
                                    $infoObj->checkRequired(
                                        'delivery',
                                        $basketExtra,
                                        $systemLoginUser
                                    );
                                }
                                $checkAllowed = $infoObj->checkAllowed($basketExtra);
                                break;
                            case 'agb':
                                $pidagb = intval($conf['PIDagb']);
                                break;
                            case 'basket':
                                $checkBasket = true;
                                break;
                            case 'edit_variant':
                                $checkEditVariants = true;
                                break;
                            case 'card':
                                if (PaymentShippingHandling::useCreditcard($basketExtra)) {
                                    $cardRequired = $cardObj->checkRequired();
                                }
                                break;
                        }
                    }
                }

                // perform action
                switch ($activity) {
                    case 'products_clear_basket':
                        // Empties the shopping basket!
                        $basketObj->clearBasket(true);
                        $basketEmpty = $basketObj->isEmpty();
                        $calculatedArray = [];
                        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
                        $calculObj->clear($calculatedArray);
                        $calculObj->setCalculatedArray($calculatedArray);
                        break;
                    case 'products_basket':
                        if (
                            count($activityArray) == 1 ||

                            count($activityArray) == 2 &&
                            !empty($activityArray['products_overview'])
                        ) {
                            $basket_tmpl = 'BASKET_TEMPLATE';
                        }
                        break;
                    case 'products_overview':
                        $basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';

                        if ($codeActivityArray[$activity]) {
                            $theCode = 'OVERVIEW';
                        }
                        break;
                    case 'products_info':
                        $suffix = '';
                        if ($subActivity) {
                            $suffix = '_' . $subActivity;
                        }
                        $basket_tmpl = 'BASKET_INFO_TEMPLATE' . $suffix;

                        if (
                            !empty($codeActivityArray[$activity]) ||
                            empty($activityArray['products_basket'])
                        ) {
                            $theCode = 'INFO';
                        }
                        $this->getOrderData($orderUid, $orderNumber, $orderArray);
                        $returnPid = !empty($pidArray['PIDinfo']) ? $pidArray['PIDinfo'] : $pidArray['PIDfinalize'];
                        $cancelPid = !empty($pidArray['PIDbasket']) ? $pidArray['PIDbasket'] : '';
                        $addParams = [];

                        $returnUrl =
                            FrontendUtility::getTypoLink_URL(
                                $cObj,
                                $returnPid,
                                $addParams
                            );
                        $cancelUrl =
                            FrontendUtility::getTypoLink_URL(
                                $cObj,
                                $cancelPid,
                                $addParams
                            );

                        if (
                            class_exists(Address::class)
                        ) {
                            $addressModel = GeneralUtility::makeInstance(Address::class);

                            $gatewayDatacollection =
                            $gateway->doDataCollectionPayment(
                                $errorMessage,
                                $addressModel,
                                $languageObj,
                                $conf,
                                $itemArray,
                                $orderUid,
                                $orderNumber,
                                $returnUrl,
                                $cancelUrl
                            );

                            if ($errorMessage != '') {
                                return '';
                            }

                            $infoArray['billing']['name'] = $addressModel->getName();
                            $infoArray['billing']['email'] = $addressModel->getEmail();
                            $infoArray['billing']['zip'] = $addressModel->getZip();
                            $infoArray['billing']['country'] = $addressModel->getCountry();
                            $infoArray['billing']['address'] = $addressModel->getStreet();
                            $infoArray['billing']['city'] = $addressModel->getCity();
                            $infoObj->setInfoArray($infoArray);
                        }
                        break;
                        case 'products_payment':
                            $isPayment = true;
                            $this->getOrderData($orderUid, $orderNumber, $orderArray);

                            if (
                                $conf['paymentActivity'] == 'payment' ||
                                $conf['paymentActivity'] == 'verify'
                            ) {
                                $handleLib =
                                PaymentShippingHandling::getHandleLib(
                                    'form',
                                    $basketExtra
                                );

                                if (
                                    is_string($handleLib) &&
                                    strpos($handleLib, 'transactor') !== false
                                ) {
                                    $addQueryString = [];
                                    $excludeList = '';
                                    $linkParams =
                                    $this->urlObj->getLinkParams(
                                        $excludeList,
                                        $addQueryString,
                                        true
                                    );

                                    $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';

                                    if (
                                        class_exists($callingClassName) &&
                                        method_exists($callingClassName, 'checkRequired')
                                    ) {
                                        $parameters = [
                                            $languageObj,
                                            $cObj,
                                            $conf,
                                        ];
                                        call_user_func_array(
                                            $callingClassName . '::init',
                                            $parameters
                                        );
                                        $parameters = [
                                            $handleLib,
                                            $basketExtra['payment.']['handleLib.'] ?? [],
                                            TT_PRODUCTS_EXT,
                                            $orderUid,
                                        ];
                                        $referenceId = call_user_func_array(
                                            $callingClassName . '::getReferenceUid',
                                            $parameters
                                        );

                                        $parameters = [
                                            $referenceId,
                                            $basketExtra['payment.']['handleLib'] ?? '',
                                            $basketExtra['payment.']['handleLib.'] ?? [],
                                            TT_PRODUCTS_EXT,
                                            $calculatedArray,
                                            $conf['paymentActivity'] ?? '',
                                            $pidArray,
                                            $linkParams,
                                            $orderArray['tracking_code'] ?? '',
                                            $orderUid,
                                            $orderNumber,
                                            $conf['orderEmail_to'] ?? '',
                                            $cardRow,
                                        ];

                                        $paymentErrorMsg = call_user_func_array(
                                            $callingClassName . '::checkRequired',
                                            $parameters
                                        );
                                    }
                                }
                            }

                            if (
                                !empty($codeActivityArray[$activity]) ||
                                empty($activityArray['products_basket'])
                            ) {
                                $theCode = 'PAYMENT';
                            }
                            $basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
                            break;
                            // a special step after payment and before finalization needed for some payment methods
                            case 'products_customized_payment': // deprecated
                            case 'products_verify':
                                $isPayment = true;

                                if (
                                    !$basketEmpty &&
                                    (
                                        $conf['paymentActivity'] == 'verify' ||
                                        $conf['paymentActivity'] == 'customized' // deprecated
                                    )
                                ) {
                                    $this->getOrderData($orderUid, $orderNumber, $orderArray);
                                    $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
                                    $this->processPayment(
                                        $orderUid,
                                        $orderNumber,
                                        $cardRow,
                                        $pidArray,
                                        $currentPaymentActivity,
                                        $calculatedArray,
                                        $basketExtra,
                                        $basketRecs,
                                        $orderArray,
                                        $productRowArray,
                                        $infoArray,
                                        $mainMarkerArray,
                                        $finalize,
                                        $finalVerify,
                                        $paymentScript,
                                        $errorCode,
                                        $errorMessage
                                    );

                                    $paymentErrorMsg = $errorMessage;

                                    if ($errorMessage != '') {
                                        $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
                                    }
                                    if (!$finalize) {
                                        $basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
                                    }
                                } else {
                                    $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
                                }
                                break;
                            case 'products_finalize':
                                $isPayment = true;

                                $handleLib = PaymentShippingHandling::getHandleLib('request', $basketExtra);
                                if ($handleLib == '') {
                                    $handleLib = PaymentShippingHandling::getHandleLib('form', $basketExtra);
                                }
                                $this->getOrderData($orderUid, $orderNumber, $orderArray);

                                if (
                                    !$basketEmpty &&
                                    $handleLib != ''
                                ) {
                                    $rc = $this->processPayment(
                                        $orderUid,
                                        $orderNumber,
                                        $cardRow,
                                        $pidArray,
                                        $currentPaymentActivity,
                                        $calculatedArray,
                                        $basketExtra,
                                        $basketRecs,
                                        $orderArray,
                                        $productRowArray,
                                        $infoArray,
                                        $mainMarkerArray,
                                        $finalize,
                                        $finalVerify,
                                        $paymentScript,
                                        $errorCode,
                                        $errorMessage
                                    );
                                    $paymentErrorMsg = $errorMessage;

                                    if ($finalize == false) {
                                        $label = $paymentErrorMsg;
                                        $markerArray['###ERROR_DETAILS###'] = $label;
                                        $basket_tmpl = 'BASKET_TEMPLATE'; // step back to the basket page
                                    } else {
                                        $content = ''; // do not show the content of payment again
                                    }
                                } else {
                                    $finalize = true;
                                }

                                if (
                                    (
                                        !empty($codeActivityArray[$activity]) ||
                                        empty($activityArray['products_basket'])
                                    ) &&
                                    $finalize
                                ) {
                                    $theCode = 'FINALIZE';
                                }
                                break;
                                default:
                                    // nothing yet
                                    $activity = 'unknown';
                                    break;
                } // switch
            }	// if ($value)

            if (
                !empty($gatewayDatacollection)
            ) {
                $content .= $gatewayDatacollection;
            } elseif ($value) {
                $templateFilename = '';

                $templateCode = $templateObj->get(
                    $theCode,
                    $templateFilename,
                    $errorCode
                );

                if ($errorCode) {
                    return '';
                }

                $newContent = $this->getContent(
                    $templateCode,
                    $templateFilename,
                    $mainMarkerArray,
                    $calculatedArray,
                    $basketExtra,
                    $basketRecs,
                    $orderUid,
                    $orderNumber,
                    $orderArray,
                    $productRowArray,
                    $theCode,
                    $basket_tmpl,
                    $isPayment,
                    $activityArray,
                    $currentPaymentActivity,
                    $pidArray,
                    $infoArray,
                    $checkBasket,
                    $basketEmpty,
                    $checkRequired,
                    $checkAllowed,
                    $cardRequired,
                    $accountRequired,
                    //                     $giftRequired,
                    $checkEditVariants,
                    $paymentErrorMsg,
                    $pidagb,
                    $cardObj,
                    $cardRow,
                    $accountObj,
                    $infoObj,
                    $markerArray,
                    $errorCode,
                    $errorMessage,
                    $finalize,
                    $finalVerify
                );

                $addQueryString = [];
                $overwriteMarkerArray = [];

                $piVars = $parameterApi->getPiVars();
                if (is_array($piVars)) {
                    $backPID = $piVars['backPID'] ?? '';
                }
                $overwriteMarkerArray =
                $this->urlObj->addURLMarkers(
                    $backPID,
                    [],
                    $theCode,
                    $addQueryString
                );
                $markerArray = array_merge($markerArray, $overwriteMarkerArray);
                $content = $templateService->substituteMarkerArray($content . $newContent, $markerArray);
            }
        } // foreach ($activityArray as $activity=>$value)

        // finalization at the end so that after every activity this can be called

        if ($finalize && !$basketEmpty) {
            if ($orderUid) {
                $checkRequired = $infoObj->checkRequired('billing', $basketExtra, $systemLoginUser);

                if (!$checkRequired) {
                    $checkRequired = $infoObj->checkRequired('delivery', $basketExtra, $systemLoginUser);
                }

                $checkAllowed = $infoObj->checkAllowed($basketExtra);

                if ($checkRequired == '' && $checkAllowed == '') {
                    $this->getOrderData($orderUid, $orderNumber, $orderArray);

                    if (
                        !$basketEmpty &&
                        trim($conf['paymentActivity']) == 'finalize'
                    ) {
                        $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
                        $this->processPayment(
                            $orderUid,
                            $orderNumber,
                            $cardRow,
                            $pidArray,
                            'finalize',
                            $calculatedArray,
                            $basketExtra,
                            $basketRecs,
                            $orderArray,
                            $productRowArray,
                            $infoArray,
                            $mainMarkerArray,
                            $finalize,
                            $finalVerify,
                            $paymentScript,
                            $errorCode,
                            $errorMessage
                        );
                        if ($errorMessage != '') {
                            $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
                        }
                    } else {
                        $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
                    }

                    // order finalization
                    $activityFinalize = GeneralUtility::makeInstance('tx_ttproducts_activity_finalize');
                    if (intval($conf['alwaysInStock'])) {
                        $alwaysInStock = 1;
                    } else {
                        $alwaysInStock = 0;
                    }

                    $usedCreditpoints = 0;
                    if (isset($_REQUEST['recs'])) {
                        $usedCreditpoints = \tx_ttproducts_creditpoints_div::getUsedCreditpoints($feUserRecord, $_REQUEST['recs']);
                    }
                    if (
                        $theCode != 'FINALIZE' &&
                        $activityArray['products_basket'] == false
                    ) {
                        $theCode = 'FINALIZE';
                        $templateFilename = '';

                        $templateCode = $templateObj->get(
                            $theCode,
                            $templateFilename,
                            $errorCode
                        );
                    }

                    $contentTmp = $activityFinalize->doProcessing(
                        $templateCode,
                        $mainMarkerArray,
                        $mainSubpartArray,
                        $mainWrappedSubpartArray,
                        // 					$this->funcTablename, neu
                        $orderUid,
                        $orderArray,
                        $feUserRecord,
                        $productRowArray,
                        $alwaysInStock,
                        $conf['useArticles'] ?? 3,
                        $addressArray,
                        $finalVerify,
                        $basketExt,
                        $usedCreditpoints,
                        $errorCode,
                        $errorMessage
                    );

                    if (
                        isset($conf['PIDthanks']) &&
                        ($conf['PIDthanks'] == $GLOBALS['TSFE']->id)
                    ) {
                        $tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
                        $contentTmpThanks = $basketViewObj->getView(
                            $errorCode,
                            $templateCode,
                            $theCode,
                            $infoViewObj,
                            $feUserRecord,
                            false,
                            false,
                            $calculatedArray,
                            true,
                            $tmpl,
                            $mainMarkerArray,
                            $mainSubpartArray,
                            $mainWrappedSubpartArray,
                            $templateFilename,
                            $basketObj->getItemArray(),
                            $notOverwritePriceIfSet = true,
                            ['0' => $orderArray],
                            $productRowArray,
                            $basketExtra,
                            $basketRecs
                        );
                        if ($contentTmpThanks != '') {
                            $contentTmp = $contentTmpThanks;
                        }
                    }

                    if (!empty($activityArray['products_payment'])) {	// forget the payment output from before if it comes to finalize
                        $content = '';
                    }
                    $content .= $contentTmp;
                    $contentNoSave = $basketViewObj->getView(
                        $errorCode,
                        $templateCode,
                        $theCode,
                        $infoViewObj,
                        $feUserRecord,
                        false,
                        false,
                        $calculatedArray,
                        true,
                        'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
                        $mainMarkerArray,
                        $mainSubpartArray,
                        $mainWrappedSubpartArray,
                        $templateFilename,
                        $basketObj->getItemArray(),
                        $notOverwritePriceIfSet = true,
                        ['0' => $orderArray],
                        $productRowArray,
                        $basketExtra,
                        $basketRecs
                    );
                    $content .= $contentNoSave;

                    // Empties the shopping basket!
                    $basketObj->clearBasket();
                } else {	// If not all required info-fields are filled in, this is shown instead:
                    $subpart = 'BASKET_REQUIRED_INFO_MISSING';
                    $requiredOut = \tx_ttproducts_api::getErrorOut(
                        $theCode,
                        $templateCode,
                        $subpartmarkerObj->spMarker('###' . $subpart . $config['templateSuffix'] . '###'),
                        $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                        $errorCode
                    );

                    if (!$requiredOut) {
                        return '';
                    }

                    $label = $this->getErrorLabel(
                        $languageObj,
                        $accountObj,
                        $cardObj,
                        $pidagb,
                        $infoArray,
                        $checkRequired,
                        $checkAllowed,
                        $cardRequired,
                        $accountRequired,
                        $paymentErrorMsg
                    );

                    $mainMarkerArray['###ERROR_DETAILS###'] = $label;
                    $urlMarkerArray = $this->urlObj->addURLMarkers(0, [], $theCode);
                    $markerArray = array_merge($mainMarkerArray, $urlMarkerArray);

                    $content .= $requiredOut;
                    $content = $templateService->substituteMarkerArray(
                        $content,
                        $markerArray
                    );
                }
            } else {
                $subpart = 'ERROR_INTERNAL';
                $errorCode[0] = 'error_order_number';
                $content = \tx_ttproducts_api::getErrorOut(
                    $theCode,
                    $templateCode,
                    $subpartmarkerObj->spMarker(
                        '###' . $subpart . $config['templateSuffix'] . '###'
                    ),
                    $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                    $errorCode
                );
            }
        }

        $content = $markerObj->replaceGlobalMarkers(
            $content,
            $mainMarkerArray,
            $mainSubpartArray,
            $mainWrappedSubpartArray
        );

        return $content;
    } // processActivities

    /**
     * Do all the things to be done for this activity
     * former functions products_basket and basketViewObj::printView
     * Takes care of basket, address info, confirmation and gate to payment
     * Also the 'products_...' script parameters are used here.
     *
     * @return	string	text to display
     */
    public function doProcessing(
        \tx_ttproducts_basket $basketObj,
        array $codes,
        $basketExtra,
        array $basketRecs,
        $basketExt,
        $addressArray,
        &$errorCode,
        &$errorMessage
    ) {
        $content = '';
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $infoObj = GeneralUtility::makeInstance('tx_ttproducts_info');
        $basketViewObj = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $basketViewObj->init(
            $this->useArticles,
            $this->urlArray
        );
        $activityApi = GeneralUtility::makeInstance(ActivityApi::class);
        $conf = $cnf->getConf();
        $infoArray = $infoObj->getInfoArray();
        $activityArray = $activityApi->getActivityArray();
        \tx_ttproducts_control_basket::uncheckAgb(
            $infoArray,
            $activityArray['products_payment'] ?? 0
        );

        if (count($activityApi->getFinalActivityArray())) {
            $content = $this->processActivities(
                $errorCode,
                $errorMessage,
                $infoArray,
                $activityApi,
                $basketObj,
                $basketExtra,
                $basketRecs,
                $basketExt,
                $codes,
                $addressArray
                );
        }

        if (
            isset($conf['enableReturnKey']) &&
            $conf['enableReturnKey'] == '0' &&
            empty($errorCode)
        ) {
            $languageObj = GeneralUtility::makeInstance(Localization::class);
            $javaScriptObj = GeneralUtility::makeInstance('tx_ttproducts_javascript');
            $javaScriptObj->set($languageObj, 'no_return');
        }

        return $content;
    }
}
