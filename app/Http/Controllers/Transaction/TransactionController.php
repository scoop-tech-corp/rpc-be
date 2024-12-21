<?php

namespace App\Http\Controllers\Transaction;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function index(Request $request)
    {

    }

    public function create(Request $request) {}

    public function update(Request $request) {}

    public function delete(Request $request) {}

    public function TransactionCategory()
    {
        $data = ['Pet Clinic', 'Pet Hotel', 'Pet Salon', 'Pacak'];

        return responseList($data);
    }
}
