<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Api;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2020 Franz Holzinger <franz@ttproducts.de>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * functions for the basket
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 *
 */
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

use JambageCom\TtProducts\Api\ParameterApi;

class ActivityApi implements SingletonInterface
{
    protected $subActivity = '0';
    protected $activityVarsArray = [
        'clear_basket' => 'products_clear_basket',
        'customized_payment' => 'products_customized_payment',
        'basket' => 'products_basket',
        'info' => 'products_info',
        'overview' => 'products_overview',
        'payment' => 'products_payment',
        'verify' => 'products_verify',
        'finalize' => 'products_finalize',
    ];
    protected $activityArray = [];
    protected $codeActivityArray = [];
    protected $finalActivityArray = [];
    protected $fixCountry = false;

    public function init($codes = []): void
    {
        if (empty($codes)) {
            return;
        }
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        // $request = $parameterApi->getRequest();

        $activityArray = [];
        $subActivity = $this->subActivity;
        $update = $parameterApi->getParameter('products_update') || $parameterApi->getParameter('products_update_x');
        $info = $parameterApi->getParameter('products_info') || $parameterApi->getParameter('products_info_x');
        $payment = $parameterApi->getParameter('products_payment') || $parameterApi->getParameter('products_payment_x');

        $gpVars = $parameterApi->getParameter(TT_PRODUCTS_EXT);

        if (
            !$update &&
            !$payment &&
            !$info &&
            isset($gpVars) &&
            is_array($gpVars) &&
            isset($gpVars['activity']) // CHANGE FHO
        ) {
            if (is_array($gpVars['activity'])) {
                $changedActivity = key($gpVars['activity']);
                if ($position = strpos((string) $changedActivity, '_')) {
                    $subActivity = substr($changedActivity, $position + 1);
                    $changedActivity = substr($changedActivity, 0, $position);
                }
                $theActivity = $this->activityVarsArray[$changedActivity];
                if ($theActivity) {
                    $activityArray[$theActivity] = $gpVars['activity'][$changedActivity];
                }
            } else {
                $changedActivity = $gpVars['activity'];
                $theActivity = $this->activityVarsArray[$changedActivity];
                $activityArray[$theActivity] = true;
            }
        }

        if ($parameterApi->getParameter('products_clear_basket') || $parameterApi->getParameter('products_clear_basket_x')) {
            $activityArray['products_clear_basket'] = true;
        }

        if ($parameterApi->getParameter('products_overview') || $parameterApi->getParameter('products_overview_x')) {
            $activityArray['products_overview'] = true;
        }

        if (!$update) {
            if ($parameterApi->getParameter('products_payment') || $parameterApi->getParameter('products_payment_x')) {
                $activityArray['products_payment'] = true;
            } elseif ($parameterApi->getParameter('products_info') || $parameterApi->getParameter('products_info_x')) {
                $activityArray['products_info'] = true;
            }
        }

        if ($parameterApi->getParameter('products_customized_payment') || $parameterApi->getParameter('products_customized_payment_x')) {
            $activityArray['products_customized_payment'] = true;
        }

        if ($parameterApi->getParameter('products_verify') || $parameterApi->getParameter('products_verify_x')) {
            $activityArray['products_verify'] = true;
        }

        if ($parameterApi->getParameter('products_finalize') || $parameterApi->getParameter('products_finalize_x')) {
            $activityArray['products_finalize'] = true;
        }


        $codeActivityArray = [];
        $isBasketCode = false;
        if (is_array($codes)) {
            foreach ($codes as $k => $code) {
                switch ($code) {
                    case 'BASKET':
                        $codeActivityArray['products_basket'] = true;
                        $isBasketCode = true;
                        break;
                    case 'INFO': // neu
                        if (
                            !(
                                !empty($activityArray['products_verify']) ||
                                !empty($activityArray['products_customized_payment']) ||
                                !empty($activityArray['products_payment']) ||
                                !empty($activityArray['products_finalize'])
                            )
                        ) {
                            $codeActivityArray['products_info'] = true;
                        }
                        $isBasketCode = true;
                        break;
                    case 'OVERVIEW':
                        $codeActivityArray['products_overview'] = true;
                        break;
                    case 'PAYMENT':
                        if (
                            !empty($activityArray['products_finalize'])
                        ) {
                            $codeActivityArray['products_finalize'] = true;
                        } else {
                            $codeActivityArray['products_payment'] = true;
                        }

                        if (!empty($activityArray['products_verify'])) {
                            $isBasketCode = true; // neu, damit verify gesetzt bleibt, wenn vorhanden
                        }
                        break;
                    case 'FINALIZE':
                        $codeActivityArray['products_finalize'] = true;
                        if (!empty($activityArray['products_verify'])) {
                            $isBasketCode = true;
                        }
                        break;
                    default:
                        // nothing
                        break;
                }
            }
        }

        $finalActivityArray = [];
        if ($isBasketCode) {
            $activityArray = array_merge($activityArray, $codeActivityArray);
            $finalActivityArray = $this->transformActivities($activityArray);
        } else {
            // only the code activities if there is no code BASKET or INFO set
            $finalActivityArray = $codeActivityArray;
        }

        $fixCountry =
        (
            !empty($finalActivityArray['products_basket']) ||
            !empty($finalActivityArray['products_info']) ||
            !empty($finalActivityArray['products_payment']) ||
            !empty($finalActivityArray['products_verify']) ||
            !empty($finalActivityArray['products_finalize']) ||
            !empty($finalActivityArray['products_customized_payment'])
        );
        $this->setFixCountry($fixCountry);
        $this->activityArray = $activityArray;
        $this->codeActivityArray = $codeActivityArray;
        $this->finalActivityArray = $finalActivityArray;
        $this->subActivity = $subActivity;
    }

