<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\Core\Config\Config;
use Suilven\FreeTextSearch\Index;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;

class IndexCreator implements \Suilven\FreeTextSearch\Interfaces\IndexCreator
{
    public function createIndex($indexName)
    {
        $indexes = new Indexes();
        $indices = $indexes->getIndexes();

        /** @var Index $indice */
        foreach($indices as $indice)
        {
            $name = $indice->getName();
            if ($name == $indexName) {
                error_log(print_r($indice->getFields(), 1));
                error_log(print_r($indice, 1));
            }

        }
    }
}
