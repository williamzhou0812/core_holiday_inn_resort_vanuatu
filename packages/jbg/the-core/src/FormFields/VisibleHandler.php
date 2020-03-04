<?php

namespace TCG\Voyager\FormFields;

class VisibleHandler extends AbstractHandler
{
    protected $codename = 'visible_checkbox';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.visible_checkbox', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
