<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\Core\Config\Config;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;

class Indexer extends \Suilven\FreeTextSearch\Base\Indexer
{
    public function index($dataObject)
    {
        error_log('>>>>>> INDEXING DO');
        $payload = $this->getFieldsToIndex($dataObject);
        error_log('---- payload returned ----');
        error_log(print_r($payload, 1));
        $coreClient = new Client();
        $client = $coreClient->getConnection();

        $indexNames = array_keys($payload);
        foreach($indexNames as $indexName) {
            $indexPayload = $payload[$indexName];
            $manticoreIndex = new \Manticoresearch\Index($client, $indexName);
            $manticoreIndex->replaceDocument($indexPayload, $dataObject->ID);
        }


    }
}
