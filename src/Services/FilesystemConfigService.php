<?php

namespace ManoCode\FileSystem\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use ManoCode\FileSystem\Models\FilesystemConfig;
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
     * 快速编辑单条
     *
     * @param $data
     *
     * @return bool
     */
    public function quickEditItem($data)
    {
        if($data['status']){
            FilesystemConfig::query()->where('id','<>',$data)->update(['status'=>0]);
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        return $this->update(Arr::pull($data, $this->primaryKey()), $data);
    }
    /**
     * 排序
     *
     * @param $query
     *
     * @return void
     */
    public function sortable($query)
    {
        $query->orderBy('id','ASC');
    }
    /**
     * 列表 获取数据
     *
     * @return array
     */
    public function list()
    {
        $query = $this->listQuery();

        $list  = $query->paginate(request()->input('perPage', 20));
        $items = $list->items();
        $total = $list->total();
        foreach ($items as $key=>$item){
            $items[$key]['status_name'] = $item['status'] == 1?'开启':'关闭';
            $items[$key]['config'] = json_decode($item['config'],true);
        }

        return compact('items', 'total');
    }
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

    /**
     * 编辑 获取数据
     *
     * @param $id
     *
     * @return Model|\Illuminate\Database\Eloquent\Collection|Builder|array|null
     */
    public function getEditData($id)
    {
        $model = $this->getModel();

        $hidden = collect([$model->getCreatedAtColumn(), $model->getUpdatedAtColumn()])
            ->filter(fn($item) => $item !== null)
            ->toArray();

        $query = $this->query();

        $this->addRelations($query, 'edit');
        $detail = $query->find($id)->makeHidden($hidden);
        if(is_string($detail['config'])){
            $detail['config'] = json_decode($detail['config'],true);
        }
        return $detail;
    }
}
