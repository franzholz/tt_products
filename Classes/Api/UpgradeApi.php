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
 *
 * @package TYPO3
 * @subpackage tt_products
 */
use Doctrine\DBAL\ParameterType;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UpgradeApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function getEmptyValues(
        &$oldEmpty,
        &$newEmpty,
        $oldType,
        $newType,
        $queryBuilder
    ): void {
        $stringEmpty = $queryBuilder->createNamedParameter('');
        $integerEmpty = $queryBuilder->createNamedParameter(0);
        $oldEmpty = $newEmpty = $stringEmpty;
        if ($oldType == \PDO::PARAM_INT) {
            $oldEmpty = $integerEmpty;
        }
        if ($newType == \PDO::PARAM_INT) {
            $newEmpty = $integerEmpty;
        }
    }

    public function countOfProductMMArticleMigrations()
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
                ->execute()->fetchOne()
            ;
        }

        return $count;
    }

    public function countOfMMTableMigrations($mmTable, $uidLocalOldField)
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
            ->fetchAll()
        ;

        if (!empty($affectedRows)) {
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
                    ->execute()->fetchOne()
                ;
            }
        }

        return $count;
    }

    public function countOfTableFieldMigrations($table, $oldField, $newField, $oldType = ParameterType::STRING, $newType = ParameterType::STRING)
    {
        $count = 0;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        $expressionBuilder = $queryBuilder->expr();
        $conditions = $expressionBuilder->andX();
        $this->getEmptyValues(
            $oldEmpty,
            $newEmpty,
            $oldType,
            $newType,
            $queryBuilder
        );

        $conditions->add(
            $expressionBuilder->gt($oldField, $oldEmpty)
        );

        $conditions->add(
            $expressionBuilder->eq($newField, $newEmpty)
        );

        $count = $queryBuilder = $queryBuilder->count('uid')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'deleted',
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->andWhere($conditions)
            ->setMaxResults(1)
            ->execute()
            ->fetchOne()
        ;

        return $count;
    }

    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles.
     */
    public function performProductMMArticleMigration(): array
    {
        $mmTable = 'tt_products_products_mm_articles';
        $articleTable = 'tt_products_articles';
        $productTable = 'tt_products';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($articleTable)
        ;
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
            ->execute()
        ;

        // Migrate entries
        while ($record = $statement->fetch()) {
            $prodUid = intval($record['uid_product']);

            /** @var Connection $connection */
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable)
            ;
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
                ->execute()->fetchOne()
            ;

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
                        'uid_foreign' => intval($record['uid']),
                    ])
                ;

                $databaseQueries[] = $queryBuilder->getSQL();
                $affectedRows = $queryBuilder->execute();

                /** @var Connection $connection */
                $connection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($productTable)
                ;
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
                    )
                ;

                $databaseQueries[] = $queryBuilder->getSQL();
                $affectedRows = $queryBuilder->execute();
            }
        }

        return $databaseQueries;
    }

    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles.
     */
    public function performProductMMGraduatedPriceMigration(): array
    {
        $mmTable = 'tt_products_mm_graduated_price';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable)
        ;
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
            ->set('sorting_foreign', $queryBuilder->quoteIdentifier('graduatedsort'), false)
        ;

        $databaseQueries[] = $queryBuilder->getSQL();
        $affectedRows = $queryBuilder->execute();

        return $databaseQueries;
    }

    /**
     * Perform migration of tt_products_articles relations to tt_products via the product_uid field into a mm table relation between tt_products and tt_products_articles.
     */
    public function performOrderMMProductMigration(): array
    {
        $mmTable = 'sys_products_orders_mm_tt_products';
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($mmTable)
        ;
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
            ->set('uid_foreign', $queryBuilder->quoteIdentifier('tt_products_uid'), false)
        ;

        $databaseQueries[] = $queryBuilder->getSQL();
        $affectedRows = $queryBuilder->execute();

        return $databaseQueries;
    }

    public function performTableFieldMigrations($table, $oldField, $newField, $oldFieldtype = ParameterType::STRING, $newFieldtype = ParameterType::STRING)
    {
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
        ;
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $this->getEmptyValues(
            $oldEmpty,
            $newEmpty,
            $oldFieldtype,
            $newFieldtype,
            $queryBuilder
        );

        // Get entries to migrate
        $statement = $queryBuilder
            ->update(
                $table
            )
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        $oldField,
                        $queryBuilder->createNamedParameter($oldEmpty, $oldFieldtype)
                    ),
                    $queryBuilder->expr()->eq(
                        $newField,
                        $queryBuilder->createNamedParameter($newEmpty, $newFieldtype)
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->set($newField, $queryBuilder->quoteIdentifier($oldField), false)
        ;

        $databaseQueries[] = $queryBuilder->getSQL();
        $affectedRows = $queryBuilder->execute();

        return $databaseQueries;
    }

    /**
     * Migrates a single field.
     *
     * @param string $customMessage
     *
     * @throws \Exception
     */
    public function performTableFieldFalMigrations(
        &$customMessage,
        $table,
        $oldField,
        $newField,
        $oldFieldtype = ParameterType::STRING,
        $newFieldtype = ParameterType::STRING,
        $sourcePath = ''
    ) {
        $databaseQueries = [];

        /** @var Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
        ;
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $this->getEmptyValues(
            $oldEmpty,
            $newEmpty,
            $oldFieldtype,
            $newFieldtype,
            $queryBuilder
        );

        // Get entries to migrate
        $statement = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gt(
                        $oldField,
                        $oldEmpty
                    ),
                    $queryBuilder->expr()->eq(
                        $newField,
                        $newEmpty
                    ),
                    $queryBuilder->expr()->eq(
                        'deleted',
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
        ;

        $databaseQueries[] = $queryBuilder->getSQL();
        $statement = $queryBuilder->execute();

        while ($row = $statement->fetch()) {
            // Do something with that single row
            if (is_array($row)) {
                $this->migrateField(
                    $customMessage,
                    $databaseQueries,
                    $table,
                    $row,
                    $oldField,
                    $newField,
                    $sourcePath
                );
            }
        }

        return $databaseQueries;
    }

    protected function migrateField(
        &$customMessage,
        &$databaseQueries,
        $table,
        array $row,
        $oldField,
        $newField,
        $sourcePath = ''
    ) {
        if (
            empty($table) ||
            empty($oldField) ||
            empty($newField) ||
            !empty($row[$newField]) ||
            !isset($GLOBALS['TCA'][$table]['columns'][$newField])
        ) {
            return false;
        }
        $fileadminDirectory = rtrim($GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'], '/') . '/';
        $i = 0;
        if ($sourcePath == '') {
            $sourcePath = 'uploads/tx_ttproducts/' . $oldField;
            if (isset($GLOBALS['TCA'][$table]['columns'][$oldField])) {
                $sourcePath = $GLOBALS['TCA'][$table]['columns'][$oldField]['config']['uploadfolder'];
            }
        }

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $defaultStorage = $resourceFactory->getDefaultStorage();
        if (!is_object($defaultStorage)) {
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $defaultStorage = $storageRepository->getDefaultStorage() ?? $storageRepository->findByUid(1);
        }
        $storageUid = (int)$defaultStorage->getUid();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $fieldItems = explode(',', $row[$oldField]);
        $pathSite = Environment::getPublicPath() . '/';

        foreach ($fieldItems as $item) {
            $fileUid = null;
            $existingFileRecord = null;
            $sourceExists = false;
            $sourcePathFile = $pathSite . $sourcePath . '/' . basename($item);
            $targetPath = 'user_upload';
            $targetDirectory = $pathSite . $fileadminDirectory . $targetPath;
            $targetDirectoryFile = $targetDirectory . '/' . basename($item);
            $targetPathFile = $targetPath . '/' . basename($item);
            // maybe the file needs to be moved, so check if the source file still exists
            if (file_exists($sourcePathFile)) {
                $sourceExists = true;
                if (!is_dir($targetDirectory)) {
                    GeneralUtility::mkdir_deep($targetDirectory);
                }
                // see if the target file already exists in the storage
                $fileSha1 = sha1_file($targetDirectoryFile);
                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file');
                $queryBuilder->getRestrictions()->removeAll();
                $existingFileRecord = $queryBuilder->select('uid')->from('sys_file')->where(
                    $queryBuilder->expr()->eq(
                        'sha1',
                        $queryBuilder->createNamedParameter($fileSha1, \PDO::PARAM_STR)
                    ),
                    $queryBuilder->expr()->eq(
                        'storage',
                        $queryBuilder->createNamedParameter($storageUid, \PDO::PARAM_INT)
                    )
                )->execute()->fetch();
                // the file exists, the file does not have to be moved again
                if (is_array($existingFileRecord) && $existingFileRecord['uid']) {
                    $fileUid = $existingFileRecord['uid'];
                } else {
                    // just move the file (no duplicate)
                    rename($sourcePathFile, $targetDirectoryFile);
                }
            } else {
                // nothing
                // Maybe the original files have already been moved.
            }

            if ($fileUid == null) {
                // get the File object if it has not been fetched before
                try {
                    // if the target file does not exist, we should just continue, but leave a message in the docs, maybe because the source file is missing.
                    // ideally, the user would be informed after the update as well.
                    /** @var File $file */
                    $file = $defaultStorage->getFile($targetPathFile);
                    $fileUid = $file->getUid();
                } catch (\InvalidArgumentException $e) {
                    $path = $targetPathFile;
                    $errorMessage = $e->getMessage();

                    // no file found, no reference can be set
                    $this->logger->warning(
                        $errorMessage . ' The file "' . $sourcePath . '/' . basename($item) . '" could not be migrated to the folder "' . $targetDirectory . '" .',
                        [
                            'table' => $table,
                            'record' => $row,
                            'field' => $oldField,
                        ]
                    );
                    $format = $errorMessage . ' Referencing field: %s.%d.%s. The item "%s" with the file "%s" could not be migrated to the folder "%s" .' . (is_writable($targetDirectory) ? '' : ' No write access!' . ($sourceExists ? '' : ' The source file does not exist. '));
                    $message = sprintf(
                        $format,
                        $table,
                        $row['uid'],
                        $oldField,
                        $item,
                        $sourcePathFile, // $sourcePath . '/' . basename($item),
                        $fileadminDirectory . $targetPath
                    );
                    $customMessage .= PHP_EOL . $message;
                    continue;
                }
            }

            if ($fileUid > 0) {
                if (is_array($existingFileRecord)) {
                    $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');

                    // Check if entries are already referenced
                    $count = $queryBuilder->count('uid')
                        ->from('sys_file_reference')
                        ->where(
                            $queryBuilder->expr()->andX(
                                $queryBuilder->expr()->eq(
                                    'fieldname',
                                    $queryBuilder->createNamedParameter($newField)
                                ),
                                $queryBuilder->expr()->eq(
                                    'deleted',
                                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    'table_local',
                                    $queryBuilder->createNamedParameter('sys_file')
                                ),
                                $queryBuilder->expr()->eq(
                                    'pid',
                                    $queryBuilder->createNamedParameter($row['pid'], \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    'uid_foreign',
                                    $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    'uid_local',
                                    $queryBuilder->createNamedParameter($fileUid, \PDO::PARAM_INT)
                                ),
                                $queryBuilder->expr()->eq(
                                    'tablenames',
                                    $queryBuilder->createNamedParameter($table)
                                )
                            )
                        )
                        ->execute()->fetchOne()
                    ;

                    // if the file record has already been assigned to this table
                    if ($count > 0) {
                        continue;
                    }
                }

                $fields = [
                    'fieldname' => $newField,
                    'table_local' => 'sys_file',
                    'pid' => $row['pid'],
                    'uid_foreign' => $row['uid'],
                    'uid_local' => $fileUid,
                    'tablenames' => $table,
                    'crdate' => time(),
                    'tstamp' => time(),
//                     'sorting' => ($i + 256),
                    'sorting_foreign' => $i,
                ];
                $queryBuilder = $connectionPool->getQueryBuilderForTable('sys_file_reference');
                $queryBuilder->insert('sys_file_reference')->values($fields)->execute();
                $databaseQueries[] = str_replace(LF, ' ', $queryBuilder->getSQL());
                ++$i;
            }
        } // Ende foreach

        // Update referencing table's original field to now contain the count of references,
        // but only if all new references could be set
        if ($i === count($fieldItems)) {
            $queryBuilder = $connectionPool->getQueryBuilderForTable($table);
            // letzter Schritt
            $queryBuilder->update($table)->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], \PDO::PARAM_INT)
                )
            )->set($newField, $i)->execute();
            $databaseQueries[] = str_replace(LF, ' ', $queryBuilder->getSQL());
        }
    }
}
