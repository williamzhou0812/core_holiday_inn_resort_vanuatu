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

class VoyagerPageController extends VoyagerBaseController
{
    use BreadRelationshipParser;

    public function index(Request $request)
    {
        // get all available sections
        $sectionDataTypeContent = DB::table('sections')
            ->select('id','title','position', 'table_reference')
            ->whereNotNull('table_reference')
            ->orderBy('position','asc')->get();

        // set default $tableReference as first item table reference value
        $tableReference = '';
        if (sizeof($sectionDataTypeContent) > 0)
            $tableReference = $sectionDataTypeContent[0]->table_reference;

        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

        // Check permission
        $this->authorize('browse', app($dataType->model_name));

        $getter = $dataType->server_side ? 'paginate' : 'get';

        $search = (object) ['value' => $request->get('s'), 'key' => $request->get('key'), 'filter' => $request->get('filter')];

        $searchNames = [];
        if ($dataType->server_side) {
            $searchable = SchemaManager::describeTable(app($dataType->model_name)->getTable())->pluck('name')->toArray();
            $dataRow = Voyager::model('DataRow')->whereDataTypeId($dataType->id)->get();
            foreach ($searchable as $key => $value) {
                $displayName = $dataRow->where('field', $value)->first()->getTranslatedAttribute('display_name');
                $searchNames[$value] = $displayName ?: ucwords(str_replace('_', ' ', $value));
            }
        }

        $orderBy = $request->get('order_by', $dataType->order_column);
        $sortOrder = $request->get('sort_order', null);
        $usesSoftDeletes = false;
        $showSoftDeleted = false;

        // Next Get or Paginate the actual content from the MODEL that corresponds to the slug DataType
        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $query = $model->{$dataType->scope}();
            } else {
                $query = $model::select('*');
            }

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model)) && Auth::user()->can('delete', app($dataType->model_name))) {
                $usesSoftDeletes = true;

                if ($request->get('showSoftDeleted')) {
                    $showSoftDeleted = true;
                    $query = $query->withTrashed();
                }
            }

            // If a column has a relationship associated with it, we do not want to show that field
            $this->removeRelationshipField($dataType, 'browse');

            if ($search->value != '' && $search->key && $search->filter) {
                $search_filter = ($search->filter == 'equals') ? '=' : 'LIKE';
                $search_value = ($search->filter == 'equals') ? $search->value : '%'.$search->value.'%';
                $query->where($search->key, $search_filter, $search_value);
            }

            if ($orderBy && in_array($orderBy, $dataType->fields())) {
                $querySortOrder = (!empty($sortOrder)) ? $sortOrder : 'desc';
                $dataTypeContent = call_user_func([
                    $query->orderBy($orderBy, $querySortOrder),
                    $getter,
                ]);
            } elseif ($model->timestamps) {
                $dataTypeContent = call_user_func([$query->latest($model::CREATED_AT), $getter]);
            } else {
                $dataTypeContent = call_user_func([$query->orderBy($model->getKeyName(), 'DESC'), $getter]);
            }

            // Replace relationships' keys for labels and create READ links if a slug is provided.
            $dataTypeContent = $this->resolveRelations($dataTypeContent, $dataType);
        } else {
            // If Model doesn't exist, get data from table name
            $dataTypeContent = call_user_func([DB::table($dataType->name), $getter]);
            $model = false;
        }

        // Check if BREAD is Translatable
        if (($isModelTranslatable = is_bread_translatable($model))) {
            $dataTypeContent->load('translations');
        }

        // Check if server side pagination is enabled
        $isServerSide = isset($dataType->server_side) && $dataType->server_side;

        // Check if a default search key is set
        $defaultSearchKey = $dataType->default_search_key ?? null;

        // Actions
        $actions = [];
        if (!empty($dataTypeContent->first())) {
            foreach (Voyager::actions() as $action) {
                $action = new $action($dataType, $dataTypeContent->first());

                if ($action->shouldActionDisplayOnDataType()) {
                    $actions[] = $action;
                }
            }
        }

        // Define showCheckboxColumn
        $showCheckboxColumn = false;
        if (Auth::user()->can('delete', app($dataType->model_name))) {
            $showCheckboxColumn = true;
        } else {
            foreach ($actions as $action) {
                if (method_exists($action, 'massAction')) {
                    $showCheckboxColumn = true;
                }
            }
        }

        // Define show order button
        $showPositioning = (isset($dataType->order_column));
        $columnAlign = 0;
        if ($showCheckboxColumn)
            $columnAlign++;
        if ($showPositioning)
            $columnAlign++;

        // Define orderColumn
        $orderColumn = [];
        if ($orderBy) {
            $index = $dataType->browseRows->where('field', $orderBy)->keys()->first() + $columnAlign;
            $orderColumn = [[$index, 'desc']];
            if (!$sortOrder && isset($dataType->order_direction)) {
                $sortOrder = $dataType->order_direction;
                $orderColumn = [[$index, $dataType->order_direction]];
            } else {
                $orderColumn = [[$index, 'desc']];
            }
        }

        $view = 'voyager::pages.browse';

        return Voyager::view($view, compact(
            'actions',
            'dataType',
            'dataTypeContent',
            'isModelTranslatable',
            'search',
            'orderBy',
            'orderColumn',
            'sortOrder',
            'searchNames',
            'isServerSide',
            'defaultSearchKey',
            'usesSoftDeletes',
            'showSoftDeleted',
            'showCheckboxColumn',
            'sectionDataTypeContent'
        ));
    }

    public function show(Request $request, $id)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

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

        $view = 'voyager::pages.read';

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable', 'isSoftDeleted'));
    }

    public function edit(Request $request, $id)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

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

        $view = 'voyager::pages.edit-add';

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    // POST BR(E)AD
    public function update(Request $request, $id)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

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

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', $model)) {
            $redirect = redirect()->route("voyager.pages.index", array('table'=>$dataType->name));
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => __('voyager::generic.successfully_updated')." {$dataType->getTranslatedAttribute('display_name_singular')}",
            'alert-type' => 'success',
        ]);
    }


    public function create(Request $request)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
            ? new $dataType->model_name()
            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        $view = 'voyager::pages.edit-add';

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    /**
     * POST BRE(A)D - Store data.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;
        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("voyager.pages.index", array('table'=>$dataType->name));
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

    public function destroy(Request $request, $id)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

        // Check permission
        $this->authorize('delete', app($dataType->model_name));

        // Init array of IDs
        $ids = [];
        if (empty($id)) {
            // Bulk delete, get IDs from POST
            $ids = explode(',', $request->ids);
        } else {
            // Single item delete, get ID from URL
            $ids[] = $id;
        }
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }
        }

        $displayName = count($ids) > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }

        return redirect()->route("voyager.pages.index", array('table'=>$dataType->name))->with($data);
    }

    public function order(Request $request)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        if (!isset($dataType->order_column) || !isset($dataType->order_display_column)) {
            return redirect()
                ->route("voyager.{$dataType->slug}.index")
                ->with([
                    'message'    => __('voyager::bread.ordering_not_set'),
                    'alert-type' => 'error',
                ]);
        }

        $model = app($dataType->model_name);
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $model = $model->withTrashed();
        }
        $results = $model->orderBy($dataType->order_column, $dataType->order_direction)->get();

        $display_column = $dataType->order_display_column;

        $dataRow = Voyager::model('DataRow')->whereDataTypeId($dataType->id)->whereField($display_column)->first();

        $view = 'voyager::pages.order';

        return Voyager::view($view, compact(
            'dataType',
            'display_column',
            'dataRow',
            'results'
        ));
    }

    public function update_order(Request $request)
    {
        // set default $tableReference as first item table reference value
        $tableReference = '';
        // check if parameter request exists
        $tableRequest = $request->query('table');
        if (isset($tableRequest) && !empty($tableRequest)) {
            $tableReference = $tableRequest;
        }

        // retrieve data type
        $dataType = null;
        if (isset($tableReference)) {
            // GET THE DataType based on the name
            $dataType = Voyager::model('DataType')->where('name', '=', $tableReference)->first();
        }
        else {
            // GET THE SLUG, ex. 'posts', 'pages', etc.
            $slug = $this->getSlug($request);

            // GET THE DataType based on the slug
            $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();
        }

        // check if data type exists
        if (!isset($dataType)) {
            abort(404); // request table not found, show 404 page
        }

        // ensure slug is provided
        $slug = $dataType->slug;

        // Check permission
        $this->authorize('edit', app($dataType->model_name));

        $model = app($dataType->model_name);

        $order = json_decode($request->input('order'));
        $column = $dataType->order_column;
        foreach ($order as $key => $item) {
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $i = $model->withTrashed()->findOrFail($item->id);
            } else {
                $i = $model->findOrFail($item->id);
            }
            $i->$column = ($key + 1);
            $i->save();
        }
    }
}
