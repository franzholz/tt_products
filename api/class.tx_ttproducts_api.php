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
 * API functions
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use JambageCom\Div2007\Utility\CompatibilityUtility;
use JambageCom\Div2007\Utility\MailUtility;
use JambageCom\Div2007\Utility\ObsoleteUtility;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

class tx_ttproducts_api
{
    public static function roundPrice($value, $format)
    {
        $result = $oldValue = $value;
        $priceRoundFormatArray = [];
        $dotPos = strpos($value, '.');
        $floatLen = strlen($value) - $dotPos - 1;

        if (strpos($format, '.') !== false) {
            $priceRoundFormatArray = GeneralUtility::trimExplode('.', $format);
        } else {
            $priceRoundFormatArray['0'] = $format;
        }

        if ($priceRoundFormatArray['0'] != '') {
            $integerPart = intval($priceRoundFormatArray['0']);
            $floatPart = $oldValue - intval($oldValue);
            $faktor = pow(10, strlen($integerPart));
            $result = (intval($oldValue / $faktor) * $faktor) + $integerPart + $floatPart;

            if ($result < $oldValue) {
                $result += $faktor;
            }

            $oldValue = $result;
        }

        if (isset($priceRoundFormatArray['1'])) {
            $formatText = $priceRoundFormatArray['1'];
            $digits = 0;
            while (substr($formatText, $digits, 1) == 'X') {
                $digits++;
            }
            $floatValue = substr($formatText, $digits);
            $faktor = pow(10, $digits);

            if ($floatValue == '') {
                $result = round($oldValue, $digits);
            } else {
                $allowedChars = '';
                $lowestValuePart = 0;
                $length = strlen($floatValue);

                if (
                    $length > 3 &&
                    strpos($floatValue, '[') === 0 &&
                    strpos($floatValue, ']') === ($length - 1)
                ) {
                    $allowedChars = substr($floatValue, 1, $length - 2);

                    if ($allowedChars != '') {
                        $digitValue = intval(round($value * $faktor * 10)) % 10;
                        $countAllowedChars = strlen($allowedChars);
                        $step = intval(10 / $countAllowedChars);
                        $allowedPos = 0;
                        $finalAddition = $digitValue;
                        $lowChar = '';
                        $lowValue = -20;
                        $highChar = '';
                        $highValue = 20;
                        $bKeepChar = false;

                        for ($allowedPos = 0; $allowedPos < $countAllowedChars; $allowedPos++) {
                            $currentChar = substr($allowedChars, $allowedPos, 1);
                            $currentValue = intval($currentChar);

                            if ($lowChar == '') {
                                $lowChar = $currentChar;
                                $lowValue = $currentValue;
                            }

                            if ($highChar == '') {
                                $highChar = $currentChar;
                                $highValue = $currentValue;
                            }

                            if (
                                $digitValue == $currentChar &&
                                $floatLen == ($length - 2)
                            ) { // '0' means '10'
                                $bKeepChar = true;
                                break;
                            } else {
                                $comparatorLow1 = $digitValue - $currentValue;
                                if ($comparatorLow1 < 0) {
                                    $comparatorLow1 += 10;
                                }

                                $comparatorLow2 = $digitValue - $lowValue;

                                if ($comparatorLow2 < 0) {
                                    $comparatorLow2 += 10;
                                }

                                if (
                                    $comparatorLow1 < $comparatorLow2
                                ) {
                                    $lowChar = $currentChar;
                                    $lowValue = $currentValue;
                                }

                                $comparatorHigh1 = $currentValue - $digitValue;

                                if ($comparatorHigh1 < 0) {
                                    $comparatorHigh1 += 10;
                                }

                                $comparatorHigh2 = $highValue - $digitValue;

                                if ($comparatorHigh2 < 0) {
                                    $comparatorHigh2 += 10;
                                }

                                if ($comparatorHigh1 < $comparatorHigh2) {
                                    $highChar = $currentChar;
                                    $highValue = $currentValue;
                                }
                            }

                            if (
                                !$bKeepChar &&
                                $lowValue != $highValue
                            ) {
                                $comparator2 = $highValue - $digitValue;
                                $highAddition = 0;
                                if ($comparator2 < 0) {
                                    $comparator2 += 10;
                                    $highAddition = 10;
                                }

                                if ($digitValue - $lowValue < $comparator2) {
                                    $finalAddition = $lowValue;
                                } else {
                                    $finalAddition = $highValue + $highAddition;
                                }
                            }
                        }
                        $lowestValuePart = (intval($finalAddition) / ($faktor * 10));
                    }
                } elseif (
                    MathUtility::canBeInterpretedAsInteger($floatValue)
                ) {
                    $floatPart = $floatValue * $faktor * 10;
                    $lowestValuePart = (intval($floatPart) / ($faktor * 10));
                }

                if (!$bKeepChar) {
                    $result = intval($oldValue * $faktor) / $faktor + $lowestValuePart;
                }
            }
        }

        return $result;
    }

