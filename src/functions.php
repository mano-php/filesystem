<?php
use Slowlyo\OwlAdmin\Admin;

if(!function_exists('UuptImageControl')){
    function UuptImageControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->ImageControl($name, $label)->receiver("/uupt/upload/{$disk}/upload-image/");
    }
}
