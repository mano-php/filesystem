<?php

namespacemano-code\FileSystem\Http\Controllers;

use Slowlyo\OwlAdmin\Controllers\AdminController;

class FilesystemController extends AdminController
{
    public function index()
    {
        $page = $this->basePage()->body('Filesystem Extension.');

        return $this->response()->success($page);
    }
}
