<?php

namespace TCG\Voyager\FormFields;

class MediaFilesHandler extends AbstractHandler
{
    protected $codename = 'media_files';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.media_files', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
