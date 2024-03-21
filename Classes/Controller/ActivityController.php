<?php

namespace JambageCom\TtProducts\Controller;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;

use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\HtmlUtility;

use JambageCom\TtProducts\Api\ActivityApi;
use JambageCom\TtProducts\Api\BasketApi;
use JambageCom\TtProducts\Api\ControlApi;
use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PaymentShippingHandling;


class ActivityController implements SingletonInterface
{
    public $pibase; // reference to object of pibase
    public $pibaseClass;
    public $cObj;
    public $conf;
    public $config;
    public $activityArray;		// activities for the CODEs
    public $funcTablename;
    public $urlObj; // url functions
    public $urlArray; // overridden url destinations
    public $useArticles;
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

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

    public function init($pibaseClass, $funcTablename, $useArticles): void
    {
        $this->pibaseClass = $pibaseClass;
        $this->pibase = GeneralUtility::makeInstance('' . $pibaseClass);
        $this->cObj = $this->pibase->getContentObjectRenderer();
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $this->conf = $cnf->conf;
        $this->config = $cnf->config;
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $this->funcTablename = $funcTablename;
        $this->useArticles = $useArticles;

        $this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
        // This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
        $this->urlArray = [];
        if (!empty($basketObj->basketExtra['payment.']['handleURL'])) {
            $this->urlArray['form_url_thanks'] = $basketObj->basketExtra['payment.']['handleURL'];
        }
        if (!empty($basketObj->basketExtra['payment.']['handleTarget'])) {	// Alternative target
            $this->urlArray['form_url_target'] = $basketObj->basketExtra['payment.']['handleTarget'];
        }
        $this->urlObj->setUrlArray($this->urlArray);
    } // init

    protected function getOrderUid(&$orderArray)
    {
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $orderUid = 0;
        $result = false;

        if (isset($orderArray['uid'])) {
            $orderUid = $orderArray['uid'];
            $result = $orderUid;
        }

        if (!$orderUid && count($basketObj->itemArray)) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $orderUid = $orderObj->getUid();
            $orderArray = $orderObj->getCurrentArray();
            if (!$orderUid) {
                $orderUid = $orderObj->getBlankUid($orderArray);
                $orderObj->setUid($orderUid);
            }
            $result = $orderUid;
        }

