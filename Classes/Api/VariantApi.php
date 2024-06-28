<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/*
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
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the variants
 * former class tx_ttproducts_variant
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class VariantApi implements SingletonInterface
{
    public const EXTERNAL_QUANTITY_SEPARATOR = '_'; // to separate any information about the external table, e.g. its type and uid "fal=4"
    public const EXTERNAL_RECORD_SEPARATOR = '|records:'; // to separate the variant part from the external record. E.g.: ;;;;;;;;;;;;;;;;;;;;;;;;;|records:dl=415_fal=959

    public const INTERNAL_VARIANT_SEPARATOR = ';';
    public const INTERNAL_VARIANT_FIELD_2_VALUE_SEPARATOR = ':';

    protected $variantConf;	// reduced local conf
    private $useArticles;
    private array $selectableArray = [];
    public $fieldArray = [];	// array of fields which are variants with ';' or by other characters separated values
    private array $selectableFieldArray = [];
    public $firstVariantRow = '';
    public $additionalKey;
    protected $additionalField = 'additional';
    private string $separator = ';';
    private string $splitSeparator = ';';
    private string $implodeSeparator = ';';

    /**
     * setting the local variables.
     */
    public function init(
        array $variantConf,
        $useArticles,
        array $selectableArray,
        array $firstVariantArray
    ): bool {
        $this->variantConf = $variantConf;
        $this->useArticles = $useArticles;
        $this->selectableArray = $selectableArray;
        $fieldArray = $variantConf;
        $additionalKey = '';

        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['variantSeparator'])) {
            $this->setSeparator($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['variantSeparator']);
        }

        $variantSeparator = $this->getSeparator();
        $splitSeparator = $variantSeparator;
        $implodeSeparator = $variantSeparator;

        if (
            strpos($splitSeparator, '\\n') !== false ||
            strpos($splitSeparator, '\\r') !== false
        ) {
            $separator = str_replace('\\r\\n', '\\n', $splitSeparator);
            $separator = str_replace('\\r', '\\n', $separator);
            $splitSeparator = str_replace('\\n', '(\\r\\n|\\n|\\r)', $separator);
            $implodeSeparator = str_replace('\\n', PHP_EOL, $separator);
        }
        $this->setSplitSeparator($splitSeparator);
        $this->setImplodeSeparator($implodeSeparator);

        $this->firstVariantRow = implode($splitSeparator, $firstVariantArray);
        if (isset($additionalKey)) {
            unset($fieldArray[$additionalKey]);
        }
        $this->fieldArray = $fieldArray;
        $this->additionalKey = $additionalKey;

        return true;
    } // init

    // neu FHO Anfang
    public function getParams(
        &$selectableArray,
        &$selectableFieldArray,
        &$firstVariantArray,
        array $conf,
        array $variantConf
    ): void {
        $fieldArray = $variantConf;
        $selectableArray = [];
        $firstVariantArray = [];

        foreach ($fieldArray as $k => $field) {
            if ($field == $this->additionalField) {
                $additionalKey = $k;
            } elseif (intval($conf[$this->getSelectConfKey($field)])) {
                $selectableArray[$k] =
                    intval($conf[$this->getSelectConfKey($field)]);
                $selectableFieldArray[$k] = $field;
                $firstVariantArray[$k] = 0;
            } else {
                $firstVariantArray[$k] = '';
            }
        }
    }

    public function getInternalFilename($type)
    {
        $result = false;
        $filenames = [
            'variantConf' => 'tt_products-variants.xml',
            'selectable' => 'tt_products-selectable.xml',
            'firstVariant' => 'tt_products-firstvariant.xml',
        ];

        if (isset($filenames[$type])) {
            $result = Environment::getVarPath() . '/' . $filenames[$type];
        }

        return $result;
    }
    // neu FHO Ende

    public function getVariantConf()
    {
        return $this->variantConf;
    }

    public function getAdditionalField()
    {
        return $this->additionalField;
    }

    public function setSeparator($separator): void
    {
        $this->separator = $separator;
    }

    public function getSeparator(): string
    {
        return $this->separator;
    }

    public function setSplitSeparator($separator): void
    {
        $this->splitSeparator = $separator;
    }

    public function getSplitSeparator(): string
    {
        return $this->splitSeparator;
    }

    public function setImplodeSeparator($separator): void
    {
        $this->implodeSeparator = $separator;
    }

    public function getImplodeSeparator(): string
    {
        return $this->implodeSeparator;
    }

    // 	public function getUseArticles () {
    // 		return $this->useArticles;
    // 	}
    //

    public function getSelectConfKey($field)
    {
        $rc = 'select' . ucfirst($field);

        return $rc;
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
    public function modifyRowFromVariant(
        &$row,
        $useArticles, // neu FHO
        $variant = ''
    ): void {
        if (!$variant) {
            $variant = $this->getVariantFromRow($row);
        }
        $variantSeparator = $this->getSplitSeparator();

        if (
            $variant != '' &&
            (
                in_array($useArticles, [1, 3]) ||
                !$useArticles && count($this->selectableArray)
            )
        ) {
            $variantArray =
                explode(
                    static::INTERNAL_VARIANT_SEPARATOR,
                    $variant
                );
            $fieldArray = $this->getFieldArray();
            $count = 0;

            foreach ($fieldArray as $key => $field) {
                if (!empty($this->selectableArray[$key])) {
                    if (
                        isset($variantArray[$count])
                    ) {
                        $variantValueArray = [];

                        if (isset($row[$field]) && strlen($row[$field])) {
                            $theVariant = $row[$field];
                            $variantValueArray =
                                preg_split(
                                    '/[\h]*' . $variantSeparator . '[\h]*/',
                                    $theVariant,
                                    -1,
                                    PREG_SPLIT_NO_EMPTY
                                );
                        }
                        $variantIndex = $variantArray[$count];
                        if (isset($variantValueArray[$variantIndex])) {
                            $row[$field] = $variantValueArray[$variantIndex];
                        } elseif ( // is it a select or radio box ?
                            $this->selectableArray[$key] == 1 ||
                            $this->selectableArray[$key] == 2
                        ) {
                            $row[$field] = $variantValueArray['0'] ?? '';
                        } else {
                            $row[$field] = '';
                        }
                    }
                    $count++;
                }
            }
        }
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
        $variant = '';

        if (isset($row['ext'])) {
            $extArray = $row['ext'];

            if (
                isset($extArray['tt_products']) &&
                is_array($extArray['tt_products'])
            ) {
                reset($extArray['tt_products']);
                $variantRow = current($extArray['tt_products']);
                if (isset($variantRow['vars'])) {
                    $variant = $variantRow['vars'];
                }
            }
        }

        return $variant;
    }

    /**
     * Returns the variant extVar number from the incoming product row and the index in the variant array.
     *
     * @param	array	the basket raw row
     *
     * @return  string	  variants separated by internal variantSeparator
     *
     * @access private
     *
     * @see modifyRowFromVariant
     */
    // neu Anfang
    public function getVariantFromProductRow(
        $row,
        $variantRow,
        $useArticles,
        $applySeparator = true
    ) {
        $result = false;
        $variantArray = [];
        $variantResultRow = [];
        $variantSeparator = $this->getSplitSeparator();

        if (
            isset($variantRow) &&
            is_array($variantRow) &&
            count($variantRow) &&
            (
                $useArticles == 1 ||
                 count($this->selectableArray)
            )
        ) {
            $fieldArray = $this->getFieldArray();
            $count = 0;
            // neu Ende

            foreach ($fieldArray as $key => $field) {
                if (!empty($this->selectableArray[$key])) {
                    $variantValue = $variantRow[$field] ?? '';

                    if ($variantValue != '' && isset($row[$field]) && strlen($row[$field])) {
                        $prodVariantArray =
                            preg_split(
                                '/[\h]*' . $variantSeparator . '[\h]*/',
                                $row[$field],
                                -1,
                                PREG_SPLIT_NO_EMPTY
                            );
                        $variantIndex = array_search($variantValue, $prodVariantArray);
                        $variantArray[] = $variantIndex;
                        $variantResultRow[$field] = $variantIndex;
                    } else {
                        $variantArray[] = '';
                    }
                    $count++;
                }
            }
        }

        if ($applySeparator) {
            $result = implode(static::INTERNAL_VARIANT_SEPARATOR, $variantArray);
        } else {
            $result = $variantResultRow;
        }

        return $result;
    }

    /**
     * Returns the variant extVar number from the incoming raw row into the basket.
     *
     * @param	array	the basket raw row
     *
     * @return  string	  variants separated by variantSeparator
     *
     * @access private
     *
     * @see modifyRowFromVariant
     */
    public function getVariantFromRawRow(
        $row,
        $useArticles, // neu FHO
        $applySeparator = true
    ) {
        $result = false;
        $variantArray = [];
        $variantRow = [];
        $selectableArray = $this->getSelectableArray();

        if (
            $useArticles == 1 ||
            count($selectableArray)
        ) {
            $fieldArray = $this->getFieldArray();
            $count = 0;

            foreach ($fieldArray as $key => $field) {
                if (!empty($selectableArray[$key])) {
                    if (isset($row[$field])) {
                        $variantValue = $row[$field] ?? '';
                        $variantArray[] = $variantValue;
                        $variantRow[$field] = $variantValue;
                    } else {
                        $variantArray[] = '';
                    }
                    $count++;
                }
            }
        }

        if ($applySeparator) {
            $result = implode(static::INTERNAL_VARIANT_SEPARATOR, $variantArray);
        } else {
            $result = $variantRow;
        }

        return $result;
    }

    /*
        public function getFirstVariantRow($row='')	{
            $rc = '';
            if (is_array($row))	{
                $fieldArray = $this->getFieldArray();
                $firstRow = $row;
                foreach ($fieldArray as $field)	{
                    $variants = $row[$field];
                    $variantArray = GeneralUtility::trimExplode (';', $variants);
                    $firstRow[$field] = $variantArray[0];
                }
                $rc = $firstRow;
            } else {
                $rc = $this->firstVariantRow;
            }
            return $rc;
        }*/

    public function getVariantRow($row = '', $variantArray = [])
    {
        $result = '';
        $variantSeparator = $this->getSplitSeparator();

        if (isset($row) && is_array($row)) {
            if (!isset($variantArray)) {
                $variantArray = [];
            }
            $fieldArray = $this->getFieldArray();
            $rcRow = $row;

            foreach ($fieldArray as $field) {
                $variants = $row[$field] ?? '';
                $tmpArray =
                    preg_split(
                        '/[\h]*' . $variantSeparator . '[\h]*/',
                        $variants,
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
                $index = ($variantArray[$field] ?? 0);
                $rcRow[$field] = $tmpArray[$index] ?? '';
            }
            $result = $rcRow;
        } else {
            $result = $this->firstVariantRow;
        }

        return $result;
    }

    // neu Anfang
    public function getVariantValuesRow($row = '')
    {
        $result = [];

        if (isset($row) && is_array($row)) {
            $fieldArray = $this->getFieldArray();
            foreach ($row as $field => $value) {
                if (in_array($field, $fieldArray)) {
                    $result[$field] = $value;
                }
            }
        }

        return $result;
    }

    public function getTableUid($table, $uid)
    {
        $rc = '|' . $table . ':' . $uid;

        return $rc;
    }

    public function getSelectableArray(): array
    {
        return $this->selectableArray;
    }

    public function getVariantValuesByArticle(
        $articleRowArray,
        $productRow,
        $withSeparator = false
    ) {
        $result = [];
        $selectableFieldArray = $this->getSelectableFieldArray();
        $variantSeparator = $this->getSplitSeparator();
        $variantImplodeSeparator = $this->getImplodeSeparator();

        foreach ($selectableFieldArray as $field) {
            if (
                isset($productRow[$field]) &&
                strlen($productRow[$field])
            ) {
                $valueArray = [];

                $productValueArray =
                    preg_split(
                        '/[\h]*' . $variantSeparator . '[\h]*/',
                        $productRow[$field],
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );

                foreach ($articleRowArray as $articleRow) {
                    $articleValueArray = [];
                    if (isset($articleRow[$field]) && strlen($articleRow[$field])) {
                        $articleValueArray =
                            preg_split(
                                '/[\h]*' . $variantSeparator . '[\h]*/',
                                $articleRow[$field],
                                -1,
                                PREG_SPLIT_NO_EMPTY
                            );
                    }

                    if (!empty($articleValueArray['0'])) {
                        $valueArray = array_merge($valueArray, $articleValueArray);
                    }
                }
                $valueArray = array_values(array_unique($valueArray));

                if (!empty($productValueArray)) {
                    $sortedValueArray = [];
                    foreach ($productValueArray as $value) {
                        if (in_array($value, $valueArray)) {
                            $sortedValueArray[] = $value;
                        }
                    }
                    $valueArray = $sortedValueArray;
                }

                if ($withSeparator) {
                    $result[$field] = implode($variantImplodeSeparator, $valueArray);
                } else {
                    $result[$field] = $valueArray;
                }
            }
        }

        return $result;
    }

    // the article rows must be in the correct order already
    public function filterArticleRowsByVariant(
        $row,
        $variant,
        $articleRowArray,
        $bCombined = false
    ) {
        $result = false;
        $variantRowArray = $this->getVariantValuesByArticle($articleRowArray, $row, false);
        $variantSeparator = $this->getSplitSeparator();

        foreach ($variantRowArray as $field => $valueArray) {
            if (
                isset($row[$field]) &&
                strlen($row[$field])
            ) {
                $variantRowArray[$field] =
                    preg_split(
                        '/[\h]*' . $variantSeparator . '[\h]*/',
                        $row[$field],
                        -1,
                        PREG_SPLIT_NO_EMPTY
                    );
            }
        }

        $variantArray =
            preg_split(
                '/[\h]*' . static::INTERNAL_VARIANT_SEPARATOR . '[\h]*/',
                $variant,
                -1
            );

        $selectableFieldArray = $this->getSelectableFieldArray();
        $possibleArticleArray = [];

        if (
            isset($articleRowArray) && is_array($articleRowArray) &&
            isset($this->selectableArray) && is_array($this->selectableArray)
        ) {
            $result = [];
        }

        foreach ($articleRowArray as $articleRow) {
            $bMatches = true;
            $vCount = 0;

            foreach ($this->selectableArray as $k => $v) {
                if ($v) {
                    $variantIndex = $variantArray[$vCount]; // $k-1
                    $field = $selectableFieldArray[$k];

                    if (
                        !MathUtility::canBeInterpretedAsInteger($variantIndex) &&
                        isset($variantRowArray[$field]) &&
                        is_array($variantRowArray[$field])
                    ) {
                        $variantIndex = array_search($variantIndex, $variantRowArray[$field]);
                    }

                    $value = $articleRow[$field];

                    if ($value != '') {
                        $valueArray = [];

                        if ($variantIndex === false) {
                            $bMatches = false;
                            break;
                        } else {
                            if (is_array($value)) {
                                // nothing
                                $valueArray = $value;
                            } elseif (strlen($value)) {
                                $valueArray =
                                    preg_split(
                                        '/[\h]*' . $variantSeparator . '[\h]*/',
                                        $value,
                                        -1,
                                        PREG_SPLIT_NO_EMPTY
                                    );
                            }
                            $variantValue = '';
                            if (
                                isset($variantRowArray[$field]) &&
                                is_array($variantRowArray[$field]) &&
                                isset($variantRowArray[$field][$variantIndex])
                            ) {
                                $variantValue = $variantRowArray[$field][$variantIndex];
                            }

                            if (!in_array($variantValue, $valueArray)) {
                                $bMatches = false;
                                break;
                            }
                        }
                    } elseif (!$bCombined) {
                        $bMatches = false;
                        break;
                    }
                }
                $vCount++;
            } // foreach ($this->selectableArray)

            if ($bMatches) {
                $result[] = $articleRow;
            }
        } // foreach ($articleRowArray)

        return $result;
    }

    // neu Anfang. Leere Varianten für E-Books
    public function getEmptyVariant()
    {
        $emptyVariantArray = [];
        $fieldArray = $this->getFieldArray();
        foreach ($fieldArray as $key => $field) {
            if (!empty($this->selectableArray[$key])) {
                $emptyVariantArray[$key] = '';
            }
        }
        $result = implode(static::INTERNAL_VARIANT_SEPARATOR, $emptyVariantArray);

        return $result;
    }

    public function getFieldArray()
    {
        return $this->fieldArray;
    }

    public function setSelectableFieldArray($selectableFieldArray): void
    {
        $this->selectableFieldArray = $selectableFieldArray;
    }

    public function getSelectableFieldArray(): array
    {
        return $this->selectableFieldArray;
    }

    public function getAdditionalKey()
    {
        return $this->additionalKey;
    }

    public function removeEmptyMarkerSubpartArray(
        &$markerArray,
        &$subpartArray,
        &$wrappedSubpartArray,
        $row,
        // 		$conf, neu
        $bHasAdditional,
        $bGiftService
    ): void {
        $areaArray = [];
        $remMarkerArray = [];
        $variantConf = $this->getVariantConf();
        $selectableArray = $this->getSelectableArray();

        if (is_array($variantConf)) {
            foreach ($variantConf as $key => $field) {
                if ($field != 'additional') {	// no additional here
                    if (
                        !isset($row[$field]) ||
                        trim($row[$field]) == '' ||
                        !$selectableArray[$key]
                    ) {
                        $remSubpartArray[] = 'display_variant' . $key;
                    } else {
                        $remMarkerArray[] = 'display_variant' . $key;
                    }
                }
            }
        }

        if ($bHasAdditional) {
            $remSubpartArray[] = 'display_variant5_isNotSingle';
            $remMarkerArray[] = 'display_variant5_isSingle';
        } else {
            $remSubpartArray[] = 'display_variant5_isSingle';
            $remMarkerArray[] = 'display_variant5_isNotSingle';
        }

        if ($bGiftService) {
            $remSubpartArray[] = 'display_variant5_NoGiftService';
            $remMarkerArray[] = 'display_variant5_giftService';
        } else {
            $remSubpartArray[] = 'display_variant5_giftService';
            $remMarkerArray[] = 'display_variant5_NoGiftService';
        }

        foreach ($remSubpartArray as $k => $subpart) {
            $subpartArray['###' . $subpart . '###'] = '';
        }

        foreach ($remMarkerArray as $k => $marker) {
            $markerArray['<!-- ###' . $marker . '### -->'] = '';
            $wrappedSubpartArray['###' . $marker . '###'] = '';
        }
    }

    public function storeVariantConf($variantConf): void
    {
        $this->storeInternalArray('variantConf', $variantConf);
    }

    public function storeSelectable($selectableArray): void
    {
        $this->storeInternalArray('selectable', $selectableArray);
    }

    public function storeFirstVariant($firstVariantArray): void
    {
        $this->storeInternalArray('firstVariant', $firstVariantArray);
    }

    public function storeInternalArray($type, array $internalArray): void
    {
        if (!empty($internalArray)) {
            $openMode = 'r+b';
            $filename = $this->getInternalFilename($type);
            $createdFile = false;
            $xmlString = '';
            if (!file_exists($filename)) {
                $internalFile = fopen($filename, 'wb');
                if (is_readable($filename)) {
                    $createdFile = true;
                } else {
                    debug('ERROR', 'error: tt_products internal file "' . $filename . '" cannot be created.');
                }
            } elseif (is_readable($filename)) {
                $internalFile = fopen($filename, 'r+b');
                $xmlString = fread($internalFile, filesize($filename));
            } else {
                debug('ERROR', 'error: tt_products internal file "' . $filename . '" is not readable.');
            }

            if ($createdFile) {
                $internalXml = GeneralUtility::array2xml($internalArray);
                fwrite($internalFile, $internalXml);
            } elseif (!empty($xmlString)) {
                $internalFromXml = GeneralUtility::xml2array($xmlString);
                $changes = array_diff_assoc($internalArray, $internalFromXml);
                if (!empty($changes)) {
                    fseek($internalFile, 0);
                    fwrite($internalFile, $internalArray);
                }
            }

            if (!empty($internalFile)) {
                fclose($internalFile);
            }
        }
    }

    public function readVariantConf()
    {
        return $this->readInternalArray('variantConf');
    }

    public function readSelectable()
    {
        return $this->readInternalArray('selectable');
    }

    public function readFirstVariant()
    {
        return $this->readInternalArray('firstVariant');
    }

    public function readInternalArray($type)
    {
        $openMode = 'r+b';
        $filename = $this->getInternalFilename($type);
        $xmlString = '';
        if (is_readable($filename)) {
            $internalFile = fopen($filename, 'r+b');
            $xmlString = fread($internalFile, filesize($filename));
        }
        $variantConfXml = GeneralUtility::xml2array($xmlString);
        if (!empty($internalFile)) {
            fclose($internalFile);
        }

        return $variantConfXml;
    }
}
