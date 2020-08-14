<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObjectSchema;
use Suilven\ManticoreSearch\Service\Client;

class ReconfigureIndexesHelper
{
    /** @param array<\Suilven\FreeTextSearch\Index> $indexes */
    public function reconfigureIndexes(array $indexes): void
    {
        error_log(print_r($indexes, true));
        foreach ($indexes as $index) {
            $className = $index->getClass();

            \error_log('-------------------');
            \error_log('CLASS NAME: ' . $className);

            $name = $index->getName();
            // ['ID', 'CreatedAt', 'LastEdited'];
            $fields = [];

            // @todo different field types
            foreach ($index->getFields() as $field) {
                $fields[] = $field;
            }

            foreach ($index->getTokens() as $token) {
                $fields[] = $token;
            }

            $singleton = \singleton($className);

            /** @var \SilverStripe\ORM\DataObjectSchema $schema */
            $schema = $singleton->getSchema();
            $specs = $schema->fieldSpecs($className, DataObjectSchema::DB_ONLY);

            $columns = [];
            foreach ($fields as $field) {
                $fieldType = $specs[$field];

                // fix likes of varchar(255)
                $fieldType = \explode('(', $fieldType)[0];

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
                        'min_infix_len' => 2,
                       // 'min_prefix_len' => 3,
                        //'morphology' => 'lemmatize_en',
                       // 'bigram_freq_words' => 'the, a, you, i'
                    ],

                    'silent' => true,
                ],
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
