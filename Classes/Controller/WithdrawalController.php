<?php

declare(strict_types=1);

/***************************************************************
*  Copyright notice
*
*  (c) 2026 Franz Holzinger (franz@ttproducts.de)
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
 * Creates a list of products for the shopping basket in TYPO3.
 * Also controls basket, searching and payment.
 *
 * @author	Franz Holzinger <franz@ttproducts.de>
 *
 * @maintainer	Franz Holzinger <franz@ttproducts.de>
 *
 *
 */
namespace JambageCom\TtProducts\Controller;

use Psr\Http\Message\ServerRequestInterface;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

use JambageCom\TtProducts\Domain\Repository\OrderRepository;


class WithdrawalController implements SingletonInterface
{

    const STATUS_IDLE = 0;
    const STATUS_FORM = 1;
    const STATUS_WITHDRAW = 2;


    /**
     * Constructor.
     */
    public function __construct(
        private readonly OrderRepository $orderRepository,
    )
    {
    }

    public function main(
        array &$errorCode,
        int $id,
        string $trackingCode,
        string $trackingName,
        string $trackingEmail,
        string $trackingComment,
        bool $withdrawal,
        bool $withdrawalConfirmation,
        string $templateSuffix,
        string $content,
        array $conf,
        ServerRequestInterface $request,
    ) : string {
        $tablesObj = GeneralUtility::makeInstance('tx_ttproducts_tables');
        $status = ($withdrawal ? self::STATUS_FORM : ($withdrawalConfirmation ? self::STATUS_WITHDRAW : self::STATUS_IDLE));
        $withdrawEmailSent = false;

        $theCode = 'WITHDRAWAL';
        $templateFile = '';
        $urlObj = GeneralUtility::makeInstance('tx_ttproducts_url_view');
        $markerObj = GeneralUtility::makeInstance('tx_ttproducts_marker');
        $markerArray = $markerObj->getGlobalMarkerArray();
        $templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $templateApi = GeneralUtility::makeInstance('tx_ttproducts_template');
        $errorCode = [];

        $content = '<b> button</b>';
        $templateCode =
            $templateApi->get(
                $theCode,
                $templateFile,
                $errorCode
            );

        $subpartmarkerObj = GeneralUtility::makeInstance('tx_ttproducts_subpartmarker');

        if ($status ==  self::STATUS_WITHDRAW) {
            $orderRow = $this->orderRepository->findRowByTrackingCode($trackingCode);

            if (!empty($orderRow)) {
                $markerArray['###WITHDRAWAL_PROCESSING###'] =
                    sprintf($markerArray['###WITHDRAWAL_PROCESSING###'], $orderRow['uid']);
            }

            if (!empty($orderRow['status_log'])) {
                $statusLog = unserialize($orderRow['status_log']);
                $statusCancel = '50';
                $isCancelled = false;

                if (is_array($statusLog)) {
                    foreach ($statusLog as $statusLine) {
                        if (
                            $statusLine['status'] == $statusCancel ||
                            $statusLine['status'] > 100
                        ) {
                            $isCancelled = true;
                            break;
                        }
                    }
                }

                if (!$isCancelled) {
                    $tracking = GeneralUtility::makeInstance('tx_ttproducts_tracking');
                    $tracking->init();
                    $statusCodes = $tracking->getStatusCodeArray();
                    $newStatusLog = [
                        'time' => time(),
                        'info' => $statusCodes[$statusCancel] ?? 'withdraw (' . $statusCancel . ')' ,
                        'status' => $statusCancel,
                        'comment' => 'submitted by withdrawal form user ' . htmlspecialchars($trackingName) . ' <' . htmlspecialchars($trackingEmail) . '> ' . htmlspecialchars($trackingComment),
                    ];
                    $statusLog[] = $newStatusLog;

                    $fieldsArray = [];
                    $fieldsArray['status_log'] = serialize($statusLog);
                    $fieldsArray['status'] = intval($newStatusLog['status']);
                    $fieldsArray['tstamp'] = time();

                    $recipient = $conf['orderEmail_to'];
                    if (
                        !empty($orderRow['email']) &&
                        !empty($orderRow['email_notify']))
                    {
                        $recipient .= ',' . $orderRow['email'];
                    }
                    $templateMarker = 'TRACKING_EMAILNOTIFY_TEMPLATE';
                    $orderNumber = $orderRow['order_no'];
                    if (MathUtility::canBeInterpretedAsInteger($orderNumber)) {
                        $orderNumber =
                            DatabaseTableApi::generateOrderNo($orderRow['order_no'], $conf['orderNumberPrefix'] ?? '');
                    }
                    $emailTemplateCode =
                        $templateApi->get(
                            'TRACKING',
                            $templateFile,
                            $errorCode
                        );

                    $orderObj = $tablesObj->get('sys_products_orders');
                    $orderData = $orderObj->getOrderData($orderRow);

                    \tx_ttproducts_email_div::sendNotifyEmail(
                        $conf,
                        $templateSuffix,
                        'fe_users',
                        $orderNumber,
                        $recipient,
                        $newStatusLog,
                        $statusCodes,
                        $trackingCode,
                        $orderRow,
                        $orderData,
                        $templateCode,
                        $templateMarker,
                        $conf['orderEmail_fromName'],
                        $conf['orderEmail_from'],
                    );

                    $GLOBALS['TYPO3_DB']->exec_UPDATEquery(
                        'sys_products_orders',
                        'uid=' . intval($orderRow['uid']),
                        $fieldsArray
                    );

                    $withdrawEmailSent = true;
                }
            }
        }

        $templateArea = '';
        switch ($status) {
            case self::STATUS_FORM:
                $templateArea = 'WITHDRAWAL_CONFIRMATION_TEMPLATE';
                break;

            case self::STATUS_WITHDRAW:
                $templateArea = 'WITHDRAWAL_PROCESSING_TEMPLATE';
                break;

            default:
                $templateArea = 'WITHDRAWAL_TEMPLATE';
                break;
        }

        $template =
            $subpartmarkerObj->getSubpart(
                $templateCode,
                $subpartmarkerObj->spMarker('###' . $templateArea . '###'),
                $errorCode
            );

        $addQueryString = [];
        $markerArray =
            $urlObj->addURLMarkers(
                $id,
                $markerArray,
                $theCode,
                $addQueryString,
                ''
            );

        $markerArray['###TRACKING_NUMBER###'] = htmlspecialchars($trackingCode);
        $markerArray['###TRACKING_NAME###'] = htmlspecialchars($trackingName);
        $markerArray['###TRACKING_EMAIL###'] = htmlspecialchars($trackingEmail);
        $markerArray['###TRACKING_COMMENT###'] = htmlspecialchars($trackingComment);

        $content = $templateService->substituteMarkerArray(
            $template,
            $markerArray
        );

        if ($withdrawEmailSent) {
            \tx_ttproducts_email_div::sendNotifyEmail(
                $conf,
                '',
                'fe_users',
                $orderNumber,
                $trackingEmail,
                $newStatusLog,
                $statusCodes,
                '',
                $orderRow,
                [],
                $content,
                $templateMarker,
                $conf['orderEmail_fromName'],
                $conf['orderEmail_from'],
            );
        }

        return $content;
    }

}
