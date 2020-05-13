<?php
/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests\Models;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;
use Suilven\FreeTextSearch\Indexes;
use Suilven\ManticoreSearch\Helper\IndexingHelper;
use Suilven\ManticoreSearch\Service\Indexer;
use Suilven\ManticoreSearch\Service\Searcher;
use Suilven\ManticoreSearch\Service\Suggester;

class FlickrPhoto extends DataObject implements TestOnly
{
    private static $table_name = 'FlickrPhoto';

    private static $db = [
        'Title' => 'Varchar(255)',
        'FlickrID' => 'Varchar',
        'Description' => 'HTMLText',
        'TakenAt' => 'Datetime',
        'FlickrLastUpdated' => DBDate::class,
        'GeoIsPublic' => DBBoolean::class,

        // flag to indicate requiring a flickr API update
        'IsDirty' => DBBoolean::class,

        'Orientation' => 'Int',
        'WoeID' => 'Int',
        'Accuracy' => 'Int',
        'FlickrPlaceID' => 'Varchar(255)',
        'Rotation' => 'Int',
        'IsPublic' => DBBoolean::class,
        'Aperture' => 'Float',
        'ShutterSpeed' => 'Varchar',
        'ImageUniqueID' => 'Varchar',
        'FocalLength35mm' => 'Int',
        'ISO' => 'Int',

        'AspectRatio' => 'Float',

        // geo
        'Lat' => 'Decimal(18,15)',
        'Lon' => 'Decimal(18,15)'
    ];

    private static $belongs_many_many = array(
        'FlickrSets' => FlickrSet::class
    );

    private static $many_many = array(
        'FlickrTags' => FlickrTag::class
    );
}
