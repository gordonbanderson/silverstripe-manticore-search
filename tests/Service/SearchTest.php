<?php
namespace Suilven\ManticoreSearch\Tests\Service;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\IndexingHelper;
use Suilven\ManticoreSearch\Service\Indexer;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;
use Suilven\ManticoreSearch\Tests\Models\FlickrAuthor;
use Suilven\ManticoreSearch\Tests\Models\FlickrPhoto;
use Suilven\ManticoreSearch\Tests\Models\FlickrSet;
use Suilven\ManticoreSearch\Tests\Models\FlickrTag;

class SearchTest extends SapphireTest
{
    protected static $extra_dataobjects = [
        FlickrPhoto::class,
        FlickrTag::class,
        FlickrSet::class,
        FlickrAuthor::class
    ];

    /**
     * @var int
     */
    private static $pageID;

    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $flickrPhoto = new FlickrPhoto();
        $flickrPhoto->Title = 'test';
        $flickrPhoto->Description = 'test';
        $flickrPhoto->write();


        $page = new \Page();
        $page->Title = 'Hometown Sandlot Knitted Saddens Days';
        $page->Content = 'Webmaster fakes disconnections overdose.  Windowing preschooler malfunctions dolts statutes.';
        $page->write();
        self::$pageID = $page->ID;
    }


    public function setUp()
    {
        parent::setUp();
        /** @var Indexes $indexesService */
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();
        $indexer = new Indexer($indexesObj);
        $indexer->reconfigureIndexes();
    }


    /**
     * This test will hopefully deal with the hardwired sitetree index name
     *
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function testIndexOneMemberAndSuggest()
    {
        $helper = new IndexingHelper();
        $member = new Member;
        $member->FirstName = 'Gordon';
        $member->Surname = 'Anderson';

        // a fake address!
        $member->Email = 'gordon.b.anderson@mailinator.com';

        $member->write();
        $helper->indexObject($member);

        /** @var Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('members');
        $suggestions = $suggester->suggest('Andersin');
        $this->assertEquals(['anderson'], $suggestions);
    }

    public function testIndexOneDocumentAndSuggest()
    {
        $helper = new IndexingHelper();
        $doc = DataObject::get_by_id(\Page::class, self::$pageID);
        $helper->indexObject($doc);

        // search for webmister, a deliberate error (should be webmaster)
        /** @var Suggester $suggester */
        $suggester = new Suggester();
        $suggester->setIndex('sitetree');
        $suggestions = $suggester->suggest('webmister');
        $this->assertEquals(['webmaster'], $suggestions);
    }


    public function testIndexOneDocumentAndSearch()
    {
        $helper = new IndexingHelper();
        $doc = DataObject::get_by_id(\Page::class, self::$pageID);
        $helper->indexObject($doc);

        $searcher = new Searcher();
        $searcher->setIndex('sitetree');
        $result = $searcher->search('Webmaster disconnections');
        $arrayResult = $result->toArray();
        $this->assertEquals(1, sizeof($arrayResult));
        $this->assertContains('Webmaster fakes disconnections overdose', $arrayResult[0]->content);
        $this->assertEquals(self::$pageID, $arrayResult[0]->ID);
    }
}
