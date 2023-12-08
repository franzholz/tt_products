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
 * interface for the variant classes
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */



interface tx_ttproducts_variant_int {
    const EXTERNAL_QUANTITY_SEPARATOR = '_'; // to separate any information about the external table, e.g. its type and uid "fal=4"
    const INTERNAL_VARIANT_SEPARATOR = ';';

    public function init($itemTable, $tablename, $useArticles);
    public function getSeparator();
    public function getSplitSeparator();
    public function getImplodeSeparator();
    public function getUseArticles();
    public function modifyRowFromVariant (&$row, $variant='');
    public function getVariantFromRow ($row);
    public function getVariantFromProductRow ($row, $variantRow, $useArticles);
    public function getVariantFromRawRow ($row);
    public function getVariantRow($row='',$varianArray = []);
    public function getTableUid ($table, $uid);
    public function getSelectableArray();
    public function getVariantValuesByArticle($articleRowArray, $productRow,$withSemicolon = false);
    public function filterArticleRowsByVariant($row, $variant, $articleRows, $bCombined = false);
    public function getFieldArray();
    public function getSelectableFieldArray();
    public function getAdditionalKey();
}

