<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2015 Franz Holzinger (franz@ttproducts.de)
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
 * Module extension (addition to function menu2 'Create Records in Alternative Languages' for the 'tt_products' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	tx_ttproducts
 */
class tx_ttproducts_modfunc2 extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	* Returns the module menu
	*
	* @return	Array with menuitems
	*/
	public function modMenu () {
		return Array (
		);
	}

	/**
	* Main method of the module
	*
	* @return	HTML
	*/
	public function main () {
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		$standardFields = array('pid', 'cruser_id', 'hidden', 'starttime', 'endtime', 'fe_group');

		$theOutput .= $this->pObj->doc->spacer(5);
		if ($GLOBALS['BE_USER']->user['admin']) {
			$currId = $this->pObj->id;
			$content = sprintf($GLOBALS['LANG']->getLL('pid_src'), $currId);
		} else {
			$content = $GLOBALS['LANG']->getLL('only_admin');
		}
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $content, 0, 1);

		if($_REQUEST['create'] != '' && $currId) {

			$content = '';
			$time = time();
			$fieldsArray = array();
			$pid = intval($currId);
			$fieldsArray['tstamp'] = $fieldsArray['crdate'] = $time;
			$uidNotFoundArray = array();

			$rowArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_products_texts', 'pid=' . $pid . ' AND deleted=0');

			if (is_array($rowArray) && count($rowArray)) {

				foreach ($rowArray as $row) {
					$insertArray = $fieldsArray;
					foreach ($row as $field => $value) {
						if (
							in_array($field, $standardFields) ||
							isset($GLOBALS['TCA']['tt_products_texts']['columns'][$field]) &&
							isset($GLOBALS['TCA']['tt_products_texts_language']['columns'][$field])
						) {
							$insertArray[$field] = $value;
						} else {
							// nothing
						}
					}
					$insertArray['text_uid'] = $row['uid'];
					$insertArray['parenttable'] = 'tt_products_language';

					// determine parentid
					$foreignRowArray = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'tt_products_language', 'prod_uid=' . $row['parentid']);

					if ($foreignRowArray) {
						foreach ($foreignRowArray as $foreignRow) {
							$updateForeignRow = 0;
							$insertArray['pid'] = $foreignRow['pid'];
							$insertArray['parentid'] = $foreignRow['uid'];
							$insertArray['sys_language_uid'] = $foreignRow['sys_language_uid'];

							$where = 'text_uid=' . $insertArray['text_uid'] . ' AND sys_language_uid=' . $insertArray['sys_language_uid'];
							$currentRow = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'tt_products_texts_language', $where);

							if ($currentRow) {
								if ($currentRow['parentid'] == 0) {
									$updateArray = array();
									$updateArray['parentid'] = $insertArray['parentid'];
									$updateArray['parenttable'] = $insertArray['parenttable'];
									$updateArray['tstamp'] = $insertArray['tstamp'];
									$where = 'uid=' . $currentRow['uid'];
									$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products_texts_language', $where, $updateArray);
									$error = $GLOBALS['TYPO3_DB']->sql_error();
									$updateForeignRow = $currentRow['uid'];
								}
							} else {
								$GLOBALS['TYPO3_DB']->exec_INSERTquery('tt_products_texts_language', $insertArray);
								$updateForeignRow = $GLOBALS['TYPO3_DB']->sql_insert_id();
							}

							if ($updateForeignRow) {
								$where = 'uid=' . $foreignRow['uid'];
								$updateArray = array();
								$updateArray['tstamp'] = $insertArray['tstamp'];
								$updateArray['text_uid'] += 1;
								$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products_language', $where, $updateArray);
							}
						}
					} else {
						$uidNotFoundArray[] = $row['parentid'];
					}
				}

				if (count($uidNotFoundArray)) {
					$content .= '<br />' . $GLOBALS['LANG']->getLL('no_alternative_product') . ': ' . implode(',', $uidNotFoundArray);
				}

				$content .= '<br />' . $GLOBALS['LANG']->getLL('created');
			} else {
				$content = '<br />' . $GLOBALS['LANG']->getLL('no_texts');
			}

			$theOutput .= $content;
		}

		if ($GLOBALS['BE_USER']->user['admin']) {
			$menu = array();
			$content = '';
			$content .= '<br /><input type="submit" name="create" value="' . $GLOBALS['LANG']->getLL('start') . '">';
			$menu[] = $content;
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .= $this->pObj->doc->section('Menu', implode(' - ' , $menu) , 0, 1);
		}

		return $theOutput;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc2/class.tx_ttproducts_modfunc2.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc2/class.tx_ttproducts_modfunc2.php']);
}
