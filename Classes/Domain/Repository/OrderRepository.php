<?php

/*
 * This file is part of the "tt_products" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace JambageCom\TtProducts\Domain\Repository;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/**
 * Order repository with all the callable functionality
 */
class OrderRepository
{
    protected string $tablename = 'sys_products_orders';

    /**
     * Constructor.
     */
    public function __construct(
        private readonly ConnectionPool $connectionPool,
    )
    {
    }

    public function getTablename() {
        return $this->tablename;
    }

    protected function getFindByTrackingCode(string $trackingCode, bool $respectEnableFields): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($this->getTablename());

        if (!$respectEnableFields) {
            $queryBuilder->getRestrictions()->removeAll();
        }

        $queryBuilder->select('*')
            ->from($this->getTablename())
            ->where(
                $queryBuilder->expr()->eq(
                    'tracking_code',
                    $queryBuilder->createNamedParameter(
                        $trackingCode,
                        Connection::PARAM_STR
                    )
                )
            )
            ->setMaxResults(1);

        $stmt = $queryBuilder->executeQuery();

        return $stmt->fetchAllAssociative();
    }

    /**
     * record as an array as result
     *
     * @param string $trackingCode tracking code of record
     * @param bool $respectEnableFields if set to false, hidden records are shown
     */
    public function findRowByTrackingCode(string $trackingCode, bool $respectEnableFields = true): ?array
    {
        $result = $this->getFindByTrackingCode($trackingCode, $respectEnableFields);

        if (
            isset($result[0]) &&
            is_array($result[0])
        ) {
            $result = $result[0];
        }

        return $result;
    }
}
