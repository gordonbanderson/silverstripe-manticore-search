<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 25/3/2561
 * Time: 1:35 น.
 */

namespace Suilven\ManticoreSearch\Service;

use Suilven\FreeTextSearch\Container\SuggesterResults;

class Suggester extends \Suilven\FreeTextSearch\Base\Suggester implements \Suilven\FreeTextSearch\Interfaces\Suggester
{
    /** @var \Suilven\ManticoreSearch\Service\Client */
    private $client;

    public function __construct()
    {
        $this->client = new Client();
    }


    public function suggest(string $q, int $limit = 5): SuggesterResults
    {
        $params = [
            'index' => $this->index,
            'body' => [
                'query'=>$q,
                'options' => [
                    'limit' => $limit,
                ],
            ],
        ];

        $response = $this->client->getConnection()->suggest($params);

        $results = new SuggesterResults();
        $results->setResults(\array_keys($response));
        $results->setIndex($this->index);
        $results->setQuery($q);
        $results->setLimit($limit);

        return $results;
    }
}
