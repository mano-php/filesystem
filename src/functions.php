<?php
use Slowlyo\OwlAdmin\Admin;

if(!function_exists('ManoImageControl')){
    function ManoImageControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->ImageControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image/");
    }
}

if(!function_exists('ManoRichTextControl')){
    function ManoRichTextControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->RichTextControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image/");
    }
}
