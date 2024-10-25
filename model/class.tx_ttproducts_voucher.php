<?php

declare(strict_types=1);

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Franz Holzinger (franz@ttproducts.de)
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
 *
 */
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\SessionHandler\SessionHandler;
use JambageCom\Voucher\Api\Api;

class tx_ttproducts_voucher extends tx_ttproducts_table_base
{
    protected $amount = 0;
    protected $amountType;
    protected $possibleAmount = 0;
    protected $voucherCode;
    protected $valid = false;
    protected $marker = 'VOUCHER';
    protected $usedVoucherCodeArray = [];
    protected $combinable = true;
    public $calculatedArray;

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
            $usedVoucherCodeArray = SessionHandler::readSession('vo');

            if (!empty($usedVoucherCodeArray)) {
                $voucherCode = key($usedVoucherCodeArray);
                $voucherArray = current($usedVoucherCodeArray);
                $amount = $voucherArray['amount'];
                $this->setAmount(floatval($amount));
                $amountType = $voucherArray['amount_type'];
                $this->setAmountType($amountType);
                $this->setUsedVoucherCodeArray($usedVoucherCodeArray);

                $combinable = true;
                foreach ($usedVoucherCodeArray as $codeRow) {
                    if (
                        isset($codeRow) && is_array($codeRow) &&
                        $codeRow['combinable'] == '1'
                    ) {
                        $combinable = false;
                        break;
                    }
                }
                $this->setCombinable($combinable);
            }
        }

        return true; // Die Voucher Tabelle muss nicht verwendet werden. Die Subpart Marker müssen ggf. gelöscht werden.
    } // init

    public static function generate(&$voucherCount, array &$codeArray, $orderUid, $itemArray, $whereGift)
    {
        $result = false;
        $voucherClassname = '\\JambageCom\\Voucher\\Api\\Api';
        if (class_exists($voucherClassname)) {
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
                            $inserted = Api::insertVoucher($voucherRow);

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

    public function getPossibleAmount()
    {
        return $this->possibleAmount;
    }

    public function setPossibleAmount($possibleAmount): void
    {
        $this->possibleAmount = $possibleAmount;
    }

    public function getCombinable()
    {
        return $this->combinable;
    }

    public function setCombinable($combinable): void
    {
        $this->combinable = $combinable;
    }

    public function getAmountType()
    {
        return $this->amountType;
    }

    public function setAmountType($amountType): void
    {
        $this->amountType = $amountType;
    }

    public function getCalculatedArray()
    {
        return $this->calculatedArray;
    }

    public function setCalculatedArray($calculatedArray): void
    {
        $this->calculatedArray = $calculatedArray;
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

    // neu Anfang
    public function getRebatePercentage()
    {
        $result = 0;
        $basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
        $calculatedArray = $basketObj->getCalculatedArray();

        if ($calculatedArray['priceTax']['goodstotal']['ALL'] > 0) {
            $result = (1 - ($calculatedArray['priceTax']['vouchergoodstotal']['ALL'] / $calculatedArray['priceTax']['goodstotal']['ALL'])) * 100;
        }

        return $result;
    }
    // neu Ende

    public function setUsedVoucherCodeArray($usedVoucherCodeArray): void
    {
        if (
            isset($usedVoucherCodeArray) &&
            is_array($usedVoucherCodeArray)
        ) {
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
        $usedVoucherCodeArray = $this->getUsedVoucherCodeArray();

        foreach ($usedVoucherCodeArray as $codeRow) {
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

    public function getVoucherRow($code)
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

        if (count($this->usedVoucherCodeArray)) {
            $lastArray = array_pop($this->usedVoucherCodeArray);
            if (
                isset($lastArray) &&
                is_array($lastArray) &&
                isset($lastArray['code'])
            ) {
                $result = $lastArray['code'];
            }
            array_push($this->usedVoucherCodeArray, $lastArray);
        }

        return $result;
    }

    // neu Anfang

    public function voucherCodeDiscountCombinable(
        $calculatedArray
    ) {
        $result = true;
        $combinable = $this->getCombinable();

        if (
            !$combinable &&
            (
                $calculatedArray['noDiscountPriceTax']['goodstotal']['ALL'] - $calculatedArray['priceTax']['goodstotal']['ALL'] > -0.1
            )
        ) {
            $result = false;
        }

        return $result;
    }

    public function voucherCodeExceedsLimit(
        $amount,
        $calculatedArray
    ) {
        $result = false;

        if (
            floatval(($amount) > $calculatedArray['priceTax']['goodstotal']['ALL']
        ) {
            $result = true;
        }

        return $result;
    }

    public function combinationAllowed($row)
    {
        $result = false;
        $lastVoucherCode = $this->getLastVoucherCodeUsed();

        if (
            $row['combinable'] == '0' &&
            $this->getCombinable() ||
            $row['combinable'] == '1' &&
            (
                $lastVoucherCode == '' ||
                $lastVoucherCode == $row['code']
            )
        ) {
            $result = true;
        }

        return $result;
    }

    public function setVoucherCodeUsed($row): void
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

    public function setValid($valid = true): void
    {
        $this->valid = $valid;
    }

    public function getValid()
    {
        return $this->valid;
    }

    public function delete($feUserRecord): void
    {
        $voucherCode = $this->getLastVoucherCodeUsed();
        $voucherRow = $this->getVoucherRow($voucherCode);

        if (
            $voucherCode &&
            isset($voucherRow) &&
            is_array($voucherRow)
        ) {
            $row = $voucherRow;
            $voucherTable = $this->getVoucherTableName();
            $voucherClassname = '\\JambageCom\\Voucher\\Api\\Api';

            if ($voucherTable == 'fe_users') {
                $whereGeneral = '';
                $uid_voucher = $row['uid'];
            } elseif (class_exists($voucherClassname)) {
                $row =
                Api::getRowFromCode(
                    $voucherCode,
                    true
                );
                $uid_voucher = $row['fe_users_uid'];
                $whereGeneral = '(fe_users_uid="' . intval($feUserRecord['uid'] ?? 0) . '" OR fe_users_uid=0) ';
                $whereGeneral .= 'AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
            }

            if (
                $uid_voucher &&
                !empty($feUserRecord['uid']) &&
                $feUserRecord['uid'] == $uid_voucher ||

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

    public function doProcessing(array $recs, array $calculatedArray, $feUserRecord): void
    {
        $voucherCode = $recs['tt_products']['vouchercode'] ?? '';
        $voucherTable = $this->getVoucherTableName();
        $this->setVoucherCode($voucherCode);
        $this->setCalculatedArray($calculatedArray);
        $this->setValid(false);
        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        if (
            $this->isVoucherCodeUsed($voucherCode) ||
            $voucherCode == ''
        ) {
            $lastVoucherCode = $this->getLastVoucherCodeUsed();
            $row = $this->getVoucherRow($lastVoucherCode);

            if (isset($row) && is_array($row)) {
                if (
                    $this->voucherCodeDiscountCombinable(
                        $calculatedArray
                    ) &&
                    !$this->voucherCodeExceedsLimit(
                        $row['amount'],
                        $calculatedArray
                    )
                ) {
                    $this->setValid(true);
                    $this->setAmount($row['amount']);
                    $this->setAmountType($row['amount_type']);
                } else {
                    $this->setAmount($row['amount']);
                    $this->setAmountType($row['amount_type']);
                    $rebateAmount = $this->getRebateAmount();
                    $this->setPossibleAmount($rebateAmount);
                    $this->setAmount(0);
                }
            }
        }

        if (
            $voucherCode &&
            !$this->isVoucherCodeUsed($voucherCode) &&
            (
                $voucherTable != 'fe_users' ||
                !empty($feUserRecord['uid'])
            )
        ) {
            $uid_voucher = '';
            $voucherfieldArray = [];
            $whereGeneral = '1=1';

            if ($voucherTable == 'fe_users') {
                $voucherfieldArray = ['uid', 'tt_products_vouchercode'];
                if (isset($feUserRecord['uid'])) {
                    $whereGeneral = $voucherTable . '.uid=' . intval($feUserRecord['uid']);
                } else {
                    $whereGeneral = '(fe_users_uid=0)';
                }
                $whereGeneral .= ' AND ' . $voucherTable . '.tt_products_vouchercode=' . $TYPO3_DB->fullQuoteStr($voucherCode, $voucherTable);
            } else {
                $voucherfieldArray = ['starttime', 'endtime', 'title', 'code', 'fe_users_uid', 'reusable', 'code', 'usecounter', 'combinable', 'amount', 'amount_type', 'tax'];
                if (isset($feUserRecord['uid'])) {
                    $whereGeneral = '(fe_users_uid="' . intval($feUserRecord['uid']) . '" OR fe_users_uid=0)';
                } else {
                    $whereGeneral = '(fe_users_uid=0)';
                }
                $whereGeneral .= ' AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
            }
            $where = $whereGeneral . $pageRepository->enableFields($voucherTable);
            $fields = implode(',', $voucherfieldArray);

            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $voucherTable, $where);
            if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                if ($voucherTable == 'fe_users') {
                    $uid_voucher = $row['uid'];
                    if (isset($this->conf['voucher.'])) {
                        $row['amount'] = floatval(($this->conf['voucher.']['amount']);
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
                    isset($feUserRecord['uid']) &&
                    $uid_voucher == $feUserRecord['uid']
                )
            ) {
                $amount = floatval(($this->getAmount());
                $amountType = intval($this->getAmountType());
                $combinable = $this->getCombinable();

                if ($amountType == $row['amount_type']) {
                    $amount += $row['amount'];
                } elseif ($row['amount_type'] == 1) {
                    $amount += $this->getPercentageAmount($row['amount']);
                }

                if (
                    $this->voucherCodeDiscountCombinable(
                        $calculatedArray
                    ) &&
                    !$this->voucherCodeExceedsLimit(
                        $amount,
                        $calculatedArray
                    )
                ) {
                    if ($this->combinationAllowed($row)) {
                        $this->setAmount($amount);
                        $this->setVoucherCode($row['code']);
                        $this->setValid(true);

                        $this->setVoucherCodeUsed($row);
                        SessionHandler::storeSession('vo', $this->getUsedVoucherCodeArray());
                    }
                } else {
                    $this->setAmount($amount);
                    $rebateAmount = $this->getRebateAmount();
                    $this->setPossibleAmount($rebateAmount);
                    $this->setAmount(0);
                }
            }
        }
    }
}
