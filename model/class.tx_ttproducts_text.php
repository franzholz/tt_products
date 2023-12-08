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
 * functions for additional texts
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_text extends tx_ttproducts_table_base {

    public function getChildUidArray (
        $theCode,
        $uid,
        $tagMarkerArray,
        $parenttable = 'tt_products'
    ) {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');
        $functablename = $this->getFuncTablename();
        $fallback = false;
        $tableConf = $cnf->getTableConf($functablename, $theCode);
        $fallback = $cnf->getFallback($tableConf);

        $resultArray = [];
        $tagWhere = '';

        if (is_array($tagMarkerArray) && count($tagMarkerArray)) {
            $tagMarkerArray = $GLOBALS['TYPO3_DB']->fullQuoteArray(
                $tagMarkerArray,
                $this->getTableObj()->getName()
            );
            $tags = implode(',',$tagMarkerArray);
            $tagWhere = ' AND marker IN (' . $tags . ')';
        }

        $where_clause = 'parentid = ' . intval($uid) . ' AND parenttable=' . $GLOBALS['TYPO3_DB']->fullQuoteStr(
            $parenttable,
            $this->getTableObj()->getName()
        ) .
            $tagWhere;

        $resultArray =
            $this->get(
                '',
                '',
                false,
                $where_clause,
                '', // $groupBy
                '', // $orderBy
                '', // $limit
                '', // $fields
                false, // $bCount
                '', // $aliasPostfix
                true, // $bUseEnableFields
                $fallback
            );

        return $resultArray;
    }
}