    // get the templateCode for an error message
    public static function getErrorOut(
        $theCode,
        $templateCode,
        $subpartMarker,
        $alternativeSubpartMarker,
        &$errorCode
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $result = false;

        if (
            $subpartMarker != '' &&
            strpos($templateCode, $subpartMarker) !== false ||
            $alternativeSubpartMarker != '' &&
            strpos($templateCode, $alternativeSubpartMarker) !== false
        ) {
            $errorTemplateCode = $templateCode;
        } else {
            $templateFile = '';
            $templateObj = GeneralUtility::makeInstance('tx_ttproducts_template');
            $errorTemplateCode = $templateObj->get(
                $theCode,
                $templateFile,
                $errorCode
            );
            if (
                $errorTemplateCode == '' ||
                strpos($errorTemplateCode, $subpartMarker) === false &&
                strpos($errorTemplateCode, $alternativeSubpartMarker) === false
            ) {
                $errorTemplateCode = $templateObj->get(
                    'ERROR',
                    $templateFile,
                    $errorCode
                );
            }
        }

        if ($errorTemplateCode != '') {
            $errorOut = false;

            if ($subpartMarker != '' || $alternativeSubpartMarker != '') {
                if (
                    $subpartMarker != '' &&
                    strpos($errorTemplateCode, $subpartMarker) !== false
                ) {
                    $errorOut =
                        $templateService->getSubpart(
                            $errorTemplateCode,
                            $subpartMarker
                        );
                } elseif (
                    $alternativeSubpartMarker != '' &&
                    strpos($errorTemplateCode, $alternativeSubpartMarker) !== false
                ) {
                    $errorOut =
                        $templateService->getSubpart(
                            $errorTemplateCode,
                            $alternativeSubpartMarker
                        );
                }
            } else {
                $errorOut = $errorTemplateCode;
            }
            $result = $errorOut;

            if ($result == '') {
                $errorCode[0] = 'no_subtemplate';
                $errorCode[1] = $subpartMarker;
                $errorCode[2] = $templateFile;
            }
        }

        return $result;
    }

