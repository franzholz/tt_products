<?php

declare(strict_types=1);

namespace JambageCom\TtProducts\Updates;

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

use Doctrine\DBAL\ParameterType;
use JambageCom\TtProducts\Api\UpgradeApi;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class ProductDatasheetUpdater implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    public const TABLE = 'tt_products';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $title = 'EXT:' . TT_PRODUCTS_EXT . ' - Migrate all file relations of datasheet of the table "' . self::TABLE . '" and its language table to sys_file_references';

    /**
     * @var string
     */
    protected $identifier = 'productDatasheetTtProducts';

    public function __construct()
    {
    }

    /**
     * Setter injection for output into upgrade wizards.
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Get title.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get description.
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Migrate the datasheets of the product table "' . self::TABLE . '" and its language records to the FAL. ';
    }

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Return a confirmation message instance.
     */
    public function getConfirmation(): Confirmation
    {
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        $title = '';
        $elementCount = $upgradeApi->countOfTableFieldMigrations(self::TABLE, 'datasheet', 'datasheet_uid', ParameterType::STRING, \PDO::PARAM_INT);

        //         countOfDatasheetMigrations(self::TABLE, self::TABLE . '_lanugae';
        if ($elementCount) {
            $title = sprintf('%d product datasheets can possibly be migrated.', $elementCount);
        } else {
            $title = 'No datasheet can be migrated.';
        }
        $message = 'You can migrate datasheet relations.';
        $confirm = 'Yes, please migrate';
        $deny = 'No, don\'t migrate';
        $result = GeneralUtility::makeInstance(
            Confirmation::class,
            $title,
            $message,
            false,
            $confirm,
            $deny,
            $elementCount > 0
        );

        return $result;
    }

    /**
     * Performs the database update.
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     *
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $result = true;
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        // user decided to migrate, migrate and mark wizard as done
        $queries = $upgradeApi->performTableFieldFalMigrations(
            $customMessage,
            self::TABLE,
            'datasheet',
            'datasheet_uid',
            ParameterType::STRING,
            \PDO::PARAM_INT,
            'uploads/tx_ttproducts/datasheet'
        );

        if (!empty($queries)) {
            foreach ($queries as $query) {
                $databaseQueries[] = $query;
            }
        }

        if ($customMessage != '') {
            $result = false;
        }

        return $result;
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary.
     */
    public function executeUpdate(): bool
    {
        $queries = [];
        $message = '';
        $result = $this->performUpdate($queries, $message);
        $this->output->write($message);

        return $result;
    }

    /**
     * Is an update necessary?
     * Is used to determine whether a wizard needs to be run.
     * Check if data for migration exists.
     */
    public function updateNecessary(): bool
    {
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        $elementCount = $upgradeApi->countOfTableFieldMigrations(self::TABLE, 'datasheet', 'datasheet_uid', ParameterType::STRING, \PDO::PARAM_INT);

        return $elementCount > 0;
    }

    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated".
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }
}
