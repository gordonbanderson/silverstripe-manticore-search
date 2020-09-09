<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DB;
use Suilven\FreeTextSearch\Helper\BulkIndexingHelper;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;
use Suilven\ManticoreSearch\Service\BulkIndexer;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;

class BulkTest extends SapphireTest implements TestOnly
{
    protected static $fixture_file = ['tests/fixtures/sitetree.yml', 'tests/fixtures/flickrphotos.yml'];

    protected static $extra_dataobjects = [
        'Suilven\ManticoreSearch\Tests\Models\FlickrPhoto',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $indexesService = new Indexes();
        $indexesArray = $indexesService->getIndexes();
        $helper = new ReconfigureIndexesHelper();
        $helper->reconfigureIndexes($indexesArray);
    }


    public function testIndexAllDocumentsSiteTree(): void
    {
        $helper = new BulkIndexingHelper();
        $nDocs = $helper->bulkIndex('sitetree');
        $this->assertEquals(50, $nDocs);

        // search against them
        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $results = $searcher->search('sodium');

        // assert number of results
        $this->assertEquals(4, $results->getTotaNumberOfResults());
        $recordsArray = $results->getRecords()->toArray();

        // assert IDs and that sodium is in the result set somewhere
        $this->assertEquals(34, $recordsArray[0]->ID);

        // @todo Why is ->title returning #34 here?
        $this->assertContains('Sodium', $recordsArray[0]->MenuTitle);

        $this->assertEquals(17, $recordsArray[1]->ID);
        $this->assertContains('sodium', $recordsArray[1]->Content);

        $this->assertEquals(20, $recordsArray[2]->ID);
        $this->assertContains('sodium', $recordsArray[2]->Content);

        $this->assertEquals(22, $recordsArray[3]->ID);
        $this->assertContains('sodium', $recordsArray[3]->Content);


        // now do a suggest
        /** @var \Suilven\ManticoreSearch\Service\Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('sitetree');
        $result = $suggester->suggest('chessbored');
        $this->assertEquals(['chessboard'], $result->getResults());
    }


    public function testAlreadyIndexed(): void
    {
        // mark all SiteTree documents as clean, ie indexed
        DB::query("UPDATE \"SiteTree_Live\" SET \"IsDirtyFreeTextSearch\" = 0");
        DB::query("UPDATE \"SiteTree\" SET \"IsDirtyFreeTextSearch\" = 0");

        // assert that no new documents are indexed
        $helper = new BulkIndexingHelper();
        $nDocs = $helper->bulkIndex('sitetree', true);
        $this->assertEquals(0, $nDocs);
    }


    public function testAddNoDocumentsInBulk(): void
    {
        $indexer = new BulkIndexer();
        $nDocs = $indexer->indexDataObjects();
        $this->assertEquals(0, $nDocs);
    }


    public function testAddDocumentWithNullField(): void
    {
        $page = new \Page();
        $page->Title = 'Test Page';
        $page->Content = null;
        $page->write();
        $helper = new BulkIndexingHelper();
        $nDocs = $helper->bulkIndex('sitetree', true);

        $this->assertEquals(51, $nDocs);

        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $results = $searcher->search('Test Page');

        // assert number of results
        $this->assertEquals(1, $results->getTotaNumberOfResults());
        $recordsArray = $results->getRecords()->toArray();
        $this->assertEquals('', $recordsArray[0]->Content);
    }
}
