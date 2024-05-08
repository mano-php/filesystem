<?php

namespace ManoCode\FileSystem\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Slowlyo\OwlAdmin\Models\BaseModel as Model;

/**
 * 文件系统
 */
class FilesystemConfig extends Model
{
    use SoftDeletes;

    protected $table = 'filesystem_config';
    
}
