<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 25/3/2561
 * Time: 1:35 น.
 */

namespace Suilven\ManticoreSearch\Service;

use Manticoresearch\Search;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use Suilven\FreeTextSearch\Container\SearchResults;
use Suilven\FreeTextSearch\Indexes;

class Searcher extends \Suilven\FreeTextSearch\Base\Searcher implements \Suilven\FreeTextSearch\Interfaces\Searcher
{
    /** @var \Suilven\ManticoreSearch\Service\Client */
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }


    public function search(?string $q): SearchResults
    {
        $client = new Client();
        $manticoreClient = $client->getConnection();

        $searcher = new Search($manticoreClient);
        $searcher->setIndex($this->indexName);
        $manticoreResult = $searcher->search($q)->highlight(
            [],
            ['pre_tags' => '<b>', 'post_tags'=>'</b>']
        )->get();

        $indexes = new Indexes();
        $index = $indexes->getIndex($this->indexName);
        $fields = $index->getFields();

        $ssResult = new ArrayList();
        while ($manticoreResult->valid()) {
            $hit = $manticoreResult->current();
            $source = $hit->getData();
            $ssDataObject = new DataObject();

            // @todo map back likes of title to Title
            $keys = \array_keys($source);
            foreach ($keys as $key) {
                $keyname = $key;
                foreach ($fields as $field) {
                    if (\strtolower($field) === $key) {
                        $keyname = $field;

                        break;
                    }
                }

                // @todo This is a hack as $Title is rendering the ID in the template
                if ($keyname === 'Title') {
                    $keyname = 'ResultTitle';
                }

                /** @phpstan-ignore-next-line */
                $ssDataObject->Highlights = $hit->getHighlight();

                /** @phpstan-ignore-next-line */
                $ssDataObject->$keyname = $source[$key];
            }

            $ssDataObject->ID = $hit->getId();
            $ssResult->push($ssDataObject);

            $manticoreResult->next();
        }

        // we now need to standardize the output returned

        $searchResults = new SearchResults();
        $searchResults->setRecords($ssResult);
        $searchResults->setPage($this->page);
        $searchResults->setPageSize($this->pageSize);
        $searchResults->setQuery($q);

        return $searchResults;
    }
}
