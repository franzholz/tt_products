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
 * JavaScript functions
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

class tx_ttproducts_javascript implements SingletonInterface
{
    public $ajax;
    public $bAjaxAdded;
    public $bCopyrightShown;
    public $copyright;
    public $fixInternetExplorer;
    private array $bIncludedArray = [];

    public function init($ajax)
    {
        if (
            isset($ajax) &&
            is_object($ajax)
        ) {
            $this->ajax = $ajax;
        }
        $this->bAjaxAdded = false;
        $this->bCopyrightShown = false;
        $this->copyright = '
/***************************************************************
*
*  javascript functions for the TYPO3 Shop System tt_products
*  relies on the javascript library "xajax"
*
*  Copyright notice
*
*  (c) 2006-2016 Franz Holzinger <franz@ttproducts.de>
*  All rights reserved
*
*  Released under GNU/GPL (http://typo3.com/License.1625.0.html)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of the script
***************************************************************/
';
        $this->fixInternetExplorer = '
if (!Array.prototype.indexOf) { // published by developer.mozilla.org
	Array.prototype.indexOf = function (searchElement /*, fromIndex */ ) {
		"use strict";
		if (this == null) {
			throw new TypeError();
		}
		var t = Object(this);
		var len = t.length >>> 0;
		if (len === 0) {
			return -1;
		}
		var n = 0;
		if (arguments.length > 1) {
			n = Number(arguments[1]);
			if (n != n) { // shortcut for verifying if it\'s NaN
				n = 0;
			} else if (n != 0 && n != Infinity && n != -Infinity) {
				n = (n > 0 || -1) * Math.floor(Math.abs(n));
			}
		}
		if (n >= len) {
			return -1;
		}
		var k = n >= 0 ? n : Math.max(len - Math.abs(n), 0);
		for (; k < len; k++) {
			if (k in t && t[k] === searchElement) {
				return k;
			}
		}
		return -1;
	}
}

	';
    }

    public static function convertHex($params)
    {
        $result = '\\x' . (ord($params[1]) < 16 ? '0' : '') . dechex(ord($params[1]));

        return $result;
    }

    /*
    * Escapes strings to be included in javascript
    *
    * @param	[type]		$s: ...
    * @return	[type]		...
    */
    public function jsspecialchars($s)
    {
        $result = preg_replace_callback(
            '/([\x09-\x2f\x3a-\x40\x5b-\x60\x7b-\x7e])/',
            'tx_ttproducts_javascript::convertHex',
            $s
        );

        return $result;
    }

