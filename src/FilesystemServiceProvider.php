<?php

namespace ManoCode\FileSystem;

use ManoCode\CustomExtend\Extend\ManoCodeServiceProvider;
use ManoCode\FileSystem\Models\FilesystemConfig;

/**
 * 文件存储系统服务提供者
 */
class FilesystemServiceProvider extends ManoCodeServiceProvider
{

    protected $menu = [
        [
            'parent' => '系统管理',
            'title' => '文件系统',
            'url' => '/filesystem_config',
            'url_type' => '1',
            'icon' => 'ant-design:file-zip-outlined',
        ],
    ];
    protected $dict = [
        [
            'key' => 'filesystem.driver',
            'value' => '文件系统驱动',
            'keys' => [
                [
                    'key' => 'local',
                    'value' => '本地存储'
                ],
                [
                    'key' => 'kodo',
                    'value' => '七牛云kodo'
                ],
                [
                    'key' => 'cos',
                    'value' => '腾讯云COS'
                ],
                [
                    'key' => 'oss',
                    'value' => '阿里云OSS'
                ]
            ]
        ]
    ];
    protected $permission = [
        [
            'name'=>'文件上传',
            'slug'=>'file-upload-api',
            'method'=>[],// 空则代表ANY
            'path'=>['/mano-code/upload/*'],// 授权接口
            'parent'=>'',// 父级权限slug字段
        ]
    ];


    public function boot()
    {
        parent::boot();
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'functions.php');
    }

    public function install()
    {
        if (!is_dir(base_path() . '/public/uploads/')) {
            mkdir(base_path() . '/public/uploads/', 0777, true);
        }
        parent::install();
        if (!FilesystemConfig::query()->where('key', 'local')->first()) {
            FilesystemConfig::query()->insert([
                'name' => '默认存储',
                'desc' => '系统默认本地存储',
                'key' => 'local',
                'driver' => 'local',
                'config' => json_encode([
                    'driver' => 'local',
                    'root' => 'uploads',
                    'throw' => false
                ]),
                'state' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        if (!FilesystemConfig::query()->where('key', 'kodo')->first()) {
            FilesystemConfig::query()->insert([
                'name' => '七牛云存储',
                'desc' => '存储在七牛云，前往开通七牛云存储服务',
                'key' => 'kodo',
                'driver' => 'kodo',
                'config' => json_encode([
                    'bucket' => ''
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        if (!FilesystemConfig::query()->where('key', 'oss')->first()) {
            FilesystemConfig::query()->insert([
                'name' => '阿里云OSS',
                'desc' => '存储在阿里云，请前往阿里云开通存储服务',
                'key' => 'oss',
                'driver' => 'oss',
                'config' => json_encode([
                    'bucket' => ''
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        if (!FilesystemConfig::query()->where('key', 'cos')->first()) {
            FilesystemConfig::query()->insert([
                'name' => '腾讯云COS',
                'desc' => '存储在腾讯云，请前往腾讯云开通存储服务',
                'key' => 'cos',
                'driver' => 'cos',
                'config' => json_encode([
                    'bucket' => ''
                ]),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function settingForm()
    {
        return $this->baseSettingForm()->body([
//            TextControl::make()->name('value')->label('Value')->required(true),
        ]);
    }
}
