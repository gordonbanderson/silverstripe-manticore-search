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

    /**
     * Generate config
     *
     * @return array of filename => sphinx config
     */
    public function generateConfig()
    {






        $allConfigs = [];


        /** @var Index $index */
        foreach ($this->indexes as $index) {
            $className = $index->getClass();
            $name = $index->getName();
            $fields = []; // ['ID', 'CreatedAt', 'LastEdited'];

            // these are stored in the db but not part of free text search, a bit like tokens I guess
            $attributes = new ArrayList();

            // @todo different field types
            foreach ($index->getFields() as $field) {
                $fields[] = $field;
            }

            foreach ($index->getTokens() as $token) {
                $fields[] = $token;
            }

            $tokens = $index->getTokens();

            /** @var DataList $query */
            $singleton = singleton($className);
            $tableName = $singleton->config()->get('table_name');

            $schema = $singleton->getSchema();

            $specs = $schema->fieldSpecs($className, DataObjectSchema::DB_ONLY);


            // need to override sort, set it to null
            Config::modify()->set($className, 'default_sort', null);

            // @todo fix reference here
            /** @var DataObject $queryObject */

            /** @var $DataList $queryObject */
            $queryObject = Versioned::get_by_stage($className, Versioned::LIVE);

            // this is how to do it with a DataList, it clones and returns a new DataList
            $queryObject = $queryObject->setQueriedColumns($fields);

            // this needs massages for sphinx
            $sql = $queryObject->sql();

            $classNameInHierarchy = $className;


            $joinClasses = [];

            // need to know for the stage, dataobjects assumed flat
            $isSiteTree = false;
            while ($classNameInHierarchy != 'SilverStripe\ORM\DataObject') {
                if ($classNameInHierarchy != 'SilverStripe\CMS\Model\SiteTree') {
                    $joinClasses[] = '\'' .  str_replace('\\', '\\\\', $classNameInHierarchy) . '\'';
                } else {
                    $isSiteTree = true;
                }

                $instance = new $classNameInHierarchy;
                $classNameInHierarchy = get_parent_class($classNameInHierarchy);
            }


            $sql = str_replace('"', '`', $sql);

            // need to move ID to first param
            if ($isSiteTree) {
                $sql = str_replace("`{$tableName}_Live`.`ID`, ", '', $sql);
                $sql = str_replace('SELECT DISTINCT', "SELECT DISTINCT `{$tableName}_Live`.`ID`, ", $sql);
            } else {
                $sql = str_replace("`{$tableName}`.`ID`, ", '', $sql);
                $sql = str_replace('SELECT DISTINCT', "SELECT DISTINCT `{$tableName}`.`ID`, ", $sql);
            }


            $commas = str_repeat('?, ', sizeof($joinClasses));
            $commas = substr($commas, 0, -2);
            $columns = implode(', ', $joinClasses);
            $sql = str_replace(
                'WHERE (`SiteTree_Live`.`ClassName` IN (' . $commas. '))',
                "WHERE (`SiteTree_Live`.`ClassName` IN ({$columns}))",
                $sql
            );


            $sqlArray = explode(PHP_EOL, $sql);
            $sql = implode(' \\' . "\n", $sqlArray);

            // loop through fields adding attribute or altering sql as needbe
            $allFields = $fields;
            $allFields[] = 'LastEdited';
            $allFields[] = 'Created';

            // make modifications to query and or attributes but only if required
            foreach ($allFields as $field) {
                if (isset($specs[$field])) {
                    $fieldType = $specs[$field];

                    switch ($fieldType) {
                        case 'DBDatetime':
                            $sql = str_replace("`$tableName`.`$field`", "UNIX_TIMESTAMP(`$tableName`.`$field`) AS `$field`", $sql);
                            // $sql = str_replace("`$tableName`.`$field`", "UNIX_TIMESTAMP(`$tableName`.`$field`) AS {$field}" , $sql);
                            $attributes->push(['Name' => $field, 'Type' => 'sql_attr_timestamp']);
                            break;
                        case 'Datetime':
                            $sql = str_replace("`$tableName`.`$field`", "UNIX_TIMESTAMP(`$tableName`.`$field`) AS `$field`", $sql);
                            // this breaks order by if field is after: $sql = str_replace("`$tableName`.`$field`", "UNIX_TIMESTAMP(`$tableName`.`$field`) AS {$field}" , $sql);
                            $attributes->push(['Name' => $field, 'Type' => 'sql_attr_timestamp']);
                            break;
                        case 'Boolean':
                            $attributes->push(['Name' => $field, 'Type' => 'sql_attr_bool']); // @todo informed guess
                            break;
                        case 'ForeignKey':
                            $attributes->push(['Name' => $field, 'Type' => 'sql_attr_uint']);
                            break;
                        default:
                            // do nothing
                            break;
                    }

                    // strings and ints may need tokenized, others as above.  See http://sphinxsearch.com/wiki/doku.php?id=fields_and_attributes
                    if (in_array($field, $tokens)) {
                        $fieldType = $specs[$field];

                        // remove string length from varchar
                        if (substr($fieldType, 0, 7) === "Varchar") {
                            $fieldTYpe = 'Varchar';
                        }

                        // @todo - float
                        // NOTE, cannot filter on string attributes, see http://sphinxsearch.com/wiki/doku.php?id=fields_and_attributes
                        // OH, it seems to work :)
                        switch ($fieldType) {
                            case 'Int':
                                $attributes->push(['Name' => $field, 'Type' => 'sql_attr_uint']);
                                break;
                            case 'Varchar':
                                $attributes->push(['Name' => $field, 'Type' => 'sql_attr_string']);
                                break;
                            case 'HTMLText':
                                $attributes->push(['Name' => $field, 'Type' => 'sql_attr_uint']);
                                break;
                            case 'Float':
                                $attributes->push(['Name' => $field, 'Type' => 'sql_attr_float']);
                                break;
                            default:
                                // do nothing
                                break;
                        }
                    }
                } else {
                    user_error("T10 The field {$field} does not exist for class {$className}");
                }

                //
            }

            /**
             * to add
             *  sql_attr_string     = classname
             */


            $params = new ArrayData([
               'IndexName' => $name,
               'SQL' => 'SQL_QUERY_HERE',
                'DB_HOST' => !empty($this->databaseHost) ? $this->databaseHost : Environment::getEnv('SS_DATABASE_SERVER'),
                'DB_USER' => Environment::getEnv('SS_DATABASE_USERNAME'),
                'DB_PASSWD' => Environment::getEnv('SS_DATABASE_PASSWORD'),
                'DB_NAME' => !empty($this->databaseName) ? $this->databaseName : Environment::getEnv('SS_DATABASE_NAME'),
                'Attributes' => $attributes,
            ]);


            $configuraton = $params->renderWith('IndexClassConfig');



            $configuration2 = str_replace('SQL_QUERY_HERE', $sql, $configuraton);

            // @todo generic naming
            $allConfigs[$name] = "{$configuration2}";
        }
        return $allConfigs;
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
