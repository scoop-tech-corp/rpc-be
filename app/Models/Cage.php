<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cage extends Model
{
    protected $table = 'cages';

    protected $fillable = [
        'locationId',
        'cageName',
        'type',
        'size',
        'status',
        'conditionStatus',
        'capacity',
        'amount',
        'notes',
        'isDeleted',
        'userId',
        'userUpdateId',
        'deletedBy',
        'deletedAt',
    ];

    public function location()
    {
        return $this->belongsTo(\App\Models\Location::class, 'locationId', 'id');
    }

    public function inspections()
    {
        return $this->hasMany(CageInspection::class, 'cageId', 'id');
    }

    public function maintenances()
    {
        return $this->hasMany(CageMaintenance::class, 'cageId', 'id');
    }

    public function cleaningLogs()
    {
        return $this->hasMany(CageCleaningLog::class, 'cageId', 'id');
    }
}
