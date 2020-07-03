<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

class Indexer extends \Suilven\FreeTextSearch\Base\Indexer
{
    public function index(\SilverStripe\ORM\DataObject $dataObject): void
    {
        $payload = $this->getFieldsToIndex($dataObject);
        $coreClient = new Client();
        $client = $coreClient->getConnection();

        $indexNames = \array_keys($payload);
        foreach ($indexNames as $indexName) {
            $indexPayload = $payload[$indexName];
            $manticoreIndex = new \Manticoresearch\Index($client, $indexName);
            $manticoreIndex->replaceDocument($indexPayload, $dataObject->ID);
        }
    }
}
