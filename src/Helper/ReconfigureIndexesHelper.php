<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Helper;

use Suilven\FreeTextSearch\Factory\IndexCreatorFactory;

class ReconfigureIndexesHelper
{
    /** @param array<\Suilven\FreeTextSearch\Index> $indexes */
    public function reconfigureIndexes(array $indexes): void
    {
        $factory = new IndexCreatorFactory();
        $indexCreator = $factory->getIndexCreator();

        /** @var \Suilven\FreeTextSearch\Index $indexObj */
        foreach ($indexes as $indexObj) {
            $indexCreator->createIndex($indexObj->getName());
        }
    }
}
