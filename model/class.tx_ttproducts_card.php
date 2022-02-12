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
 * credit card functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_card extends tx_ttproducts_table_base {
	var $ccArray;	// credit card data
	var $allowedArray = array(); // allowed uids of credit cards
	var $inputFieldArray = array('cc_type', 'cc_number_1','cc_number_2','cc_number_3', 'cc_number_4', 'owner_name', 'cvv2', 'endtime_mm', 'endtime_yy');
	var $sizeArray = array('cc_type' => 4, 'cc_number_1' => 4,'cc_number_2' => 4,'cc_number_3' => 4, 'cc_number_4' => 4, 'owner_name' => 0, 'cvv2' => 4, 'endtime_mm' => 2, 'endtime_yy'  => 2);
	var $asteriskArray = array(2 => '**', 4 => '****');


	public function init ($functablename) {
		$result = true;

		$basketObj = GeneralUtility::makeInstance('tx_ttproducts_basket');
		$formerBasket = $basketObj->recs;
		$basketExtra = tx_ttproducts_control_basket::getBasketExtra();

		if (isset($basketExtra) && is_array($basketExtra) && isset($basketExtra['payment.'])) {
			$allowedUids = $basketExtra['payment.']['creditcards'] ?? '';
		}

		$result = parent::init($functablename);

		$this->ccArray = $formerBasket['creditcard'] ?? [];
		if (isset($allowedUids)) {
			$this->allowedArray = GeneralUtility::trimExplode(',',$allowedUids);
		}
		$bNumberRecentlyModified = false;

		foreach ($this->inputFieldArray as $k => $field) {
			$size = $this->sizeArray[$field];
			if ($size) {
				if (isset($this->ccArray[$field]) && strcmp ($this->ccArray[$field], $this->asteriskArray[$size]) != 0) {
					$bNumberRecentlyModified = true;
				}
			}
		}

		if ($bNumberRecentlyModified) {

			$ccArray = tx_ttproducts_control_session::readSession('cc');
			if (!$ccArray) {
				$ccArray = array();
			}

			$allowedTags = '';
			foreach ($ccArray as $type => $ccRow) {
				$ccArray[$type] = strip_tags ($ccRow, $allowedTags);
			}

			if ($this->ccArray) {
				$newId = $this->create ($ccArray['cc_uid'], $this->ccArray);

				if ($newId) {
					$ccArray['cc_uid'] = $newId;
					tx_ttproducts_control_session::writeSession('cc', $ccArray);

					for ($i = 1; $i <= 3; ++$i) {
						$this->ccArray['cc_number_' . $i] = ($this->ccArray['cc_number_' . $i] ? $this->asteriskArray[$this->sizeArray['cc_number_' . $i]] : '');
					}

					$this->ccArray['cvv2'] = ($this->ccArray['cvv2'] ? $this->asteriskArray[$this->sizeArray['cvv2']] : '' );
					if (!is_array($this->conf['payment.']['creditcardSelect.']['mm.'])) {
						$this->ccArray['endtime_mm'] = ($this->ccArray['endtime_mm'] ? $this->asteriskArray[$this->sizeArray['endtime_mm']] : '');
					}
					if (!is_array($this->conf['payment.']['creditcardSelect.']['yy.'])) {
						$this->ccArray['endtime_yy'] = ($this->ccArray['endtime_yy'] ? $this->asteriskArray[$this->sizeArray['endtime_yy']] : '');
					}
				}
			}
		}

		return $result;
	}


	// **************************
	// ORDER related functions
	// **************************

	/**
	 * Create a new credit card record
	 *
	 * This creates a new credit card record on the page with pid PID_sys_products_orders. That page must exist!
	 */
	public function create ($uid, $ccArray) {
		$newId = 0;
		$tablename = $this->getTablename();
		$pid = intval($this->conf['PID_sys_products_orders']);
		if (!$pid) {
			$pid = intval($GLOBALS['TSFE']->id);
		}

		if ($ccArray['cc_number_1'] && $GLOBALS['TSFE']->sys_page->getPage_noCheck($pid)) {
			$time = time();
			$timeArray =
				array(
					'hour' => 0, // hour
					'minute' => 0, // minute
					'second' => 0, // second
					'month' => intval($ccArray['endtime_mm']), // month
					'day' => 28, // day
					'year' => intval($ccArray['endtime_yy']) // year
				);
			$endtime = mktime ($timeArray['hour'], $timeArray['minute'], $timeArray['second'], $timeArray['month'], $timeArray['day'], $timeArray['year']);

			if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
				$endtime += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
			}

			for ($i = 1; $i <= 4; ++$i) {
				$ccArray['cc_number_' . $i] = ($ccArray['cc_number_' . $i] ? $ccArray['cc_number_' . $i] : '   ');
			}

			$newFields = array (
				'pid' => intval($pid),
				'tstamp' => $time,
				'crdate' => $time,
				'endtime' => $endtime,
				'owner_name' => $ccArray['owner_name'],
				'cc_number' => $ccArray['cc_number_1'] . $ccArray['cc_number_2'] . $ccArray['cc_number_3'] . $ccArray['cc_number_4'],
				'cc_type' => $ccArray['cc_type'],
				'cvv2' => $ccArray['cvv2']
			);

			if ($uid) {
				$where_clause = 'uid=' . $uid;
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $tablename, $where_clause);
				$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
				for ($i = 1; $i <= 4; ++$i) {
                    $tmpOldPart = '';
                    if (!empty($row['cc_number'])) {
                        $tmpOldPart = substr($row['cc_number'], ($i-1) * 4, 4);
                    }
                    if (isset($ccArray['cc_number_' . $i])) {
                        if (strcmp($ccArray['cc_number_' . $i], $this->asteriskArray[$this->sizeArray['cc_number_' . $i]]) == 0) {
                            $ccArray['cc_number_' . $i] = $tmpOldPart;
                        }
                    }
				}
				$fieldArray = array('cc_type', 'owner_name', 'cvv2');

				foreach ($fieldArray as $k => $field) {
					if (isset($ccArray[$field]) && strcmp($ccArray[$field], $this->asteriskArray[$this->sizeArray[$field]]) == 0) {
						unset($newFields[$field]); // prevent from change into asterisks
					}
				}
				$newFields['cc_number'] = $ccArray['cc_number_1'] . $ccArray['cc_number_2'].$ccArray['cc_number_3'].$ccArray['cc_number_4'];
				$oldEndtime = getdate($row['endtime']);
				if (strcmp($ccArray['endtime_mm'], $this->asteriskArray[$this->sizeArray['endtime_mm']]) == 0) {
					$ccArray['endtime_mm'] = $oldEndtime['mon'];
				}
				if (strcmp($ccArray['endtime_yy'], $this->asteriskArray[$this->sizeArray['endtime_yy']]) == 0) {
					$ccArray['endtime_yy'] = $oldEndtime['year'];
				}

				$timeArray =
					array(
						'hour' => 0, // hour
						'minute' => 0, // minute
						'second' => 0, // second
						'month' => intval($ccArray['endtime_mm']), // month
						'day' => 28, // day
						'year' => intval($ccArray['endtime_yy']) // year
					);
				$endtime = mktime($timeArray['hour'], $timeArray['minute'], $timeArray['second'], $timeArray['month'], $timeArray['day'], $timeArray['year']);

				if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'])) {
					$endtime += ($GLOBALS['TYPO3_CONF_VARS']['SYS']['serverTimeZone'] * 3600);
				}

				$newFields['endtime'] = $endtime;

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery($tablename, $where_clause, $newFields);
				$newId = $uid;
			} else {
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($tablename, $newFields);
				$newId = $GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}
		return $newId;
	} // create


	public function getUid () {
		$result = 0;
		$ccArray = tx_ttproducts_control_session::readSession('cc');
		if (isset($ccArray['cc_uid'])) {
			$result = $ccArray['cc_uid'];
		}
		return $result;
	}


	public function getAllowedArray () {
		return $this->allowedArray;
	}


	public function getRow ($uid, $bFieldArrayAll = false) {
		$rcArray = array();
		if ($bFieldArrayAll) {
			foreach ($this->inputFieldArray as $k => $field) {
				$rcArray[$field] = '';
			}
		}

		if ($uid) {
			$where = 'uid = ' . intval($uid);

			$fields = '*';
			if ($bFieldArrayAll) {
				$fields = implode(',', $this->inputFieldArray);
			}
			$tablename = $this->getTablename();
			if ($tablename == '') {
				$tablename = 'sys_products_cards';
			}
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery($fields, $tablename, $where);
			$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
			$GLOBALS['TYPO3_DB']->sql_free_result($res);
			if ($row) {
				$rcArray = $row;
			}
		}
		return $rcArray;
	}


	/**
	 * Checks if required fields for credit cards and bank accounts are filled in
	 */
	public function checkRequired () {
		$rc = '';
		$allowedArray = $this->getAllowedArray();

		foreach ($this->inputFieldArray as $k => $field)	{
			if ($field == 'cc_type' && empty($allowedArray)) {
				continue;
			}

			$testVal = $this->ccArray[$field] ?? '';
			if (
				!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($testVal) &&
				!$testVal
			) {
				$rc = $field;
				break;
			}
		}
		return $rc;
	} // checkRequired
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_card.php']);
}


