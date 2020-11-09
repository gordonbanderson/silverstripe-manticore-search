<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\ORM\DataObject;
use Suilven\FreeTextSearch\Factory\IndexerFactory;

/**
 * Class BulkIndexer
 *
 * @package Suilven\ManticoreSearch\Service
 * @todo Move some of this into a base indexer
 */
class BulkIndexer implements \Suilven\FreeTextSearch\Interfaces\BulkIndexer
{
    /** @var array<int,array<string,string|float|bool|int|null>> */
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
        $factory = new IndexerFactory();
        $indexer = $factory->getIndexer();
        $indexer->setIndexName($this->index);
        $payload = $indexer->getIndexablePayload($dataObject);
        $toIndex = $payload[$this->index];

        $keys = \array_keys($toIndex);
        foreach ($keys as $key) {
            if (!\is_null($toIndex[$key])) {
                continue;
            }

            $toIndex[$key] = '';
        }

       // unset($toIndex['Photographer']);
        print_r($toIndex);

        // @todo Fix indexing of parent id
        unset($toIndex['ParentID']);
        $this->bulkIndexData[$dataObject->ID] = $toIndex;
    }


    public function indexDataObjects(): int
    {
        $body = [];
        $nDataObjects = 0;

        foreach (\array_keys($this->bulkIndexData) as $dataObjectID) {
            $docPayload = [
                'replace' => [
                    'index' => $this->index,
                    'id' => $dataObjectID,
                    'doc' => $this->bulkIndexData[$dataObjectID],
                ],
            ];
            $body[] = $docPayload;
            $nDataObjects++;
        }

        if ($nDataObjects === 0) {
            return 0;
        }

        $coreClient = new Client();
        $client = $coreClient->getConnection();
        $payload = ['body' => $body];
        $client->bulk($payload);
        $this->resetBulkIndexData();

        return $nDataObjects;
    }


    private function resetBulkIndexData(): void
    {
        $this->bulkIndexData = [];
    }
}
