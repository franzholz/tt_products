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
 * functions for the page id list
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\Div2007\Utility\FrontendUtility;


class tx_ttproducts_pid_list {
	protected $pid_list;				// list of page ids
	protected $recursive;
	protected $pageArray = array();		// pid_list as array
	protected $allPages = false;


	/**
	 * Sets the pid_list internal var
	 */
	public function setPidlist ($pid_list) {
		if ($pid_list == -1) {
			$this->allPages = true;
			$this->recursive = 0;
		}
		$this->pid_list = $pid_list;
	}


	/**
	 * gets the latest applied recursive
	 */
	public function getRecursive () {
		return $this->recursive;
	}


	/**
	 * Gets the pid_list internal var or the child pid_list of the page id as parameter
	 */
	public function getPidlist ($pid='') {
		$rc = '';
		if ($pid) {
			$this->applyRecursive(1,$pid,false);
			$rc = $pid;
		} else {
			$rc = $this->pid_list;
		}
		return $rc;
	}


	/**
	 * Sets the pid_list internal var
	 */
	public function setPageArray () {
		$this->pageArray = GeneralUtility::trimExplode (',', $this->pid_list);
		$this->pageArray = array_flip($this->pageArray);
	}


	public function getPageArray ($pid = 0) {
		if (
            $pid
        )	{
			$rc = isset($this->pageArray[$pid]) || $this->allPages;
		} else {
			$rc = $this->pageArray;
		}

		return $rc;
	}


	/**
	 * Extends the internal pid_list by the levels given by $recursive
	 *
	 * @param	[type]		$recursive: ...
	 * @param	[type]		$pids: ...
	 * @param	[type]		$bStore: ...
	 * @return	[type]		...
	 */
	public function applyRecursive ($recursive, &$pids, $bStore = false) {
		$cObj = FrontendUtility::getContentObjectRenderer();

		if ($pids == -1) {
			$this->allPages = true;
		}

		if ($pids != '') {
			$pid_list = &$pids;
		} else {
			$pid_list = $this->pid_list;
		}

		if (!$pid_list && !$this->allPages) {
			$pid_list = $GLOBALS['TSFE']->id;
		}

		if ($recursive && !$this->allPages) {
			// get pid-list if recursivity is enabled
			$recursive = intval($recursive);
			$this->recursive = $recursive;
			$pidSubArray = array();

			$pid_list_arr = explode(',', $pid_list);
			foreach ($pid_list_arr as $val) {
				$pidSub = $cObj->getTreeList($val, $recursive);
				if ($pidSub != '') {
					$pidSubArray[] = $pidSub;
				}
			}

			$pid_list .= ',' . implode(',', $pidSubArray);
			$pid_list_arr = explode(',', $pid_list);
			$flippedArray = array_flip($pid_list_arr);
			$pid_list_arr = array_keys($flippedArray);
			sort($pid_list_arr, SORT_NUMERIC);
			$pid_list = implode(',', $pid_list_arr);
			$pid_list = preg_replace('/^,/', '', $pid_list);
		}

		if ($bStore) {
			$this->pid_list = $pid_list;
		}
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_pid_list.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/model/class.tx_ttproducts_pid_list.php']);
}

