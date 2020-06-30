<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 25/3/2561
 * Time: 1:35 น.
 */

namespace Suilven\ManticoreSearch\Service;

use Foolz\SphinxQL\Drivers\Pdo\ResultSet;
use Foolz\SphinxQL\Facet;
use Foolz\SphinxQL\Helper;
use Foolz\SphinxQL\SphinxQL;
use Manticoresearch\Search;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\View\ArrayData;
use Suilven\FreeTextSearch\Base\SearcherBase;
use Suilven\FreeTextSearch\Container\SearchResults;
use Suilven\FreeTextSearch\Indexes;

class Searcher extends SearcherBase implements \Suilven\FreeTextSearch\Interfaces\Searcher
{

    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }


    public function search($q): SearchResults
    {
        $search = [
            'body' => [
                'index' => $this->indexName,
                'query' => [
                    'match' => ['*' => $q],
                ],
            ]
        ];

        $client = new Client();
        $manticoreClient = $client->getConnection();

        $searcher = new Search($manticoreClient);
        $searcher->setIndex($this->indexName);
        $manticoreResult = $searcher->search($q)->get();

        $ssResult = new ArrayList();
        while ($manticoreResult->valid()) {
            $hit = $manticoreResult->current();
            $source = $hit->getData();
            $ssDataObject = new DataObject();

            // @todo map back likes of title to Title
            $keys = array_keys($source);
            foreach ($keys as $key) {
                $ssDataObject->$key = $source[$key];
            }

            $ssDataObject->ID = $hit->getId();
            $ssResult->push($ssDataObject);

            $manticoreResult->next();
        }

        // we now need to standardize the output returned

        return $ssResult;
    }
}
