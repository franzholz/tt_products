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

class MoveItemsUtility {

    static public function moveAll (
        &$infoArray,
        $currId,
        $destId,
        $age
    ) {
        $result = true;
        $infoArray = array();

        if($currId && $destId) {
            $infoArray['rows'] = array();
            $fieldsArray = array();
            $fieldsArray['pid'] = intval($destId);
            $day = 24 * 60 * 60;
            $time = time();
            $tstamplimit = $time - $age * $day;
            $tableArray = array('tt_products', 'tt_products_articles');

            foreach ($tableArray as $table) {

                $GLOBALS['TYPO3_DB']->exec_UPDATEquery($table, 'pid=' . intval($currId) . ' AND tstamp<' . $tstamplimit, $fieldsArray);
                $count = $GLOBALS['TYPO3_DB']->sql_affected_rows();
                if ($count) {
                    $infoArray['rows'][$table] = $count;
                }
            }
        }

        return $result;
    }
}

