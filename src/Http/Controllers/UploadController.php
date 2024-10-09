<?php

namespace ManoCode\FileSystem\Http\Controllers;

use Illuminate\Filesystem\Filesystem;
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
        if(strlen(strval($disk))<=0){
            /**
             * 读取系统默认启用的配置
             */
            $disk = \ManoCode\FileSystem\Models\FilesystemConfig::query()->where('state', 1)->value('key');
            if(strlen(strval($disk))<=0){
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

        $fileName = self::generateUniqueFileName($filesystem, $type, $ext);

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

        return  Storage::build($diskConfigBody);
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

    private static function generateUniqueFileName($filesystem, $type, $ext)
    {
        do {
            $fileName = Admin::config('admin.upload.directory.' . $type) . '/' . Str::random(50) . ".{$ext}";
        } while ($filesystem->exists($fileName));

        return $fileName;
    }
}
