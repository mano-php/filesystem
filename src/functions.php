<?php

use Illuminate\Support\Facades\Crypt;
use Slowlyo\OwlAdmin\Admin;


if (!function_exists('ManoImageControl')) {
    function ManoImageControl(string $name = '', string $label = '', ?string $disk = null, ?string $path_gen_template = null, ?string $name_gen_template = null)
    {
        if (strlen(strval($disk)) <= 0) {
            $disk = $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');;
        }
        return amis()->ImageControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}");
    }
}
if (!function_exists('ManoFileControl')) {
    function ManoFileControl(string $name = '', string $label = '', ?string $disk = null, ?string $path_gen_template = null, ?string $name_gen_template = null)
    {
        if (strlen(strval($disk)) <= 0) {
            $disk = $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');;
        }
        return amis()->FileControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-file?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}")->
        startChunkApi("/mano-code/upload/{$disk}/upload_chunk_start")->
        chunkApi("/mano-code/upload/{$disk}/upload_chunk")->
        finishChunkApi("/mano-code/upload/{$disk}/upload_chunk_finish?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}");
    }
}

if (!function_exists('ManoRichTextControl')) {
    function ManoRichTextControl(string $name = '', string $label = '', ?string $disk = null, ?string $path_gen_template = null, ?string $name_gen_template = null)
    {
        if (strlen(strval($disk)) <= 0) {
            $disk = $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');;
        }
        return amis()->RichTextControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-rich?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}");
    }
}

if (!function_exists('ManoWangEditorControl')) {
    function ManoWangEditorControl(string $name = '', string $label = '', ?string $disk = null, ?string $path_gen_template = null, ?string $name_gen_template = null)
    {
        $prefix = (string)Admin::config('admin.route.prefix');
        if (strlen(strval($disk)) <= 0) {
            $disk = $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');;
        }
        return amis()->WangEditor($name, $label)->uploadImageServer("/{$prefix}/mano-code/upload/{$disk}/upload-rich?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}")->uploadVideoServer("/{$prefix}/mano-code/upload/{$disk}/upload-rich?path_gen_template={$path_gen_template}&name_gen_template={$name_gen_template}");
    }
}

if (!function_exists('getStorageFilesystem')) {
    function getStorageFilesystem(?string $disk = null)
    {
        if (strlen(strval($disk)) <= 0) {
            $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');
        }
        return \ManoCode\FileSystem\Http\Controllers\UploadController::getStorageFilesystem($disk);
    }
}

/**
 * 获取OSS直传组件
 */
if (!function_exists('ManoOssFileControl')) {
    function ManoOssFileControl(string $name = '', string $label = '', ?string $disk = null, int $expAfter = 300, int $maxFileSize = 1048576000, bool $https = true)
    {
        if (strlen(strval($disk)) <= 0) {
            $disk = 'oss';
        }
        if (!($diskConfig = \ManoCode\FileSystem\Http\Controllers\UploadController::getDiskConfig($disk))) {
            throw new \Exception("存储器:{$disk} 不存在");
        }
        $ossConfig = collect(json_decode($diskConfig->getAttribute('config'), true));
        $config = array(
            'dir' => $ossConfig->get('root'), // 上传目录
            'bucket' => $ossConfig->get('bucket'),// Bucket 名称
            'accessKeyId' => $ossConfig->get('access_key'),// 安全受限的 Access key ID
            'accessKeySecret' => $ossConfig->get('secret_key'),// Access key secret
            'expAfter' => $expAfter, // 签名失效时间，秒
            'maxSize' => $maxFileSize // 文件最大尺寸
        );
        $host = 'https://' . $ossConfig->get('bucket') . '.' . $ossConfig->get('endpoint');
        $now = strtotime('now');
        $expireTime = $now + $config['expAfter'];
        $expiration = gmdate('Y-m-d\TH:i:s\Z', $expireTime);
        $policy = array(
            "expiration" => $expiration,
            "conditions" => array(
                array("content-length-range", 0, $config['maxSize']),
                array("starts-with", "\$key", $config['dir'])
            )
        );
        $policyString = base64_encode(json_encode($policy));
        $signature = base64_encode(hash_hmac('sha1', $policyString, $config['accessKeySecret'], true));
        $tokenData = array(
            "signature" => $signature,
            "policy" => $policyString,
//            "host" => $host,
            "accessid" => $config['accessKeyId'],
            "expire" => $expireTime,
            "dir" => $config['dir']
        );
        if ($https) {
            $callback_api = str_replace('http://', 'https://', url('/api/oss-callback'));
        } else {
            $callback_api = url('/api/oss-callback');
        }
        $callback_param = [
            'callbackUrl' => $callback_api,
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        /**
         * 获取配置
         */
        $makeConfig = Crypt::encryptString(json_encode([
            "disk" => $disk,
            "expAfter" => $expAfter,
            "maxFileSize" => $maxFileSize,
            "https" => $https
        ]));
        $prefix = (string)Admin::config('admin.route.prefix');
        return amis()->FileControl($name, $label)->useChunk(false)->receiver([
            'url' => $host,
            'method' => 'post',
            'requestAdaptor' => "
                var make_config_data = api.data.get('make_config_data');
                var filename = api.data.get('file').name;

                // 使用 URLSearchParams 构建查询参数
                var params = new URLSearchParams({
                    make_config_data: make_config_data,
                    filename: filename
                });

//                console.log('api',api);

                // 构建完整的 API 地址
                var signatureApi = `/{$prefix}/mano-code/upload/get_oss_token?\${params.toString()}`;

//                console.log(signatureApi);

                try{
                    return fetch(signatureApi, {
                      method: 'GET',
                      headers: {
                          'Authorization': 'Bearer ' + window.localStorage.getItem('admin-api-token')
                      }
                  })
                  .then(response => response.json())
                  .then(data => {
                        console.log(data);
                      // 更新 signature 和 callback
                      api.data.set('signature', data.signature);
                      api.data.set('callback', data.callback);
                      api.data.set('OSSAccessKeyId', data.OSSAccessKeyId);
                      api.data.set('policy', data.policy);
                      // 设置上传文件的 key
                      api.data.set('key', data.key);
                      // 返回更新后的 api 对象
                      return api;
                  })
                  .catch(error => {
                      console.error('Error fetching signature:', error);
                      throw new Error('Failed to fetch signature');
                  });
                }catch(err){
                    console.log(err);
                }
            ",
            'messages' => [],
            'dataType' => 'form-data',
            'data' => [
                /**
                 * 用于获取配置的（防篡改）
                 */
                'make_config_data' => $makeConfig,
                'key' => '',
                'policy' => '',
                'OSSAccessKeyId' => '',
                'success_action_status' => 200,
                'x-oss-forbid-overwrite' => true,
                'signature' => '',
                'callback' => ''
            ],
        ]);
    }
}
