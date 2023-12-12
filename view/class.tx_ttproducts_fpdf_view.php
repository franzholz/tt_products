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
 * functions for the creation of PDF files using FPDF
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class tx_ttproducts_fpdf_view
{
    /**
     * generates the bill as a PDF file.
     *
     * @param	string		reference to an item array with all the data of the item
     *
     * @return	string / boolean	returns the absolute filename of the PDF bill or false
     * 		 			for the tt_producst record, $row
     *
     * @access private
     */
    public function generate(
        $cObj,
        $basketView,
        $infoViewObj,
        $templateCode,
        $mainMarkerArray,
        $itemArray,
        $calculatedArray,
        $orderArray,
        $productRowArray,
        $basketExtra,
        $basketRecs,
        $typeCode,
        $generationConf,
        $absFileName
    ) {
        $result = false;
        $renderCharset = 'UTF-8';

        // require_once '/path/to/src/PhpWord/Autoloader.php';
        // \PhpOffice\PhpWord\Autoloader::register();

        $subpart = $typeCode . '_PDF_HEADER_TEMPLATE';
        $header = $basketView->getView(
            $errorCode,
            $templateCode,
            $typeCode,
            $infoViewObj,
            false,
            true,
            $calculatedArray,
            false,
            $subpart,
            $mainMarkerArray,
            '',
            $itemArray,
            $notOverwritePriceIfSet = false,
            ['0' => $orderArray],
            $productRowArray,
            $basketExtra,
            $basketRecs
        );
        $subpart = $typeCode . '_PDF_TEMPLATE';
        $body = $basketView->getView(
            $errorCode,
            $templateCode,
            $typeCode,
            $infoViewObj,
            false,
            true,
            $calculatedArray,
            false,
            $subpart,
            $mainMarkerArray,
            '',
            $itemArray,
            $notOverwritePriceIfSet = false,
            ['0' => $orderArray],
            $basketExtra,
            $basketRecs
        );

        $subpart = $typeCode . '_PDF_FOOTER_TEMPLATE';
        $footer = $basketView->getView(
            $errorCode,
            $templateCode,
            $typeCode,
            $infoViewObj,
            false,
            true,
            $calculatedArray,
            false,
            $subpart,
            $mainMarkerArray,
            '',
            $itemArray,
            $notOverwritePriceIfSet = false,
            ['0' => $orderArray],
            $productRowArray,
            $basketExtra,
            $basketRecs
        );

        $csConvObj = GeneralUtility::makeInstance(CharsetConverter::class);
        $header = $csConvObj->conv(
            $header,
            $renderCharset,
            'iso-8859-1'
        );

        $body = $csConvObj->conv(
            $body,
            $renderCharset,
            'iso-8859-1'
        );

        $footer = $csConvObj->conv(
            $footer,
            $renderCharset,
            'iso-8859-1'
        );

        $pdf = GeneralUtility::makeInstance('tx_ttproducts_fpdf');
        $pdf->init($cObj, 'Arial', '', 10);
        $pdf->setHeader($header);
        $pdf->setFooter($footer);
        $pdf->AddPage();
        $pdf->setBody($body);
        $pdf->Body();

        // $pdf->MultiCell(0, 4, $body, 1);
        $pdf->Output($absFileName, 'F');
        $result = $absFileName;

        return $result;
    }
}
