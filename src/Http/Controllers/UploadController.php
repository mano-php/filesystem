<?php

namespace Uupt\FileSystem\Http\Controllers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Controllers\AdminController;
use Uupt\FileSystem\Models\FilesystemConfig;

/**
 * 上传文件工具类
 */
class UploadController extends AdminController
{

    public function uploadImage(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        return $this->upload('image');
    }


    public function uploadFile(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        return $this->upload();
    }

    public function uploadRich(): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        $fromWangEditor = false;
        $file = request()->file('file');

        if (!$file) {
            $fromWangEditor = true;
            $file = request()->file('wangeditor-uploaded-image');
            if (!$file) {
                $file = request()->file('wangeditor-uploaded-video');
            }
        }

        if (!$file) {
            return $this->response()->additional(['errno' => 1])->fail(__('admin.upload_file_error'));
        }

        $path = $file->store(Admin::config('admin.upload.directory.rich'), Admin::config('admin.upload.disk'));

        $link = Storage::disk(Admin::config('admin.upload.disk'))->url($path);

        if ($fromWangEditor) {
            return $this->response()->additional(['errno' => 0])->success(['url' => $link]);
        }

        return $this->response()->additional(compact('link'))->success(compact('link'));
    }

    /**
     * @param $type
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    protected function upload($type = 'file'): \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
    {
        $basePath = '/';
        $disk = request()->route('disk', 'local');
        $diskConfig = FilesystemConfig::query()->where('key', $disk)->first();
        $file = request()->file('file');

        if (!$file) {
            return $this->response()->fail(__('admin.upload_file_error'));
        }
        if (is_string($diskConfig->getAttribute('config'))) {
            $diskConfigBody = json_decode($diskConfig->getAttribute('config'), true);
        }
        $diskConfigBody['driver'] = $diskConfig->getAttribute('driver');
        // 本地路径处理
        if ($diskConfig->getAttribute('driver') === 'local') {
            $diskConfigBody['base_path'] = base_path($diskConfigBody['base_path']);
            $basePath = str_replace(base_path(), '', $diskConfigBody['base_path']) . '/';
            $diskConfigBody['throw'] = boolval($diskConfigBody['throw']);
        }
        // OSS 参数修正
        if ($diskConfig->getAttribute('driver') === 'oss') {
            $diskConfigBody['root'] = strval($diskConfigBody['root']);
            if (!$diskConfigBody['isCName']) {
                $basePath = "https://{$diskConfigBody['bucket']}.{$diskConfigBody['endpoint']}/";
            } else {
                $basePath = "{$diskConfigBody['endpoint']}/";
            }
        }
        if ($diskConfig->getAttribute('driver') === 'cos') {
            if (!(isset($diskConfigBody['domain']) && strval($diskConfigBody['domain']) >= 1)) {
                $diskConfigBody['domain'] = "{$diskConfigBody['bucket']}.cos.{$diskConfigBody['region']}.myqcloud.com";
            }
            $basePath = "https://{$diskConfigBody['domain']}/";
            if (!(isset($diskConfigBody['prefix']) && strval($diskConfigBody['prefix']) >= 1)) {
                $diskConfigBody['prefix'] = '/';
            }
        }
        $filesystem = Storage::build($diskConfigBody);
        do {
            $fileName = Admin::config('admin.upload.directory.' . $type) . '/' . Str::random(50) . ".{$file->getClientOriginalExtension()}";

        } while ($filesystem->exists($fileName));

        $filesystem->put($fileName, file_get_contents($file->getRealPath()));

        return $this->response()->success(['value' => $basePath . $fileName]);
    }
}
