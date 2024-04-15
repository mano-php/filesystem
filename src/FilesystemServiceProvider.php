<?php

namespace Uupt\FileSystem;

use Slowlyo\OwlAdmin\Extend\Extension;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;
use Slowlyo\OwlDict\Models\AdminDict as AdminDictModel;

class FilesystemServiceProvider extends ServiceProvider
{
    public function install()
    {
        parent::install();
        $this->installDict();
    }
    protected function installDict()
    {
        $dicts = [
            [
                'key' => 'uupt.filesystem.driver',
                'value' => '采购状态',
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
        require_once(__DIR__.DIRECTORY_SEPARATOR.'functions.php');
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
