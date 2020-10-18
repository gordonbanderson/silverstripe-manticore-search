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
use Suilven\FreeTextSearch\Container\Facet;
use Suilven\FreeTextSearch\Container\SearchResults;
use Suilven\FreeTextSearch\Helper\SearchHelper;
use Suilven\FreeTextSearch\Indexes;
use Suilven\FreeTextSearch\Types\SearchParamTypes;

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
        $q = \is_null($q)
            ? ''
            : $q;
        if ($this->searchType === SearchParamTypes::OR) {
            $q = $this->makeQueryOr($q);
        }
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

        // @todo Deal with subsequent params
        foreach ($this->facettedTokens as $facetName) {
            // manticore errors out with no error message if the facet name is not lowercase.  The second param is an
            // alias, use the correctly capitalized version of the fact
            $searcher->facet(\strtolower($facetName), $facetName);
        }

        $manticoreResult = $searcher->search($q)->get();
        $allFields = $this->getAllFields($index);

        $ssResult = new ArrayList();
        while ($manticoreResult->valid()) {
            $hit = $manticoreResult->current();
            $source = $hit->getData();
            $ssDataObject = new DataObject();

            $this->populateSearchResult($ssDataObject, $allFields, $source);

            // manticore lowercases fields, so as above normalize them back to the SS fieldnames
            $highlights = $hit->getHighlight();
            $fieldsToHighlight = $index->getHighlightedFields();
            $this->addHighlights($ssDataObject, $allFields, $highlights, $fieldsToHighlight);

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

        // create facet result objects
        $manticoreFacets = $manticoreResult->getFacets();

        \error_log(\print_r($manticoreFacets, true));

        if (!\is_null($manticoreFacets)) {
            $facetTitles = \array_keys($manticoreFacets);
            foreach ($facetTitles as $facetTitle) {
                $facet = new Facet($facetTitle);
                foreach ($manticoreFacets[$facetTitle]['buckets'] as $count) {
                    $facet->addFacetCount($count['key'], $count['doc_count']);
                }
                $searchResults->addFacet($facet);
            }
        }

        $endTime = \microtime(true);
        $delta = $endTime - $startTime;
        $delta = \round(1000*$delta)/1000;
        $searchResults->setTime($delta);

        return $searchResults;
    }


    /** @return array<string> */
    public function getAllFields(\Suilven\FreeTextSearch\Index $index): array
    {
        $allFields = \array_merge(
            $index->getFields(),
            $index->getTokens(),
            //$index->getHasManyFields(),
            $index->getHasOneFields(),
            $index->getStoredFields()
        );

        $hasManyFields = $index->getHasManyFields();
        foreach (\array_keys($hasManyFields) as $key) {
            $allFields[] = $key;
        }

        return $allFields;
    }


    public function refactorKeyName(string $keyname): string
    {
        // @todo This is a hack as $Title is rendering the ID in the template
        if ($keyname === 'Title') {
            $keyname = 'ResultTitle';
        } elseif ($keyname === 'link') {
            $keyname = 'Link';
        };

        return $keyname;
    }


    /** @param array<string> $allFields */
    public function matchKey(string $key, array $allFields): string
    {
        $keyname = $key;
        foreach ($allFields as $field) {
            if (\strtolower($field) === $key) {
                $keyname = $field;

                break;
            }
        }

        return $keyname;
    }


    /** @param \SilverStripe\ORM\DataObject $dataObject a dataObject relevant to the index */
    public function searchForSimilar(DataObject $dataObject): SearchResults
    {
        $helper = new SearchHelper();
        $indexedTextFields = $helper->getTextFieldPayload($dataObject);
        $textForCurrentIndex = $indexedTextFields[$this->indexName];

        // @todo Search by multiple fields?
        $amalgamatedText = '';
        foreach (\array_keys($textForCurrentIndex) as $fieldName) {
            $amalgamatedText .= $textForCurrentIndex[$fieldName] . ' ';
        }

        $this->searchType = SearchParamTypes::OR;
        $text = $this->getLeastCommonTerms($amalgamatedText, 10);

        return $this->search($text);
    }


    /**
     * Find terms suitable for similarity searching
     *
     * @todo Rename this method, or separate into a helper?
     * @param string $text text of a document being searched for
     */
    private function getLeastCommonTerms(string $text, int $number = 20): string
    {
        $client = new Client();
        $connection = $client->getConnection();
        $params = [
            'index' => $this->indexName,
            'body' => [
                'query'=>$text,
                'options' => [
                    'stats' =>1,
                    'fold_lemmas' => 1,
                ],
            ],
        ];

        $keywords = $connection->keywords($params);

        /* @phpstan-ignore-next-line */
        \usort(
            $keywords,
            static function ($a, $b): void {

                ($a["docs"] <= $b["docs"])
                    ? -1
                    : +1;
            }
        );

        $wordInstances = [];
        $wordNDocs = [];
        foreach ($keywords as $entry) {
            // @todo this or normalized?
            $word = $entry['tokenized'];

            // if a word is unique to the source document, it is useless for finding other similar documents
            if ($entry['docs'] > 1) {
                if (!isset($wordInstances[$word])) {
                    $wordInstances[$word] = 0;
                }
                $wordInstances[$word] += 1;
            }

            $wordNDocs[$word] = $entry['docs'];
        }

        $toGlue = \array_keys($wordInstances);
        $toGlue = \array_slice($toGlue, 0, $number);
        $text = \implode(' ', $toGlue);

        return $text;
    }


    /**
     * Make a query OR instead of the default AND
     *
     * @param string $q the search query
     * @return string same query for with the terms separated by a | character,to form an OR query
     */
    private function makeQueryOr(string $q): string
    {
        $q = \trim($q);
        /** @var array<int, string> $splits */
        $splits = \preg_split('/\s+/', $q);

        return \implode('|', $splits);
    }


    /**
     * @param array<string> $allFields
     * @param array<string, string|int|float|bool> $source
     */
    private function populateSearchResult(DataObject &$ssDataObject, array $allFields, array $source): void
    {
        $keys = \array_keys($source);
        foreach ($keys as $key) {
            /** @var string $keyname */
            $keyname = $this->matchKey($key, $allFields);
            $keyname = $this->refactorKeyName($keyname);

            /** @phpstan-ignore-next-line */
            $ssDataObject->$keyname = $source[$key];
        }
    }


    /**
     * @param array<string> $allFields
     * @param array<array<string>> $highlights
     * @param array<string> $fieldsToHighlight
     */
    private function addHighlights(
        DataObject &$ssDataObject,
        array $allFields,
        array $highlights,
        array $fieldsToHighlight
    ): void {
        $highlightsSS = [];
        $lowercaseFieldsToHighlight = [];
        foreach ($fieldsToHighlight as $fieldname) {
            $lowercaseFieldsToHighlight[] = \strtolower($fieldname);
        }

        $keys = \array_keys($highlights);
        foreach ($keys as $key) {
            if (!isset($highlights[$key]) || !\in_array($key, $lowercaseFieldsToHighlight, true)) {
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
    }
}
