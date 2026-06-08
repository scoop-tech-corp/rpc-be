<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CageMaintenance extends Model
{
    protected $table = 'cage_maintenances';

    protected $fillable = [
        'cageId',
        'title',
        'description',
        'status',
        'reportedBy',
        'assignedTo',
        'estimatedDone',
        'completedAt',
        'completionNote',
        'userId',
        'userUpdateId',
    ];

    public function cage()
    {
        return $this->belongsTo(Cage::class, 'cageId', 'id');
    }
}
