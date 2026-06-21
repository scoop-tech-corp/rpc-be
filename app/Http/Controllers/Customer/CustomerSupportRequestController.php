<?php

namespace App\Http\Controllers\Customer;

use DB;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Customer\CustomerSupportRequest;
use App\Models\Customer\CustomerSupportRequestHistory;

class CustomerSupportRequestController extends Controller
{
    // ── Index (admin list) ────────────────────────────────────────────────────

    public function index(Request $request)
    {
        try {
            $query = DB::table('customer_support_requests as csr')
                ->leftJoin('customer as c', 'c.id', '=', 'csr.customerId')
                ->leftJoin('location as l', 'l.id', '=', 'csr.locationId')
                ->leftJoin('users as u', 'u.id', '=', 'csr.handledBy')
                ->select(
                    'csr.id',
                    'csr.customerId',
                    DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                    'csr.locationId',
                    'l.locationName',
                    'csr.subject',
                    'csr.message',
                    'csr.status',
                    'csr.handledBy',
                    DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as handledByName"),
                    'csr.resolvedAt',
                    'csr.created_at'
                )
                ->where('csr.isDeleted', 0);

            if ($request->filled('keyword')) {
                $kw = $request->keyword;
                $query->where(function ($q) use ($kw) {
                    $q->where(DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,''))"), 'like', "%{$kw}%")
                      ->orWhere('csr.subject', 'like', "%{$kw}%")
                      ->orWhere('csr.message', 'like', "%{$kw}%");
                });
            }

            if ($request->filled('locationId') && $request->locationId !== '') {
                $query->where('csr.locationId', $request->locationId);
            }

            if ($request->filled('status')) {
                $query->where('csr.status', $request->status);
            }

            if ($request->filled('orderColumn') && $request->filled('orderValue')) {
                $query->orderBy($request->orderColumn, $request->orderValue);
            } else {
                $query->orderBy('csr.created_at', 'desc');
            }

            $result = paginateData($query, $request);

            return responseIndex((int) $result['totalPagination'], $result['data']);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Create (admin input) ──────────────────────────────────────────────────

    public function create(Request $request)
    {
        $request->validate([
            'customerId' => 'required|integer',
            'subject'    => 'required|string|max:255',
            'message'    => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $csr = CustomerSupportRequest::create([
                'customerId' => $request->customerId,
                'locationId' => $request->locationId ?: null,
                'subject'    => $request->subject,
                'message'    => $request->message,
                'status'     => 'open',
                'isDeleted'  => 0,
            ]);

            // catat history awal
            CustomerSupportRequestHistory::create([
                'supportRequestId' => $csr->id,
                'fromStatus'       => null,
                'toStatus'         => 'open',
                'changedBy'        => $request->user()->id,
                'notes'            => 'Pengajuan dibuat oleh admin',
            ]);

            DB::commit();
            if ($csr->locationId) {
                sendNotificationToStaffAtLocation($csr->locationId, [1, 6], 'support', "Permintaan dukungan baru: {$csr->subject}", 'info');
            }
            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Self-submit (customer portal) ─────────────────────────────────────────

    /**
     * Customer yang sudah login (roleId=4) submit pengajuan sendiri.
     * customerId di-resolve dari users.id → customer.userId.
     */
    public function selfSubmit(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $userId   = $request->user()->id;
            $customer = DB::table('customer')->where('userId', $userId)->where('isDeleted', 0)->first();

            if (!$customer) {
                return responseInvalid(['Akun Anda belum terhubung ke data customer. Hubungi administrator.']);
            }

            $csr = CustomerSupportRequest::create([
                'customerId' => $customer->id,
                'locationId' => $customer->locationId ?: null,
                'subject'    => $request->subject,
                'message'    => $request->message,
                'status'     => 'open',
                'isDeleted'  => 0,
            ]);

            CustomerSupportRequestHistory::create([
                'supportRequestId' => $csr->id,
                'fromStatus'       => null,
                'toStatus'         => 'open',
                'changedBy'        => $userId,
                'notes'            => 'Pengajuan dibuat oleh customer',
            ]);

            DB::commit();
            if ($csr->locationId) {
                sendNotificationToStaffAtLocation($csr->locationId, [1, 6], 'support', "Permintaan dukungan baru dari customer: {$csr->subject}", 'info');
            }
            return responseCreate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── My requests (customer portal — list milik sendiri) ────────────────────

    public function myRequests(Request $request)
    {
        try {
            $userId   = $request->user()->id;
            $customer = DB::table('customer')->where('userId', $userId)->where('isDeleted', 0)->first();

            if (!$customer) {
                return responseIndex(0, []);
            }

            $query = DB::table('customer_support_requests as csr')
                ->leftJoin('location as l', 'l.id', '=', 'csr.locationId')
                ->leftJoin('users as u', 'u.id', '=', 'csr.handledBy')
                ->select(
                    'csr.id',
                    'csr.subject',
                    'csr.message',
                    'csr.status',
                    'l.locationName',
                    DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as handledByName"),
                    'csr.resolvedAt',
                    'csr.created_at'
                )
                ->where('csr.customerId', $customer->id)
                ->where('csr.isDeleted', 0)
                ->orderBy('csr.created_at', 'desc');

            $result = paginateData($query, $request);
            return responseIndex((int) $result['totalPagination'], $result['data']);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Update (admin — ubah status, handledBy) ───────────────────────────────

    public function update(Request $request)
    {
        $request->validate([
            'supportRequestId' => 'required|integer',
            'customerId'       => 'required|integer',
            'subject'          => 'required|string|max:255',
            'message'          => 'required|string',
            'status'           => 'required|in:open,in_progress,closed',
        ]);

        DB::beginTransaction();
        try {
            $old = CustomerSupportRequest::find($request->supportRequestId);
            if (!$old) {
                return responseInvalid(['Data tidak ditemukan.']);
            }

            $authUserId = $request->user()->id;
            $newStatus  = $request->status;

            $updateData = [
                'customerId' => $request->customerId,
                'locationId' => $request->locationId ?: null,
                'subject'    => $request->subject,
                'message'    => $request->message,
                'status'     => $newStatus,
                'updated_at' => Carbon::now(),
            ];

            // handledBy: auto-assign logged-in user saat status in_progress
            if ($newStatus === 'in_progress') {
                $updateData['handledBy'] = $authUserId;
            }

            // resolvedAt: set saat closed
            if ($newStatus === 'closed') {
                $updateData['resolvedAt'] = Carbon::now();
                // handledBy tetap yang sudah ada, atau assign jika belum ada
                if (!$old->handledBy) {
                    $updateData['handledBy'] = $authUserId;
                }
            }

            CustomerSupportRequest::where('id', $request->supportRequestId)->update($updateData);

            // Catat history jika status berubah
            if ($old->status !== $newStatus) {
                CustomerSupportRequestHistory::create([
                    'supportRequestId' => $request->supportRequestId,
                    'fromStatus'       => $old->status,
                    'toStatus'         => $newStatus,
                    'changedBy'        => $authUserId,
                    'notes'            => $request->notes ?: null,
                ]);
            }

            DB::commit();
            return responseUpdate();
        } catch (\Exception $e) {
            DB::rollBack();
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── History ───────────────────────────────────────────────────────────────

    public function history(Request $request)
    {
        $request->validate(['supportRequestId' => 'required|integer']);

        try {
            $rows = DB::table('customer_support_request_histories as h')
                ->leftJoin('users as u', 'u.id', '=', 'h.changedBy')
                ->select(
                    'h.id',
                    'h.fromStatus',
                    'h.toStatus',
                    'h.notes',
                    DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as changedByName"),
                    'h.created_at'
                )
                ->where('h.supportRequestId', $request->supportRequestId)
                ->orderBy('h.created_at', 'asc')
                ->get();

            return response()->json(['data' => $rows]);
        } catch (\Exception $e) {
            return responseInvalid([$e->getMessage()]);
        }
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function delete(Request $request)
    {
        $request->validate([
            'supportRequestId' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $ids = is_array($request->supportRequestId) ? $request->supportRequestId : [$request->supportRequestId];

            CustomerSupportRequest::whereIn('id', $ids)->update([
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
