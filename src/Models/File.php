<?php

namespace Ugly\Base\Models;

use Illuminate\Database\Eloquent\Model;
use Ugly\Base\Traits\SearchModel;

class File extends Model
{
    use SearchModel;

    protected $guarded = [];
}
