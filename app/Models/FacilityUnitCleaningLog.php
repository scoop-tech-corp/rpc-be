<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUnitCleaningLog extends Model
{
    protected $table = 'facility_unit_cleaning_logs';

    protected $fillable = [
        'facilityUnitId',
        'cleaningStatus',
        'cleanedAt',
        'catatan',
        'userId',
    ];

    protected $casts = [
        'cleanedAt' => 'datetime',
    ];

    public function facilityUnit()
    {
        return $this->belongsTo(\App\Models\Facility\FacilityUnit::class, 'facilityUnitId');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userId');
    }
}
