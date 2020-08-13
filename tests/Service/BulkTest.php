<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\IndexingHelper;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;

class BulkTest extends SapphireTest
{
    protected static $fixture_file = 'tests/fixtures/sitetree.yml';

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
        // index all SiteTree objects
        $helper = new IndexingHelper();
        $helper->bulkIndex('SilverStripe\CMS\Model\SiteTree');


        // search against them
        $searcher = new Searcher();
        $searcher->setIndex('sitetree');
        $results = $searcher->search('sodium');

        // assert number of results
        $this->assertEquals(4, \sizeof($results));

        // assert IDs and that sodium is in the result set somewhere
        $this->assertEquals(34, $results[0]->ID);

        // @todo Why is ->title returning #34 here?
        $this->assertContains('Sodium', $results[0]->menutitle);

        $this->assertEquals(17, $results[1]->ID);
        $this->assertContains('sodium', $results[1]->content);

        $this->assertEquals(20, $results[2]->ID);
        $this->assertContains('sodium', $results[2]->content);

        $this->assertEquals(22, $results[3]->ID);
        $this->assertContains('sodium', $results[3]->content);


        // now do a suggest
        /** @var \Suilven\ManticoreSearch\Service\Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('sitetree');
        $suggestions = $suggester->suggest('chessbored');
        $this->assertEquals(['chessboard'], $suggestions);
    }
}
