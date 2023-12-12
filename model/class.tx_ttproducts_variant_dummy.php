<?php

use TYPO3\CMS\Core\SingletonInterface;

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * Part of the tt_products (Shop System) extension.
 *
 * article functions without object instance
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
class tx_ttproducts_variant_dummy implements tx_ttproducts_variant_int, SingletonInterface
{
    private array $selectableArray = [];
    public $conf;	// reduced local conf

    /**
     * setting the local variables.
     */
    public function init($itemTable, $tablename, $useArticles)
    {
        return true;
    } // init

    /**
     * getting the articles for a product.
     */
    public function getUseArticles()
    {
    }

    public function getSeparator()
    {
        return '---';
    }

    public function getSplitSeparator()
    {
        return '---';
    }

    public function getImplodeSeparator()
    {
        return '---';
    }

    /**
     * fills in the row fields from the variant extVar string.
     *
     * @param	array		the row
     * @param	string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see getVariantFromRow
     */
    public function modifyRowFromVariant(&$row, $variant = '')
    {
    }

    /**
     * Returns the variant extVar string from the variant values in the row.
     *
     * @param	array		the row
     *
     * @return  string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see modifyRowFromVariant
     */
    public function getVariantFromRow($row)
    {
    }

    public function getVariantFromProductRow($row, $variantRow, $useArticles)
    {
    }

    /**
     * Returns the variant extVar string from the incoming raw row into the basket.
     *
     * @param	array	the basket raw row
     *
     * @return  string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see modifyRowFromVariant
     */
    public function getVariantFromRawRow($row)
    {
    }

    public function getVariantRow($row = '', $varianArray = [])
    {
    }

    public function getTableUid($table, $uid)
    {
        $rc = '|' . $table . '|' . $uid;

        return $rc;
    }

    public function getSelectableArray()
    {
        return $this->selectableArray;
    }

    public function getVariantValuesByArticle($articleRowArray, $productRow, $withSemicolon = false)
    {
    }

    public function filterArticleRowsByVariant($row, $variant, $articleRows, $bCombined = false)
    {
    }

    public function getFieldArray()
    {
    }

    public function getSelectableFieldArray()
    {
        return $this->selectableFieldArray;
    }

    public function getAdditionalKey()
    {
    }
}
