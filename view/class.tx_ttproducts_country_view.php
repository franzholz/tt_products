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
 * functions for the static_info_countries table view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 */
class tx_ttproducts_country_view extends tx_ttproducts_table_base_view
{
    /**
     * Template marker substitution
     * Fills in the markerArray with data for a country.
     *
     * @param	array		reference to an item array with all the data of the item
     * @param	array		marker array
     *
     * @return	array
     *
     * @access private
     */
    public function getRowMarkers(&$markerArray, $prefix, $row): void
    {
        $thePrefix = $prefix . '_' . $this->getMarker() . '_';
        foreach ($row as $field => $value) {
            $markerKey = $thePrefix . strtoupper($field);
            $markerArray['###' . $markerKey . '###'] =
                htmlspecialchars($value);
        }
    }
}
