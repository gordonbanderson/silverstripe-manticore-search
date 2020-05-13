<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 21:14 น.
 */

namespace Suilven\ManticoreSearch\Service;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\DataObjectSchema;
use SilverStripe\Versioned\Versioned;
use SilverStripe\View\ArrayData;
use Suilven\FreeTextSearch\Index;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\ReconfigureIndexesHelper;

class Indexer
{
    protected $databaseName;

    protected $databaseHost;

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
    }

    /**
     * @param mixed $databaseName
     */
    public function setDatabaseName($databaseName)
    {
        $this->databaseName = $databaseName;
    }

    /**
     * @param mixed $databaseHost
     */
    public function setDatabaseHost($databaseHost)
    {
        $this->databaseHost = $databaseHost;
    }



    public function reconfigureIndexes()
    {
        $helper = new ReconfigureIndexesHelper();
        $helper->reconfigureIndexes($this->indexes);
    }

    /**
     * Create a valid sphinx.conf file and save it.  Note that the commandline or web server user must have write
     * access to the path defined in _config.
     */
    public function saveConfig()
    {
        // specific to the runnnig of sphinx
        $common = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'sphinxconfig' . DIRECTORY_SEPARATOR . 'common.conf');
        $indexer = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'sphinxconfig' . DIRECTORY_SEPARATOR . 'indexer.conf');
        $searchd = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..'
            . DIRECTORY_SEPARATOR . 'sphinxconfig' . DIRECTORY_SEPARATOR . 'searchd.conf');


        // specific to silverstripe data
        $sphinxConfigurations = $this->generateConfig();
        $sphinxSavePath = Config::inst()->get(Client::class, 'config_file');

        $config = $common . $indexer . $searchd;

        foreach (array_keys($sphinxConfigurations) as $filename) {
            $config .= $sphinxConfigurations[$filename];
        }

        file_put_contents($sphinxSavePath, $config);
    }
}
