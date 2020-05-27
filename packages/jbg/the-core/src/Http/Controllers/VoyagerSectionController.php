<?php

namespace TCG\Voyager\Http\Controllers;

use Exception;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;
use TCG\Voyager\Http\Controllers\Traits\BreadRelationshipParser;

class VoyagerSectionController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function show(Request $request, $id)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        $isSoftDeleted = false;

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
            if ($dataTypeContent->deleted_at) {
                $isSoftDeleted = true;
            }
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }

        // Replace relationships' keys for labels and create READ links if a slug is provided.
        $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType, true);

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'read');

        // Check permission
        $this->authorize('read', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // SUB SECTION LOGIC
        // get table references
        $tableReference = $dataTypeContent->table_reference;

        // get model for table reference
        $subSectionDataTypeContent = array();
        if (isset($tableReference) && !empty($tableReference)) {
            $referenceDataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();

            $referenceDisplayStatusExists = false;
            $referenceDisplayFromExists = false;
            $referenceDisplayToExists = false;
            $subSectionDataTypeContent = [];
            $selectFields = array('id','title','position');
            // check columns
            if (isset($referenceDataType)) {
                $referenceFieldOptions = SchemaManager::describeTable((strlen($referenceDataType->model_name) != 0)
                        ? DB::getTablePrefix().app($referenceDataType->model_name)->getTable()
                        : DB::getTablePrefix().$referenceDataType->name
                );
                // generate where statement
                $referenceDisplayStatusExists = ($referenceFieldOptions->has('display_status'));
                $referenceDisplayFromExists = ($referenceFieldOptions->has('display_from'));
                $referenceDisplayToExists = ($referenceFieldOptions->has('display_to'));

                $selectFields = array('id','title','position');

                // get records from table for display_status == 1
                if ($referenceDisplayFromExists) {
                    $selectFields[] = 'display_from';
                }
                if ($referenceDisplayToExists) {
                    $selectFields[] = 'display_to';
                }
                $tmpTable = DB::table($tableReference)->select($selectFields);
                if ($referenceDisplayStatusExists)
                {
                    $tmpTable->where('display_status', '1');
                }
                $subSectionDataTypeContent =  $tmpTable->orderBy('position','asc')->get();
            }
        }

        $view = 'voyager::bread.read';

        if (view()->exists("voyager::$slug.read")) {
            $view = "voyager::$slug.read";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted', 'subSectionDataTypeContent','selectFields'));
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

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // SUB SECTION LOGIC
        // get table references
        $tableReference = $dataTypeContent->table_reference;

        $subSectionDataTypeContent = array();
        if (isset($tableReference) && !empty($tableReference)) {
            // get model for table reference
            $referenceDataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();

            $referenceDisplayStatusExists = false;
            $referenceDisplayFromExists = false;
            $referenceDisplayToExists = false;
            $subSectionDataTypeContent = [];
            $selectFields = array('id','title','position');

            if (isset($referenceDataType)) {
                $referenceFieldOptions = SchemaManager::describeTable((strlen($referenceDataType->model_name) != 0)
                        ? DB::getTablePrefix().app($referenceDataType->model_name)->getTable()
                        : DB::getTablePrefix().$referenceDataType->name
                );
                // generate where statement
                $referenceDisplayStatusExists = ($referenceFieldOptions->has('display_status'));
                $referenceDisplayFromExists = ($referenceFieldOptions->has('display_from'));
                $referenceDisplayToExists = ($referenceFieldOptions->has('display_to'));
                $selectFields = array('id','title','position');

                // get records from table for display_status == 1
                if ($referenceDisplayFromExists) {
                    $selectFields[] = 'display_from';
                }
                if ($referenceDisplayToExists) {
                    $selectFields[] = 'display_to';
                }
                $tmpTable = DB::table($tableReference)->select($selectFields);
                if ($referenceDisplayStatusExists)
                {
                    $tmpTable->where('display_status', '1');
                }
                $subSectionDataTypeContent =  $tmpTable->orderBy('position','asc')->get();
            }
        }

        $view = 'voyager::bread.edit-add';

        if (view()->exists("voyager::$slug.edit-add")) {
            $view = "voyager::$slug.edit-add";
        }

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'subSectionDataTypeContent','selectFields'));
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

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();
        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);


        // Reorder position for section table data
        $tableReference = $data->table_reference;
        $ordering = $request->section_table;
        $this->reorderSectionTableData($tableReference, $ordering);

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


    protected function reorderSectionTableData($tableReference, $ordering) {
        // get data type by table name
        $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        if (!isset($dataType))
            return;
        // convert ordering to object
        $newOrder = json_decode($ordering);
        if (!isset($newOrder) || !is_array($newOrder) || sizeof($newOrder) <= 1)
            return; // nothing to reorder

        // get records from table
        $subSectionDataTypeContent = DB::table($tableReference)->select('id','title','position')->orderBy('position','asc')->get();
        $dividedBy = pow(10, strlen(strval(sizeof($newOrder))));
        $lastItem = null;
        foreach($newOrder as $item) {
            // find object from original list
            $found = $subSectionDataTypeContent->firstWhere('id', $item->id);
            if (!isset($found))
            {
                continue; // not found in original list
            }
            // set last post if first time
            if (isset($lastItem)) {
                // check this pos with last pos
                if (floatval($found->position) < floatval($lastItem->position)) {
                    // set position value to be last item + this pos as decimal
                    $increaseBy = floatval($found->position) / $dividedBy;
                    $found->position = floatval($lastItem->position) + $increaseBy;
                }
            }
            // set last pos
            $lastItem = $found;
        }
        // sort by position
        $sorted = $subSectionDataTypeContent->sortBy('position');
        // reset position
        // Begin Transaction
        DB::beginTransaction();
        try {
            $pos = 0;
            foreach ($sorted as $item) {
                // set position
                $item->position = ++$pos;
                // perform update
                DB::table($tableReference)->where('id', $item->id)->update(['position' => $item->position]);
            }
            // Commit Transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback Transaction
            DB::rollback();
        }
    }

    public function store(Request $request)
    {
        $slug = $this->getSlug($request);

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        // set position to zero
        $newId = $data->id;
        $pageModel = app($dataType->model_name);
        $newRecord = $pageModel->findOrFail($newId);
        // set value
        $newRecord->position = 0;
        $newRecord->save();
        // reordering
        $allRecords = $pageModel->orderBy('position')->get();
        $posCount = 1;
        foreach($allRecords as $record) {
            $record->position = $posCount++;
            $record->save();
        }

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
}
