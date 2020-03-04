<link rel="stylesheet" href="{{ voyager_asset('css/checkbox.min.css') }}">
<?php $checked = false; ?>
@if(isset($dataTypeContent->{$row->field}) || old($row->field))
    <?php $checked = old($row->field, $dataTypeContent->{$row->field}); ?>
@else
    <?php $checked = isset($options->checked) &&
        filter_var($options->checked, FILTER_VALIDATE_BOOLEAN) ? true: false; ?>
@endif
<div class="visible_chk_panel">
    <br>
    <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
    <label class="el-switch el-switch-sm">
    @if(isset($options->on) && isset($options->off))
        <input type="checkbox" name="{{ $row->field }}"
            data-on="{{ $options->on }}" {!! $checked ? 'checked="checked"' : '' !!}
            data-off="{{ $options->off }}">
    @else
        <input type="checkbox" name="{{ $row->field }}"
            @if($checked) checked @endif>
    @endif
      <span class="el-switch-style"></span>
    </label>
    <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
</div>


