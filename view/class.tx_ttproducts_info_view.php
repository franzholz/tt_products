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
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 */

use JambageCom\Div2007\Api\OldStaticInfoTablesApi;
use JambageCom\Div2007\Api\StaticInfoTablesApi;
use JambageCom\Div2007\Utility\ExtensionUtility;
use JambageCom\Div2007\Utility\StaticInfoTablesUtility;
use JambageCom\TtProducts\Api\CustomerApi;

use JambageCom\TtProducts\Api\Localization;
use JambageCom\TtProducts\Api\PaymentShippingHandling;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_info_view implements SingletonInterface
{
    public $conf;
    public $config;
    public $infoArray; // elements: 'billing' and 'delivery' addresses
    // contains former basket $personInfo and $deliveryInfo

    public $country;			// object of the type tx_table_db
    public $password;	// automatically generated random password for a new frontend user
    public $bHasBeenInitialised = false;
    protected $modelObj;

    public function init(
        tx_ttproducts_info $modelObj
    ) {
        $result = true;
        $this->modelObj = $modelObj;
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

        $this->conf = $cnf->getConf();
        $this->config = $cnf->getConfig();

        $this->infoArray = tx_ttproducts_control_basket::getInfoArray();

        $this->bHasBeenInitialised = true;

        return $result;
    } // init

    public function getModelObj()
    {
        return $this->modelObj;
    }

    public function needsInit()
    {
        return !$this->bHasBeenInitialised;
    }

    public function getInfoArray()
    {
        return $this->getModelObj()->getInfoArray();
    }

    public function getSubpartMarkerArray(
        array &$subpartArray,
        array &$wrappedSubpartArray,
        $viewTagArray
    ): void {
        $modelObj = $this->getModelObj();
        $areIdentical = $modelObj->billingEqualsShipping();
        $identicalMarkerKey = 'BILLING_EQUALS_SHIPPING';
        $notIdenticalMarkerKey = 'BILLING_EQUALS_NOT_SHIPPING';
        if ($areIdentical) {
            $wrappedSubpartArray['###' . $identicalMarkerKey . '###'] = '';
            $subpartArray['###' . $notIdenticalMarkerKey . '###'] = '';
        } else {
            $subpartArray['###' . $identicalMarkerKey . '###'] = '';
            $wrappedSubpartArray['###' . $notIdenticalMarkerKey . '###'] = '';
        }

        $infoArray = $modelObj->getInfoArray();
        $fields = $modelObj->getFields();
        $fieldArray = explode(',', $fields);
        $typeArray = ['billing', 'delivery'];
        foreach ($typeArray as $type) {
            foreach ($fieldArray as $field) {
                $hasMarkerKey = strtoupper($type) . '_' . strtoupper($field) . '_NOT_EMPTY';
                $hasNotMarkerKey = strtoupper($type) . '_' . strtoupper($field) . '_EMPTY';
                if (!isset($viewTagArray[$hasMarkerKey])) {
                    $hasMarkerKey = '';
                }
                if (!isset($viewTagArray[$hasNotMarkerKey])) {
                    $hasNotMarkerKey = '';
                }

                if (
                    isset($infoArray[$type]) &&
                    isset($infoArray[$type][$field]) &&
                    !empty($infoArray[$type][$field])
                ) {
                    if ($hasMarkerKey != '') {
                        $wrappedSubpartArray['###' . $hasMarkerKey . '###'] = '';
                    }
                    if ($hasNotMarkerKey != '') {
                        $subpartArray['###' . $hasNotMarkerKey . '###'] = '';
                    }
                } else {
                    if ($hasNotMarkerKey != '') {
                        $wrappedSubpartArray['###' . $hasNotMarkerKey . '###'] = '';
                    }
                    if ($hasMarkerKey != '') {
                        $subpartArray['###' . $hasMarkerKey . '###'] = '';
                    }
                }
            }
        }
        $checkbox1MarkerKey = 'CHECKBOX1_CHECKED';
        if (
            !empty($infoArray['delivery']['checkbox1'])
        ) {
            $wrappedSubpartArray['###' . $checkbox1MarkerKey . '###'] = '';
        } else {
            $subpartArray['###' . $checkbox1MarkerKey . '###'] = '';
        }
    }

    /**
     * Template marker substitution
     * Fills in the markerArray with data for a product.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	string		title of the category
     * @param	int		number of images to be shown
     * @param	object		the image cObj to be used
     * @param	array		information about the parent HTML form
     *
     * @return	array
     */
    public function getRowMarkerArray(
        $basketExtra,
        &$markerArray,
        $bHtml,
        $bSelectSalutation
    ): void {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $context = GeneralUtility::makeInstance(Context::class);

        $fields = CustomerApi::getFields();
        $infoFields = GeneralUtility::trimExplode(',', $fields); // Fields...
        $orderAddressViewObj = $tablesObj->get('fe_users', true);
        $orderAddressObj = $orderAddressViewObj->getModelObj();
        $selectInfoFields = $orderAddressObj->getSelectInfoFields();
        $piVars = tx_ttproducts_model_control::getPiVars();
        if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            $staticInfoApi = GeneralUtility::makeInstance(StaticInfoTablesApi::class);
        } else {
            $staticInfoApi = GeneralUtility::makeInstance(OldStaticInfoTablesApi::class);
        }

        foreach ($infoFields as $k => $fName) {
            if (!in_array($fName, $selectInfoFields)) {
                $fieldMarker = strtoupper($fName);
                if ($bHtml) {
                    $markerArray['###PERSON_' . $fieldMarker . '###'] =
                    htmlspecialchars($this->infoArray['billing'][$fName] ?? '');
                    $markerArray['###DELIVERY_' . $fieldMarker . '###'] =
                    htmlspecialchars($this->infoArray['delivery'][$fName] ?? '');
                } else {
                    $markerArray['###PERSON_' . $fieldMarker . '###'] =
                    $this->infoArray['billing'][$fName] ?? '';
                    $markerArray['###DELIVERY_' . $fieldMarker . '###'] =
                    $this->infoArray['delivery'][$fName] ?? '';
                }
            }
        }

        if (!empty($this->conf['useStaticInfoCountry']) && $staticInfoApi->isActive()) {
            $countryViewObj = $tablesObj->get('static_countries', true);
            $countryObj = $countryViewObj->getModelObj();

            $bReady = false;
            $whereCountries = $this->getModelObj()->getWhereAllowedCountries($basketExtra);
            $countryCodeArray = [];
            $countryCodeArray['billing'] = ($this->infoArray['billing']['country_code'] ?? $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && !empty($GLOBALS['TSFE']->fe_user->user['static_info_country']) ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : false));
            $countryCodeArray['delivery'] = ($this->infoArray['delivery']['country_code'] ?? ($context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && !empty($GLOBALS['TSFE']->fe_user->user['static_info_country']) ? $GLOBALS['TSFE']->fe_user->user['static_info_country'] : false));

            $zoneCodeArray = [];
            $zoneCodeArray['billing'] = (
                !empty($this->infoArray['billing']['zone']) ?
                    $this->infoArray['billing']['zone'] : (
                        $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && !empty($GLOBALS['TSFE']->fe_user->user['zone']) ?
                            $GLOBALS['TSFE']->fe_user->user['zone'] :
                            false
                    )
            );
            $zoneCodeArray['delivery'] = (
                !empty($this->infoArray['delivery']['zone']) ?
                    $this->infoArray['delivery']['zone'] : (
                        $context->getPropertyFromAspect('frontend.user', 'isLoggedIn') && !empty($GLOBALS['TSFE']->fe_user->user['zone']) ?
                            $GLOBALS['TSFE']->fe_user->user['zone'] :
                            false
                    )
            );

            if (
                $countryCodeArray['billing'] === false &&
                !empty($this->infoArray['billing']['country']) &&
                !empty($this->infoArray['delivery']['country'])
            ) {
                // nothing to do
                $bReady = true;
            } elseif (
                ExtensionManagementUtility::isLoaded('static_info_tables')
            ) {
                $eInfo = ExtensionUtility::getExtensionInfo('static_info_tables');
                $sitVersion = $eInfo['version'];

                if (version_compare($sitVersion, '2.0.1', '>=')) {
                    $markerArray['###PERSON_COUNTRY_CODE###'] =
                        $staticInfoApi->buildStaticInfoSelector(
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
                            [],
                            1,
                            $outSelectedArray
                        );

                    if (isset($outSelectedArray) && is_array($outSelectedArray)) {
                        $countryCode = current($outSelectedArray);
                        $markerArray['###PERSON_ZONE###'] =
                            $staticInfoApi->buildStaticInfoSelector(
                                'SUBDIVISIONS',
                                'recs[personinfo][zone]',
                                '',
                                $zoneCodeArray['billing'],
                                $countryCode,
                                0,
                                '',
                                '',
                                '',
                                ''
                            );
                        $countryRow = $countryObj->isoGet($countryCode);
                        $countryViewObj->getRowMarkers($markerArray, 'PERSON', $countryRow);
                    } else {
                        $markerArray['###PERSON_ZONE###'] = '';
                    }
                    $countryArray = $staticInfoApi->initCountries('ALL', '', false, $whereCountries);
                    $markerArray['###PERSON_COUNTRY_FIRST###'] = current($countryArray);
                    $markerArray['###PERSON_COUNTRY_FIRST_HIDDEN###'] = '<input type="hidden" name="recs[personinfo][country_code]" size="3" value="' . current(array_keys($countryArray)) . '">';

                    $markerArray['###PERSON_COUNTRY###'] =
                        $staticInfoApi->getStaticInfoName($countryCodeArray['billing'], 'COUNTRIES', '', '');
                    unset($outSelectedArray);

                    $markerArray['###DELIVERY_COUNTRY_CODE###'] =
                        $staticInfoApi->buildStaticInfoSelector(
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
                            [],
                            1,
                            $outSelectedArray
                        );

                    if (isset($outSelectedArray) && is_array($outSelectedArray)) {
                        $countryCode = current($outSelectedArray);
                        $markerArray['###DELIVERY_ZONE###'] =
                            $staticInfoApi->buildStaticInfoSelector(
                                'SUBDIVISIONS',
                                'recs[delivery][zone]',
                                '',
                                $zoneCodeArray['billing'],
                                $countryCode,
                                0,
                                '',
                                '',
                                '',
                                ''
                            );
                        $countryRow = $countryObj->isoGet($countryCode);
                        $countryViewObj->getRowMarkers(
                            $markerArray,
                            'DELIVERY',
                            $countryRow
                        );
                    } else {
                        $markerArray['###DELIVERY_ZONE###'] = '';
                    }

                    $markerArray['###DELIVERY_COUNTRY_FIRST###'] = $markerArray['###PERSON_COUNTRY_FIRST###'];
                    $markerArray['###DELIVERY_COUNTRY###'] =
                        $staticInfoApi->getStaticInfoName(
                            $countryCodeArray['delivery'],
                            'COUNTRIES',
                            '',
                            ''
                        );
                    $bReady = true;
                }

                $markerArray['###PERSON_ZONE_DISPLAY###'] =
                    StaticInfoTablesUtility::getTitleFromIsoCode(
                        'static_country_zones',
                        [
                            $zoneCodeArray['billing'],
                            $countryCodeArray['billing'],
                        ]
                    );
                $markerArray['###DELIVERY_ZONE_DISPLAY###'] =
                    StaticInfoTablesUtility::getTitleFromIsoCode(
                        'static_country_zones',
                        [
                            $zoneCodeArray['delivery'],
                            $countryCodeArray['delivery'],
                        ]
                    );
            }

            if (!$bReady) {
                $markerArray['###PERSON_COUNTRY_CODE###'] =
                    $staticInfoApi->buildStaticInfoSelector(
                        'COUNTRIES',
                        'recs[personinfo][country_code]',
                        '',
                        $countryCodeArray['billing'],
                        '',
                        0,
                        'field_personinfo_country_code'
                    );
                $markerArray['###PERSON_COUNTRY###'] =
                    $staticInfoApi->getStaticInfoName(
                        $countryCodeArray['billing'],
                        'COUNTRIES',
                        '',
                        ''
                    );

                $markerArray['###DELIVERY_COUNTRY_CODE###'] =
                    $staticInfoApi->buildStaticInfoSelector(
                        'COUNTRIES',
                        'recs[delivery][country_code]',
                        '',
                        $countryCodeArray['delivery'],
                        '',
                        0,
                        'field_delivery_country_code'
                    );
                $markerArray['###DELIVERY_COUNTRY###'] =
                    $staticInfoApi->getStaticInfoName(
                        $countryCodeArray['delivery'],
                        'COUNTRIES',
                        '',
                        ''
                    );
            }
        }

        // Markers for use if you want to output line-broken address information
        $markerArray['###PERSON_ADDRESS_DISPLAY###'] = nl2br($markerArray['###PERSON_ADDRESS###']);
        $markerArray['###DELIVERY_ADDRESS_DISPLAY###'] = nl2br($markerArray['###DELIVERY_ADDRESS###']);

        $orderAddressViewObj->getAddressMarkerArray(
            'fe_users',
            $this->infoArray['billing'],
            $markerArray,
            $bSelectSalutation,
            'personinfo'
        );

        $orderAddressViewObj->getAddressMarkerArray(
            'fe_users',
            $this->infoArray['delivery'],
            $markerArray,
            $bSelectSalutation,
            'delivery'
        );

        $text = $this->infoArray['delivery']['note'] ?? '';
        $markerArray['###DELIVERY_NOTE###'] = $text;
        $markerArray['###DELIVERY_NOTE_DISPLAY###'] = nl2br($text);
        $markerArray['###DELIVERY_GIFT_SERVICE###'] = $this->infoArray['delivery']['giftservice'] ?? '';
        $markerArray['###DELIVERY_GIFT_SERVICE_DISPLAY###'] = nl2br($this->infoArray['delivery']['giftservice'] ?? '');
        if (isset($this->infoArray['delivery']['radio1'])) {
            $markerArray['###DELIVERY_RADIO1_1###'] = ($this->infoArray['delivery']['radio1'] == '1' ? 'checked ' : '');
            $markerArray['###DELIVERY_RADIO1_2###'] = ($this->infoArray['delivery']['radio1'] == '2' ? 'checked ' : '');
            $markerArray['###DELIVERY_RADIO1_DISPLAY###'] = $this->infoArray['delivery']['radio1'];
        }

        // Desired delivery date.
        $markerArray['###DELIVERY_DESIRED_DATE###'] = $this->infoArray['delivery']['desired_date'] ?? '';
        $markerArray['###DELIVERY_DESIRED_TIME###'] = $this->infoArray['delivery']['desired_time'] ?? '';
        $markerArray['###DELIVERY_STORE_SELECT###'] = '';

        $shippingType = PaymentShippingHandling::get(
            'shipping',
            'type',
            $basketExtra
        );

        if ($shippingType == 'pick_store') {
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
                $uidStoreArray = [];

                if (isset($this->conf['UIDstore'])) {
                    $tmpArray = GeneralUtility::trimExplode(',', $this->conf['UIDstore']);
                    foreach ($tmpArray as $value) {
                        if ($value) {
                            $uidStoreArray[] = $value;
                        }
                    }
                }

                $where_clause = '';
                if ($tablename == 'fe_users' && !empty($this->conf['UIDstoreGroup'])) {
                    $orChecks = [];
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
                    $tableFieldArray = [
                        'tx_party_addresses' => ['post_code', 'locality', 'remarks'],
                        'tt_address' => ['zip', 'city', 'name', 'address'],
                        'fe_users' => ['zip', 'city', 'name', 'address'],
                    ];
                    $valueArray = [];
                    if ($addressArray && isset($tableFieldArray[$tablename]) && is_array($tableFieldArray[$tablename])) {
                        foreach ($addressArray as $uid => $row) {
                            $boxContent = '';
                            if ($layout != '') {
                                $boxMarkerArray = [];
                                foreach ($row as $field => $value) {
                                    $boxMarkerArray['###' . strtoupper($field) . '###'] = $value;
                                }
                                $boxContent = $templateService->substituteMarkerArray($layout, $boxMarkerArray);
                            } else {
                                $partRow = [];
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
                                [],
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
        $markerArray['###FE_USER_TT_PRODUCTS_DISCOUNT###'] = $GLOBALS['TSFE']->fe_user->user['tt_products_discount'] ?? '';
        $markerArray['###FE_USER_USERNAME###'] = $GLOBALS['TSFE']->fe_user->user['username'] ?? '';
        $markerArray['###FE_USER_UID###'] = $GLOBALS['TSFE']->fe_user->user['uid'] ?? '';
        $bAgb = (isset($this->infoArray['billing']['agb']) && $this->infoArray['billing']['agb'] && (!isset($piVars['agb']) || $piVars['agb'] > 0));
        $markerArray['###FE_USER_CNUM###'] = $GLOBALS['TSFE']->fe_user->user['cnum'] ?? '';
        $markerArray['###PERSON_AGB###'] = 'value="1" ' . ($bAgb ? 'checked="checked"' : '');
        $markerArray['###USERNAME###'] = $this->infoArray['billing']['email'] ?? '';
        $markerArray['###PASSWORD###'] = $this->password;
        $valueArray = $GLOBALS['TCA']['sys_products_orders']['columns']['foundby']['config']['items'];

        $foundbyType = 'radio';
        if (
            isset($conf['foundby.']) &&
            isset($conf['foundby.']['type'])
        ) {
            $foundbyType = $conf['foundby.']['type'];
        }

        if (
            isset($conf['foundby.']['hideValue.']) &&
            $conf['foundby.']['hideValue.']['0']
        ) {
            unset($valueArray['0']);
        }

        $foundbyText = tx_ttproducts_form_div::createSelect(
            $languageObj,
            $valueArray,
            'recs[delivery][foundby]',
            $this->infoArray['delivery']['foundby'] ?? '',
            true,
            true,
            [],
            $foundbyType
        );

        $foundbyKey = $this->infoArray['delivery']['foundby'] ?? '';
        if (isset($valueArray[$foundbyKey])) {
            $tmp = $languageObj->splitLabel($valueArray[$foundbyKey][0]);
            $text = $languageObj->getLabel($tmp);
        }

        $markerArray['###DELIVERY_FOUNDBY###'] = $text;
        $markerArray['###DELIVERY_FOUNDBY_KEY###'] = $foundbyKey;
        $markerArray['###DELIVERY_FOUNDBY_SELECTOR###'] = $foundbyText;
        $markerArray['###DELIVERY_FOUNDBY_OTHERS###'] = $this->infoArray['delivery']['foundby_others'] ?? '';
    } // getMarkerArray
}
