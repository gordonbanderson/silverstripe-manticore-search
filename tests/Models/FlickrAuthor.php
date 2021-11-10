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

class FlickrAuthor extends DataObject implements TestOnly
{
    private static $table_name = 'FlickrAuthor';

    /** @var array<string,string> */
    private static $db = [
        'PathAlias' => 'Varchar',
        'DisplayName' => 'Varchar',
        'FlickrID' => 'Varchar',
    ];

    /** @var array<string,string> */
    private static $belongs_many_many = array(
        'FlickrPhotos' => FlickrPhoto::class
    );
}
