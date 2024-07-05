<?php

namespace ManoCode\FileSystem\Http\Controllers;

use Slowlyo\OwlAdmin\Controllers\AdminController;

class FilesystemController extends AdminController
{
    public function index()
    {
        $page = $this->basePage()->body('文件存储系统.');

        return $this->response()->success($page);
    }
}
