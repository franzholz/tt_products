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
 * functions for the taxes
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


use JambageCom\TtProducts\Api\PaymentShippingHandling;


class tx_ttproducts_field_tax extends tx_ttproducts_field_base {
	protected $useStaticTaxes = false;

	/**
	 *
	 */
	public function preInit (
		$useStaticTaxes,
		$uidStore,
		$infoArray,
		$conf
	) {
		parent::init();

		if ($useStaticTaxes) { // change static_taxes
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$staticTaxObj = $tablesObj->get('static_taxes', false);

			if ($staticTaxObj && is_object($staticTaxObj)) {
				$staticTaxObj->setStoreData($uidStore);

				if ($staticTaxObj->isValid()) {
					$taxArray = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['tax.'];
					$taxFields = '';

					if (
						isset($taxArray) &&
						is_array($taxArray) &&
						isset($taxArray['fields'])
					) {
						$taxFields = implode(',', GeneralUtility::trimExplode(',', $taxArray['fields']));
					}

					if (
						GeneralUtility::inList($taxFields, 'tax_id') ||
						GeneralUtility::inList($taxFields, 'taxcat_id')
					) {
						$this->setUseStaticTaxes(true);
					}
				}
				$staticTaxObj->initTaxes($infoArray, $conf);
			}
		}
	} // init

	public function getUseStaticTaxes () {
		return $this->useStaticTaxes;
	}

	public function setUseStaticTaxes ($useStaticTaxes) {
		$this->useStaticTaxes = $useStaticTaxes;
	}

	public function getTax (
		&$taxInfoArray,
		array $row,
		$basketExtra,
		$basketRecs = array(),
		$bEnableTaxZero = false
	) {
		$result = $this->getFieldValue(
			$taxInfoArray,
			$row,
			'tax',
			$basketExtra,
			$basketRecs,
			$bEnableTaxZero
		);
		return $result;
	}

	public function getFieldValue (
		&$taxInfoArray,
		array $row,
		$fieldname,
		$basketExtra = array(),
		$basketRecs = array(),
		$bEnableTaxZero = false
	) {
		$fieldValue = 0;
		$newTax = '';

		if (
			$this->getUseStaticTaxes() ||
			tx_ttproducts_static_tax::need4StaticTax($row)
		) {
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$staticTaxObj = $tablesObj->get('static_taxes', false);
			$staticTaxObj->getStaticTax(
				$basketRecs,
				$row,
				$newTax,
				$taxInfoArray
			);
		}

		if (is_numeric($newTax)) {
			$fieldValue = $newTax;
		} else {
			if (!empty($row)) {
				$resultValue =
					parent::getFieldValue(
						$taxInfoArray,
						$row,
						$fieldname,
						$basketExtra,
						$basketRecs,
						$bEnableTaxZero
					);

				if (is_numeric($resultValue)) {
					$fieldValue = $resultValue;
				}
			}

			if ($fieldValue == 0) {
				if (!$bEnableTaxZero && $this->conf['TAXpercentage']) {
					$fieldValue = floatval($this->conf['TAXpercentage']);
				} else {
					$fieldValue = 0;
				}
			}

			if (
				tx_ttproducts_static_tax::isInstalled()
			) {
				$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
				$staticTaxObj = $tablesObj->get('static_taxes', false);
				$staticTaxObj->getTaxInfo(
					$taxInfoArray,
					$shopCountryArray,
					$fieldValue,
					array(),
					$basketRecs
				);
			}
		}

		return $fieldValue;
	}

	public function getFieldCalculatedValue (
		$fieldValue,
		$basketExtra
	) {
		$taxFromShipping =
			PaymentShippingHandling::getReplaceTaxPercentage(
				$basketExtra,
				'shipping',
				$fieldValue
			);	// if set then this has a tax which will override the tax of the products

		if (
			isset($taxFromShipping) &&
			is_double($taxFromShipping)
		) {
			$fieldValue = $taxFromShipping;
		} else {
			$fieldValue = false;
		}

		return $fieldValue;
	}

	public function getTaxRates (
		&$shopCountryArray,
		&$taxInfoArray,
		array $uidArray,
		array $basketRecs
	) {
		$result = null;
		if (
			tx_ttproducts_static_tax::isInstalled()
		) {
			$taxInfoArray = array();
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$staticTaxObj = $tablesObj->get('static_taxes', false);
			$taxResult = $staticTaxObj->getTaxInfo(
				$taxInfoArray,
				$shopCountryArray,
				0.0,
				$uidArray,
				$basketRecs
			);

			if ($taxResult) {

                $taxRates = array();
                if (
                    isset($taxInfoArray) &&
                    is_array($taxInfoArray) &&
                    !empty($taxInfoArray)
                ) {
                    foreach ($taxInfoArray as $countryCode => $taxRowArray) {
                        foreach ($taxRowArray as $taxRow) {
                            $taxRates[$countryCode][] = $taxRow['tx_rate'];
                        }
                    }
                }
                $result = $taxRates;
            }
		}

		if (!$result) {
			$result = GeneralUtility::trimExplode(',', $this->conf['TAXrates']);
			$result = array('ALL' => $result);
		}

		return $result;
	}
}

