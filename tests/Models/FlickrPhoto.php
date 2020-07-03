<?php declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests\Models;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class FlickrPhoto extends DataObject implements TestOnly
{
    private static $table_name = 'FlickrPhoto';

    private static $db = [
        'Title' => 'Varchar(255)',
        'FlickrID' => 'Varchar',
        'Description' => 'HTMLText',
        'TakenAt' => 'Datetime',
        'FlickrLastUpdated' => 'Date',
        'GeoIsPublic' => 'Boolean',

        // flag to indicate requiring a flickr API update
        'IsDirty' => 'Boolean',

        'Orientation' => 'Int',
        'WoeID' => 'Int',
        'Accuracy' => 'Int',
        'FlickrPlaceID' => 'Varchar(255)',
        'Rotation' => 'Int',
        'IsPublic' => 'Boolean',
        'Aperture' => 'Float',
        'ShutterSpeed' => 'Varchar',
        'ImageUniqueID' => 'Varchar',
        'FocalLength35mm' => 'Int',
        'ISO' => 'Int',

        'AspectRatio' => 'Float',

        // geo
        'Lat' => 'Decimal(18,15)',
        'Lon' => 'Decimal(18,15)',
    ];

    private static $belongs_many_many = array(
        'FlickrSets' => FlickrSet::class
    );

    private static $many_many = array(
        'FlickrTags' => FlickrTag::class
    );
}
