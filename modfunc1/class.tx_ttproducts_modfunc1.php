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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/




/**
 * Module extension (addition to function menu) 'Move Items' for the 'tt_products' extension.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package	TYPO3
 * @subpackage	tx_ttproducts
 */
class tx_ttproducts_modfunc1 extends \TYPO3\CMS\Backend\Module\AbstractFunctionModule {

	/**
	* Returns the module menu
	*
	* @return	Array with menuitems
	*/
	public function modMenu () {
		return Array (
			'tx_ttproducts_modfunc1_pid_dest' => '',
			'tx_ttproducts_modfunc1_age' => '',
		);
	}

	/**
	* Main method of the module
	*
	* @return	HTML
	*/
	public function main () {
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!

		$theOutput .= $this->pObj->doc->spacer(5);
		if ($GLOBALS['BE_USER']->user['admin']) {
			$currId = $this->pObj->id;
			$content = sprintf($GLOBALS['LANG']->getLL('pid_src'), $currId);
		} else {
			$content = $GLOBALS['LANG']->getLL('only_admin');
		}
		$theOutput .= $this->pObj->doc->section($GLOBALS['LANG']->getLL('title'), $content, 0, 1);
		$destId = $this->pObj->MOD_SETTINGS['tx_ttproducts_modfunc1_pid_dest'];
		$age = $this->pObj->MOD_SETTINGS['tx_ttproducts_modfunc1_age'];

		if($_REQUEST['create'] != '' && $currId && $destId && $age) {
			$fieldsArray = array();
			$fieldsArray['pid'] = intval($destId);
			$day = 24 * 60 * 60;
			$time = time();
			$tstamplimit = $time - $age * $day;
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'pid=' . intval($currId) . ' AND tstamp<' . $tstamplimit, $fieldsArray);
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products_articles', 'pid=' . intval($currId) . ' AND tstamp<' . $tstamplimit, $fieldsArray);
			$content .= '<br />' . $GLOBALS['LANG']->getLL('moved');
		}

		if ($GLOBALS['BE_USER']->user['admin']) {
			$menu = array();

			$content = '';
			$content .= '<br />' . $GLOBALS['LANG']->getLL('pid_dest') . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncInput(
				$this->pObj->id,
				'SET[tx_ttproducts_modfunc1_pid_dest]',
				$destId
			);
			$content .= '<br />' . $GLOBALS['LANG']->getLL('age') . \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncInput(
				$this->pObj->id,
				'SET[tx_ttproducts_modfunc1_age]',
				$age
			);
			$content .= '<br /><input type="submit" name="create" value="' . $GLOBALS['LANG']->getLL('start') . '">';
			$menu[] = $content;
			$theOutput .= $this->pObj->doc->spacer(5);
			$theOutput .= $this->pObj->doc->section('Menu', implode(' - ' , $menu) , 0, 1);
		}

		return $theOutput;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc1/class.tx_ttproducts_modfunc1.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/modfunc1/class.tx_ttproducts_modfunc1.php']);
}

