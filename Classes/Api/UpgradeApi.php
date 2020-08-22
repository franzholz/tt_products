<?php

namespace JambageCom\TtProducts\Api;

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


/**
 * Part of the tt_products (Shop System) extension.
 *
 * functions for the payment
 *
 * @author  Franz Holzinger <franz@ttproducts.de>
 * @package TYPO3
 * @subpackage tt_products
 *
 *
 */


use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpgradeApi {

    public function countOfProductMMArticleMigrations ()
    {
        $count = 0;

        if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][TT_PRODUCTS_EXT]['articleMode'] > 0) {
            $articleTable = 'tt_products_articles';

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($articleTable);
            $queryBuilder->getRestrictions()->removeAll();
            $count = $queryBuilder->count('uid')
                ->from($articleTable)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->gt(
                            'uid_product',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'deleted',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    )
                )
                ->execute()->fetchColumn(0);
        }

        return $count;
    }

    public function countOfMMTableMigrations ($mmTable, $uidLocalOldField)
    {
        $count = 0;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($mmTable);
        $queryBuilder->getRestrictions()->removeAll();
        $affectedRows = $queryBuilder->select('*')
            ->from($mmTable)
            ->where(
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->orderBy('crdate', 'DESC')
            ->execute()
            ->fetchAll();
    
        if (is_array($affectedRows)) {
            $row = $affectedRows['0'];
            if (
                isset($row[$uidLocalOldField]) &&
                isset($row['uid_local']) &&
                $row[$uidLocalOldField] > 0 &&
                $row[$uidLocalOldField] != $row['uid_local']
            ) {
                $count = $queryBuilder->count('uid')
                    ->from($mmTable)
                    ->where(
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->gt(
                                $uidLocalOldField,
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'uid_local',
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'deleted',
                                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            )
                        )
                    )
                    ->execute()->fetchColumn(0);
                }
                
            }

        return $count;
    }


    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles
     *
     * @return array
     */
    public function performProductMMArticleMigration (): array
    {
        $mmTable = 'tt_products_products_mm_articles';
        $articleTable = 'tt_products_articles';
        $productTable = 'tt_products';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($articleTable);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $fields = [$articleTable . '.uid', $articleTable . '.pid', $articleTable . '.uid_product'];

        // Get entries to migrate
        $statement = $queryBuilder
            ->select(
                ...$fields
            )
            ->from($articleTable)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        $articleTable . '.uid_product',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        $articleTable . '.deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->execute();

        // Migrate entries
        while ($record = $statement->fetch()) {
            $prodUid = intval($record['uid_product']);

            /** @var Connection $connection */
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable);
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->getRestrictions()->removeAll();
            $mmCount = $queryBuilder->count('uid')
                ->from($mmTable)
                ->where(
                    $queryBuilder->expr()->andX(
                        $queryBuilder->expr()->eq(
                            'uid_foreign',
                            $queryBuilder->createNamedParameter(intval($record['uid']), \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'uid_local',
                            $queryBuilder->createNamedParameter($prodUid, \PDO::PARAM_INT)
                        ),
                        $queryBuilder->expr()->eq(
                            'deleted',
                            $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                        )
                    )
                )
                ->execute()->fetchColumn(0);

            if ($mmCount == 0) {
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll();
                $time = time();

                $queryBuilder
                    ->insert($mmTable)
                    ->values([
                        'pid' => intval($record['pid']),
                        'crdate' => $time,
                        'tstamp' => $time,
                        'sorting' => 1,
                        'uid_local' => $prodUid,
                        'uid_foreign' => intval($record['uid'])
                    ]);
                
                $databaseQueries[] = $queryBuilder->getSQL();
                $affectedRows = $queryBuilder->execute();

                /** @var Connection $connection */
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($productTable);
                $queryBuilder = $connection->createQueryBuilder();
                $queryBuilder->getRestrictions()->removeAll();

                $queryBuilder
                    ->update($productTable)
                    ->where(
                        $queryBuilder->expr()->andX(
                            $queryBuilder->expr()->eq(
                                'uid',
                                    $queryBuilder->createNamedParameter($prodUid, \PDO::PARAM_INT)
                            ),
                            $queryBuilder->expr()->eq(
                                'deleted',
                                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            )
                        )
                    )
                    ->set(
                        'article_uid',
                        $queryBuilder->expr()->sum(
                            'article_uid',
                            $queryBuilder->createNamedParameter(1, \PDO::PARAM_INT)
                        )
                    );

                $databaseQueries[] = $queryBuilder->getSQL();
                $affectedRows = $queryBuilder->execute();
            }
        }

        return $databaseQueries;
    }

    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles
     *
     * @return array
     */
    public function performProductMMGraduatedPriceMigration (): array
    {
        $mmTable = 'tt_products_mm_graduated_price';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        // Get entries to migrate
        $statement = $queryBuilder
            ->update(
                $mmTable
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'product_uid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        'graduated_price_uid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->set('uid_local', $queryBuilder->quoteIdentifier('product_uid'), false)
            ->set('uid_foreign', $queryBuilder->quoteIdentifier('graduated_price_uid'), false)
            ->set('sorting', $queryBuilder->quoteIdentifier('productsort'), false)
            ->set('sorting_foreign', $queryBuilder->quoteIdentifier('graduatedsort'), false);

        $databaseQueries[] = $queryBuilder->getSQL();
        $affectedRows = $queryBuilder->execute();

        return $databaseQueries;
    }

    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles
     *
     * @return array
     */
    public function performOrderMMProductMigration (): array
    {
        $mmTable = 'sys_products_orders_mm_tt_products';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        // Get entries to migrate
        $statement = $queryBuilder
            ->update(
                $mmTable
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        'sys_products_orders_uid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        'tt_products_uid',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_local',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'uid_foreign',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->set('uid_local', $queryBuilder->quoteIdentifier('sys_products_orders_uid'), false)
            ->set('uid_foreign', $queryBuilder->quoteIdentifier('tt_products_uid'), false);
            
        $databaseQueries[] = $queryBuilder->getSQL();
        $affectedRows = $queryBuilder->execute();

        return $databaseQueries;
    }
}

