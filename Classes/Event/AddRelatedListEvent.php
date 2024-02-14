<?php

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

namespace JambageCom\TtProducts\Event;

/**
 * This event is fired before a related view output starts
 */
final class AddRelatedListEvent
{
    public function __construct(
        private array $result,
        private readonly string $code,
        private readonly string $funcTablename,
        private readonly int $uid,
        private readonly array $paramUidArray,
        private readonly int $useArticles,
    ) {}

    public function getResult(): array
    {
        return $this->result;
    }

    public function setResult(array $result): void
    {
        $this->result = $result;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getFuncTablename(): string
    {
        return $this->funcTablename;
    }

    public function getUid(): int
    {
        return $this->uid;
    }

    public function getParamUidArray(): array
    {
        return $this->paramUidArray;
    }

    public function getUseArticles(): int
    {
        return $this->useArticles;
    }
}

