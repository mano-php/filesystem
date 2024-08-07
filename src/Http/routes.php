<?php

use Slowlyo\OwlAdmin\Admin;
use ManoCode\FileSystem\Http\Controllers;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

// 文件系统配置
Route::resource('filesystem_config', \ManoCode\FileSystem\Http\Controllers\FilesystemConfigController::class);

Route::get('filesystem', [Controllers\FilesystemController::class, 'index']);
/**
 * 上传图片
 */
Route::any('/mano-code/upload/{disk}/upload-image', [Controllers\UploadController::class, 'uploadImage']);
/**
 * 上传文件
 */
Route::any('/mano-code/upload/{disk}/upload-file', [Controllers\UploadController::class, 'uploadFile']);
/**
 * 富文本上传
 */
Route::any('/mano-code/upload/{disk}/upload-rich', [Controllers\UploadController::class, 'uploadRich']);
/**
 * 开始分片上传
 */
Route::any('/mano-code/upload/{disk}/upload_chunk_start', [Controllers\UploadController::class, 'chunkUploadStart']);
/**
 * 分片上传
 */
Route::any('/mano-code/upload/{disk}/upload_chunk', [Controllers\UploadController::class, 'chunkUpload']);
/**
 * 上传完成
 */
Route::any('/mano-code/upload/{disk}/upload_chunk_finish', [Controllers\UploadController::class, 'chunkUploadFinish']);
