<?php

declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use Suilven\FreeTextSearch\Exception\UnsupportedException;
use Suilven\FreeTextSearch\Helper\IndexingHelper;
use Suilven\FreeTextSearch\Helper\SpecsHelper;
use Suilven\FreeTextSearch\Indexes;
use Suilven\FreeTextSearch\Types\FieldTypes;
use Suilven\FreeTextSearch\Types\LanguageTypes;
use Suilven\FreeTextSearch\Types\TokenizerTypes;

// @phpcs:disable Generic.Files.LineLength.TooLong
// @phpcs:disable SlevomatCodingStandard.Files.LineLength.LineTooLong
class IndexCreator extends \Suilven\FreeTextSearch\Base\IndexCreator implements \Suilven\FreeTextSearch\Interfaces\IndexCreator
{
    /**
     * Create an index
     *
     * @todo Refactor into Indexer base
     * @param string $indexName the name of the index
     */
    public function createIndex(string $indexName): void
    {
        $indexingHelper = new IndexingHelper();
        $fields = $indexingHelper->getFields($indexName);
        $storedFields = $this->getStoredFields($indexName);

        $specsHelper = new SpecsHelper();
        $specs = $specsHelper->getFieldSpecs($indexName);

        $columns = [];
        foreach ($fields as $field) {
            // this will be the most common
            $indexType = 'text';
            $options = [];

            if (isset($specs[$field])) {
                $fieldType = $specs[$field];

                // @todo configure index to strip HTML
                switch ($fieldType) {
                    case FieldTypes::FOREIGN_KEY:
                        // @todo this perhaps needs to be a token
                        // See https://docs.manticoresearch.com/3.4.0/html/indexing/data_types.html

                        // @todo also how to mark strings for tokenizing?
                        $indexType = 'bigint';

                        break;
                    case FieldTypes::INTEGER:
                        $indexType = 'integer';

                        break;
                    case FieldTypes::FLOAT:
                        $indexType = 'float';

                        break;
                    case FieldTypes::TIME:
                        $indexType = 'timestamp';

                        break;
                    case FieldTypes::BOOLEAN:
                        // @todo is there a better type?
                        $indexType = 'integer';

                        break;
                }

                if ($indexType === 'text') {
                    $options = ['indexed', 'stored'];
                }
            }


            // override for Link, do not index it.  The storing of the Link URL is to save on database hierarchy
            // traversal when rendering search results
            //if ($field === 'Link' || \in_array($field, $storedFields, true)) {
            if ($field === 'Link') {
                $indexType = 'text';
                $options = ['stored'];
            }

            if (\in_array($field, $storedFields, true)) {
                $options = ['stored'];
            }
            $columns[$field] = ['type' => $indexType, 'options' => $options];
        }


        // @todo Add has one

        $indexes = new Indexes();
        $index = $indexes->getIndex($indexName);
        $mvaFields = $index->getHasManyFields();
        $hasOneFields = $index->getHasOneFields();

        foreach (\array_keys($mvaFields) as $mvaColumnName) {
            $columns[$mvaColumnName] = ['type' => 'multi'];
        }

        foreach (\array_keys($hasOneFields) as $hasOneColumnName) {
            $columns[$hasOneColumnName] = ['type' => 'bigint'];
        }


        $client = new Client();
        $manticoreClient = $client->getConnection();

        $settings = [
            'rt_mem_limit' => '256M',
            'dict' => 'keywords',
            'min_infix_len' => 2,
            'html_strip' => 1,
            'bigram_index' => 'all',
            'stopwords' => 'en',
        ];

        $manticoreTokenizer = null;

        // @todo this may need refactored
        $manticoreLanguage = $index->getLanguage();

        $tokenizer = $index->getTokenizer();
        if ($tokenizer !== TokenizerTypes::NONE) {
            switch ($tokenizer) {
                case TokenizerTypes::PORTER:
                    $manticoreTokenizer = 'porter';

                    break;
                case TokenizerTypes::SNOWBALL:
                    $manticoreTokenizer = 'snowball';

                    break;
                case TokenizerTypes::METAPHONE:
                    $manticoreTokenizer = 'metaphone';

                    break;
                case TokenizerTypes::SOUNDEX:
                    $manticoreTokenizer = 'soundex';

                    break;
                case TokenizerTypes::LEMMATIZER:
                    $manticoreTokenizer = 'lemmatizer';
                    $settings['lemmatizer_base'] = '/usr/local/share';

                    break;
            }

            $settings['morphology'] = $this->getMorphology($manticoreTokenizer, $manticoreLanguage);
        }




        // drop index, and updating an existing one does not effect change
        $manticoreClient->indices()->drop(['index' => $indexName, 'body'=>['silent'=>true]]);
        $manticoreIndex = new \Manticoresearch\Index($manticoreClient, $indexName);

        $manticoreIndex->create(
            $columns,
            $settings,
            true
        );
    }


    /**
     * @TODO Increase range of languages
     * @return string the name of the tokenizer to use at the Manticore config level
     * @throws \Suilven\FreeTextSearch\Exception\UnsupportedException if the combination of tokenizer and language cannot be used
     */
    private function getMorphology(?string $tokenizer, string $language): string
    {
        // @TODO add other languages, this is to get things up and rolling
        if ($language !== LanguageTypes::ENGLISH) {
            throw new UnsupportedException('Only English is supported for now #WorkInProgress');
        }

        $result = TokenizerTypes::NONE;

        switch ($tokenizer) {
            case TokenizerTypes::PORTER:
                $result = 'stem_en';

                break;
            case TokenizerTypes::LEMMATIZER:
                // @todo make the _all configurable
                $result = 'lemmatize_en_all';

                break;
            case TokenizerTypes::SOUNDEX:
                $result = 'soundex';

                break;
            case TokenizerTypes::METAPHONE:
                $result = 'metaphone';

                break;
        }

        return $result;
    }
}
