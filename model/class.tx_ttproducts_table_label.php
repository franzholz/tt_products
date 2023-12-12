<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2013-2016 Franz Holzinger <franz@ttproducts.de>
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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Part of the tt_products (Shop System) extension.
 *
 * label functions for the tables
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_table_label
{
    /**
     * Factory function which is called by label_userFunc. The function decides how to build the label.
     * The result is directly written to $params['title'], since this parameter is passed by reference.
     *
     * The function gets the label from the proper model.
     *
     * @return	string		The result is also written into $params['title']
     */
    public function getLabel(
        &$params,
        $pObj
    ) {
        // Only get labels for tt_products* tables
        if (
            !substr($params['table'], 0, 11) == 'tt_products' &&
            !substr($params['table'], 0, 12) == 'sys_products'
        ) {
            return '';
        }

        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');

        // Init
        $label = '';
        $tablename = $params['table'];
        $className = $tablesObj->gettableClass($tablename);

        // Get the label from the model
        if ($className) {
            $model = GeneralUtility::makeInstance($className);
            $row =
                $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', $tablename, 'uid=' . intval($params['row']['uid']));
            $label = $model->getLabel($row);
        }

        // Write new label back to the params-array (passed by reference)
        if ($label != '') {
            $params['title'] = $label;
        }

        return $label;
    }
}
