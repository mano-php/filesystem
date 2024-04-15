<?php

use Slowlyo\OwlAdmin\Admin;

if(!function_exists('UuptImageControl')){
    function UuptImageControl($name = '', $label = ''){
        return amis()->ImageControl($name, $label)->receiver('/uupt/upload-image');
    }
}
