<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Middleware;

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
use JambageCom\TtProducts\Api\ParameterApi;
use JambageCom\TtProducts\SessionHandler\SessionHandler;
use JambageCom\TtProducts\Api\VariantApi;
use JambageCom\TtProducts\Api\BasketApi;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Stores the original request for an Ajax call before processing a request for the TYPO3 Frontend.
 */
class StoreBasket implements MiddlewareInterface
{
    /**
     * to store the current basket.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parameterApi = GeneralUtility::makeInstance(ParameterApi::class);
        $parameterApi->setRequest($request);
        $basketExtRaw = $parameterApi->getBasketExtRaw();

        if (is_array($basketExtRaw) && !empty($basketExtRaw)) {
            $sessionHandler = GeneralUtility::makeInstance(SessionHandler::class);
            $sessionHandler->allowCookie(); // TODO
            $variantApi = GeneralUtility::makeInstance(VariantApi::class);
            $basketApi = GeneralUtility::makeInstance(BasketApi::class);
            $basketExt = $basketApi->readBasketExt();
            $basketSetup = $basketApi->readBasketSetup();
            $variantConf = $variantApi->readVariantConf();
            $selectableArray = $variantApi->readSelectable();
            $firstVariantArray = $variantApi->readFirstVariant();
            $variantApi->init(
                $variantConf,
                $basketSetup['useArticles'] ?? 0,
                $selectableArray,
                $firstVariantArray
            );

            if (!empty($basketSetup) && is_array($variantConf)) {
                $basketApi->process(
                    $basketExt,
                    $basketExtRaw,
                    $basketSetup['updateMode'] ?? 0,
                    true,
                    $basketSetup['basketMaxQuantity'] ?? 1000,
                    $basketSetup['alwaysInStock'] ?? 0,
                    $basketSetup['alwaysUpdateOrderAmount'] ?? 1,
                    $basketSetup['quantityIsFloat'] ?? 0,
                    $basketSetup['useArticles'] ?? 3,
                    $variantConf
                );
            }
        }

        return $handler->handle($request);
    }
}
