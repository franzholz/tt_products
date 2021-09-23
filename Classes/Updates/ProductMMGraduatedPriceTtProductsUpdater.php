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


use Symfony\Component\Console\Output\OutputInterface;


use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ChattyInterface;
use TYPO3\CMS\Install\Updates\Confirmation;
use TYPO3\CMS\Install\Updates\ConfirmableInterface;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;



use JambageCom\TtProducts\Api\UpgradeApi;


class ProductMMGraduatedPriceTtProductsUpdater implements UpgradeWizardInterface, ConfirmableInterface, ChattyInterface
{
    const TABLE = 'tt_products_mm_graduated_price';

     /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $title = 'EXT:' . TT_PRODUCTS_EXT . ' - Migrate product to graduated price relations because of new requirements for IRRE inline intermediate tables in TYPO3.';

    /**
     * @var string
     */
    protected $identifier = 'productMMGraduatedPriceTtProducts';

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
        return 'Migrate the product to graduated price relations for the obligatory fields uid_local and uid_foreign inside of the relational mm table "tt_products_mm_graduated_price" for "tt_products" records. IRRE inline intermediate tables require the field names uid_local and uid_foreign. ';
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
        $title = '';
        $elementCount = $this->upgradeApi->countOfMMTableMigrations(self::TABLE, 'product_uid');
        if ($elementCount) {
            $title = sprintf('%s product to graduated price relations can possibly be migrated.', $elementCount);
        } else {
            $title = 'No graduated price records can be migrated';
        }
        $message = 'You can migrate product to graduated price relations.
        ';
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
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        // user decided to migrate, migrate and mark wizard as done
        $queries = $this->upgradeApi->performProductMMGraduatedPriceMigration();
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
        $elementCount = $this->upgradeApi->countOfMMTableMigrations(self::TABLE, 'product_uid');
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

    /**
     * Marks some wizard as being "seen" so that it will not be shown again.
     */
//     protected function markWizardAsDone() {
//         $service = GeneralUtility::makeInstance(UpgradeWizardsService::class);
// 
//         $service->markWizardAsDone($this->getIdentifier());
//     }
}
