<?php
/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Class that adds the wizard icon.
 *
 * @author  Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_wizicon {

    /**
     * Processing the wizard items array
     *
     * @param array $wizardItems The wizard items
     * @return array Modified array with wizard items
     */
    public function proc($wizardItems) {
        $params = '&defVals[tt_content][CType]=list&defVals[tt_content][list_type]=5&defVals[tt_content][select_key]=HELP';
        $wizardItems['plugins_tx_ttproducts_pi1'] = array(
            'icon' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath(TT_PRODUCTS_EXT) . 'Resources/Public/Images/PluginWizard.png',
            'title' => $GLOBALS['LANG']->sL('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:plugins_title'),
            'description' => $GLOBALS['LANG']->sL('LLL:EXT:' . TT_PRODUCTS_EXT . '/locallang.xml:plugins_description'),
            'params' => $params
        );


        return $wizardItems;
    }
}


