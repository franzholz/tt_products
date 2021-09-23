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

 
if (
    version_compare(TYPO3_version, '9.5.0', '<')
) {
    return;
}

use Symfony\Component\Console\Output\OutputInterface;


use TYPO3\CMS\Core\Utility\GeneralUtility;

use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;

use JambageCom\TtProducts\Api\UpgradeApi;


class ProductMMArticleTtProductsUpdater implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    const TABLE = 'tt_products_products_mm_articles';

     /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $title = 'EXT:' . TT_PRODUCTS_EXT . ' - Migrate product to article relations after changing articleMode from 0 to 1 or 2.';

    /**
     * @var string
     */
    protected $identifier = 'productMMArticleTtProducts';


    /** @var UpgradeApi */
    protected $upgradeApi;

    public function __construct()
    {
        $this->upgradeApi = GeneralUtility::makeInstance(UpgradeApi::class);
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
        return 'Migrate the articles to products relations into the relational mm table "tt_products_products_mm_articles" for "tt_products" and "tt_products_articles" records. This wizard migrates all articles which have a parent product assigned. The mm table between products and articles will be filled accordingly to these relations. This shall be executed once if you change the articleMode from 0 to 1 or 2. Otherwise your present article to product relations from article mode 0 will be lost. ';
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
        $message = '';
        $elementCount = $this->upgradeApi->countOfProductMMArticleMigrations();

        if ($elementCount) {
            $message = sprintf('%s product to article relations can possibly be migrated.  Afterwards you can empty the field uid_product in the table tt_products_articles using phpMyAdmin.', $elementCount);
        } else {
            $message = 'Nothing can be migrated';
        }

        $title = 'Migration of product to article relations if you have switched from articleMode 0 to articleMode 1 or 2 in the extension configuration.';
        $confirm = 'Yes, please migrate now!';
        $deny = 'No';
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
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $queries = $this->upgradeApi->performProductMMArticleMigration();
        if (!empty($queries)) {
            foreach ($queries as $query) {
                $databaseQueries[] = $query;
            }
        }

        return true;
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
        $elementCount = $this->upgradeApi->countOfProductMMArticleMigrations();
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
