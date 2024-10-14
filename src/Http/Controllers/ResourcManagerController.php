<?php

namespace ManoCode\FileSystem\Http\Controllers;

use Darabonba\GatewaySpi\Models\InterceptorContext\request;
use Slowlyo\OwlAdmin\Controllers\AdminController;

/**
 *
 */
class ResourcManagerController extends AdminController
{
    /**
     * 获取文件和目录列表
     * @return void
     */
    public function files()
    {
        // 获取文件系统实例
        $filesystem = getStorageFilesystem(request()->route('disk', 'local'));
        $fileInfoList = [];
        $path = request()->input('path', null);
        if (strlen(strval($path)) <= 0) {
            $path = null;
        }

        // 获取目录列表
        $directories = $filesystem->directories($path);

        foreach ($directories as $directory) {
            $item = [
                'name' => basename($directory), // 目录名
                'prev_path' => dirname(dirname($directory)), // 上层目录
                'path' => $directory, // 目录路径
                'type' => 'directory', // 类型标识
                'size' => 0, // 目录大小可以设置为0
                'url' => '', // 目录的URL可以不设置
                'last_modified' => '目录', // 最后修改时间
            ];
            $item['icon'] = url('/file-icon/folder.svg'); // 目录图标
            // 获取目录信息
            $fileInfoList[] = $item;
        }

        // 获取文件和目录列表
        $files = $filesystem->files($path); // 或者用 $filesystem->files() 和 $filesystem->directories() 合并

        foreach ($files as $file) {
            $item = [
                'name' => basename($file), // 文件名
                'path' => $file, // 文件路径
                'prev_path' => dirname(dirname($file)), // 上层目录
                'type' => pathinfo($file, PATHINFO_EXTENSION),
                'url' => $filesystem->url($file), // 文件的公开URL
                'last_modified' => date('Y-m-d H:i:s', $filesystem->lastModified($file)), // 最后修改时间
            ];
            $item['icon'] = $this->getFileIcon($item['type'], $item['url']);
            try{
                $item['size'] = $this->formatSize($filesystem->size($file));
            }catch (\Throwable $throwable){
                $item['size'] = '0';
            }
            // 获取文件信息
            $fileInfoList[] = $item;
        }
        return $this->response()->success([
            'items' => $fileInfoList,
            'prevPath' => $path === null ? '' : dirname(dirname($path)),
        ]);
    }
    protected function formatSize($size) {
        if ($size < 1024) {
            return $size . ' B'; // 字节
        } elseif ($size < 1024 ** 2) {
            return round($size / 1024, 2) . ' KB'; // 千字节
        } elseif ($size < 1024 ** 3) {
            return round($size / (1024 ** 2), 2) . ' MB'; // 兆字节
        } else {
            return round($size / (1024 ** 3), 2) . ' GB'; // 吉字节
        }
    }

    /**
     * 区分图标
     * @param string $type
     * @param string $url
     * @return string
     */
    public function getFileIcon(string $type, string $url)
    {
        // 图片格式
        if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'tiff'])) {
            return $url;
        }
        // 视频文件
        if (in_array($type, ['mp4', 'avi', 'mov', 'mkv', 'wmv', 'flv'])) {
            return url('/file-icon/video.svg');
        }
        // 安卓包
        if (in_array($type, ['apk'])) {
            return url('/file-icon/android.svg');
        }
        // 音频
        if (in_array($type, ['mp3', 'wav', 'aac', 'flac', 'ogg'])) {
            return url('/file-icon/audio.svg');
        }
        // 代码
        if (in_array($type, ['php', 'js', 'css', 'html', 'py', 'java', 'c', 'cpp', 'rb', 'go'])) {
            return url('/file-icon/code.svg');
        }
        // 压缩包
        if (in_array($type, ['zip', 'gz', 'rar', 'tar', '7z', 'bz2'])) {
            return url('/file-icon/compress.svg');
        }
        // 文档类
        if (in_array($type, ['doc', 'docx'])) {
            return url('/file-icon/doc.svg');
        }
        // pdf
        if (in_array($type, ['pdf'])) {
            return url('/file-icon/pdf.svg');
        }
        // 幻灯片
        if (in_array($type, ['ppt', 'pptx'])) {
            return url('/file-icon/ppt.svg');
        }
        // 文本
        if (in_array($type, ['txt', 'log'])) {
            return url('/file-icon/txt.svg');
        }
        // 表格
//    if (in_array($type, ['xls', 'xlsx'])) {
//        return '/file-icon/excel.svg';
//    }
        // 其他文件
        return url('/file-icon/other.svg');
    }


    /**
     * 新增文件
     * @return void
     */
    public function put()
    {
        // 获取文件系统实例
        $filesystem = getStorageFilesystem(request()->route('disk', 'local'));
        $fileInfoList = [];
        $path = request()->input('path', null);
        $body = request()->input('body', '');
        if (strlen(strval($path)) <= 0) {
            $path = null;
        }
        $filesystem->put($path, $body);
        return $this->response()->success([], '创建成功');
    }

    /**
     * 删除
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function delete()
    {
        // 获取文件系统实例
        $filesystem = getStorageFilesystem(request()->route('disk', 'local'));
        $fileInfoList = [];
        $path = request()->input('path', null);
        if (strlen(strval($path)) <= 0) {
            $path = null;
        }
        if (!$filesystem->exists($path)) {
            return $this->response()->fail('路径不存在');
        }
        $filesystem->delete($path);
        return $this->response()->success(['path' => dirname($path)], '删除成功');
    }

    /**
     * 修改内容
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function save()
    {
        // 获取文件系统实例
        $filesystem = getStorageFilesystem(request()->route('disk', 'local'));
        $fileInfoList = [];
        $path = request()->input('path', null);
        $body = request()->input('body', '');
        if (strlen(strval($path)) <= 0) {
            $path = null;
        }
        $filesystem->put($path, $body);
        return $this->response()->success([], '修改成功');
    }
}
