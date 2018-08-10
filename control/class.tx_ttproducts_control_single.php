<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2009 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the control of the single view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_control_single implements t3lib_Singleton {

	/**
	 * Triggers events when the single view has been called
	 *
	 * @return	void
	 * @access private
	 */
	function triggerEvents ($conf)	{
		global $TSFE, $TYPO3_DB;

		if (isset($conf['trigger.']))	{

			$triggerConf = $conf['trigger.'];
			$piVars = tx_ttproducts_model_control::getPiVars();
			$piVar = tx_ttproducts_model_control::getPiVar('tt_products');
			$uid = $piVars[$piVar];

			if ($TSFE->loginUser)	{
	/*
	$productTableObj->getTableConf('LISTVIEWEDITEMS');*/
				$mmTablename = 'sys_products_fe_users_mm_visited_products';

				if ($uid && in_array($mmTablename, $triggerConf))	{	// check if this trigger has been activated

					$where = 'uid_local=' . intval($TSFE->fe_user->user['uid']) . ' AND uid_foreign=' . intval($uid);
					$mmArray = $TYPO3_DB->exec_SELECTgetRows('*', $mmTablename, $where, '', 'tstamp', '1');
					$time = time();

					if ($mmArray)	{
						$updateFields = $mmArray['0'];
						$updateFields['uid_foreign'] = $uid;
						$updateFields['tstamp'] = $time;
						$updateFields['qty'] += 1;
						$TYPO3_DB->exec_UPDATEquery($mmTablename, $where, $updateFields);
					} else {
						$insertFields = array (
							'tstamp' => $time,
							'uid_local' => $TSFE->fe_user->user['uid'],
							'uid_foreign' => $uid,
							'qty' => 1
						);
						$TYPO3_DB->exec_INSERTquery($mmTablename, $insertFields);
					}
				}
			}

			$tablename = 'sys_products_visited_products';

			if ($uid && in_array($tablename, $triggerConf))	{	// check if this trigger has been activated

				$where = 'uid=' . intval($uid);
				$rowArray = $TYPO3_DB->exec_SELECTgetRows('*', $tablename, $where, '', 'tstamp', '1');
				$time = time();
				if ($rowArray)	{

					$updateFields = $rowArray['0'];
					$updateFields['tstamp'] = $time;
					$updateFields['qty'] += 1;
					$TYPO3_DB->exec_UPDATEquery($tablename, $where, $updateFields);
				} else {
					$insertFields = array (
						'pid' => $TSFE->id,
						'tstamp' => $time,
						'uid' => $uid,
						'qty' => 1
					);
					$TYPO3_DB->exec_INSERTquery($tablename, $insertFields);
				}
			}
		}
	} // triggerEvents
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_single.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/control/class.tx_ttproducts_control_single.php']);
}


?>