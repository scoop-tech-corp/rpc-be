<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CageInspection extends Model
{
    protected $table = 'cage_inspections';

    protected $fillable = [
        'cageId',
        'conditionResult',
        'findings',
        'recommendation',
        'createMaintenance',
        'inspectedAt',
        'userId',
    ];

    public function cage()
    {
        return $this->belongsTo(Cage::class, 'cageId', 'id');
    }
}
