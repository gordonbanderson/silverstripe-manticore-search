<?php
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
    public function getConnection()
    {
        $host = Config::inst()->get('Suilven\ManticoreSearch\Service\Client', 'host');
        $port = Config::inst()->get('Suilven\ManticoreSearch\Service\Client', 'port');

        error_log('**** HOST: ' . $host);
        error_log('**** PORT: ' . $port);


        $config = ['host'=>$host,'port'=>$port];
        return  new \Manticoresearch\Client($config);
    }



    /**
     * Execute reindex command.  @todo Can this be done using SphinxQL?
     */
    public function reindex()
    {
        $reindexCommand = Config::inst()->get('Suilven\ManticoreSearch\Service\Client', 'cmd_reindex');


    }
}
