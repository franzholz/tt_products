<?php

namespace JambageCom\TtProducts\Domain\Model\Dto;


/**
 * This file is part of the "news" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;



/**
 * Extension Manager configuration.
 */
final class EmConfiguration implements SingletonInterface
{
    // private ?ExtensionConfiguration $extensionConfiguration = null;
    public const EXTENSION_KEY =  'tt_products';

    /** @var string */
    protected $extensionKey = self::EXTENSION_KEY;

    /** @var int */
    protected $pageAsCategory = 0;

    /** @var string */
    protected $addressTable = 'fe_users';

    /** @var bool */
    protected $checkCookies = false;

    /** @var string */
    protected $orderBySortingTables = '';

    /** @var int */
    protected $articleMode = 2;

    /** @var string */
    protected $variantSeparator = ';';

    /** @var array */
    protected $tax = ['fields' => 'tax'];

    /** @var bool */
    protected $fal = false;

    /** @var bool */
    protected $sepa = false;

    /** @var bool */
    protected $bic = false;

    /** @var bool */
    protected $creditpoints = false;

    /** @var string */
    protected $templateFile = '';

    /** @var string */
    protected $templateCheck = '/([^#]+(#{2}|#{5}|#{7,8})([^#])+?)/';

    /** @var int */
    protected $endtimeYear = 2038;

    /** @var array */
    protected $where = ['category' => ''];

    /** @var array */
    protected $hook = ['setPageTitle' => true];

    /** @var array */
    protected $exclude = [
        'tt_products' => '',
        'tt_products_language' => 'datasheet,www,image,image_uid,itemnumber,smallimage,smallimage_uid',
        'tt_products_cat' => '',
        'tt_products_cat_language' => '',
        'tt_products_articles' => '',
        'tt_products_articles_language' => '',
        'tt_products_texts' => '',
        'tt_products_texts_language' => '',
        'sys_products_orders' => 'client_ip,date_of_birth,telephone,fax,ac_uid,cc_uid',
    ];

    /** @var array */
    protected $error = [
        'configuration' => true,
    ];

    /** @var string */
    protected $slugBehaviour = 'unique';

    /**
     * Fill the properties properly.
     *
     * @param array $configuration em configuration
     */
    public function __construct(
        private readonly ExtensionConfiguration $extensionConfiguration,
        array $configuration = [],
    )
    {
        $this->templateFile = 'EXT:' . $this->extensionKey . '/Resources/Private/Templates/example_locallang_xml.html';

        if (empty($configuration)) {
            try {
                $configuration = $this->extensionConfiguration->get($this->extensionKey);
            } catch (\Exception $exception) {
                // do nothing
            }
        }

        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $position = (int) strpos($key, '.');
                $property = substr($key, 0, $position);
                if (
                    property_exists(self::class, $property) &&
                    is_array($this->$property)
                ) {
                    $this->$property =
                    array_merge($this->$property, $value);
                }
            } elseif (property_exists(self::class, $key)) {
                $this->$key = $value;
            }
        }
    }

    // public function injectExtensionConfiguration(ExtensionConfiguration $extensionConfiguration)
    // {
    //     $this->extensionConfiguration = $extensionConfiguration;
    // }

    public function getExtensionKey(): string
    {
        return $this->extensionKey;
    }

    public function getPageAsCategory(): int
    {
        return $this->pageAsCategory;
    }

    public function getAdressTable(): string
    {
        return $this->addressTable;
    }

    public function getCheckCookies(): bool
    {
        return $this->checkCookies;
    }

    public function getOrderBySortingTables(): string
    {
        return $this->orderBySortingTables;
    }

    public function getArticleMode(): int
    {
        return $this->articleMode;
    }

    public function getVariantSeparator(): string
    {
        return $this->variantSeparator;
    }

    public function getTax($parameter): string
    {
        return $this->tax[$parameter];
    }

    public function getFal(): bool
    {
        return $this->fal;
    }

    public function getSepa(): bool
    {
        return $this->sepa;
    }

    public function getBic(): bool
    {
        return $this->bic;
    }

    public function getCreditpoints(): bool
    {
        return $this->creditpoints;
    }

    public function getTemplateFile(): string
    {
        return $this->templateFile;
    }

    public function getTemplateCheck(): string
    {
        return $this->templateCheck;
    }

    public function getEndtimeYear(): string
    {
        return $this->endtimeYear;
    }

    public function getWhere($parameter): string
    {
        return $this->where[$parameter];
    }

    public function getHook($parameter): bool
    {
        return $this->hook[$parameter];
    }

    public function getExclude($parameter): string
    {
        return $this->exclude[$parameter];
    }

    public function getError($parameter): bool
    {
        return $this->error[$parameter];
    }

    public function getSlugBehaviour(): string
    {
        return $this->slugBehaviour;
    }
}

