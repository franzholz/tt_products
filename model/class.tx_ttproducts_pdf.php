<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Franz Holzinger (franz@ttproducts.de)
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
 * functions for the PDF generation
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 */


require(t3lib_extMgm::extPath('fpdf') . 'class.tx_fpdf.php');


class tx_ttproducts_pdf extends FPDF implements t3lib_Singleton {
	private $header;
	private $footer;
	private $body;
	public $cObj;
	protected $family;
	protected $style;
	protected $size;


	public function init ($cObj, $family, $style, $size) {
		$this->family = $family;
		$this->style = $style;
		$this->size = $size;
		$this->cObj = $cObj;

		$this->SetFont($family, $style, $size);
	}

	public function setHeader ($header) {
		$this->header = $header;
	}

	public function setFooter ($footer) {
		$this->footer = $footer;
	}

	public function setBody ($body) {
		$this->body = $body;
	}

	public function Header () {
		//Police Arial gras 15
		$this->SetFont('Arial', 'B', 15);
		$this->MultiCell(0, 6, $this->header, 1);
	}

	public function Footer () {
		//Positionnement à 1,5 cm du bas
		$this->SetY(-15);
		//Police Arial gras 15
		$this->SetFont('Arial', 'I', 8);
		$this->MultiCell(0, 6, $this->footer, 1);
	}

	private function addEmptyColumns($bLastLine) {

		$row = array();
		$row['1'] = '';
		$row['2'] = '';
		$row['3'] = '';

		$this->getDimensions($widthArray);

		foreach ($row as $k => $v) {
			if ($k2 < 2) {
				$this->Cell($widthArray[$k], 6, $v, 'LR', 0);
			} else {
				$this->Cell($widthArray[$k], 6, $v, 'LR' . ($bLastLine ? 'T' : ''), 0, 'R');
			}
		}
	}

	private function getDimensions (&$widthArray) {
		//Column widths
		$widthArray = array(80, 25, 40, 45);
	}

	//Better table
	public function ImprovedTable ($header, $data) {
		$this->getDimensions($widthArray);

		$totalWidth = 0;
		foreach ($widthArray as $width) {
			$totalWidth += $width;
		}

		//Header
		for($i = 0; $i < count($header); $i++) {
			$this->Cell($widthArray[$i], 7, $header[$i], 1, 0, 'C');
		}
		$this->Ln();

		$dataCount = count($data);
		$rowCount = 4;
		$columnNo = 1;

		//Data. The keys must start with 1
		foreach($data as $k1 => $row) {
			$bLastLine = ($k1 == $dataCount);
			if ($bLastLine) {
				$this->SetFont($this->family, 'B', $this->size);
			}
			$oldRowCount = $rowCount;
			$rowCount = count($row);

			foreach ($row as $k2 => $v2) {

				if ($columnNo > 4) {
					$columnNo = 1;
				}

				if ($k2 == 0 && trim($v2) == '' && $rowCount == 4 && $oldRowCount == 1 && $columnNo == 2) {
					// skip first column which has been filled in from former row
					continue;
				}
				$l2 = intval($this->GetStringWidth($v2));
				unset($value);

				if ($l2 > $widthArray[$k2] - 5) {
					$subStringCount = intval ($l2 / ($widthArray[$k2] - 10)) + 1;
					$averageStringLength = strlen($v2) / $subStringCount;
					if (!isset($additonalRow)) {
						$additonalRow = array();
					}

					$startPosition = 0;
					for ($i = 0; $i < $subStringCount; $i++) {
						$subValue = substr($v2, $startPosition, $averageStringLength);
						$lastSpacePosition = strrpos($subValue, ' ');
						$subValue = substr($subValue, 0, $lastSpacePosition);
						$startPosition += strlen($subValue) + 1;

						if ($i == 0) {
							if ($oldRowCount > 1) {
								$value = $subValue;
							} else {
								$additonalRow[] = $subValue;
							}
						} else {
							$additonalRow[] = $subValue;
						}
					}
				} else {
					if ($oldRowCount > 1 || $rowCount > 1) {
						$value = $v2;
					} else {
						$additonalRow[] = $v2;
					}
				}

				if (isset($value)) {

					if ($k2 < 2) {
						$this->Cell($widthArray[$k2], 6, $value, 'LR', 0);
					} else {
						$this->Cell($widthArray[$k2], 6, $value, 'LR' . ($bLastLine ? 'T' : ''), 0, 'R');
					}
					$columnNo++;

					if ($columnNo <= 4) {
						continue;
					}
					$this->Ln();
				} else {
					continue;
				}

				if (isset($additonalRow) && is_array($additonalRow)) {

					foreach ($additonalRow as $k3 => $subValue) {
						$bLastSubRow = ($k3 == ($subRowCount - 1));

						$this->Cell($widthArray['0'], 6, $subValue, 'LR', ($subRowCount > 1 && !$bLastSubRow ? 1 : 0));
						$this->addEmptyColumns($bLastLine);
						$this->Ln();
					}
					$columnNo = 1;
					unset($additonalRow);
				}
			}
		}

		$this->SetFont($this->family, $this->style, $this->size);

		//Closure line
		$this->Cell(array_sum($w), 0, '', 'T');
		$this->Ln();
	}

	public function Body () {
		$tempContent = $this->cObj->getSubpart($this->body, '###PDF_TABLE_1###');
		$tempContentArray = preg_split('/[\n]+/', $tempContent);
		$dataArray = array();
		foreach ($tempContentArray as $tmpContent) {
			if (trim($tmpContent) != '') {
				$dataArray[] = preg_split('/\|/', $tmpContent, -1, PREG_SPLIT_NO_EMPTY);
			}
		}

		$header = $dataArray['0'];
		unset($dataArray['0']);
		$this->ImprovedTable($header, $dataArray);

		$restBody = $this->cObj->substituteMarkerArrayCached(
				$this->body,
				array(),
				array('###PDF_TABLE_1###' => ''),
				array()
			);

		// $this->SetX($xPos);
		$this->MultiCell(0, 4, $restBody, '1L');
	}
}


?>
