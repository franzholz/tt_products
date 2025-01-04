<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility;

use JambageCom\TtProducts\Hooks\OrderBackend;

class OrderedProductsElement extends AbstractFormElement
{
    public function render()
    {
        FrontendSimulatorUtility::simulateFrontendEnvironment();

        $orderView = GeneralUtility::makeInstance(OrderBackend::class);
        $result = $this->initializeResultArray();
        $result['html'] =
            $orderView->tceSingleOrder(
                $this->data
            );
        FrontendSimulatorUtility::resetFrontendEnvironment();

        return $result;
    }
}
