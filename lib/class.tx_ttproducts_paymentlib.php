<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
 * Part of the tt_products (Shopping System) extension.
 *
 * Payment Library extra functions
 * deprecated: use the extension transactor (Payment Transactor API) instead of this code
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\ExtensionUtility;


class tx_ttproducts_paymentlib implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;
	public $basketView;
	public $urlObj;
	private $providerObject;


	public function init (
		$pibase,
		$basketView,
		$urlObj
	) {
		$this->pibase = $pibase;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$this->conf = $cnf->conf;
		$this->config = $cnf->config;

		$this->basketView = $basketView;
		$this->urlObj = $urlObj;
	}


	/**
	 * returns the gateway mode from the settings
	 */
	public function getGatewayMode (
		$handleLib,
		$confScript
	) {
		if ($handleLib == 'paymentlib') {
			$gatewayModeArray = array('form' => TX_PAYMENTLIB_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE);
		} else {
			$gatewayModeArray = array('form' => TX_PAYMENTLIB2_GATEWAYMODE_FORM, 'webservice' => TX_PAYMENTLIB2_GATEWAYMODE_WEBSERVICE);
		}

		$gatewayMode = $gatewayModeArray[$confScript['gatewaymode']];
		if (!$gatewayMode) {
			$gatewayMode = $gatewayModeArray['form'];
		}
		return $gatewayMode;
	}


	public function getReferenceUid () {
		$referenceId = false;
		$providerObject = $this->getProviderObject();

		if (is_object($providerObject)) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

			$orderObj = $tablesObj->get('sys_products_orders');
			$orderUid = $orderObj->getUid();

			if (!$orderUid) {
				$orderUid = $orderObj->getBlankUid($orderArray);
			}
			if (method_exists($providerObject, 'generateReferenceUid')) {
				$referenceId = $providerObject->generateReferenceUid($orderUid, TT_PRODUCTS_EXT);
			} else if (method_exists($providerObject, 'createUniqueID')) {
				$referenceId = $providerObject->createUniqueID($orderUid, TT_PRODUCTS_EXT);
			} else if (method_exists($providerObject, 'getLibObj')) {
				$libObj = $providerObject->getLibObj();
				if (is_object($libObj)) {
					$referenceId = $libObj->createUniqueID($orderUid, TT_PRODUCTS_EXT);
				}
			}
		}
		return $referenceId;
	}


	private function setProviderObject ($providerObject) {
		$this->providerObject = $providerObject;
	}


	public function getProviderObject () {
		return $this->providerObject;
	}


	/**
	 * Include handle extension library
	 */
	public function includeHandleLib (
		$handleLib,
		$basketExtra,
		$basketRecs,
		$calculatedArray,
		$itemArray,
		$orderArray,
		&$confScript,
		&$bFinalize,
		&$errorMessage
	) {
		$lConf = $confScript;
		$content = '';

		if (strpos($handleLib,'paymentlib') !== false) {

// 			if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($handleLib)) {
// 				GeneralUtility::requireOnce(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($handleLib) . 'lib/class.tx_' . $handleLib . '_providerfactory.php');
// 			}
			$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
			$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
			$providerFactoryObj = ($handleLib == 'paymentlib' ? tx_paymentlib_providerfactory::getInstance() : tx_paymentlib2_providerfactory::getInstance());
			$paymentMethod = $confScript['paymentMethod'];
			$providerProxyObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);

			if (is_object($providerProxyObject)) {
				if (method_exists($providerProxyObject, 'getRealInstance')) {
					$providerObject = $providerProxyObject->getRealInstance();
				} else {
					$providerObject = $providerProxyObject;
				}
				$this->setProviderObject($providerObject);
				$providerKey = $providerObject->getProviderKey();
				$gatewayMode = $this->getGatewayMode($handleLib, $confScript);
				$ok = $providerObject->transaction_init(
					($handleLib == 'paymentlib' ? TX_PAYMENTLIB_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER : TX_PAYMENTLIB2_TRANSACTION_ACTION_AUTHORIZEANDTRANSFER),
					$paymentMethod,
					$gatewayMode,
					TT_PRODUCTS_EXT,
					$confScript['conf.']
				);

				if (!$ok) {
					$errorMessage = 'ERROR: Could not initialize transaction.';
					return '';
				}
				$this->getPaymentBasket($basketExtra, $basketRecs, $totalArr, $addrArr, $paymentBasketArray);
				$referenceId = $this->getReferenceUid();

				if (!$referenceId) {
					$errorMessage = $languageObj->getLabel(
						'error_reference_id'
					);
					return '';
				}

				$transactionDetailsArr =
					$this->getTransactionDetails(
						$referenceId,
						$handleLib,
						$confScript,
						$totalArr,
						$addrArr,
						$orderArray,
						$paymentBasketArray
					);
					// Set payment details and get the form data:
				$ok = $providerObject->transaction_setDetails($transactionDetailsArr);

				if (!$ok) {
					$errorMessage = $languageObj->getLabel(
						'error_transaction_details'
					);
					return '';
				}

					// Get results of a possible earlier submit and display messages:
				$transactionResultsArr = $providerObject->transaction_getResults($referenceId);
				$referenceId = $this->getReferenceUid(); // in the case of a callback, a former order than the current would have been read in

				if ($providerObject->transaction_succeded($transactionResultsArr)) {
					$bFinalize = true;
				} else if ($providerObject->transaction_failed($transactionResultsArr)) {
					$errorMessage = '<span style="color:red;">'.htmlspecialchars($providerObject->transaction_message($transactionResultsArr)).'</span><br />';
					$errorMessage .= '<br />';
					$content = '';
				} else {
					$providerObject->transaction_setOkPage($transactionDetailsArr['transaction']['successlink']);
					$providerObject->transaction_setErrorPage($transactionDetailsArr['transaction']['faillink']);

					$compGatewayForm = ($handleLib == 'paymentlib' ? TX_PAYMENTLIB_GATEWAYMODE_FORM : TX_PAYMENTLIB2_GATEWAYMODE_FORM);
					$compGatewayWebservice = ($handleLib == 'paymentlib' ? TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE : TX_PAYMENTLIB2_GATEWAYMODE_WEBSERVICE);

					if ($gatewayMode == $compGatewayForm) {

						$templateFilename = $lConf['templateFile'] ? $lConf['templateFile'] : (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('addons_tt_products') ? 'EXT:' . TT_PRODUCTS_EXT . '/template/paymentlib.tmpl' : '');
						if (!$templateFilename) {
							$templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
							$templateFilename = $templateObj->getTemplateFile();
						}
                        $incFile = '';

                        if (
                            version_compare(TYPO3_version, '9.4.0', '>=')
                        ) {
                            $sanitizer = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Resource\FilePathSanitizer::class);
                            $incFile = $sanitizer->sanitize($templateFilename);
                        } else {
                            $incFile = $GLOBALS['TSFE']->tmpl->getFileName($templateFilename);
                        }

						$localTemplateCode = file_get_contents($incFile);
						$markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
						$localTemplateCode = tx_div2007_core::substituteMarkerArrayCached($localTemplateCode, $markerObj->getGlobalMarkerArray());

							// Render hidden fields:
						$hiddenFields = '';
						$hiddenFieldsArr = $providerObject->transaction_formGetHiddenFields();
						foreach ($hiddenFieldsArr as $key => $value) {
							$hiddenFields .= '<input name="' . $key . '" type="hidden" value="' . htmlspecialchars($value) . '" />' . chr(10);
						}

						$formuri = $providerObject->transaction_formGetActionURI();
						if (strstr ($formuri, 'ERROR') != false) {
							$bError = true;
						}

						if ($formuri && !$bError) {
							$markerArray=array();
							$markerArray['###HIDDENFIELDS###'] = $markerArray['###HIDDEN_FIELDS###'] = $hiddenFields;
							$markerArray['###REDIRECT_URL###'] = $formuri;
							$markerArray['###PAYMENTLIB_TITLE###'] = $lConf['extTitle'];
							$markerArray['###PAYMENTLIB_INFO###'] = $lConf['extInfo'];
                            $markerArray['###PAYMENTLIB_IMAGE###'] = (
                                $lConf['extImage'] == 'IMAGE' &&
                                isset($lConf['extImage.']) &&
                                is_array($lConf['extImage.']) ?
                                $this->pibase->cObj->getContentObject('IMAGE')->render($lConf['extImage.']) :
                                $lConf['extImage']
                            );
							$markerArray['###PAYMENTLIB_WWW###'] = $lConf['extWww'];

							$content = $this->basketView->getView(
								$errorCode,
								$localTemplateCode,
								'PAYMENT',
								$this->info,
								false,
								false,
								$calculatedArray,
								true,
								'PAYMENTLIB_FORM_TEMPLATE',
								$markerArray,
								$templateFilename,
								$itemArray,
                                $notOverwritePriceIfSet = false,
								$orderArray,
								$basketExtra,
								$basketRecs
							);
						} else {
							if ($bError) {
								$errorMessage = $formuri;
							} else {
								$errorMessage = $languageObj->getLabel('error_relay_url');
							}
						}
					} else if ($gatewayMode == $compGatewayWebservice) {
						$rc = $providerObject->transaction_process();
						$resultsArray = $providerObject->transaction_getResults($referenceId);//array holen mit allen daten

						if ($providerObject->transaction_succeded($resultsArray) == false) {
							$errorMessage = $providerObject->transaction_message($resultsArray); // message auslesen
						} else {
							$bFinalize = true;
						}
						$contentArray = array();
					}
				}
			} else {
				$errorMessage = 'ERROR: Could not find provider object for payment method \''.$paymentMethod.'\' .';
			}
		}
		return $content;
	} // includeHandleLib


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in correctly
	 */
	public function checkRequired (
		$referenceId,
		$orderArray,
		$handleLib,
		$confScript
	) {
		$rc = '';

		if (strpos($handleLib,'paymentlib') !== false) {
			$providerFactoryObj = ($handleLib == 'paymentlib' ? tx_paymentlib_providerfactory::getInstance() : tx_paymentlib2_providerfactory::getInstance());
			$paymentMethod = $confScript['paymentMethod'];
			$providerObject = $providerFactoryObj->getProviderObjectByPaymentMethod($paymentMethod);
			if (is_object($providerObject)) {
				$providerKey = $providerObject->getProviderKey();
				$paymentBasketArray = array();
				$addrArr = array();
				$totalArr = array();
				$transactionDetailsArr =
					$this->getTransactionDetails(
						$referenceId,
						$confScript,
						$totalArr,
						$addrArr,
						$orderArray,
						$paymentBasketArray
					);
				echo "<br><br>ausgabe details: ";
				print_r ($transactionDetailsArr);
				echo "<br><br>";
				$set = $providerObject->transaction_setDetails($transactionDetailsArr);
				$ok = $providerObject->transaction_validate();

				if (!$ok) return 'ERROR: invalide data.';
				if ($providerObject->transaction_succeded() == false) {
					$rc = $providerObject->transaction_message();
				}
			}
		}
		return $rc;
	} // checkRequired


	public function getUrl (
		$conf,
		$pid
	) {
		if (!$pid) {
			$pid = $GLOBALS['TSFE']->id;
		}
		$addQueryString = array();
		$excludeList = '';
		$target = '';
		$url = tx_div2007_alpha5::getTypoLink_URL_fh003(
			$this->pibase->cObj,
			$pid,
			$this->urlObj->getLinkParams(
				$excludeList,
				$addQueryString,
				true
			),
			$target,
			$conf
		);
		return $url;
	}


	/**
	 * Gets all the data needed for the transaction or the verification check
	 */
	public function getTransactionDetails (
		$referenceId,
		$handleLib,
		$confScript,
		$totalArr,
		$addrArr,
		$orderArray,
		&$paymentBasketArray
	) {
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');

		$param = '';
		$paramNameActivity = '&products_' . $this->conf['paymentActivity'];
		$paramFailLink = $paramNameActivity . '=0' . $param;
		$paramSuccessLink = $paramNameActivity . '=1' . $param;
		$paramReturi = $param;

			// Prepare some values for the form fields:
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');
		$calculatedArray = &$calculObj->getCalculatedArray();

		$totalPrice = $calculatedArray['priceNoTax']['total']['ALL'];
		$totalPriceFormatted = $priceViewObj->priceFormat($totalPrice);
		$orderObj = $tablesObj->get('sys_products_orders');
		$orderUid = $orderObj->getUid();
		if (!$orderUid) {
			$orderUid = $orderObj->getBlankUid(); // Gets an order number, creates a new order if no order is associated with the current session
		}

		if ($this->conf['paymentActivity'] == 'finalize' && $confScript['returnPID']) {
			$successPid = $confScript['returnPID'];
		} else {
			$successPid = ($this->conf['paymentActivity'] == 'payment' || $this->conf['paymentActivity'] == 'verify' ? ($this->conf['PIDthanks'] ? $this->conf['PIDthanks'] : $this->conf['PIDfinalize']) : $GLOBALS['TSFE']->id);
		}
		$conf = array('returnLast' => 'url');
		$urlDir = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
		$retlink = $urlDir . $this->getUrl($conf, $GLOBALS['TSFE']->id);
		$returi = $retlink . $paramReturi;
		$faillink = $urlDir . $this->getUrl($conf, $this->conf['PIDpayment']) . $paramFailLink;
		$successlink = $urlDir . $this->getUrl($conf, $successPid) . $paramSuccessLink;
		$transactionDetailsArr = array (
			'transaction' => array (
				'amount' => $totalPrice,
				'currency' => $confScript['currency'] ? $confScript['currency'] : $confScript['Currency'],
				'orderuid' => $orderUid,
				'returi' => $returi,
				'faillink' => $faillink,
				'successlink' => $successlink
			),
			'total' => $totalArr,
			'tracking' => $orderArray['tracking_code'],
			'address' => $addrArr,
			'basket' => $paymentBasketArray,
		);
		if ($this->conf['paymentActivity'] == 'verify') {
			$transactionDetailsArr['transaction']['verifylink'] = $retlink . $paramNameActivity . '=1';
		}

		if (isset($confScript['conf.']) && is_array($confScript['conf.'])) {
			$transactionDetailsArr['options'] = $confScript['conf.'];
		}
		$transactionDetailsArr['options']['reference'] = $referenceId;

		$gatewayMode = $this->getGatewayMode($handleLib, $confScript);
		$cardObj = $tablesObj->get('sys_products_cards');
		if (is_object($this->card) && $gatewayMode == TX_PAYMENTLIB_GATEWAYMODE_WEBSERVICE) {
			$cardUid = $cardObj->getUid();
			$cardRow = $cardObj->getRow($cardUid);
			$transactionDetailsArr['cc'] = $cardRow;
		}

		return $transactionDetailsArr;
	}

	//****************************************************//
	//* Filling the basket of a paymentlib basket if the *//
	//* selected payment-method has a own basket for its *//
	//* needs                                            *//
	//*--------------------------------------------------*//
	//* @providerObject		The paymentlib-object which  *//
	//*                     holds the payment-basket     *//
	//****************************************************//
	public function &getPaymentBasket (
		$basketExtra,
		$basketRecs,
		&$totalArr,
		&$addrArr,
		&$basketArr
	) {
		$bUseStaticInfo = false;
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');

		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')) {
			$eInfo = ExtensionUtility::getExtensionInfo('static_info_tables');
			$sitVersion = $eInfo['version'];
			if (version_compare($sitVersion, '2.0.5', '>=')) {
				$bUseStaticInfo = true;
			}
		}

		// Get references to the concerning baskets
		$calculObj = GeneralUtility::makeInstance('tx_ttproducts_basket_calculate');

		// Get references from the shop basket
		$items = $basketObj->itemArray;
		$calculatedArray = &$calculObj->getCalculatedArray();

		// Setting up total values
		$totalArr = array();
		$totalArr['goodsnotax'] = $this->fFloat($calculatedArray['priceNoTax']['goodstotal']['ALL']);
		$totalArr['goodstax'] = $this->fFloat($calculatedArray['priceTax']['goodstotal']['ALL']);
		$totalArr['paymentnotax'] = $this->fFloat($calculatedArray['payment']['priceNoTax']);
		$totalArr['paymenttax'] = $this->fFloat($calculatedArray['payment']['priceTax']);
		$totalArr['shippingnotax'] = $this->fFloat($calculatedArray['shipping']['priceNoTax']);
		$totalArr['shippingtax'] = $this->fFloat($calculatedArray['shipping']['priceTax']);
		$totalArr['handlingnotax'] = $this->fFloat($calculatedArray['handling']['priceNoTax']);
		$totalArr['handlingtax'] = $this->fFloat($calculatedArray['handling']['0']['priceTax']);
