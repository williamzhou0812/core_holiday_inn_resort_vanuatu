<div class="media-files-panel">
@if(isset($dataTypeContent->{$row->field}))
    @php
    $decoded = json_decode($dataTypeContent->{$row->field});
    @endphp
    @if($decoded !== null)
        @foreach($decoded as $file)
            <div data-field-name="{{ $row->field }}">
              <div class="row">
                <div class="col-sm-11">
                    <a class="fileType" target="_blank"
                        href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}"
                        data-file-name="{{ $file->original_name }}" data-id="{{ $dataTypeContent->getKey() }}">
                        @if (strpos($file->mime_type, 'image') !== false)
                        <img class="img-responsive"
                            src="{{ filter_var($file->download_link, FILTER_VALIDATE_URL) ? $file->download_link : Voyager::image($file->download_link) }}">
                        @elseif (strpos($file->mime_type, 'video') !== false)
                            <video controls width="500">
                               <source src="{{ filter_var($file->download_link, FILTER_VALIDATE_URL) ? $file->download_link : Voyager::image($file->download_link) }}"
                                       type="{{ $file->mime_type }}">

                               Sorry, your browser doesn't support embedded videos.
                            </video>
                        @else
                            {{ $file->original_name }}
                        @endif
                    </a>
                </div>
                <div class="col-sm-1">
                    <a href="#" class="voyager-x remove-multi-media-file" title="Delete"></a>
                    @if (property_exists($file, 'added') && $file->added == 'true')
                     <input type="hidden" name="{{ $row->field }}_added_files[]" value="{{ $file->download_link }}" class="{{ $row->field }}_data">
                    @else
                    <input type="hidden" name="{{ $row->field }}_files[]" value="{{ $file->download_link }}" class="{{ $row->field }}_data">
                    @endif
                </div>
              </div>
            </div>
        @endforeach
    @else
      <div data-field-name="{{ $row->field }}">
        <a class="fileType" target="_blank"
          href="{{ Storage::disk(config('voyager.storage.disk'))->url($dataTypeContent->{$row->field}) }}"
          data-file-name="{{ $dataTypeContent->{$row->field} }}" data-id="{{ $dataTypeContent->getKey() }}">
          Download
        </a>
        <a href="#" class="voyager-x remove-single-file"></a>
      </div>
    @endif

@endif
    <div class="upload-section">
        <div class="file-uploader">
            <input @if($row->required == 1 && !isset($dataTypeContent->{$row->field})) @endif type="file" name="{{ $row->field }}[]" multiple="multiple" class="media_file_uploader">
        </div>
        <div class="or-text">
        or
        </div>
        <div class="browse-media">
            <button name="{{ $row->field }}_browse_media" type="submit" class="btn btn-warning btn-browse-media"  value="browse_media"><i class="voyager-documentation"></i> Browse Library</button>
        </div>
        <div style="clear: both;"></div>
    </div>
</div>
