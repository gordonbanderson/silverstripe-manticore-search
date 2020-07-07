<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;

use SilverStripe\ORM\DataObject;
use Suilven\FreeTextSearch\Index;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Client;

class IndexingHelper
{


    // @todo Remove this

    /** @param string $classname The class to index, e.g. SilverStripe\CMS\Model\SiteTree */
    public function bulkIndex(string $classname): void
    {
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();

        /** @var \Suilven\FreeTextSearch\Index $index */
        foreach ($indexesObj as $index) {
            if ($index->getClass() !== $classname) {
                continue;
            }

            // @todo Make this configurable
            $bulkSize = 100;
            $singleton = \singleton($classname);
            $page = 0;
            $count = $singleton::get()->count();

            $nPages = 1+(\floor($count/$bulkSize));
            for ($page=0; $page< $nPages; $page++) {
                $dataObjects = $singleton::get()->limit($bulkSize, $bulkSize*$page);

                $bulkData = [];
                foreach ($dataObjects as $dataObject) {
                    $payload = $this->getDocumentPayload($index, $dataObject);
                    $row = [
                        'replace' => [
                            'index' => $index->getName(),
                            'id' => $dataObject->ID,
                            'doc' => $payload,
                        ],
                    ];

                    $bulkData[] = $row;
                }

                $client = new Client();
                $connection = $client->getConnection();

                // @todo Check for error in response and throw an exception
                $connection->bulk(['body'=>$bulkData]);
            }
        }
    }


    // @todo Check object exists prior to indexing attempt and throw an appropriate error

    /**
     * Index a data object in all of the indexes it belongs to
     *
     * @param \SilverStripe\ORM\DataObject $ssDataObject the data object to be indexed
     * @phpstan-ignore-next-line
     */
    public function indexObject(DataObject $ssDataObject): void
    {
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();

        /** @var \Suilven\FreeTextSearch\Index $index */
        foreach ($indexesObj as $index) {
            $ancestry = $ssDataObject->getClassAncestry();
            $ancestry = \array_reverse($ancestry);
            foreach ($ancestry as $key) {
                if ($index->getClass() !== $key) {
                    continue;
                }

                $payload = $this->getDocumentPayload($index, $ssDataObject);

                //  unset($payload['Sort']);

                // this seems to break, not sure why - null issues?
                unset($payload['ParentID']);
                //     unset($payload['MenuTitle']);
                //      unset($payload['Content']);

                $doc = [
                    'index'=>$index->getName(),
                    'id' => $ssDataObject->ID,
                    'doc' => $payload,
                ];

                $client = new Client();
                $connection = $client->getConnection();
                $connection->replace(['body' =>$doc]);
            }
        }
    }


    /** @return array<string,string|float|int|bool> */
    public function getDocumentPayload(Index $index, DataObject $ssDataObject): array
    {
        $payload = [];
        foreach ($index->getFields() as $field) {
            // ParentID breaks bulk indexing.  No idea why :(
            // GBA: It seems to be the wrong type, it is 'indexed stored' instead of bigint
            if ($field === 'ParentID') {
                continue;
            }

            $payload[$field] = $ssDataObject->$field;
        }

        return $payload;
    }
}
