<?php
namespace JambageCom\TtProducts\Slots;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


/**
 * Class for slots to signals from the rendering of the Web>List module
 */
class DatabaseRecordListSlots
{
    /**
     * Adds input row of values to the internal csvLines array as a CSV formatted line
     *
     * @param string $table name of the table
     * @param mixed[] $csvRow Array with values to be listed.
     * @param boolean $header true, if it is the header row
     * @return mixed[] Array with changed values to be listed
     */
    public function addValuesToCsvRow ($table, $csvRow, $header)
    {
        $result = false;
        $useCsv = false;
        if (
            $table == 'sys_products_orders' &&
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT])
        ) {
            $useCsv = 
                (
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['backend']) &&
                    isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['backend']['csv']) &&
                    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['backend']['csv'][$table] == 'iban' 
                );
        }

        if (
            $useCsv
        ) {
            $newField = 'iban';

            if ($header) {
                $csvRow[$newField] = $newField;
            } else {
                $newValue = '';

                if (
                    !empty($csvRow['ac_uid']) &&
                    $csvRow['ac_uid'] != ':'
                ) {
                    $valueArray = explode (':', $csvRow['ac_uid']);
                    $newValue = $valueArray['1'];
                }
                $csvRow[$newField] = $newValue;
            }
            $result = array($table, $csvRow, $header);
        }

        if ($result) {
            return $result;
        }
    }
}


