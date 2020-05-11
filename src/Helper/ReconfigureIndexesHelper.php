<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;


use Manticoresearch\Exceptions\ResponseException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObjectSchema;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Client;

class ReconfigureIndexesHelper
{
    /** @param Indexes $indexes */
    public function reconfigureIndexes($indexes)
    {
        if (Director::isDev()) {
            error_log('MODE: DEV');
        }

        if (Director::isTest()) {
            error_log('MODE: TEST');
        }

        if (Director::isLive()) {
            error_log('MODE: LIVE');
        }

        foreach ($indexes as $index) {
            $className = $index->getClass();
            $name = $index->getName();
            $fields = []; // ['ID', 'CreatedAt', 'LastEdited'];


            // @todo different field types
            foreach ($index->getFields() as $field) {
                $fields[] = $field;
            }

            foreach ($index->getTokens() as $token) {
                $fields[] = $token;
            }

            /** @var DataList $query */
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
                    ]
                ]
            ];


            $manticoreClient = new Client();
            $connection = $manticoreClient->getConnection();

            // @todo Question for Manticore: Can one pass the if exists clause?
            try {
                $connection->indices()->drop($indexData);
            } catch(ResponseException $ex) {
                $message = $ex->getMessage();
                if (substr($message,0,18) != '"DROP TABLE failed') {
                    throw $ex;
                }
            }
            $connection->indices()->create($indexData);


            // need to override sort, set it to null
            Config::modify()->set($className, 'default_sort', null);
        }
    }
}
