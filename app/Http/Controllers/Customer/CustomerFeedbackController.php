<?php

namespace App\Http\Controllers\Customer;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerFeedback;

class CustomerFeedbackController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = DB::table('customer_feedbacks as cf')
                ->leftJoin('customer as c', 'c.id', '=', 'cf.customerId')
                ->leftJoin('location as l', 'l.id', '=', 'cf.locationId')
                ->select(
                    'cf.id',
                    'cf.customerId',
                    DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                    'cf.locationId',
                    'l.locationName',
                    'cf.transactionId',
                    'cf.transactionType',
                    'cf.rating',
                    'cf.message',
                    'cf.created_at'
                )
                ->where('cf.isDeleted', 0);

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where(DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,''))"), 'like', "%{$kw}%")
                      ->orWhere('cf.message', 'like', "%{$kw}%")
                      ->orWhere('cf.transactionType', 'like', "%{$kw}%");
                });
            }

            if ($request->filled('locationId') && $request->locationId !== '') {
                $query->where('cf.locationId', $request->locationId);
            }

            if ($request->filled('rating')) {
                $query->where('cf.rating', $request->rating);
            }

            if ($request->filled('orderColumn') && $request->filled('orderValue')) {
                $query->orderBy($request->orderColumn, $request->orderValue);
            } else {
                $query->orderBy('cf.created_at', 'desc');
            }

            $result = paginateData($query, $request);

            return responseIndex((int) $result['totalPagination'], $result['data']);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    public function create(Request $request)
    {
        $request->validate([
            'customerId' => 'required|integer',
            'rating'     => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();
        try {
            CustomerFeedback::create([
                'customerId'      => $request->customerId,
                'locationId'      => $request->locationId ?: null,
                'transactionId'   => $request->transactionId ?: null,
                'transactionType' => $request->transactionType ?: null,
                'rating'          => $request->rating,
                'message'         => $request->message ?: null,
                'isDeleted'       => 0,
            ]);

            DB::commit();
            if ($request->locationId) {
                $stars = str_repeat('★', $request->rating) . str_repeat('☆', 5 - $request->rating);
                sendNotificationToStaffAtLocation($request->locationId, [18, 20], 'feedback', "Feedback baru dari customer: {$stars} ({$request->rating}/5).", $request->rating >= 4 ? 'success' : ($request->rating <= 2 ? 'error' : 'warning'));
            }
            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    public function update(Request $request)
    {
        $request->validate([
            'feedbackId' => 'required|integer',
            'customerId' => 'required|integer',
            'rating'     => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();
        try {
            CustomerFeedback::where('id', $request->feedbackId)
                ->update([
                    'customerId'      => $request->customerId,
                    'locationId'      => $request->locationId ?: null,
                    'transactionId'   => $request->transactionId ?: null,
                    'transactionType' => $request->transactionType ?: null,
                    'rating'          => $request->rating,
                    'message'         => $request->message ?: null,
                    'updated_at'      => Carbon::now(),
                ]);

            DB::commit();
            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'feedbackId' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $ids = is_array($request->feedbackId) ? $request->feedbackId : [$request->feedbackId];

            CustomerFeedback::whereIn('id', $ids)->update([
                'isDeleted' => 1,
                'deletedBy' => $request->user()->id,
                'deletedAt' => Carbon::now(),
            ]);

            DB::commit();
            return responseDelete();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }
}
