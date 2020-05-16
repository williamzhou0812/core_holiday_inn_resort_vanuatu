<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\ExpectationFailedException;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\ContentTypes\DateTime;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;
use Carbon\Carbon;


class VoyagerShowcaseController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    /**
     * Remove translations, images and files related to a BREAD item.
     *
     * @param \Illuminate\Database\Eloquent\Model $dataType
     * @param \Illuminate\Database\Eloquent\Model $data
     *
     * @return void
     */
    protected function cleanup($dataType, $data)
    {
        // Delete Translations, if present
        if (is_bread_translatable($data)) {
            $data->deleteAttributeTranslations($data->getTranslatableAttributes());
        }

        // Delete Images
        $this->deleteBreadImages($data, $dataType->deleteRows->whereIn('type', ['image', 'multiple_images']));

        // Delete Files
        foreach ($dataType->deleteRows->where('type', 'file') as $row) {
            if (isset($data->{$row->field})) {
                foreach (json_decode($data->{$row->field}) as $file) {
                    $this->deleteFileIfExists($file->download_link);
                }
            }
        }

        // delete media files
        foreach ($dataType->deleteRows->where('type', 'media_files') as $row) {
            if (isset($data->{$row->field})) {
                foreach (json_decode($data->{$row->field}) as $file) {
                    if (property_exists($file, 'reference_only') && $file->reference_only == 'true') {
                        continue; // do not need to delete
                    }

                    $this->deleteFileIfExists($file->download_link);
                }
            }
        }

        // Delete media-picker files
        $dataType->rows->where('type', 'media_picker')->where('details.delete_files', true)->each(function ($row) use ($data) {
            $content = $data->{$row->field};
            if (isset($content)) {
                if (!is_array($content)) {
                    $content = json_decode($content);
                }
                if (is_array($content)) {
                    foreach ($content as $file) {
                        $this->deleteFileIfExists($file);
                    }
                } else {
                    $this->deleteFileIfExists($content);
                }
            }
        });
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $model = $model->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $data = $model->withTrashed()->findOrFail($id);
        } else {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);
        }

        // Check permission
        $this->authorize('edit', $data);

        // check if browse media button click, this is very specific implementation
        $browseMedia = $request->input('file_browse_media');
        if (isset($browseMedia)) {
            // create uuid
            $uuid = (string) Str::uuid();
            // data
            $session_data = $request->all();
            $session_data['redirect'] = $request->url() . '/edit';
            // store request inputs into session
            $request->session()->put('showcases.browse_media-' . $uuid, $session_data);

            $filetype = strtolower($data->type);
            // redirect to media index page
            return redirect()->route('voyager.library.index', array('request_id'=>$uuid, 'filetype'=>$filetype));
        }

        // find all fields that have media file type
        $originalList = array();
        foreach ($dataType->editRows->where('type', 'media_files') as $row) {
            // collect original data
            if (isset($data->{$row->field})) {
                $originalList = array_merge($originalList,  json_decode($data->{$row->field}));
            }
        }

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        $updatedData = $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        // find the updated list
        $updatedList = array();
        foreach ($dataType->editRows->where('type', 'media_files') as $row) {
            if (isset($updatedData->{$row->field})) {
                $updatedList = array_merge($updatedList, json_decode($updatedData->{$row->field}));
            }
        }

        // check files that have references removed
        $deleted_list = array();
        foreach($originalList as $toCheck) {
            $found = false;
            foreach($updatedList as $fileInfo) {
                if ($toCheck->download_link == $fileInfo->download_link) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                // check if it is a reference only
                if (!property_exists($toCheck, 'reference_only') || $toCheck->reference_only !== 'true') {
                    $deleted_list[] = $toCheck;
                }
            }
        }
        
        // delete actual files
        foreach ($deleted_list as $file) {
            $this->deleteFileIfExists($file->download_link);
        }

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', $model)) {
            $redirect = redirect()->route("voyager.{$dataType->slug}.index");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }

    public function edit(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $model = $model->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $model = $model->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$model, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // retrieve special parameter from request
        $requestId = $request->input('request_id');
        if (!isset($requestId))
            $requestId = '';
        // check if session data exists
        $sessionData = $request->session()->get('showcases.browse_media-' . $requestId);
        if (isset($sessionData)) {
            // override data
            foreach ($dataType->editRows as $key => $row) {
                $row_field = $row->field;
                if (array_key_exists($row_field, $sessionData)) {
                    $dataTypeContent->$row_field = $sessionData[$row_field];
                }
                else if (array_key_exists($row_field . '_datepart', $sessionData)) {
                    // parse date time
                    // get date and time part
                    $contentDatePart = $sessionData[$row_field . '_datepart'];
                    $contentTimePart = $sessionData[$row_field . '_timepart'];

                    if (!isset($contentDatePart) || empty($contentDatePart)) {
                        $dataTypeContent->$row_field = null;
                        continue;
                    }
                    if (!isset($contentTimePart) || empty($contentTimePart)) {
                        $contentTimePart = '12:00 AM';
                    }
                    // parse date time
                    $contentInput = $contentDatePart . ' ' . $contentTimePart;
                    $format = 'j/n/Y+ g:i A';
                    $dateInfo = date_parse_from_format($format, $contentInput);
                    $carbonDate = Carbon::create($dateInfo['year'], $dateInfo['month'], $dateInfo['day'], $dateInfo['hour'], $dateInfo['minute']);
                    $dataTypeContent->$row_field = $carbonDate->toDateTimeString();
                }
                else if ($row_field == 'file') {
                    // get file list from session
                    $file_files = (array_key_exists('file_files', $sessionData)) ? $sessionData['file_files'] : [];
                    $file_json =  $dataTypeContent->file;
                    if (!isset($file_json))
                        $file_json = '[]';
                    $file_objs = json_decode($file_json);
                    $updated_files = array();
                    foreach($file_objs as $obj) {
                        $link = $obj->download_link;
                        $found = false;
                        foreach($file_files as $session_file) {
                            if ($link == $session_file) {
                                $found  = true;
                                break;
                            }
                        }
                        if ($found) {
                            $updated_files[] = $obj;
                        }
                    }

                    // get selected files
                    $selectedFiles = $request->session()->get('showcases.browse_media_files-' . $requestId);
                    if (isset($selectedFiles)) {
                        foreach($selectedFiles as $newfile) {
                            $download_link = ltrim(str_replace('/', "\\", $newfile), "\\");
                            $ext = pathinfo($newfile);
                            $updated_files[] = array(
                                'download_link' => ltrim(str_replace('/', "\\", $newfile), "\\"),
                                'original_name' => $ext['basename'],
                                'mime_type' => $this->getMimeType($ext['extension']),
                                'reference_only' => 'true',
                                'added' => 'true'
                            );
                        }
                    }

                    $updated_json = json_encode($updated_files);
                    $dataTypeContent->file = $updated_json;
                }
            }

        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function create(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? new $dataType->model_name()
            : false;

        // retrieve special parameter from request
        $requestId = $request->input('request_id');
        if (!isset($requestId))
            $requestId = '';

        // check if session data exists
        $sessionData = $request->session()->get('showcases.browse_media-' . $requestId);
        if (isset($sessionData)) {
            // override data
            foreach ($dataType->editRows as $key => $row) {
                $row_field = $row->field;
                if (array_key_exists($row_field, $sessionData)) {
                    $dataTypeContent->$row_field = $sessionData[$row_field];
                }
                else if (array_key_exists($row_field . '_datepart', $sessionData)) {
                    // parse date time
                    // get date and time part
                    $contentDatePart = $sessionData[$row_field . '_datepart'];
                    $contentTimePart = $sessionData[$row_field . '_timepart'];

                    if (!isset($contentDatePart) || empty($contentDatePart)) {
                        $dataTypeContent->$row_field = null;
                        continue;
                    }
                    if (!isset($contentTimePart) || empty($contentTimePart)) {
                        $contentTimePart = '12:00 AM';
                    }
                    // parse date time
                    $contentInput = $contentDatePart . ' ' . $contentTimePart;
                    $format = 'j/n/Y+ g:i A';
                    $dateInfo = date_parse_from_format($format, $contentInput);
                    $carbonDate = Carbon::create($dateInfo['year'], $dateInfo['month'], $dateInfo['day'], $dateInfo['hour'], $dateInfo['minute']);
                    $dataTypeContent->$row_field = $carbonDate->toDateTimeString();
                }
                else if ($row_field == 'file') {
                    // get file list from session
                    $file_files = (array_key_exists('file_files', $sessionData)) ? $sessionData['file_files'] : [];
                    $file_json =  $dataTypeContent->file;
                    if (!isset($file_json))
                        $file_json = '[]';
                    $file_objs = json_decode($file_json);
                    $updated_files = array();
                    foreach($file_objs as $obj) {
                        $link = $obj->download_link;
                        $found = false;
                        foreach($file_files as $session_file) {
                            if ($link == $session_file) {
                                $found  = true;
                                break;
                            }
                        }
                        if ($found) {
                            $updated_files[] = $obj;
                        }
                    }

                    // get selected files
                    $selectedFiles = $request->session()->get('showcases.browse_media_files-' . $requestId);
                    if (isset($selectedFiles)) {
                        foreach($selectedFiles as $newfile) {
                            $download_link = ltrim(str_replace('/', "\\", $newfile), "\\");
                            $ext = pathinfo($newfile);
                            $updated_files[] = array(
                                'download_link' => ltrim(str_replace('/', "\\", $newfile), "\\"),
                                'original_name' => $ext['basename'],
                                'mime_type' => $this->getMimeType($ext['extension']),
                                'reference_only' => 'true',
                                'added' => 'true'
                            );
                        }
                    }

                    $updated_json = json_encode($updated_files);
                    $dataTypeContent->file = $updated_json;
                }
            }
        }

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // check if browse media button click, this is very specific implementation
        $browseMedia = $request->input('file_browse_media');
        if (isset($browseMedia)) {
            // create uuid
            $uuid = (string) Str::uuid();
            // data
            $session_data = $request->all();
            $session_data['redirect'] = $request->url() . '/create';
            // store request inputs into session
            $request->session()->put('showcases.browse_media-' . $uuid, $session_data);
            // redirect to media index page
            return redirect()->route('voyager.library.index', array('request_id'=>$uuid));
        }


        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.{$dataType->slug}.index");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => __('voyager::generic.successfully_added_new')." {$dataType->getTranslatedAttribute('display_name_singular')}",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }

    private function getMimeType($extension) {
        // set of types
        $types = array(
            'ai'      => 'application/postscript',
            'aif'     => 'audio/x-aiff',
            'aifc'    => 'audio/x-aiff',
            'aiff'    => 'audio/x-aiff',
            'asc'     => 'text/plain',
            'atom'    => 'application/atom+xml',
            'atom'    => 'application/atom+xml',
            'au'      => 'audio/basic',
            'avi'     => 'video/x-msvideo',
            'bcpio'   => 'application/x-bcpio',
            'bin'     => 'application/octet-stream',
            'bmp'     => 'image/bmp',
            'cdf'     => 'application/x-netcdf',
            'cgm'     => 'image/cgm',
            'class'   => 'application/octet-stream',
            'cpio'    => 'application/x-cpio',
            'cpt'     => 'application/mac-compactpro',
            'csh'     => 'application/x-csh',
            'css'     => 'text/css',
            'csv'     => 'text/csv',
            'dcr'     => 'application/x-director',
            'dir'     => 'application/x-director',
            'djv'     => 'image/vnd.djvu',
            'djvu'    => 'image/vnd.djvu',
            'dll'     => 'application/octet-stream',
            'dmg'     => 'application/octet-stream',
            'dms'     => 'application/octet-stream',
            'doc'     => 'application/msword',
            'dtd'     => 'application/xml-dtd',
            'dvi'     => 'application/x-dvi',
            'dxr'     => 'application/x-director',
            'eps'     => 'application/postscript',
            'etx'     => 'text/x-setext',
            'exe'     => 'application/octet-stream',
            'ez'      => 'application/andrew-inset',
            'gif'     => 'image/gif',
            'gram'    => 'application/srgs',
            'grxml'   => 'application/srgs+xml',
            'gtar'    => 'application/x-gtar',
            'hdf'     => 'application/x-hdf',
            'hqx'     => 'application/mac-binhex40',
            'htm'     => 'text/html',
            'html'    => 'text/html',
            'ice'     => 'x-conference/x-cooltalk',
            'ico'     => 'image/x-icon',
            'ics'     => 'text/calendar',
            'ief'     => 'image/ief',
            'ifb'     => 'text/calendar',
            'iges'    => 'model/iges',
            'igs'     => 'model/iges',
            'jpe'     => 'image/jpeg',
            'jpeg'    => 'image/jpeg',
            'jpg'     => 'image/jpeg',
            'js'      => 'application/x-javascript',
            'json'    => 'application/json',
            'kar'     => 'audio/midi',
            'latex'   => 'application/x-latex',
            'lha'     => 'application/octet-stream',
            'lzh'     => 'application/octet-stream',
            'm3u'     => 'audio/x-mpegurl',
            'man'     => 'application/x-troff-man',
            'mathml'  => 'application/mathml+xml',
            'me'      => 'application/x-troff-me',
            'mesh'    => 'model/mesh',
            'mid'     => 'audio/midi',
            'midi'    => 'audio/midi',
            'mif'     => 'application/vnd.mif',
            'mov'     => 'video/quicktime',
            'movie'   => 'video/x-sgi-movie',
            'mp2'     => 'audio/mpeg',
            'mp3'     => 'audio/mpeg',
            'mpe'     => 'video/mpeg',
            'mpeg'    => 'video/mpeg',
            'mpg'     => 'video/mpeg',
            'mpga'    => 'audio/mpeg',
            'ms'      => 'application/x-troff-ms',
            'msh'     => 'model/mesh',
            'mxu'     => 'video/vnd.mpegurl',
            'nc'      => 'application/x-netcdf',
            'oda'     => 'application/oda',
            'ogg'     => 'application/ogg',
            'pbm'     => 'image/x-portable-bitmap',
            'pdb'     => 'chemical/x-pdb',
            'pdf'     => 'application/pdf',
            'pgm'     => 'image/x-portable-graymap',
            'pgn'     => 'application/x-chess-pgn',
            'png'     => 'image/png',
            'pnm'     => 'image/x-portable-anymap',
            'ppm'     => 'image/x-portable-pixmap',
            'ppt'     => 'application/vnd.ms-powerpoint',
            'ps'      => 'application/postscript',
            'qt'      => 'video/quicktime',
            'ra'      => 'audio/x-pn-realaudio',
            'ram'     => 'audio/x-pn-realaudio',
            'ras'     => 'image/x-cmu-raster',
            'rdf'     => 'application/rdf+xml',
            'rgb'     => 'image/x-rgb',
            'rm'      => 'application/vnd.rn-realmedia',
            'roff'    => 'application/x-troff',
            'rss'     => 'application/rss+xml',
            'rtf'     => 'text/rtf',
            'rtx'     => 'text/richtext',
            'sgm'     => 'text/sgml',
            'sgml'    => 'text/sgml',
            'sh'      => 'application/x-sh',
            'shar'    => 'application/x-shar',
            'silo'    => 'model/mesh',
            'sit'     => 'application/x-stuffit',
            'skd'     => 'application/x-koan',
            'skm'     => 'application/x-koan',
            'skp'     => 'application/x-koan',
            'skt'     => 'application/x-koan',
            'smi'     => 'application/smil',
            'smil'    => 'application/smil',
            'snd'     => 'audio/basic',
            'so'      => 'application/octet-stream',
            'spl'     => 'application/x-futuresplash',
            'src'     => 'application/x-wais-source',
            'sv4cpio' => 'application/x-sv4cpio',
            'sv4crc'  => 'application/x-sv4crc',
            'svg'     => 'image/svg+xml',
            'svgz'    => 'image/svg+xml',
            'swf'     => 'application/x-shockwave-flash',
            't'       => 'application/x-troff',
            'tar'     => 'application/x-tar',
            'tcl'     => 'application/x-tcl',
            'tex'     => 'application/x-tex',
            'texi'    => 'application/x-texinfo',
            'texinfo' => 'application/x-texinfo',
            'tif'     => 'image/tiff',
            'tiff'    => 'image/tiff',
            'tr'      => 'application/x-troff',
            'tsv'     => 'text/tab-separated-values',
            'txt'     => 'text/plain',
            'ustar'   => 'application/x-ustar',
            'vcd'     => 'application/x-cdlink',
            'vrml'    => 'model/vrml',
            'vxml'    => 'application/voicexml+xml',
            'wav'     => 'audio/x-wav',
            'wbmp'    => 'image/vnd.wap.wbmp',
            'wbxml'   => 'application/vnd.wap.wbxml',
            'wml'     => 'text/vnd.wap.wml',
            'wmlc'    => 'application/vnd.wap.wmlc',
            'wmls'    => 'text/vnd.wap.wmlscript',
            'wmlsc'   => 'application/vnd.wap.wmlscriptc',
            'wrl'     => 'model/vrml',
            'xbm'     => 'image/x-xbitmap',
            'xht'     => 'application/xhtml+xml',
            'xhtml'   => 'application/xhtml+xml',
            'xls'     => 'application/vnd.ms-excel',
            'xml'     => 'application/xml',
            'xpm'     => 'image/x-xpixmap',
            'xsl'     => 'application/xml',
            'xslt'    => 'application/xslt+xml',
            'xul'     => 'application/vnd.mozilla.xul+xml',
            'xwd'     => 'image/x-xwindowdump',
            'xyz'     => 'chemical/x-xyz',
            'zip'     => 'application/zip'
        );

        if (array_key_exists(strtolower($extension), $types)) {
            return $types[strtolower($extension)];
        }
        return 'unknown/'.strtolower($extension);
    }
}
