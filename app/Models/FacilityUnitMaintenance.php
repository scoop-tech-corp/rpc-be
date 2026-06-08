<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityUnitMaintenance extends Model
{
    protected $table = 'facility_unit_maintenances';

    protected $fillable = [
        'facilityUnitId',
        'title',
        'description',
        'status',
        'reportedBy',
        'assignedTo',
        'estimatedDone',
        'completedAt',
        'completionNote',
        'userId',
    ];

    protected $casts = [
        'estimatedDone' => 'date',
        'completedAt'   => 'datetime',
    ];

    public function facilityUnit()
    {
        return $this->belongsTo(\App\Models\Facility\FacilityUnit::class, 'facilityUnitId');
    }

    public function reporter()
    {
        return $this->belongsTo(\App\Models\User::class, 'reportedBy');
    }

    public function assignee()
    {
        return $this->belongsTo(\App\Models\User::class, 'assignedTo');
    }
}
