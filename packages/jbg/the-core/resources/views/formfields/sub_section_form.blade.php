@php
    $dataSourceJson = old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '');
    if (empty($dataSourceJson))
        $dataSourceJson = '[]';
@endphp
<script>
    subDataSources['{{ $row->field }}'] =
    @php
      echo $dataSourceJson;
    @endphp
    ;
    subDataColumns['{{ $row->field }}'] =  [
     @foreach($row->details->data_table->columns as $columnInfo)
       { title: "{{ $columnInfo->title }}", data: "{{ $columnInfo->field }}"
            @if ($columnInfo->input_type == 'UpDown')
               , render: function (data, type, row) {
                   return '<button type="button" class="btn btn-xs btn-primary btn-pos" data-pos="up" onclick="javascript:editDataTableUpDown(\'{{ $row->field }}\',\'' + row.id +'\',true,\'{{ $columnInfo->field }}\')"><i class="voyager-angle-up"></i></button>' +
                    '&nbsp;<button type="button" class="btn btn-xs btn-primary btn-pos" data-pos="down" onclick="javascript:editDataTableUpDown(\'{{ $row->field }}\',\'' + row.id + '\',false,\'{{ $columnInfo->field }}\')"><i class="voyager-angle-down"></i></button>';
               }
            @elseif ($columnInfo->input_type == 'Text')
                , render: function (data, type, row) {
                    return '<span style="display: none">' + row.{{ $columnInfo->field }} + '</span><input style="width: 100%" type="text" id="text-'  + row.id + '" data-id="' + row.id +'" value="' + row.{{ $columnInfo->field }} + '"/>';
                }
            @endif
       },
     @endforeach
    ];

</script>
<input @if($row->required == 1) required @endif type="hidden" class="form-control" name="{{ $row->field }}"
        id="subsection_text_{{ $row->field }}"
        placeholder="{{ old($row->field, $options->placeholder ?? $row->getTranslatedAttribute('display_name')) }}"
       {!! isBreadSlugAutoGenerator($options) !!}
       value="{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}">
<div class="subsection_dtable_panel">
<table id="subsection_dtable_{{ $row->field }}" class="display">
</table>
</div>
