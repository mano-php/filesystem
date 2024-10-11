<?php

namespace ManoCode\FileSystem\Http\Controllers;

use Slowlyo\OwlAdmin\Renderers\Page;
use Slowlyo\OwlAdmin\Renderers\Form;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use ManoCode\FileSystem\Services\FilesystemConfigService;
use Slowlyo\OwlAdmin\Support\Cores\AdminPipeline;

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
            ->bulkActions('')
            ->columns([
                amis()->TableColumn('id', 'ID')->sortable(),
                amis()->TableColumn('name', '名称'),
                amis()->TableColumn('desc', '描述'),
				amis()->TableColumn('key', '引用标识'),
                amis()->SelectControl('driver', '驱动')->options(FilesystemConfigService::DRIVER_LISTS)->static(),
                amis()->TableColumn('state', '是否开启')->quickEdit(
                    amis()->SwitchControl()->mode('inline')->saveImmediately(true)->disabledOn('${id>4}')
                ),
                amis()->TableColumn('updated_at', __('admin.updated_at'))->set('type', 'datetime'),
                $this->rowActions(true)
            ]);

        return $this->baseList($crud);
    }
    /**
     * 操作列
     *
     * @param bool   $dialog
     * @param string $dialogSize
     *
     * @return \Slowlyo\OwlAdmin\Renderers\Operation
     */
    protected function rowActions(bool|array|string $dialog = false, string $dialogSize = '')
    {
        if (is_array($dialog)) {
            return amis()->Operation()->label(__('admin.actions'))->buttons($dialog);
        }

        return amis()->Operation()->label(__('admin.actions'))->buttons([
//            $this->rowResourceButton($dialog, 'full'),
            $this->rowEditButton($dialog, $dialogSize),
//            $this->rowDeleteButton(),
        ]);
    }

    /**
     * 资源管理
     *
     * @param bool|string $dialog     是否弹窗, 弹窗: true|dialog, 抽屉: drawer,
     * @param string      $dialogSize 弹窗大小, 默认: md, 可选值: xs | sm | md | lg | xl | full
     * @param string      $title      弹窗标题 & 按钮文字, 默认: 编辑
     *
     * @return \Slowlyo\OwlAdmin\Renderers\DialogAction|\Slowlyo\OwlAdmin\Renderers\LinkAction
     */
    protected function rowResourceButton(bool|string $dialog = false, string $dialogSize = 'xl', string $title = '')
    {
        $title  = "资源管理器";
        $action = amis()->LinkAction()->link(admin_url('/mano-code/${key}/resourc-manager'));

        if ($dialog) {
            $form = json_decode(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'resourc-manager.json'));

            if ($dialog === 'drawer') {
                $action = amis()->DrawerAction()->drawer(
                    amis()->Drawer()->title($title)->body($form)->size($dialogSize)
                );
            } else {
                $action = amis()->DialogAction()->dialog(
                    amis()->Dialog()->title($title)->body($form)->size($dialogSize)
                );
            }
        }

        $action->label($title)->level('link');

        return AdminPipeline::handle(AdminPipeline::PIPE_EDIT_ACTION, $action);
    }

    public function form($isEdit = false): Form
    {
        return $this->baseForm()->body([
            amis()->HiddenControl('id','ID'),
            amis()->TextControl('name', '名称')->disabledOn('${id<=4}')->required(),
            amis()->TextareaControl('desc', '描述')->disabledOn('${id<=4}')->required(),
            amis()->TextControl('key', '引用标识')->disabledOn('${id<=4}')->remark('建议用字母命名并且 以 . 作为分隔')->maxLength(50)->required(),
            amis()->SelectControl('driver', '驱动')->disabledOn('${id<=4}')->options(FilesystemConfigService::DRIVER_LISTS)->value('local')->required(),
            amis()->HiddenControl('state', '开启')->disabledOn('${id>4}')->value(0)->required(),
            amis()->TextControl('path_gen_template','路径生成规则')->remark("{date} : 代表年月日,例如:2024-10-11 <br />  <br /> {datetime} : 代表年月日时分秒,例如:2024-10-11 17:19:21 <br />  <br /> {time} : 代表当前时间戳,例如:1728638496 <br />  <br /> {uuid} : 代表生成UUID,例如:a1a65110-1c10-400b-8357-e6774793a5a5 <br />  <br /> {type} : 代表生成文件分类,例如:image <br />  <br /> {ext} : 代表生成文件后缀名,例如:jpg <br /> <br />  {rand(32)} : 代表生成32位的随机字符,例如:fnooT7QqvsCSLk3Y8jCo1QBJ12W5CFcC")->required(),
            amis()->TextControl('name_gen_template','文件名生成规则')->remark("{date} : 代表年月日,例如:2024-10-11 <br />  <br /> {datetime} : 代表年月日时分秒,例如:2024-10-11 17:19:21 <br />  <br /> {time} : 代表当前时间戳,例如:1728638496 <br />  <br /> {uuid} : 代表生成UUID,例如:a1a65110-1c10-400b-8357-e6774793a5a5 <br />  <br /> {type} : 代表生成文件分类,例如:image <br />  <br /> {ext} : 代表生成文件后缀名,例如:jpg <br /> <br />  {rand(32)} : 代表生成32位的随机字符,例如:fnooT7QqvsCSLk3Y8jCo1QBJ12W5CFcC")->required(),
            amis()->Divider()->title('详细配置')->titlePosition('center'),
            // 七牛云存储
            amis()->Container()->hiddenOn('${driver!="kodo"}')->body([
                amis()->TextControl('config.domains.default', '七牛域名')->required()->remark('你的七牛域名'),
                amis()->TextControl('config.domains.https', 'HTTPS域名')->required()->remark('可以使用你的七牛域名'),
                amis()->TextControl('config.domains.custom', '自定义域名')->required()->remark('可以使用你的七牛域名'),
                amis()->TextControl('config.access_key', 'ACCESS_KEY')->required(),
                amis()->TextControl('config.secret_key', 'SECRET_KEY')->required(),
                amis()->TextControl('config.bucket', 'BUCKET')->required(),
            ]),
            // OSS 阿里云对象存储
            amis()->Container()->hiddenOn('${driver!="oss"}')->body([
                amis()->TextControl('config.root', '前缀')->remark('根目录的话 直接为空，如果有开头不用以 / 开头'),
                amis()->TextControl('config.access_key', 'ACCESS_KEY')->required(),
                amis()->TextControl('config.secret_key', 'SECRET_KEY')->required(),
                amis()->TextControl('config.endpoint', 'ENDPOINT')->required()->remark('自定义域名，填写自定义域名。'),
                amis()->TextControl('config.bucket', 'BUCKET')->required(),
                amis()->SwitchControl('config.isCName', 'IS_CNAME')->trueValue(true)->falseValue(false)->value(false)->required(),
            ]),
            // COS 腾讯云对象存储
            amis()->Container()->hiddenOn('${driver!="cos"}')->body([
                amis()->TextControl('config.app_id', 'APP_ID')->required(),
                amis()->TextControl('config.secret_id', 'SECRET_ID')->required(),
                amis()->TextControl('config.secret_key', 'SECRET_KEY')->required(),

                amis()->GroupControl()->body([
                    amis()->TextControl('config.bucket', 'BUCKET')->remark('例如：demo-1325518132')->required(),
                    amis()->TextControl('config.prefix', '全局路径前缀')->remark('根目录的话 直接为空，如果有开头不用以 / 开头'),
                ]),
                amis()->TextControl('config.region', 'region')->remark('例如: ap-guangzhou')->required()->remark('自定义域名，填写自定义域名。'),
                amis()->TextControl('config.cdn', 'CND域名')->remark('可选，使用 CDN 域名时指定生成的 URL host'),
                amis()->GroupControl()->body([
                    amis()->SwitchControl('config.use_https', 'SSL')->remark('可选，是否使用 https，默认 开启')->trueValue(true)->falseValue(false)->value(true)->required(),
                    amis()->SwitchControl('config.signed_url', '签名链接')->remark('可选，如果 bucket 为私有访问请打开此项')->trueValue(true)->falseValue(false)->value(false)->required(),
                ]),
                amis()->TextControl('config.domain', '自定义域名')->remark('可选'),
            ]),
            // 本地
            amis()->Container()->hiddenOn('${driver!="local"}')->body([
                amis()->TextControl('config.root', '基础路径')->remark('基于base_path() 函数的参数。 例如:uploads')->required(),
                amis()->SwitchControl('config.throw', '是否抛出异常')->trueValue(true)->falseValue(false)->value(false)->required(),
            ]),
        ]);
    }
}
