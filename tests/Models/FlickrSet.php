<?php

declare(strict_types = 1);

/**
 * Created by PhpStorm.
 * User: gordon
 * Date: 24/3/2561
 * Time: 20:36 น.
 */

namespace Suilven\ManticoreSearch\Tests\Models;

use SilverStripe\Dev\TestOnly;
use SilverStripe\ORM\DataObject;

class FlickrSet extends DataObject implements TestOnly
{
    private static $table_name = 'FlickrSet';

    private static $db = [
        'Title' => 'Varchar(255)',
        'FlickrID' => 'Varchar',
        'Description' => 'HTMLText',
        'FirstPictureTakenAt' => 'Datetime',
        // flag to indicate requiring a flickr API update
        'IsDirty' => 'Boolean',
        'LockGeo' => 'Boolean',
        'BatchTags' => 'Varchar',
        'BatchTitle' => 'Varchar',
        'BatchDescription' => 'HTMLText',
        'ImageFooter' => 'Text',
        'SpriteCSS' => 'Text',
        'SortOrder' => "Enum('TakenAt,UploadUnixTimeStamp', 'UploadUnixTimeStamp')",
    ];

    private static $belongs_many_many = array(
        'FlickrPhotos' => FlickrPhoto::class
    );
}
