<?php

namespace JambageCom\TtProducts\Hooks;

/***************************************************************
*  Copyright notice
*
*  (c) 2019 Franz Holzinger <franz@ttproducts.de>
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
*  but WITHOUT ANY WARRANTY; w+ithout even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Part of the tt_products (Shop System) extension.
 *
 * hook for front end processing like after a login
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
class FrontendProcessor
{
    public function loginConfirmed($params, $pObj)
    {
        $conf = $GLOBALS['TSFE']->tmpl->setup['plugin.'][TT_PRODUCTS_EXT . '.'];

        \tx_ttproducts_control_memo::copySession2Feuser($params, $pObj, $conf);
        $this->resetAdresses($params, $pObj);
    }

    public function resetAdresses(&$params, $pObj)
    {
        $recs = \tx_ttproducts_control_basket::getStoredRecs();

        if (isset($recs) && is_array($recs)) {
            unset($recs['personinfo']);
            unset($recs['delivery']);
            \tx_ttproducts_control_basket::setStoredRecs($recs);
        }
    }
}
