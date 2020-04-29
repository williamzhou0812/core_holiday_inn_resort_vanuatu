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
            // redirect to media index page
            return redirect()->route('voyager.library.index', array('request_id'=>$uuid));
        }

        // find all fields that have media file type
        $originalList = array();
        foreach ($dataType->editRows->where('type', 'media_files') as $row) {
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
                $deleted_list[] = $toCheck;
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
            }
        }



        //throw new \Mockery\CountValidator\Exception("ddd");

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

}
