<?php declare(strict_types = 1);

namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Suilven\FreeTextSearch\Helper\BulkIndexingHelper;
use Suilven\FreeTextSearch\Indexes;
use Suilven\FreeTextSearch\Types\SearchParamTypes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Tests\Models\FlickrAuthor;
use Suilven\ManticoreSearch\Tests\Models\FlickrPhoto;
use Suilven\ManticoreSearch\Tests\Models\FlickrSet;
use Suilven\ManticoreSearch\Tests\Models\FlickrTag;

class SearchFixturesTest extends SapphireTest
{
    protected static $fixture_file = ['tests/fixtures/sitetree.yml', 'tests/fixtures/flickrphotos.yml'];

    protected static $extra_dataobjects = [
        FlickrPhoto::class,
        FlickrTag::class,
        FlickrSet::class,
        FlickrAuthor::class,
    ];

    /** @var int */
    private static $pageID;

    public function setUp(): void
    {
        parent::setUp();

        /** @var \Suilven\FreeTextSearch\Indexes $indexesService */
        $indexesService = new Indexes();
        $indexesArray = $indexesService->getIndexes();
        $helper = new ReconfigureIndexesHelper();
        $helper->reconfigureIndexes($indexesArray);

        \error_log('INDEXING');
        $helper = new BulkIndexingHelper();
        $helper->bulkIndex('sitetree');
        \error_log('/INDEXING');
    }


    public function testSimilar(): void
    {
        $page = $this->objFromFixture(SiteTree::class, 'sitetree_49');
        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $result = $searcher->searchForSimilar($page);

        // 1 is the original doc, 3 others contain sheep, 1 contains mussy an 1 contains shuttlecock
        $this->assertEquals(6, $result->getTotaNumberOfResults());
        $hits = $result->getRecords();
        $ids = [];
        foreach ($hits as $hit) {
            $ids[] = $hit->ID;
        }

        $this->assertEquals([49, 40, 45, 21, 36, 47], $ids);
    }


    public function testAndSearch(): void
    {
        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $searcher->setSearchType(SearchParamTypes::AND);
        $result = $searcher->search('sheep shuttlecock');
        $this->assertEquals(1, $result->getTotaNumberOfResults());
        $hits = $result->getRecords();
        $ids = [];
        foreach ($hits as $hit) {
            $ids[] = $hit->ID;
        }
        $this->assertEquals([49], $ids);
    }


    public function testORSearch(): void
    {
        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $searcher->setSearchType(SearchParamTypes::OR);
        $result = $searcher->search('sheep shuttlecock');
        $this->assertEquals(5, $result->getTotaNumberOfResults());
        $hits = $result->getRecords();
        $ids = [];
        foreach ($hits as $hit) {
            $ids[] = $hit->ID;
        }
        $this->assertEquals([49, 45, 21, 36, 47], $ids);
    }
}
