<?php
namespace TCG\Voyager\FormFields;

class SubSectionHandler extends AbstractHandler
{
    protected $codename = 'sub_section_form';

    public function createContent($row, $dataType, $dataTypeContent, $options)
    {
        return view('voyager::formfields.sub_section_form', [
            'row'             => $row,
            'options'         => $options,
            'dataType'        => $dataType,
            'dataTypeContent' => $dataTypeContent,
        ]);
    }
}
