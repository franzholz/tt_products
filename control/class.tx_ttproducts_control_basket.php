<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2019 Franz Holzinger (franz@ttproducts.de)
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
 * control function for the basket.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_control_basket {
	static protected $recs;
	static protected $basketExt = array();
	static protected $basketExtra = array();
	static private   $bHasBeenInitialised = false;


    static public function storeNewRecs ($transmissionSecurity = false) {
        $recs = GeneralUtility::_GP('recs');
        if (
            is_array($recs) &&
            $transmissionSecurity
        ) {
            $errorCode = array();
            $errorMessage = '';
            $security = GeneralUtility::makeInstance(\JambageCom\Div2007\Security\TransmissionSecurity::class);
            $decryptionResult = $security->decryptIncomingFields(
                $recs,
                $errorCode,
                $errorMessage
            );
        }

        if (
            is_array($recs)
        ) {
            $api = GeneralUtility::makeInstance( \JambageCom\Div2007\Api\Frontend::class);
            // If any record registration is submitted, register the record.
            $api->record_registration(
                $recs,
                $GLOBALS['TYPO3_CONF_VARS']['FE']['maxSessionDataSize'],
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['checkCookies']
            );
        }
    }

	static public function init (
		&$conf,
		$tablesObj,
		array $recs = array(),
		array $basketRec = array()
	) {
		if (!self::$bHasBeenInitialised) {
            self::setRecs($recs);

            if (is_object($GLOBALS['TSFE'])) {
                self::setBasketExt(self::getStoredBasketExt());
				$basketExtra = self::getBasketExtras($tablesObj, $recs, $conf);
                self::setBasketExtra($basketExtra);
            } else {
                self::setRecs($recs);
				self::setBasketExt(array());
				self::setBasketExtra(array());
            }
            self::$bHasBeenInitialised = true;
		}
	}


	/**
	 * Setting shipping, payment methods
	 */
	static public function getBasketExtras ($tablesObj, $basketRec, &$conf) {

		$basketExtra = array();

		// handling and shipping
		$pskeyArray = array('shipping' => false, 'handling' => true);	// keep this order, because shipping can unable some payment and handling configuration
		$excludePayment = '';
		$excludeHandling = '';

		foreach ($pskeyArray as $pskey => $bIsMulti) {

			if ($conf[$pskey . '.']) {

				if ($bIsMulti) {
					ksort($conf[$pskey . '.']);

					foreach ($conf[$pskey . '.'] as $k => $confArray) {

						if (strpos($k, '.') == strlen($k) - 1) {
							$k1 = substr($k,0,strlen($k) - 1);

							if (
								tx_div2007_core::testInt($k1)
							) {
								self::getHandlingShipping(
									$basketRec,
									$pskey,
									$k1,
									$confArray,
									$excludePayment,
									$excludeHandling,
									$basketExtra
								);
							}
						}
					}
				} else {
					$confArray = $conf[$pskey . '.'];
					self::getHandlingShipping(
						$basketRec,
						$pskey,
						'',
						$confArray,
						$excludePayment,
						$excludeHandling,
						$basketExtra
					);
				}
			}

				// overwrite handling from shipping
			if ($pskey == 'shipping' && $conf['handling.']) {
				if ($excludeHandling) {
					$exclArr = GeneralUtility::intExplode(',', $excludeHandling);
					foreach($exclArr as $theVal) {
						unset($conf['handling.'][$theVal]);
						unset($conf['handling.'][$theVal . '.']);
					}
				}
			}
		}

		// overwrite payment from shipping
		if (is_array($basketExtra['shipping.']) &&
			is_array($basketExtra['shipping.']['replacePayment.'])) {
			if (!$conf['payment.']) {
				$conf['payment.'] = array();
			}

			foreach ($basketExtra['shipping.']['replacePayment.'] as $k1 => $replaceArray) {
				foreach ($replaceArray as $k2 => $value2) {
					if (is_array($value2)) {
						$conf['payment.'][$k1][$k2] = array_merge($conf['payment.'][$k1][$k2], $value2);
					} else {
						$conf['payment.'][$k1][$k2] = $value2;
					}
				}
			}
		}

			// payment
		if ($conf['payment.']) {
			if ($excludePayment) {
				$exclArr = GeneralUtility::intExplode(',', $excludePayment);

				foreach($exclArr as $theVal) {
					unset($conf['payment.'][$theVal]);
					unset($conf['payment.'][$theVal . '.']);
				}
			}

			$confArray = self::cleanConfArr($conf['payment.']);
			foreach($confArray as $key => $val) {
				if ($val['show'] || !isset($val['show'])) {
					if ($val['type'] == 'fe_users') {
						if (
                            $GLOBALS['TSFE']->loginUser &&
                            is_array($GLOBALS['TSFE']->fe_user->user)
                        ) {
							$paymentField = $tablesObj->get('fe_users')->getFieldName('payment');
							$paymentMethod = $GLOBALS['TSFE']->fe_user->user[$paymentField];
							$conf['payment.'][$key . '.']['title'] = $paymentMethod;
						} else {
							unset($conf['payment.'][$key . '.']);
						}
					}
					if (($val['visibleForGroupID'] != '') &&
						(!$tablesObj->get('fe_users')->isUserInGroup($GLOBALS['TSFE']->fe_user->user, $val['visibleForGroupID']))) {
						unset($conf['payment.'][$key . '.']);
					}
				}
			}
			ksort($conf['payment.']);
			reset($conf['payment.']);
			$k = intval($basketRec['tt_products']['payment']);
			if (!self::checkExtraAvailable($conf['payment.'][$k . '.'])) {
				$temp = self::cleanConfArr($conf['payment.'], 1);
				$k = intval(key($temp));
			}
			$basketExtra['payment'] = array($k);
			$basketExtra['payment.'] = $conf['payment.'][$k . '.'];
		}

		return $basketExtra;
	} // getBasketExtras


	static public function cleanConfArr ($confArray, $checkShow = 0) {
		$outArr = array();
		if (is_array($confArray)) {
			foreach($confArray as $key => $val) {
				if (
					intval($key) &&
					is_array($val) &&
					!tx_div2007_core::testInt($key) &&
					(!$checkShow || !isset($val['show']) || $val['show'])
				) {
					$i = intval($key);
 					$outArr[$i] = $val;
				}
			}
		}
		ksort($outArr);
		reset($outArr);
		return $outArr;
	} // cleanConfArr


	/**
	 * Check if payment/shipping option is available
	 */
	static public function checkExtraAvailable ($confArray) {
		$result = false;

		if (
			is_array($confArray) &&
			(
				!isset($confArray['show']) ||
				$confArray['show']
			)
		) {
			$result = true;
		}

		return $result;
	} // checkExtraAvailable


	/**
	 * Setting shipping, payment methods
	 */
	static public function getHandlingShipping (
		$basketRec,
		$pskey,
		$subkey,
		$confArray,
		&$excludePayment,
		&$excludeHandling,
		&$basketExtra
	) {
		ksort($confArray);
		if ($subkey != '') {
			$valueArray = GeneralUtility::trimExplode('-', $basketRec['tt_products'][$pskey][$subkey]);
		} else {
			$valueArray = GeneralUtility::trimExplode('-', $basketRec['tt_products'][$pskey]);
		}
		$k = intval($valueArray[0]);
		if (!self::checkExtraAvailable($confArray[$k . '.'])) {
			$temp = self::cleanConfArr($confArray, 1);
			$valueArray[0] = $k = intval(key($temp));
		}

		if ($subkey != '') {
			$basketExtra[$pskey . '.'][$subkey] = $valueArray;
			$basketExtra[$pskey . '.'][$subkey . '.'] = $confArray[$k . '.'];
			if ($pskey == 'shipping') {
				$newExcludePayment = trim($basketExtra[$pskey . '.'][$subkey . '.']['excludePayment']);
				$newExcludeHandling = trim($basketExtra[$pskey . '.'][$subkey . '.']['excludeHandling']);
			}
		} else {
			$basketExtra[$pskey] = $valueArray;
			$basketExtra[$pskey . '.'] = $confArray[$k . '.'];
			if ($pskey == 'shipping') {
				$newExcludePayment = trim($basketExtra[$pskey . '.']['excludePayment']);
				$newExcludeHandling = trim($basketExtra[$pskey . '.']['excludeHandling']);
			}
		}

		if ($newExcludePayment != '') {
			$excludePayment = ($excludePayment != '' ? $excludePayment . ',' : '') . $newExcludePayment;
		}
		if ($newExcludeHandling != '') {
			$excludeHandling = ($excludeHandling != '' ? $excludeHandling . ',' : '') . $newExcludeHandling;
		}
	}


	static public function getRecs () {
		return self::$recs;
	}


	static public function setRecs ($recs) {
		self::$recs = $recs;
	}


	static public function getStoredRecs () {
		$rc = $GLOBALS['TSFE']->fe_user->getKey('ses', 'recs');
		return $rc;
	}


	static public function setStoredRecs ($valueArrayy) {
		self::store('recs', $valueArrayy);
	}


	static public function store ($type, $valueArray) {
        tx_ttproducts_control_session::writeSession($type, $valueArray);
	}


	static public function getBasketExt () {
        return self::$basketExt;
    }

    
    static public function setBasketExt ($basketExt) {
        self::$basketExt = $basketExt;
    }


    static public function getBasketExtra () {
        return self::$basketExtra;
    }


    static public function setBasketExtra ($basketExtra) {
        self::$basketExtra = $basketExtra;
    }


	static public function getStoredBasketExt () {
		$rc = $GLOBALS['TSFE']->fe_user->getKey('ses','basketExt');
		return $rc;
	}


	static public function getInfoArray () {
		$formerBasket = self::getRecs();

		$infoArray = array();

		if (isset($formerBasket) && is_array($formerBasket)) {
			$infoArray['billing'] = $formerBasket['personinfo'];
			$infoArray['delivery'] = $formerBasket['delivery'];
		}
		if (!$infoArray['billing']) {
			$infoArray['billing'] = array();
		}
		if (!$infoArray['delivery']) {
			$infoArray['delivery'] = array();
		}
		return $infoArray;
	}


	static public function fixCountries (&$infoArray) {
		$rc = false;

		if (
			$infoArray['billing']['country_code'] != '' &&
			(
				$infoArray['delivery']['zip'] == '' ||
				($infoArray['delivery']['zip'] != '' && $infoArray['delivery']['zip'] == $infoArray['billing']['zip'])
			)
		) {
			// a country change in the select box shall be copied
			$infoArray['delivery']['country_code'] = $infoArray['billing']['country_code'];
			$rc = true;
		}
		return $rc;
	}


	static public function destruct () {
		self::$bHasBeenInitialised = false;
	}


	static public function getRoundFormat ($type = '') {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$result = $cnf->getBasketConf('round', $type); // check the basket rounding format
// 		$roundDiscount = $cnf->getBasketConf('round', 'discount');

		if (isset($result) && is_array($result)) {
			$result = '';
		}
		return $result;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basket.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_basket.php']);
}