    public function fixCountry($infoObj, $basketExtra, $conf): void
    {
        $systemLoginUser =
        CustomerApi::isSystemLoginUser(
            $conf
        );
        $infoArray = $infoObj->getInfoArray();
        $fixCountry = $this->getFixCountry();

        if (
            $fixCountry &&
            $infoObj->checkRequired(
                'billing',
                $basketExtra,
                $systemLoginUser
            ) == ''
        ) {
            $overwrite =
            ControlApi::isOverwriteMode($infoArray);
            $needsDeliveryAddress = \tx_ttproducts_control_basket::needsDeliveryAddresss($basketExtra);
            $infoObj->mapPersonIntoDelivery(
                $basketExtra,
                $overwrite,
                $needsDeliveryAddress
            );
            \tx_ttproducts_control_basket::setInfoArray($infoObj->getInfoArray());
        }
    }

    /**
     * returns the activities in the order in which they have to be processed.
     *
     * @param		string
     */
    public function transformActivities($activities)
    {
        $retActivities = [];
        $codeActivities = [];
        $codeActivityArray = [
            '1' => 'products_overview',
            'products_basket',
            'products_info',
            'products_payment',
            'products_customized_payment',
            'products_verify',
            'products_finalize',
        ];
        $activityArray = [
            '1' => 'products_clear_basket',
        ];

        if (is_array($activities)) {
            foreach ($activities as $activity => $value) {
                if ($value && in_array($activity, $codeActivityArray)) {
                    $codeActivities[$activity] = true;
                }
            }
        }

        if (!empty($codeActivities['products_info'])) {
            if (!empty($codeActivities['products_payment'])) {
                $codeActivities['products_payment'] = false;
            }
        }

        if (
            !empty($codeActivities['products_basket']) &&
            count($codeActivities) > 1
        ) {
            if (
                count($codeActivities) > 2 ||
                empty($codeActivities['products_overview'])
            ) {
                $codeActivities['products_basket'] = false;
            }
        }

        $sortedCodeActivities = [];
        foreach ($codeActivityArray as $activity) { // You must keep the order of activities.
            if (isset($codeActivities[$activity])) {
                $sortedCodeActivities[$activity] = $codeActivities[$activity];
            }
        }
        $codeActivities = $sortedCodeActivities;

        if (is_array($activities)) {
            foreach ($activityArray as $k => $activity) {
                if (
                    !empty($activities[$activity])
                ) {
                    $retActivities[$activity] = true;
                }
            }
            $retActivities = array_merge($retActivities, $codeActivities);
        }

        return $retActivities;
    }

    public function getSubActivity()
    {
        return $this->subActivity;
    }

    public function getActivityArray()
    {
        return $this->activityArray;
    }

    public function getCodeActivityArray()
    {
        return $this->codeActivityArray;
    }

    public function getActivityVarsArray()
    {
        return $this->activityVarsArray;
    }

    public function getFinalActivityArray()
    {
        return $this->finalActivityArray;
    }

    public function setFixCountry($fixCountry): void
    {
        $this->fixCountry = $fixCountry;
    }

    public function getFixCountry()
    {
        return $this->fixCountry;
    }
}
