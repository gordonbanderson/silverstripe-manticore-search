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
        $payload = $this->getIndexablePayload($dataObject);
        error_log('PAYLOAD: ' . print_r($payload, true));
        $coreClient = new Client();
        $client = $coreClient->getConnection();

        $indexNames = \array_keys($payload);
        foreach ($indexNames as $indexName) {
            error_log('INDEXING AGAINST ' . $indexName);
            $indexPayload = $payload[$indexName];

            // skip empty payloads
            if ($indexPayload !== []) {
                error_log('INDEX PAYLOAD ORIG: ' . print_r($indexPayload, true));
                unset($indexPayload['ParentID']); // @todo fix parent id indexing
                error_log('INDEX PAYLOAD CHANGED: ' . print_r($indexPayload, true));

                $manticoreIndex = new \Manticoresearch\Index($client, $indexName);
                $desc = $manticoreIndex->describe();
                error_log('---- description ----');
                error_log(print_r($desc, true));
                //$manticoreIndex->replaceDocument($indexPayload, $dataObject->ID);
                $manticoreIndex->replaceDocument($indexPayload, $dataObject->ID);
            }

        }
    }
}
