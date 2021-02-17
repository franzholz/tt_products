<?php
declare(strict_types = 1);
namespace JambageCom\TtProducts\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;


class OrderedProductsElement extends AbstractFormElement
{
    public function render()
    {
        $orderView = GeneralUtility::makeInstance(\JambageCom\TtProducts\Hooks\OrderBackend::class);
        $result = $this->initializeResultArray();
        $result['html'] =
            $orderView->tceSingleOrder(
                $this->data,
                null
            );
        return $result;
    }
}



