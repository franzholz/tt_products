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

use Symfony\Component\Console\Output\OutputInterface;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

use JambageCom\TtProducts\Api\UpgradeApi;


class ProductImageUpdater implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    const TABLES = 'tt_products,tt_products_language,tt_products_cat,tt_products_articles';

    protected $tableFields = [
        'tt_products' => ['image', 'smallimage'],
        'tt_products_language' => ['image', 'smallimage'],
        'tt_products_cat' => ['image', 'sliderimage'],
        'tt_products_articles' => ['image', 'smallimage'],
    ];

     /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $title = 'EXT:' . TT_PRODUCTS_EXT . ' - Migrate all images of the tables ' . self::TABLES . ' to sys_file_references';
        
    /**
     * @var string
     */
    protected $identifier = 'productImageTtProducts';

    public function __construct()
    {
    }

    /**
     * Setter injection for output into upgrade wizards
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get description
     *
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Migrate the images of the tt_products tables "' . self::TABLES . '" to the FAL. ';
    }

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * Return a confirmation message instance
     *
     * @return \TYPO3\CMS\Install\Updates\Confirmation
     */
    public function getConfirmation(): Confirmation
    {
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        $title = '';
        $tables = explode(',', self::TABLES);
        foreach ($tables as $table) {
            if (!isset($this->tableFields[$table])) {
                continue;
            }
            $fields = $this->tableFields[$table];
            $elementCount = 0;
            foreach ($fields as $field) { 
                $elementCount += $upgradeApi->countOfTableFieldMigrations($table, $field, $field . '_uid', ParameterType::STRING, \PDO::PARAM_INT);
            }
            
    //         countOfImageMigrations(self::TABLE, self::TABLE . '_lanugae';
            if ($elementCount) {
                $title .= sprintf('%d %s images can possibly be migrated.' . PHP_EOL, $elementCount, $table);
            }
        }
        if ($title == '') {
            $title = 'No image can be migrated.';
        }
        $message = 'You can migrate images.';
        $confirm = 'Yes, please migrate';
        $deny = 'No, don\'t migrate';
        $result = GeneralUtility::makeInstance(
            Confirmation::class,
            $title,
            $message,
            false,
            $confirm,
            $deny,
            ($elementCount > 0 && version_compare(TYPO3_version, '9.5.0', '>='))
        );

        return $result;
    }

    /**
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $result = true;
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        $tables = explode(',', self::TABLES);

        foreach ($tables as $table) {
            if (!isset($this->tableFields[$table])) {
                continue;
            }
            $fields = $this->tableFields[$table];

            foreach ($fields as $field) { 
                // user decided to migrate, migrate and mark wizard as done
                $queries = $upgradeApi->performTableFieldFalMigrations(
                    $customMessage,
                    $table,
                    $field,
                    $field . '_uid',
                    ParameterType::STRING,
                    \PDO::PARAM_INT,
                    '' // no default path uploads/pics here
                );
            
                if (!empty($queries)) {
                    foreach ($queries as $query) {
                        $databaseQueries[] = $query;
                    }
                }
            }
        }
        
        if ($customMessage != '') {
            $result = false;
        }
        return $result;
    }

    /**
     * Execute the update
     * Called when a wizard reports that an update is necessary
     *
     * @return bool
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
     *
     * @return bool
     */
    public function updateNecessary(): bool
    {
        $upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
        $elementCount = 0;
        $tables = explode(',', self::TABLES);
        foreach ($tables as $table) {
            if (!isset($this->tableFields[$table])) {
                continue;
            }
            $fields = $this->tableFields[$table];
            foreach ($fields as $field) { 
                $elementCount += 
                    $upgradeApi->countOfTableFieldMigrations(
                        $table,
                        $field, 
                        $field . '_uid', 
                        ParameterType::STRING,
                        \PDO::PARAM_INT
                    );
                if ($elementCount > 0) {
                    break;
                }
            }
            if ($elementCount > 0) {
                break;
            }
        }
        return ($elementCount > 0);
    } 
	
    /**
     * Returns an array of class names of Prerequisite classes
     * This way a wizard can define dependencies like "database up-to-date" or
     * "reference index updated"
     *
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }
}
