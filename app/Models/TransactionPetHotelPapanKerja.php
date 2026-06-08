<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetHotelPapanKerja extends Model
{
    protected $table = 'transaction_pet_hotel_papan_kerja';

    protected $fillable = [
        'transactionId',
        'type',
        'scheduledDate',
        'time',
        'activity',
        'instructions',
        'isDone',
        'statusAktivitas',
        'temuan',
        'kondisiFeses',
        'catatan',
        'foto',
        'completedBy',
        'completedAt',
        'userId',
    ];

    protected $casts = [
        'instructions' => 'array',
        'temuan'       => 'array',
        'isDone'       => 'boolean',
        'completedAt'  => 'datetime',
    ];
}
