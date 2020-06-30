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

class Indexer extends \Suilven\FreeTextSearch\Base\Indexer
{
    public function addDataObjectToIndex($dataObject, $index)
    {
        error_log('INDEXING DO');
    }
}
