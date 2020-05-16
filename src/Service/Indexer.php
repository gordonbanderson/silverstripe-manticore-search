<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\Core\Config\Config;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;

class Indexer
{
    /**
     * @var null|Indexes indexes in current context
     */
    private $indexes = null;


    /**
     * Indexer constructor.
     * @param Indexes $indexes indexes in context
     */
    public function __construct($indexes)
    {
        $this->indexes = $indexes;

        $config = Config::inst()->get('Suilven\FreeTextSearch\Indexes', 'indexes') ;
    }


    public function reconfigureIndexes()
    {
        $helper = new ReconfigureIndexesHelper();
        $helper->reconfigureIndexes($this->indexes);
    }
}
