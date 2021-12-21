<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2009 Franz Holzinger (franz@ttproducts.de)
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
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */


class tx_ttproducts_integrate implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Includes the locallang.xlf and returns the $LOCAL_LANG array found in that file.
	 */
	public function generateArticleMM()	{
		$count = 0;
		$mmTable = 'tt_products_products_mm_articles';

		$resArticle = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','tt_products_articles', 'uid_product>0 AND deleted=0');
		while ($rowArticle = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($resArticle) )	{
			$prodUid = intval($rowArticle['uid_product']);
			$where = 'uid_foreign='.$rowArticle['uid'].' AND uid_local='.$prodUid;
			$resMM = $GLOBALS['TYPO3_DB']->exec_SELECTquery('count(*)',$mmTable, $where);

			$rowMM = $GLOBALS['TYPO3_DB']->sql_fetch_row($resMM);
			$GLOBALS['TYPO3_DB']->sql_free_result($resMM);
			$productsCount = $rowMM[0];

			if ($productsCount == 0)	{
				$insertFields = array();
				$insertFields['uid_local'] = $prodUid;
				$insertFields['uid_foreign'] = intval($rowArticle['uid']);

				if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] == '2')	{
					$insertFields['crdate'] = $insertFields['tstamp'] = time();
					$insertFields['pid'] = $rowArticle['pid'];
					$insertFields['sorting'] = 1;
				}
				$GLOBALS['TYPO3_DB']->exec_INSERTquery($mmTable, $insertFields);

				$updateFields = array();
				$updateFields['article_uid'] = 'article_uid + 1';
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tt_products', 'uid='.$prodUid, $updateFields, 'article_uid');
				$count++;
			}
		}

		return $count;
	}
}

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_integrate.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/lib/class.tx_ttproducts_integrate.php']);
}

