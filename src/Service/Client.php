<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 19:58 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\Core\Config\Config;

class Client
{
    /**
     * Get a connection to sphinx using the values configured in YML files for port and host
     *
     * @return \Manticoresearch\Client Client object for accessing Manticore
     */
    public function getConnection(): \Manticoresearch\Client
    {
        $host = Config::inst()->get('Suilven\ManticoreSearch\Service\Client', 'host');
        $port = Config::inst()->get('Suilven\ManticoreSearch\Service\Client', 'port');

        $config = ['host'=>$host, 'port'=>$port];

        error_log('CLIENT CONFIG: ' . print_r($config, true));

        return new \Manticoresearch\Client($config);
    }


    /**
     * Execute reindex command. @todo Can this be done using SphinxQL?
     */
    public function reindex(): void
    {
        // @todo
    }
}
