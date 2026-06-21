<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionBreedingPapanKerja extends Model
{
    protected $table = 'transaction_breeding_papan_kerja';

    protected $fillable = [
        'transactionId',
        'activity',
        'scheduledDate',
        'time',
        'instructions',
        'isDone',
        'statusAktivitas',
        'temuan',
        'catatan',
        'foto',
        'completedBy',
        'completedAt',
        'userId',
    ];

    protected $casts = [
        'isDone'      => 'boolean',
        'completedAt' => 'datetime',
    ];
}
