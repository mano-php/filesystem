<?php

namespace Uupt\FileSystem\Http\Controllers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Slowlyo\OwlAdmin\Admin;
use Slowlyo\OwlAdmin\Controllers\AdminController;

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
        $file           = request()->file('file');

        if (!$file) {
            $fromWangEditor = true;
            $file           = request()->file('wangeditor-uploaded-image');
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
        $file = request()->file('file');

        if (!$file) {
            return $this->response()->fail(__('admin.upload_file_error'));
        }
        $filesystem = Storage::createLocalDriver([
            'driver' => 'local',
            'root' => base_path('public'),
            'throw' => false,
        ]);
        do{
            $fileName = Admin::config('admin.upload.directory.' . $type).'/'.Str::random(50).".{$file->getClientOriginalExtension()}";

        }while($filesystem->exists($fileName));

        $filesystem->move($file->getRealPath(),$fileName);

        return $this->response()->success(['value' => $fileName]);
    }
}
