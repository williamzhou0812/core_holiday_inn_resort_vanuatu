@extends('voyager::master')

@section('page_title', __('voyager::generic.view').' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <h1 class="page-title">
        <i class="{{ $dataType->icon }}"></i> {{ __('voyager::generic.viewing') }} {{ ucfirst($dataType->getTranslatedAttribute('display_name_singular')) }} {{ __('voyager::generic.content') }}&nbsp;

        @can('edit', $dataTypeContent)
            <a href="{{ route('voyager.'.$dataType->slug.'.edit', $dataTypeContent->getKey()) }}" class="btn btn-info">
                <span class="glyphicon glyphicon-pencil"></span>&nbsp;
                {{ __('voyager::generic.edit') }}
            </a>
        @endcan
        @can('delete', $dataTypeContent)
            @if($isSoftDeleted)
                <a href="{{ route('voyager.'.$dataType->slug.'.restore', $dataTypeContent->getKey()) }}" title="{{ __('voyager::generic.restore') }}" class="btn btn-default restore" data-id="{{ $dataTypeContent->getKey() }}" id="restore-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.restore') }}</span>
                </a>
            @else
                <a href="javascript:;" title="{{ __('voyager::generic.delete') }}" class="btn btn-danger delete" data-id="{{ $dataTypeContent->getKey() }}" id="delete-{{ $dataTypeContent->getKey() }}">
                    <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">{{ __('voyager::generic.delete') }}</span>
                </a>
            @endif
        @endcan

        <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="btn btn-warning">
            <span class="glyphicon glyphicon-list"></span>&nbsp;
            {{ __('voyager::generic.return_to_list') }}
        </a>
    </h1>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    @php
        // build dictionary for easy access
        $readRowsDict = array();
        foreach($dataType->readRows as $row) {
            $readRowsDict[$row->field] = $row;
        }
    @endphp;
    <div class="page-content read container-fluid showcase-read">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered showcase-panel" style="padding-bottom:5px;">
                    <div class="row bottom-border">
                        <div class="col-md-6 title-panel">
                        @php
                        $row = $readRowsDict['name'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <p>{{ $dataTypeContent->{$row->field} }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                        @php
                        $row = $readRowsDict['type'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <p>{{ $dataTypeContent->{$row->field} }}</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                        @php
                        $row = $readRowsDict['display_status'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                            @php
                                $visible_checked = false;
                                if (property_exists($row->details, 'on') && property_exists($row->details, 'off')) {
                                    $visible_checked = ($dataTypeContent->{$row->field});
                                }
                                else {
                                    $visible_checked = strval($dataTypeContent->{$row->field}) == '1';
                                }
                            @endphp
                            @if($visible_checked)
                                <span class="glyphicon glyphicon-eye-open" aria-hidden="true"></span>
                            @else
                                <span class="glyphicon glyphicon-eye-close" aria-hidden="true"></span>
                            @endif
                            </div>
                        </div>
                    </div>
                    <div class="row bottom-border">
                        <div class="col-md-3 created-panel">
                        @php
                        $row = $readRowsDict['created_at'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                                 @if ( property_exists($row->details, 'format') && !is_null($dataTypeContent->{$row->field}) )
                                     {{ \Carbon\Carbon::parse($dataTypeContent->{$row->field})->formatLocalized($row->details->format) }}
                                 @else
                                     {{ $dataTypeContent->{$row->field} }}
                                 @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                        @php
                        $row = $readRowsDict['display_from'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                                 @if ( property_exists($row->details, 'format') && !is_null($dataTypeContent->{$row->field}) )
                                     {{ \Carbon\Carbon::parse($dataTypeContent->{$row->field})->formatLocalized($row->details->format) }}
                                 @else
                                     {{ $dataTypeContent->{$row->field} }}
                                 @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                        @php
                        $row = $readRowsDict['display_to'];
                        @endphp
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                                 @if ( property_exists($row->details, 'format') && !is_null($dataTypeContent->{$row->field}) )
                                     {{ \Carbon\Carbon::parse($dataTypeContent->{$row->field})->formatLocalized($row->details->format) }}
                                 @else
                                     {{ $dataTypeContent->{$row->field} }}
                                 @endif
                            </div>
                        </div>
                    </div>
                    <div class="row no-border">
                        @php
                        $row = $readRowsDict['file'];
                        $fileType = 'Image'; // default to image
                        $typeRow = $readRowsDict['type'];
                        if (isset($typeRow)) {
                            $fileType = $dataTypeContent->type;
                        }
                        $fileValue = $dataTypeContent->{$row->field};
                        $fileList = json_decode($fileValue);
                        @endphp
                        <div class="col-md-12">
                            <div class="panel-heading" style="border-bottom:0;">
                                <h3 class="panel-title">{{ __('voyager::generic.preview') }}</h3>
                            </div>
                            <div class="panel-body" style="padding-top:0;">
                            @if ($fileType == 'Image')
                                @php
                                    $view_img_width = '';
                                    if (property_exists($row->details, 'view_image_width')) {
                                        $view_img_width = $row->details->view_image_width;
                                    }
                                @endphp
                                @foreach($fileList as $fileObj)
                                    <img class="img-responsive"
                                    @if (!empty($view_img_width))
                                     width="{{ $view_img_width }}"
                                    @endif
                                     src="{{ filter_var($fileObj->download_link, FILTER_VALIDATE_URL) ? $fileObj->download_link : Voyager::image($fileObj->download_link) }}">
                                    <br/>
                                @endforeach
                            @elseif($fileType == 'Video')
                                @foreach($fileList as $fileObj)
                                    <video controls width="500">
                                       <source src="{{ filter_var($fileObj->download_link, FILTER_VALIDATE_URL) ? $fileObj->download_link : Voyager::image($fileObj->download_link) }}"
                                               type="{{ $fileObj->mime_type }}">

                                       Sorry, your browser doesn't support embedded videos.
                                    </video>
                                    <br/>
                                @endforeach
                            @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><i class="voyager-trash"></i> {{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</h4>
                </div>
                <div class="modal-footer">
                    <form action="{{ route('voyager.'.$dataType->slug.'.index') }}" id="delete_form" method="POST">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <input type="submit" class="btn btn-danger pull-right delete-confirm"
                               value="{{ __('voyager::generic.delete_confirm') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}">
                    </form>
                    <button type="button" class="btn btn-default pull-right" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            $(document).ready(function () {
                $('.side-body').multilingual();
            });
        </script>
    @endif
    <script>
        var deleteFormAction;
        $('.delete').on('click', function (e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/)
                ? deleteFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });

    </script>
@stop
