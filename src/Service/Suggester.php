<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 25/3/2561
 * Time: 1:35 น.
 */

namespace Suilven\ManticoreSearch\Service;

class Suggester extends \Suilven\FreeTextSearch\Base\Suggester implements \Suilven\FreeTextSearch\Interfaces\Suggester
{
    /**
     * @var Client
     */
    private $client;

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
