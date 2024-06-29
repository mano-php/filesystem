<?php
use Slowlyo\OwlAdmin\Admin;


if(!function_exists('ManoImageControl')){
    function ManoImageControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->ImageControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image");
    }
}
if(!function_exists('ManoFileControl')){
    function ManoFileControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->FileControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-file")->
        startChunkApi("/mano-code/upload/{$disk}/upload_chunk_start")->
        chunkApi("/mano-code/upload/{$disk}/upload_chunk")->
        finishChunkApi("/mano-code/upload/{$disk}/upload_chunk_finish");
    }
}

if(!function_exists('ManoRichTextControl')){
    function ManoRichTextControl(string $name = '', string $label = '',string $disk = 'local'){
        return amis()->RichTextControl($name, $label)->receiver("/mano-code/upload/{$disk}/upload-image");
    }
}
