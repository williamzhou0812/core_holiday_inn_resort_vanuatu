@php
    $edit = !is_null($dataTypeContent->getKey());
    $add  = is_null($dataTypeContent->getKey());
@endphp

@extends('voyager::master')

@section('css')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('page_title', __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i>
        {{ __('voyager::generic.'.($edit ? 'edit' : 'add')).' '.$dataType->getTranslatedAttribute('display_name_singular') }} {{ __('voyager::generic.content') }}
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content edit-add container-fluid showcase-edit">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered">
                    <!-- form start -->
                    <form role="form"
                            class="form-edit-add"
                            action="{{ $edit ? route('voyager.'.$dataType->slug.'.update', $dataTypeContent->getKey()) : route('voyager.'.$dataType->slug.'.store') }}"
                            method="POST" enctype="multipart/form-data">
                        <!-- PUT Method if we are editing -->
                        @if($edit)
                            {{ method_field("PUT") }}
                        @endif

                        <!-- CSRF TOKEN -->
                        {{ csrf_field() }}

                        <div class="panel-body">

                            @if (count($errors) > 0)
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <!-- Adding / Editing -->
                            @php
                                $dataTypeRows = $dataType->{($edit ? 'editRows' : 'addRows' )};
                                // build dictionary for easy access
                                $dataTypeRowsDict = array();
                                foreach($dataTypeRows as $row) {
                                    $dataTypeRowsDict[$row->field] = $row;
                                }
                            @endphp

                            <div class="row bottom-border">
                                @php
                                $row = $dataTypeRowsDict['name'];
                                @endphp
                                <div class="form-group col-md-5 {{ $errors->has($row->field) ? 'has-error' : '' }} title-panel">
                                    <label class="control-label" for="name">{{ __('voyager::generic.title') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                @if (array_key_exists('type', $dataTypeRowsDict))
                                @php
                                $row = $dataTypeRowsDict['type'];
                                @endphp
                                <div class="form-group col-md-4 {{ $errors->has($row->field) ? 'has-error' : '' }}">
                                    <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                                @else
                                <div class="form-group col-md-4">
                                    <label class="control-label" for="name">Type</label>
                                    <div class="type-display">
                                     {{ $dataTypeContent->type }}
                                    </div>
                                </div>
                                @endif
                                @php
                                $row = $dataTypeRowsDict['display_status'];
                                @endphp
                                <div class="form-group col-md-3 {{ $errors->has($row->field) ? 'has-error' : '' }}">
                                    <label class="control-label" for="name">Visibility</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                            <div class="row bottom-border">
                                @php
                                $row = $dataTypeRowsDict['display_from'];
                                @endphp
                                <div class="form-group col-md-5 {{ $errors->has($row->field) ? 'has-error' : '' }} display-from-panel">
                                    <div class="full-border">
                                        <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                        @include('voyager::multilingual.input-hidden-bread-edit-add')
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                        @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                            {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                        @endforeach
                                        @if ($errors->has($row->field))
                                            @foreach ($errors->get($row->field) as $error)
                                                <span class="help-block">{{ $error }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                                @php
                                $row = $dataTypeRowsDict['display_to'];
                                @endphp
                                <div class="form-group col-md-7 {{ $errors->has($row->field) ? 'has-error' : '' }} display-to-panel">
                                    <div class="full-border">
                                        <label class="control-label" for="name">{{ $row->getTranslatedAttribute('display_name') }}</label>
                                        @include('voyager::multilingual.input-hidden-bread-edit-add')
                                        {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                        @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                            {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                        @endforeach
                                        @if ($errors->has($row->field))
                                            @foreach ($errors->get($row->field) as $error)
                                                <span class="help-block">{{ $error }}</span>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                @php
                                $row = $dataTypeRowsDict['file'];
                                $fileType = 'Image'; // default to image
                                $fileType = $dataTypeContent->type;
                                @endphp
                                <div class="form-group col-md-12 {{ $errors->has($row->field) ? 'has-error' : '' }} file-panel">
                                    <label class="control-label" for="name">{{ $fileType }}</label>
                                    @include('voyager::multilingual.input-hidden-bread-edit-add')
                                    {!! app('voyager')->formField($row, $dataType, $dataTypeContent) !!}
                                    @foreach (app('voyager')->afterFormFields($row, $dataType, $dataTypeContent) as $after)
                                        {!! $after->handle($row, $dataType, $dataTypeContent) !!}
                                    @endforeach
                                    @if ($errors->has($row->field))
                                        @foreach ($errors->get($row->field) as $error)
                                            <span class="help-block">{{ $error }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </div>
                        </div><!-- panel-body -->

                        <div class="panel-footer">
                            @section('submit-buttons')
                                <button type="submit" class="btn btn-primary save">{{ __('voyager::generic.save') }}</button>
                            @stop
                            @yield('submit-buttons')
                        </div>
                    </form>

                    <iframe id="form_target" name="form_target" style="display:none"></iframe>
                    <form id="my_form" action="{{ route('voyager.upload') }}" target="form_target" method="post"
                            enctype="multipart/form-data" style="width:0;height:0;overflow:hidden">
                        <input name="image" id="upload_file" type="file"
                                 onchange="$('#my_form').submit();this.value='';">
                        <input type="hidden" name="type_slug" id="type_slug" value="{{ $dataType->slug }}">
                        {{ csrf_field() }}
                    </form>

                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-danger" id="confirm_delete_modal">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"
                            aria-hidden="true">&times;</button>
                    <h4 class="modal-title"><i class="voyager-warning"></i> {{ __('voyager::generic.are_you_sure') }}</h4>
                </div>

                <div class="modal-body">
                    <h4>{{ __('voyager::generic.are_you_sure_delete') }} '<span class="confirm_delete_name"></span>'</h4>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <button type="button" class="btn btn-danger" id="confirm_delete">{{ __('voyager::generic.delete_confirm') }}</button>
                </div>
                <input type="hidden" name="modal-data" class="confirm_delete_type" value=""/>
            </div>
        </div>
    </div>
    <!-- End Delete File Modal -->
@stop

@section('javascript')
    <script>
        var params = {};
        var $file;

        function deleteHandler(tag, isMulti) {
          return function() {
            $file = $(this).siblings(tag);

            params = {
                slug:   '{{ $dataType->slug }}',
                filename:  $file.data('file-name'),
                id:     $file.data('id'),
                field:  $file.parent().data('field-name'),
                multi: isMulti,
                _token: '{{ csrf_token() }}'
            }

            $('.confirm_delete_name').text(params.filename);
            $('.confirm_delete_type').text('default');
            $('#confirm_delete_modal').modal('show');
          };
        }

        function deleteMediaHandler(tag, isMulti) {
            return function() {
                $file = $(this).parent().parent().find('.fileType');
                params = {
                    slug:   '{{ $dataType->slug }}',
                    filename:  $file.data('file-name'),
                    id:     $file.data('id'),
                    field:  $file.parent().parent().parent().data('field-name'),
                    multi: isMulti,
                    _token: '{{ csrf_token() }}'
                }
                $('.confirm_delete_name').text(params.filename);
                $('.confirm_delete_type').text('mediafiles');
                $('#confirm_delete_modal').modal('show');
            };
        }

        $('document').ready(function () {
            $('.toggleswitch').bootstrapToggle();

            //Init datepicker for date fields if data-datepicker attribute defined
            //or if browser does not handle date inputs
            $('.form-group input[type=date]').each(function (idx, elt) {
                if (elt.hasAttribute('data-datepicker')) {
                    elt.type = 'text';
                    $(elt).datetimepicker($(elt).data('datepicker'));
                } else if (elt.type != 'date') {
                    elt.type = 'text';
                    $(elt).datetimepicker({
                        format: 'L',
                        extraFormats: [ 'YYYY-MM-DD' ]
                    }).datetimepicker($(elt).data('datepicker'));
                }
            });

            $('.form-control.datepart-picker').each(function(idx, elt) {
              $(elt).datetimepicker({
                format: 'DD/MM/YYYY',
                defaultDate: $(elt).data('datepart')
              });
              var btnId = $(elt).attr('name')+'_btn';
              $('#'+btnId).click(function() {
                $(elt).focus();
              });
            });
            $('.form-control.timepart-picker').each(function(idx, elt) {
              $(elt).datetimepicker({
                format: 'h:mm A',
                defaultDate: $(elt).data('timepart')
              });
              var btnId = $(elt).attr('name')+'_btn';
              $('#'+btnId).click(function() {
                $(elt).focus();
              });
            });

            @if ($isModelTranslatable)
                $('.side-body').multilingual({"editing": true});
            @endif

            $('.side-body input[data-slug-origin]').each(function(i, el) {
                $(el).slugify();
            });

            $('.form-group').on('click', '.remove-multi-image', deleteHandler('img', true));
            $('.form-group').on('click', '.remove-single-image', deleteHandler('img', false));
            $('.form-group').on('click', '.remove-multi-file', deleteHandler('a', true));
            $('.form-group').on('click', '.remove-single-file', deleteHandler('a', false));
            $('.form-group').on('click', '.remove-multi-media-file', deleteMediaHandler('a', true));

            $('#confirm_delete').on('click', function(){
                if ($('.confirm_delete_type').text() == "default") {
                    $.post('{{ route('voyager.'.$dataType->slug.'.media.remove') }}', params, function (response) {
                        if ( response
                            && response.data
                            && response.data.status
                            && response.data.status == 200 ) {

                            toastr.success(response.data.message);
                            $file.parent().fadeOut(300, function() { $(this).remove(); })
                        } else {
                            toastr.error("Error removing file.");
                        }
                    });
                }
                else if($('.confirm_delete_type').text() == "mediafiles") {
                    $file.parent().parent().parent().fadeOut(300, function() { $(this).remove(); });
                }

                $('#confirm_delete_modal').modal('hide');
            });
            $('[data-toggle="tooltip"]').tooltip();

            @php
                $fileType = $dataTypeContent->type;
            @endphp
            var $default_file_type = '{{ $fileType }}';

            $('.media_file_uploader').each(function(i, el) {
                $(el).attr('accept', $default_file_type + '/*');
            });
        });
    </script>
@stop
