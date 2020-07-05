<?php declare(strict_types=1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\ORM\DataObject;
use Suilven\ManticoreSearch\Helper\IndexingHelper;

/**
 * Class BulkIndexer
 * @package Suilven\ManticoreSearch\Service
 *
 * @todo Move some of this into a base indexer
 */
class BulkIndexer  implements \Suilven\FreeTextSearch\Interfaces\BulkIndexer
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
     *
     * @param DataObject $dataObject
     */
    public function addDataObject(DataObject $dataObject): void
    {
        $helper = new IndexingHelper();
        $payload = $helper->getFieldsToIndex($dataObject);
        $this->bulkIndexData[$dataObject->ID] = $payload;
    }


    public function indexDataObjects()
    {
        $body = [];
        foreach(array_keys($this->bulkIndexData) as $dataObjectID)
        {
            $docPayload = [
                'replace' => [
                    'index' => $this->index,
                    'id' => $dataObjectID,
                    'doc' => [
                        $this->bulkIndexData[$dataObjectID]
                    ]
                ]
            ];
            $body[] = $docPayload;
        }

        $coreClient = new Client();
        $client = $coreClient->getConnection();
        $client->bulk(['body' => $body]);
        $this->resetBulkIndexData();
    }


    private function resetBulkIndexData(): void
    {
        $this->bulkIndexData = [];

    }


    /**
     * $doc = [
    'body' => [
    ['insert' => [
    'index' => 'testrt',
    'id' => 34,
    'doc' => [
    'gid' => 1,
    'title' => 'a new added document',
    ]
    ]],
    ['update' => [
    'index' => 'testrt',
    'id' => 56,
    'doc' => [
    'gid' => 4,
    ]
    ]],
    ['delete' => [
    'index' => 'testrt',
    'id' => 100
    ]]
    ]
    ];

    $response = $client->bulk($doc);

     */
}