    public static function createFeuser(
        $bAllowCreation,
        $templateCode,
        $conf,
        $infoObj,
        $basketView,
        $calculatedArray,
        $fromArray
    ) {
        $result = false;
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $infoArray = $infoObj->infoArray;
        $apostrophe = $conf['orderEmail_apostrophe'];

        $pid = ($conf['PIDuserFolder'] ?: ($conf['PIDbasket'] ?: $GLOBALS['TSFE']->id));
        $pid = intval($pid);
        $username = strtolower(trim($infoArray['billing']['email']));
        $rowArray =
            $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                'uid,username',
                'fe_users',
                'username=' .
                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                        $username,
                        'fe_users'
                    ) . ' AND pid=' . $pid . ' AND deleted=0'
            );
        $num_rows = count($rowArray);

        if ($num_rows) {
            if (isset($rowArray['0']) && is_array($rowArray['0']) && isset($rowArray['0']['uid'])) {
                $result = intval($rowArray['0']['uid']);
            }
        } elseif ($bAllowCreation) {
            $password = substr(md5(random_int(0, mt_getrandmax())), 0, 12);
            $infoObj->password = $password;
            try {
                $hashInstance = GeneralUtility::makeInstance(PasswordHashFactory::class)->getDefaultHashInstance('FE');
                $password = $hashInstance->getHashedPassword($password);
            } catch (InvalidPasswordHashException $e) {
                debug($tmp, 'no FE user could be generated!'); // keep this
            }
            $tableFieldArray = $tablesObj->get('fe_users')->getTableObj()->tableFieldArray;
            $insertFields = [	// TODO: check with TCA
                'pid' => intval($pid),
                'tstamp' => time(),
                'crdate' => time(),
                'username' => $username,
                'password' => $password,
                'usergroup' => $conf['memberOfGroup'],
            ];

            foreach ($tableFieldArray as $fieldname => $value) {
                $fieldvalue = $infoArray['billing'][$fieldname] ?? null;
                if (isset($fieldvalue)) {
                    $insertFields[$fieldname] = $fieldvalue;
                }
            }

            if (
                ExtensionManagementUtility::isLoaded('agency') ||
                ExtensionManagementUtility::isLoaded('femanager') ||
                ExtensionManagementUtility::isLoaded('sr_feuser_register')
            ) {
                if ($conf['useStaticInfoCountry'] && isset($infoArray['billing']['country_code'])) {
                    $insertFields['static_info_country'] = $infoArray['billing']['country_code'];
                } else {
                    $insertFields['static_info_country'] = '';
                }
            }

            if (!empty($infoArray['billing']['date_of_birth'])) {
                $date = str_replace('-', '/', $infoArray['billing']['date_of_birth']);
                $insertFields['date_of_birth'] = strtotime($date);
            }

            $res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('fe_users', $insertFields);
            // send new user mail
            if (!empty($infoArray['billing']['email'])) {
                $empty = '';
                $emailContent = trim(
                    $basketView->getView(
                        $errorCode,
                        $templateCode,
                        'EMAIL',
                        $infoObj,
                        false,
                        false,
                        $calculatedArray,
                        false,
                        'EMAIL_NEWUSER_TEMPLATE',
                        [],
                        '',
                        [],
                        $notOverwritePriceIfSet = true,
                        [],
                        [],
                        [],
                        []
                    )
                );

                if ($emailContent != '') {
                    $parts = explode(chr(10), $emailContent, 2);
                    $subject = trim($parts[0]);
                    $plain_message = trim($parts[1]);
                    $tmp = '';

                    MailUtility::send(
                        $infoArray['billing']['email'],
                        $apostrophe . $subject . $apostrophe,
                        $plain_message,
                        $tmp,
                        $fromArray['shop']['email'],
                        $fromArray['shop']['name'],
                        '',
                        '',
                        '',
                        '',
                        '',
                        TT_PRODUCTS_EXT,
                        'sendMail'
                    );
                }
            }

            $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
                'uid',
                'fe_users',
                'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'fe_users') .
                    ' AND pid=' . $pid . ' AND deleted=0'
            );

            while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
                $result = intval($row['uid']);
                break;
            }
            $GLOBALS['TYPO3_DB']->sql_free_result($res);
        }

        return $result;
    }

    public static function splitSubjectAndText(
        $templateCode,
        $defaultSubject,
        $markerArray,
        &$subject,
        &$text
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $parts = preg_split('/[\n\r]+/', $templateCode, 2);	// First line is subject
        $subject = trim($parts[0]);
        $text = trim($parts[1]);

        if (empty($text)) {	// the user did not use the subject field
            $text = $subject;
        }
        $text = $templateService->substituteMarkerArrayCached($text, $markerArray);
        if (empty($subject)) {
            $subject = $defaultSubject;
        }
    }

    public static function generateBillNo(
        $orderUid,
        $orderPrefix
    ) {
        $newNumber = 1;
        $rows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            'uid,bill_no',
            'sys_products_orders',
            'bill_no > \'\'',
            '',
            'uid DESC',
            '1'
        );

        if (
            is_array($rows) &&
            isset($rows['0']) &&
            !empty($rows['0']['bill_no'])
        ) {
            $billNo = $rows['0']['bill_no'];
            $found = preg_match_all('/([\d]+)/', $billNo, $match);

            if (
                $found &&
                isset($match) &&
                is_array($match) &&
                isset($match[0]) &&
                is_array($match[0])
            ) {
                $newNumber = $match[0][0] + 1;
            }
        }
        $newNumber = $orderPrefix . $newNumber;

        return $newNumber;
    }

    /**
     * Finalize an order.
     *
     * This finalizes an order. The basket info has already been saved before on the payment page, if
     * a payment gateway is called afterwards.
     * A finalized order is then marked 'not hidden' and with status=1
     * The basket is also emptied, but address info is preserved for any new orders.
     * $orderUid is the order-uid to finalize
     * $mainMarkerArray is optional and may be pre-prepared fields for substitutiong in the template.
     */
    public static function finalizeOrder(
        $pObj,
        $templateCode,
        $mainMarkerArray,
        $functablename,
        $orderUid,
        &$orderArray,
        $itemArray,
        $calculatedArray,
        $addressArray,
        $basketExtra,
        $basketRecs,
        $basketExtGift,
        $usedCreditpoints,
        $bDebug,
        &$errorMessage
    ) {
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $result = true;
        $cnfObj = GeneralUtility::makeInstance('tx_ttproducts_config'); // init ok
        // $markerObj  init ok
        $basketView = GeneralUtility::makeInstance('tx_ttproducts_basket_view');
        $infoViewObj = GeneralUtility::makeInstance('tx_ttproducts_info_view');
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables'); // init ok
        $billdeliveryObj = GeneralUtility::makeInstance('tx_ttproducts_billdelivery');
        $fileArray = []; // bill or delivery
        $voucherCount = 0;

        $activityFinalize = GeneralUtility::makeInstance('tx_ttproducts_activity_finalize');

        $emailObj = $tablesObj->get('tt_products_emails');
        $orderObj = $tablesObj->get('sys_products_orders');
        $cObj = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $conf = $cnfObj->getConf();
        $customerEmailHTML = '';

        $orderRow =
            $orderObj->get(
                $orderUid,
                0,
                false,
                '',
                '',
                '',
                '',
                'uid,hidden,feusers_uid,status,bill_no',
                false,
                '',
                false
            );

        if (
            !empty($orderRow) &&
            is_array($orderRow) &&
            $orderRow['hidden'] == '0' &&
            $orderRow['status'] != '0'
        ) {
            return false; // the order has already been processed before
        }
        $orderObj->updateRecord($orderUid, ['hidden' => 0]); // mark this order immediately as unhidden in order to let no instant message from the gateway execute the following PHP code twice

        $instockTableArray = '';
        $customerEmail = $infoViewObj->getCustomerEmail();
        $defaultFromArray = $infoViewObj->getFromArray($customerEmail);

        $emailControlArray =
            $activityFinalize->getEmailControlArray(
                $templateCode,
                $conf,
                $defaultFromArray
            );

        if (isset($emailControlArray['customer'])) {
            $markerArray['###CUSTOMER_RECIPIENTS_EMAIL###'] = implode(',', $emailControlArray['customer']['none']['recipient']);

            $customerEmailHTML =
                $basketView->getView(
                    $errorCode,
                    $templateCode,
                    'EMAIL',
                    $infoViewObj,
                    false,
                    false,
                    $calculatedArray,
                    true,
                    $emailControlArray['customer']['none']['htmltemplate'],
                    $markerArray,
                    '',
                    $itemArray,
                    $notOverwritePriceIfSet = true,
                    ['0' => $orderArray],
                    [],
                    $basketExtra,
                    $basketRecs
                );
        }

        if ($GLOBALS['TSFE']->absRefPrefix == '') {
            $absRefPrefix = GeneralUtility::getIndpEnv('TYPO3_SITE_URL');
            $markerArray['"index.php'] = '"' . $absRefPrefix . 'index.php';
        }

        $apostrophe = $conf['orderEmail_apostrophe'];
        $bdArray = $billdeliveryObj->getTypeArray();

        foreach ($bdArray as $type) {
            if (
                isset($conf[$type . '.']) &&
                is_array($conf[$type . '.']) &&
                $conf[$type . '.']['generation'] == 'auto'
            ) {
                if (
                    $type == 'bill' &&
                    !$orderRow['bill_no']
                ) {
                    $newBillNumber = self::generateBillNo(
                        $orderUid,
                        substr($conf['orderBillNumberPrefix'] ?? '', 0, 30)
                    );
                    $orderArray['bill_no'] = $newBillNumber;
                }

                $absFilename =
                    $billdeliveryObj->generateBill(
                        $cObj,
                        $templateCode,
                        $mainMarkerArray,
                        $itemArray,
                        $calculatedArray,
                        $orderArray,
                        $basketExtra,
                        $basketRecs,
                        $type,
                        $conf[$type . '.']
                    );

                if ($absFilename) {
                    $fileArray[$type] = $absFilename;
                }
            }
        }

        if (
            isset($conf['gift.']) &&
            isset($conf['gift.']['type']) &&
            $conf['gift.']['type'] == 'voucher' &&
            isset($conf['whereGift']) &&
            $conf['whereGift'] != ''
        ) {
            $voucherCount = 0;
            $codeArray = [];
            tx_ttproducts_voucher::generate($voucherCount, $codeArray, $orderUid, $itemArray, $conf['whereGift']);
        }

        $orderObj->putData(
            $orderUid,
            $orderArray,
            $itemArray,
            $customerEmailHTML,
            1,
            $basketExtra,
            $calculatedArray,
            '',
            [], // TODO: $giftServiceArticleArray,
            '', // TODO: $vouchercode
            $usedCreditpoints,
            $voucherCount,
            !$bDebug // if true, then the order record must already be existing
        );

        $creditpointsObj = GeneralUtility::makeInstance('tx_ttproducts_field_creditpoints');
        $creditpointsObj->pay();

        // any gift orders in the extended basket?
        if ($basketExtGift) {
            $pid = intval($conf['PIDGiftsTable']);

            if (!$pid) {
                $pid = intval($GLOBALS['TSFE']->id);
            }

            $rc = tx_ttproducts_gifts_div::saveOrderRecord(
                $orderUid,
                $pid,
                $basketExtGift
            );
        }
        $theCode = 'FINALIZE';
        $orderObj->createMM($orderUid, $itemArray, $theCode);
        $addcsv = '';

        // Generate CSV for each order
        if ($conf['generateCSV']) {
            // get bank account info
            $account = $tablesObj->get('sys_products_accounts');
            $accountUid = $account->getUid();

            $csv = GeneralUtility::makeInstance('tx_ttproducts_csv');

            $csvfilepath = Environment::getPublicPath() . '/' . $conf['CSVdestination'];

            $csv->create(
                $functablename,
                $conf,
                $itemArray,
                $calculatedArray,
                $accountUid,
                $infoViewObj,
                $orderUid,
                $basketExtra,
                $csvfilepath,
                $errorMessage
            );
            if (!$conf['CSVnotInEmail']) {
                $addcsv = $csvfilepath;
            }
        }

        // Generate XML for each order
        if ($conf['generateXML']) {
            $orderXML =
                trim(
                    $basketView->getView(
                        $errorCode,
                        $templateCode,
                        'EMAIL',
                        $infoViewObj,
                        false,
                        true,
                        $calculatedArray,
                        true,
                        'BASKET_ORDERXML_TEMPLATE',
                        $mainMarkerArray,
                        '',
                        $itemArray,
                        $notOverwritePriceIfSet = true,
                        ['0' => $orderArray],
                        [],
                        $basketExtra,
                        $basketRecs
                    )
                );

            if ($orderXML) {
                $csvfilepath = Environment::getPublicPath() . '/' . $conf['XMLdestination'];

                if (substr($xmlFilepath, strlen($xmlFilepath) - 1, 1) != '/') {
                    $xmlFilepath .= '/';
                }
                $xmlFilepath .= $orderObj->getNumber($orderUid) . '.xml';
                $xmlFile = fopen($xmlFilepath, 'w');
                fwrite($xmlFile, $orderXML);
                fclose($xmlFile);
            }
        }

        // #################################

        $markerArray['###MESSAGE_PAYMENT_SCRIPT###'] = '';
        $empty = '';

        if ($conf['orderEmail_toAddress']) {
            $infoViewObjArray = $addressArray;
            if (is_array($infoViewObjArray) && count($infoViewObjArray)) {
                foreach ($infoViewObjArray as $infoViewObjUid => $infoViewObjRow) {
                    if (
                        !isset($emailControlArray['shop']['none']['recipient']) ||
                        !in_array($infoViewObjRow['email'], $emailControlArray['shop']['none']['recipient'])
                    ) {
                        $emailControlArray['shop']['none']['recipient'][] = $infoViewObjRow['email'];
                    }
                }
            }
        }

        if (
            isset($conf['orderEmail.']) && is_array($conf['orderEmail.'])
        ) {
            foreach ($conf['orderEmail.'] as $k => $emailConfig) {
                $suffix = strtolower($emailConfig['suffix']);
                if (!isset($suffix)) {
                    $suffix = 'shop';
                }

                if (
                    !empty($emailConfig['to']) ||
                    !empty($emailConfig['to.']) ||
                    $suffix == 'shop' ||
                    $suffix == 'customer'
                ) {
                    if (!empty($emailConfig['shipping_point'])) {
                        $shippingPoint = strtolower($emailConfig['shipping_point']);
                    } else {
                        $shippingPoint = 'none';
                    }

                    if (!empty($emailConfig['to.'])) {
                        $toConfig = $emailConfig['to.'];
                        if (
                            CompatibilityUtility::isLoggedIn() &&
                            !empty($GLOBALS['TSFE']->fe_user->user) &&
                            !empty($GLOBALS['TSFE']->fe_user->user['username']) &&
                            $toConfig['table'] == 'fe_users' &&
                            !empty($toConfig['field']) &&
                            !empty($toConfig['foreign_table']) &&
                            !empty($toConfig['foreign_field']) &&
                            !empty($toConfig['foreign_email_field']) &&
                            !empty($GLOBALS['TSFE']->fe_user->user[$toConfig['field']])
                        ) {
                            $where_clause =
                                $toConfig['foreign_table'] . '.' .
                                    $toConfig['foreign_field'] . '=' .
                                    $GLOBALS['TYPO3_DB']->fullQuoteStr(
                                        $GLOBALS['TSFE']->fe_user->user[$toConfig['field']],
                                        $toConfig['foreign_table']
                                    );
                            $recordArray =
                                $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
                                    $toConfig['foreign_email_field'],
                                    $toConfig['foreign_table'],
                                    $where_clause
                                );

                            if (isset($recordArray) && is_array($recordArray)) {
                                foreach ($recordArray as $record) {
                                    if (!empty($record[$toConfig['foreign_email_field']])) {
                                        $emailControlArray[$suffix][$shippingPoint]['recipient'][] = $record[$toConfig['foreign_email_field']];
                                    }
                                }
                            }
                        }
                    }

                    $emailControlArray[$suffix][$shippingPoint]['attachmentFile'] = [];

                    if (!empty($emailConfig['to'])) {
                        $emailArray = GeneralUtility::trimExplode(',', $emailConfig['to']);

                        foreach ($emailArray as $email) {
                            if (
                                !isset($emailControlArray[$suffix][$shippingPoint]['recipient']) ||
                                is_array($emailControlArray[$suffix][$shippingPoint]['recipient']) &&
                                !in_array($email, $emailControlArray[$suffix][$shippingPoint]['recipient'])
                            ) {
                                $emailControlArray[$suffix][$shippingPoint]['recipient'][] = $email;
                            }
                        }
                    }

                    if (!empty($emailConfig['attachment'])) {
                        $emailControlArray[$suffix][$shippingPoint]['attachment'] = GeneralUtility::trimExplode(',', $emailConfig['attachment']);

                        foreach ($emailControlArray[$suffix][$shippingPoint]['attachment'] as $attachmentType) {
                            if (isset($fileArray[$attachmentType])) {
                                $emailControlArray[$suffix][$shippingPoint]['attachmentFile'][] = $fileArray[$attachmentType];
                            }
                        }
                    }

                    if ($suffix != 'customer') {
                        $templateSubpart = 'EMAIL_PLAINTEXT_TEMPLATE_' . strtoupper($suffix);
                        $htmlTemplateSubpart = 'EMAIL_HTML_TEMPLATE_' . strtoupper($suffix);
                        if (
                            $suffix == 'shop'
                        ) {
                            if (strpos($templateCode, $templateSubpart) === false) {
                                $templateSubpart = $emailControlArray['customer']['none']['template'];
                            }
                            if (strpos($templateCode, $htmlTemplateSubpart) === false) {
                                $htmlTemplateSubpart = $emailControlArray['customer']['none']['htmltemplate'];
                            }
                        }

                        $emailControlArray[$suffix][$shippingPoint]['template'] = $templateSubpart;
                        $emailControlArray[$suffix][$shippingPoint]['htmltemplate'] = $htmlTemplateSubpart;

                        if ($addcsv != '') {
                            $emailControlArray[$suffix][$shippingPoint]['attachmentFile'][] = $addcsv;
                        }
                    }

                    if ($suffix == 'shop' && isset($conf['orderEmail_bcc'])) {
                        $emailControlArray[$suffix][$shippingPoint]['bcc'] = $conf['orderEmail_bcc'];
                    }

                    if (!$emailConfig['from'] || $emailConfig['from'] == 'shop') {
                        $emailControlArray[$suffix][$shippingPoint]['from'] = $defaultFromArray['shop'];
                    } elseif ($emailConfig['from'] == 'customer') {
                        $emailControlArray[$suffix][$shippingPoint]['from'] = $defaultFromArray['customer'];
                    } elseif (isset($emailConfig['from.'])) {
                        $emailControlArray[$suffix][$shippingPoint]['from'] = [
                            'email' => $emailConfig['from.']['email'],
                            'name' => $emailConfig['from.']['name'],
                        ];
                    }

                    if ($shippingPoint != 'none') {
                        $emailControlArray[$suffix][$shippingPoint]['recipient'] = array_unique(GeneralUtility::trimExplode(',', $emailConfig['to']));
                        if (!empty($emailConfig['subject'])) {
                            $emailControlArray[$suffix][$shippingPoint]['subject'] = $emailConfig['subject'];
                        }
                    }

                    if (isset($emailConfig['returnPath'])) {
                        $emailControlArray[$suffix][$shippingPoint]['returnPath'] = $emailConfig['returnPath'];
                    }
                }
            }
        }

        if (
            isset($conf['orderEmail_radio.']) &&
            is_array($conf['orderEmail_radio.']) &&
            isset($conf['orderEmail_radio.']['1.']) &&
            is_array($conf['orderEmail_radio.']) &&
            isset($conf['orderEmail_radio.']['1.'][$infoViewObj->infoArray['delivery']['radio1']])
        ) {
            $emailControlArray['radio1']['none']['recipient'][] = $conf['orderEmail_radio.']['1.'][$infoViewObj->infoArray['delivery']['radio1']];
        }

        if (isset($emailControlArray['radio1']['none']['recipient'])) {
            $emailControlArray['radio1']['none']['template'] = 'EMAIL_PLAINTEXT_TEMPLATE_RADIO1';
            $emailControlArray['radio1']['none']['htmltemplate'] = 'EMAIL_HTML_TEMPLATE_RADIO1';
        }

        if ($conf['orderEmail_order2']) {
            $emailControlArray['customer']['none']['recipient'] = array_merge($emailControlArray['customer']['none']['recipient'], $emailControlArray['shop']['none']['recipient']);
            $emailControlArray['customer']['none']['recipient'] = array_unique($emailControlArray['customer']['none']['recipient']);
        }
        $customerHTMLmailContent = '';
        $posEmailPlaintext = strpos($templateCode, $emailControlArray['customer']['none']['template']);

        if (
            $templateCode != '' &&
            ($posEmailPlaintext !== false || $conf['orderEmail_htmlmail'])
        ) {
            if ($conf['orderEmail_htmlmail']) {	// If htmlmail lib is included, then generate a nice HTML-email
                $HTMLmailShell = $templateService->getSubpart($templateCode, '###EMAIL_HTML_SHELL###');

                $customerHTMLmailContent =
                    $templateService->substituteMarker(
                        $HTMLmailShell,
                        '###HTML_BODY###',
                        $customerEmailHTML
                    );
                $customerHTMLmailContent =
                    $templateService->substituteMarkerArray(
                        $customerHTMLmailContent,
                        $markerArray
                    );

                // Remove image tags to the products:
                if (!empty($conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])) {
                    $htmlParser = GeneralUtility::makeInstance(HtmlParser::class);
                    $htmlMailParts = $htmlParser->splitTags('img', $customerHTMLmailContent);

                    foreach ($htmlMailParts as $kkk => $vvv) {
                        if ($kkk % 2) {
                            [$attrib] = $htmlParser->get_tag_attributes($vvv);
                            if (GeneralUtility::isFirstPartOfStr($attrib['src'], $conf['orderEmail_htmlmail.']['removeImagesWithPrefix'])) {
                                $htmlMailParts[$kkk] = '';
                            }
                        }
                    }
                    $customerHTMLmailContent = implode('', $htmlMailParts);
                }
            } else {	// ... else just plain text...
                // nothing to initialize
            }

            $agbAttachment = ($conf['AGBattachment'] ? GeneralUtility::getFileAbsFileName($conf['AGBattachment']) : '');

            if ($agbAttachment != '') {
                $emailControlArray['customer']['none']['attachmentFile'][] = $agbAttachment;

                if (isset($emailControlArray['radio1']['none']['recipient'])) {
                    $emailControlArray['radio1']['none']['attachmentFile'][] = $agbAttachment;
                }
            }

            $categoryInserted = [];
            $shippingPointInserted = [];

            // send distributor emails from email entered at the category level
            foreach ($itemArray as $sort => $actItemArray) {
                foreach ($actItemArray as $k1 => $actItem) {
                    $row = $actItem['rec'];
                    $extArray = $row['ext'];
                    $category = $row['category'];
                    $shipping_point = strtolower($row['shipping_point'] ?? '');
                    $suffix = 'shop';

                    if (!empty($categoryInserted[$category])) {
                        $suffix = $categoryInserted[$category];
                    } elseif ($category) {
                        $emailRow = null;
                        $categoryArray = $tablesObj->get('tt_products_cat')->get($category);
                        if (!empty($categoryArray['email_uid'])) {
                            $emailRow = $emailObj->getEmail($categoryArray['email_uid']);
                        }

                        if (isset($emailRow) && is_array($emailRow)) {
                            $email = $emailRow['email'];
                            $emailArray = [];
                            if (!empty($emailRow['name'])) {
                                $emailArray = [$email => $emailArray['name']];
                            } else {
                                $emailArray = [$email];
                            }

                            if (!empty($emailRow['suffix'])) {
                                $suffix = strtolower($emailRow['suffix']);
                            }

                            if (
                                !isset($emailControlArray[$suffix]['none']['recipient']) ||
                                is_array($emailControlArray[$suffix]['none']['recipient']) &&
                                !in_array($email, $emailControlArray[$suffix]['none']['recipient'])
                            ) {
                                $emailControlArray[$suffix]['none']['recipient'][] = $emailArray;
                            }
                        }
                        $categoryInserted[$category] = $suffix;
                    }

                    // TODO hier die FAL Bedingung eintragen. Und hier die Rechnung erzeugen.
                    if (
                        $suffix == 'shop' // not for category specific suffix
                    ) {
                        $addItem = false;
                        if (
                            isset($extArray['records']) &&
                            is_array($extArray['records'])
                        ) {
                            if (
                                isset($emailControlArray['download'])
                            ) {
                                $externalRowArray = $extArray['records'];
                                foreach ($externalRowArray as $tablename => $externalRow) {
                                    if ($tablename == 'sys_file_reference') {
                                        $suffix = 'download';
                                        $addItem = true;
                                        break;
                                    }
                                }
                            }
                        } else {
                            if (
                                isset($emailControlArray['product'])
                            ) {
                                $suffix = 'product';
                                $addItem = true;
                            }
                        }

                        if ($addItem) {
                            $emailControlArray[$suffix]['none']['itemArray'][$sort][] = $actItem;
                        }
                        $suffix = 'shop';
                    }

                    $emailControlArray[$suffix]['none']['itemArray'][$sort][] = $actItem;

                    if ($shipping_point) {
                        foreach ($emailControlArray as $suffix => $shippingControl) {
                            foreach ($shippingControl as $shippingKey => $shippingPointControl) {
                                // the shippingKey can be comma separated multiple value
                                $shippingKeyArray = GeneralUtility::trimExplode(',', $shippingKey);
                                foreach ($shippingKeyArray as $key) {
                                    if (
                                        $key == $shipping_point &&
                                        isset($shippingControl[$shippingKey]) &&
                                        is_array($shippingControl[$shippingKey]) &&
                                        (
                                            !isset($emailControlArray[$suffix][$shippingKey]) ||
                                            !isset($emailControlArray[$suffix][$shippingKey]['itemArray']) ||
                                            !isset($emailControlArray[$suffix][$shippingKey]['itemArray'][$sort]) ||
                                            !in_array($actItem, $emailControlArray[$suffix][$shippingKey]['itemArray'][$sort])
                                        )
                                    ) {
                                        $emailControlArray[$suffix][$shippingKey]['itemArray'][$sort][] =
                                            $actItem;
                                    }
                                }
                            }
                        }
                    }
                }
            }

            $reducedCalculatedArray = $calculatedArray; // TODO: allow calculation on reduced products

            foreach ($emailControlArray as $suffix => $shippingControlArray) {
                foreach ($shippingControlArray as $shippingPoint => $suffixControlArray) {
                    if (isset($suffixControlArray) && is_array($suffixControlArray)) {
                        if (
                            isset($suffixControlArray['itemArray']) &&
                            is_array($suffixControlArray['itemArray'])
                        ) {
                            $basketItemArray = $suffixControlArray['itemArray'];
                        } elseif ($suffix == 'customer' || $suffix == 'shop') {
                            $basketItemArray = $itemArray;
                        } else {
                            $basketItemArray = '';
                        }
                        $basketText = '';
                        $basketHtml = '';

                        if (isset($basketItemArray) && is_array($basketItemArray)) {
                            $basketText =
                                $basketView->getView(
                                    $errorCode,
                                    $templateCode,
                                    'EMAIL',
                                    $infoViewObj,
                                    false,
                                    true,
                                    $reducedCalculatedArray,
                                    false,
                                    $suffixControlArray['template'],
                                    $mainMarkerArray,
                                    '',
                                    $basketItemArray,
                                    $notOverwritePriceIfSet = true,
                                    ['0' => $orderArray],
                                    [],
                                    $basketExtra,
                                    $basketRecs
                                );
                            $basketText = trim($basketText);

                            if ($conf['orderEmail_htmlmail']) {
                                $basketHtml =
                                    $basketView->getView(
                                        $errorCode,
                                        $templateCode,
                                        'EMAIL',
                                        $infoViewObj,
                                        false,
                                        false,
                                        $reducedCalculatedArray,
                                        true,
                                        $suffixControlArray['htmltemplate'],
                                        $mainMarkerArray,
                                        '',
                                        $basketItemArray,
                                        $notOverwritePriceIfSet = true,
                                        ['0' => $orderArray],
                                        [],
                                        $basketExtra,
                                        $basketRecs
                                    );
                                $basketHtml = trim($basketHtml);
                            }
                        }

                        if (
                            $basketText != '' ||
                            $basketHtml != ''
                        ) {
                            if ($basketText != '') {
                                self::splitSubjectAndText(
                                    $basketText,
                                    $conf['orderEmail_subject'],
                                    $markerArray,
                                    $subject,
                                    $textContent
                                );
                            }

                            $subject = (!empty($suffixControlArray['subject']) ? $suffixControlArray['subject'] : $subject);
                            $HTMLmailContent = '';
                            if ($basketHtml != '') {
                                $HTMLmailContent =
                                    $templateService->substituteMarker(
                                        $HTMLmailShell,
                                        '###HTML_BODY###',
                                        $basketHtml
                                    );

                                $HTMLmailContent =
                                    $templateService->substituteMarkerArray(
                                        $HTMLmailContent,
                                        $markerArray
                                    );
                            }

                            $fromArray = [];

                            if (
                                isset($suffixControlArray['from']) &&
                                is_array($suffixControlArray['from'])
                            ) {
                                $fromArray = $suffixControlArray['from'];
                            } else {
                                $fromArray = $defaultFromArray['shop'];
                            }

                            if (
                                isset($suffixControlArray['recipient']) &&
                                is_array($suffixControlArray['recipient'])
                            ) {
                                foreach ($suffixControlArray['recipient'] as $recipientEmail) {
                                    MailUtility::send(
                                        $recipientEmail,
                                        $apostrophe . $subject . $apostrophe,
                                        $textContent,
                                        $HTMLmailContent,
                                        $fromArray['email'],
                                        $fromArray['name'],
                                        $suffixControlArray['attachmentFile'] ?? '',
                                        $suffixControlArray['cc'] ?? '',
                                        $suffixControlArray['bcc'] ?? '',
                                        $suffixControlArray['returnPath'] ?? '',
                                        $suffixControlArray['replyTo'] ?? '',
                                        TT_PRODUCTS_EXT,
                                        'sendMail'
                                    );
                                }
                            }
                        }
                    }
                }
            }

            $finalizeConf = $cnfObj->getFinalizeConf('productsFilter');

            if (is_array($finalizeConf) && count($finalizeConf)) {
                foreach ($finalizeConf as $k => $confpart) {
                    $reducedItemArray = [];

                    if (isset($confpart['pid']) && isset($confpart['email'])) {
                        foreach ($itemArray as $sort => $actItemArray) {
                            $reducedActItemArray = [];
                            foreach ($actItemArray as $k1 => $actItem) {
                                $row = $actItem['rec'];
                                if ($row['pid'] == $confpart['pid']) {
                                    $reducedActItemArray[] = $actItem;
                                }
                            }
                            if (count($reducedActItemArray)) {
                                $reducedItemArray[$sort] = $reducedActItemArray;
                            }
                        }

                        if (!empty($emailControlArray['shop']['none']['content'])) {
                            $emailKey = 'shop';
                        } else {
                            $emailKey = 'customer';
                        }

                        $reducedCalculatedArray = $calculatedArray;  // Todo: use a different calculation

                        $reducedBasketPlaintext =
                            trim(
                                $basketView->getView(
                                    $errorCode,
                                    $templateCode,
                                    'EMAIL',
                                    $infoViewObj,
                                    false,
                                    true,
                                    $reducedCalculatedArray,
                                    $conf['orderEmail_htmlmail'],
                                    $emailControlArray[$emailKey]['none']['template'],
                                    $mainMarkerArray,
                                    '',
                                    $reducedItemArray,
                                    $notOverwritePriceIfSet = true,
                                    ['0' => $orderArray],
                                    [],
                                    $basketExtra,
                                    $basketRecs
                                )
                            );
                        self::splitSubjectAndText(
                            $reducedBasketPlaintext,
                            $conf['orderEmail_subject'],
                            $markerArray,
                            $subject,
                            $textContent
                        );

                        if ($conf['orderEmail_htmlmail']) {
                            $reducedBasketHtml =
                                trim(
                                    $basketView->getView(
                                        $errorCode,
                                        $templateCode,
                                        'EMAIL',
                                        $infoViewObj,
                                        false,
                                        true,
                                        $reducedCalculatedArray,
                                        true,
                                        $emailControlArray[$emailKey]['none']['htmltemplate'],
                                        $mainMarkerArray,
                                        '',
                                        $reducedItemArray,
                                        $notOverwritePriceIfSet = true,
                                        ['0' => $orderArray],
                                        [],
                                        $basketExtra,
                                        $basketRecs
                                    )
                                );

                            $HTMLmailContent =
                                $templateService->substituteMarker(
                                    $HTMLmailShell,
                                    '###HTML_BODY###',
                                    $reducedBasketHtml
                                );

                            $HTMLmailContent =
                                $templateService->substituteMarkerArray(
                                    $HTMLmailContent,
                                    $markerArray
                                );
                        } else {
                            $HTMLmailContent = '';
                        }

                        MailUtility::send(
                            $confpart['email'],
                            $apostrophe . $subject . $apostrophe,
                            $textContent,
                            $HTMLmailContent,
                            $emailControlArray['customer']['none']['from']['email'],
                            $emailControlArray['customer']['none']['from']['name'],
                            '',
                            '',
                            '',
                            $emailControlArray['customer']['none']['returnPath'] ?? '',
                            $emailControlArray['customer']['none']['replyTo'] ?? '',
                            TT_PRODUCTS_EXT,
                            'sendMail'
                        );
                    }
                }
            }

            if (
                isset($emailControlArray['radio1']['plaintext']) &&
                isset($emailControlArray['radio1']['recipient'])
            ) {
                foreach ($emailControlArray['radio1']['recipient'] as $key => $recipient) {
                    MailUtility::send(
                        $recipient,
                        $apostrophe . $emailControlArray['radio1']['none']['subject'] . $apostrophe,
                        $emailControlArray['radio1']['none']['plaintext'],
                        $customerHTMLmailContent,
                        $emailControlArray['shop']['none']['from']['email'],
                        $emailControlArray['shop']['none']['from']['name'],
                        $emailControlArray['radio1']['none']['attachmentFile'],
                        '',
                        '',
                        $emailControlArray['shop']['none']['returnPath'] ?? '',
                        $emailControlArray['shop']['none']['replyTo'] ?? '',
                        TT_PRODUCTS_EXT,
                        'sendMail'
                    );
                }
            }
        } else {
            debug($templateCode, '$templateCode is empty'); // keep this
        }

        // 3 different hook methods - There must be one for your needs, too.

        // This cObject may be used to call a function which clears settings in an external order system.
        // The output is NOT included anywhere
        ObsoleteUtility::getExternalCObject($pObj, 'externalFinalizing');

        if (!empty($conf['externalOrderProcessFunc'])) {
            ObsoleteUtility::userProcess(
                $pObj,
                $conf,
                'externalOrderProcessFunc',
                $itemArray
            );
        }

        // Call all finalizeOrder hooks
        if (
            isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder']) &&
            is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'])
        ) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['finalizeOrder'] as $classRef) {
                $hookObj = GeneralUtility::makeInstance($classRef);
                if (method_exists($hookObj, 'finalizeOrder')) {
                    $hookObj->finalizeOrder(
                        $pObj,
                        $infoViewObj,
                        $templateCode,
                        $basketView,
                        $functablename,
                        $orderUid,
                        $orderConfirmationHTML,
                        $errorMessage,
                        $result
                    );
                }
            }
        }

        return $result;
    }
}
