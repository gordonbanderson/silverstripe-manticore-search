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
        $startTime = \microtime(true);
        $client = new Client();
        $manticoreClient = $client->getConnection();

        $searcher = new Search($manticoreClient);
        $searcher->setIndex($this->indexName);

        $searcher->limit($this->pageSize);
        $offset=$this->pageSize * ($this->page-1);
        $searcher->offset($offset);

        $indexes = new Indexes();
        $index = $indexes->getIndex($this->indexName);

        $searcher->highlight(
            [],
            ['pre_tags' => '<b>', 'post_tags'=>'</b>']
        );

        $manticoreResult = $searcher->search($q)->get();

        $allFields = \array_merge(
            $index->getFields(),
            $index->getTokens(),
            //$index->getHasManyFields(),
            $index->getHasOneFields(),
            $index->getStoredFields()
        );


        $hasManyFields = $index->getHasManyFields();
        foreach(array_keys($hasManyFields) as $key) {
            $allFields[] = $key;
        }

        $ssResult = new ArrayList();
        while ($manticoreResult->valid()) {
            $hit = $manticoreResult->current();
            $source = $hit->getData();
            //print_r($source);
            $ssDataObject = new DataObject();

            $keys = \array_keys($source);

            foreach ($keys as $key) {
                $keyname = $key;
                foreach ($allFields as $field) {
                    if (\strtolower($field) === $key) {
                        $keyname = $field;

                        break;
                    }
                }

                // @todo This is a hack as $Title is rendering the ID in the template
                if ($keyname === 'Title') {
                    $keyname = 'ResultTitle';
                } elseif ($keyname === 'link') {
                    $keyname = 'Link';
                };

                /** @phpstan-ignore-next-line */
                $ssDataObject->$keyname = $source[$key];
            }


            // manticore lowercases fields, so as above normalize them back to the SS fieldnames
            $highlights = $hit->getHighlight();
            $highlightsSS = [];

            $fieldsToHighlight = $index->getHighlightedFields();

            $keys = \array_keys($highlights);
            foreach ($keys as $key) {
                if (!isset($highlights[$key]) || !\in_array($key, $fieldsToHighlight, true)) {
                    continue;
                }
                $keyname = $key;
                foreach ($allFields as $field) {
                    if (\strtolower($field) === $key) {
                        $keyname = $field;

                        continue;
                    }
                }

                if ($key === 'link') {
                    $keyname = 'Link';
                }

                $highlightsSS[$keyname] = $highlights[$key];
            }

            /** @phpstan-ignore-next-line */
            $ssDataObject->Highlights = $highlightsSS;

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
        $searchResults->setTotalNumberOfResults($manticoreResult->getTotal());

        $endTime = \microtime(true);
        $delta = $endTime - $startTime;
        $delta = \round(1000*$delta)/1000;
        $searchResults->setTime($delta);

        return $searchResults;
    }
}
