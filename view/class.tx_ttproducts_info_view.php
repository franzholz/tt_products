<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the info addresses view
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_info_view implements \TYPO3\CMS\Core\SingletonInterface {
	public $pibase; // reference to object of pibase
	public $conf;
	public $config;
	public $infoArray; // elements: 'billing' and 'delivery' addresses
			// contains former basket $personInfo and $deliveryInfo

	public $feuserextrafields;		// exension with additional fe_users fields
	public $country;			// object of the type tx_table_db
	public $password;	// automatically generated random password for a new frontend user
	public $feuserfields;
	public $creditpointfields;
	public $overwriteMode = 0;
	public $bDeliveryAddress = false;	// normally the delivery is copied from the bill data. But also another table can be used for it.
	public $bHasBeenInitialised = false;


	private function init_intern ()	{
		$this->feuserfields = 'name,cnum,first_name,last_name,username,email,telephone,title,salutation,address,house_no,telephone,fax,email,company,city,zip,state,country,country_code,tt_products_vat,date_of_birth,tt_products_business_partner,tt_products_organisation_form';
		$this->creditpointfields = 'tt_products_creditpoints,tt_products_vouchercode';

		// if feuserextrafields is loaded use also these extra fields
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('feuserextrafields')) {
			$this->feuserextrafields = ',tx_feuserextrafields_initials_name, tx_feuserextrafields_prefix_name, tx_feuserextrafields_gsm_tel,'.
				'tx_feuserextrafields_company_deliv, tx_feuserextrafields_address_deliv, tx_feuserextrafields_housenumber,'.
					'tx_feuserextrafields_housenumber_deliv, tx_feuserextrafields_housenumberadd, tx_feuserextrafields_housenumberadd_deliv,'.
					'tx_feuserextrafields_pobox, tx_feuserextrafields_pobox_deliv, tx_feuserextrafields_zip_deliv, tx_feuserextrafields_city_deliv,'.
					'tx_feuserextrafields_country, tx_feuserextrafields_country_deliv';
			$this->feuserfields .= ',' . $this->feuserextrafields;
		}

		if (isset($GLOBALS['TCA']['fe_users']['columns']) && is_array(($GLOBALS['TCA']['fe_users']['columns'])))	{
			foreach (($GLOBALS['TCA']['fe_users']['columns']) as $field => $fieldTCA)	{
				if (!GeneralUtility::inList($this->feuserfields, $field))	{
					$this->feuserfields .= ',' . $field;
				}
			}
		}
	}


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init ($pibase, $bProductsPayment, $fixCountry, $basketExtra)  {
		$this->pibase = $pibase;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->infoArray = tx_ttproducts_control_basket::getInfoArray();

// 		$this->infoArray['billing'] = $formerBasket['personinfo'];
// 		$this->infoArray['delivery'] = $formerBasket['delivery'];

		$shippingType = $paymentshippingObj->get('shipping', 'type', $basketExtra);
		if (
			$shippingType == 'pick_store' ||
			$shippingType == 'nocopy'
		)	{
			$this->bDeliveryAddress = true;
		}

		$allowedTags = '<br><a><b><td><tr><div>';
		foreach ($this->infoArray as $type => $infoRowArray)	{
			if (is_array($infoRowArray))	{
				foreach ($infoRowArray as $k => $infoRow)	{
					$this->infoArray[$type][$k] = strip_tags($infoRow, $allowedTags);
				}
			} else {
				$this->infoArray[$type] = strip_tags($infoRowArray, $allowedTags);
			}
		}
		$this->init_intern();

		$requiredInfoFields = $this->getRequiredInfoFields('', $basketExtra);
		$checkField = '';
		$possibleCheckFieldArray = array('name', 'last_name', 'email', 'telephone');

		foreach ($possibleCheckFieldArray as $possibleCheckField) {
			if (GeneralUtility::inList($requiredInfoFields, $possibleCheckField)) {
				$checkField = $possibleCheckField;
				break;
			}
		}
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if ($this->conf['useStaticInfoCountry'] && $this->infoArray['billing']['country_code'] && is_object($staticInfo))	{
			$this->infoArray['billing']['country'] = $staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['billing']['country_code'],'','');

			if ($fixCountry) {
				$bFixCountries = tx_ttproducts_control_basket::fixCountries($this->infoArray);
			}

			$this->infoArray['delivery']['country'] = $staticInfo->getStaticInfoName('COUNTRIES', $this->infoArray['delivery']['country_code'],'','');
		}

		if (
			\JambageCom\Div2007\Utility\CompatibilityUtility::isLoggedIn() &&
			(!$this->infoArray['billing'] || !$this->infoArray['billing'][$checkField] || $this->conf['editLockedLoginInfo'] || $this->infoArray['billing']['error']) &&
			$this->conf['lockLoginUserInfo']
		)	{
			$address = '';
			$this->infoArray['billing']['feusers_uid'] = $GLOBALS['TSFE']->fe_user->user['uid'];

			if ($this->conf['useStaticInfoCountry'] && !$this->infoArray['billing']['country_code'])	{
				$this->infoArray['billing']['country_code'] = $GLOBALS['TSFE']->fe_user->user['static_info_country'];
// 				if (!$this->bDeliveryAddress)	{
// 					$this->infoArray['delivery']['country_code'] = $GLOBALS['TSFE']->fe_user->user['static_info_country'];
// 				}
			}

			if ($this->conf['loginUserInfoAddress']) {
				$address = implode(chr(10),
					GeneralUtility::trimExplode(
						chr(10),
						$GLOBALS['TSFE']->fe_user->user['address'].chr(10) . ($GLOBALS['TSFE']->fe_user->user['house_no'] != '' ? $GLOBALS['TSFE']->fe_user->user['house_no'] . chr(10) : '') .
						$GLOBALS['TSFE']->fe_user->user['zip'].' '.$GLOBALS['TSFE']->fe_user->user['city'].chr(10).
						($this->conf['useStaticInfoCountry'] ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : $GLOBALS['TSFE']->fe_user->user['country']),
						1
					)
				);
			} else {
				$address = $GLOBALS['TSFE']->fe_user->user['address'];
			}
			$this->infoArray['billing']['address'] = $address;

			$fields = $this->feuserfields.','.$this->creditpointfields;
			$fieldArray = GeneralUtility::trimExplode(',',$fields);
			foreach ($fieldArray as $k => $field)	{
				$this->infoArray['billing'][$field] = ($this->infoArray['billing'][$field] ? $this->infoArray['billing'][$field]: $GLOBALS['TSFE']->fe_user->user[$field]);
			}
			$this->infoArray['billing']['country'] = ($this->infoArray['billing']['country'] ? $this->infoArray['billing']['country'] : (($this->conf['useStaticInfoCountry'] || !$GLOBALS['TSFE']->fe_user->user['country']) ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : $GLOBALS['TSFE']->fe_user->user['country']));
			$this->infoArray['billing']['agb'] = (isset($this->infoArray['billing']['agb']) ? $this->infoArray['billing']['agb'] : $GLOBALS['TSFE']->fe_user->user['agb']);

			$dateBirth = $this->infoArray['billing']['date_of_birth'];
			$tmpPos =  strpos($dateBirth,'-');
			if (!$dateBirth || $tmpPos === false || $tmpPos == 0)	{
				$this->infoArray['billing']['date_of_birth'] = date('d-m-Y', ($GLOBALS['TSFE']->fe_user->user['date_of_birth']));
			}
			unset($this->infoArray['billing']['error']);
			$this->overwriteMode = 1;
		}

		if ($bProductsPayment && isset($_REQUEST['recs']) && is_array($_REQUEST['recs']) &&
			isset($_REQUEST['recs']['personinfo']) && is_array($_REQUEST['recs']['personinfo']) && !$_REQUEST['recs']['personinfo']['agb'])	{
			$this->infoArray['billing']['agb'] = false;
		}

		$this->bHasBeenInitialised = true;
	} // init


	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init2 ($infoArray)  {
		$pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi1_base');
		$this->pibase = $pibase;
		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

		$this->conf = &$cnf->conf;
		$this->config = &$cnf->config;

		$this->infoArray = $infoArray;

		$this->init_intern();

		$this->bHasBeenInitialised = true;
	}


	public function needsInit ()	{
		return !$this->bHasBeenInitialised;
	}


	public function getCustomerEmail () {
		$infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');

		$result = ($this->conf['orderEmail_toDelivery'] && $this->infoArray['delivery']['email'] || !$this->infoArray['billing']['email'] ? $this->infoArray['delivery']['email'] : $this->infoArray['billing']['email']); // former: deliveryInfo

		return $result;
	}


	/**
	 * Fills in all empty fields in the delivery info array
	 */
	public function mapPersonIntoDelivery ()	{

			// all of the delivery address will be overwritten when no address and no email address have been filled in
		if (
            (
                !trim($this->infoArray['delivery']['address']) &&
                !trim($this->infoArray['delivery']['email']) ||
                $this->overwriteMode
            ) &&
            !$this->bDeliveryAddress
        ) {
			$fieldArray = GeneralUtility::trimExplode(',', $this->feuserfields . ',feusers_uid');
			$address = trim($this->infoArray['delivery']['address']);

			foreach($fieldArray as $k => $fName) {
				if (
					isset($this->infoArray['billing'][$fName]) &&
					(
						$this->infoArray['delivery'][$fName] == '' ||
						(
							$this->infoArray['delivery'][$fName] == '0' && !$address
						) ||
                        in_array($fName, array('country', 'country_code', 'zone'))
					)
				) {
					$this->infoArray['delivery'][$fName] = $this->infoArray['billing'][$fName];
				}
			}
		}

			// Call info hooks
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['info'])) {
			foreach  ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['info'] as $classRef) {
				$hookObj = GeneralUtility::makeInstance($classRef);
				if (method_exists($hookObj, 'mapPersonIntoDelivery')) {
					$hookObj->mapPersonIntoDelivery($this);
				}
			}
		}
	} // mapPersonIntoDelivery


	/**
	 * Checks if required fields are filled in
	 */
	public function getRequiredInfoFields ($type = '', $basketExtra)	{
		$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
		$rc = '';
		$requiredInfoFieldArray = $this->conf['requiredInfoFields.'];
		if ($type != '' && isset($requiredInfoFieldArray) && is_array($requiredInfoFieldArray) && isset($requiredInfoFieldArray[$type]))	{
			$requiredInfoFields = $requiredInfoFieldArray[$type];
		} else {
			$requiredInfoFields = trim($this->conf['requiredInfoFields']);
		}
		$addRequiredInfoFields = $paymentshippingObj->getAddRequiredInfoFields($type, $basketExtra);
		if ($addRequiredInfoFields != '')	{
			$requiredInfoFields .= ','.$addRequiredInfoFields;
		}
		return $requiredInfoFields;
	}


	/**
	 * Gets regular Expressions for Field-Checks
	 */
	public function getFieldChecks ($type) {
		$rc = '';
		$fieldCheckArray = $this->conf['regExCheck.'];

		$fieldChecks = array();
		if (isset($fieldCheckArray) && is_array($fieldCheckArray)) {
			// Array komplett durchlaufen
			foreach ($fieldCheckArray as $key => $value) {
				if (isset($value) && is_array($value)) {
					// spezifischer TS-Eintrag
					if ($key == $type . '.') {
						foreach ($value as $key2 => $value2) {
							$fieldChecks[$key2] = $value2;
						}
					}
				} else {
					// unspezifischer TS-Eintrag
					$fieldChecks[$key] = $value;
				}
			}
		}
		return $fieldChecks;
	}


	/**
	 * Checks if required fields are filled in
	 */
	public function checkRequired ($type, $basketExtra)	{

		if (!$this->bDeliveryAddress || $type == 'billing')	{
			$requiredInfoFields = $this->getRequiredInfoFields($type, $basketExtra);

			if ($requiredInfoFields)	{
				$infoFields = GeneralUtility::trimExplode(',',$requiredInfoFields);

				foreach($infoFields as $fName)	{

					if (trim($this->infoArray[$type][$fName]) == '')	{
						$rc = $fName;
						break;
					}
				}
			}

			// RegEx-Check
			$checkFieldsExpr = $this->getFieldChecks($type);
			if (($checkFieldsExpr) && (is_array($checkFieldsExpr))) {
				foreach ($checkFieldsExpr as $fName => $checkExpr) {
					if (trim($this->infoArray[$type][$fName]) != '') {
						if (preg_match('/' . $checkExpr . '/', $this->infoArray[$type][$fName]) == 0) {
							$rc = $fName;
							break;
						}
					}
				}
			}
		}

		return $rc;
	} // checkRequired


	/**
	 * Checks if the filled in fields are allowed
	 */
	public function checkAllowed ($basketExtra)	{
		$rc = '';

		$where = $this->getWhereAllowedCountries($basketExtra);
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if ($where && $this->conf['useStaticInfoCountry'] && is_object($staticInfo))	{
			$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
			$countryObj = $tablesObj->get('static_countries');
			if (is_object($countryObj))	{
				$type = ($this->bDeliveryAddress ? 'billing' : 'delivery');
				$row = $countryObj->isoGet($this->infoArray[$type]['country_code'], $where);
				if (!$row)	{
					$rc = 'country';
				}
			}
		}
		return $rc;
	} // checkAllowed


	/**
	 * gets the WHERE clause for the allowed static_countries
	 */
	public function getWhereAllowedCountries ($basketExtra)	{
		$where = '';
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		if (is_object($staticInfo))	{
			$paymentshippingObj = GeneralUtility::makeInstance('tx_ttproducts_paymentshipping');
			$where = $paymentshippingObj->getWhere($basketExtra, 'static_countries');
		}
		return $where;
	} // getWhereAllowedCountries


	/**
	 * Template marker substitution
	 * Fills in the markerArray with data for a product
	 *
	 * @param	array		reference to an item array with all the data of the item
	 * @param	string		title of the category
	 * @param	integer		number of images to be shown
	 * @param	object		the image cObj to be used
	 * @param	array		information about the parent HTML form
	 * @return	array
	 * @access private
	 */
	public function getRowMarkerArray ($basketExtra, &$markerArray, $bHtml, $bSelectSalutation)	{
		$cObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
		$parser = $cObj;
        if (
            defined('TYPO3_version') &&
            version_compare(TYPO3_version, '7.0.0', '>=')
        ) {
            $parser = tx_div2007_core::newHtmlParser(false);
        }

		$cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
		$tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
		$languageObj = GeneralUtility::makeInstance(\JambageCom\TtProducts\Api\Localization::class);
		$infoFields = GeneralUtility::trimExplode(',',$this->feuserfields); // Fields...
		$orderAddressViewObj = $tablesObj->get('fe_users', true);
		$orderAddressObj = $orderAddressViewObj->getModelObj();
		$selectInfoFields = $orderAddressObj->getSelectInfoFields();
		$staticInfo = tx_ttproducts_static_info::getStaticInfo();

		foreach ($infoFields as $k => $fName) {
			if (!in_array($fName, $selectInfoFields)) {
				$fieldMarker = strtoupper($fName);
				if ($bHtml) {
					$markerArray['###PERSON_' . $fieldMarker . '###'] =
					htmlspecialchars($this->infoArray['billing'][$fName]);
					$markerArray['###DELIVERY_' . $fieldMarker . '###'] =
					htmlspecialchars($this->infoArray['delivery'][$fName]);
				} else {
					$markerArray['###PERSON_' . $fieldMarker . '###'] =
					$this->infoArray['billing'][$fName];
					$markerArray['###DELIVERY_' . $fieldMarker . '###'] =
					$this->infoArray['delivery'][$fName];
				}
			}
		}

		if ($this->conf['useStaticInfoCountry'] && is_object($staticInfo))	{
			$bReady = false;
			$whereCountries = $this->getWhereAllowedCountries($basketExtra);
			$countryCodeArray = array();
			$countryCodeArray['billing'] = ($this->infoArray['billing']['country_code'] != '' ? $this->infoArray['billing']['country_code'] : ($GLOBALS['TSFE']->fe_user->user['static_info_country'] != '' ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : false));
            $countryCodeArray['delivery'] = ($this->infoArray['delivery']['country_code'] != '' ? $this->infoArray['delivery']['country_code'] : ($GLOBALS['TSFE']->fe_user->user['static_info_country'] != '' ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : false));

			$zoneCodeArray = array();
			$zoneCodeArray['billing'] = ($this->infoArray['billing']['zone'] != '' ? $this->infoArray['billing']['zone'] : ($GLOBALS['TSFE']->fe_user->user['zone'] != '' ? $GLOBALS['TSFE']->fe_user->user['zone'] : false));
			$zoneCodeArray['delivery'] = ($this->infoArray['delivery']['zone'] != '' ? $this->infoArray['delivery']['zone'] : ($GLOBALS['TSFE']->fe_user->user['zone'] != '' ? $GLOBALS['TSFE']->fe_user->user['zone'] : false));


            if (
                $countryCodeArray['billing'] === false &&
                $this->infoArray['billing']['country'] != ''
            ) {
                // nothing to do
                $bReady = true;
            } else if (
                \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('static_info_tables')
            ) {
				$eInfo = tx_div2007_alpha5::getExtensionInfo_fh003('static_info_tables');
				$sitVersion = $eInfo['version'];

				if (version_compare($sitVersion, '2.0.1', '>='))	{
					$markerArray['###PERSON_COUNTRY_CODE###'] =
						$staticInfo->buildStaticInfoSelector(
							'COUNTRIES',
							'recs[personinfo][country_code]',
							'',
							$countryCodeArray['billing'],
							'',
							$this->conf['onChangeCountryAttribute'],
							'field_personinfo_country_code',
							'',
							$whereCountries,
							'',
							false,
							array(),
							1,
							$outSelectedArray
						);

					if (isset($outSelectedArray) && is_array($outSelectedArray))	{
						$markerArray['###PERSON_ZONE###'] =
							$staticInfo->buildStaticInfoSelector(
								'SUBDIVISIONS',
								'recs[personinfo][zone]',
								'',
								$zoneCodeArray['billing'],
								current($outSelectedArray),
								0,
								'',
								'',
								'',
								''
							);
					} else {
						$markerArray['###PERSON_ZONE###'] = '';
					}
					$countryArray = $staticInfo->initCountries('ALL', '', false, $whereCountries);
					$markerArray['###PERSON_COUNTRY_FIRST###'] = current($countryArray);
					$markerArray['###PERSON_COUNTRY_FIRST_HIDDEN###'] = '<input type="hidden" name="recs[personinfo][country_code]" size="3" value="' . current(array_keys($countryArray)) . '">';

					$markerArray['###PERSON_COUNTRY###'] =
						$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
					unset($outSelectedArray);
					$markerArray['###DELIVERY_COUNTRY_CODE###'] =
						$staticInfo->buildStaticInfoSelector(
							'COUNTRIES',
							'recs[delivery][country_code]',
							'',
							$countryCodeArray['delivery'],
							'',
							$this->conf['onChangeCountryAttribute'],
							'field_delivery_country_code',
							'',
							$whereCountries,
							'',
							false,
							array(),
							1,
							$outSelectedArray
						);

					if (isset($outSelectedArray) && is_array($outSelectedArray))	{
						$markerArray['###DELIVERY_ZONE###'] =
							$staticInfo->buildStaticInfoSelector(
								'SUBDIVISIONS',
								'recs[delivery][zone]',
								'',
								$zoneCodeArray['billing'],
								current($outSelectedArray),
								0,
								'',
								'',
								'',
								''
							);
					} else {
						$markerArray['###DELIVERY_ZONE###'] = '';
					}

					$markerArray['###DELIVERY_COUNTRY_FIRST###'] = $markerArray['###PERSON_COUNTRY_FIRST###'];
					$markerArray['###DELIVERY_COUNTRY###'] =
						$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['delivery'],'','');
					$bReady = true;
				}

				$markerArray['###PERSON_ZONE_DISPLAY###'] = tx_div2007_staticinfotables::getTitleFromIsoCode('static_country_zones', array($zoneCodeArray['billing'], $countryCodeArray['billing']));
				$markerArray['###DELIVERY_ZONE_DISPLAY###'] = tx_div2007_staticinfotables::getTitleFromIsoCode('static_country_zones', array($zoneCodeArray['delivery'], $countryCodeArray['delivery']));
			}

			if (!$bReady)	{
				$markerArray['###PERSON_COUNTRY_CODE###'] =
					$staticInfo->buildStaticInfoSelector(
						'COUNTRIES',
						'recs[personinfo][country_code]',
						'',
						$countryCodeArray['billing'],
						'',
						0,
						'field_personinfo_country_code'
					);
				$markerArray['###PERSON_COUNTRY###'] =
					$staticInfo->getStaticInfoName('COUNTRIES', $countryCodeArray['billing'],'','');
				$markerArray['###DELIVERY_COUNTRY_CODE###'] =
					$staticInfo->buildStaticInfoSelector(
						'COUNTRIES',
						'recs[delivery][country_code]',
						'',
						$countryCodeArray['delivery'],
						'',
						0,
						'field_delivery_country_code'
					);
				$markerArray['###DELIVERY_COUNTRY###'] =
					$staticInfo->getStaticInfoName(
						'COUNTRIES',
						$countryCodeArray['delivery'],
						'',
						''
					);
			}
		}

			// Markers for use if you want to output line-broken address information
		$markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
		$markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

		$orderAddressViewObj->getAddressMarkerArray($this->infoArray['billing'], $markerArray, $bSelectSalutation, 'personinfo');
		$orderAddressViewObj->getAddressMarkerArray($this->infoArray['delivery'], $markerArray, $bSelectSalutation, 'delivery');

		$text = tx_div2007_core::csConv($this->infoArray['delivery']['note'],$GLOBALS['TSFE']->metaCharset);
		$markerArray['###DELIVERY_NOTE###'] = $text;
		$markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($text);
		$markerArray['###DELIVERY_GIFT_SERVICE###'] = $this->infoArray['delivery']['giftservice'];
		$markerArray['###DELIVERY_GIFT_SERVICE_DISPLAY###'] = nl2br($this->infoArray['delivery']['giftservice']);
		$markerArray['###DELIVERY_RADIO1_1###'] = ($this->infoArray['delivery']['radio1'] == '1' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_2###'] = ($this->infoArray['delivery']['radio1'] == '2' ? 'checked ' : '');
		$markerArray['###DELIVERY_RADIO1_DISPLAY###'] = $this->infoArray['delivery']['radio1'];

			// Desired delivery date.
		$markerArray['###DELIVERY_DESIRED_DATE###'] = $this->infoArray['delivery']['desired_date'];
		$markerArray['###DELIVERY_DESIRED_TIME###'] = $this->infoArray['delivery']['desired_time'];
		$markerArray['###DELIVERY_STORE_SELECT###'] = '';

		if ($this->bDeliveryAddress) {
			$addressObj = $tablesObj->get('address', false);
			if (is_object($addressObj)) {
				$markerArray['###DELIVERY_STORE_SELECT###'] = '';
				$tablename = $addressObj->getTablename();
				$tableconf = $cnf->getTableConf('address', 'INFO');
				$formConf = $cnf->getFormConf('INFO');
				$layout = '';
				if (
					isset($formConf) &&
					is_array($formConf) &&
					isset($formConf['selectStore.']) &&
					isset($formConf['selectStore.']['layout'])
				) {
					$layout = $formConf['selectStore.']['layout'];
				}
				$orderBy = $tableconf['orderBy'];

				$uidStoreArray = array();

				if (isset($this->conf['UIDstore'])) {

					$tmpArray = GeneralUtility::trimExplode(',', $this->conf['UIDstore']);
					foreach ($tmpArray as $value) {
						if ($value) {
							$uidStoreArray[] = $value;
						}
					}
				}

				$where_clause = '';
				if ($tablename == 'fe_users' && $this->conf['UIDstoreGroup'] != '') {
					$orChecks = array();
					$memberGroups = GeneralUtility::trimExplode(',', $this->conf['UIDstoreGroup']);
					foreach ($memberGroups as $value) {
						$orChecks[] = $GLOBALS['TYPO3_DB']->listQuery('usergroup', $value, $tablename);
					}
					$where_clause = implode(' OR ', $orChecks);
				}

				if (is_array($uidStoreArray) && count($uidStoreArray)) {
					if ($where_clause != '') {
						$where_clause .= ' OR ';
					}
					$where_clause .= 'uid IN (' . implode(',', $uidStoreArray) . ')';
				}

				if ($where_clause != '') {
					$addressArray =
						$addressObj->get(
							'',
							0,
							false,
							$where_clause,
							'',
							$orderBy,
							'',
							'',
							false,
							''
						);
					$actUidStore = $this->infoArray['delivery']['store'];
					$tableFieldArray = array(
						'tx_party_addresses' => array('post_code','locality','remarks'),
						'tt_address' => array('zip','city','name','address'),
						'fe_users' => array('zip','city','name','address')
					);
					$valueArray = array();
					if ($addressArray && isset($tableFieldArray[$tablename]) && is_array($tableFieldArray[$tablename])) {
						foreach ($addressArray as $uid => $row) {

							$boxContent = '';
							if ($layout != '') {
								$boxMarkerArray = array();
								foreach ($row as $field => $value) {
									$boxMarkerArray['###' . strtoupper($field) . '###'] = $value;
								}
								$boxContent = $parser->substituteMarkerArray($layout,  $boxMarkerArray);
							} else {
								$partRow = array();
								foreach ($tableFieldArray[$tablename] as $field) {
									$partRow[$field] = $row[$field];
								}
								$boxContent = implode(',', $partRow);
							}
							$valueArray[$uid] = $boxContent;
						}
						$theFormConf = $formConf['selectStore.'];
						$dataArray = $theFormConf['data.'];

						$markerArray['###DELIVERY_STORE_SELECT###'] =
							tx_ttproducts_form_div::createSelect(
								$languageObj,
								$valueArray,
								'recs[delivery][store]',
								$actUidStore,
								true,
								false,
								array(),
								'select',
								$dataArray,
								'',
								'',
								''
							);
					}

					if ($actUidStore && $addressArray[$actUidStore]) {
						$row = $addressArray[$actUidStore];
						foreach ($row as $field => $value) {
							$markerArray['###DELIVERY_' . strtoupper($field) . '###'] = $value;
						}
					}
				}
			}
		}

			// Fe users:
		$markerArray['###FE_USER_TT_PRODUCTS_DISCOUNT###'] = $GLOBALS['TSFE']->fe_user->user['tt_products_discount'];
		$markerArray['###FE_USER_USERNAME###'] = $GLOBALS['TSFE']->fe_user->user['username'];
		$markerArray['###FE_USER_UID###'] = $GLOBALS['TSFE']->fe_user->user['uid'];
		$bAgb = ($this->infoArray['billing']['agb'] && (!isset($this->pibase->piVars['agb']) || $this->pibase->piVars['agb']>0));
		$markerArray['###FE_USER_CNUM###'] = $GLOBALS['TSFE']->fe_user->user['cnum'];
		$markerArray['###PERSON_AGB###'] = 'value="1" '. ($bAgb ? 'checked="checked"' : '');
		$markerArray['###USERNAME###'] = $this->infoArray['billing']['email'];
		$markerArray['###PASSWORD###'] = $this->password;
		$valueArray = $GLOBALS['TCA']['sys_products_orders']['columns']['foundby']['config']['items'];

		$foundbyType = 'radio';
		if (
            isset($this->conf['foundby.']) &&
            isset($this->conf['foundby.']['type'])
        ) {
            $foundbyType = $this->conf['foundby.']['type'];
        }

        if (
            isset($this->conf['foundby.']['hideValue.']) &&
            $this->conf['foundby.']['hideValue.']['0']
        ) {
            unset($valueArray['0']);
        }

		$foundbyText = tx_ttproducts_form_div::createSelect(
			$languageObj,
			$valueArray,
			'recs[delivery][foundby]',
			$this->infoArray['delivery']['foundby'],
			true,
			true,
			array(),
			$foundbyType
		);
// 
		$foundbyKey = $this->infoArray['delivery']['foundby'];
		if (is_array($valueArray[$foundbyKey]))	{
			$tmp = tx_div2007_alpha5::sL_fh002($valueArray[$foundbyKey][0]);
			$text = $languageObj->getLabel($tmp);
		}

		$markerArray['###DELIVERY_FOUNDBY###'] = $text;
		$markerArray['###DELIVERY_FOUNDBY_KEY###'] = $foundbyKey;
		$markerArray['###DELIVERY_FOUNDBY_SELECTOR###'] = $foundbyText;
		$markerArray['###DELIVERY_FOUNDBY_OTHERS###'] = $this->infoArray['delivery']['foundby_others'];
	} // getMarkerArray
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_info_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_info_view.php']);
}

