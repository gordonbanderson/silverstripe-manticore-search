<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 25/3/2561
 * Time: 1:35 น.
 */

namespace Suilven\ManticoreSearch\Service;

class Suggester
{
    /**
     * @var Client
     */
    private $client;

    private $index = 'sitetree';

    /**
     * @param string $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }


    public function __construct()
    {
        $this->client = new Client();
    }

    public function suggest($q, $limit = 5)
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query'=>$q,
                'options' => [
                    'limit' => $limit
                ]
            ]
        ];

        $response = $this->client->getConnection()->suggest($params);
        return array_keys($response);
    }
    
}