    /**
     * Sets JavaScript code in the additionalJavaScript array.
     *
     * @param		string		  $fieldname is the field in the table you want to create a JavaScript for
     * @param		array		  category array
     * @param		int		  counter
     *
     * @see
     */
    public function set(
        $languageObj,
        $fieldname,
        $params = '',
        $currentRecord = '',
        $count = 0,
        $catid = 'cat',
        $parentFieldArray = [],
        $piVarArray = [],
        $fieldArray = [],
        $method = 'clickShow'
    ) {
        $bDirectHTML = false;
        $code = '';
        $bError = false;
        $prefixId = tx_ttproducts_model_control::getPrefixId();

        if (
            !$this->bCopyrightShown &&
            $fieldname != 'xajax'
        ) {
            $code = $this->copyright;
            $this->bCopyrightShown = true;
            $code .= $this->fixInternetExplorer;
        }

        if (
            !is_object($this->ajax) &&
            in_array($fieldname, ['fetchdata'])
        ) {
            $fieldname = 'error';
        }

        $JSfieldname = $fieldname;
        switch ($fieldname) {
            case 'email' :
                $message = $languageObj->getLabel('invalid_email');
                $emailArr = explode('|', $message);

                $code .= '
	var test = function(eing) {
		var reg = /@/;
		var rc = true;
		if (!reg.exec(eing)) {
	 		rc = false;
	 	}
	 	return rc;
	}

	var checkEmail = function(element) {
		if (test(element.value)){
			return (true);
		}
		alert("' . $emailArr[0] . '\'"+element.value+"\'' . $emailArr[1] . '");
		return (false);
	}

	var checkParams = function(formObj) {
		var rc = true;
		for (var i = 0; i < formObj.length; i++) {
			if (formObj[i].type == "text") {
				var email = /email/i;
				if (email.exec(formObj[i].name)) {
					rc = checkEmail (formObj[i]);
				}
			}
			if (!rc) {
				break;
			}
		}
		return rc;
	}
	';
                break;
            case 'selectcat':
                if (
                    !ExtensionManagementUtility::isLoaded(TAXAJAX_EXT)
                ) {
                    return false;
                }

                $name = 'tt_products[' . $fieldname . ']';
                if (is_array($params)) {
                    $funcs = count($params);
                    $cnf = GeneralUtility::makeInstance('tx_ttproducts_config');

                    $ajaxConf = $cnf->getAJAXConf();
                    if (is_array($ajaxConf)) {
                        // TODO: make it possible that AJAX gets all the necessary configuration
                        $code .= '
		var conf = new Array();';
                        $code .= '
		';
                        foreach ($ajaxConf as $k => $actConf) {
                            $pVar = GeneralUtility::_GP($k);
                            if (isset($pVar) && is_array($actConf[$pVar . '.'])) {
                                foreach ($actConf[$pVar . '.'] as $k2 => $v2) {
                                    $code .= 'conf[' . $k2 . '] = ' . $v2 . '; ';
                                }
                            }
                        }
                        $code .= '
		';
                    }
                    $code .= 'var c = new Array(); // categories
		var boxCount = ' . $count . '; // number of select boxes
		var pi = new Array(); // names of pi vars;
		var selectBoxNames = new Array(); // names of select boxes;
		var inAction = false; // is the script still running?
		var maxFunc = ' . $funcs . ';

		selectBoxNames[0] = "' . $name . '";
		';
                    foreach ($piVarArray as $fnr => $pivar) {
                        $code .= 'pi[' . $fnr . '] = "' . $pivar . '";';
                    }
                    $code .= '
		';
                    foreach ($params as $fnr => $catArray) {
                        if (!is_array($catArray)) {
                            continue;
                        }
                        $code .= 'c[' . $fnr . '] = new Array(' . count($catArray) . ');';
                        foreach ($catArray as $k => $row) {
                            $code .= 'c[' . $fnr . '][' . $k . '] = new Array(3);';
                            $code .= 'c[' . $fnr . '][' . $k . '][0] = "' . $this->jsspecialchars($row['title']) . '"; ';
                            $parentField = $parentFieldArray[$fnr];
                            $code .= 'c[' . $fnr . '][' . $k . '][1] = "' . intval($row[$parentField]) . '"; ';
                            $child_category = $row['child_category'] ?? 0;
                            if (is_array($child_category)) {
                                $code .= 'c[' . $fnr . '][' . $k . '][2] = new Array(' . count($child_category) . ');';
                                $count = 0;
                                foreach ($child_category as $k1 => $childCat) {
                                    $newCode = 'c[' . $fnr . '][' . $k . '][2][' . $count . '] = "' . $childCat . '"; ';
                                    $code .= $newCode;
                                    $count++;
                                }
                            } else {
                                $code .= 'c[' . $fnr . '][' . $k . '][2] = "0"; ';
                            }
                            $code .= '
		';
                        }
                    }
                }
                $code .=
        '
		' .
    'var fillSelect = function(select, id, contentId, showSubCategories) {
		var sb;
		var sbt;
		var index;
		var subcategories;
		var bShowArticle = 0;
		var len;
		var idel;
		var category;
		var func = 0;
		var selectBoxes;
		var bRootFunctions = 0;
		var categoryArray = new Array();

		if (inAction == true) {
			return false;
		}
		inAction = true;

		selectBoxes = document.getElementsByName(selectBoxNames[0]);
		for(var i = 0; i < selectBoxes.length && i < id - 1; i++)
		{
			var obj = selectBoxes.item(i);
			category = obj.value;
			if (
				category != "" &&
				category != "0" &&
				!isNaN(category) &&
				categoryArray.indexOf(category) == -1
			) {
				categoryArray.push(category);
			} else {
				break;
			}
		}

		category = categoryArray.pop();

		if (id > 0) {
			bRootFunctions = (maxFunc > 1) && (id == 2);

			idel = "' . $catid . '-" + contentId + "-" + 1;
			sb = document.getElementById(idel);
			if (sb == false) {
				return false;
			}

			if (maxFunc > 1) {
				func = sb.selectedIndex - 1;
				if (func == false || func < 0 || func > maxFunc) {
					func = 0;
					bRootFunctions = false;
				}
			}

			for (var l = boxCount; l >= id; l--) {
				idel = "' . $catid . '-" + contentId + "-" + l;
				sbt = document.getElementById(idel);
				if (sbt == false) {
					break;
				}
				sbt.options.length = 0;
				sbt.selectedIndex = 0;
			}

			if (sb.selectedIndex == 0) {
				// nothing
			} else if (bRootFunctions) {
				if (categoryArray.length > 1) {
					category = categoryArray.join(",");
				}

				// lens = c[func].length;
				subcategories = new Array();
				var count = 0;
				for (k in c[func]) {
					if (c[func][k][1] == 0) {
						subcategories[count] = k;
						count++;
					}
				}
			} else if (category > 0) {
				subcategories = c[func][category][2];
			}

			if (
				(typeof(subcategories) == "object") &&
				(showSubCategories == 1)
			) {
				var newOption = new Option("", 0);
				sbt.options[0] = newOption; // sbt.options.add(newOption);
				len = subcategories.length;

				for (k = 0; k < len; k++) {
					var cat = subcategories[k];
					var text = c[func][cat][0];
					newOption = new Option(text, cat);
					sbt.options[k+1] = newOption; // sbt.options.add(newOption);
				}
				sbt.name = selectBoxNames[func];
			} else {
				bShowArticle = 1;
			}
		} else {
			bShowArticle = 1;
		}

		if (bShowArticle) {
			var data = new Array();

		data["' . $prefixId . '"] = new Array();
		data["' . $prefixId . '"]["' . $catid . '"] = category;
		';

                $contentPiVar = tx_ttproducts_model_control::getPiVar('tt_content');

                if ($currentRecord != '') {
                    $code .= '
		data["' . $prefixId . '"]["' . $contentPiVar . '"] = contentId;
			';
                }

                $code .= '
				';

                if ($method == 'clickShow') {
                    $code .= TT_PRODUCTS_EXT . '_showArticle(data);';
                }
                $code .= '
		} else {
			/* nothing */
		}
		';
                $code .= '
		inAction = false;
		return true;
	}
		';
                $code .= '
	var doFetchData = function(contentId) {
		var data = new Array();
		var func;

		idel = "' . $catid . '-" + contentId + "-" + 1;
		sb = document.getElementById(idel);
		func = sb.selectedIndex - 1;
		for (var k = 2; k <= boxCount; k++) {
			idel = "' . $catid . '-" + contentId + "-" + k;
			sb = document.getElementById(idel);
			if (sb == false) {
				break;
			}
			index = sb.selectedIndex;
			if (index > 0) {
				value = sb.options[index].value;
				if (value) {
					data["' . $prefixId . '"] = new Array();
					data["' . $prefixId . '"][pi[func]] = value;
				}
			}
		}
		var sub = document.getElementsByName("' . $prefixId . '[submit]")[0];
		for (k in sub.form.elements) {
			var el = sub.form.elements[k];
			var elname;
			if (el) {
				elname = String(el.name);
			}
			if (elname && elname.indexOf("function") == -1 && elname.indexOf("' . $prefixId . '") == 0) {
				var start = elname.indexOf("[");
				var end = elname.indexOf("]");
				var element = elname.substring(start+1,end);
				data["' . $prefixId . '"][element] = el.value;
			}
		}

			';
                if (
                    $method == 'submitShow'
                ) {
                    $code .= $prefixId . '_showList(data);';
                }
                $code .= '
		return true;
	}
	';
                break;

            case 'fetchdata':
                if (
                    !ExtensionManagementUtility::isLoaded(TAXAJAX_EXT) ||
                    !is_array($params)
                ) {
                    return false;
                }
                $code .= 'var vBoxCount = new Array(' . count($params) . '); // number of select boxes' . chr(13);
                $code .= 'var v = new Array(); // variants' . chr(13) . chr(13);
                foreach ($params as $tablename => $selectableVariantFieldArray) {
                    if (is_array($selectableVariantFieldArray)) {
                        $code .= 'vBoxCount["' . $tablename . '"] = ' . count($selectableVariantFieldArray) . ';' . chr(13);
                        $code .= 'v["' . $tablename . '"] = new Array(' . count($selectableVariantFieldArray) . ');' . chr(13);
                        $k = 0;
                        foreach ($selectableVariantFieldArray as $variant => $field) {
                            $code .= 'v["' . $tablename . '"][' . $k . '] = "' . str_replace('_', '-', $field) . '";' . chr(13);
                            $k++;
                        }
                    }
                }

                $code .= '

	var doFetchRow = function(table, view, uid) {
		var data = new Array();
		var sb;
		var temp = table.split("_");
		var feTable = temp.join("-");';

                $code .= '
		data["view"] = view;
		data[table] = new Array();
		data[table]["uid"] = uid;
		for (var k = 0; k < vBoxCount[table]; k++) {
			var field = v[table][k];
			var id = feTable+"-"+view+"-"+uid+"-"+field;
			htmltag = document.getElementById(id);
			if (typeof htmltag == "object") {
				if (field.indexOf("edit-") == 0) {
					// edit variant
					try {
						var value = htmltag.value;
						data[table][field] = value;
					}
					catch (e) {
						// nothing
					}
				} else {
						// select box
					try {
						var index = htmltag.selectedIndex;
						if (typeof index != "undefined") {
							var value = htmltag.options[index].value;
							data[table][field] = value;
						}
					}
					catch (e) {
						// nothing
					}
				}
			}
		}
	';

                $code .= '	' . TT_PRODUCTS_EXT . '_fetchRow(data);';

                $code .= '
		return true;
	}';
                break;

            case 'direct':
                if (is_array($params)) {
                    reset($params);
                    $code .= current($params);
                    $JSfieldname = $fieldname . '-' . key($params);
                }
                break;

            case 'xajax':
                // XAJAX part
                if (
                    !$this->bAjaxAdded &&
                    is_object($this->ajax) &&
                    is_object($this->ajax->taxajax)
                ) {
                    $path =
                        PathUtility::stripPathSitePrefix(
                            ExtensionManagementUtility::extPath(TAXAJAX_EXT)
                        );
                    $code = $this->ajax->taxajax->getJavascript($path);
                    $this->bXajaxAdded = true;
                }
                $JSfieldname = 'tx_ttproducts-xajax';
                $bDirectHTML = true;
                break;

            case 'colorbox':
                $JSfieldname = 'tx_ttproducts-colorbox';
                $colorboxFile = PathUtility::getAbsoluteWebPath(PATH_BE_TTPRODUCTS . 'Resources/Public/JavaScript/tt_products_colorbox.js');

                FrontendUtility::addJavascriptFile(
                    $colorboxFile,
                    $JSfieldname
                );
                break;

            default:
                $bError = true;
                break;
        } // switch

        if (!$bError) {
            if (
                $code != '' &&
                $JSfieldname != ''
            ) {
                if ($bDirectHTML) {
                    $pageRenderer = $this->getPageRenderer();
                    $pageRenderer->addHeaderData($code);
                } else {
                    $assetCollector = GeneralUtility::makeInstance(AssetCollector::class);
                    $assetCollector->addInlineJavaScript($JSfieldname, $code, [], ['priority' => true]);
                }
            }
        }
    } // setJS

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(\TYPO3\CMS\Core\Page\PageRenderer::class);
    }

    public function setIncluded($filename)
    {
        $this->bIncludedArray[$filename] = true;
    }

    public function getIncluded($filename)
    {
        return $this->bIncludedArray[$filename];
    }
}
