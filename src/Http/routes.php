<?php

use Slowlyo\OwlAdmin\Admin;
use Uupt\FileSystem\Http\Controllers;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
// 文件系统配置
Route::resource('filesystem_config', \Uupt\FileSystem\Http\Controllers\FilesystemConfigController::class);

Route::get('filesystem', [Controllers\FilesystemController::class, 'index']);

Route::any('/uupt/upload/{disk}/upload-image',[Controllers\UploadController::class,'uploadImage']);
