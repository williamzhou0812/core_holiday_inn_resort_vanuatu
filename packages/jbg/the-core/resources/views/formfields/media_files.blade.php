@if(isset($dataTypeContent->{$row->field}))
    @php
    $decoded = json_decode($dataTypeContent->{$row->field});
    @endphp
    <div class="media-files-panel">
    @if($decoded !== null)
        @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
            <div data-field-name="{{ $row->field }}">
              <div class="row">
                <div class="col-sm-11">
                    @if (strpos($file->mime_type, 'image') == 0)
                    <a class="fileType" target="_blank"
                        href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}"
                        data-file-name="{{ $file->original_name }}" data-id="{{ $dataTypeContent->getKey() }}">
                        <img class="img-responsive"
                            src="{{ filter_var($file->download_link, FILTER_VALIDATE_URL) ? $file->download_link : Voyager::image($file->download_link) }}">
                    </a>
                    @endif
                </div>
                <div class="col-sm-1">
                    <a href="#" class="voyager-x remove-multi-media-file"></a>
                </div>
              </div>
            </div>
        @endforeach
    @else
      <div data-field-name="{{ $row->field }}">
        <a class="fileType" target="_blank"
          href="{{ Storage::disk(config('voyager.storage.disk'))->url($dataTypeContent->{$row->field}) }}"
          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}">>
          Download
        </a>
        <a href="#" class="voyager-x remove-single-file"></a>
      </div>
    @endif
    </div>
@endif
-------
<input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) required @endif type="file" name="{{ $row->field }}[]" multiple="multiple">
---------------
