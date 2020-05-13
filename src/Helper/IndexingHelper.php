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
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use Suilven\FreeTextSearch\Index;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Client;

class IndexingHelper
{


    public function bulkIndex($classname)
    {
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();

        /** @var Index $index */
        foreach ($indexesObj as $index) {
            if ($index->getClass() == $classname) {
                $bulkSize = 100; // @todo Make this configurable
                $singleton = singleton($classname);
                $page = 0;
                $count = $singleton::get()->count();

                $nPages = 1+(abs($count/$bulkSize));
                for ($i=0; $i< $nPages; $i++) {
                    $dataObjects = $singleton::get()->limit($bulkSize, $bulkSize*$i);

                    $bulkData = [];
                    foreach ($dataObjects as $dataObject) {
                        $payload = $this->getDocumentPayload($index, $dataObject);
                        $row = [
                            'insert' => [
                                'index' => $index->getName(),
                                'id' => $dataObject->ID,
                                'doc' => $payload
                            ]
                        ];

                        $bulkData[] = $row;
                    }

                    $client = new Client();
                    $connection = $client->getConnection();
                    $connection->bulk(['body'=>$bulkData]);
                }
            }
        }
    }

    /**
     * @param DataObject $ssDataObject
     */
    public function indexObject($ssDataObject)
    {
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();

        /** @var Index $index */
        foreach ($indexesObj as $index) {
            $ancestry = $ssDataObject->getClassAncestry();
            array_reverse($ancestry);
            foreach ($ancestry as $key) {
                if ($index->getClass() == $key) {
                    $payload = $this->getDocumentPayload($index, $ssDataObject);

                    //  unset($payload['Sort']);

                    // this seems to break, not sure why - null issues?
                    unset($payload['ParentID']);
                    //     unset($payload['MenuTitle']);
                    //      unset($payload['Content']);

                    // @todo Remove hardwire
                    $doc = [
                        'index'=>'sitetree',
                        'id' => $ssDataObject->ID,
                        'doc' => $payload
                    ];

                    $client = new Client();
                    $connection = $client->getConnection();
                    $connection->replace(['body' =>$doc]);
                }
            }
        }
    }

    /**
     * @param Index $index
     * @param DataObject $ssDataObject
     * @return array[]
     */
    public function getDocumentPayload(Index $index, DataObject $ssDataObject): array
    {
        $payload = [];
        foreach ($index->getFields() as $field) {
            // ParentID breaks bulk indexing.  No idea why :(
            // GBA: It seems to be the wrong type, it is 'indexed stored' instead of bigint
            if ($field != 'ParentID') {
                $payload[$field] = $ssDataObject->$field;
            }
        }

        return $payload;
    }
}
