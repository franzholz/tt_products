<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Form\Element;

use JambageCom\TtProducts\Hooks\OrderBackend;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class OrderHtmlElement extends AbstractFormElement
{
    public function render()
    {
        $orderView = GeneralUtility::makeInstance(OrderBackend::class);
        $result = $this->initializeResultArray();
        $result['html'] =
            $orderView->displayOrderHtml(
                $this->data,
                null
            );

        return $result;
    }
}
