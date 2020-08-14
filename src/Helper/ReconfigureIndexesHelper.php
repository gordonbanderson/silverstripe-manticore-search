<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;

use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\DataObjectSchema;
use Suilven\FreeTextSearch\Factory\IndexCreatorFactory;
use Suilven\FreeTextSearch\Index;
use Suilven\ManticoreSearch\Service\Client;

class ReconfigureIndexesHelper
{
    /** @param array<\Suilven\FreeTextSearch\Index> $indexes */
    public function reconfigureIndexes(array $indexes): void
    {
        error_log(print_r($indexes, true));
        $factory = new IndexCreatorFactory();
        $indexCreator = $factory->getIndexCreator();

        /** @var Index $indexObj */
        foreach ($indexes as $indexObj) {
            error_log(print_r($indexObj, true));
            $indexCreator->createIndex($indexObj->getName());


            // need to override sort, set it to null
            //Config::modify()->set($className, 'default_sort', null);
        }
    }
}
