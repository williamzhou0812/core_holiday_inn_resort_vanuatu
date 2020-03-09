<!--input @if($row->required == 1) required @endif type="text" class="form-control" name="{{ $row->field }}"
        placeholder="{{ old($row->field, $options->placeholder ?? $row->getTranslatedAttribute('display_name')) }}"
       {!! isBreadSlugAutoGenerator($options) !!}
       value="{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}"-->
@php
    $dataSourceJson = old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '');
    if (empty($dataSourceJson))
        $dataSourceJson = '[]';
    $dataSource = json_decode($dataSourceJson);
    var_dump($dataSource);
     var_dump($row);
@endphp
<div class="subsection_dtable_panel">
<table id="subsection_dtable_{{ $row->field }}" class="display">
    <thead>
        <tr>
        @foreach($row->details->data_table->columns as $columnInfo)
            <th>{{ $columnInfo->title }}</th>
        @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($dataSource as $rowInfo)
        <tr>
            @foreach($row->details->data_table->columns as $columnInfo)
                @php
                    $displayValue = '';
                    $field = $columnInfo->field;
                    if (property_exists($rowInfo, $field)) {
                        $displayValue = $rowInfo->$field;
                    }
                @endphp
                @if ($columnInfo->input_type == 'UpDown')
                    <td>
                        <button type="button" class="btn btn-xs btn-primary btn-pos" data-pos="up" data-id="{{ $rowInfo->id }}"><i class="voyager-angle-up"></i></button>
                        <button type="button" class="btn btn-xs btn-primary btn-pos" data-pos="down" data-id="{{ $rowInfo->id }}"><i class="voyager-angle-down"></i></button>
                    </td>
                @elseif ($columnInfo->input_type == 'Text')
                    <td><input style="width: 100%" type="text" id="text-{{ $rowInfo->id }}" data-id="{{ $rowInfo->id }}" value="{{ $displayValue }}"/></td>
                @else
                    <td>{{ $displayValue }}</td>
                @endif
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
</div>