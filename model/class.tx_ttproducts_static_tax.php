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
 * functions for the static_taxes table
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\ExtensionUtility;


class tx_ttproducts_static_tax extends tx_ttproducts_table_base {
	protected $uidStore;
	protected $setShopCountryCode;
	private $allTaxesArray = array();
	private $taxArray;
	private $countryArray = array();
	private $taxIdArray = array();
	static private $isInstalled = false;
	static private $need4StaticTax = false; // if the usage of static_info_tables_taxes is required due to some circumstances: E.g. if download products inside of the EU are sold

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($functablename) {
		$result = false;

		if (self::isInstalled()) {
			$result = parent::init($functablename);
			if (!$result) {
				return false;
			}
			$tablename = $this->getTablename();
			$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
			$tableconf = $cnf->getTableConf('static_taxes');
			$this->getTableObj()->setDefaultFieldArray(array('uid' => 'uid', 'pid' => 'pid'));
			$this->getTableObj()->setTCAFieldArray('static_taxes');

			$requiredFields = 'uid,pid';
			if (!empty($this->tableconf['requiredFields']))	{
				$tmp = $tableconf['requiredFields'];
				$requiredFields = ($tmp ? $tmp : $requiredFields);
			}
			$requiredListArray = GeneralUtility::trimExplode(',', $requiredFields);
			$this->getTableObj()->setRequiredFieldArray($requiredListArray);
		}

		return $result;
	} // init

	public function initTaxes (
		$infoArray,
		$conf
	) {
		if (
			isset($infoArray) &&
			is_array($infoArray) &&
			isset($infoArray['billing']) &&
			isset($infoArray['billing']['country_code']) &&
			isset($conf['TAXpercentage.'])
		) {
			$whereArray = array();
			$taxRateArray = array();
			$taxTitleArray = array();

			foreach ($conf['TAXpercentage.'] as $key => $confArray) {

				$position = strpos($key, '.');
				$index = substr($key, 0, $position);
				$whereArray[$index] = $confArray['where.']['static_countries'];
				$taxRateArray[$index] = $confArray['tax.']['tx_rate'];
				$taxTitleArray[$index] = $confArray['tax.']['title'];
			}

			$shippingCountryCode = $infoArray['billing']['country_code'];
			if (
				isset($infoArray['delivery']) &&
				isset($infoArray['delivery']['country_code'])
			) {
				$shippingCountryCode = $infoArray['delivery']['country_code'];
			}

			\JambageCom\StaticInfoTablesTaxes\Api\TaxApi::init(
				$shopCountryCode = '',
				$shopCountrySubdivisionCode = '',
				$shippingCountryCode,
				$shippingCountrySubdivisionCode = '',
				$billingCountryCode = '',
				$billingCountrySubdivisionCode = '',
				$whereArray,
				$taxRateArray,
				$taxTitleArray
			);
		}
	}

	static public function isInstalled () {
		$result = self::$isInstalled;

		if (
			!$result &&
			\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables_taxes')) {
			$eInfo = ExtensionUtility::getExtensionInfo('static_info_tables_taxes');

			if (is_array($eInfo)) {
				$sittVersion = $eInfo['version'];
				if (version_compare($sittVersion, '0.3.0', '>=')) {
					$result = self::$isInstalled = true;
				}
			}
		}

		return $result;
	}

	public function isValid () {
		$result = self::isInstalled() && !$this->needsInit() && $this->getUidStore();
		return $result;
	}

	public function getUidStore () {
		return $this->uidStore;
	}

	public function setUidStore ($uid) {
		$this->uidStore = $uid;
	}

	public function getShopCountryCode () {
		return $this->setShopCountryCode;
	}

	public function setShopCountryCode ($setShopCountryCode) {
		$this->setShopCountryCode = $setShopCountryCode;
	}

