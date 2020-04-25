<?php

namespace TCG\Voyager\Http\Controllers\ContentTypes;

use Carbon\Carbon;
use Mockery\CountValidator\Exception;

class DateTime extends BaseType
{
    public function handle()
    {
        if (!in_array($this->request->method(), ['PUT', 'POST'])) {
            return;
        }

        // get date and time part
        $contentDatePart = $this->request->input($this->row->field . '_datepart');
        $contentTimePart = $this->request->input($this->row->field . '_timepart');

        if (empty($contentDatePart)) {
            return;
        }
        if (empty($contentTimePart)) {
            $contentTimePart = '12:00 AM';
        }
        // parse date time
        $input = $contentDatePart . ' ' . $contentTimePart;
        $format = 'j/n/Y+ g:i A';
        $dateInfo = date_parse_from_format($format, $input);
        $carbonDate = Carbon::create($dateInfo['year'], $dateInfo['month'], $dateInfo['day'], $dateInfo['hour'], $dateInfo['minute']);
        return $carbonDate;
    }
}
