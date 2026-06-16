<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationLog extends Model
{
    protected $table = 'quotationLogs';

    protected $fillable = [
        'quotationId',
        'fromStatus',
        'toStatus',
        'remarks',
        'changedBy',
    ];
}
