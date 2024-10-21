<?php

namespace ManoCode\FileSystem\Http\Controllers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use ManoCode\FileSystem\Models\FilesystemConfig;

/**
 * 上传文件工具类
 */
class UploadController extends AdminController
{

    public function uploadImage(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        try {
            [$basePath, $fileName] = $this->upload('image');
        } catch (\Throwable $throwable) {
            return $this->response()->fail(__('admin.upload_file_error') . ":{$throwable->getMessage()}");
        }
        return $this->response()->success(['value' => $basePath . $fileName]);
    }

    public function uploadFile(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        try {
            [$basePath, $fileName] = $this->upload('file');
        } catch (\Throwable $throwable) {
            return $this->response()->fail(__('admin.upload_file_error') . ":{$throwable->getMessage()}");
        }
        return $this->response()->success(['value' => $basePath . $fileName]);
    }

    public function uploadRich(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        $fromWangEditor = false;
        $file = request()->file('file');
        $key = 'file';
        $type = 'file';

        if (!$file) {
            $fromWangEditor = true;
            $file = request()->file('wangeditor-uploaded-image');
            $key = 'wangeditor-uploaded-image';
            $type = 'image';
            if (!$file) {
                $file = request()->file('wangeditor-uploaded-video');
                $key = 'wangeditor-uploaded-video';
                $type = 'video';
            }
        }
        try {
            [$basePath, $fileName] = $this->upload($type, $key);
            if ($fromWangEditor) {
                return $this->response()->additional(['errno' => 0])->success(['url' => $basePath . $fileName]);
            } else {
                return $this->response()->success(['value' => $basePath . $fileName]);
            }
        } catch (\Throwable $throwable) {
            return $this->response()->fail(__('admin.upload_file_error') . ":{$throwable->getMessage()}");
        }
    }

