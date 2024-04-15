<?php

namespace Uupt\FileSystem\Services;

use Uupt\FileSystem\Models\FilesystemConfig;
use Slowlyo\OwlAdmin\Services\AdminService;

/**
 * 文件系统
 *
 * @method FilesystemConfig getModel()
 * @method FilesystemConfig|\Illuminate\Database\Query\Builder query()
 */
class FilesystemConfigService extends AdminService
{
    protected string $modelName = FilesystemConfig::class;

    /**
     * saving 钩子 (执行于新增/修改前)
     *
     * 可以通过判断 $primaryKey 是否存在来判断是新增还是修改
     *
     * @param $data
     * @param $primaryKey
     *
     * @return void
     */
    public function saving(&$data, $primaryKey = '')
    {
        if(isset($data['config']) && is_array($data['config']) && count($data['config'])>=1){
            $data['config'] = json_encode($data['config']);
        }
    }
}
