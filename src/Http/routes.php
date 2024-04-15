<?php

use Slowlyo\OwlAdmin\Admin;
use Uupt\FileSystem\Http\Controllers;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('filesystem', [Controllers\FilesystemController::class, 'index']);

Route::any('/uupt/upload-image',[Controllers\UploadController::class,'uploadImage']);
