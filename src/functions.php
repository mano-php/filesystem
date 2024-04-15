<?php
use Slowlyo\OwlAdmin\Admin;

if(!function_exists('UuptImageControl')){
    function UuptImageControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->ImageControl($name, $label)->receiver("/uupt/upload/{$disk}/upload-image/");
    }
}

if(!function_exists('UuptRichTextControl')){
    function UuptRichTextControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->RichTextControl($name, $label)->receiver("/uupt/upload/{$disk}/upload-image/");
    }
}
