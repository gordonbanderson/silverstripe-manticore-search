<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\IndexingHelper;
use Suilven\ManticoreSearch\Service\Indexer;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;

class BulkTest extends SapphireTest
{
    protected static $fixture_file = 'tests/fixtures.yml';



    public function setUp()
    {
        parent::setUp();
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();
        $indexer = new Indexer($indexesObj);
        $indexer->reconfigureIndexes();
    }

    public function test_index_all_documents_sitetree()
    {
        $helper = new IndexingHelper();
        $doc = SiteTree::get()->limit(1)->first();

        $helper->bulkIndex($doc->ClassName);

        $searcher = new Searcher();
        $searcher->setIndex('sitetree');
        $results = $searcher->search('sodium');



        error_log('RESULTS');
        error_log(print_r($results, 1));
    }
}
