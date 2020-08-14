<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\ORM\DataObjectSchema;
use Suilven\FreeTextSearch\Indexes;
use Suilven\FreeTextSearch\Types\FieldTypes;

class IndexCreator implements \Suilven\FreeTextSearch\Interfaces\IndexCreator
{
    /**
     * Create an index
     *
     * @todo Refactor into Indexer base
     * @param string $indexName the name of the index
     */
    public function createIndex(string $indexName): void
    {
        $indexes = new Indexes();

        $index = $indexes->getIndex($indexName);

        $fields = [];

        $singleton = \singleton($index->getClass());


        // @todo different field types
        foreach ($index->getFields() as $field) {
            $fields[] = $field;
        }

        foreach ($index->getTokens() as $token) {
            $fields[] = $token;
        }

        /** @var \SilverStripe\ORM\DataObjectSchema $schema */
        $schema = $singleton->getSchema();
        $specs = $schema->fieldSpecs($index->getClass(), DataObjectSchema::INCLUDE_CLASS);




        $columns = [];
        foreach ($fields as $field) {
            $fieldType = $specs[$field];

            \error_log('T1 FT=' . $fieldType);

            // fix likes of varchar(255)
            $fieldType = \explode('(', $fieldType)[0];
            \error_log('T2 FT=' . $fieldType);

            // remove the class name
            $fieldType = \explode('.', $fieldType)[1];

            // this will be the most common
            $indexType = 'text';

            \error_log('FIELD TYPE: ' . $fieldType);

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
            $columns[$field] = ['type' => $indexType, 'options' => $options];
        }



        $client = new Client();
        $manticoreClient = $client->getConnection();


        $settings = [
            'rt_mem_limit' => '256M',
            'dict' => 'keywords',
            'min_infix_len' => 2,
            'html_strip' => 1,
        ];



        // drop index, and updating an existing one does not effect change
        $manticoreClient->indices()->drop(['index' => $indexName, 'body'=>['silent'=>true]]);


        $manticoreIndex = new \Manticoresearch\Index($manticoreClient, $indexName);

        $manticoreIndex->create(
            $columns,
            $settings,
            true,
        );
    }
}
