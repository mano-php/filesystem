<?php

namespace Uupt\FileSystem\Http\Controllers;

use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Uupt\FileSystem\Services\FilesystemConfigService;

/**
 * 文件系统
 *
 * @property FilesystemConfigService $service
 */
class FilesystemConfigController extends AdminController
{
    protected string $serviceName = FilesystemConfigService::class;

    public function list(): Page
    {
        $crud = $this->baseCRUD()
            ->filterTogglable(false)
			->headerToolbar([
				$this->createButton(true),
				...$this->baseHeaderToolBar()
			])
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable(),
				amis()->TableColumn('name', '名称'),
				amis()->TableColumn('key', '引用标识'),
				amis()->TableColumn('driver', '驱动'),
                amis()->TableColumn('desc', '描述'),
				amis()->TableColumn('created_at', __('admin.created_at'))->set('type', 'datetime')->sortable(),
				amis()->TableColumn('updated_at', __('admin.updated_at'))->set('type', 'datetime')->sortable(),
                $this->rowActions(true)
            ]);

        return $this->baseList($crud);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->TextControl('name', '名称')->required(),
			amis()->TextareaControl('desc', '描述')->required(),
			amis()->TextControl('key', '引用标识')->remark('建议用字母命名并且 以 . 作为分隔')->maxLength(50)->required(),
			amis()->SelectControl('driver', '驱动')->options(admin_dict()->getOptions('uupt.filesystem.driver'))->value('local')->required(),
            // OSS
            amis()->Container()->hiddenOn('${driver!="oss"}')->body([
                amis()->HiddenControl('config.driver', 'driver')->value('oss')->required(),
                amis()->TextControl('config.root', '前缀')->remark('根目录的话 直接为空，如果有开头不用以 / 开头'),
                amis()->TextControl('config.access_key', 'ACCESS_KEY')->required(),
                amis()->TextControl('config.secret_key', 'SECRET_KEY')->required(),
                amis()->TextControl('config.endpoint', 'ENDPOINT')->required()->remark('自定义域名，填写自定义域名。'),
                amis()->TextControl('config.bucket', 'BUCKET')->required(),
                amis()->SwitchControl('config.isCName', 'IS_CNAME')->trueValue(true)->falseValue(false)->value(false)->required(),
            ]),
            // 本地
            amis()->Container()->hiddenOn('${driver!="local"}')->body([
                amis()->HiddenControl('config.driver', 'driver')->value('local')->required(),
                amis()->TextControl('config.root', '基础路径')->remark('基于base_path() 函数的参数 、可直接 写 例如:upload')->required(),
                amis()->SwitchControl('config.throw', '是否抛出异常')->trueValue(true)->falseValue(false)->value(false)->required(),
            ]),
        ]);
    }

    public function detail(): Form
    {
        return $this->baseDetail()->body([
            amis()->TextControl('id', 'ID')->static(),
			amis()->TextControl('name', '名称')->static(),
			amis()->TextareaControl('desc', '描述')->static(),
            amis()->TextControl('key', '引用标识')->static(),
			amis()->SelectControl('driver', '驱动')->options(admin_dict()->getOptions('filesystem.driver'))->static(),
			amis()->TextControl('config', '配置内容')->static(),
			amis()->TextControl('created_at', __('admin.created_at'))->static(),
			amis()->TextControl('updated_at', __('admin.updated_at'))->static()
        ]);
    }
}
