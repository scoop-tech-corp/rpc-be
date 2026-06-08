<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CageCleaningLog extends Model
{
    protected $table = 'cage_cleaning_logs';

    protected $fillable = [
        'cageId',
        'cleaningStatus',
        'cleanedAt',
        'catatan',
        'userId',
    ];

    public function cage()
    {
        return $this->belongsTo(Cage::class, 'cageId', 'id');
    }
}
