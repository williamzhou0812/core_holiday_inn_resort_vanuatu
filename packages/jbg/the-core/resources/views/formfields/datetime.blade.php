<div class="row date_time_panel">
    <div class="col-sm-6">
        <label class="control-label" for="name">{{ __('voyager::generic.date') }}</label>
        <input @if($row->required == 1) required @endif type="datetime" class="form-control datepart-picker" name="{{ $row->field }}_datepart"
               value="" data-datepart="@if(isset($dataTypeContent->{$row->field})){{ \Carbon\Carbon::parse(old($row->field, $dataTypeContent->{$row->field}))->format('n/j/Y') }}@else{{ '' }}@endif">
    </div>
    <div class="col-sm-6">
        <label class="control-label" for="name">{{ __('voyager::generic.time') }}</label>
        <input @if($row->required == 1) required @endif type="datetime" class="form-control timepart-picker" name="{{ $row->field }}_timepart"
               value="" data-timepart="@if(isset($dataTypeContent->{$row->field})){{ \Carbon\Carbon::parse(old($row->field, $dataTypeContent->{$row->field}))->format('n/j/Y H:i') }}@else{{ '' }}@endif">
    </div>
</div>
