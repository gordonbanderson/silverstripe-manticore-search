<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use Suilven\FreeTextSearch\Indexes;
use Suilven\FreeTextSearch\Types\FieldTypes;

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
        $fields = $this->getFields($indexName);
        $storedFields = $this->getStoredFields($indexName);
        $specs = $this->getFieldSpecs($indexName);

        \error_log('SPECS');
        \print_r($specs);

        $columns = [];
        foreach ($fields as $field) {
            /*
            if ($field === 'Link') {
                error_log('Skipping link field');
               continue;
            }
            */

            if (isset($specs[$field])) {
                $fieldType = $specs[$field];

                // this will be the most common
                $indexType = 'text';

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

                $options = [];
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

        \error_log(\print_r($mvaFields, true));

        foreach (\array_keys($mvaFields) as $mvaColumnName) {
            $columns[$mvaColumnName] = ['type' => 'multi'];
        }

        $client = new Client();
        $manticoreClient = $client->getConnection();

        $settings = [
            'rt_mem_limit' => '256M',
            'dict' => 'keywords',
            'min_infix_len' => 2,
            'html_strip' => 1,
            'bigram_index' => 'all',
        ];


        // drop index, and updating an existing one does not effect change
        $manticoreClient->indices()->drop(['index' => $indexName, 'body'=>['silent'=>true]]);


        $manticoreIndex = new \Manticoresearch\Index($manticoreClient, $indexName);

        error_log('----- payload -----');
        error_log(print_r($columns, true));

        $manticoreIndex->create(
            $columns,
            $settings,
            true
        );
    }
}
