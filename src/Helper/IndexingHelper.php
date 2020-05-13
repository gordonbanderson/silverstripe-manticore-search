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


/*
    THIS WORKS

                    $docs =[
                        ['insert'=> ['index'=>'sitetree','id'=>2,'doc'=>['Title'=>'Interstellar','Content'=>'A team of explorers travel through a wormhole in space in an attempt to ensure humanity\'s survival.']]],
                        ['insert'=> ['index'=>'sitetree','id'=>3,'doc'=>['Title'=>'Inception','Content'=>'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.']]],
                        ['insert'=> ['index'=>'sitetree','id'=>4,'doc'=>['Title'=>'1917 ','Content'=>' As a regiment assembles to wage war deep in enemy territory, two soldiers are assigned to race against time and deliver a message that will stop 1,600 men from walking straight into a deadly trap.']]],
                        ['insert'=> ['index'=>'sitetree','id'=>5,'doc'=>['Title'=>'Alien','Content'=>' After a space merchant vessel receives an unknown transmission as a distress call, one of the team\'s member is attacked by a mysterious life form and they soon realize that its life cycle has merely begun.']]]
                    ];

*/



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
            error_log('INDEX CLASS=' . $index->getClass());
            error_log('SS CLASSNAME: ' . $ssDataObject->ClassName);
            if ($index->getClass() == $ssDataObject->ClassName) {
                $payload = $this->getDocumentPayload($index, $ssDataObject);

                //  unset($payload['Sort']);

                // this seems to break, not sure why - null issues?
                unset($payload['ParentID']);
           //     unset($payload['MenuTitle']);
          //      unset($payload['Content']);

                $doc = [
                    'index'=>'sitetree',
                    'id' => $ssDataObject->ID,
                    'doc' => $payload
                ];


                $client = new Client();
                $connection = $client->getConnection();
                $response = $connection->replace(['body' =>$doc], $ssDataObject->ID);
                error_log('RESPONSE FOR REPLACE');
                error_log(print_r($response, 1));
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
            if ($field != 'ParentID') {
                $payload[$field] = $ssDataObject->$field;
            }
        }

        return $payload;
    }
}
