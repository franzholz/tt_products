<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the order addresses
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_orderaddress_view extends tx_ttproducts_table_base_view
{
    public $dataArray; // array of read in frontend users
    public $table;		 // object of the type tx_table_db
    public $fields = [];
    public $tableconf;
    public $piVar = 'fe';
    public $marker = 'FEUSER';
    public $image;

    public function getWrappedSubpartArray(
        $viewTagArray,
        $useBackPid,
        &$subpartArray,
        &$wrappedSubpartArray
    ): void {
        $marker = 'FE_GROUP';
        $markerLogin = 'LOGIN';
        $markerNologin = 'NOLOGIN';
        foreach ($viewTagArray as $tag => $value) {
            if (strpos($tag, $marker . '_') === 0) {
                $tagPart1 = substr($tag, strlen($marker . '_'));
                $offset = strpos($tagPart1, '_TEMPLATE');
                if ($offset > 0) {
                    $groupNumber = substr($tagPart1, 0, $offset);

                    if (MathUtility::canBeInterpretedAsInteger($groupNumber)) {
                        $comparatorNumber = $groupNumber;
                        if (!$comparatorNumber) {
                            $comparatorNumber = -1; // Also a logged in Front End User has group 0!
                        }

                        if (GeneralUtility::inList(implode(',', GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'groupIds')), $comparatorNumber)) {
                            $wrappedSubpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = ['', ''];
                        } else {
                            $subpartArray['###FE_GROUP_' . $groupNumber . '_TEMPLATE###'] = '';
                        }
                    }
                }
            } elseif (strpos($tag, $markerLogin . '_') === 0) {
                if (
                    CompatibilityUtility::isLoggedIn() &&
                    isset($GLOBALS['TSFE']->fe_user->user) &&
                    is_array($GLOBALS['TSFE']->fe_user->user) &&
                    isset($GLOBALS['TSFE']->fe_user->user['uid'])
                ) {
                    $wrappedSubpartArray['###LOGIN_TEMPLATE###'] = ['', ''];
                } else {
                    $subpartArray['###LOGIN_TEMPLATE###'] = '';
                }
            } elseif (strpos($tag, $markerNologin . '_') === 0) {
                if (
                    isset($GLOBALS['TSFE']->fe_user->user) &&
                    is_array($GLOBALS['TSFE']->fe_user->user) &&
                    isset($GLOBALS['TSFE']->fe_user->user['uid'])
                ) {
                    $subpartArray['###NOLOGIN_TEMPLATE###'] = '';
                } else {
                    $wrappedSubpartArray['###NOLOGIN_TEMPLATE###'] = ['', ''];
                }
            }
        }

        if (
            isset($viewTagArray['FE_CONDITION1_TRUE_TEMPLATE']) ||
            isset($viewTagArray['FE_CONDITION1_FALSE_TEMPLATE'])
        ) {
            if (
                $this->getModelObj()->getCondition() ||
                !$this->getModelObj()->getConditionRecord()
            ) {
                $wrappedSubpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = ['', ''];
                $subpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = '';
            } else {
                $wrappedSubpartArray['###FE_CONDITION1_FALSE_TEMPLATE###'] = ['', ''];
                $subpartArray['###FE_CONDITION1_TRUE_TEMPLATE###'] = '';
            }
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
     *
     * @access private
     */
    public function getAddressMarkerArray(
        $functablename,
        $row,
        &$markerArray,
        $bSelect,
        $type
    ): void {
        $fieldOutputArray = [];
        $modelObj = $this->getModelObj();
        $selectInfoFields = $modelObj->getSelectInfoFields();
        $languageObj = GeneralUtility::makeInstance(Localization::class);

        if ($bSelect) {
            foreach ($selectInfoFields as $field) {
                $tablename = $modelObj->getTCATableFromField($field);
                $fieldOutputArray[$field] =
                    tx_ttproducts_form_div::createSelect(
                        $languageObj,
                        $GLOBALS['TCA'][$tablename]['columns'][$field]['config']['items'],
                        'recs[' . $type . '][' . $field . ']',
                        is_array($row) && isset($row[$field]) ? $row[$field] : '',
                        true,
                        true,
                        [],
                        'select',
                        ['id' => 'field_' . $type . '_' . $field] // Add ID for field to be able to use labels.
                    );
            }
        } else {
            foreach ($selectInfoFields as $field) {
                $tablename = $modelObj->getTCATableFromField($field);
                $itemConfig = $GLOBALS['TCA'][$tablename]['columns'][$field]['config']['items'];

                if (
                    isset($row[$field]) &&
                    $row[$field] != '' &&
                    isset($itemConfig) &&
                    is_array($itemConfig)
                ) {
                    $tcaValue = '';
                    foreach ($itemConfig as $subItemConfig) {
                        if (
                            isset($subItemConfig) &&
                            is_array($subItemConfig) &&
                            $subItemConfig['1'] == $row[$field]
                        ) {
                            $tcaValue = $subItemConfig['0'];
                            break;
                        }
                    }

                    $tmp = $languageObj->splitLabel($tcaValue);
                    $fieldOutputArray[$field] = htmlspecialchars(
                        $languageObj->getLabel(
                            $tmp
                        )
                    );
                } else {
                    $fieldOutputArray[$field] = '';
                }
            }
        }

        foreach ($fieldOutputArray as $field => $fieldOutput) {
            $markerkey = '###' . ($type == 'personinfo' ? 'PERSON' : 'DELIVERY') . '_' . strtoupper($field) . '###';
            $markerArray[$markerkey] = $fieldOutput;
        }
    } // getAddressMarkerArray
}
