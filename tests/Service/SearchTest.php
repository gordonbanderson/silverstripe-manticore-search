<?php declare(strict_types = 1);

namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Suilven\FreeTextSearch\Factory\IndexerFactory;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;
use Suilven\ManticoreSearch\Tests\Models\FlickrPhoto;

class SearchTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        FlickrPhoto::class,
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
    }


    /**
     * This test will hopefully deal with the hardwired sitetree index name
     *
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function testIndexOneMemberAndSuggest(): void
    {
        $member = new Member();
        $member->FirstName = 'Gordon';
        $member->Surname = 'Anderson';

        // a fake address!
        $member->Email = 'gordon.b.anderson@mailinator.com';

        $member->write();

        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName('members');
        $indexer->index($member);

        /** @var \Suilven\ManticoreSearch\Service\Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('members');
        $result = $suggester->suggest('Andersin');
        $this->assertEquals(['anderson'], $result->getResults());
    }


    public function testIndexOneDocumentAndSuggest(): void
    {
        $doc = DataObject::get_by_id(\Page::class, self::$pageID);

        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName('sitetree');
        $indexer->index($doc);

        // search for webmister, a deliberate error (should be webmaster)
        /** @var \Suilven\ManticoreSearch\Service\Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('sitetree');
        $result = $suggester->suggest('webmister');
        $this->assertEquals(['webmaster'], $result->getResults());
    }


    public function testIndexOneDocumentAndSearch(): void
    {

        $doc = DataObject::get_by_id(\Page::class, self::$pageID);
        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName('sitetree');
        $indexer->index($doc);

        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');

        /** @var \Suilven\FreeTextSearch\Container\SearchResults $result */
        $result = $searcher->search('Webmaster disconnections');

        $this->assertInstanceOf('Suilven\FreeTextSearch\Container\SearchResults', $result);
        $this->assertEquals(1, $result->getTotaNumberOfResults());
        $records = $result->getRecords();
        $first = $records->first();
        $this->assertContains('Webmaster fakes disconnections overdose', $first->Content);
        $this->assertEquals(self::$pageID, $first->ID);
    }


    public function testIndexOneDocumentAndNullSearch(): void
    {
        $doc = DataObject::get_by_id(\Page::class, self::$pageID);
        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName('sitetree');
        $indexer->index($doc);

        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');

        /** @var \Suilven\FreeTextSearch\Container\SearchResults $result */
        $result = $searcher->search(null);
        $records = $result->getRecords();
        $first = $records->first();

        // null search returns only document in the index
        $this->assertContains('Webmaster fakes disconnections overdose', $first->Content);
    }


    public function testHighlights(): void
    {
        $doc = DataObject::get_by_id(\Page::class, self::$pageID);
        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName('sitetree');
        $indexer->index($doc);

        $searcher = new Searcher();
        $searcher->setIndexName('sitetree');

        /** @var \Suilven\FreeTextSearch\Container\SearchResults $result */
        $result = $searcher->search('knitted');
        $this->assertEquals(1, $result->getTotaNumberOfResults());

        $records = $result->getRecords();
        $first = $records->first();


        // null search returns only document in the index
        $this->assertContains('Webmaster fakes disconnections overdose', $first->Content);

        $this->assertEquals([
            'Title' => ['Hometown Sandlot <b>Knitted</b> Saddens Days'],
            'Content' => ['Webmaster fakes disconnections overdose. Windowing preschooler malfunctions dolts' .
                ' statutes.'],
        ], $first->Highlights);
    }


    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        $flickrPhoto = new FlickrPhoto();
        $flickrPhoto->Title = 'This is a photo';
        $flickrPhoto->Description = 'taken by a camera';
        $flickrPhoto->write();


        $page = new \Page();
        $page->Title = 'Hometown Sandlot Knitted Saddens Days';
        $page->Content = 'Webmaster fakes disconnections overdose.  Windowing preschooler malfunctions dolts statutes.';
        $page->write();
        self::$pageID = $page->ID;
    }
}
