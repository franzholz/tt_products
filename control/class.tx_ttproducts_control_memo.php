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
 * functions for the control of the single view
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */

use JambageCom\Div2007\Utility\FrontendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class tx_ttproducts_control_memo
{
    protected static $memoTableFieldArray = [
        'tt_products' => 'memoItems',
        'tx_dam' => 'memodam',
    ];
    protected static $memoItemArray = [];
    protected static $controlVars = [
        'addmemo',
        'delmemo',
        'upmemo',
        'downmemo',
    ];

    public static function getControlVars()
    {
        return self::$controlVars;
    }

    public static function getMemoTableFieldArray()
    {
        return self::$memoTableFieldArray;
    }

    public static function bIsAllowed($type, $conf)
    {
        $result = false;

        if (
            isset($conf['memo.']) &&
            isset($conf['memo.']['allow'])
        ) {
            if (GeneralUtility::inList($conf['memo.']['allow'], $type)) {
                $result = true;
            }
        }

        return $result;
    }

    public static function bUseFeuser($conf)
    {
        $result = false;
        $fe_user_uid = FrontendUtility::getFrontEndUser('uid');

        if ($fe_user_uid) {
            $result = self::bIsAllowed('fe_users', $conf);
        }

        return $result;
    }

    public static function bUseSession($conf)
    {
        $result = self::bIsAllowed('session', $conf);

        return $result;
    }

    public static function process($funcTablename, $piVars, $conf): void
    {
        $bMemoChanged = false;
        self::loadMemo($funcTablename, $conf);

        $memoItems = self::getMemoItems($funcTablename);
        if (!is_array($memoItems)) {
            $memoItems = [];
        }
        $controlVars = self::getControlVars();
        $memoArray = [];
        foreach ($controlVars as $controlVar) {
            if (!empty($piVars[$controlVar])) {
                $memoArray[$controlVar] = explode(',', $piVars[$controlVar]);
            }
        }

        if (isset($piVars['memo']) && is_array($piVars['memo'])) {
            if (!isset($memoArray['addmemo'])) {
                $memoArray['addmemo'] = [];
            }
            if (!isset($memoArray['delmemo'])) {
                $memoArray['delmemo'] = [];
            }

            foreach ($piVars['memo'] as $k => $v) {
                if (MathUtility::canBeInterpretedAsInteger($k) && $k != '' && $v) {
                    $memoArray['addmemo'][] = intval($k);
                } elseif ($k == 'uids') {
                    $uidArray = explode(',', $v);
                    foreach ($uidArray as $uid) {
                        if (MathUtility::canBeInterpretedAsInteger($uid) && $uid != '' && in_array($uid, $memoItems)) {
                            $memoArray['delmemo'][] = $uid;
                        }
                    }
                }
            }
        }

        if (isset($memoArray['addmemo']) && is_array($memoArray['addmemo'])) {
            foreach ($memoArray['addmemo'] as $addMemoSingle) {
                if (!in_array($addMemoSingle, $memoItems)) {
                    $uid = intval($addMemoSingle);
                    if ($uid) {
                        $memoItems[] = $uid;
                        $bMemoChanged = true;
                    }
                }
            }
        }

        if (isset($memoArray['delmemo']) && is_array($memoArray['delmemo'])) {
            foreach ($memoArray['delmemo'] as $delMemoSingle) {
                $val = intval($delMemoSingle);
                if (in_array($val, $memoItems)) {
                    unset($memoItems[array_search($val, $memoItems)]);
                    $bMemoChanged = true;
                }
            }
        }

        if (isset($memoArray['upmemo']) && is_array($memoArray['upmemo'])) {
            foreach ($memoArray['upmemo'] as $memoSingle) {
                $val = intval($memoSingle);
                $key = array_search($val, $memoItems);
                if ($key !== false && $key > 0) {
                    $formerValue = $memoItems[$key - 1];
                    $memoItems[$key - 1] = $val;
                    $memoItems[$key] = $formerValue;
                    $bMemoChanged = true;
                }
            }
        }

        if (isset($memoArray['downmemo']) && is_array($memoArray['downmemo'])) {
            $maxKey = count($memoItems) - 1;
            foreach ($memoArray['downmemo'] as $memoSingle) {
                $val = intval($memoSingle);
                $key = array_search($val, $memoItems);
                if ($key !== false && $key < $maxKey) {
                    $formerValue = $memoItems[$key + 1];
                    $memoItems[$key + 1] = $val;
                    $memoItems[$key] = $formerValue;
                    $bMemoChanged = true;
                }
            }
        }

        if ($bMemoChanged) {
            self::saveMemo($funcTablename, $memoItems, $conf);
            self::setMemoItems($funcTablename, $memoItems);
        }
    }

    public static function getMemoField($funcTablename, $bFeuser)
    {
        if (isset(self::$memoTableFieldArray[$funcTablename])) {
            $result = ($bFeuser ? 'tt_products_' : '') . self::$memoTableFieldArray[$funcTablename];
        } else {
            $result = false;
        }

        return $result;
    }

    public static function getMemoItems($funcTablename)
    {
        $result = self::$memoItemArray[$funcTablename];

        return $result;
    }

    public static function setMemoItems($funcTablename, $v): void
    {
        if (!is_array($v)) {
            if ($v == '') {
                $v = [];
            } else {
                $v = explode(',', $v);
            }
        }
        self::$memoItemArray[$funcTablename] = $v;
    }

    public static function readSessionMemoItems($funcTablename)
    {
        $result = '';
        $session = tx_ttproducts_control_session::readSessionData();
        $tableArray = self::getMemoTableFieldArray();
        $field = $tableArray[$funcTablename];

        if (
            $field != '' &&
            is_array($session) &&
            isset($session[$field])
        ) {
            $result = $session[$field];
        }

        return $result;
    }

    public static function readFeUserMemoItems($funcTablename)
    {
        $result = '';
        $feuserField = self::getMemoField($funcTablename, true);

        if ($GLOBALS['TSFE']->fe_user->user[$feuserField]) {
            $result = explode(',', $GLOBALS['TSFE']->fe_user->user[$feuserField]);
        }

        return $result;
    }

    public static function loadMemo($funcTablename, $conf): void
    {
        $memoItems = '';
        // 		$bFeuser = self::bUseFeuser($conf);
        // 		$theField = self::getMemoField($funcTablename, $bFeuser);

        if (self::bUseFeuser($conf)) {
            $memoItems = self::readFeUserMemoItems($funcTablename);
        } else {
            $memoItems = self::readSessionMemoItems($funcTablename);
        }
        self::setMemoItems($funcTablename, $memoItems);
    }

    public static function saveMemo($funcTablename, $memoItems, $conf): void
    {
        $bFeuser = self::bUseFeuser($conf);
        $feuserField = self::getMemoField($funcTablename, $bFeuser);

        $fieldsArray = [];
        $fieldsArray[$feuserField] = implode(',', $memoItems);

        if ($bFeuser) {
            $fe_user_uid = FrontendUtility::getFrontEndUser('uid');
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery('fe_users', 'uid=' . $fe_user_uid, $fieldsArray);
        } else {
            tx_ttproducts_control_session::writeSessionData($fieldsArray);
        }
    }

    public static function copySession2Feuser($params, $pObj, $conf): void
    {
        $tableArray = self::getMemoTableFieldArray();
        foreach ($tableArray as $funcTablename => $type) {
            $memoItems = self::readSessionMemoItems($funcTablename);

            if (!empty($memoItems) && is_array($memoItems)) {
                $feuserMemoItems = self::readFeUserMemoItems($funcTablename);
                if (isset($feuserMemoItems) && is_array($feuserMemoItems)) {
                    $memoItems = array_merge($feuserMemoItems, $memoItems);
                }
                self::saveMemo($funcTablename, $memoItems, $conf);
            }
        }
    }

    /**
     * Adds link markers to a wrapped subpart array.
     */
    public static function getWrappedSubpartArray(
        &$wrappedSubpartArray,
        $pidMemo,
        $uid,
        $cObj,
        $urlObj,
        $excludeList = '',
        $addQueryString = [],
        $css_current = '',
        $useBackPid = true
    ): void {
        $cmdArray = ['add', 'del'];

        foreach ($cmdArray as $cmd) {
            $addQueryString[$cmd . 'memo'] = $uid;

            $pageLink = FrontendUtility::getTypoLink_URL(
                $cObj,
                $pidMemo,
                $urlObj->getLinkParams(
                    $excludeList,
                    $addQueryString,
                    true,
                    $useBackPid
                )
            );

            $wrappedSubpartArray['###LINK_MEMO_' . strtoupper($cmd) . '###'] = ['<a href="' . htmlspecialchars($pageLink) . '"' . $css_current . '>', '</a>'];
            unset($addQueryString[$cmd . 'memo']);
        }
    }
}
