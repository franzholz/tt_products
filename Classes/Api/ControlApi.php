<?php

namespace JambageCom\TtProducts\Api;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is
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
 * control functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class ControlApi {
	static protected $conf = array();
    static protected $cObj = null;

    static public function init ($conf, $cObj) {
        static::$conf = $conf;
        static::$cObj = $cObj;
    }

    static public function getConf () {
        return static::$conf;
    }

    static public function getCObj () {
        return static::$cObj;
    }

	static public function isOverwriteMode ($infoArray) {
		$overwriteMode = false;
		$conf = self::getConf();

		$checkField = \JambageCom\TtProducts\Api\CustomerApi::getPossibleCheckField();

		if (
			(
				!$infoArray['billing'] ||
				!$infoArray['billing'][$checkField] ||
				$conf['editLockedLoginInfo'] ||
				!empty($infoArray['billing']['error'])
			) &&
			$conf['lockLoginUserInfo']
		) {
			$overwriteMode = true;
		}

		return $overwriteMode;
	}
}


