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
use Suilven\FreeTextSearch\Helper\BulkIndexingHelper;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;
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
        $helper->bulkIndex('sitetree');

        // search against them
        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');
        $results = $searcher->search('sodium');

        // assert number of results
        $this->assertEquals(4, $results->getNumberOfResults());
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
}