        return $result;
    }

    protected function getOrdernumber($orderUid)
    {
        $result = '';

        if ($orderUid) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $result = $orderObj->getNumber($orderUid);
        }

        return $result;
    }

    /**
     * returns the activities in the order in which they have to be processed.
     */
    public function transformActivities($activities)
    {
        $retActivities = [];
        $codeActivities = [];
        $codeActivityArray = [
            '1' => 'products_overview',
                'products_basket',
                'products_info',
                'products_payment',
                'products_customized_payment',
                'products_verify',
                'products_finalize',
        ];

        $activityArray = [
            '1' => 'products_redeem_gift',
            'products_clear_basket',
        ];

        /*		if ($activities['products_basket']) {
                    $basketActivityArray = array(
                        '1' =>
                            'products_info',
                            'products_payment',
                            'products_finalize'
                        );
                    // if 'products_basket' has been set, then the user should always return to the basket page
                    foreach ($basketActivityArray as $k => $activity) {
                        if ($activities[$activity]) {
                            unset($activities[$activity]);
                        }
                    }
                }*/

        if (is_array($activities)) {
            foreach ($activities as $activity => $value) {
                if ($value && in_array($activity, $codeActivityArray)) {
                    $codeActivities[$activity] = true;
                }
            }
        }

        if (!empty($codeActivities['products_info'])) {
            if (!empty($codeActivities['products_payment'])) {
                $codeActivities['products_payment'] = false;
            }
        }

        if (
            !empty($codeActivities['products_basket']) &&
            count($codeActivities) > 1
        ) {
            if (
                count($codeActivities) > 2 ||
                empty($codeActivities['products_overview'])
            ) {
                $codeActivities['products_basket'] = false;
            }
        }

        $sortedCodeActivities = [];
        foreach ($codeActivityArray as $activity) { // You must keep the order of activities.
            if (isset($codeActivities[$activity])) {
                $sortedCodeActivities[$activity] = $codeActivities[$activity];
            }
        }
        $codeActivities = $sortedCodeActivities;

        if (is_array($activities)) {
            foreach ($activityArray as $k => $activity) {
                if (!empty($activities[$activity])) {
                    $retActivities[$activity] = true;
                }
            }
            $retActivities = array_merge($retActivities, $codeActivities);
        }

        return $retActivities;
    }

    protected function getTransactorConf($handleLib)
    {
        $transactorConf = '';

        $transactorConf = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get($handleLib);

        return $transactorConf;
    }

    protected function processPayment(
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
        &$bFinalize,
        &$bFinalVerify,
        &$paymentScript,
        &$errorCode,
        &$errorMessage
    ) {
        $content = '';
        $paymentScript = false;
        $handleLib = '';
        $localTemplateCode = '';

        if ($orderUid) {
            $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
            $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
            $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
            $handleScript = '';
            if (isset($basketExtra['payment.']['handleScript'])) {
                $sanitizer = GeneralUtility::makeInstance(FilePathSanitizer::class);
                $handleScript = $sanitizer->sanitize($basketExtra['payment.']['handleScript']);
            }

            $handleLib = $basketExtra['payment.']['handleLib'] ?? '';

            $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

            if ($handleScript) {
                $paymentScript = true;
                $content = PaymentShippingHandling::includeHandleScript(
                    $handleScript,
                    $basketExtra['payment.']['handleScript.'] ?? '',
                    $this->conf['paymentActivity'] ?? '',
                    $bFinalize,
                    $this->pibase,
                    $infoViewObj
                );
            } elseif (
                strpos($handleLib, 'paymentlib') === false &&
                ExtensionManagementUtility::isLoaded($handleLib)
            ) {
                $transactorConf = $this->getTransactorConf($handleLib);
                $useNewTransactor = false;

                if (
                    !empty($transactorConf)
                ) {
                    if (
                        isset($transactorConf['compatibility']) &&
                        $transactorConf['compatibility'] == '0'
                    ) {
                        $useNewTransactor = true;
                    }
                }

                if ($useNewTransactor) {
                    $paymentScript = true;
                    $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';
                    call_user_func($callingClassName . '::test');
                    $markerArray = [];

                    if (
                        class_exists($callingClassName) &&
                        method_exists($callingClassName, 'init') &&
                        method_exists($callingClassName, 'includeHandleLib')
                    ) {
                        $languageObj = GeneralUtility::makeInstance(Localization::class);
                        call_user_func($callingClassName . '::init', $languageObj, $this->cObj, $this->conf);
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
                            $handleLib,
                            $basketExtra['payment.']['handleLib.'] ?? [],
                            TT_PRODUCTS_EXT,
                            $basketObj->getItemArray(),
                            $calculatedArray,
                            $basketObj->recs['delivery']['note'] ?? '',
                            $this->conf['paymentActivity'] ?? '',
                            $currentPaymentActivity,
                            $infoViewObj->infoArray,
                            $pidArray,
                            $linkParams,
                            $orderArray['tracking_code'] ?? '',
                            $orderUid,
                            $orderNumber,
                            $this->conf['orderEmail_to'] ?? '',
                            $cardRow,
                            &$bFinalize,
                            &$bFinalVerify,
                            &$markerArray,
                            &$templateFilename,
                            &$localTemplateCode,
                            &$errorMessage,
                        ];
                        $content = call_user_func_array(
                            $callingClassName . '::includeHandleLib',
                            $parameters
                        );
                    }
                } else {
                    $paymentScript = true;
                    // Payment Transactor or any alternative extension besides paymentlib
                    // Get references to the concerning baskets
                    $languageObj = GeneralUtility::makeInstance(Localization::class);
                    $addQueryString = [];
                    $excludeList = '';
                    $linkParams =
                        $this->urlObj->getLinkParams(
                            $excludeList,
                            $addQueryString,
                            true,
                            false
                        );

                    $markerArray = [];
                    tx_transactor_api::init(
                        $languageObj,
                        $this->cObj,
                        $this->conf
                    );
                    $content = tx_transactor_api::includeHandleLib(
                        $handleLib,
                        $basketExtra['payment.']['handleLib.'] ?? [],
                        TT_PRODUCTS_EXT,
                        $basketObj->getItemArray(),
                        $calculatedArray,
                        $basketObj->recs['delivery']['note'],
                        $this->conf['paymentActivity'],
                        $currentPaymentActivity,
                        $infoViewObj->infoArray,
                        $pidArray,
                        $linkParams,
                        $orderArray['tracking_code'],
                        $orderUid,
                        $cardRow,
                        $bFinalize,
                        $bFinalVerify,
                        $markerArray,
                        $templateFilename,
                        $localTemplateCode,
                        $errorMessage
                    );
                }

                if (
                    !$errorMessage &&
                    $content == '' &&
                    !$bFinalize &&
                    $localTemplateCode != ''
                ) {
                    $content = $basketView->getView(
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
                        $templateFilename,
                        $basketObj->getItemArray(),
                        $notOverwritePriceIfSet = true,
                        ['0' => $orderArray],
                        $productRowArray,
                        $basketExtra,
                        $basketRecs
                    );
                }
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
        $giftRequired,
        $paymentErrorMsg
    ) {
        $label = '';
        $languageKey = '';

        if ($checkRequired || $checkAllowed) {
            $check = ($checkRequired ?: $checkAllowed);
            $check = ($check ?: $giftRequired);
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
                $editPID = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_agency.']['editPID'];

                if (CompatibilityUtility::isLoggedIn() && $editPID) {
                    $cObj = ControlApi::getCObj();
                    $addParams = ['products_payment' => 1];
                    $addParams = $this->urlObj->getLinkParams('', $addParams, true);
                    // 					$agencyBackUrl =
                    // 						$this->pibase->pi_getPageLink($GLOBALS['TSFE']->id, '', $addParams);
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
                $label = $GLOBALS['TSFE']->sL('LLL:EXT:sr_feuser_register/Resources/Private/Language/locallang.xlf:' . $languageKey);
                $editPID = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];

                if (CompatibilityUtility::isLoggedIn() && $editPID) {
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
        } elseif ($pidagb && empty($_REQUEST['recs']['personinfo']['agb']) && !GeneralUtility::_GET('products_payment') && empty($infoArray['billing']['agb'])) {
            // so AGB has not been accepted
            $label = $languageObj->getLabel('accept_AGB');

            $addQueryString['agb'] = 0;
        } elseif ($cardRequired) {
            $label = '*' . $languageObj->getLabel($cardObj->getTablename() . '.' . $cardRequired) . '*';
        } elseif ($accountRequired) {
            $label = '*' . $languageObj->getLabel($accountObj->getTablename()) . ': ' . $languageObj->getLabel($accountObj->getTablename() . '.' . $accountRequired) . '*';
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
        array $mainMarkerArray,
        array $calculatedArray,
        $basketExtra,
        array $basketRecs,
        $orderUid,
        $orderNumber,
        $orderArray,
        $productRowArray,
        $theCode,
        $basket_tmpl,
        $bPayment,
        $activityArray,
        $currentPaymentActivity,
        $pidArray,
        $infoArray,
        $checkBasket,
        $bBasketEmpty,
        $checkRequired,
        $checkAllowed,
        $cardRequired,
        $accountRequired,
        $giftRequired,
        $checkEditVariants,
        $paymentErrorMsg,
        $pidagb,
        $cardObj,
        $cardRow,
        $accountObj,
        &$markerArray,
        &$errorCode,
        &$errorMessage,
        &$bFinalize,
        &$bFinalVerify
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $empty = '';
        $cObj = ControlApi::getCObj();
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $taxObj = GeneralUtility::makeInstance('tx_ttproducts_field_tax');
        $content = '';
        $hiddenFields = '';
        $paymentScript = false;

        if ($checkBasket && !$bBasketEmpty) {
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
                    $value < doubleval($basketConf['value'])
                ) {
                    $basket_tmpl = 'BASKET_TEMPLATE_MINPRICE_ERROR';
                    $bFinalize = false;
                }
            }
        }

        $basketMarkerArray = [];
        if ($checkBasket && $bBasketEmpty) {
            $contentEmpty = '';
            if (!empty($this->activityArray['products_overview'])) {
                $contentEmpty = $templateService->getSubpart(
                    $templateCode,
                    $subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY' . $this->config['templateSuffix'] . '###')
                );

                if (!$contentEmpty) {
                    $contentEmpty = $templateService->getSubpart(
                        $templateCode,
                        $subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY###')
                    );
                }
            } elseif (
                !empty($this->activityArray['products_basket']) ||
                !empty($this->activityArray['products_info']) ||
                !empty($this->activityArray['products_payment'])
            ) {
                $subpart = 'BASKET_TEMPLATE_EMPTY';
                $contentEmpty = tx_ttproducts_api::getErrorOut(
                    $theCode,
                    $templateCode,
                    $subpartmarkerObj->spMarker('###' . $subpart . $this->config['templateSuffix'] . '###'),
                    $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                    $errorCode
                );
            } elseif (!empty($this->activityArray['products_finalize'])) {
                // Todo: Neuabsenden einer bereits abgesendeten Bestellung. Der Warenkorb ist schon gelöscht.
                if (!$orderArray) {
                    $contentEmpty = $languageObj->getLabel('order_already_finalized');
                }
            }

            if ($contentEmpty != '') {
                $contentEmpty = $markerObj->replaceGlobalMarkers($contentEmpty);
                $bFinalize = false;
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
                $basketView->getMarkerArray(
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
            empty($giftRequired) &&
            (
                empty($pidagb) ||
                !empty($_REQUEST['recs']['personinfo']['agb']) ||
                ($bPayment && GeneralUtility::_GET('products_payment')) ||
                !empty($infoArray['billing']['agb'])
            )
        ) {
            if (
                $bPayment &&
                !$bBasketEmpty &&
                isset($this->conf['paymentActivity']) &&
                (
                    $this->conf['paymentActivity'] == 'payment' ||
                    $this->conf['paymentActivity'] == 'verify'
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
                        $bFinalize,
                        $bFinalVerify,
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
                !$bFinalize &&
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
                $paymentHTML = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $theCode,
                    $infoViewObj,
                    $this->activityArray['products_info'] ?? false,
                    false,
                    $calculatedArray,
                    true,
                    $basket_tmpl,
                    $mainMarkerArray,
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

                        if (
                            isset($errorArray['error']) &&
                            is_array($errorArray['error'])
                        ) {
                            foreach ($errorArray['error'] as $field => $fieldErrorMessage) {
                                $errorMessage =
                                    $messageArr[0] . $errorRow[$field] . $messageArr[1] .
                                    $errorRow['title'] . $messageArr[2];
                                $errorOut .= $errorMessage . '<br />';
                                $errorOut .= $fieldErrorMessage . '<br />';
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
                $paymentScript  // Do not save a redundant payment HTML if there is no payment script at all
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
                    $basketObj->getCalculatedArray(),
                    $basketObj->recs['tt_products']['giftcode'],
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
                $bFinalize
            )
        ) {	// If not all required info-fields are filled in, this is shown instead:
            $infoArray['billing']['error'] = 1;
            $subpart = 'BASKET_REQUIRED_INFO_MISSING';
            $requiredOut = tx_ttproducts_api::getErrorOut(
                $theCode,
                $templateCode,
                $subpartmarkerObj->spMarker('###' . $subpart . $this->config['templateSuffix'] . '###'),
                $subpartmarkerObj->spMarker('###' . $subpart . '###'),
                $errorCode
            );

            if (!$errorCode) {
                $content .=
                    $markerObj->replaceGlobalMarkers($requiredOut);
            }

            $label = '';
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
                $giftRequired,
                $paymentErrorMsg
            );
            $markerArray['###ERROR_DETAILS###'] = $label;
            $bFinalize = false;
        }

        if (strpos($templateCode, '###ERROR_DETAILS###') !== false) {
            $tempContent =
                $templateService->getSubpart(
                    $templateCode,
                    $subpartmarkerObj->spMarker(
                        '###' . $basket_tmpl . $this->config['templateSuffix'] . '###'
                    )
                );
            if (strpos($tempContent, '###ERROR_DETAILS###') !== false) {
                $errorMessage = ''; // the error message is part of the HTML template
            }
        }

        return $content;
    } // getContent

    public function processActivities(
        $activityArray,
        $activityVarsArray,
        $codeActivityArray,
        &$calculatedArray,
        $basketExtra,
        array $basketRecs,
        $basketExt,
        $addressArray,
        &$errorCode,
        &$errorMessage
    ) {
        $empty = '';
        $content = '';
        $bPayment = false;
        $checkRequired = '';
        $cardRequired = '';
        $accountRequired = '';
        $paymentErrorMsg = '';
        $pidagb = '';
        $cardObj = null;
        $cardRow = [];
        $accountObj = null;

        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
        $orderUid = false;
        $orderNumber = '';

        $markerArray = [];
        $checkAllowed = false;
        $checkBasket = false;
        $checkEditVariants = false;
        $giftRequired = false;
        $bBasketEmpty = $basketObj->isEmpty();
        $orderArray = \tx_ttproducts_control_basket::getStoredOrder();
        $productRowArray = []; // Todo: make this a parameter

        $markerArray['###ERROR_DETAILS###'] = '';
        $conf = $cnf->getConf();

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
        $bFinalize = false; // no finalization must be called.
        $bFinalVerify = false;

        if (
            !empty($activityArray['products_info']) ||
            !empty($activityArray['products_payment']) ||
            !empty($activityArray['products_customized_payment']) ||
            !empty($activityArray['products_verify']) ||
            !empty($activityArray['products_finalize'])
        ) {
            // get credit card info
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

                if (isset($activityConf['check'])) {
                    $checkArray = GeneralUtility::trimExplode(',', $activityConf['check']);

                    foreach ($checkArray as $checkType) {
                        switch ($checkType) {
                            case 'account':
                                if (PaymentShippingHandling::useAccount($basketExtra)) {
                                    $accountRequired = $accountObj->checkRequired();
                                }
                                break;
                            case 'address':
                                $checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);

                                if (!$checkRequired) {
                                    $checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
                                }
                                $checkAllowed = $infoViewObj->checkAllowed($basketExtra);
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
                            case 'gift':
                                $wrongGiftNumber = 0;
                                $giftRequired = tx_ttproducts_gifts_div::checkRequired($basketExt, $infoViewObj, $wrongGiftNumber);
                                if ($wrongGiftNumber) {
                                    tx_ttproducts_gifts_div::deleteGiftNumber($wrongGiftNumber);
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
                        $bBasketEmpty = $basketObj->isEmpty();
                        $calculatedArray = [];
                        $calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
                        $calculObj->setCalculatedArray($calculatedArray);
                        $calculObj->clear();
                        $calculatedArray = $calculObj->getCalculatedArray();
                        break;
                    case 'products_basket':
                        if (
                            count($activityArray) == 1 ||
                            count($activityArray) == 2 && !empty($activityArray['products_overview'])
                        ) {
                            $basket_tmpl = 'BASKET_TEMPLATE';
                        }
                        break;
                    case 'products_overview':
                        $basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';

                        if (!empty($codeActivityArray[$activity])) {
                            $theCode = 'OVERVIEW';
                        }
                        break;
                    case 'products_redeem_gift': 	// this shall never be the only activity
                        if (trim($GLOBALS['TSFE']->fe_user->user['username']) == '') {
                            $basket_tmpl = 'BASKET_TEMPLATE_NOT_LOGGED_IN';
                        } else {
                            $uniqueId = GeneralUtility::trimExplode('-', $basketObj->recs['tt_products']['giftcode'], true);
                            $query = 'uid=\'' . intval($uniqueId[0]) . '\' AND crdate=\'' . intval($uniqueId[1]) . '\' AND NOT deleted';
                            $giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
                            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes);

                            $pricefactor = doubleval($conf['creditpoints.']['pricefactor']);
                            if ($row && $pricefactor > 0) {
                                $money = $row['amount'];
                                $uid = $row['uid'];
                                $fieldsArray = [];
                                $fieldsArray['deleted'] = 1;
                                // Delete the gift record
                                $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products_gifts', 'uid=' . intval($uid), $fieldsArray);
                                $creditpoints = $money / $pricefactor;
                                tx_ttproducts_creditpoints_div::addCreditPoints($GLOBALS['TSFE']->fe_user->user['username'], $creditpoints);
                                $cpArray = tx_ttproducts_control_session::readSession('cp');
                                $cpArray['gift']['amount'] += $creditpoints;
                                \tx_ttproducts_control_basket::store('cp', $cpArray);
                            }
                        }
                        break;
                    case 'products_info':
                        $basket_tmpl = 'BASKET_INFO_TEMPLATE';

                        if (!empty($codeActivityArray[$activity]) ||
                            empty($activityArray['products_basket'])) {
                            $theCode = 'INFO';
                        }
                        break;
                    case 'products_payment':
                        $bPayment = true;
                        $orderUid = $this->getOrderUid($orderArray);
                        $orderNumber = $this->getOrdernumber($orderUid);

                        if ($conf['paymentActivity'] == 'payment' || $conf['paymentActivity'] == 'verify') {
                            $handleLib =
                                PaymentShippingHandling::getHandleLib(
                                    'request',
                                    $basketExtra
                                );

                            if (strpos($handleLib, 'transactor') !== false) {
                                // Payment Transactor
                                tx_transactor_api::init($this->pibase, $this->cObj, $conf);
                                $referenceId = tx_transactor_api::getReferenceUid(
                                    $handleLib,
                                    $basketObj->basketExtra['payment.']['handleLib.'],
                                    TT_PRODUCTS_EXT,
                                    $orderUid
                                );
                                $addQueryString = [];
                                $excludeList = '';
                                $linkParams =
                                    $this->urlObj->getLinkParams(
                                        $excludeList,
                                        $addQueryString,
                                        true
                                    );
                                $useNewTransactor = false;
                                $transactorConf = $this->getTransactorConf($handleLib);
                                if (
                                    isset($transactorConf['compatibility']) &&
                                    $transactorConf['compatibility'] == '0'
                                ) {
                                    $useNewTransactor = true;
                                }

                                if ($useNewTransactor) {
                                    $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';

                                    if (
                                        class_exists($callingClassName) &&
                                        method_exists($callingClassName, 'checkRequired')
                                    ) {
                                        $parameters = [
                                            $referenceId,
                                            $basketExtra['payment.']['handleLib'] ?? '',
                                            $basketExtra['payment.']['handleLib.'] ?? [],
                                            TT_PRODUCTS_EXT,
                                            $calculatedArray,
                                            $conf['paymentActivity'],
                                            $pidArray,
                                            $linkParams,
                                            $orderArray['tracking_code'] ?? '',
                                            $orderUid,
                                            $orderNumber,
                                            $this->conf['orderEmail_to'] ?? '',
                                            $cardRow,
                                        ];

                                        $paymentErrorMsg = call_user_func_array(
                                            $callingClassName . '::checkRequired',
                                            $parameters
                                        );
                                    }
                                } else {
                                    $paymentErrorMsg = tx_transactor_api::checkRequired(
                                        $referenceId,
                                        $basketExtra['payment.']['handleLib'] ?? '',
                                        $basketExtra['payment.']['handleLib.'] ?? [],
                                        TT_PRODUCTS_EXT,
                                        $calculatedArray,
                                        $conf['paymentActivity'],
                                        $pidArray,
                                        $linkParams,
                                        $orderArray['tracking_code'],
                                        $orderUid,
                                        $cardRow
                                    );
                                }
                            } elseif (strpos($handleLib, 'paymentlib') !== false) {
                                $paymentlib = GeneralUtility::makeInstance('tx_ttproducts_paymentlib');
                                $paymentlib->init($this->pibase, $basketView, $this->urlObj);
                                $referenceId = $paymentlib->getReferenceUid();
                                $paymentErrorMsg = $paymentlib->checkRequired(
                                    $referenceId,
                                    $orderArray,
                                    $basketObj->basketExtra['payment.']['handleLib'],
                                    $basketObj->basketExtra['payment.']['handleLib.']
                                );
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
                        $bPayment = true;

                        if (
                            !$bBasketEmpty &&
                            (
                                $conf['paymentActivity'] == 'verify' || $conf['paymentActivity'] == 'customized' // deprecated
                            )
                        ) {
                            $orderUid = $this->getOrderUid($orderArray);
                            $orderNumber = $this->getOrdernumber($orderUid);

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
                                    $bFinalize,
                                    $bFinalVerify,
                                    $paymentScript,
                                    $errorCode,
                                    $errorMessage
                                );

                            $paymentErrorMsg = $errorMessage;

                            if ($errorMessage != '') {
                                $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
                            }
                            if (!$bFinalize) {
                                $basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
                            }
                        } else {
                            $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
                        }
                        break;
                    case 'products_finalize':
                        $bPayment = true;
                        $handleLib = PaymentShippingHandling::getHandleLib('request', $basketExtra);
                        if ($handleLib == '') {
                            $handleLib = PaymentShippingHandling::getHandleLib('form', $basketExtra);
                        }
                        $orderUid = $this->getOrderUid($orderArray);
                        $orderNumber = $this->getOrdernumber($orderUid);

                        if (
                            !$bBasketEmpty &&
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
                                $bFinalize,
                                $bFinalVerify,
                                $paymentScript,
                                $errorCode,
                                $errorMessage
                            );
                            $paymentErrorMsg = $errorMessage;

                            if ($bFinalize == false) {
                                $label = $paymentErrorMsg;
                                $markerArray['###ERROR_DETAILS###'] = $label;
                                $basket_tmpl = 'BASKET_TEMPLATE'; // step back to the basket page
                            } else {
                                $content = ''; // do not show the content of payment again
                            }
                        } else {
                            $bFinalize = true;
                        }

                        if (
                            (!empty($codeActivityArray[$activity]) || empty($activityArray['products_basket'])) &&
                            $bFinalize
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
            $templateFilename = '';
            $templateCode = $templateObj->get(
                $theCode,
                $templateFilename,
                $errorCode
            );

            if ($errorCode) {
                return '';
            }

            if ($value) {
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
                    $bPayment,
                    $activityArray,
                    $currentPaymentActivity,
                    $pidArray,
                    $infoViewObj->infoArray,
                    $checkBasket,
                    $bBasketEmpty,
                    $checkRequired,
                    $checkAllowed,
                    $cardRequired,
                    $accountRequired,
                    $giftRequired,
                    $checkEditVariants,
                    $paymentErrorMsg,
                    $pidagb,
                    $cardObj,
                    $cardRow,
                    $accountObj,
                    $markerArray,
                    $errorCode,
                    $errorMessage,
                    $bFinalize,
                    $bFinalVerify
                );

                $addQueryString = [];
                $overwriteMarkerArray = [];

                $piVars = tx_ttproducts_model_control::getPiVars();
                if (is_array($piVars)) {
                    $backPID = $piVars['backPID'] ?? '';
                }
                $overwriteMarkerArray =
                    $this->urlObj->addURLMarkers(
                        $backPID,
                        [],
                        $addQueryString
                    );
                $markerArray = array_merge($markerArray, $overwriteMarkerArray);
                $content = $templateService->substituteMarkerArray($content . $newContent, $markerArray);
            }
        } // foreach ($activityArray as $activity=>$value)

        // finalization at the end so that after every activity this can be called
        if ($bFinalize && !$bBasketEmpty && $orderUid) {
            $checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);

            if (!$checkRequired) {
                $checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
            }

            $checkAllowed = $infoViewObj->checkAllowed($basketExtra);
            if ($checkRequired == '' && $checkAllowed == '') {
                $orderUid = $this->getOrderUid($orderArray);
                $orderNumber = $this->getOrdernumber($orderUid);

                if (
                    !$bBasketEmpty &&
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
                            $bFinalize,
                            $bFinalVerify,
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
                    $usedCreditpoints = tx_ttproducts_creditpoints_div::getUsedCreditpoints($_REQUEST['recs']);
                }

                $contentTmp = $activityFinalize->doProcessing(
                    $templateCode,
                    $mainMarkerArray,
                    $this->funcTablename,
                    $orderUid,
                    $orderArray,
                    $productRowArray,
                    $alwaysInStock,
                    $conf['useArticles'] ?? 3,
                    $addressArray,
                    $bFinalVerify,
                    $basketExt,
                    $usedCreditpoints,
                    $errorCode,
                    $errorMessage
                );

                if (isset($conf['PIDthanks']) && $conf['PIDthanks'] == $GLOBALS['TSFE']->id) {
                    $tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
                    $contentTmpThanks = $basketView->getView(
                        $errorCode,
                        $templateCode,
                        $theCode,
                        $infoViewObj,
                        false,
                        false,
                        $calculatedArray,
                        true,
                        $tmpl,
                        $mainMarkerArray,
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
                $contentNoSave = $basketView->getView(
                    $errorCode,
                    $templateCode,
                    $theCode,
                    $infoViewObj,
                    false,
                    false,
                    $calculatedArray,
                    true,
                    'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
                    $mainMarkerArray,
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
                $requiredOut = tx_ttproducts_api::getErrorOut(
                    $theCode,
                    $templateCode,
                    $subpartmarkerObj->spMarker('###' . $subpart . $this->config['templateSuffix'] . '###'),
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
                    $infoViewObj->infoArray,
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
        }

        $content = $markerObj->replaceGlobalMarkers(
            $content
        );

        return $content;
    } // processActivities

    /**
     * Do all the things to be done for this activity
     * former functions products_basket and basketView::printView
     * Takes care of basket, address info, confirmation and gate to payment
     * Also the 'products_...' script parameters are used here.
     *
     * @param	array		  CODEs for display mode
     *
     * @return	string	text to display
     */
    public function doProcessing(
        $codes,
        $calculatedArray,
        $basketExtra,
        array $basketRecs,
        $basketExt,
        $addressArray,
        &$errorCode,
        &$errorMessage
    ) {
        $content = '';
        $empty = '';
        $activityArray = [];
        $this->activityArray = [];
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
        $templateFilename = '';
        $templateCode = $templateObj->get(
            '',
            $templateFilename,
            $errorCode
        );

        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $basketView->init(
            $this->useArticles,
            $errorCode,
            $this->urlArray
        );

        $activityVarsArray = [
            'clear_basket' => 'products_clear_basket',
            'customized_payment' => 'products_customized_payment',
            'basket' => 'products_basket',
            'finalize' => 'products_finalize',
            'info' => 'products_info',
            'overview' => 'products_overview',
            'payment' => 'products_payment',
            'redeem_gift' => 'products_redeem_gift',
            'verify' => 'products_verify',
        ];

        $update = GeneralUtility::_POST('products_update') || GeneralUtility::_POST('products_update_x');
        $info = GeneralUtility::_POST('products_info') || GeneralUtility::_POST('products_info_x');
        $payment = GeneralUtility::_POST('products_payment') || GeneralUtility::_POST('products_payment_x');
        $gpVars = GeneralUtility::_GP(TT_PRODUCTS_EXT);

        if (!$update && !$payment && !$info && isset($gpVars) && is_array($gpVars) && isset($gpVars['activity']) && is_array($gpVars['activity'])) {
            $changedActivity = key($gpVars['activity']);
            $theActivity = $activityVarsArray[$changedActivity];
            if ($theActivity) {
                $activityArray[$theActivity] = $gpVars['activity'][$changedActivity];
            }
        }

        // use '_x' for coordinates from Internet Explorer if button images are used
        if (GeneralUtility::_GP('products_redeem_gift') || GeneralUtility::_GP('products_redeem_gift_x')) {
            $activityArray['products_redeem_gift'] = true;
        }

        if (GeneralUtility::_GP('products_clear_basket') || GeneralUtility::_GP('products_clear_basket_x')) {
            $activityArray['products_clear_basket'] = true;
        }
        if (GeneralUtility::_GP('products_overview') || GeneralUtility::_GP('products_overview_x')) {
            $activityArray['products_overview'] = true;
        }
        if (!$update) {
            if (GeneralUtility::_GP('products_payment') || GeneralUtility::_GP('products_payment_x')) {
                $activityArray['products_payment'] = true;
            } elseif (GeneralUtility::_GP('products_info') || GeneralUtility::_GP('products_info_x')) {
                $activityArray['products_info'] = true;
            }
        }

        if (GeneralUtility::_GP('products_customized_payment') || GeneralUtility::_GP('products_customized_payment_x')) {
            $activityArray['products_customized_payment'] = true;
        }
        if (GeneralUtility::_GP('products_verify') || GeneralUtility::_GP('products_verify_x')) {
            $activityArray['products_verify'] = true;
        }
        if (GeneralUtility::_GP('products_finalize') || GeneralUtility::_GP('products_finalize_x')) {
            $activityArray['products_finalize'] = true;
        }

        $codeActivityArray = [];
        $bBasketCode = false;
        if (is_array($codes)) {
            foreach ($codes as $k => $code) {
                switch ($code) {
                    case 'BASKET':
                        $codeActivityArray['products_basket'] = true;
                        $bBasketCode = true;
                        break;
                    case 'INFO':
                        if (
                            !(
                                !empty($activityArray['products_verify']) ||
                                !empty($activityArray['products_customized_payment']) ||
                                !empty($activityArray['products_payment']) ||
                                !empty($activityArray['products_finalize'])
                            )
                        ) {
                            $codeActivityArray['products_info'] = true;
                        }
                        $bBasketCode = true;
                        break;
                    case 'OVERVIEW':
                        $codeActivityArray['products_overview'] = true;
                        break;
                    case 'PAYMENT':
                        if (
                            !empty($activityArray['products_finalize'])
                        ) {
                            $codeActivityArray['products_finalize'] = true;
                        } else {
                            $codeActivityArray['products_payment'] = true;
                        }

                        if (!empty($activityArray['products_verify'])) {
                            $bBasketCode = true; // damit verify gesetzt bleibt, wenn vorhanden
                        }
                        break;
                    case 'FINALIZE':
                        $codeActivityArray['products_finalize'] = true;
                        if (!empty($activityArray['products_verify'])) {
                            $bBasketCode = true;
                        }
                        break;
                    default:
                        // nothing
                        break;
                }
            }
        }

        if ($bBasketCode) {
            $activityArray = array_merge($activityArray, $codeActivityArray);
            $this->activityArray = $this->transformActivities($activityArray);
        } else {
            // only the code activities if there is no code BASKET or INFO set
            $this->activityArray = $codeActivityArray;
        }

        $activityApi = GeneralUtility::makeInstance(ActivityApi::class);
        $activityApi->setActivityArray($this->activityArray);

        $fixCountry =
            (
                !empty($this->activityArray['products_basket']) ||
                !empty($this->activityArray['products_info']) ||
                !empty($this->activityArray['products_payment']) ||
                !empty($this->activityArray['products_verify']) ||
                !empty($this->activityArray['products_finalize']) ||
                !empty($this->activityArray['products_customized_payment'])
            );

        $infoViewObj->init(
            $activityArray['products_payment'] ?? false,
            $fixCountry,
            $basketExtra
        );

        if (
            $fixCountry &&
            $infoViewObj->checkRequired('billing', $basketExtra) == ''
        ) {
            $infoViewObj->mapPersonIntoDelivery($basketExtra);
        }

        if (count($this->activityArray)) {
            $content = $this->processActivities(
                $this->activityArray,
                $activityVarsArray,
                $codeActivityArray,
                $calculatedArray,
                $basketExtra,
                $basketRecs,
                $basketExt,
                $addressArray,
                $errorCode,
                $errorMessage
            );
        }

        return $content;
    }
}
