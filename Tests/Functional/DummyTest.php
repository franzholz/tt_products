<?php

declare(strict_types=1);

namespace Jambagecom\TtProducts\Tests\Functional;

use JambageCom\TtProducts\Domain\Model\Dto\EmConfiguration;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class DummyTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'typo3/cms-filelist',
    ];
    protected array $testExtensionsToLoad = [
        'jambagecom/div2007',
        'jambagecom/tsparser',
        'friendsoftypo3/typo3db-legacy',
        'jambagecom/tt-products',
    ];
    #[Test]
    public function dummy(): void
    {
        $emConfig = GeneralUtility::makeInstance(EmConfiguration::class);

        self::assertInstanceOf(EmConfiguration::class, $emConfig);
    }
}
