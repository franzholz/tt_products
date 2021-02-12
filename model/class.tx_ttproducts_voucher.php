<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2017 Franz Holzinger (franz@ttproducts.de)
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
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;


class tx_ttproducts_voucher extends tx_ttproducts_table_base {
	public $amount;
	public $amountType;
	public $code;
	public $bValid = false;
	public $marker = 'VOUCHER';
	public $usedCodeArray = array();

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	public function init($cObj, $functablename)  {
		parent::init($cObj, $functablename);
		$usedCodeArray = $GLOBALS['TSFE']->fe_user->getKey('ses','vo');

		if (isset($usedCodeArray) && is_array($usedCodeArray))	{
			$voucherCode = key($usedCodeArray);
			$voucherArray = current($usedCodeArray);
			$amount = $voucherArray['amount'];
			$this->setAmount(floatval($amount));
			$amountType = $voucherArray['amount_type'];
			$this->setAmountType($amountType);
			$this->setUsedCodeArray($usedCodeArray);
		}
	} // init

	public function getAmount ()	{

		return $this->amount;
	}

	public function setAmount ($amount)	{
		$this->amount = $amount;
	}

	public function getAmountType ()	{
		return $this->amountType;
	}

	public function setAmountType ($amountType)	{
		$this->amountType = $amountType;
	}


	public function getPercentageAmount ($amount) {
		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$calculatedArray = $basketObj->getCalculatedArray();

		$amount = $calculatedArray['priceTax']['goodstotal'] * ($amount / 100);
		return $amount;
	}


	public function getRebateAmount ()	{

		$amountType = $this->getAmountType();

		$amount = $this->getAmount();

		if ($amountType == 1)	{
			$amount = $this->getPercentageAmount($amount);
		}

		return $amount;
	}


	public function setUsedCodeArray ($usedCodeArray)	{
		if (isset($usedCodeArray) && is_array($usedCodeArray))	{
			$this->usedCodeArray = $usedCodeArray;
		}
	}

	public function getUsedCodeArray ()	{
		return $this->usedCodeArray;
	}

	public function isCodeUsed ($code)	{

		$result = false;

		foreach ($this->usedCodeArray as $codeRow) {
			if ($codeRow['code'] == $code) {
				$result = true;
				break;
			}
		}

		return $result;
	}


	public function getVoucherArray ($code) {
		$result = false;

		foreach ($this->usedCodeArray as $codeRow) {
			if ($codeRow['code'] == $code) {
				$result = $codeRow;
				break;
			}
		}

		return $result;
	}


	public function getLastCodeUsed () {
		$result = '';

		if (count($this->usedCodeArray)) {
			$lastArray = array_pop($this->usedCodeArray);
			$result = $lastArray['code'];
			array_push($this->usedCodeArray, $lastArray);
		}
		return $result;
	}

	public function setCodeUsed ($code, $row)	{
		array_push($this->usedCodeArray, $row);
	}

	public function getCode ()	{
		return $this->code;
	}

	public function setCode ($code)	{
		$this->code = $code;
	}

    public function getVoucherTableName ()	{
        $result = 'fe_users';
        if ($this->conf['table.']['voucher'])	{
            $result = $this->conf['table.']['voucher'];
        } else if ($this->conf['voucher.']['table'])    {
            $result = $this->conf['voucher.']['table'];
        }

        return $result;
    }

	public function setValid($bValid=true)	{
		$this->bValid = $bValid;
	}

	public function getValid()	{
		return $this->bValid;
	}

	public function delete()	{
		$voucherCode = $this->getLastCodeUsed();
		$voucherArray = $this->getVoucherArray($voucherCode);

		if ($voucherCode && isset($voucherArray) && is_array($voucherArray))	{
			$row = $voucherArray;
			$voucherTable = $this->getVoucherTableName();

			if ($voucherTable == 'fe_users')	{
				$whereGeneral = '';
				$uid_voucher = $row['uid'];
			} else {
                $row = tx_voucher_api::getRowFromCode($voucherCode, true);
				$uid_voucher = $row['fe_users_uid'];
				$whereGeneral = '(fe_users_uid="'.$GLOBALS['TSFE']->fe_user->user['uid'].'" OR fe_users_uid=0) ';
				$whereGeneral .= 'AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
			}

			if (
				$uid_voucher &&
				$GLOBALS['TSFE']->fe_user->user['uid'] == $uid_voucher
					||
				$voucherTable != 'fe_users' &&
				!$row['reusable']
			) {
				$updateArray = array();
				$where = $whereGeneral;
				if ($voucherTable == 'fe_users')	{
					$where = 'uid="'.$row['uid'] . '"';
					$updateArray['tt_products_vouchercode'] = '';
				} else {
					$updateArray['deleted'] = 1;
				}

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($voucherTable, $where, $updateArray);
			}
		}
	}

	public function doProcessing(&$recs)	{
		$voucherCode = $recs['tt_products']['vouchercode'];
		$this->setCode($voucherCode);
		if ($this->isCodeUsed($voucherCode) || $voucherCode == '')	{
			$this->setValid(true);
			$lastVoucherCode = $this->getLastCodeUsed();

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
            !$this->isCodeUsed($voucherCode) &&
            (
                is_array($this->conf['voucher.']) ||
                isset($this->conf['table.']['voucher'])
            )
        ) {
			$uid_voucher = '';
			$voucherfieldArray = array();
			$whereGeneral = '';
			$voucherTable = $this->getVoucherTableName();

            if ($voucherTable == 'fe_users') {
                $voucherfieldArray = array('uid', 'tt_products_vouchercode');
                $whereGeneral = $voucherTable . '.uid=' . intval($GLOBALS['TSFE']->fe_user->user['uid']);
                $whereGeneral .= ' AND ' . $voucherTable . '.tt_products_vouchercode=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
			} else {
				$voucherfieldArray = array('starttime', 'endtime', 'title', 'fe_users_uid', 'reusable', 'code', 'amount', 'amount_type', 'note');
				$whereGeneral = '(fe_users_uid="' . intval($GLOBALS['TSFE']->fe_user->user['uid']) . '" OR fe_users_uid=0) ';
				$whereGeneral .= 'AND code=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($voucherCode, $voucherTable);
			}
            $enableFields = \JambageCom\Div2007\Utility\TableUtility::enableFields($voucherTable);
			$where = $whereGeneral . $enableFields;
			$fields = implode (',', $voucherfieldArray);

			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $voucherTable, $where);
			if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				if ($voucherTable == 'fe_users')	{
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

			if ($row && ($voucherTable != 'fe_users' || $uid_voucher == $GLOBALS['TSFE']->fe_user->user['uid']))	{

				$amount = doubleval($this->getAmount());
				$amountType = intval($this->getAmountType());

				if ($amountType == $row['amount_type']) {
					$amount += $row['amount'];
				} else if ($row['amount_type'] == 1){
					$amount += $this->getPercentageAmount($row['amount']);
				}

				$this->setAmount($amount);
				$this->setCode($row['code']);
				$this->setValid(true);

				$this->setCodeUsed($voucherCode, $row);
				tx_ttproducts_control_session::writeSession('vo', $this->getUsedCodeArray());
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_voucher.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_voucher.php']);
}

