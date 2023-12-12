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
 * credit card functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\TtProducts\Api\Localization;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_card_view extends tx_ttproducts_table_base_view
{
    public $marker = 'CARD';

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
    public function getMarkerArray($row, &$markerArray, array $allowedArray, $tablename = 'sys_products_cards')
    {
        $languageObj = GeneralUtility::makeInstance(Localization::class);
        $cObj = FrontendUtility::getContentObjectRenderer();
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $conf = $cnf->getConf();
        $ccNumberArray = [];
        $ccTypeTextSelected = '';

        if (count($allowedArray)) {
            $ccTypeText =
                tx_ttproducts_form_div::createSelect(
                    $languageObj,
                    $GLOBALS['TCA'][$tablename]['columns']['cc_type']['config']['items'],
                    'recs[creditcard][cc_type]',
                    $row['cc_type'] ?? '',
                    true,
                    true,
                    $allowedArray
                );
        } else {
            $ccTypeText = '';
        }

        if (is_array($row)) {
            for ($i = 1; $i <= 4; ++$i) {
                $value = '';
                if (isset($row['cc_number_' . $i])) {
                    $value = $row['cc_number_' . $i];
                } elseif (isset($row['cc_number'])) {
                    $value = substr($row['cc_number'], ($i - 1) * 4, 4);
                }
                $ccNumberArray[$i - 1] = $value;
            }
        }
        $ccOwnerName = $row['owner_name'] ?? '';

        $markerArray['###PERSON_CARDS_OWNER_NAME###'] = htmlentities($ccOwnerName, ENT_QUOTES, 'UTF-8');
        $markerArray['###PERSON_CARDS_CC_TYPE###'] = $ccTypeText;
        $markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $row['cc_type'] ?? '';
        if (isset($row['cc_type'])) {
            $tmp = $GLOBALS['TCA'][$tablename]['columns']['cc_type']['config']['items'][$row['cc_type']]['0'];
            $tmp = $languageObj->splitLabel($tmp);
            $ccTypeTextSelected = $languageObj->getLabel($tmp);
        }
        $markerArray['###PERSON_CARDS_CC_TYPE_SELECTED###'] = $ccTypeTextSelected;
        for ($i = 1; $i <= 4; ++$i) {
            $markerArray['###PERSON_CARDS_CC_NUMBER_' . $i . '###'] = $ccNumberArray[$i - 1];
        }
        $markerArray['###PERSON_CARDS_CC_NUMBER###'] = $row['cc_number'] ?? '';
        $markerArray['###PERSON_CARDS_CVV2###'] = $row['cvv2'] ?? '';

        $month = '';
        $year = '';

        if (isset($row['endtime'])) {
            $dateArray = explode('-', strftime('%d-%m-%Y', $row['endtime']));
            if (isset($row['endtime_mm'])) {
                $month = $row['endtime_mm'];
            } else {
                $month = $dateArray['1'];
            }

            if (isset($row['endtime_yy'])) {
                $year = $row['endtime_yy'];
            } else {
                $year = substr($dateArray['2'], 2, 2);
            }
        }

        $markerArray['###PERSON_CARDS_ENDTIME_MM###'] = $month;
        $markerArray['###PERSON_CARDS_ENDTIME_YY###'] = $year;
        $markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] = '';
        $markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] = '';
        $markerArray['###PERSON_CARDS_ENDTIME###'] = isset($row['endtime']) ? $cObj->stdWrap($row['endtime'], $conf['cardEndDate_stdWrap.']) : '';

        if (isset($conf['payment.']['creditcardSelect.'])) {
            $mmArray = $conf['payment.']['creditcardSelect.']['mm.'];
            if (is_array($mmArray)) {
                $valueArray = tx_ttproducts_form_div::fetchValueArray($mmArray['valueArray.']);
                $markerArray['###PERSON_CARDS_ENDTIME_MM_SELECT###'] =
                    tx_ttproducts_form_div::createSelect(
                        $languageObj,
                        $valueArray,
                        'recs[creditcard][endtime_mm]',
                        $month,
                        true,
                        true
                    );
            }
            $yyArray = $conf['payment.']['creditcardSelect.']['yy.'];
            if (is_array($yyArray)) {
                $valueArray = tx_ttproducts_form_div::fetchValueArray($yyArray['valueArray.']);
                $markerArray['###PERSON_CARDS_ENDTIME_YY_SELECT###'] =
                    tx_ttproducts_form_div::createSelect(
                        $languageObj,
                        $valueArray,
                        'recs[creditcard][endtime_yy]',
                        $year,
                        true,
                        true
                    );
            }
        }
    } // getMarkerArray
}