    /**
     * @param $type
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function upload($type = 'file', $key = 'file'): array
    {
        $disk = request()->route('disk');
        if (strlen(strval($disk)) <= 0) {
            /**
             * 读取系统默认启用的配置
             */
            $disk = $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->where('id', '<=', 4)->value('key');;
            if (strlen(strval($disk)) <= 0) {
                throw new \Exception('系统未配置默认存储');
            }
        }
        [$basePath, $fileName] = self::doUpload($type, $disk, $key);
        return [$basePath, $fileName];
    }


    public function chunkUploadStart()
    {
        $uploadId = Str::uuid();

        cache()->put($uploadId, [], 600);

        app('filesystem')->makeDirectory(storage_path('app/public/chunk/' . $uploadId));

        return $this->response()->success(compact('uploadId'));
    }

    public function chunkUpload()
    {
        $uploadId = request('uploadId');
        $partNumber = request('partNumber');
        $file = request()->file('file');

        $path = 'chunk/' . $uploadId;

        $file->storeAs($path, $partNumber, 'public');

        $eTag = md5(Storage::disk('public')->get($path . '/' . $partNumber));

        return $this->response()->success(compact('eTag'));
    }

    public function chunkUploadFinish()
    {
        $fileName = request('filename');
        $partList = request('partList');
        $uploadId = request('uploadId');
        $type = request('t');

        $ext = pathinfo($fileName, PATHINFO_EXTENSION);
        $path = $type . '/' . $uploadId . '.' . $ext;
        $fullPath = storage_path('app/public/' . $path);

        $dir = dirname($fullPath);
        if (!is_dir($dir)) {
            app('filesystem')->makeDirectory($dir);
        }

        for ($i = 0; $i < count($partList); $i++) {
            $partNumber = $partList[$i]['partNumber'];
            $eTag = $partList[$i]['eTag'];

            $partPath = 'chunk/' . $uploadId . '/' . $partNumber;

            $partETag = md5(Storage::disk('public')->get($partPath));

            if ($eTag != $partETag) {
                return $this->response()->fail('分片上传失败');
            }

            file_put_contents($fullPath, Storage::disk('public')->get($partPath), FILE_APPEND);
        }

        clearstatcache();

        app('files')->deleteDirectory(storage_path('app/public/chunk/' . $uploadId));
        try {
            $disk = request()->route('disk', 'local');
            [$basePath, $fileName] = self::uploadDoUpload($fullPath, 'file', $disk);
        } catch (\Throwable $throwable) {
            return $this->response()->fail(__('admin.upload_file_error') . ":{$throwable->getMessage()}");
        }

        return $this->response()->success(['value' => $basePath . $fileName]);
    }


    /**
     * @param string $type
     * @param string $disk
     * @return array
     * @throws \Exception
     */
    public static function doUpload($type = 'file', $disk = 'local', $key = 'file')
    {
        $file = request()->file($key);
        if (!$file) {
            throw new \Exception('上传失败');
        }

        $originFile = $file->getRealPath();
        $ext = $file->getClientOriginalExtension();

        return self::handleUpload($originFile, $type, $disk, $ext);
    }

    public function uploadDoUpload($originFile, $type = 'file', $disk = 'local')
    {
        $ext = pathinfo($originFile, PATHINFO_EXTENSION);
        return self::handleUpload($originFile, $type, $disk, $ext);
    }

    /**
     * @param string $originFile
     * @param string $type
     * @param string $disk
     * @param string $ext
     * @return array
     */
    private static function handleUpload($originFile, $type, $disk, $ext)
    {
        $diskConfig = self::getDiskConfig($disk);
        $diskConfigBody = self::processDiskConfig($diskConfig);
        $basePath = self::getBasePath($diskConfigBody);

        $filesystem = Storage::build($diskConfigBody);

        $fileName = self::generateUniqueFileName($filesystem, $type, $ext, $disk, $originFile);

        $filesystem->put($fileName, file_get_contents($originFile));

        return [$basePath, $fileName, $ext];
    }

    public static function getDiskConfig($disk)
    {
        return FilesystemConfig::query()->where('key', $disk)->first();
    }

    /**
     * 获取当前存储器
     * @param $disk
     * @return \Illuminate\Contracts\Filesystem\Filesystem
     */
    public static function getStorageFilesystem(string $disk = 'local')
    {
        $diskConfig = self::getDiskConfig($disk);
        $diskConfigBody = self::processDiskConfig($diskConfig);
        $basePath = self::getBasePath($diskConfigBody);

        return Storage::build($diskConfigBody);
    }

    private static function processDiskConfig($diskConfig)
    {
        $diskConfigBody = is_string($diskConfig->getAttribute('config'))
            ? json_decode($diskConfig->getAttribute('config'), true)
            : $diskConfig->getAttribute('config');

        $diskConfigBody['driver'] = $diskConfig->getAttribute('driver');

        switch ($diskConfigBody['driver']) {
            case 'local':
                $diskConfigBody['base_path'] = base_path($diskConfigBody['root']);
                $diskConfigBody['throw'] = boolval($diskConfigBody['throw']);
                break;
            case 'oss':
                $diskConfigBody['root'] = strval($diskConfigBody['root']);
                break;
            case 'cos':
                if (!(isset($diskConfigBody['domain']) && strval($diskConfigBody['domain']) >= 1)) {
                    $diskConfigBody['domain'] = "{$diskConfigBody['bucket']}.cos.{$diskConfigBody['region']}.myqcloud.com";
                }
                if (!(isset($diskConfigBody['prefix']) && strval($diskConfigBody['prefix']) >= 1)) {
                    $diskConfigBody['prefix'] = '/';
                }
                break;
            case 'kodo':
                $diskConfigBody['driver'] = 'qiniu';
                break;
        }

        return $diskConfigBody;
    }

    private static function getBasePath($diskConfigBody)
    {
        switch ($diskConfigBody['driver']) {
            case 'local':
                return str_replace(base_path(), '', $diskConfigBody['root']) . '/';
            case 'oss':
                return $diskConfigBody['isCName']
                    ? "{$diskConfigBody['endpoint']}/"
                    : "https://{$diskConfigBody['bucket']}.{$diskConfigBody['endpoint']}/{$diskConfigBody['root']}";
            case 'cos':
                return "https://{$diskConfigBody['domain']}/";
            case 'qiniu':
                return "https://{$diskConfigBody['domains']['https']}/";
            default:
                return '/';
        }
    }

    /**
     * 生成文件目录+文件名
     * @param $filesystem
     * @param $type
     * @param $ext
     * @param $disk
     * @return string
     */
    private static function generateUniqueFileName($filesystem, $type, $ext, $disk, $originFile = null)
    {
        /**
         * 目录生成规则
         */
        if (strlen(strval(request()->input('path_gen_template', ''))) >= 1) {
            $path_gen_template = strval(request()->input('path_gen_template', ''));
        } else {
            $path_gen_template = FilesystemConfig::query()->where('key', $disk)->value('path_gen_template');
        }
        /**
         * 文件名生成规则
         */
        if (strlen(strval(request()->input('name_gen_template', ''))) >= 1) {
            $name_gen_template = strval(request()->input('name_gen_template', ''));
        } else {
            $name_gen_template = FilesystemConfig::query()->where('key', $disk)->value('name_gen_template');
        }
        $replacements = [
            '{date}' => date('Y-m-d'),
            '{datetime}' => date('Y-m-d-H-i-s'),
            '{time}' => time(),
            '{uuid}' => Str::uuid(),
            '{type}' => $type,
            '{ext}' => $ext,
            '{ext}' => $ext,
        ];
        if (file_exists($originFile)) {
            $replacements['{hash}'] = md5(file_get_contents($originFile));
        }

        // 查找随机长度
        $random_length = preg_match('/{rand\((\d+)\)}/', $path_gen_template, $matches) ? $matches[1] : 32;

        // 添加随机字符串替换到替换数组
        $replacements['{rand(' . $random_length . ')}'] = Str::random($random_length);

        // 执行替换
        $path_gen_template = str_replace(array_keys($replacements), array_values($replacements), $path_gen_template);

        // 查找随机长度
        $random_length = preg_match('/\{rand\((\d+)\)}/', $name_gen_template, $matches) ? $matches[1] : 32;
        // 添加随机字符串替换到替换数组
        $replacements['{rand(' . $random_length . ')}'] = Str::random($random_length);

        // 执行替换
        $name_gen_template = str_replace(array_keys($replacements), array_values($replacements), $name_gen_template);
        /**
         * 拼接文件名
         */
        $fileName = rtrim($path_gen_template, '/') . '/' . ltrim($name_gen_template, '/');

        return $fileName;
    }

    /**
     * 获取OSS Token
     * @return array
     * @throws \Exception
     */
    public function getOssToken()
    {
        try {
            $requestConfig = json_decode(Crypt::decryptString(request()->input('make_config_data')), true);
        } catch (\Throwable $throwable) {
            return [];
        }
        $disk = $requestConfig['disk'];
        $expAfter = $requestConfig['expAfter'];
        $maxFileSize = $requestConfig['maxFileSize'];
        $https = $requestConfig['https'];
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
        $fileName = request('filename', '');
        return [
            'key' => rtrim($ossConfig->get('root'), '/') . '/' . self::generateUniqueFileName(null, 'file', pathinfo($fileName, PATHINFO_EXTENSION), $disk),
            'policy' => $policyString,
            'OSSAccessKeyId' => $config['accessKeyId'],
            'success_action_status' => 200,
            'x-oss-forbid-overwrite' => true,
            'signature' => $signature,
            'callback' => $base64_callback_body
        ];
    }
}
