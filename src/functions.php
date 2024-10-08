<?php

use Slowlyo\OwlAdmin\Admin;


if (!function_exists('ManoImageControl')) {
    function ManoImageControl(string $name = '', string $label = '')
    {
        $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
        return amis()->ImageControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image");
    }
}
if (!function_exists('ManoFileControl')) {
    function ManoFileControl(string $name = '', string $label = '')
    {
        $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
        return amis()->FileControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-file")->
        startChunkApi("/mano-code/upload/{$disk}/upload_chunk_start")->
        chunkApi("/mano-code/upload/{$disk}/upload_chunk")->
        finishChunkApi("/mano-code/upload/{$disk}/upload_chunk_finish");
    }
}

if (!function_exists('ManoRichTextControl')) {
    function ManoRichTextControl(string $name = '', string $label = '')
    {
        $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
        return amis()->RichTextControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-rich");
    }
}

if (!function_exists('ManoWangEditorControl')) {
    function ManoWangEditorControl(string $name = '', string $label = '')
    {
        $prefix = (string)Admin::config('admin.route.prefix');
        $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
        return amis()->WangEditor($name, $label)->uploadImageServer("/{$prefix}/mano-code/upload/{$disk}/upload-rich")->uploadVideoServer("/{$prefix}/mano-code/upload/{$disk}/upload-rich");
    }
}

if(!function_exists('getStorageFilesystem')){
    function getStorageFilesystem(string $disk = null)
    {
        if(!(strlen(strval($disk))>=1)){
            $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
        }
        return \ManoCode\FileSystem\Http\Controllers\UploadController::getStorageFilesystem($disk);
    }
}

/**
 * 获取OSS直传组件
 */
if (!function_exists('ManoOssFileControl')) {
    function ManoOssFileControl(string $name = '', string $label = '', int $expAfter = 300, int $maxFileSize = 1048576000)
    {
        $diskConfig = \ManoCode\FileSystem\Http\Controllers\UploadController::getDiskConfig('oss');

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
        $callback_param = [
            'callbackUrl' => str_replace('http://','https://',url('/api/oss-callback')),
            'callbackBody' => 'filename=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}',
            'callbackBodyType' => 'application/x-www-form-urlencoded',
        ];
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);
        return amis()->FileControl($name, $label)->useChunk(false)->receiver([
            'url' => $host,
            'method' => 'post',
            'requestAdaptor' => "console.log('api------', api.data.get('file'));\r\nconsole.log('api------', api);\r\napi.data.set('key', '{$ossConfig->get('root')}' + (new Date()).getTime() + '_' + api.data.get('file').name);\r\nreturn api;",
            'adaptor' => '',
            'messages' => [],
            'dataType' => 'form-data',
            'data' => [
                'key' => '',
                'policy' => $policyString,
                'OSSAccessKeyId' => $config['accessKeyId'],
                'success_action_status' => 200,
                'x-oss-forbid-overwrite' => true,
                'signature' => $signature,
                'callback' => $base64_callback_body
            ],
        ]);
    }
}
