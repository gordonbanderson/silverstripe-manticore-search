<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Population\Tests;

use Faker\Factory;
use SilverStripe\Dev\SapphireTest;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Indexer;

class PopulationPrepTest extends SapphireTest
{
    protected static $fixture_file = 'tests/fixtures.yml';

    public function setUp(): void
    {
        parent::setUp();

        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();
        $indexer = new Indexer($indexesObj);
        $indexer->reconfigureIndexes();
    }


    public function assfsfdtestStub(): void
    {
        $this->assertTrue();
    }


    public function skiptestPopulate(): void
    {
        $fixtures = "SilverStripe\Security\Member:\n";
        $faker = Factory::create();
        for ($i=1; $i<=10; $i++) {
            $member = '  member_'.$i . ":\n";
            $fixtures .= $member;
            //FirstName
            //Surname
            //Email
            $firstname = $faker->firstName;
            $surname = $faker->lastName;
            $domain = $faker->freeEmailDomain;
            $email = \strtolower($firstname . '.' . $surname). '@' . $domain;
            $fixtures .= "    FirstName: " . $firstname . "\n";
            $fixtures .= "    Surname: " . $surname . "\n";
            $fixtures .= "    Email: " . $email . "\n";
        }


        /*
         *  * @property string $URLSegment
 * @property string $Title
 * @property string $MenuTitle
 * @property string $Content HTML content of the page.
 * @property string $MetaDescription
 * @property string $ExtraMeta
 * @property string $ShowInMenus
 * @property string $ShowInSearch
 * @property string $Sort Integer value denoting the sort order.
 * @property string $ReportClass
 * @property bool $HasBrokenFile True if this page has a broken file shortcode
 * @property bool $HasBrokenLink True if this page has a broken page shortcode
 *
 * @method ManyManyList ViewerGroups() List of groups that can view this object.
 * @method ManyManyList EditorGroups() List of groups that can edit this object.
 * @method SiteTree Parent()
 * @method HasManyList|SiteTreeLink[] BackLinks() List of SiteTreeLink objects attached to this page
 *

         */

        $fixtures .= "\n\nSilverStripe\CMS\Model:\n";
        // https://dev.gutenberg.org/files/28657/28657-8.txt
        $book = \file_get_contents('./dict.txt');

        $words = \explode(\PHP_EOL, $book);
        for ($i=1; $i<=50; $i++) {
            $sitetree = '  sitetree_'.$i . ":\n";
            $fixtures .= $sitetree;

            $fixtures .= "    Title: " . $this->getRandomTitle($words) . "\n";

            $paragraph = $this->getRandomParagraph($words, 10);
            $fixtures .= "    Content: " . $paragraph . "\n";
        }

           echo $fixtures;
    }


    private function getRandomWord($words, $precaps = false)
    {
        $randIndex = \array_rand($words);
        if ($precaps) {
            return \ucwords($words[$randIndex]);
        }

        return $words[$randIndex];
    }


    private function getRandomSentence($words, $maxWords = 20)
    {
        $nWords = \rand(4, $maxWords);
        $sentence = [];
        for ($i=0; $i<=$nWords; $i++) {
            $sentence[] = $this->getRandomWord($words, $i===0);
        }

        return \implode(' ', $sentence);
    }


    private function getRandomTitle($words)
    {
        return \ucwords($this->getRandomSentence($words, 10));
    }


    private function getRandomParagraph($words, $maxSentences = 10)
    {
        $nSentences = \rand(1, $maxSentences);
        $sentences = [];
        for ($i=0; $i<$nSentences; $i++) {
            $sentences[] = $this->getRandomSentence($words, $i===0);
        }

        return \implode('.  ', $sentences) . '.  ';
    }
}
