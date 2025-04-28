<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransactionPetClinicCheckUpResult extends Model
{
    protected $table = "transactionPetClinicCheckUpResults";

    protected $dates = ['created_at', 'deletedAt'];

    protected $guarded = ['id'];

    protected $fillable = [
        'transactionPetClinicId',
        'weight',
        'weightCategory',
        'temperature',
        'temperatureBottom',
        'temperatureTop',
        'temperatureCategory',
        'isLice',
        'noteLice',
        'isFlea',
        'noteFlea',
        'isCaplak',
        'noteCaplak',
        'isTungau',
        'noteTungau',
        'ectoParasitCategory',
        'isNematoda',
        'noteNematoda',
        'isTermatoda',
        'noteTermatoda',
        'isCestode',
        'noteCestode',
        'isFungiFound',
        'konjung',
        'ginggiva',
        'ear',
        'tongue',
        'nose',
        'CRT',
        'genitals',
        'neurologicalFindings',
        'lokomosiFindings',
        'isSnot',
        'noteSnot',
        'breathType',
        'breathSoundType',
        'breathSoundNote',
        'othersFoundBreath',
        'isPulsus',
        'heartSound',
        'othersFoundHeart',
        'othersFoundSkin',
        'othersFoundHair',
        'maleTesticles',
        'othersMaleTesticles',
        'penisCondition',
        'vaginalDischargeType',
        'urinationType',
        'othersUrination',
        'othersFoundUrogenital',
        'abnormalitasCavumOris',
        'intestinalPeristalsis',
        'perkusiAbdomen',
        'rektumKloaka',
        'othersCharacterRektumKloaka',
        'fecesForm',
        'fecesColor',
        'fecesWithCharacter',
        'othersFoundDigesti',
        'reflectPupil',
        'eyeBallCondition',
        'othersFoundVision',
        'earlobe',
        'isEarwax',
        'earwaxCharacter',
        'othersFoundEar',
        'userId',
        'userUpdateId'
    ];

}
