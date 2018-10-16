<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2010 Franz Holzinger (franz@ttproducts.de)
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
 * hook functions for TYPO3 FE extensions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


class tx_ttproducts_hooks_be implements t3lib_Singleton {

	public function displayCategoryTree ($PA, $fobj) {
		$result = FALSE;

		if (t3lib_extMgm::isLoaded('mbi_products_categories')) {
			$treeObj = FALSE;

			if (class_exists('JambageCom\\MbiProductsCategories\\View\\TreeSelector')) {
				$treeObj = t3lib_div::makeInstance('JambageCom\\MbiProductsCategories\\View\\TreeSelector');
			} else if (class_exists('tx_mbiproductscategories_treeview')) {
				$treeObj = t3lib_div::makeInstance('tx_mbiproductscategories_treeview');
			}

			if (is_object($treeObj)) {
				$result = $treeObj->displayCategoryTree($PA, $fobj);
			}
		}

		return $result;
	}


	public function displayOrderHtml ($PA, $fobj) {
		$result = 'ERROR';

		$table = $PA['table'];
		$field = $PA['field'];
		$row   = $PA['row'];

			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];
		$orderData = unserialize($row['orderData']);

		if (
			is_array($orderData) &&
			isset($orderData['html_output']) &&
			isset($config['parameters']) &&
			is_array($config['parameters']) &&
			isset($config['parameters']['format']) &&
			$config['parameters']['format'] == 'html'
		) {
			$result = $orderData['html_output'];
		}

		return $result;
	}
	
	public function tceSingleOrder($PA, $fobj) {
	    $ret = 'Keine Produkte bestellt.';
	    
	    $table = $PA['table'];
	    $field = $PA['field'];
	    $row   = $PA['row'];

	    $config = $PA['fieldConf']['config'];
	    $orderData = unserialize($row['orderData']);
	    
	    // Typoscript is not set in backend. Hard-coding this for now.
	    $cnf = t3lib_div::makeInstance('tx_ttproducts_config');
	    $cnf->conf['priceDec'] = 2;
	    $cnf->conf['priceDecPoint'] = ',';
	    $cnf->conf['priceThousandPoint'] = '.';
	    
	    $priceViewObj = t3lib_div::makeInstance('tx_ttproducts_field_price_view');
	    
	    
	    if (
	        is_array($orderData) && 
	        isset($orderData['itemArray']) &&
	        isset($orderData['calculatedArray'])
	        ) {
	            $array = $orderData['calculatedArray'];
	            $count = $array['count'];
	            $shipping = $priceViewObj->priceFormat($array['priceTax']['shipping']);
	            $total = $priceViewObj->priceFormat($array['priceTax']['total']);
	            
	            $data = reset($orderData['itemArray']);
	            $data = reset($data);
	            if (is_array($data) && count($data) > 0) {
	                $ret = $count . " Artikel bestellt:";
	                $ret .="<ul>";
	                foreach($data as $row) {
	                    $count = $row['count'];
	                    $price = $priceViewObj->priceFormat($row['priceTax']);
	                    $title = $row['rec']['title'];
	                    
                        $ret .= "<li>$count x $title ($price)</li>";
	                }
	                $ret .= "</ul>";
	                
	                $ret .= "Gesamt: $total<br>(inkl. $shipping Versandkosten)";
	                
	            }
	        }
	        
	    
	    return $ret;
	}
}


if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_be.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/tt_products/hooks/class.tx_ttproducts_hooks_be.php']);
}

?>
