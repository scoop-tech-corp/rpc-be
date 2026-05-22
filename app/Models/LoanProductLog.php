<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LoanProductLog extends Model
{
    protected $table = 'loanProductLogs';

    protected $guarded = ['id'];

    protected $fillable = [
        'loanProductId', 'action', 'description', 'userId',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    public function loan()
    {
        return $this->belongsTo(LoanProduct::class, 'loanProductId');
    }
}
