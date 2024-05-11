# 文件存储、上传扩展


#### 扩展使用Laravel FileSystem 作为存储底层驱动

### 1. 表单使用

```php
return $this->baseForm()->body([
            amis()->HiddenControl('id','ID','local'), 
            ManoImageControl('goods_image','商品主图')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
]);
```

### 2. 富文本图片存储

```php
return $this->baseForm()->body([
            amis()->HiddenControl('id','ID'), 
            ManoRichTextControl('content','商品详情','local')->required(), // local为默认存储驱动 也可以配置七牛 或者腾讯OCS 或者阿里云OSS
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
