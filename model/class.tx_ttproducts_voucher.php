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
 * functions for the voucher system
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use JambageCom\Div2007\Utility\TableUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_voucher extends tx_ttproducts_table_base
{
    protected $amount = 0;
    protected $amountType;
    protected $voucherCode;
    protected $bValid = false;
    protected $marker = 'VOUCHER';
    protected $usedVoucherCodeArray = [];

    /**
     * Getting all voucher codes into internal array.
     */
    public function init($funcTablename): bool
    {
        $result = false;
        if (ExtensionManagementUtility::isLoaded('voucher')) {
            $result = parent::init($funcTablename);
        }

        if ($result) {
            $usedVoucherCodeArray = tx_ttproducts_control_session::readSession('vo');

            if (!empty($usedVoucherCodeArray)) {
                $voucherCode = key($usedVoucherCodeArray);
                $voucherArray = current($usedVoucherCodeArray);
                $amount = $voucherArray['amount'];
                $this->setAmount(floatval($amount));
                $amountType = $voucherArray['amount_type'];
                $this->setAmountType($amountType);
                $this->setUsedVoucherCodeArray($usedVoucherCodeArray);
            }
        }

        return $result;
    } // init

    public static function generate(&$voucherCount = 0, &$codeArray = [], $orderUid, $itemArray, $whereGift)
    {
        $result = false;

        if (class_exists('tx_voucher_api')) {
            $result = true;
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    if (
                        tx_ttproducts_sql::isValid($row, $whereGift)
                    ) {
                        $count = intval($actItem['count']);
                        for ($i = 0; $i < $count; $i++) {
                            $voucherRow = [];
                            $voucherRow['hidden'] = '1';
                            $voucherRow['amount'] = $actItem['priceTax'];
                            $voucherRow['tax'] = $actItem['tax'];
                            $voucherRow['title'] = $row['title'];
                            $inserted = tx_voucher_api::insertVoucher($voucherRow);

                            if ($inserted) {
                                $uid = $voucherRow['uid'];
                                $codeArray[] = $voucherRow['code'];
                                $newRow = [];
                                $newRow['crdate'] = $voucherRow['crdate'];
                                $newRow['tstamp'] = $voucherRow['tstamp'];
                                $newRow['uid_local'] = $orderUid;
                                $newRow['uid_foreign'] = $uid;
                                $table = 'sys_products_orders_mm_gained_voucher_codes';

                                $GLOBALS['TYPO3_DB']->exec_INSERTquery($table, $newRow);

                                $newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
                                if ($newId) {
                                    $voucherCount++;
                                } else {
                                    $result = false;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                }

                if (!$result) {
                    break;
                }
            }
        }

        return $result;
    }

    public function isEnabled()
    {
        $result = false;

        if (
            isset($this->conf['voucher.']) &&
            isset($this->conf['voucher.']['active']) &&
            $this->conf['voucher.']['active'] == '1' &&
            parent::isEnabled()
        ) {
            $result = true;
        }

        return $result;
    }

    public static function unhideGifts(array &$uidArray, $orderRow, $whereGift)
    {
        $result = false;
        $uidArray = [];

        if ($orderRow['gained_voucher'] > 0) {
            $table = 'sys_products_orders_mm_gained_voucher_codes';
            $where_clause = 'uid_local=' . intval($orderRow['uid']) . ' AND hidden=0 AND deleted=0';

            $voucherRelationRows =
                $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                    'uid_local,uid_foreign',
                    $table,
                    $where_clause
                );

            if (
                is_array($voucherRelationRows) &&
                ExtensionManagementUtility::isLoaded('voucher')
            ) {
                $table = 'tx_voucher_codes';

                foreach ($voucherRelationRows as $voucherRelationRow) {
                    $updateRow = [];
                    $updateRow['hidden'] = '0';
                    $uid = intval($voucherRelationRow['uid_foreign']);
                    $uidArray[] = $uid;
                    $where_clause = 'uid=' . $uid . ' AND hidden=1';

                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        $table,
                        $where_clause,
                        $updateRow
                    );
                    $result = true;
                }
            }
        }

        return $result;
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function setAmount($amount): void
    {
        $this->amount = $amount;
    }

    public function getAmountType()
    {
        return $this->amountType;
    }

    public function setAmountType($amountType): void
    {
        $this->amountType = $amountType;
    }

    public function getPercentageAmount($amount)
    {
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $calculatedArray = $basketObj->getCalculatedArray();
        $amount = $calculatedArray['priceTax']['goodstotal']['ALL'] * ($amount / 100);

        return $amount;
    }

    public function getRebateAmount()
    {
        $amountType = $this->getAmountType();
        $amount = $this->getAmount();

        if ($amountType == 1) {
            $amount = $this->getPercentageAmount($amount);
        }

        return $amount;
    }

    public function setUsedVoucherCodeArray($usedVoucherCodeArray): void
    {
        if (isset($usedVoucherCodeArray) && is_array($usedVoucherCodeArray)) {
            $this->usedVoucherCodeArray = $usedVoucherCodeArray;
        }
    }

    public function getUsedVoucherCodeArray()
    {
        return $this->usedVoucherCodeArray;
    }

    public function isVoucherCodeUsed($code)
    {
        $result = false;

        foreach ($this->usedVoucherCodeArray as $codeRow) {
            if (
                isset($codeRow) && is_array($codeRow) &&
                $codeRow['code'] == $code
            ) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    public function getVoucherArray($code)
    {
        $result = false;

        foreach ($this->usedVoucherCodeArray as $codeRow) {
            if ($codeRow['code'] == $code) {
                $result = $codeRow;
                break;
            }
        }

        return $result;
    }

    public function getLastVoucherCodeUsed()
    {
        $result = '';

        if (is_array($this->usedVoucherCodeArray) && count($this->usedVoucherCodeArray)) {
            $lastArray = array_pop($this->usedVoucherCodeArray);
            $result = key($lastArray);
            array_push($this->usedVoucherCodeArray, $lastArray);
        }

        return $result;
    }

    public function setVoucherCodeUsed($code, $row): void
    {
        array_push($this->usedVoucherCodeArray, $row);
    }

    public function getVoucherCode()
    {
        return $this->voucherCode;
    }

    public function setVoucherCode($code): void
    {
        $this->voucherCode = $code;
    }

    public function getVoucherTableName()
    {
        $result = 'fe_users';
        if ($this->conf['table.']['voucher']) {
            $result = $this->conf['table.']['voucher'];
        }

        return $result;
    }

    public function setValid($bValid = true): void
    {
        $this->bValid = $bValid;
    }

    public function getValid()
    {
        return $this->bValid;
    }

    public function delete(): void
    {
        $voucherCode = $this->getLastVoucherCodeUsed();
        $voucherArray = $this->getVoucherArray($voucherCode);

        if (
            $voucherCode &&
            isset($voucherArray) &&
            is_array($voucherArray)
        ) {
            $row = $voucherArray;

            $voucherTable = $this->getVoucherTableName();

            if ($voucherTable == 'fe_users') {
                $whereGeneral = '';
                $uid_voucher = $row['uid'];
            } else {
                $row = tx_voucher_api::getRowFromCode($voucherCode, true);
                $uid_voucher = $row['fe_users_uid'];
                $whereGeneral = '(fe_users_uid="' . $GLOBALS['TSFE']->fe_user->user['uid'] . '" OR fe_users_uid=0) ';
                $whereGeneral .= 'AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
            }

            if (
                $uid_voucher &&
                $GLOBALS['TSFE']->fe_user->user['uid'] == $uid_voucher ||
                $voucherTable != 'fe_users' &&
                !$row['reusable']
            ) {
                $updateArray = [];
                $where = $whereGeneral;
                if ($voucherTable == 'fe_users') {
                    $where = 'uid="' . $row['uid'] . '"';
                    $updateArray['tt_products_vouchercode'] = '';
                } else {
                    $updateArray['deleted'] = 1;
                }

                $GLOBALS['TYPO3_DB']->exec_UPDATEquery($voucherTable, $where, $updateArray);
            }
        }
    }

    public function doProcessing($recs): void
    {
        $voucherCode = $recs['tt_products']['vouchercode'] ?? '';
        $this->setVoucherCode($voucherCode);
        $cObj = FrontendUtility::getContentObjectRenderer();

        if (
            $this->isVoucherCodeUsed($voucherCode) ||
            $voucherCode == ''
        ) {
            $this->setValid(true);
            $lastVoucherCode = $this->getLastVoucherCodeUsed();
            // 			$row = $this->usedVoucherCodeArray[$lastVoucherCode];
            $row = $this->getVoucherArray($lastVoucherCode);

            if (isset($row) && is_array($row)) {
                $this->setAmount($row['amount']);
                $this->setAmountType($row['amount_type']);
            }
        } else {
            $this->setValid(false);
        }

        if (
            $voucherCode &&
            !$this->isVoucherCodeUsed($voucherCode) &&
            $GLOBALS['TSFE']->fe_user->user['uid']
        ) {
            $uid_voucher = '';
            $voucherfieldArray = [];
            $whereGeneral = '';
            $voucherTable = $this->getVoucherTableName();
            if ($voucherTable == 'fe_users') {
                $voucherfieldArray = ['uid', 'tt_products_vouchercode'];
                $whereGeneral = $voucherTable . '.uid=' . intval($GLOBALS['TSFE']->fe_user->user['uid']);
                $whereGeneral .= ' AND ' . $voucherTable . '.tt_products_vouchercode=' . $TYPO3_DB->fullQuoteStr($voucherCode, $voucherTable);
            } else {
                $voucherfieldArray = ['starttime', 'endtime', 'title', 'fe_users_uid', 'reusable', 'code', 'amount', 'amount_type', 'note'];
                $whereGeneral = '(fe_users_uid="' . intval($GLOBALS['TSFE']->fe_user->user['uid']) . '" OR fe_users_uid=0) ';
                $whereGeneral .= 'AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
            }
            $enableFields = TableUtility::enableFields($voucherTable);

            $where = $whereGeneral . $enableFields;
            $fields = implode(',', $voucherfieldArray);

            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $voucherTable, $where);
            if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if ($voucherTable == 'fe_users') {
                    $uid_voucher = $row['uid'];
                    if (isset($this->conf['voucher.'])) {
                        $row['amount'] = doubleval($this->conf['voucher.']['amount']);
                        $row['amount_type'] = intval($this->conf['voucher.']['amount_type']);
                    }
                    $row['starttime'] = 0;
                    $row['endtime'] = 0;
                    $row['code'] = $row['tt_products_vouchercode'];
                } else {
                    $uid_voucher = $row['fe_users_uid'];
                }
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);

            if (
                $row &&
                (
                    $voucherTable != 'fe_users' ||
                    $uid_voucher == $GLOBALS['TSFE']->fe_user->user['uid']
                )
            ) {
                $amount = doubleval($this->getAmount());
                $amountType = intval($this->getAmountType());

                if ($amountType == $row['amount_type']) {
                    $amount += $row['amount'];
                } elseif ($row['amount_type'] == 1) {
                    $amount += $this->getPercentageAmount($row['amount']);
                }

                $this->setAmount($amount);
                $this->setVoucherCode($row['code']);
                $this->setValid(true);

                $this->setVoucherCodeUsed($voucherCode, $row);
                tx_ttproducts_control_session::writeSession('vo', $this->getUsedVoucherCodeArray());
            }

            // 			if ($uid_voucher) {
            // 				// first check if not inserted own vouchercode
            // 				if ($GLOBALS['TSFE']->fe_user->user['uid'] != $uid_voucher) {
            // 					$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
            // 					$basketObj->calculatedArray['priceTax']['voucher'] = $this->conf['voucher.']['price'];
            // 				}
            // 			}
        }
    }
}
