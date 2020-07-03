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

class IndexCreator implements \Suilven\FreeTextSearch\Interfaces\IndexCreator
{
    public function createIndex(string $indexName): void
    {
        $indexes = new Indexes();

        $index = $indexes->getIndex($indexName);

        $singleton = \singleton($index->getClass());

        // ['ID', 'CreatedAt', 'LastEdited'];
        $fields = ['CreatedAt'];

        // @todo different field types
        foreach ($index->getFields() as $field) {
            $fields[] = $field;
        }

        foreach ($index->getTokens() as $token) {
            $fields[] = $token;
        }

        /** @var \SilverStripe\ORM\DataObjectSchema $schema */
        $schema = $singleton->getSchema();
        $specs = $schema->fieldSpecs($index->getClass(), DataObjectSchema::DB_ONLY);

        $columns = [];
        foreach ($fields as $field) {
            $fieldType = $specs[$field];

            // fix likes of varchar(255)
            $fieldType = \explode('(', $fieldType)[0];

            // this will be the most common
            $indexType = 'text';

            // @todo configure index to strip HTML
            switch ($fieldType) {
                case 'Int':
                    $indexType = 'integer';

                    break;
                case 'Float':
                    $indexType = 'float';

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

        \error_log('INDEX CREATION PAYLOAD: $columns');
        \error_log(\print_r($columns, 1));

        $manticoreIndex = new \Manticoresearch\Index($manticoreClient, $indexName);

        $manticoreIndex->create(
            $columns,
            $settings,
            true,
        );
    }
}