	public function setStoreData ($uidStore) {
		if (self::isInstalled() && $uidStore > 0) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$orderAdressObj = $tablesObj->get('address', false);
			$storeRow = $orderAdressObj->get($uidStore);
			$theCountryCode = '';

			if (!empty($storeRow)) {
				$staticInfoCountryField = $orderAdressObj->getField('static_info_country');
				if (
					!isset($storeRow[$staticInfoCountryField]) &&
					isset($storeRow['country'])
				) {
					$staticInfoCountryField = 'country';
				}
				$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
				$tableconf = $cnf->getTableConf('address');

				if (
					$tableconf['countryReference'] == 'uid' &&
					\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($storeRow[$staticInfoCountryField])
				) {
					$countryObj = $tablesObj->get('static_countries');
					if (is_object($countryObj)) {
						$countryRow = $countryObj->get($storeRow[$staticInfoCountryField]);
						$theCountryCode = $countryRow['cn_iso_3'];
					}
				} else {
					$theCountryCode = $storeRow[$staticInfoCountryField];
				}

				if ($theCountryCode != '') {
					$zoneField = $orderAdressObj->getField('zone');
					if ($tableconf['zoneReference'] == 'uid') {
						$zoneArray =
							$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
								'zn_code',
								'static_country_zones',
								'uid=' . intval($storeRow[$zoneField])
							);
						if (isset($zoneArray) && is_array($zoneArray) && isset($zoneArray[0])) {
							$theZoneCode = $zoneArray[0]['zn_code'];
						}
					} else {
						$theZoneCode = $storeRow[$zoneField];
					}
					$this->countryArray['shop'] = array();
					$this->countryArray['shipping'] = array();
					$this->countryArray['billing'] = array();
					$this->countryArray['shop']['country_code'] = $theCountryCode;
					$this->countryArray['shop']['zone'] = $theZoneCode;
					$this->countryArray['shipping']['country_code'] = $theCountryCode;
					$this->countryArray['shipping']['zone'] = $theZoneCode;
					$this->countryArray['billing']['country_code'] = '';
					$this->countryArray['billing']['zone'] = '';
					$this->setUidStore($uidStore); // this must be done at the end of successful processing
					$this->setShopCountryCode($theCountryCode);
				}
			}
/*			$allTaxesArray = array();
			$this->getStaticTax($row,$tax,$allTaxesArray); // call it to set the member variables*/
		}
	}

	protected function didValuesChange (array $countryArray) {
		if (
            is_array($this->countryArray['shipping']) && 
            count($this->countryArray['shipping'])
        ) {
			$result = (
				count(
					array_diff_assoc($this->countryArray['shipping'], $countryArray['shipping'])
				) > 0
			);
		} else {
			$result = (is_array($countryArray['shipping']) && (count($countryArray['shipping']) > 0));
		}
		return $result;
	}

	public function setAllTaxesArray ($taxArray, $taxId = '') {
		if ($taxId > 0) {
			$this->allTaxesArray[$taxId] = $taxArray;
		} else {
			$this->allTaxesArray = $taxArray;
		}
	}

	public function getAllTaxesArray ($taxId = '') {
		if ($taxId > 0) {
			$result = $this->allTaxesArray[$taxId];
		} else {
			$result = $this->allTaxesArray;
		}
		return $result;
	}

	public function setTax ($tax, $taxId) {
		if ($taxId > 0) {
			$this->taxArray[$taxId] = $tax;
		}
	}

	public function getTaxArray ($taxId) {
		$result = false;
		if ($taxId > 0) {
			$result = $this->taxArray[$taxId];
		}

		return $result;
	}

	public function setTaxId ($taxId) {
		$this->taxIdArray[] = $taxId;
		$this->taxIdArray = array_unique($this->taxIdArray);
	}

	public function storeValues ($countryArray) {
		$this->countryArray = $countryArray;
	}

	public function getCategoryArray ($uidArray) {
		$result = array();

		$isValid = true;
		foreach ($uidArray as $uid) {
			if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($uid)) {
				$isValid = false;
				$result = false;
				break;
			}
		}

		if ($isValid) {
			$uids = implode(',', $uidArray);
			$where = 'uid_local IN (' . $uids . ')' .
				\JambageCom\Div2007\Utility\TableUtility::enableFields(
					'tt_products_products_mm_tax_categories'
				);
			$rowArray =
				$GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'tt_products_products_mm_tax_categories',
					$where
				);

            if (
				is_array($rowArray) &&
				!empty($rowArray)
			) {
				foreach ($rowArray as $categoryRow) {
					$result[] = $categoryRow['uid_foreign'];
				}
			}
		}

		return $result;
	}

	public function getTaxInfo (
		&$taxInfoArray,
		&$shopCountryArray,
		$tax,
		array $uidArray,
		array $basketRecs
	) {
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
        }

        $countryArray = $this->countryArray;
		if (
			!isset($countryArray['shop']) ||
			$countryArray['shop']['country_code'] == ''
		) {
			return false;
		}

		if (
			isset($basketRecs) &&
			is_array($basketRecs) &&
			!empty($basketRecs)
		) {
			$personInfo = $basketRecs['personinfo'];
			$deliveryInfo = $basketRecs['delivery'];
		}

		if (
			isset($deliveryInfo) &&
			is_array($deliveryInfo)
		) {
			$countryArray['shipping']['country_code'] =  $deliveryInfo['country_code'];
			$countryArray['shipping']['zone'] = $deliveryInfo['zone'];
		}

		if (
			isset($personInfo) &&
			is_array($personInfo)
		) {
			$countryArray['billing']['country_code'] =  $personInfo['country_code'];
			$countryArray['billing']['zone'] = $personInfo['zone'];
		}
		$categoryArray = array();
		if (!empty($uidArray)) {
 			$categoryArray = $this->getCategoryArray($uidArray);
		}

		$countryCode = '';
		$zoneCode = '';

		$taxInfoArray =
			\JambageCom\StaticInfoTablesTaxes\Api\TaxApi::fetchCountryTaxes(
				$countryCode,
				$zoneCode,
				$staticInfoApi,
				$tax,
				$categoryArray,
				-1,
				$countryArray['shop']['country_code'],
				$countryArray['shop']['zone'],
				$countryArray['shipping']['country_code'],
				$countryArray['shipping']['zone'],
				$countryArray['billing']['country_code'],
				$countryArray['billing']['zone'],
				0,
				true
			);
		$shopCountryArray = $countryArray['shop'];
		return true;
	}

	static public function setNeed4StaticTax ($value) {
		self::$need4StaticTax = $value;
	}

	static public function getNeed4StaticTax () {
		return self::$need4StaticTax;
	}

	static public function need4StaticTax (
		array $row
	) {
		if (self::getNeed4StaticTax()) {
			return true;
		}

		$result = false;

		if (
			isset($row['ext']) &&
			is_array($row['ext'])
		) {
			$extArray = $row['ext'];
		}

		if (
			self::isInstalled() &&
			isset($extArray['records']) &&
			is_array($extArray['records']) &&
			isset($extArray['records']['tt_products_downloads']) &&
			isset($extArray['records']['sys_file_reference'])
		) {
			$result = true;
			self::setNeed4StaticTax(true);
		}

		return $result;
	}

	public function getStaticTax (
		$basketRecs,
		array $row,
		&$tax,
		&$taxInfoArray
	) {
		$extArray = array();
		$categoryArray = array();

		if (
			!empty($row) &&
			$this->getUidStore() &&
			self::isInstalled()
		) {
			if (
				isset($basketRecs) &&
				is_array($basketRecs) &&
				!empty($basketRecs)
			) {
				$deliveryInfo = $basketRecs['delivery'];
				$personInfo = $basketRecs['personinfo'];
			}
			$taxId = 0;
			if (isset($row['tax_id'])) {
				$taxId = intval($row['tax_id']);
			}
			$taxCatId = 0;
			if (isset($row['taxcat_id'])) {
				$taxCatId = intval($row['taxcat_id']);
			}

			if (
				isset($row['ext']) &&
				is_array($row['ext'])
			) {
				$extArray = $row['ext'];
			}

			if (
				isset($extArray['records']) &&
				is_array($extArray['records']) &&
				isset($extArray['records']['tt_products_downloads']) &&
				isset($extArray['records']['sys_file_reference'])
			) {
				// TODO
				$categoryArray[] = \JambageCom\StaticInfoTablesTaxes\Api\TaxApi::EU_CATEGORY_DIGITAL_MEDIA_EBOOK;
				// EU download media detected. This overwrites the tax class of the product, if available.
			}

			if (
				isset($this->countryArray['shop']['country_code']) &&
				(
					$taxId > 0 ||
					$taxCatId > 0 ||
					is_array($categoryArray) && count($categoryArray)
				)
			) {
				$uid = $row['uid'];
				$taxInfoArray = array();
                if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
                    $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\StaticInfoTablesApi::class);
                } else {
                    $staticInfoApi = GeneralUtility::makeInstance(\JambageCom\Div2007\Api\OldStaticInfoTablesApi::class);
                }
				$countryArray = $this->countryArray;

				if (isset($personInfo) && is_array($personInfo)) {
					$countryArray['billing']['country_code'] = $personInfo['country_code'];
					$countryArray['billing']['zone'] = $personInfo['zone'];
				}

				if (isset($deliveryInfo) && is_array($deliveryInfo)) {
					$countryArray['shipping']['country_code'] = $deliveryInfo['country_code'];
					$countryArray['shipping']['zone'] = $deliveryInfo['zone'];
				}

				if ($taxCatId && empty($categoryArray)) {
					$categoryArray = $this->getCategoryArray(array($uid));
				}

				if (empty($categoryArray) && $taxId) {
					$taxInfoArray = $this->getAllTaxesArray($taxId);
				}

				if (
					$this->didValuesChange($countryArray) ||
					empty($taxInfoArray)
				) {
					$countryCode = '';
					$zoneCode = '';

					$taxInfoArray =
						\JambageCom\StaticInfoTablesTaxes\Api\TaxApi::fetchCountryTaxes(
							$countryCode,
							$zoneCode,
							$staticInfoApi,
							0.0,
							$categoryArray,
							$taxId,
							$countryArray['shop']['country_code'],
							$countryArray['shop']['zone'],
							$countryArray['shipping']['country_code'],
							$countryArray['shipping']['zone'],
							$countryArray['billing']['country_code'],
							$countryArray['billing']['zone'],
							0,
							true
						);

					$this->storeValues(
						$countryArray
					);

					if (empty($categoryArray) && $taxId) {
						$this->setAllTaxesArray(
							$taxInfoArray,
							$taxId
						);
					}
					$tax = 0.0;

					if (
						isset($taxInfoArray) &&
						is_array($taxInfoArray) &&
						!empty($taxInfoArray) &&
						isset($taxInfoArray[$countryCode]) &&
						is_array($taxInfoArray[$countryCode])
					) {
						if (count($taxInfoArray) == 1) {
							$taxRow = current($taxInfoArray[$countryCode]);
							$tax = $taxRow['tx_rate'];
						} else { // calculate together many taxes and use them as one single tax. (Canada)
							$priceOne =
								\JambageCom\StaticInfoTablesTaxes\Api\TaxApi::applyConsumerTaxes(
									$taxInfoArray,
									doubleval(1)
								);
							if ($priceOne !== false) {
								$tax = ($priceOne - 1) * 100;
							}
						}
					}
					if (empty($categoryArray)) {
						$this->setTax($tax, $taxId);
					}
				} else {
					$tax = $this->getTaxArray($taxId);
				}
			}
		}
	}
}


