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
 * search box display functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_search_view implements SingletonInterface
{
    public $conf;
    public $config;

    public function init()
    {
        $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

        $this->conf = $cnf->getConf();
        $this->config = $cnf->getConfig();
    }

    /**
     * Displays the search for the first letter.
     */
    public function &printFirstletter($pibaseObj, &$templateCode, $columns, &$error_code)
    {
        // local_table

        /*		$ctrlArray = GeneralUtility::makeInstance('tx_ttproducts_model_control');
                $ctrlArray = tx_ttproducts_model_control::$tableParamsArray;*/

        $searboxViewObj = GeneralUtility::makeInstance('tx_searchbox_view');

        $paramArray = [
            'local' => ['table' => $this->config['local_table'], 'param' => $this->config['local_param']],
            'foreign' => ['table' => $this->config['foreign_table'], 'param' => $this->config['foreign_param']],
        ];
        $rc = $searboxViewObj->printFirstletter($pibaseObj, $pibaseObj->prefixId, $this->conf['PIDlistDisplay'], $templateCode, $columns, $paramArray, $this->config['fields'], $this->config['group_by_fields'], $this->config['all'], $this->config['delimiter'], $error_code);

        return $rc;
    }

    /**
     * Displays the search for the year.
     */
    public function printYear($pibaseObj, &$templateCode, $columns, &$error_code)
    {
        $searboxViewObj = GeneralUtility::makeInstance('tx_searchbox_view');
        $paramArray = [
            'local' => ['table' => $this->config['local_table'], 'param' => $this->config['local_param']],
            'foreign' => ['table' => $this->config['foreign_table'], 'param' => $this->config['foreign_param']],
        ];
        $rc = $searboxViewObj->printYear($pibaseObj, $pibaseObj->prefixId, $this->conf['PIDlistDisplay'], $templateCode, $columns, $paramArray, $this->config['parameters'], $this->config['fields'], $this->config['all'], $error_code);

        return $rc;
    }

    /**
     * Displays the search for the key field.
     */
    public function printKeyField($pibaseObj, &$templateCode, $columns, $type, $formid, $keyfieldConf, &$error_code)
    {
        $searboxViewObj = GeneralUtility::makeInstance('tx_searchbox_view');
        $paramArray = [
            'local' => ['table' => $this->config['local_table'], 'param' => $this->config['local_param']],
            'foreign' => ['table' => $this->config['foreign_table'], 'param' => $this->config['foreign_param']],
        ];

        $rc = $searboxViewObj->printKeyField($pibaseObj, $pibaseObj->prefixId, $this->conf['PIDlistDisplay'], $templateCode, $columns, $paramArray, $this->config['parameters'], $this->config['fields'], $type, $this->config['url'], $this->config['all'], $keyfieldConf, $formid, $error_code);

        return $rc;
    }

    /**
     * Displays the search for the last entries.
     */
    public function &printLastEntries($pibaseObj, &$templateCode, $columns, &$error_code)
    {
        $searboxViewObj = GeneralUtility::makeInstance('tx_searchbox_view');
        $paramArray = [
            'local' => ['table' => $this->config['local_table'], 'param' => $this->config['local_param']],
            'foreign' => ['table' => $this->config['foreign_table'], 'param' => $this->config['foreign_param']],
        ];
        $rc = $searboxViewObj->printLastEntries($pibaseObj, $pibaseObj->prefixId, $this->conf['PIDlistDisplay'], $templateCode, $columns, $paramArray, $this->config['parameters'], $this->config['fields'], $error_code);

        return $rc;
    }

    /**
     * Displays the search for the last entries.
     */
    public function &printTextField($pibaseObj, &$templateCode, $columns, $formid, $contentRow, &$error_code)
    {
        $searboxViewObj = GeneralUtility::makeInstance('tx_searchbox_view');
        $paramArray = [
            'local' => ['table' => $this->config['local_table'], 'param' => $this->config['local_param']],
            'foreign' => ['table' => $this->config['foreign_table'], 'param' => $this->config['foreign_param']],
        ];
        $rc = $searboxViewObj->printTextField($pibaseObj, $pibaseObj->prefixId, $this->conf['PIDlistDisplay'], $templateCode, $columns, $paramArray, $this->config['parameters'], $this->config['fields'], $this->config['url'], $formid, $contentRow['uid'], $error_code);

        return $rc;
    }
}
