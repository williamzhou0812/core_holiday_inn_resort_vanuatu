<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mockery\CountValidator\Exception;
use Illuminate\Http\Request;

class MediaFiles extends BaseType
{
    /**
     * @var
     */
    protected $original_data;

    public function __construct(Request $request, $slug, $row, $options, $original_data)
    {
        parent::__construct($request, $slug, $row, $options);
        $this->original_data = $original_data;
    }

    /**
     * @return string
     */
    public function handle()
    {
        // get original list
        $original_list = array();
        if (isset($this->original_data) && !empty($this->original_data)) {
            $original_list = json_decode($this->original_data);
        }

        // find any that was deleted
        $existing_list = $this->request->input($this->row->field . '_files');
        if (!isset($existing_list)) {
            $existing_list = array();
        }
        $deleted_list = array();
        $remaining_list = array();
        foreach($original_list as $fileinfo) {
            // check if this file still remain in existing list
            $found = false;
            foreach($existing_list as $existing_file_link) {
                if ($fileinfo->download_link == $existing_file_link) {
                    $found = true;
                    break;
                }
            }
            // add to corresponding list
            if (!$found) {
                $deleted_list[] = $fileinfo;
            }
            else {
                $remaining_list[] = $fileinfo;
            }
        }

        // check if files being uploaded
        if (!$this->request->hasFile($this->row->field)) {
            return json_encode($remaining_list);
        }

        // process uploaded files
        $files = Arr::wrap($this->request->file($this->row->field));
        $path = $this->generatePath();

        foreach ($files as $file) {
            $filename = $this->generateFileName($file, $path);
            $file->storeAs(
                $path,
                $filename.'.'.$file->getClientOriginalExtension(),
                config('voyager.storage.disk', 'public')
            );

            array_push($remaining_list, [
                'download_link' => $path.$filename.'.'.$file->getClientOriginalExtension(),
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType()
            ]);
        }
        return json_encode($remaining_list);
    }

    /**
     * @return string
     */
    protected function generatePath()
    {
        return $this->slug.DIRECTORY_SEPARATOR.date('FY').DIRECTORY_SEPARATOR;
    }

    /**
     * @return string
     */
    protected function generateFileName($file, $path)
    {
        if (isset($this->options->preserveFileUploadName) && $this->options->preserveFileUploadName) {
            $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension());
            $filename_counter = 1;

            // Make sure the filename does not exist, if it does make sure to add a number to the end 1, 2, 3, etc...
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = basename($file->getClientOriginalName(), '.'.$file->getClientOriginalExtension()).(string) ($filename_counter++);
            }
        } else {
            $filename = Str::random(20);

            // Make sure the filename does not exist, if it does, just regenerate
            while (Storage::disk(config('voyager.storage.disk'))->exists($path.$filename.'.'.$file->getClientOriginalExtension())) {
                $filename = Str::random(20);
            }
        }

        return $filename;
    }
}
