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

#### OSS直传文件组件

```php

ManoOssFileControl('avatar','头像')->required(),

```


#### 自定义目录 文件 的名称生成规则 默认的设置在存储器

```php

// 可用变量

//    {date}      =>   2024-10-11                              // 年月日
//    {datetime}  =>   2024-10-11 17:19:21                     // 年月日时分秒
//    {time}      =>   1728638496                              // 时间戳
//    {uuid}      =>   a1a65110-1c10-400b-8357-e6774793a5a5    // UUID
//    {type}      =>   image                                   // 文件分类
//    {ext}       =>   jpg                                     // 文件后缀名
//    {hash}      =>   698d51a19d8a121ce581499d7b701668        // 文件hash值
//    {rand(32)}  =>   fnooT7QqvsCSLk3Y8jCo1QBJ12W5CFcC        // 随机字符串


ManoImageControl('avatar','头像','oss','my-file/{type}','{time}.{ext}')->required(),
ManoFileControl('avatar','头像','oss','my-file/{type}','{time}.{ext}')->required(),
```
