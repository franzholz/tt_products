<?php

namespace JambageCom\TtProducts\Utility;

/***************************************************************
*  Copyright notice
*
*  (c) 2017 Franz Holzinger <franz@ttproducts.de>
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
 * functions for the import of images into FAL
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

class CreateLanguagesUtility {

    static public function createAll (
        &$infoArray,
        &$errorLanguageCodeArray,
        $currId
    ) {
        debug($tmp, 'moveAll ');
        debug ($_REQUEST, '$_REQUEST');
        $result = true;
        $infoArray = array();

        debug ($currId, '$currId');

        if($currId) {
            $tableArray = array('tt_products_texts' => 'tt_products_texts_language');
                // Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
            $standardFields = array('pid', 'cruser_id', 'hidden', 'starttime', 'endtime', 'fe_group');

            $infoArray['rows'] = array();
            $time = time();
            $fieldsArray = array();
            $pid = intval($currId);

            foreach ($tableArray as $table => $languageTable) {
                $fieldsArray['tstamp'] = $fieldsArray['crdate'] = $time;
                $uidNotFoundArray = array();
                $errorLanguageCodeArray[$table] = array();

                $rowArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    '*',
                    $table,
                    'pid=' . $pid . ' AND deleted=0'
                );

                if (is_array($rowArray) && count($rowArray)) {

                    foreach ($rowArray as $row) {
                        $insertArray = $fieldsArray;
                        foreach ($row as $field => $value) {
                            if (
                                in_array($field, $standardFields) ||
                                isset($GLOBALS['TCA'][$table]['columns'][$field]) &&
                                isset($GLOBALS['TCA'][$languageTable]['columns'][$field])
                            ) {
                                $insertArray[$field] = $value;
                            } else {
                                // nothing
                            }
                        }
                        $insertArray['text_uid'] = $row['uid'];

                        if ($table == 'tt_products_texts') {
                            $insertArray['parenttable'] = 'tt_products_language';

                            // determine parentid
                            $foreignRowArray =
                                $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                    '*',
                                    'tt_products_language',
                                    'prod_uid=' . $row['parentid']
                                );

                            if ($foreignRowArray) {
                                foreach ($foreignRowArray as $foreignRow) {
                                    $updateForeignRow = 0;
                                    $insertArray['pid'] = $foreignRow['pid'];
                                    $insertArray['parentid'] = $foreignRow['uid'];
                                    $insertArray['sys_language_uid'] = $foreignRow['sys_language_uid'];

                                    $where = 'text_uid=' . $insertArray['text_uid'] . ' AND sys_language_uid=' . $insertArray['sys_language_uid'];

                                    $currentRow =
                                        $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                                            '*',
                                            $languageTable,
                                            $where
                                        );

                                    if ($currentRow) {
                                        if ($currentRow['parentid'] == 0) {
                                            $updateArray = array();
                                            $updateArray['parentid'] = $insertArray['parentid'];
                                            $updateArray['parenttable'] = $insertArray['parenttable'];
                                            $updateArray['tstamp'] = $insertArray['tstamp'];
                                            $where = 'uid=' . $currentRow['uid'];
                                            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                                                $languageTable,
                                                $where,
                                                $updateArray
                                            );
                                            $error = $GLOBALS['TYPO3_DB']->sql_error();
                                            $updateForeignRow = $currentRow['uid'];
                                        }
                                    } else {
                                        $GLOBALS['TYPO3_DB']->exec_INSERTquery(
                                            $languageTable,
                                            $insertArray
                                        );
                                        $updateForeignRow = $GLOBALS['TYPO3_DB']->sql_insert_id();
                                    }

                                    if ($updateForeignRow) {
                                        $where = 'uid=' . $foreignRow['uid'];
                                        $updateArray = array();
                                        $updateArray['tstamp'] = $insertArray['tstamp'];
                                        $updateArray['text_uid'] += 1;
                                        $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                                            'tt_products_language',
                                            $where,
                                            $updateArray
                                        );
                                    }
                                }
                            } else {
                                $uidNotFoundArray[] = $row['parentid'];
                            }
                        }
                    }

                    if (
                        is_array($uidNotFoundArray) && count($uidNotFoundArray)
                    ) {
                        $errorLanguageCodeArray[$table]['code'] = 'no_alternative_product';

                        $errorLanguageCodeArray[$table]['parameter'] = implode(',', $uidNotFoundArray);
                    }
                } else {
                    $errorLanguageCodeArray[$table]['code'] = 'no_texts';
                    $result = false;
                }
            }
        }

        debug ($theOutput, 'MODFUNC1 ENDE $result');
        debug ($infoArray, 'MODFUNC1 ENDE $infoArray');
        return $result;
    }
}


