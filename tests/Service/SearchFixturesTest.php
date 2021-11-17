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

        \sort($ids);
        $this->assertEquals([21, 36, 40, 45, 47, 49], $ids);
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
        $titles = [];
        \var_export($hits);
        foreach ($hits as $hit) {
            $titles[] = $hit->ResultTitle;
        }

        \var_export($titles);
        $this->assertEquals(
            [
                'Timbres Mussy Crests Dubs Essence Wrinkled Shuttlecock Rowdies Tics Annoy Governable',
                'Putted Thrifts Trifectas Heartier Skimped Charged Hurdle Unrolled',
                'Proximity Consonance Sulphide Addends Objectors Stringently Fouled Becalms Raconteurs Gouger Unacknowledged',
                'Pacifier Loon Profiled Entanglement Elfin Menageries Egregious Stoney',
                'Asserts Ratcheted Trenches Ambiances Sackcloth Bluest Lounging',
            ],
            $titles
        );
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
        $titles = [];
        foreach ($hits as $hit) {
            $titles[] = $hit->ResultTitle;
        }

        $this->assertEquals(
            array (
                0 => 'The Story Is Colour Aqua',
                1 => 'The Late Kept Hangin[verb_ing] About Just At The Inn Offer, Summering Round The Compare Like A So Marking For A Let',
                2 => 'Were It Not For The Breads, The Home Out Would Not Be Land',
                3 => 'Shut In, However, By Story, It Was Slow To Floor His Each, Which We Had Observed With The Subject Trouble',
                4 => 'Shut In, However, By God, It Was Orange To Care His Hour, Which We Had Observed With The Go Tram',
                5 => 'This Is A Random String From 1 To 4 [one',
                6 => 'Colouring At Night Is More Fun Than Squareing During The Day',
                7 => 'Shut In, However, By Aunt, It Was Just To Hear His Bridge, Which We Had Observed With The Glad North',
                8 => 'He Was Past Of Eat That Support About The Condition Of Bitch, And Scoffed At The Much Place Of Its Blowing Falls Who Were Renting Us',
                9 => 'Among These Were A Couple Of Sizes, A Neighbouring Ball I Employed Usually, A Heaven Shorting A Wine, Gregg The Butcher And His Little Boy, And Two Or Three Loafers And Golf Caddies Who Were Accustomed To Hang About The Railway Station',
                10 => 'The Still Kept Hangin[verb_ing] About Just Home The Inn Hard, Partying Round The Not Like A Good Discovering For A Ice',
                11 => 'Wintering At Night Is More Fun Than Bettering During The Day',
                12 => 'The Came Kept Hangin[verb_ing] About Just In The Inn On, Laughing Round The Dress Like A Group Branching For A Bleed',
                13 => 'For A Laugh Where Cooks Are Scared Of Branchs Why Don’t They Have Good Clock',
                14 => 'For A Story Where Ones Are Scared Of Sends Why Don’t They Have Juice Rubber',
            ),
            $titles
        );


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
        $titles = [];
        foreach ($hits as $hit) {
            $titles[] = $hit->ResultTitle;
        }

        $this->assertEquals(
            array (
                'The Came Kept Hangin[verb_ing] About Just In The Inn On, Laughing Round The Dress Like A Group Branching For A Bleed',
                'Shut In, However, By Aunt, It Was Just To Hear His Bridge, Which We Had Observed With The Glad North',
                'He Is Now Much Recovered From His Welcome And Is Continually On The Not, Apparently Leting For The Knock That Preceded His Own',
                'It Was Present In The Hello, Your Ladder Was Third',
                'The At Was Feeding Shortly',
                'How Slowly The Private Passes Here, Encompassed As I Am By Plastic And Leave',
                'Mooning At Night Is More Fun Than Liveing During The Day',
                'It Was Strong In The Window, Their Speak Was Plane',
                'Tom\'s Younger Brother (or Rather Half-brother) Sid Was Already Through With His Part Of The Work (picking Up Chips), For He Was A Quiet Boy, And Had No Adventurous, Trouble-some Ways',
                'He Was Base Of Early That King About The Condition Of Raise, And Scoffed At The Better Push Of Its Beaning Storms Who Were Reminding Us',
                'He Is Now Much Recovered From His Science And Is Continually On The Come, Apparently Nameing For The Possible That Preceded His Own',
                'Shut In, However, By Comb, It Was Choice To Piece His Bit, Which We Had Observed With The Cut Rise',
                'How Slowly The That Passes Here, Encompassed As I Am By Sword And Gold',
            )
            , $titles);

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
