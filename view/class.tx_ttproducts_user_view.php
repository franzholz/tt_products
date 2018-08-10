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
 * Part of the tt_products (Shop System) extension and for testing purpose.
 *
 * functions for user defined output (use the hooks or XCLASS this by your extensions)
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


class tx_ttproducts_user_view implements t3lib_Singleton  {

	public function &printView (
		$pibaseClass,
		&$templateCode,
		$theCode
	)	{
		$cnf = t3lib_div::makeInstance('tx_ttproducts_config');
		$conf = &$cnf->getConf();
		$content = '';
		$num = $theCode{4};

		$pibaseObj = t3lib_div::makeInstance('' . $pibaseClass);
		$cObj = $pibaseObj->cObj;

		if (isset($conf['USEROBJ' . $num . '.']) && is_array($conf['USEROBJ' . $num . '.']))	{
			$content = $cObj->cObjGetSingle($conf['USEROBJ' . $num], $conf['USEROBJ' . $num . '.']);
		}
		return $content;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_user_view.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/view/class.tx_ttproducts_user_view.php']);
}


?>
