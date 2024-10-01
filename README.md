# 文件存储、上传扩展


#### 扩展使用Laravel FileSystem 接口 作为存储底层驱动

### 1. 表单使用

```php
return $this->baseForm()->body([
            amis()->HiddenControl('id','ID'), 
            ManoImageControl('goods_image','商品主图')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
]);
```

### 2. 富文本 图片、文件、视频 上传

```php
return $this->baseForm()->body([
            amis()->HiddenControl('id','ID'), 
            ManoRichTextControl('content','商品详情')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
            ManoWangEditorControl('content','详细描述')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
]);
```

### 3. 附件上传

```php
return $this->baseForm()->body([
            amis()->HiddenControl('id','ID'), 
            ManoFileControl('content','商品详情')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
]);
```


### 列表展示图片

```php
$crud = $this->baseCRUD()
            ->filterTogglable(false)
            ->headerToolbar([
				$this->createButton(true),
				...$this->baseHeaderToolBar()
            ])
            ->bulkActions('')
            ->columns([
                // .........
                amis()->TableColumn('goods_image')->type('image')
                // .........
        ]);
```


#### API接口使用上传文件接口 在src/Http/api_routes.php 内定义上传路由 表单字段为 `file`

```php
/**
 * 测试上传接口
 */
Route::any('/api-upload-demo', function () {
    $upload = new ManoCode\FileSystem\Http\Controllers\UploadController();
    try {
        /**
         * 参数一 类型 image or file
         * 参数二 form 字段 例如默认的 `file`
         */
        [$basePath, $fileName] = $upload->upload('image','file'); // image | file
    } catch (\Throwable $throwable) {
        return response()->json([
            'status' => 400,
            'msg' => '上传失败',
        ]);
    }
    return response()->json(['status' => 200, 'msg' => '上传成功', 'data' => [
        'basePath' => $basePath,
        'fileName' => $fileName
    ]]);
});
```


#### 获取存储器用于删除、查询文件的操作

```php

// 写入文件
getStorageFilesystem()->put('demo/test.txt','Hello World');
// 删除文件
getStorageFilesystem()->delete('demo/test.txt');
// 获取配置目录下的文件
getStorageFilesystem()->files();
// 文件是否存在
getStorageFilesystem()->exists('demo/test.txt');
// 指定存储器的名称（默认获取当前开启的存储驱动）
getStorageFilesystem('local')->exists('demo/test.txt');
```
