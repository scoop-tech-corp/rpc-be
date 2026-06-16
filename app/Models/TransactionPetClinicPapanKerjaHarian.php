<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicPapanKerjaHarian extends Model
{
    protected $table = 'transactionPetClinicPapanKerjaHarian';

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionId', 'activityName', 'activityNote', 'tanggal', 'time',
        'status', 'doneNote', 'doneBy', 'doneAt',
        // Field klinis vetnurse
        'statusAktivitas', 'kondisiUmum', 'nafsuMakan',
        'outputFeses', 'outputUrin', 'obatDiberikan', 'catatanObat',
        'catatan', 'foto',
        'isDeleted', 'userId', 'userUpdateId',
    ];

    protected $casts = [
        'obatDiberikan' => 'boolean',
        'doneAt'        => 'datetime',
    ];
}
