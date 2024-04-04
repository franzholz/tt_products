<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * account functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\BasketApi;

class tx_ttproducts_account extends tx_ttproducts_table_base
{
    public $pibase; // reference to object of pibase
    public $conf;
    public $acArray;	// credit card data
    public $bIsAllowed = false; // enable of bank ACCOUNTS
    public $requiredFieldArray = ['owner_name', 'iban', 'ac_number', 'bic'];
    public $tablename = 'sys_products_accounts';
    public $asterisk = '********';
    public $useAsterisk = false;
    public $sepa = true;

    public function init($funcTablename): bool
    {
        $result = true;

        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['sepa']) {
            $this->sepa = true;
            $this->requiredFieldArray = ['owner_name', 'iban'];
            if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['bic']) {
                $this->requiredFieldArray[] = 'bic';
            }
        } else {
            $this->sepa = false;
            $this->requiredFieldArray = ['owner_name', 'ac_number', 'bic'];
        }
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $basketApi = GeneralUtility::makeInstance(BasketApi::class);
        $formerBasket = $basketObj->recs;
        $basketExtra = $basketApi->getBasketExtra();
        $bIsAllowed = $basketExtra['payment.']['accounts'] ?? false;

        if (isset($basketExtra['payment.']['useAsterisk'])) {
            $this->useAsterisk = $basketExtra['payment.']['useAsterisk'];
        }

        $result = parent::init('sys_products_accounts');
        $this->acArray = [];
        $this->acArray = $formerBasket['account'] ?? '';
        if (isset($bIsAllowed)) {
            $this->bIsAllowed = $bIsAllowed;
        }

        $bNumberRecentlyModified = true;

        if (
            !empty($this->sepa) && empty($this->acArray['iban']) ||
            empty($this->sepa) && empty($this->acArray['ac_number'])
        ) {
            $bNumberRecentlyModified = false;
        }

        if ($bNumberRecentlyModified) {
            $acArray = tx_ttproducts_control_session::readSession('ac');
            if (!$acArray) {
                $acArray = [];
            }
            $acArray['ac_uid'] = $this->create($acArray['ac_uid'], $this->acArray);
            $GLOBALS['TSFE']->fe_user->setKey('ses', 'ac', $acArray);
            if ($this->useAsterisk) {
                if (
                    $this->sepa
                ) {
                    $this->acArray['iban'] = $this->asterisk;
                } else {
                    $this->acArray['ac_number'] = $this->asterisk;
                }
            }
        }

        return $result;
    }

    public function getIsAllowed()
    {
        return $this->bIsAllowed;
    }

    // **************************
    // ORDER related functions
    // **************************
    /**
     * Create a new credit card record.
     *
     * This creates a new account record on the page with pid PID_sys_products_orders. This page must exist!
     */
    public function create($uid, $acArray)
    {
        $newId = 0;
        $pid = intval($this->conf['PID_sys_products_orders']);
        if (!$pid) {
            $pid = intval($GLOBALS['TSFE']->id);
        }

        $accountField = 'iban';
        if (
            !$this->sepa
        ) {
            $accountField = 'ac_number';
        }

        if (
            !empty($acArray['owner_name']) &&
            $acArray[$accountField] &&
            $GLOBALS['TSFE']->sys_page->getPage_noCheck($pid)
        ) {
            $time = time();
            $newFields = [
                'pid' => intval($pid),
                'tstamp' => $time,
                'crdate' => $time,
                'owner_name' => $acArray['owner_name'],
                'bic' => $acArray['bic'],
            ];

            if (strcmp($acArray[$accountField], $this->asterisk) != 0) {
                $newFields[$accountField] = $acArray[$accountField];
            }

            if ($uid) {
                $GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->tablename, 'uid=' . $uid, $newFields);
                $newId = $uid;
            } else {
                $GLOBALS['TYPO3_DB']->exec_INSERTquery($this->tablename, $newFields);
                $newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
            }
        }

        return $newId;
    } // create

    public function getUid()
    {
        $result = 0;
        $accountArray = tx_ttproducts_control_session::readSession('ac');
        if (isset($accountArray['ac_uid'])) {
            $result = $accountArray['ac_uid'];
        }

        return $result;
    }

    public function getRow($uid, $bFieldArrayAll = false)
    {
        $result = [];
        if ($bFieldArrayAll) {
            foreach ($this->requiredFieldArray as $k => $field) {
                $result[$field] = '';
            }
        }

        if ($uid) {
            $where = 'uid = ' . intval($uid);
            // Fetching the products
            $fields = '*';
            if ($bFieldArrayAll) {
                $fields = implode(',', $this->requiredFieldArray);
            }
            $tablename = $this->getTablename();
            if ($tablename == '') {
                $tablename = 'sys_products_accounts';
            }
            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $tablename, $where);
            $row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
            if ($row) {
                $result = $row;
            }
        }

        return $result;
    }

    /**
     * Returns the label of the record, Usage in the following format:
     *
     * @return	string		Label of the record
     */
    public function getLabel($row)
    {
        $result = $row['owner_name'] . ':';

        if ($this->sepa) {
            $result .= $row['iban'];
        } else {
            $result .= $row['ac_number'] . ':' . $row['bic'];
        }

        return $result;
    }

    /**
     * Returns the label of the record, Usage in the following format:
     * taken from https://codedump.io/share/uL5GlRG5SjCL/1/validate-iban-php
     * It fits the
     * http://en.wikipedia.org/wiki/International_Bank_Account_Number#Validating_the_IBAN
     *
     * @return	string		Label of the record
     */
    public static function checkIBAN($iban)
    {
        $result = false;

        if (!extension_loaded('bcmath')) {
            throw new RuntimeException('Required PHP module bcmath is not loaded!', 50007);
        }

        $iban = strtolower(str_replace(' ', '', $iban));
        $Countries = [
            'al' => 28, 'ad' => 24, 'at' => 20, 'az' => 28, 'bh' => 22, 'be' => 16, 'ba' => 20, 'br' => 29, 'bg' => 22, 'cr' => 21, 'hr' => 21, 'cy' => 28, 'cz' => 24, 'dk' => 18, 'do' => 28,
            'ee' => 20, 'fo ' => 18, 'fi' => 18, 'fr' => 27, 'ge' => 22, 'de' => 22, 'gi' => 23, 'gr' => 27, 'gl' => 18, 'gt' => 28, 'hu' => 28, 'is' => 26, 'ie' => 22, 'il' => 23, 'it' => 27,
            'jo' => 30, 'kz' => 20, 'kw' => 30, 'lv' => 21, 'lb' => 28, 'li' => 21, 'lt' => 20, 'lu' => 20, 'mk' => 19, 'mt' => 31, 'mr' => 27, 'mu' => 30, 'mc' => 27, 'md' => 24, 'me' => 22, 'nl' => 18, 'no' => 15, 'pk' => 24, 'ps' => 29,
            'pl' => 28, 'pt' => 25, 'qa' => 29, 'ro' => 24, 'sm' => 27, 'sa' => 24, 'rs' => 22, 'sk' => 24, 'si' => 19, 'es' => 24, 'se' => 24, 'ch' => 21, 'tn' => 24, 'tr' => 26, 'ae' => 23, 'gb' => 22, 'vg' => 24];
        $Chars =
            [
                'a' => 10, 'b' => 11, 'c' => 12, 'd' => 13, 'e' => 14, 'f' => 15, 'g' => 16, 'h' => 17, 'i' => 18, 'j' => 19, 'k' => 20, 'l' => 21, 'm' => 22, 'n' => 23, 'o' => 24, 'p' => 25, 'q' => 26, 'r' => 27, 's' => 28, 't' => 29, 'u' => 30, 'v' => 31, 'w' => 32, 'x' => 33, 'y' => 34, 'z' => 35];

        if (strlen($iban) == $Countries[substr($iban, 0, 2)]) {
            $MovedChar = substr($iban, 4) . substr($iban, 0, 4);
            $MovedCharArray = str_split($MovedChar);
            $NewString = '';

            foreach ($MovedCharArray as $key => $value) {
                if (!is_numeric($MovedCharArray[$key])) {
                    $MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
                }
                $NewString .= $MovedCharArray[$key];
            }

            if (bcmod($NewString, '97') == 1) {
                $result = true;
            }
        }

        return $result;
    }

    /**
     * Checks if required fields for bank accounts are filled in.
     */
    public function checkRequired()
    {
        $result = '';
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        if (ExtensionManagementUtility::isLoaded('static_info_tables_banks_de')) {
            $bankObj = $tablesObj->get('static_banks_de');
        }

        foreach ($this->requiredFieldArray as $k => $field) {
            if (!$this->acArray[$field]) {
                $result = $field;
                break;
            }

            $isValid = true;
            switch ($field) {
                case 'iban':
                    $isValid = self::checkIBAN($this->acArray[$field]);
                    break;
                case 'bic':
                    if (
                        is_object($bankObj)
                    ) {
                        $where_clause = 'sort_code=' .
                            intval(implode('', GeneralUtility::trimExplode(' ', $this->acArray[$field]))) . ' AND level=1';
                        $bankRow = $bankObj->get('', 0, false, $where_clause);

                        if (!$bankRow) {
                            $isValid = false;
                        }
                    }
                    break;
            }

            if (!$isValid) {
                $result = $field;
                break;
            }
        }

        return $result;
    } // checkRequired
}
