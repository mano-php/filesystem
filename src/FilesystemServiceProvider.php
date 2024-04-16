<?php

namespace Uupt\FileSystem;

use Slowlyo\OwlAdmin\Extend\Extension;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;
use Slowlyo\OwlDict\Models\AdminDict as AdminDictModel;
use Uupt\FileSystem\Models\FilesystemConfig;

class FilesystemServiceProvider extends ServiceProvider
{
    protected $menu = [
        [
            'parent'    => '系统管理',
            'title'     => '文件系统',
            'url'       => '/filesystem_config',
            'url_type'  => '1',
            'icon'      => 'ant-design:file-zip-outlined',
        ]
    ];
    public function install()
    {
        parent::install();
        $this->installDict();
        if(!FilesystemConfig::query()->where('key','local')->first()){
            FilesystemConfig::query()->insert([
                'name'=>'默认存储',
                'desc'=>'系统默认本地存储',
                'key'=>'local',
                'driver'=>'local',
                'config'=>json_encode([
                    'driver'=>'local',
                    'root'=>'uploads',
                    'throw'=>false
                ]),
                'created_at'=>date('Y-m-d H:i:s'),
                'updated_at'=>date('Y-m-d H:i:s'),
            ]);
        }
    }
    protected function installDict()
    {
        $dicts = [
            [
                'key' => 'uupt.filesystem.driver',
                'value' => '文件系统驱动',
                'keys' => [
                    ['key' => 'local', 'value' => '本地存储'],
                    ['key' => 'kodo', 'value' => '七牛云kodo'],
                    ['key' => 'cos', 'value' => '腾讯云COS'],
                    ['key' => 'oss', 'value' => '阿里云OSS'],
                ]
            ],
        ];
        foreach ($dicts as $dict) {
            $dictModel = AdminDictModel::query()->where('key', $dict['key'])->first();
            if (!$dictModel) {
                $dictModel = new AdminDictModel();
                $dictModel->value = $dict['value'];
                $dictModel->enabled = 1;
                $dictModel->key = $dict['key'];
                $dictModel->save();
            }
            foreach ($dict['keys'] as $value) {
                $dictValueModel = AdminDictModel::query()->where('parent_id', $dictModel->id)->where('key', $value['key'])->first();
                if (!$dictValueModel) {
                    $dictValueModel = new AdminDictModel();
                    $dictValueModel->parent_id = $dictModel->id;
                    $dictValueModel->key = $value['key'];
                    $dictValueModel->value = $value['value'];
                    $dictValueModel->enabled = 1;
                    $dictValueModel->save();
                }
            }
        }
    }
    public function boot()
    {
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'functions.php');
        if (Extension::tableExists()) {
            $this->autoRegister();
            $this->init();
        }
    }


	public function settingForm()
	{
	    return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(true),
	    ]);
	}
}
