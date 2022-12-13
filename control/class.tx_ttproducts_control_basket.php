<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
use TYPO3\CMS\Core\Utility\MathUtility;


use JambageCom\TtProducts\Api\CustomerApi;


abstract class BasketRecsIndex {
	const Billing = 'personinfo';
	const Delivery = 'delivery';
}


class tx_ttproducts_control_basket {
	static protected $recs = [];
	static protected $basketExt = [];	// "Basket Extension" - holds extended attributes
	static protected $basketExtra = [];	// initBasket() uses this for additional information like the current payment/shipping methods
	static protected $infoArray = [];
	static private $pidListObj;
	static private $bHasBeenInitialised = false;
	static private $funcTablename;			// tt_products or tt_products_articles


    static public function storeNewRecs ($transmissionSecurity = false) {
        $recs = GeneralUtility::_GP('recs');
        if (
            is_array($recs) &&
            $transmissionSecurity
        ) {
        // TODO  transmission security
            $errorCode = [];
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
                0,
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['checkCookies']
            );
        }
    }

	static public function init (
		&$conf,
		$tablesObj,
		$pid_list,
		$useArticles,
		array $recs = [],
		array $basketRec = []
	) {
		if (!self::$bHasBeenInitialised) {
            self::setRecs($recs);

			if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE'])) {
				self::setBasketExt(self::getStoredBasketExt());
				$basketExtra = self::getBasketExtras($tablesObj, $recs, $conf);
				self::setBasketExtra($basketExtra);
			} else {
				self::setRecs($recs);
				self::setBasketExt([]);
				self::setBasketExtra([]);
			}

			self::$pidListObj = GeneralUtility::makeInstance('tx_ttproducts_pid_list');
			self::$pidListObj->applyRecursive(
				99,
				$pid_list,
				true
			);

			self::$pidListObj->setPageArray();

			if ($useArticles == 2) {
				$funcTablename = 'tt_products_articles';
			} else {
				$funcTablename = 'tt_products';
			}
			self::setFuncTablename($funcTablename);
			$recs = self::getRecs();
			CustomerApi::init(
				$conf,
				$recs[BasketRecsIndex::Billing] ?? '',
				$recs[BasketRecsIndex::Delivery] ?? '',
				self::getBasketExtra()
			);

			self::$bHasBeenInitialised = true;
		}
	}


	static public function cleanConfArr ($confArray, $checkShow = 0) {
		$outArr = [];
		if (is_array($confArray)) {
			foreach($confArray as $key => $val) {
				if (
					intval($key) &&
					is_array($val) &&
					!MathUtility::canBeInterpretedAsInteger($key) &&
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
	static public function getBasketExtras ($tablesObj, $basketRec, &$conf) {

		$basketExtra = [];

// 		$conf = $cnfObj->getConf();
		// handling and shipping
		$pskeyArray = ['shipping' => false, 'handling' => true];	// keep this order, because shipping can unable some payment and handling configuration
		$excludePayment = '';
		$excludeHandling = '';

		foreach ($pskeyArray as $pskey => $bIsMulti) {

			if (!empty($conf[$pskey . '.'])) {        
				if ($bIsMulti) {
					ksort($conf[$pskey . '.']);

					foreach ($conf[$pskey . '.'] as $k => $confArray) {

						if (strpos($k, '.') == strlen($k) - 1) {
							$k1 = substr($k, 0, strlen($k) - 1);

							if (
								MathUtility::canBeInterpretedAsInteger($k1)
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
			if ($pskey == 'shipping' && !empty($conf['handling.'])) {
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
		if (isset($basketExtra['shipping.']) &&
			!empty($basketExtra['shipping.']['replacePayment.'])
        ) {
			if (!$conf['payment.']) {
				$conf['payment.'] = [];
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
			foreach($confArray as $confKey => $val) {
//                 if (
//                     ($val['show'] || !isset($val['show']))
//                 ) {
                if (
                    isset($val['type']) &&
                    $val['type'] == 'fe_users'
                ) {
                    if (
                        \JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
                        is_array($GLOBALS['TSFE']->fe_user->user)
                    ) {
                        $paymentField = $tablesObj->get('fe_users')->getFieldName('payment');
                        $paymentMethod = $GLOBALS['TSFE']->fe_user->user[$paymentField];
                        $conf['payment.'][$confKey . '.']['title'] = $paymentMethod;
                    } else {
                        unset($conf['payment.'][$confKey . '.']);
                    }
                }
                if (
                    !empty($val['visibleForGroupID']) &&
                    (!$tablesObj->get('fe_users')->isUserInGroup($GLOBALS['TSFE']->fe_user->user, $val['visibleForGroupID']))) {
                    unset($conf['payment.'][$confKey . '.']);
                }
// 				}
			}
			ksort($conf['payment.']);
			reset($conf['payment.']);
            $k = 0;
            if (isset($basketRec['tt_products']['payment'])) {
                $k = intval($basketRec['tt_products']['payment']);
            } else if (count($confArray) == 1) {
                $k = key($confArray);
            }

            if (
                $k &&
                isset($conf['payment.'][$k . '.']) &&
                !static::checkExtraAvailable($conf['payment.'][$k . '.'])
            ) {
                $temp = static::cleanConfArr($conf['payment.'], 1);
                $k = intval(key($temp));
            }

            if ($k) {
                $basketExtra['payment'] = [$k];
                if (isset($conf['payment.'][$k . '.'])) {
                    $basketExtra['payment.'] = $conf['payment.'][$k . '.'];
                }
            }
		}

		return $basketExtra;
	} // getBasketExtras


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
        $valueArray = [];
        $k = 0;
        if (
            $subkey != '' &&
            isset($basketRec['tt_products'][$pskey][$subkey]) ||
            $subkey == '' &&
            isset($basketRec['tt_products'][$pskey])
        ) {
            if ($subkey != '') {
                $valueArray = GeneralUtility::trimExplode('-', $basketRec['tt_products'][$pskey][$subkey]);
            } else {
                $valueArray = GeneralUtility::trimExplode('-', $basketRec['tt_products'][$pskey]);
            }
            $k = intval($valueArray[0]);
        } else {
            foreach ($confArray as $confKey => $confValue) {
                if (strpos($confKey, '.') == strlen($confKey) - 1) {
                    $currentKey = substr($confKey, 0, strlen($confKey) - 1);

                    if (
                        MathUtility::canBeInterpretedAsInteger($currentKey)
                    ) {
                        $k = $currentKey;
                        break;
                    }
                }
            }
        }

		if (!self::checkExtraAvailable($confArray[$k . '.'] ?? [])) {
			$temp = self::cleanConfArr($confArray, 1);
			$valueArray[0] = $k = intval(key($temp));
		}

		if ($subkey != '') {
			$basketExtra[$pskey . '.'][$subkey] = $valueArray;
			$basketExtra[$pskey . '.'][$subkey . '.'] = $confArray[$k . '.'] ?? [];
			if ($pskey == 'shipping') {
				$newExcludePayment = trim($basketExtra[$pskey . '.'][$subkey . '.']['excludePayment'] ?? '');
				$newExcludeHandling = trim($basketExtra[$pskey . '.'][$subkey . '.']['excludeHandling'] ?? '');
			}
		} else {
			$basketExtra[$pskey] = $valueArray;
			$basketExtra[$pskey . '.'] = $confArray[$k . '.'] ?? [];
			if ($pskey == 'shipping') {
				$newExcludePayment = trim($basketExtra[$pskey . '.']['excludePayment'] ?? '');
				$newExcludeHandling = trim($basketExtra[$pskey . '.']['excludeHandling'] ?? '');
			}
		}

		if ($newExcludePayment != '') {
			$excludePayment = ($excludePayment != '' ? $excludePayment . ',' : '') . $newExcludePayment;
		}
		if ($newExcludeHandling != '') {
			$excludeHandling = ($excludeHandling != '' ? $excludeHandling . ',' : '') . $newExcludeHandling;
		}
	}


	static public function getCmdArray () {
		$result = array('delete');

		return $result;
	}


	static public function getPidListObj () {
		return self::$pidListObj;
	}


	static public function doProcessing () {

		$piVars = tx_ttproducts_model_control::getPiVars();
		$basketExtModified = false;

		if (isset($piVars) && is_array($piVars)) {
			foreach ($piVars as $piVar => $value) {
				switch ($piVar) {
					case 'delete':
						$uid = $value;

						$basketVar = tx_ttproducts_model_control::getBasketParamVar();

						if (isset($piVars[$basketVar])) {

							if (
								isset(self::$basketExt[$uid]) &&
								is_array(self::$basketExt[$uid])
							) {

								foreach (self::$basketExt[$uid] as $allVariants => $count) {
									if (
										md5($allVariants) == $piVars[$basketVar]
									) {
										unset(self::$basketExt[$uid][$allVariants]);
										$basketExtModified = true;
									}
								}
							}
						}
					break;
				}
			}
		}

		if ($basketExtModified) {
			self::storeBasketExt(self::$basketExt);
		}
	}


	static public function setFuncTablename ($funcTablename) {
		self::$funcTablename = $funcTablename;
	}


	static public function getFuncTablename () {
		return self::$funcTablename;
	}


	static public function getRecs () {
		return self::$recs;
	}


	static public function setRecs ($recs) {

		$newRecs = [];
 		$allowedTags = '<br><a><b><td><tr><div>';

		foreach ($recs as $type => $valueArray) {
			if (is_array($valueArray)) {
				foreach ($valueArray as $k => $infoRow) {
					$newRecs[$type][$k] = strip_tags($infoRow, $allowedTags);
				}
			} else {
				$newRecs[$type] = strip_tags($valueArray, $allowedTags);
			}
		}

		self::$recs = $newRecs;
	}


	static public function getStoredRecs () {
		$result = [];

        $recs = tx_ttproducts_control_session::readSession('recs');
        if (!empty($recs)) {
            $result = $recs;
        }
        
		return $result;
	}


	static public function setStoredRecs ($valueArray) {
		self::store('recs', $valueArray);
	}


	static public function getStoredVariantRecs () {
		$result = tx_ttproducts_control_session::readSession('variant');
		return $result;
	}


	static public function setStoredVariantRecs ($valueArray) {
		self::store('variant', $valueArray);
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


	static public function getBasketExtRaw () {
		$basketVar = tx_ttproducts_model_control::getBasketVar();
		$result = GeneralUtility::_GP($basketVar);
		return $result;
	}


	static public function getStoredBasketExt () {
		$result = tx_ttproducts_control_session::readSession('basketExt');
		return $result;
	}


	static public function getStoredOrder () {
		$result = tx_ttproducts_control_session::readSession('order');
		return $result;
	}


	static public function storeBasketExt ($basketExt) {
		self::store('basketExt', $basketExt);
		self::setBasketExt($basketExt);
	}

    static public function generatedBasketExtFromRow ($row, $count) {
        $basketExt = [];

        $extArray = $row['ext'];
        $extVarLine = isset($extArray['extVarLine']) ? $extArray['extVarLine'] : '';
        $basketExt[$row['uid']][$extVarLine] = $count;

        return $basketExt;
    }


	static public function removeFromBasketExt ($removeBasketExt) {
		$basketExt = self::getStoredBasketExt();
		$bChanged = false;

		if (isset($removeBasketExt) && is_array($removeBasketExt)) {
			foreach ($removeBasketExt as $uid => $removeRow) {
				$allVariants = key($removeRow);
				$bRemove = current($removeRow);

				if (
					$bRemove &&
					isset($basketExt[$uid]) &&
					isset($basketExt[$uid][$allVariants])
				) {
					unset($basketExt[$uid][$allVariants]);
					$bChanged = true;
				}
			}
		}
		if ($bChanged) {
			self::storeBasketExt($basketExt);
		}
	}

	static public function getBasketCount (
		$row,
		$variant,
		$quantityIsFloat,
		$ignoreVariant = false
	) {
		$count = '';
		$basketExt = static::getBasketExt();
		$uid = $row['uid'];

		if (isset($basketExt[$uid])) {
			$subArr = $basketExt[$uid];

			if (
				$ignoreVariant &&
				is_array($subArr)
			) {
				$count = 0;
				foreach ($subArr as $subVariant => $subCount) {
					$count += $subCount;
				}
			} else if (
				is_array($subArr) &&
				isset($subArr[$variant])
			) {
				$tmpCount = $subArr[$variant];

				if (
					$tmpCount > 0 &&
					(
						$quantityIsFloat ||
						MathUtility::canBeInterpretedAsInteger($tmpCount)
					)
				) {
					$count = $tmpCount;
				}
			}
		}
		return $count;
	}


	static public function getStoredInfoArray () {
		$formerBasket = self::getRecs();

		$infoArray = [];

		if (isset($formerBasket) && is_array($formerBasket)) {
			$infoArray['billing'] = $formerBasket['personinfo'] ?? '';
			$infoArray['delivery'] = $formerBasket['delivery'] ?? '';
		}
		if (!$infoArray['billing']) {
			$infoArray['billing'] = [];
		}
		if (!$infoArray['delivery']) {
			$infoArray['delivery'] = [];
		}
		return $infoArray;
	}


	static public function setInfoArray ($infoArray) {
		self::$infoArray = $infoArray;

		if (
			isset($infoArray['billing']) &&
			is_array($infoArray['billing'])
		) {
			CustomerApi::setBillingInfo($infoArray['billing']);
		}

		if (
			isset($infoArray['delivery']) &&
			is_array($infoArray['delivery'])
		) {
			CustomerApi::setShippingInfo($infoArray['delivery']);
		}
	}


	static public function getInfoArray () {
		return self::$infoArray;
	}

    static public function setCountry (&$infoArray, $basketExtra) {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
        }

        if (
            $staticInfoApi->isActive() &&
            !empty($infoArray['billing']['country_code'])
        ) {
            $infoArray['billing']['country'] =
                $staticInfoApi->getStaticInfoName(
                    $infoArray['billing']['country_code'],
                    'COUNTRIES',
                    '',
                    ''
                );
 
            if (static::needsDeliveryAddresss($basketExtra)) {
                $infoArray['delivery']['country'] =
                    $staticInfoApi->getStaticInfoName(
                        $infoArray['delivery']['country_code'],
                        'COUNTRIES',
                        '',
                        ''
                    );
            }
        }
    }

	static public function uncheckAgb (&$infoArray, $bProductsPayment) {
		if (
			$bProductsPayment &&
			isset($_REQUEST['recs']) &&
			is_array($_REQUEST['recs']) &&
			isset($_REQUEST['recs']['personinfo']) &&
			is_array($_REQUEST['recs']['personinfo']) &&
			empty($_REQUEST['recs']['personinfo']['agb'])
		) {
			$infoArray['billing']['agb'] = false;
		}
	}

	// normally the delivery is copied from the bill data. But also another table can be used for it.
	static public function needsDeliveryAddresss ($basketExtra) {
		$result = true;

		$shippingType = \JambageCom\TtProducts\Api\PaymentShippingHandling::get(
			'shipping',
			'type',
			$basketExtra
		);

		if (
			$shippingType == 'pick_store' ||
			$shippingType == 'nocopy'
		) {
			$result = false;
		}

		return $result;
	}

	static public function fixCountries (&$infoArray) {
		$result = false;

		if (
			!empty($infoArray['billing']['country_code']) &&
			(
                !isset($infoArray['delivery']['zip']) ||
				$infoArray['delivery']['zip'] == '' ||
				(
					$infoArray['delivery']['zip'] == $infoArray['billing']['zip']
				)
			)
		) {
			// a country change in the select box shall be copied
			$infoArray['delivery']['country_code'] = $infoArray['billing']['country_code'];
			$result = true;
		}
		return $result;
	}


	static public function addLoginData (
		&$infoArray,
		$loginUserInfoAddress,
		$useStaticInfoCountry
	) {
		if (
            isset($GLOBALS['TSFE']) &&
			\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
			\JambageCom\TtProducts\Api\ControlApi::isOverwriteMode($infoArray)
		) {
			$address = '';
			$infoArray['billing']['feusers_uid'] =
				$GLOBALS['TSFE']->fe_user->user['uid'];

			if (
				$useStaticInfoCountry &&
				empty($infoArray['billing']['country_code'])
			) {
				$infoArray['billing']['country_code'] =
					$GLOBALS['TSFE']->fe_user->user['static_info_country'];
			}

			if ($loginUserInfoAddress) {
				$address = implode(
					chr(10),
					GeneralUtility::trimExplode(
						chr(10),
						$GLOBALS['TSFE']->fe_user->user['address'] . chr(10) .
							(
                                isset($GLOBALS['TSFE']->fe_user->user['house_no']) &&
								$GLOBALS['TSFE']->fe_user->user['house_no'] != '' ?
									$GLOBALS['TSFE']->fe_user->user['house_no'] . chr(10) :
									''
							) .
						$GLOBALS['TSFE']->fe_user->user['zip'] . ' ' . $GLOBALS['TSFE']->fe_user->user['city'] . chr(10) .
							(
								$useStaticInfoCountry ?
									$GLOBALS['TSFE']->fe_user->user['static_info_country'] :
									$GLOBALS['TSFE']->fe_user->user['country']
							),
						1
					)
				);
			} else {
				$address = $GLOBALS['TSFE']->fe_user->user['address'];
			}
			$infoArray['billing']['address'] = $address;
			$fields = CustomerApi::getFields() . ',' . CustomerApi::getCreditPointFields();

			$fieldArray = GeneralUtility::trimExplode(',', $fields);
			foreach ($fieldArray as $k => $field) {
                if (empty($infoArray['billing'][$field])) {
                    $infoArray['billing'][$field] = $GLOBALS['TSFE']->fe_user->user[$field];
				}
			}

            $typeArray = array('billing', 'delivery');
            foreach ($typeArray as $type) {
                if (
                    empty($infoArray[$type]['country']) &&
                    (
                        !empty($infoArray[$type]['static_info_country']) ||
                        !empty($infoArray[$type]['country_code'])
                    ) &&
                    $useStaticInfoCountry
                ) {
                    if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                        $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
                    } else {
                        $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
                    }

                    if (
                        $staticInfoApi->isActive()
                    ) {
                        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
                        $countryObj = $tablesObj->get('static_countries');

                        if (is_object($countryObj)) {
                            if (!empty($infoArray[$type]['static_info_country'])) {
                                $iso3Field = 'static_info_country';
                            } else if (!empty($infoArray[$type]['country_code'])) {
                                $iso3Field = 'country_code';
                            }

                            $row = $countryObj->isoGet($infoArray[$type][$iso3Field]);
                            if (isset($row['cn_short_de'])) {
                                $infoArray[$type]['country'] = $row['cn_short_de'];
                            }
                        }
                    }
                }
           }

			$infoArray['billing']['agb'] =
				(
					isset($infoArray['billing']['agb']) ?
						$infoArray['billing']['agb'] :
						$GLOBALS['TSFE']->fe_user->user['agb']
				);

			$dateBirth = $infoArray['billing']['date_of_birth'];
			$tmpPos =  strpos($dateBirth, '-');

			if (
				!$dateBirth ||
				$tmpPos === false ||
				$tmpPos == 0
			) {
				$infoArray['billing']['date_of_birth'] =
					date('d-m-Y', $GLOBALS['TSFE']->fe_user->user['date_of_birth']);
			}
			unset($infoArray['billing']['error']);
		}
	}


	static public function getTagName ($uid, $fieldname) {
		$result = tx_ttproducts_model_control::getBasketVar() . '[' . $uid . '][' . $fieldname . ']';
		return $result;
	}


	static public function getAjaxVariantFunction ($row, $functablename, $theCode) {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('taxajax')) {
			$result = 'doFetchRow(\'' . $functablename . '\',\'' . strtolower($theCode) . '\',' . $row['uid'] . ');';
		} else {
			$result = '';
		}
		return $result;
	}


	static public function destruct () {
		self::$bHasBeenInitialised = false;
	}


	static public function getRoundFormat ($type = '') {
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$result = $cnf->getBasketConf('round', $type); // check the basket rounding format

		if (isset($result) && is_array($result)) {
			$result = '';
		}

		return $result;
	}


	static public function readControl ($key = '') {
		$result = false;
		$ctrlArray = tx_ttproducts_control_session::readSession('ctrl');

		if (isset($ctrlArray) && is_array($ctrlArray)) {
			if ($key != '' && isset($ctrlArray[$key])) {
				$result = $ctrlArray[$key];
			} else {
				$result = $ctrlArray;
			}
		}

		return $result;
	}


	static public function writeControl ($valArray) {

		if (
			!isset($valArray) ||
			!is_array($valArray)
		) {
			$valArray = [];
		}
		self::store('ctrl', $valArray);
	}
}


