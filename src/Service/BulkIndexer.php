<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\ORM\DataObject;
use Suilven\FreeTextSearch\Helper\IndexingHelper;

/**
 * Class BulkIndexer
 *
 * @package Suilven\ManticoreSearch\Service
 * @todo Move some of this into a base indexer
 */
class BulkIndexer implements \Suilven\FreeTextSearch\Interfaces\BulkIndexer
{
    protected $bulkIndexData;

    /** @var string */
    protected $index;


    public function __construct()
    {
        $this->resetBulkIndexData();
    }


    /** @param string $newIndex the new index name */
    public function setIndex(string $newIndex): void
    {
        $this->index = $newIndex;
    }


    /**
     * Note this makes the assumption of unique IDs, along with one index
     */
    public function addDataObject(DataObject $dataObject): void
    {
        $helper = new IndexingHelper();
        $payload = $helper->getFieldsToIndex($dataObject);
        $this->bulkIndexData[$dataObject->ID] = $payload[$this->index];
    }


    public function indexDataObjects(): void
    {
        $body = [];

        foreach (\array_keys($this->bulkIndexData) as $dataObjectID) {
            $docPayload = [
                'replace' => [
                    'index' => $this->index,
                    'id' => $dataObjectID,
                    'doc' => $this->bulkIndexData[$dataObjectID],
                ],
            ];
            $body[] = $docPayload;
        }

        $coreClient = new Client();
        $client = $coreClient->getConnection();
        $payload = ['body' => $body];
        $client->bulk($payload);
        $this->resetBulkIndexData();
    }


    private function resetBulkIndexData(): void
    {
        $this->bulkIndexData = [];
    }
}
