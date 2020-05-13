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
use Suilven\FreeTextSearch\Indexes;

class Searcher
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


    public function oldSearch($q)
    {
        $startMs = round(microtime(true) * 1000);
        $connection = $this->client->getConnection();

        // @todo make fields configurable?
        $query = SphinxQL::create($connection)->select('id')
            ->from([$this->indexName .'_index', $this->indexName  . '_rt']);

        if (!empty($q)) {
            $query->match('?', $q);
        }


        // string int fixes needed here
        foreach ($this->filters as $key => $value) {
            if ($key !== 'q') {
                if (ctype_digit($value)) {
                    if (is_int($value + 0)) {
                        $value = (int) $value;
                    } elseif (is_float($value + 0)) {
                        $value = (float) $value;
                    }
                }

                $query->where($key, $value);
            }
        }

        foreach ($this->facettedTokens as $tokenToFacet) {
            $query->facet(Facet::create($connection)
                ->facet(array($tokenToFacet)));
                // @todo->orderBy('iso', 'ASC')
        }


        $query->limit(($this->page-1) * $this->pageSize, $this->pageSize);


        /** @var array $result */
        $result = $query->executeBatch()
            ->getStored();

        /** @var ResultSet $resultSet */
        $resultSet = $result[0];

        $ids = $resultSet->fetchAllAssoc();

        $ctr = 1;
        $facets = [];
        foreach ($this->facettedTokens as $token) {
            $resultSet = $result[$ctr];
            $rawFacets = $resultSet->fetchAllAssoc();
            $tokenFacets = [];
            foreach ($rawFacets as $singleFacet) {
                if (isset($singleFacet[$token])) {
                    $value = $singleFacet[$token];
                    $count = $singleFacet['count(*)'];

                    // do this way to maintain order from Sphinx
                    $nextFacet = ['Value' => $value, 'Count' => $count, 'Name' => $token,
                        'ExtraParam' => "$token=$value"];
                    $filterForFacet = $this->filters;

                    if (isset($this->filters[$token])) {
                        $nextFacet['Selected'] = true;
                        unset($filterForFacet[$token]);
                    } else {
                        // additional value to the URL, unselected facet
                        $filterForFacet[$token] = $value;
                    }

                    // @todo - escaping?
                    $urlParams = '';
                    foreach ($filterForFacet as $n => $v) {
                        //if (!isset($this->filters[$token])) {
                            $urlParams .= "{$n}={$v}&";
                       // }
                    }

                    $nextFacet['Params'] = substr($urlParams, 0, -1);

                    $tokenFacets[] = $nextFacet;
                }
            }
            $ctr++;

            // @todo huyman readable title
            $facets[] = ['Name' => $token, 'Facets' => new ArrayList($tokenFacets)];
        }

        $metaQuery = SphinxQL::create($connection)->query('SHOW META;');
        $metaData = $metaQuery->execute();

        $searchInfo = [];
        foreach ($metaData->getStored() as $info) {
            $varname = $info['Variable_name'];
            $value = $info['Value'];
            $searchInfo[$varname] = $value;
        }

        $formattedResults = new ArrayList();

        foreach ($ids as $assoc) {
            // @todo use array merge to minimize db queries
            // @todo need to get this from the index definition

            $indexesService = new Indexes();
            $indexes = $indexesService->getIndexes();

            $clazz = '';

            // @todo fix this, return an associative array from the above
            foreach ($indexes as $indexObj) {
                $name = $indexObj->getName();
                if ($name == $this->indexName) {
                    $clazz = $indexObj->getClass();
                    break;
                }
            }

            $dataobject = DataObject::get_by_id($clazz, $assoc['id']);

            // Get highlight snippets, but only if a query parameter was passed in
            if (!empty($q)) {
                $snippets = Helper::create($connection)->callSnippets(
                    // @todo get from index, need all text fields
                    $dataobject->Title . ' ' . $dataobject->Content,
                    //@todo hardwired
                    'sitetree_index',
                    $q,
                    [
                        'around' => 10,
                        'limit' => 200,
                        'before_match' => '<b>',
                        'after_match' => '</b>',
                        'chunk_separator' => '...',
                        'html_strip_mode' => 'strip',
                    ]
                )->execute()->getStored();
                $dataobject->Snippets = $snippets[0]['snippet'];
            }




            $formattedResult = new ArrayData([
                'Record' => $dataobject
            ]);

            $formattedResults->push($formattedResult);
        }

        $elapsed = round(microtime(true) * 1000) - $startMs;

        $pagination = new PaginatedList($formattedResults);
        $pagination->setCurrentPage($this->page);
        $pagination->setPageLength($this->pageSize);
        $pagination->setTotalItems($searchInfo['total_found']);



        return [
            'Records' => $formattedResults,
            'PageSize' => $this->pageSize,
            'Page' => $this->page,
            'TotalPages' => 1+round($searchInfo['total_found'] / $this->pageSize),
            'ResultsFound' => $searchInfo['total_found'],
            'Time' => $elapsed/1000.0,
            'Pagination' => $pagination,
            'AllFacets' => empty($facets) ? false : new ArrayList($facets)
        ];
    }
}
