<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2016 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the creation of PDF files
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */

 
use TYPO3\CMS\Core\Utility\GeneralUtility;



class tx_ttproducts_pdf_view {

	/**
	 * generates the bill as a PDF file
	 *
	 * @param	string		reference to an item array with all the data of the item
	 * @return	string / boolean	returns the absolute filename of the PDF bill or false
	 * 		 			for the tt_producst record, $row
	 * @access private
	 */
	public function generate (
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

		$infoArray = $infoViewObj->getInfoArray();
		$priceViewObj = GeneralUtility::makeInstance('tx_ttproducts_field_price_view');

		if (
			!empty($itemArray) &&
			!empty($infoArray) &&
			is_array($generationConf['handleLib.'])
		) {
			switch (strtoupper($generationConf['handleLib'])) {
				case 'PHPWORD':
                    $pathsite = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
					$itemObj = GeneralUtility::makeInstance('tx_ttproducts_basketitem');
					$path = $pathsite . $generationConf['handleLib.']['path'];

 					GeneralUtility::requireOnce ($path . '/src/PhpWord/Autoloader.php');
					\PhpOffice\PhpWord\Autoloader::register();

					$phpWord = new \PhpOffice\PhpWord\PhpWord();
					$templateFile = $pathsite . $generationConf['handleLib.']['template'];

					if (!file_exists($templateFile)) {
						return false;
					}
					$nameInfo = pathinfo($templateFile);
					$document = $phpWord->loadTemplate($templateFile);
					$document->setValue('date', date('d.m.Y')); // On section/content
					foreach ($infoArray['billing'] as $field => $value) {
						$document->setValue('billing_' . $field, $value);
					}

                    $document->setValue('trackingcode', $orderArray['tracking_code']);
                    $trackingParts = explode('-', $orderArray['tracking_code']);
                    $document->setValue('trackingno', $trackingParts['0']);
					$document->setValue('billno', $orderArray['bill_no']); // On section/content
					$document->setValue('cnum', $infoArray['billing']['cnum']); // On section/content

					$lineCount = 0;
					// loop over all items in the basket indexed by sorting text
					foreach ($itemArray as $sort => $actItemArray) {
						$lineCount += count($actItemArray);
					}
					$document->cloneRow('article_title', $lineCount);

					$lineCount = 0;
					// loop over all items in the basket indexed by sorting text
					foreach ($itemArray as $sort => $actItemArray) {
						foreach ($actItemArray as $k1 => $actItem) {
							$extArray = [];
							$lineCount++;
							$row = $actItem['rec'];

							if (
								isset($row['ext']) &&
								is_array($row['ext'])
							) {
								$extArray = $row['ext'];
							} else {
								continue;
							}
							$outputRow = $row;

							if (
								isset($extArray['mergeArticles']) &&
								is_array($extArray['mergeArticles'])
							) {
								$outputRow = $extArray['mergeArticles'];
							}
							if (
								isset($extArray['records']) &&
								is_array($extArray['records'])
							) {
								$newTitleArray = [];
								$externalRowArray = $extArray['records'];

								foreach ($externalRowArray as $tablename => $externalRow) {
									$newTitleArray[] = $externalRow['title'];
								}
								$outputRow['title'] = implode(' | ', $newTitleArray);
							}

							foreach ($outputRow as $field => $value) {
								if (
									$field != 'ext' &&
									!strpos($field, '_uid') &&
									!strpos($field, '_id') &&
									is_string($value)
								) {
									if (strpos($field, 'price') !== false) {
										$value = $priceViewObj->priceFormat($value);
									}
									$document->setValue('article_' . $field . '#' . $lineCount, $value);
								}
							}
							$quantity = $itemObj->getQuantity($actItem);
							$document->setValue('count#' . $lineCount, $quantity);

							$document->setValue('price1#' . $lineCount, $priceViewObj->priceFormat($actItem['priceTax']));
							$document->setValue('price1total#' . $lineCount, $priceViewObj->priceFormat($actItem['priceTax'] * $quantity));
						}
					}

 					$document->setValue('pricenotaxtotal', $priceViewObj->priceFormat($calculatedArray['priceNoTax']['total']['ALL']));

					if (
						isset($calculatedArray['priceNoTax']) &&
						is_array($calculatedArray['priceNoTax']) &&
						isset($calculatedArray['priceNoTax']['sametaxtotal']) &&
						is_array($calculatedArray['priceNoTax']['sametaxtotal']) &&
						!empty($calculatedArray['priceNoTax']['sametaxtotal'])
					) {
						$lineCount = 0;
						foreach ($calculatedArray['priceNoTax']['sametaxtotal'] as $countryCode => $taxRow) {
							if ($countryCode == 'ALL' || !is_array($taxRow)) {
								continue;
							}
							$lineCount += count($taxRow);
						}
						$document->cloneRow('onlytax_line', $lineCount);

						$lineCount = 0;
						foreach ($calculatedArray['priceNoTax']['sametaxtotal'] as $countryCode => $taxRow) {
							if ($countryCode == 'ALL') {
								continue;
							}
							foreach ($taxRow as $tax => $value) {
								$lineCount++;
								$document->setValue('onlytax_line#' . $lineCount, '');
								$document->setValue('country#' . $lineCount, $countryCode);
								$document->setValue('onlytax#' . $lineCount, $tax . ' %');
								$taxValue = $value * ($tax / 100);
								$document->setValue('priceonlytaxtotal#' . $lineCount, $priceViewObj->priceFormat($taxValue));
							}
						}
					}

					$document->setValue('pricetaxtotal', $priceViewObj->priceFormat($calculatedArray['priceTax']['total']['ALL']));
					$typeArray = ['payment', 'shipping'];
					$fieldArray = ['title', 'price'];
					foreach ($typeArray as $type) {
						foreach ($fieldArray as $field) {
							$value = '';
							if (
								isset($basketExtra[$type . '.']) &&
								isset($basketExtra[$type . '.'][$field])
							) {
								$value = $basketExtra[$type . '.'][$field];
							}

							if ($field == 'price') {
								$value = $priceViewObj->priceFormat($value);
							}

							$document->setValue($type . '_' . $field, $value);
						}
					}
					$header = $generationConf['handleLib.']['rendererLibrary.']['marks.']['header'];
					$headerArray = explode(PHP_EOL, $header);

					foreach ($headerArray as $k => $header) {
						$document->setValue('header_' . ($k + 1), $header);
					}

					$name = $nameInfo['dirname'] . '/' . $nameInfo['filename'] . '-out'. '.docx';
					$document->saveAs($name);
					GeneralUtility::requireOnce ($path . '/samples/Sample_Footer.php');
 					$phpWord = \PhpOffice\PhpWord\IOFactory::load($name);

					if (is_array($generationConf['handleLib.']['rendererLibrary.'])) {

                        $pathsite = \TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/';
						$rendererName = \PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF;	//   PDF_RENDERER_MPDF PDF_RENDERER_TCPDF
						$rendererLibraryPath = $pathsite . $generationConf['handleLib.']['rendererLibrary.']['path'];
						\PhpOffice\PhpWord\Settings::setPdfRenderer($rendererName, $rendererLibraryPath);
						$objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'PDF');
						$name = $nameInfo['dirname'] . '/' . $nameInfo['filename'] . '-' . $orderArray['tracking_code'] . '.pdf';
						$objWriter->save($name);

						$result = $name;
					}

					break;
				default:
					break;
			}
		}

		return $result;
	}
}

