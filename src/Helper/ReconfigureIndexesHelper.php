<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectSchema;
use Suilven\FreeTextSearch\Index;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Client;

class ReconfigureIndexesHelper
{
    /** @param array<Index> $indexes */
    public function reconfigureIndexes($indexes)
    {
        foreach ($indexes as $index) {
            error_log('INDEX: ' . $index->getName());
            $className = $index->getClass();
            $name = $index->getName();
            $fields = []; // ['ID', 'CreatedAt', 'LastEdited'];


            // @todo different field types
            foreach ($index->getFields() as $field) {
                error_log('FIELD: ' . $field);
                $fields[] = $field;
            }

            foreach ($index->getTokens() as $token) {
                error_log('TOKEN: ' . $token);

                $fields[] = $token;
            }

            $singleton = singleton($className);
            $tableName = $singleton->config()->get('table_name');

            $schema = $singleton->getSchema();
            $specs = $schema->fieldSpecs($className, DataObjectSchema::DB_ONLY);


            $columns = [];
            foreach ($fields as $field) {
                $fieldType = $specs[$field];

                // fix likes of varchar(255)
                $fieldType = explode('(', $fieldType)[0];

                // this will be the most common
                $indexType = 'text';

                // @todo stripe HTML from HTML sourced index fields
                switch ($fieldType) {
                    case 'Int':
                        $indexType = 'integer';
                        break;
                    case 'Float':
                        $indexType = 'float';
                        break;
                }

                $columns[$field] = ['type' => $indexType];
            }

            $indexData = [
                'index' => $name,
                'body' => [
                    'columns' => $columns,

                    // trying to get suggest to work
                    // see https://docs.manticoresearch.com/latest/html/conf_options_reference/index_configuration_options.html
                    'settings' => [
                        'dict' => 'keywords',
                        'min_infix_len' => 2
                       // 'min_prefix_len' => 3,
                        //'morphology' => 'lemmatize_en',
                       // 'bigram_freq_words' => 'the, a, you, i'
                    ],

                    'silent' => true
                ]
            ];

            $manticoreClient = new Client();
            $connection = $manticoreClient->getConnection();

            $connection->indices()->drop($indexData);
            $connection->indices()->create($indexData);


            // need to override sort, set it to null
            Config::modify()->set($className, 'default_sort', null);
        }
    }
}
