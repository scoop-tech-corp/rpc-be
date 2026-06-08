<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUnitInspection extends Model
{
    protected $table = 'facility_unit_inspections';

    protected $fillable = [
        'facilityUnitId',
        'conditionResult',
        'findings',
        'recommendation',
        'createMaintenance',
        'inspectedAt',
        'userId',
    ];

    protected $casts = [
        'createMaintenance' => 'boolean',
        'inspectedAt'       => 'datetime',
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
