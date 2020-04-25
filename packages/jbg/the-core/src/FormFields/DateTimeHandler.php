<?php

namespace TCG\Voyager\FormFields;

class DateTimeHandler extends AbstractHandler
{
    protected $codename = 'datetime';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.datetime', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
