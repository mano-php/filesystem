<?php

namespace ManoCode\FileSystem;

use Iidestiny\Flysystem\Oss\OssAdapter;
use Illuminate\Filesystem\FilesystemAdapter;
use League\Flysystem\Filesystem;
use ManoCode\CustomExtend\Extend\ManoCodeServiceProvider;
use ManoCode\FileSystem\Models\FilesystemConfig;
use Illuminate\Support\Facades\Route;

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
        ],
    ];


    public function boot()
    {
        app('filesystem')->extend('oss', function ($app, $config) {
            $root = $config['root'] ?? null;
            $buckets = $config['buckets'] ?? [];

            $adapter = new \ManoCode\FileSystem\Adapter\OssAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['endpoint'],
                $config['bucket'],
                $config['isCName'],
                $root,
                $buckets
            );

            $adapter->setCdnUrl($config['url'] ?? null);

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
        parent::boot();
        Route::any('/api/oss-callback', function () {
            $data = request()->all();
            if (isset($data['filename'])){
                $diskConfig = \ManoCode\FileSystem\Http\Controllers\UploadController::getDiskConfig('oss');
                $ossConfig = collect(json_decode($diskConfig->getAttribute('config'), true));
                $data['data']['value'] = 'https://' . $ossConfig->get('bucket') . '.' . $ossConfig->get('endpoint').'/'.$data['filename'];
            }
            return count($data)<=0?new ArrayObject():$data;
        });
        require_once(__DIR__ . DIRECTORY_SEPARATOR . 'functions.php');
    }

    public function install()
    {
        $this->publishable();
        if (!is_dir(base_path('uploads'))) {
            mkdir(base_path('uploads'), 0777, true);
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
