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


class tx_ttproducts_voucher extends tx_ttproducts_table_base {
	var $amount;
	var $amountType;
	var $code;
	var $bValid = FALSE;
	var $marker = 'VOUCHER';
	var $usedCodeArray = array();

	/**
	 * Getting all tt_products_cat categories into internal array
	 */
	function init($cObj, $functablename)  {
		global $TSFE;

		parent::init($cObj, $functablename);
		$usedCodeArray = $TSFE->fe_user->getKey('ses','vo');

		if (isset($usedCodeArray) && is_array($usedCodeArray))	{
			list($voucherCode, $voucherArray) = each($usedCodeArray);
			$amount = $voucherArray['amount'];
			$this->setAmount(floatval($amount));
			$amountType = $voucherArray['amount_type'];
			$this->setAmountType($amountType);
			$this->setUsedCodeArray($usedCodeArray);
		}
	} // init

	function getAmount ()	{

		return $this->amount;
	}

	function setAmount ($amount)	{
		$this->amount = $amount;
	}

	function getAmountType ()	{
		return $this->amountType;
	}

	function setAmountType ($amountType)	{
		$this->amountType = $amountType;
	}


	function getPercentageAmount ($amount) {
		$basketObj = t3lib_div::makeInstance('tx_ttproducts_basket');
		$calculatedArray = $basketObj->getCalculatedArray();

		$amount = $calculatedArray['priceTax']['goodstotal'] * ($amount / 100);
		return $amount;
	}


	function getRebateAmount ()	{

		$amountType = $this->getAmountType();

		$amount = $this->getAmount();

		if ($amountType == 1)	{
			$amount = $this->getPercentageAmount($amount);
		}

		return $amount;
	}


	function setUsedCodeArray ($usedCodeArray)	{
		if (isset($usedCodeArray) && is_array($usedCodeArray))	{
			$this->usedCodeArray = $usedCodeArray;
		}
	}

	function getUsedCodeArray ()	{
		return $this->usedCodeArray;
	}

	function isCodeUsed ($code)	{

		$result = FALSE;

		foreach ($this->usedCodeArray as $codeRow) {
			if ($codeRow['code'] == $code) {
				$result = TRUE;
				break;
			}
		}

		return $result;
	}


	function getVoucherArray ($code) {
		$result = FALSE;

		foreach ($this->usedCodeArray as $codeRow) {
			if ($codeRow['code'] == $code) {
				$result = $codeRow;
				break;
			}
		}

		return $result;
	}


	function getLastCodeUsed () {
		$result = '';

		if (count($this->usedCodeArray)) {
			$lastArray = array_pop($this->usedCodeArray);
			$result = $lastArray['code'];
			array_push($this->usedCodeArray, $lastArray);
		}
		return $result;
	}

	function setCodeUsed ($code, $row)	{
		array_push($this->usedCodeArray, $row);
	}

	function getCode ()	{
		return $this->code;
	}

	function setCode ($code)	{
		$this->code = $code;
	}

    function getVoucherTableName ()	{
        $result = 'fe_users';
        if ($this->conf['table.']['voucher'])	{
            $result = $this->conf['table.']['voucher'];
        } else if ($this->conf['voucher.']['table'])    {
            $result = $this->conf['voucher.']['table'];
        }

        return $result;
    }

	function setValid($bValid=TRUE)	{
		$this->bValid = $bValid;
	}

	function getValid()	{
		return $this->bValid;
	}

	function delete()	{
		global $TYPO3_DB, $TSFE;

		$voucherCode = $this->getLastCodeUsed();
		$voucherArray = $this->getVoucherArray($voucherCode);

		if ($voucherCode && isset($voucherArray) && is_array($voucherArray))	{
			$row = $voucherArray;
			$voucherTable = $this->getVoucherTableName();

			if ($voucherTable == 'fe_users')	{
				$whereGeneral = '';
				$uid_voucher = $row['uid'];
			} else {
                $row = tx_voucher_api::getRowFromCode($voucherCode, TRUE);
				$uid_voucher = $row['fe_users_uid'];
				$whereGeneral = '(fe_users_uid="'.$TSFE->fe_user->user['uid'].'" OR fe_users_uid=0) ';
				$whereGeneral .= 'AND code=' . $TYPO3_DB->fullQuoteStr($voucherCode, $voucherTable);
			}

			if (
				$uid_voucher &&
				$TSFE->fe_user->user['uid'] == $uid_voucher
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

				$TYPO3_DB->exec_UPDATEquery($voucherTable, $where, $updateArray);
			}
		}
	}


	function doProcessing(&$recs)	{
		global $TYPO3_DB, $TSFE;

		$voucherCode = $recs['tt_products']['vouchercode'];
		$this->setCode($voucherCode);
		if ($this->isCodeUsed($voucherCode) || $voucherCode == '')	{
			$this->setValid(TRUE);
			$lastVoucherCode = $this->getLastCodeUsed();

			$row = $this->getVoucherArray($lastVoucherCode);

			if (isset($row) && is_array($row)) {
				$this->setAmount($row['amount']);
				$this->setAmountType($row['amount_type']);
			}
		} else {
			$this->setValid(FALSE);
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
                $whereGeneral = $voucherTable . '.uid=' . intval($TSFE->fe_user->user['uid']);
                $whereGeneral .= ' AND ' . $voucherTable . '.tt_products_vouchercode=' . $TYPO3_DB->fullQuoteStr($voucherCode, $voucherTable);
			} else {
				$voucherfieldArray = array('starttime', 'endtime', 'title', 'fe_users_uid', 'reusable', 'code', 'amount', 'amount_type', 'note');
				$whereGeneral = '(fe_users_uid="' . intval($TSFE->fe_user->user['uid']) . '" OR fe_users_uid=0) ';
				$whereGeneral .= 'AND code=' . $TYPO3_DB->fullQuoteStr($voucherCode, $voucherTable);
			}
			$where = $whereGeneral.$this->cObj->enableFields($voucherTable);
			$fields = implode (',', $voucherfieldArray);

			$res = $TYPO3_DB->exec_SELECTquery($fields, $voucherTable, $where);
			if ($row = $TYPO3_DB->sql_fetch_assoc($res)) {
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
			$TYPO3_DB->sql_free_result($res);

			if ($row && ($voucherTable != 'fe_users' || $uid_voucher == $TSFE->fe_user->user['uid']))	{

				$amount = doubleval($this->getAmount());
				$amountType = intval($this->getAmountType());

				if ($amountType == $row['amount_type']) {
					$amount += $row['amount'];
				} else if ($row['amount_type'] == 1){
					$amount += $this->getPercentageAmount($row['amount']);
				}

				$this->setAmount($amount);
				$this->setCode($row['code']);
				$this->setValid(TRUE);

				$this->setCodeUsed($voucherCode, $row);
				$TSFE->fe_user->setKey('ses', 'vo', $this->getUsedCodeArray());
			}
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_voucher.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_voucher.php']);
}


?>
