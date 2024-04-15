<?php

namespace Uupt\FileSystem;

use Slowlyo\OwlAdmin\Extend\Extension;
use Slowlyo\OwlAdmin\Renderers\TextControl;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;

class FilesystemServiceProvider extends ServiceProvider
{
    public function boot()
    {
        require_once(__DIR__.DIRECTORY_SEPARATOR.'functions.php');
        if (Extension::tableExists()) {
            $this->autoRegister();
            $this->init();
        }
    }

	public function settingForm()
	{
	    return $this->baseSettingForm()->body([
            TextControl::make()->name('value')->label('Value')->required(true),
	    ]);
	}
}