/*		$totalArr['amountnotax'] = $this->fFloat($calculatedArray['priceNoTax']['total']['ALL']);
		$totalArr['amounttax'] = $this->fFloat($calculatedArray['priceTax']['total']['ALL']);*/
		$totalArr['amountnotax'] = $this->fFloat($calculatedArray['priceNoTax']['vouchertotal']['ALL']);
		$totalArr['amounttax'] = $this->fFloat($calculatedArray['priceTax']['vouchertotal']['ALL']);
		$totalArr['taxrate'] = $calculatedArray['maxtax']['goodstotal']['ALL'];
		$totalArr['totaltax'] = $this->fFloat($totalArr['amounttax'] - $totalArr['amountnotax']);

		// Setting up address info values
		$mapAddrFields = array(
			'first_name' => 'first_name',
			'last_name' => 'last_name',
			'address' => 'address1',
			'zip' => 'zip',
			'city' => 'city',
			'telephone' => 'phone',
			'email' => 'email',
			'country' => 'country'
		);
		$tmpAddrArr = array(
			'person' => &$infoViewObj->infoArray['billing'],
			'delivery' => &$infoViewObj->infoArray['delivery']
		);
		$addrArr = array();

		foreach($tmpAddrArr as $key => $basketAddrArr) {
			$addrArr[$key] = array();

			// Correct firstname- and lastname-field if they have no value
			if ($basketAddrArr['first_name'] == '' && $basketAddrArr['last_name'] == '') {
				$tmpNameArr = explode(" ", $basketAddrArr['name'], 2);
				$basketAddrArr['first_name'] = $tmpNameArr[0];
				$basketAddrArr['last_name'] = $tmpNameArr[1];
			}

			// Map address fields
			foreach ($basketAddrArr as $mapKey => $value) {
				$paymentLibKey = $mapAddrFields[$mapKey];
				if ($paymentLibKey != '') {
					$addrArr[$key][$paymentLibKey] = $value;
				}
			}

			// guess country and language settings for invoice address. One of these vars has to be set: country, countryISO2, $countryISO3 or countryISONr
			// you can also set 2 or more of these codes. The codes will be joined with 'OR' in the select-statement and only the first
			// record which is found will be returned. If there is no record at all, the codes will be returned untouched

			if ($bUseStaticInfo) {
				$countryArray = tx_div2007_staticinfotables::fetchCountries($addrArr[$key]['country'], $addrArr[$key]['countryISO2'], $addrArr[$key]['countryISO3'], $addrArr[$key]['countryISONr']);
				$countryRow = $countryArray[0];

				if (is_array($countryRow) && count($countryRow)) {
					$addrArr[$key]['country'] = $countryRow['cn_iso_2'];
				}
			}
		}
		$addrArr['delivery']['note'] = $basketObj->recs['delivery']['note'];

		// Fill the basket array
		$basketArr = array();
		$priceObj = GeneralUtility::makeInstance('tx_ttproducts_field_price');

		$totalCount = 0;
		foreach ($items as $sort => $actItemArray) {
			foreach ($actItemArray as $k1 => $actItem) {
				$totalCount += intval($actItem['count']);
			}
		}

		foreach ($items as $sort => $actItemArray) {
			$basketArr[$sort] = array();
			foreach ($actItemArray as $k1 => $actItem) {
				$row = $actItem['rec'];
				$tax = $row['tax'];
				$count = intval($actItem['count']);
				$basketRow = array(
					'item_name' => $row['title'],
					'on0' => $row['title'],
					'os0' => $row['note'],
					'on1' => $row['www'],
					'os2' => $row['note2'],
					'quantity' => $count,
// 					'singlepricenotax' => $this->fFloat($actItem['priceNoTax']),
// 					'singleprice' =>  $this->fFloat($actItem['priceTax']),
					'amount' => $this->fFloat($actItem['priceNoTax']),
					'shipping' => $count * $totalArr['shippingtax'] / $totalCount,
					'handling' => $this->fFloat($priceObj->getPrice($basketExtra, $basketRecs, $row['handling'], 0, $row)),
					'taxpercent' => $tax,
					'tax' => $this->fFloat($actItem['priceTax'] - $actItem['priceNoTax']),
					'totaltax' => $this->fFloat($actItem['totalTax']) - $this->fFloat($actItem['totalNoTax']),
					'item_number' => $row['itemnumber'],
				);
				$basketArr[$sort][] = $basketRow;
			}
		}

		if ($calculatedArray['priceTax']['vouchertotal']['ALL'] != $calculatedArray['priceTax']['total']['ALL']) {
			$voucherAmount = $calculatedArray['priceTax']['vouchertotal']['ALL'] - $calculatedArray['priceTax']['total']['ALL'];
			$voucherText = $languageObj->getLabel('voucher_payment_article');

			$basketArr['VOUCHER'][] =
				array(
					'item_name' => $voucherText,
					'on0' => $voucherText,
					'quantity' => 1,
					'amount' => $voucherAmount,
					'taxpercent' => 0,
					'item_number' => 'VOUCHER'
				);
			$totalArr['goodsnotax'] = $this->fFloat($calculatedArray['priceNoTax']['goodstotal']['ALL'] + $voucherAmount);
			$totalArr['goodstax'] = $this->fFloat($calculatedArray['priceTax']['goodstotal']['ALL'] + $voucherAmount);
		}
	}


	public function fFloat ($value = 0) {
		if (is_float($value)) {
			$float = $value;
		} else {
			$float = floatval($value);
		}

		return round($float, 2);
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_paymentlib.php']);
}

