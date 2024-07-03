<?php

namespace ManoCode\FileSystem\Extend;

use Illuminate\Support\Facades\Event;
use Slowlyo\OwlAdmin\Events\ExtensionChanged;
use Slowlyo\OwlAdmin\Extend\ServiceProvider;

/**
 * ManoCode 的 服务提供者
 */
class ManoCodeServiceProvider extends ServiceProvider
{
    use CanImportMenu, CanImportDict;

    protected $menu = [];
    protected $dict = [];

    /**
     * 监听扩展注册事件
     * @return void
     */
    public function register()
    {
        /**
         * 监听启用禁用 事件
         */
        Event::listen(ExtensionChanged::class, function (ExtensionChanged $event) {
            if ($event->name === $this->getName() && $event->type == 'enable') {
                // 安装菜单
                if (method_exists($this, 'refreshMenu')) {
                    $this->refreshMenu();
                }
                // 安装字典
                if (method_exists($this, 'refreshDict')) {
                    $this->refreshDict();
                }
            } else if ($event->name === $this->getName() && $event->type == 'disable') {
                // 删除菜单
                if (method_exists($this, 'flushMenu')) {
                    $this->flushMenu();
                }
                // 删除字典
                if (method_exists($this, 'flushDict')) {
                    $this->flushDict();
                }
            } else if ($event->name === $this->getName() && $event->type == 'install') {
                // 安装菜单
                if (method_exists($this, 'refreshMenu')) {
                    $this->refreshMenu();
                }
                // 安装字典
                if (method_exists($this, 'refreshDict')) {
                    $this->refreshDict();
                }
            } else if ($event->name === $this->getName() && $event->type == 'uninstall') {
                // 删除菜单
                if (method_exists($this, 'flushMenu')) {
                    $this->flushMenu();
                }
                // 删除字典
                if (method_exists($this, 'flushDict')) {
                    $this->flushDict();
                }
            }
        });
    }
}
