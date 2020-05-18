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
use Suilven\FreeTextSearch\Factory\SearcherInterface;
use Suilven\FreeTextSearch\Indexes;

class Searcher implements SearcherInterface
{

    private $client;

    private $pageSize = 10;

    private $page = 1;

    private $indexName = 'sitetree';

    /**
     * @var array tokens that are facetted, e.g. Aperture, BlogID
     */
    private $facettedTokens = [];

    /**
     * @var array tokens for has many
     */
    private $hasManyTokens = [];

    /**
     * @var array associative array of filters against tokens
     */
    private $filters = [];

    /**
     * @param array $filters
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;
    }

    /**
     * @param int $pageSize
     */
    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;
    }

    /**
     * @param string $indexName
     */
    public function setIndex($indexName)
    {
        $this->indexName = $indexName;
    }


    /**
     * @param array $facettedTokens
     */
    public function setFacettedTokens($facettedTokens)
    {
        $this->facettedTokens = $facettedTokens;
    }


    /**
     * @param array $hasManyTokens
     */
    public function setHasManyTokens($hasManyTokens)
    {
        $this->facettedTokens = $hasManyTokens;
    }


    /**
     * @param int $page
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    public function __construct()
    {
        $this->client = new Client();
    }


    public function search($q)
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
