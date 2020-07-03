<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 11/4/2561
 * Time: 16:22 น.
 */

namespace Suilven\ManticoreSearch\Task;

use SilverStripe\Dev\BuildTask;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Service\Indexer;

class ReconfigureIndexTask extends BuildTask
{

    protected $title = 'Regenerate Sphinx Configuration';

    protected $description = 'Regenerate sphinx configuration from models';

    protected $enabled = true;

    private static $segment = 'sphinxconfig';


    public function run($request): void
    {
        $indexesService = new Indexes();
        $indexesObj = $indexesService->getIndexes();
        $indexer = new Indexer($indexesObj);
        $indexer->reconfigureIndexes();
    }
}
