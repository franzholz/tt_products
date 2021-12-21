<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use JambageCom\Div2007\Utility\FrontendUtility;

class tx_ttproducts_control implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $pibaseClass;
	public $cObj;
	public $conf;
	public $config;
	public $basket; 	// the basket object
	public $activityArray;		// activities for the CODEs
	public $funcTablename;
	public $subpartmarkerObj; // subpart marker functions
	public $urlObj; // url functions
	public $urlArray; // overridden url destinations
	public $useArticles;

    static public $nextActivity = array(
            'basket'  => 'info',
            'info'    => 'payment',
            'payment' => 'finalize'
        );
    static public $activityMap = array(
            'basket'  => 'products_basket',
            'info'    => 'products_info',
            'payment' => 'products_payment',
            'finalize' => 'products_finalize'
        );

	public function init ($pibaseClass, $conf, $config, $funcTablename, $useArticles, $basketExtra)  {
		$this->pibaseClass = $pibaseClass;
		$this->pibase = GeneralUtility::makeInstance('' . $pibaseClass);
		$this->cObj = $this->pibase->cObj;
		$this->conf = $conf;
		$this->config = $config;
		$this->basket = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$this->funcTablename = $funcTablename;
		$this->useArticles = $useArticles;

		$this->subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');
		$this->subpartmarkerObj->init($this->cObj);
		$this->urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view'); // a copy of it
		// This handleURL is called instead of the THANKS-url in order to let handleScript process the information if payment by credit card or so.
		$this->urlArray = array();
		if ($basketExtra['payment.']['handleURL'])	{
			$this->urlArray['form_url_thanks'] = $basketExtra['payment.']['handleURL'];
		}
		if ($basketExtra['payment.']['handleTarget'])	{	// Alternative target
			$this->urlArray['form_url_target'] = $basketExtra['payment.']['handleTarget'];
		}
		$this->urlObj->setUrlArray($this->urlArray);
	} // init

	private function getStoredOrderArray () {
        return $GLOBALS['TSFE']->fe_user->getKey('ses','order');
	}

	protected function getOrderUid () {
		$result = false;
		$orderUid = 0;
		$storedOrderArray = $this->getStoredOrderArray();

		if (isset($storedOrderArray['orderUid'])) {
			$orderUid = $storedOrderArray['orderUid'];
			$result = $orderUid;
		}

		if (!$orderUid && count($this->basket->getItemArray())) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$orderObj = $tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();
			if (!$orderUid)	{
				$orderUid = $orderObj->getBlankUid();
			}
			$result = $orderUid;
		}
		return $result;
	}


    protected function getOrdernumber ($orderUid) {
        $result = '';

        if ($orderUid) {
            $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
            $orderObj = $tablesObj->get('sys_products_orders');
            $result = $orderObj->getNumber($orderUid);
        }
        return $result;
    }


	/**
	 * returns the activities in the order in which they have to be processed
	 *
	 * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
	 * @return	  void
	 */
	public function transformActivities ($activities)	{
		$retActivities = array();
		$codeActivities = array();
		$codeActivityArray = array (
			'1' =>
				'products_overview',
				'products_basket',
				'products_info',
				'products_payment',
				'products_customized_payment',
				'products_verify',
				'products_finalize',
		);

		$activityArray =  array (
			'1' =>
			'products_redeem_gift',
			'products_clear_basket'
		);

		if (is_array($activities)) {
			foreach ($codeActivityArray as $k => $activity) {
				if ($activities[$activity]) {
					$codeActivities[$activity] = true;
				}
			}
		}

		if ($codeActivities['products_info']) {
			if($codeActivities['products_payment']) {
				$codeActivities['products_payment'] = false;
			}
		}
		if ($codeActivities['products_basket'] && count($codeActivities) > 1) {
			$codeActivities['products_basket'] = false;
		}

		$sortedCodeActivities = array();
        foreach ($codeActivityArray as $activity) { // You must keep the order of activities.
            if (isset($codeActivities[$activity])) {
                $sortedCodeActivities[$activity] = $codeActivities[$activity];
            }
        }
        $codeActivities = $sortedCodeActivities;

		if (is_array($activities)) {
			foreach ($activityArray as $k => $activity) {
				if ($activities[$activity]) {
					$retActivities[$activity] = true;
				}
			}
			$retActivities = array_merge($retActivities, $codeActivities);
		}
		return ($retActivities);
	}

	protected function getTransactorConf ($handleLib) {
        $transactorConf = '';

        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '9.0.0', '>=')
        ) {
            $transactorConf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class
            )->get($handleLib);
        } else { // before TYPO3 9
            $transactorConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$handleLib]);
        }

        return $transactorConf;
    }

	protected function processPayment (
		$orderUid,
		$orderNumber,
		$cardRow,
		$pidArray,
		$currentPaymentActivity,
		$calculatedArray,
		$basketExtra,
		&$bFinalize,
		&$errorCode,
		&$errorMessage
	)	{
		$content = '';
		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $handleScript = '';
        if (isset($basketExtra['payment.']['handleScript'])) {
            if (
                version_compare(TYPO3_version, '9.4.0', '>=')
            ) {
                $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                $handleScript = $sanitizer->sanitize($basketExtra['payment.']['handleScript']);
            } else {
                $handleScript = $GLOBALS['TSFE']->tmpl->getFileName($basketExtra['payment.']['handleScript']);
            }
        }
		$handleLib = $basketExtra['payment.']['handleLib'];
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		if ($handleScript)	{
			$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
			$content = $paymentshippingObj->includeHandleScript($handleScript, $basketExtra['payment.']['handleScript.'], $this->conf['paymentActivity'], $bFinalize, $this->pibase, $infoViewObj);
		} else if (strpos($handleLib, 'transactor') !== false && ExtensionManagementUtility::isLoaded($handleLib))	{
            $languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
            $transactorConf = $this->getTransactorConf($handleLib);
            
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

            // Get references to the concerning baskets
			$addQueryString = array();
			$excludeList = '';
			$linkParams = $this->urlObj->getLinkParams($excludeList, $addQueryString, true, false);

			$markerArray = array();
            if ($useNewTransactor) {
                $callingClassName = '\\JambageCom\\Transactor\\Api\\Start';
                call_user_func($callingClassName . '::test');

                if (
                    class_exists($callingClassName) &&
                    method_exists($callingClassName, 'init') &&
                    method_exists($callingClassName, 'includeHandleLib')
                ) {
                    call_user_func($callingClassName . '::init', $languageObj, $this->cObj, $this->conf);
                    $parameters = array(
                        $handleLib,
                        $basketExtra['payment.']['handleLib.'],
                        TT_PRODUCTS_EXT,
                        $this->basket->getItemArray(),
                        $calculatedArray,
                        $this->basket->recs['delivery']['note'],
                        $this->conf['paymentActivity'],
                        $currentPaymentActivity,
                        $infoViewObj->infoArray,
                        $pidArray,
                        $linkParams,
                        $this->basket->order['orderTrackingNo'],
                        $orderUid,
                        $orderNumber,
                        $this->conf['orderEmail_to'],
                        $cardRow,
                        &$bFinalize,
                        &$bFinalVerify,
                        &$markerArray,
                        &$templateFilename,
                        &$localTemplateCode,
                        &$errorMessage
                    );
                    $content = call_user_func_array(
                        $callingClassName . '::includeHandleLib',
                        $parameters
                    );
                }
            } else {
                tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
                $content = tx_transactor_api::includeHandleLib(
                    $handleLib,
                    $basketExtra['payment.']['handleLib.'],
                    TT_PRODUCTS_EXT,
                    $this->basket->getItemArray(),
                    $calculatedArray,
                    $this->basket->recs['delivery']['note'],
                    $this->conf['paymentActivity'],
                    $currentPaymentActivity,
                    $infoViewObj->infoArray,
                    $pidArray,
                    $linkParams,
                    $this->basket->order['orderTrackingNo'],
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

			if (!$errorMessage && $content == '' && !$bFinalize && $localTemplateCode != '') {
                $orderArray = $this->getStoredOrderArray();
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
					$this->basket->getItemArray(),
					$orderArray,
					$basketExtra
				);
			}
		}

		return $content;
	} // processPayment


	public function getErrorLabel (
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
    )
    {
        if ($checkRequired || $checkAllowed) {
            $check = ($checkRequired ? $checkRequired : $checkAllowed);
            $languageKey = '';
            
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

			if (ExtensionManagementUtility::isLoaded('sr_feuser_register')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $GLOBALS['TSFE']->sL('LLL:EXT:sr_feuser_register/Resources/Private/Language/locallang.xlf:' . $languageKey);
				$editPID = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_srfeuserregister_pi1.']['editPID'];

				if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() && $editPID) {
					$addParams = array ('products_payment' => 1);
					$addParams = $this->urlObj->getLinkParams('',$addParams,true);
					$srfeuserBackUrl = $this->pibase->pi_getPageLink($GLOBALS['TSFE']->id,'',$addParams);
					$srfeuserParams = array('tx_srfeuserregister_pi1[backURL]' => $srfeuserBackUrl);
					$addParams = $this->urlObj->getLinkParams('',$srfeuserParams,true);
					$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID,'',$addParams);
				}
			} else if (ExtensionManagementUtility::isLoaded('agency')) {
                if (!$languageKey) {
                    $languageKey = 'missing_' . $check;
                }
                $label = $GLOBALS['TSFE']->sL('LLL:EXT:agency/pi/locallang.xlf:' . $languageKey);
				$editPID = $GLOBALS['TSFE']->tmpl->setup['plugin.']['tx_agency.']['editPID'];

				if (\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() && $editPID) {
					$addParams = array('products_payment' => 1);
					$addParams = $this->urlObj->getLinkParams('', $addParams, true);
					$agencyBackUrl = $this->pibase->pi_getPageLink($GLOBALS['TSFE']->id, '', $addParams);
					$agencyParams = array('agency[backURL]' => $agencyBackUrl);
					$addParams = $this->urlObj->getLinkParams('', $agencyParams, true);
					$markerArray['###FORM_URL_INFO###'] = $this->pibase->pi_getPageLink($editPID, '', $addParams);
				}
			}

            if (!$label) {
                if ($languageKey) {
                    $label = $languageObj->getLabel($languageKey);
                } else {
                    $tmpArray = GeneralUtility::trimExplode('|', $languageObj->getLabel('missing'));
                    $languageKey = 'missing_' . $check;
                    $label = $languageObj->getLabel($languageKey);
                    if ($label)	{
                        $label = $tmpArray[0] .' '. $label . ' '. $tmpArray[1];
                    } else {
                        $label = 'field: ' . $check;
                    }
                }
            }
		} else if ($pidagb && !$_REQUEST['recs']['personinfo']['agb'] && !GeneralUtility::_GET('products_payment') && !$infoArray['billing']['agb']) {
				// so AGB has not been accepted
			$label = $languageObj->getLabel('accept_AGB');

			$addQueryString['agb']=0;
		} else if ($cardRequired)	{
			$label = '*' . $languageObj->getLabel($cardObj->getTablename() . '.' . $cardRequired) . '*';
		} else if ($accountRequired)	{
			$label = '*' . $languageObj->getLabel($accountObj->getTablename()) . ': ' . $languageObj->getLabel($accountObj->getTablename() . '.' . $accountRequired) . '*';
		} else if ($paymentErrorMsg)	{
			$label = $paymentErrorMsg;
		} else {
			$message = $languageObj->getLabel('internal_error');
			$messageArr = explode('|', $message);
			$label = $messageArr[0] . 'TTP_2' . $messageArr[1] . 'products_payment'.$messageArr[2];
		}

		return $label;
	}


	public function getContent (
		$templateCode,
		$templateFilename,
		$mainMarkerArray,
		$calculatedArray,
		$basketExtra,
		$theCode,
		$basket_tmpl,
		$bPayment,
		$orderUid,
		$orderNumber,
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
		$paymentErrorMsg,
		$pidagb,
		$cardObj,
		$cardRow,
		$accountObj,
		&$markerArray,
		&$errorCode,
		&$errorMessage,
		&$bFinalize
	) {
		$empty = '';
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$orderObj = $tablesObj->get('sys_products_orders');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$content = '';
		if ($checkBasket && !$basketEmpty)	{
			$basketConf = $cnf->getBasketConf('minPrice'); // check the basket limits

			foreach ($activityArray as $activity => $valid) {
				if ($valid) {
					$bNeedsMinCheck = in_array($activity, array('products_info','products_payment', 'products_customized_payment',  'products_verify', 'products_finalize', 'unknown'));
				}
				if ($bNeedsMinCheck) {
					break;
				}
			}

			if ($bNeedsMinCheck && $basketConf['type'] == 'price')	{
				$value = $calculatedArray['priceTax'][$basketConf['collect']];
				if (isset($value) && isset($basketConf['collect']) && $value < doubleval($basketConf['value']))	{
					$basket_tmpl = 'BASKET_TEMPLATE_MINPRICE_ERROR';
					$bFinalize = false;
				}
			}
		}

		$basketMarkerArray = array();

		if ($checkBasket && $basketEmpty)	{
			$contentEmpty = '';
			if ($this->activityArray['products_overview']) {
				tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	//
				$contentEmpty = tx_div2007_core::getSubpart(
					$templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = tx_div2007_core::getSubpart(
						$templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_OVERVIEW_EMPTY###')
					);
				}
			} else if (
                $this->activityArray['products_basket'] ||
                $this->activityArray['products_info'] ||
                $this->activityArray['products_payment']
            ) {
				$contentEmpty = tx_div2007_core::getSubpart(
					$templateCode,
					$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY' . $this->config['templateSuffix'] . '###')
				);

				if (!$contentEmpty)	{
					$contentEmpty = tx_div2007_core::getSubpart(
						$templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_TEMPLATE_EMPTY###')
					);
				}
			} else if ($this->activityArray['products_finalize'])	{
				// Todo: Neuabsenden einer bereits abgesendeten Bestellung. Der Warenkorb ist schon gelöscht.
				if (!$basketObj->order)	{
					$contentEmpty = $languageObj->getLabel( 'order_already_finalized');
				}
			}

			if ($contentEmpty != '')	{

				$contentEmpty = $markerObj->replaceGlobalMarkers($contentEmpty);
				$bFinalize = false;
			}
			$content .= $contentEmpty;
			$calculatedArray = $basketObj->getCalculatedArray();
			$basketMarkerArray = $basketView->getMarkerArray($calculatedArray);
			$markerArray = $basketMarkerArray;
		} else if (
            empty($checkRequired) &&
            empty($checkAllowed) &&
            empty($cardRequired) &&
            empty($accountRequired) &&
            empty($paymentErrorMsg) &&
			(
                empty($pidagb) ||
                $_REQUEST['recs']['personinfo']['agb'] ||
                (
                    $bPayment && GeneralUtility::_GET('products_payment')
                ) ||
                $infoArray['billing']['agb']
            )
        ) {
            if (
                !$basketEmpty &&
                $bPayment &&
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
						$bFinalize,
						$errorCode,
						$errorMessage
					);

                if ($errorMessage != '')	{
					$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
					$markerArray['###ERROR_DETAILS###'] = $errorMessage;
				}
			} else {
				$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
			}

			$paymentHTML = '';
			if (!$bFinalize && $basket_tmpl != '') {
				$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
                if (is_array($activityArray)) {
                    $shortActivity = '';
                    $nextActivity = '';
                    foreach ($activityArray as $activity) {
                        $shortActivity = array_search($activity, static::$activityMap);
                        $nextActivity  = static::$nextActivity[$activity];
                        break;
                    }

                    if ($shortActivity) {
                        $xhtmlFix = \JambageCom\Div2007\Utility\HtmlUtility::getXhtmlFix();
                        $hiddenFields .= '<input type="hidden" name="' . TT_PRODUCTS_EXT . '[activity][' . $shortActivity . ']" value="1"' . $xhtmlFix . '>';
                    }
                }
                $mainMarkerArray['###HIDDENFIELDS###'] = $hiddenFields;
                $nextUrl = FrontendUtility::getTypoLink_URL(
                    $this->cObj,
                    $this->conf['PID' . $nextActivity],
                    array()
                );

                $mainMarkerArray['###FORM_URL_NEXT_ACTIVITY###'] = $nextUrl;
                $orderArray = $this->getStoredOrderArray();
				$paymentHTML = $basketView->getView(
                    $errorCode,
                    $templateCode,
					$theCode,
					$infoViewObj,
					$this->activityArray['products_info'],
					false,
					$calculatedArray,
					true,
					$basket_tmpl,
					$mainMarkerArray,
                    '',
                    $this->basket->getItemArray(),
                    $orderArray,
					$basketExtra
				);
				$content .= $paymentHTML;
			}

			if ($orderUid && $paymentHTML != '') {
				$orderObj->setData($orderUid, $paymentHTML, 0, $basketExtra);
			}
		} else {	// If not all required info-fields are filled in, this is shown instead:
			$infoArray['billing']['error'] = 1;
			$requiredOut =
				$markerObj->replaceGlobalMarkers(
					tx_div2007_core::getSubpart(
						$templateCode,
						$this->subpartmarkerObj->spMarker('###BASKET_REQUIRED_INFO_MISSING###')
					)
				);

            if (!$requiredOut) {
                $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
                $errorCode[0] = 'no_subtemplate';
                $errorCode[1] = '###BASKET_REQUIRED_INFO_MISSING###';
                $errorCode[2] = $templateObj->getTemplateFile();
                return '';
            }
            $content .= $requiredOut;
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
				$paymentErrorMsg
			);
			$markerArray['###ERROR_DETAILS###'] = $label;
		}

		return $content;
	} // getContent


	public function processActivities (
		$activityArray,
		$activityVarsArray,
		$codeActivityArray,
		$calculatedArray,
		$basketExtra,
		&$errorCode,
		&$errorMessage
	)	{
		$basket_tmpl = '';
		$empty = '';
		$content = '';
        $basketEmpty = (count($this->basket->getItemArray()) == 0);
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
		$parser = $this->cObj;
		$orderArray = $this->getStoredOrderArray();
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$markerArray = array();
		$markerArray['###ERROR_DETAILS###'] = '';

		$pidTypeArray = array('PIDthanks','PIDfinalize','PIDpayment');
		$pidArray = array();
		foreach ($pidTypeArray as $pidType)	{
			if ($cnf->conf[$pidType])	{
				$pidArray[$pidType] = $cnf->conf[$pidType];
			}
		}

		$mainMarkerArray = array();
		$bFinalize = false; // no finalization must be called.

		if ($activityArray['products_info'] || $activityArray['products_payment'] || $activityArray['products_customized_payment'] || $activityArray['products_verify'] || $activityArray['products_finalize'])	{
			// get credit card info
			$cardViewObj = $tablesObj->get('sys_products_cards',true);
			$cardObj = $cardViewObj->getModelObj();
			$cardUid = $cardObj->getUid();
			$cardRow = $cardObj->getRow($cardUid);
			$cardViewObj->getMarkerArray($cardRow, $mainMarkerArray, $cardObj->getAllowedArray(), $cardObj->getTablename());

			// get bank account info
			$accountViewObj = $tablesObj->get('sys_products_accounts', true);
			$accountObj = $accountViewObj->getModelObj();
			$accountViewObj->getMarkerArray($accountObj->acArray, $mainMarkerArray, $accountObj->getIsAllowed());
		}

		foreach ($activityArray as $activity => $value) {
			$theCode = 'BASKET';

			if ($value) {
				$currentPaymentActivity = array_search($activity, $activityVarsArray);
				$activityConf = $cnf->getBasketConf('activity', $currentPaymentActivity);

				if (isset($activityConf['check']))	{
					$checkArray = GeneralUtility::trimExplode(',', $activityConf['check']);

					foreach ($checkArray as $checkType)	{

						switch ($checkType)	{
							case 'account':
								if ($paymentshippingObj->useAccount($basketExtra))	{
									$accountRequired = $accountObj->checkRequired();
								}
								break;
							case 'address':
								$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);
								if (!$checkRequired)	{
									$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
								}
								$checkAllowed = $infoViewObj->checkAllowed($basketExtra);
								break;
							case 'agb':
								$pidagb = intval($this->conf['PIDagb']);
								break;
							case 'basket':
								$checkBasket = true;
								break;
							case 'card':
								if ($paymentshippingObj->useCreditcard($basketExtra))	{
									$cardRequired = $cardObj->checkRequired();
								}
								break;
						}
					}
				}

					// perform action
				switch($activity)	{
					case 'products_clear_basket':
						// Empties the shopping basket!
						$this->basket->clearBasket(true);
						$calculatedArray = array();
						$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
						$calculObj->setCalculatedArray($calculatedArray);
                        $basketEmpty = (count($this->basket->getItemArray()) == 0);
					break;
					case 'products_basket':
						if (count($activityArray) == 1) {
							$basket_tmpl = 'BASKET_TEMPLATE';
						}
					break;
					case 'products_overview':
						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
						$basket_tmpl = 'BASKET_OVERVIEW_TEMPLATE';

						if ($codeActivityArray[$activity])	{
							$theCode = 'OVERVIEW';
						}
					break;
					case 'products_redeem_gift': 	// this shall never be the only activity
						if (trim($GLOBALS['TSFE']->fe_user->user['username']) == '') {
							$basket_tmpl = 'BASKET_TEMPLATE_NOT_LOGGED_IN';
						} else {
							$uniqueId = GeneralUtility::trimExplode ('-', $this->basket->recs['tt_products']['giftcode'], true);
							$query='uid=\''.intval($uniqueId[0]).'\' AND crdate=\''.intval($uniqueId[1]).'\''.' AND NOT deleted' ;
							$giftRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'tt_products_gifts', $query);
							$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($giftRes);

							$pricefactor = doubleval($this->conf['creditpoints.']['pricefactor']);
							if ($row && $pricefactor > 0) {
								$money = $row['amount'];
								$uid = $row['uid'];
								$fieldsArray = array();
								$fieldsArray['deleted']=1;
									// Delete the gift record
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products_gifts', 'uid='.intval($uid), $fieldsArray);
								$creditpoints = $money / $pricefactor;
								tx_ttproducts_creditpoints_div::addCreditPoints($GLOBALS['TSFE']->fe_user->user['username'], $creditpoints);
								$cpArray = $GLOBALS['TSFE']->fe_user->getKey('ses','cp');
								$cpArray['gift']['amount'] += $creditpoints;
								$GLOBALS['TSFE']->fe_user->setKey('ses','cp',$cpArray);
							}
						}
					break;
					case 'products_info':
						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);
						$basket_tmpl = 'BASKET_INFO_TEMPLATE';
                        if (
                            $codeActivityArray[$activity] ||
                            $activityArray['products_basket'] == false
                        ) {
                            $theCode = 'INFO';
                        }

                        break;
					case 'products_payment':
						$bPayment = true;
						$orderUid = $this->getOrderUid();
						$orderNumber = $this->getOrdernumber($orderUid);

						tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
						$pidagb = intval($this->conf['PIDagb']);
						$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);
						if (!$checkRequired)	{
							$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
						}
						$checkAllowed = $infoViewObj->checkAllowed($basketExtra);

						if ($paymentshippingObj->useCreditcard($basketExtra))	{
							$cardRequired = $cardObj->checkRequired();
						}

						if ($paymentshippingObj->useAccount($basketExtra))	{
							$accountRequired = $accountObj->checkRequired();
						}

						if ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify')	{
							$handleLib = $paymentshippingObj->getHandleLib('request', $basketExtra);
							if (strpos($handleLib,'transactor') !== false)	{
								// Payment Transactor
								tx_transactor_api::init($this->pibase, $this->cObj, $this->conf);
								$referenceId = tx_transactor_api::getReferenceUid(
									$handleLib,
									$basketExtra['payment.']['handleLib.'],
									TT_PRODUCTS_EXT,
									$orderUid
								);
								$addQueryString = array();
								$excludeList = '';
								$linkParams = $this->urlObj->getLinkParams($excludeList,$addQueryString,true);
								$transactorConf = $this->getTransactorConf($handleLib);
                                $useNewTransactor = false;
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
                                        $parameters = array(
                                            $referenceId,
                                            $basketExtra['payment.']['handleLib'],
                                            $basketExtra['payment.']['handleLib.'],
                                            TT_PRODUCTS_EXT,
                                            $calculatedArray,
                                            $this->conf['paymentActivity'],
                                            $pidArray,
                                            $linkParams,
                                            $this->basket->order['orderTrackingNo'],
                                            $orderUid,
                                            $orderNumber, // neu
                                            $this->conf['orderEmail_to'],
                                            $cardRow
                                        );

                                        $paymentErrorMsg = call_user_func_array(
                                            $callingClassName . '::checkRequired',
                                            $parameters
                                        );
                                    }
                                } else {
                                    $paymentErrorMsg = tx_transactor_api::checkRequired(
                                        $referenceId,
                                        $basketExtra['payment.']['handleLib'],
                                        $basketExtra['payment.']['handleLib.'],
                                        TT_PRODUCTS_EXT,
                                        $calculatedArray,
                                        $this->conf['paymentActivity'],
                                        $pidArray,
                                        $linkParams,
                                        $this->basket->order['orderTrackingNo'],
                                        $orderUid,
                                        $cardRow
                                    );
                                }
							}
						}
						if ($codeActivityArray[$activity])	{
							$theCode = 'PAYMENT';
						}
						$basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
					break;
					// a special step after payment and before finalization needed for some payment methods
					case 'products_customized_payment': // deprecated
					case 'products_verify':
						$bPayment = true;

                        if (
                            !$basketEmpty &&
                            (
                                $this->conf['paymentActivity']=='verify' ||
                                $this->conf['paymentActivity']=='customized' /* deprecated */
                            )
                        ) {
							$orderUid = $this->getOrderUid();
                            $mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] =
								$this->processPayment(
									$orderUid,
									$orderNumber,
									$cardRow,
									$pidArray,
									$currentPaymentActivity,
									$calculatedArray,
									$basketExtra,
									$bFinalize,
									$errorCode,
									$errorMessage
								);

							$paymentErrorMsg = $errorMessage;

							if ($errorMessage != '')	{
								$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
							}
							if (!$bFinalize)	{
								$basket_tmpl = 'BASKET_PAYMENT_TEMPLATE';
							}
						} else {
							$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
						}
					break;
					case 'products_finalize':
						$bPayment = true;
						$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');

						$handleLib = $paymentshippingObj->getHandleLib('request', $basketExtra);
						if ($handleLib == '')	{
							$handleLib = $paymentshippingObj->getHandleLib('form', $basketExtra);
						}

						if (
                            !$basketEmpty &&
                            $handleLib != ''
                        ) {
							$orderUid = $this->getOrderUid();
							$orderNumber = $this->getOrdernumber($orderUid);
							$rc = $this->processPayment(
								$orderUid,
								$orderNumber,
								$cardRow,
								$pidArray,
								$currentPaymentActivity,
								$calculatedArray,
								$basketExtra,
								$bFinalize,
								$errorCode,
								$errorMessage
							);
							$paymentErrorMsg = $errorMessage;

							if($bFinalize == false && $errorMessage != ''){
								$label = $paymentErrorMsg;
								$markerArray['###ERROR_DETAILS###'] = $label;
								$basket_tmpl = 'BASKET_TEMPLATE'; // step back to the basket page
							} else {
								$content = ''; // do not show the content of payment again
							}
						} else {
							$bFinalize = true;
						}
						if ($codeActivityArray[$activity] && $bFinalize)	{
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
					$theCode,
					$basket_tmpl,
					$bPayment,
					$orderUid,
					$orderNumber,
					$activityArray,
					$currentPaymentActivity,
					$pidArray,
					$infoViewObj->infoArray,
					$checkBasket,
					$basketEmpty,
					$checkRequired,
					$checkAllowed,
					$cardRequired,
					$accountRequired,
					$paymentErrorMsg,
					$pidagb,
					$cardObj,
					$cardRow,
					$accountObj,
					$markerArray,
					$errorCode,
					$errorMessage,
					$bFinalize
				);

				$addQueryString = array();
				$overwriteMarkerArray = array();
				$overwriteMarkerArray = $this->urlObj->addURLMarkers(0, array(),$addQueryString);
				$markerArray = array_merge($markerArray, $overwriteMarkerArray);
				$content = $parser->substituteMarkerArray($content . $newContent, $markerArray);
			}
		} // foreach ($activityArray as $activity=>$value)

			// finalization at the end so that after every activity this can be called
		if ($bFinalize) {
			$checkRequired = $infoViewObj->checkRequired('billing', $basketExtra);

			if (!$checkRequired)	{
				$checkRequired = $infoViewObj->checkRequired('delivery', $basketExtra);
			}

			$checkAllowed = $infoViewObj->checkAllowed($basketExtra);
			if ($checkRequired == '' && $checkAllowed == '')	{
				tx_div2007_alpha5::load_noLinkExtCobj_fh002($this->pibase);	// TODO
                $handleScript = '';
                if (isset($basketExtra['payment.']['handleScript'])) {
                    if (
                        version_compare(TYPO3_version, '9.4.0', '>=')
                    ) {
                        $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                        $handleScript = $sanitizer->sanitize($basketExtra['payment.']['handleScript']);
                    } else {
                        $handleScript = $GLOBALS['TSFE']->tmpl->getFileName($basketExtra['payment.']['handleScript']);
                    }
                }
				$orderUid = $this->getOrderUid();
				$orderNumber = $this->getOrdernumber($orderUid);

                if (
                    !$basketEmpty &&
                    trim($this->conf['paymentActivity']) == 'finalize'
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
							$bFinalize,
							$errorCode,
							$errorMessage
						);
					if ($errorMessage != '')	{
						$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = $errorMessage;
					}
				} else {
					$mainMarkerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
				}

					// order finalization
				$activityFinalize = GeneralUtility::makeInstance('tx_ttproducts_activity_finalize');
				$orderObj = $tablesObj->get('sys_products_orders');
				$activityFinalize->init(
					$this->pibase,
					$orderObj
				);
                $orderArray = $this->getStoredOrderArray();

				$contentTmp = $activityFinalize->doProcessing(
					$templateCode,
					$mainMarkerArray,
					$this->funcTablename,
					$orderUid,
					$basketExtra,
					$orderArray,
					$errorCode,
					$errorMessage
                );

				if ($this->conf['PIDthanks'] > 0) {
					$tmpl = 'BASKET_ORDERTHANKS_TEMPLATE';
					$contentTmpThanks = $basketView->getView(
                        $errorCode,
						$templateCode,
						'BASKET',
						$infoViewObj,
						false,
						false,
						$calculatedArray,
						true,
						$tmpl,
						$mainMarkerArray,
						'',
						$this->basket->getItemArray(),
						$orderArray,
						$basketExtra
					);

					if ($contentTmpThanks != '') {
						$contentTmp = $contentTmpThanks;
					}
				}
				if ($activityArray['products_payment'])	{	// forget the payment output from before if it comes to finalize
					$content = '';
				}
				$content .= $contentTmp;
				$contentNoSave = $basketView->getView(
                    $errorCode,
					$templateCode,
					'BASKET',
					$infoViewObj,
					false,
					false,
					$calculatedArray,
					true,
					'BASKET_ORDERCONFIRMATION_NOSAVE_TEMPLATE',
					$mainMarkerArray,
					'',
					$this->basket->getItemArray(),
					$orderArray,
					$basketExtra
				);
				$content .= $contentNoSave;

				// Empties the shopping basket!
				$this->basket->clearBasket();
			} else {
				$urlMarkerArray = $this->urlObj->addURLMarkers(0, array());
				$markerArray = array_merge($mainMarkerArray, $urlMarkerArray);

				$content = $parser->substituteMarkerArray(
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
	 * @return	string	text to display
	 */
	public function doProcessing (
		$codes,
		$calculatedArray,
		$basketExtra,
		&$errorCode,
		&$errorMessage
	) {
		$content = '';
		$empty = '';
		$activityArray = array();
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

		$basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
		$basketView->init(
			$this->pibaseClass,
			$this->urlArray,
			$this->useArticles,
			$errorCode
		);
		$activityVarsArray = array(
			'clear_basket' => 'products_clear_basket',
			'customized_payment' => 'products_customized_payment',
			'basket' => 'products_basket',
			'finalize' => 'products_finalize',
			'info' => 'products_info',
			'overview' => 'products_overview',
			'payment' => 'products_payment',
			'redeem_gift' => 'products_redeem_gift',
			'verify' => 'products_verify'
		);

		$update = GeneralUtility::_POST('products_update') || GeneralUtility::_POST('products_update_x');
		$info = GeneralUtility::_POST('products_info') || GeneralUtility::_POST('products_info_x');
		$payment = GeneralUtility::_POST('products_payment') || GeneralUtility::_POST('products_payment_x');
		$gpVars = GeneralUtility::_GP(TT_PRODUCTS_EXT);

		if (!$update && !$payment && !$info && isset($gpVars) && is_array($gpVars) && isset($gpVars['activity']) && is_array($gpVars['activity']))	{
			$changedActivity = key($gpVars['activity']);
			$theActivity = $activityVarsArray[$changedActivity];

			if ($theActivity)	{
				$activityArray[$theActivity] = $gpVars['activity'][$changedActivity];
			}
		}

			// use '_x' for coordinates from Internet Explorer if button images are used
		if (GeneralUtility::_GP('products_redeem_gift') || GeneralUtility::_GP('products_redeem_gift_x'))    {
		 	$activityArray['products_redeem_gift'] = true;
		}

		if (GeneralUtility::_GP('products_clear_basket') || GeneralUtility::_GP('products_clear_basket_x'))    {
			$activityArray['products_clear_basket'] = true;
		}
		if (GeneralUtility::_GP('products_overview') || GeneralUtility::_GP('products_overview_x'))    {
			$activityArray['products_overview'] = true;
		}
		if (!$update) {
			if (GeneralUtility::_GP('products_payment') || GeneralUtility::_GP('products_payment_x'))    {
				$activityArray['products_payment'] = true;
			} else if (GeneralUtility::_GP('products_info') || GeneralUtility::_GP('products_info_x'))    {
				$activityArray['products_info'] = true;
			}
		}
		if (GeneralUtility::_GP('products_customized_payment') || GeneralUtility::_GP('products_customized_payment_x'))    {
			$activityArray['products_customized_payment'] = true;
		}
		if (GeneralUtility::_GP('products_verify') || GeneralUtility::_GP('products_verify_x'))    {
			$activityArray['products_verify'] = true;
		}
		if (GeneralUtility::_GP('products_finalize') || GeneralUtility::_GP('products_finalize_x'))    {
			$activityArray['products_finalize'] = true;
		}

		$codeActivityArray = array();
		$bBasketCode = false;
		if (is_array($codes)) {
			foreach ($codes as $k => $code) {
				if ($code == 'BASKET')	{
					$codeActivityArray['products_basket'] = true;
					$bBasketCode = true;
				} elseif ($code == 'INFO') {
                    if (
                        !(
                            $activityArray['products_payment'] ||
                            $activityArray['products_verify'] || $activityArray['products_finalize']
                        )
                    ) {
                        $codeActivityArray['products_info'] = true;
                    }
					$bBasketCode = true;
				} elseif ($code == 'OVERVIEW') {
					$codeActivityArray['products_overview'] = true;
                } elseif ($code == 'PAYMENT') {
                    if (
                        $activityArray['products_finalize']
                    ) {
                        $codeActivityArray['products_finalize'] = true;
                    } else {
                        $codeActivityArray['products_payment'] = true;
                    }
                    if ($activityArray['products_verify']) {
                        $bBasketCode = true;
                    }
                } elseif ($code == 'FINALIZE')  {
                    $codeActivityArray['products_finalize'] = true;
                    if ($activityArray['products_verify']) {
                        $bBasketCode = true;
                    }
                }
			}
		}

		if ($bBasketCode)	{
			$activityArray = array_merge($activityArray, $codeActivityArray);
			$this->activityArray = $this->transformActivities($activityArray);
		} else {
			// only the code activities if there is no code BASKET or INFO set
			$this->activityArray = $codeActivityArray;
		}
		tx_ttproducts_model_activity::setActivityArray($this->activityArray);
		$fixCountry = ($this->activityArray['products_basket'] || $this->activityArray['products_info'] || $this->activityArray['products_payment'] || $this->activityArray['products_verify'] || $this->activityArray['products_finalize'] || $this->activityArray['products_customized_payment']);

		$infoViewObj->init(
			$this->pibase,
			$activityArray['products_payment'],
			$fixCountry,
			$basketExtra
		);

		if (
			$fixCountry &&
			$infoViewObj->checkRequired('billing', $basketExtra) == ''
		) {
			$infoViewObj->mapPersonIntoDelivery();
		}

		if (!empty($this->activityArray)) {
			$content = $this->processActivities(
				$this->activityArray,
				$activityVarsArray,
				$codeActivityArray,
				$calculatedArray,
				$basketExtra,
				$errorCode,
				$errorMessage
			);
		}
		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control.php']);
}

