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
 * Search plugins for the shop system.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_pi_search
{
    public $cObj;

    public function setContentObjectRenderer(ContentObjectRenderer $cObj): void
    {
        $this->cObj = $cObj;
    }

    /**
     * Main method. Call this from TypoScript by a USER cObject.
     */
    public function main($content, $conf)
    {
        $pibaseObj = GeneralUtility::makeInstance('tx_ttproducts_pi_search_base');
        $pibaseObj->setContentObjectRenderer($this->cObj);
        $languageSubpath = '/Resources/Private/Language/';

        if (!empty($conf['templateFile'])) {
            $content = $pibaseObj->main($content, $conf);
        } else {
            $errorText = $GLOBALS['TSFE']->sL(
                'LLL:EXT:' . TT_PRODUCTS_EXT . $languageSubpath . 'PiSearch/locallang.xlf:no_template'
            );

            $content = str_replace('|', 'plugin.tt_products_pi_search.templateFile', $errorText);
        }

        return $content;
    }
}
