<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the customer
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;


abstract class CustomerTypes {
	const Billing = 1;
	const Delivery = 2;
}



class CustomerApi {
	static private $billingInfo;
	static private $shippingInfo;
	static private $fields =
	'name,cnum,first_name,last_name,username,email,telephone,title,salutation,address,house_no,telephone,fax,email,company,city,zip,state,country,country_code,tt_products_vat,date_of_birth,tt_products_business_partner,tt_products_organisation_form';
	static private $requiredInfoFields = '';
	static protected $possibleCheckFieldArray = array('name', 'last_name', 'email', 'telephone');
	static protected $creditpointfields = 'tt_products_creditpoints,tt_products_vouchercode';


	static public function init (
		$conf,
		$billingRow,
		$deliveryRow,
		$basektExtra
	) {
		if (
			isset($basketRecs) &&
			is_array($basketRecs) &&
			!empty($basketRecs) &&
			isset($billingRow) &&
			isset($deliveryRow)
		) {
			self::setBillingInfo($billingRow);
			self::setShippingInfo($deliveryRow);
		}

		$fields = self::getFields();

		if (
			isset($GLOBALS['TCA']['fe_users']['columns']) &&
			is_array($GLOBALS['TCA']['fe_users']['columns'])
		) {
			foreach (($GLOBALS['TCA']['fe_users']['columns']) as $field => $fieldTCA) {
				if (!GeneralUtility::inList($fields, $field)) {
					$fields .= ',' . $field;
				}
			}
		}
		self::setFields($fields);
		$requiredInfoFields = array();
		$requiredInfoFieldArray = $conf['requiredInfoFields.'];
		$typeArray = array('billing', 'delivery');

		foreach ($typeArray as $type) {
			if (
				isset($requiredInfoFieldArray) &&
				is_array($requiredInfoFieldArray) &&
				isset($requiredInfoFieldArray[$type])
			) {
				$requiredInfoFields[$type] = $requiredInfoFieldArray[$type];
			} else {
				$requiredInfoFields[$type] = trim($conf['requiredInfoFields']);
			}

			$addRequiredInfoFields =
				PaymentShippingHandling::getAddRequiredInfoFields(
					$type,
					$basketExtra
				);

			if ($addRequiredInfoFields != '') {
				$requiredInfoFields[$type] .= ',' . $addRequiredInfoFields;
			}
		}

		self::setRequiredInfoFields($requiredInfoFields);
	}


	static public function setBillingInfo (array $value) {
		if (
			isset($value['name']) &&
			isset($value['email'])
		) {
			self::$billingInfo = $value;
		}
	}


	static public function getBillingInfo () {
		return self::$billingInfo;
	}


	static public function setShippingInfo (array $value) {
		if (
			isset($value['name']) &&
			isset($value['email'])
		) {
			self::$shippingInfo = $value;
		}
	}


	static public function getShippingInfo () {
		return self::$shippingInfo;
	}


	static public function getBillingIso3 ($defaultValue = '') {
		$result = '';

		$billingInfo = self::getBillingInfo();

		if (
			is_array($billingInfo) &&
			isset($billingInfo['static_info_country'])
		) {
			$result = $billingInfo['static_info_country'];
		} else if ($defaultValue != '') {
			$result = $defaultValue;
		}

		return $result;
	}


	static public function setFields ($fields) {
		self::$fields = $fields;
	}


	static public function getFields () {
		return self::$fields;
	}


	static public function getCreditPointFields () {
		return self::$creditpointfields;
	}


	static public function setRequiredInfoFields ($requiredInfoFields) {
		self::$requiredInfoFields = $requiredInfoFields;
	}


	/**
	 * Checks if required fields are filled in
	 */
	static public function getRequiredInfoFields ($type) {
		$result = false;
		if (
			isset(self::$requiredInfoFields[$type]) &&
			!empty(self::$requiredInfoFields[$type])
		) {
			$result = self::$requiredInfoFields[$type];
		}

		return $result;
	}


	static public function getPossibleCheckField () {
		$requiredInfoFields = self::getRequiredInfoFields('billing');
		$checkField = '';
		foreach (self::$possibleCheckFieldArray as $possibleCheckField) {
			if (GeneralUtility::inList($requiredInfoFields, $possibleCheckField)) {
				$checkField = $possibleCheckField;
				break;
			}
		}

		return $checkField;
	}


}


