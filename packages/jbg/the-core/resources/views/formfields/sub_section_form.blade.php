@php
    $dataSourceJson = old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '');
    if (empty($dataSourceJson))
        $dataSourceJson = '[]';

@endphp
<script>
    subDataSources['{{ $row->field }}'] =
    @php
      echo $dataSourceJson;;
    @endphp
    ;
    subDataColumns['{{ $row->field }}'] =  [
     @foreach($row->details->data_table->columns as $columnInfo)
       { title: "{{ $columnInfo->title }}", data: "{{ $columnInfo->field }}"
            @if ($columnInfo->input_type == 'UpDown')
               , render: function (data, type, row) {
                   return 'up down';
               }
            @elseif ($columnInfo->input_type == 'Text')
                , render: function (data, type, row) {
                    return '<input style="width: 100%" type="text" id="text-'  + row.id + '" data-id="' + row.id +'" value="' + row.{{ $columnInfo->field }} + '"/>';
                }
            @endif
       },
     @endforeach
    ];

</script>
<div class="subsection_dtable_panel">
<table id="subsection_dtable_{{ $row->field }}" class="display">
</table>
</div>
