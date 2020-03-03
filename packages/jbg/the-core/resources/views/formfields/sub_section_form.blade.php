<!--input @if($row->required == 1) required @endif type="text" class="form-control" name="{{ $row->field }}"
        placeholder="{{ old($row->field, $options->placeholder ?? $row->getTranslatedAttribute('display_name')) }}"
       {!! isBreadSlugAutoGenerator($options) !!}
       value="{{ old($row->field, $dataTypeContent->{$row->field} ?? $options->default ?? '') }}"-->

<div class="subsection_dtable_panel">
<table id="subsection_dtable_{{ $row->field }}" class="display">
    <thead>
        <tr>
            <th></th>
            <th>Position</th>
            <th>Page Title</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>UP DOWN</td>
            <td>1</td>
            <td>ABC restaurant</td>
        </tr>
        <tr>
            <td>UP DOWN</td>
            <td>2</td>
            <td>BAR test</td>
        </tr>
    </tbody>
</table>
</div>