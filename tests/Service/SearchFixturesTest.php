<?php

declare(strict_types = 1);

namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Suilven\FreeTextSearch\Container\Facet;
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

        $helper = new BulkIndexingHelper();
        $helper->bulkIndex('sitetree');
        $helper->bulkIndex('flickrphotos');
        $helper->bulkIndex('members');
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
        $this->assertEquals([49, 45, 40, 47, 21, 36], $ids);
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

        $this->assertEquals([49, 45, 47, 21, 36], $ids);
    }


    public function testFlickrFacetsEmptySearchTerm(): void
    {
        $searcher = new Searcher();
        $searcher->setIndexName('flickrphotos');
        $searcher->setSearchType(SearchParamTypes::AND);
        $searcher->setFacettedTokens(['ISO', 'Aperture', 'Orientation']);
        $result = $searcher->search('*');
        $this->assertEquals(50, $result->getTotaNumberOfResults());
        $hits = $result->getRecords();
        $ids = [];
        foreach ($hits as $hit) {
            $ids[] = $hit->ID;
        }

        // TODO What is the default search order - this was 1 to 15 consecutively
        $this->assertEquals([10, 49, 7, 33, 18, 37, 27, 29, 31, 6, 14, 35, 48, 22, 25], $ids);


        $facets = $result->getFacets();
        $this->assertEquals([
            1600 => 7,
            800 => 11,
            400 => 6,
            200 => 8,
            100 => 10,
            64 => 4,
            25 => 4,
        ], $facets[0]->asKeyValueArray());

        $this->assertEquals([
            32 => 1,
            27 => 9,
            22 => 6,
            16 => 7,
            11 => 7,
            8 => 5,
            5 => 6,
            4 => 3,
            2 => 6,
        ], $facets[1]->asKeyValueArray());

        $this->assertEquals([
            90 => 20,
            0 => 30,
        ], $facets[2]->asKeyValueArray());

        $this->checkSumDocumentCount($facets[0], 50);
        $this->checkSumDocumentCount($facets[1], 50);
        $this->checkSumDocumentCount($facets[2], 50);
    }


    public function testFlickrFacetsIncludeSearchTerm(): void
    {
        $searcher = new Searcher();
        $searcher->setIndexName('flickrphotos');
        $searcher->setSearchType(SearchParamTypes::AND);
        $searcher->setFacettedTokens(['ISO', 'Aperture', 'Orientation']);
        $result = $searcher->search('Tom');
        $this->assertEquals(13, $result->getTotaNumberOfResults());
        $hits = $result->getRecords();
        $ids = [];
        foreach ($hits as $hit) {
            $ids[] = $hit->ID;
        }

        $this->assertEquals([48, 29, 47, 23, 9, 32, 34, 16, 12, 5, 24, 43, 46], $ids);

        /** @var array<\Suilven\FreeTextSearch\Container\Facet> $facets */
        $facets = $result->getFacets();

        $this->assertEquals('ISO', $facets[0]->getName());
        $this->assertEquals('Aperture', $facets[1]->getName());
        $this->assertEquals('Orientation', $facets[2]->getName());


        $this->assertEquals([
            1600 => 2,
            800 => 3,
            400 => 1,
            200 => 2,
            100 => 2,
            25 => 3,
        ], $facets[0]->asKeyValueArray());


        $this->assertEquals([
            27 => 1,
            16 => 3,
            11 => 2,
            8 => 3,
            5 => 1,
            2 => 3,
        ], $facets[1]->asKeyValueArray());

        $this->assertEquals([
            90 => 5,
            0 => 8,
        ], $facets[2]->asKeyValueArray());

        $this->checkSumDocumentCount($facets[0], 13);
        $this->checkSumDocumentCount($facets[1], 13);
        $this->checkSumDocumentCount($facets[2], 13);
    }


    private function checkSumDocumentCount(Facet $facet, int $expectedCount): void
    {
        $sum = 0;
        $kvArray = $facet->asKeyValueArray();

        /** @var \Suilven\FreeTextSearch\Container\FacetCount $key */
        foreach (\array_keys($kvArray) as $key) {
            $sum += $kvArray[$key];
        }

        $this->assertEquals($expectedCount, $sum);
    }
}
