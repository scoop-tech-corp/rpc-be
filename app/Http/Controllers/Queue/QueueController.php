<?php

namespace App\Http\Controllers\Queue;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    // Prefix per service type untuk nomor antrian
    private const PREFIX_MAP = [
        'Pet Clinic' => 'C',
        'Pet Hotel'  => 'H',
        'Pet Salon'  => 'S',
        'Breeding'   => 'B',
    ];

    // -------------------------------------------------------------------------
    // GET /queue  — daftar antrian hari ini (staff, butuh auth)
    // -------------------------------------------------------------------------
    public function index(Request $request)
    {
        $today = Carbon::today()->toDateString();

        $query = DB::table('queues as q')
            ->join('customer as c', 'q.customerId', '=', 'c.id')
            ->join('customerPets as p', 'q.petId', '=', 'p.id')
            ->join('location as l', 'q.locationId', '=', 'l.id')
            ->leftJoin('users as u', 'q.doctorId', '=', 'u.id')
            ->leftJoin('bookings as b', 'q.bookingId', '=', 'b.id')
            ->select([
                'q.id',
                'q.queueNumber',
                'q.serviceType',
                'q.status',
                'q.chiefComplaint',
                'q.queueDate',
                'q.calledAt',
                'q.startServiceAt',
                'q.endServiceAt',
                'q.bookingId',
                'q.locationId',
                'q.customerId',
                'q.petId',
                'q.doctorId',
                'l.locationName',
                DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                'p.petName',
                DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as doctorName"),
                'q.created_at',
            ])
            ->where('q.isDeleted', 0)
            ->where('q.queueDate', $today)
            ->orderBy('q.id', 'asc');

        if ($request->filled('locationId')) {
            $query->where('q.locationId', $request->locationId);
        }

        if ($request->filled('serviceType')) {
            $query->where('q.serviceType', $request->serviceType);
        }

        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('q.status', $statuses);
        }

        if ($request->filled('queueDate')) {
            $query->where('q.queueDate', $request->queueDate);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /queue  — tambah antrian walk-in
    // -------------------------------------------------------------------------
    public function store(Request $request)
    {
        $request->validate([
            'locationId'    => 'required|integer',
            'customerId'    => 'required|integer',
            'petId'         => 'required|integer',
            'serviceType'   => 'required|in:Pet Clinic,Pet Hotel,Pet Salon,Breeding',
            'chiefComplaint' => 'nullable|string',
            'doctorId'      => 'nullable|integer',
        ]);

        $today  = Carbon::today()->toDateString();
        $prefix = self::PREFIX_MAP[$request->serviceType];

        // Generate nomor antrian: hitung antrian hari ini per lokasi + service type
        $count = Queue::where('queueDate', $today)
            ->where('locationId', $request->locationId)
            ->where('serviceType', $request->serviceType)
            ->where('isDeleted', 0)
            ->count();

        $queueNumber = $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $queue = Queue::create([
            'queueNumber'    => $queueNumber,
            'serviceType'    => $request->serviceType,
            'locationId'     => $request->locationId,
            'customerId'     => $request->customerId,
            'petId'          => $request->petId,
            'doctorId'       => $request->doctorId,
            'bookingId'      => null,
            'chiefComplaint' => $request->chiefComplaint,
            'status'         => 'waiting',
            'queueDate'      => $today,
            'createdBy'      => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Antrian berhasil ditambahkan.',
            'data'    => $queue,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // POST /queue/convert  — konversi dari booking ke antrian
    // -------------------------------------------------------------------------
    public function convertFromBooking(Request $request)
    {
        $request->validate([
            'bookingId'     => 'required|integer',
            'chiefComplaint' => 'nullable|string',
        ]);

        // Ambil data booking
        $booking = DB::table('bookings as b')
            ->where('b.id', $request->bookingId)
            ->where('b.isDeleted', 0)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking tidak ditemukan.'], 404);
        }

        // Cek apakah sudah pernah dikonversi
        $exists = Queue::where('bookingId', $request->bookingId)
            ->where('isDeleted', 0)
            ->first();

        if ($exists) {
            return response()->json(['message' => 'Booking ini sudah dikonversi menjadi antrian.'], 422);
        }

        $today  = Carbon::today()->toDateString();
        $serviceType = $booking->serviceType;
        $prefix = self::PREFIX_MAP[$serviceType] ?? 'X';

        $count = Queue::where('queueDate', $today)
            ->where('locationId', $booking->locationId)
            ->where('serviceType', $serviceType)
            ->where('isDeleted', 0)
            ->count();

        $queueNumber = $prefix . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);

        $queue = Queue::create([
            'queueNumber'    => $queueNumber,
            'serviceType'    => $serviceType,
            'locationId'     => $booking->locationId,
            'customerId'     => $booking->customerId,
            'petId'          => $booking->petId,
            'doctorId'       => $booking->doctorId,
            'bookingId'      => $booking->id,
            'chiefComplaint' => $request->chiefComplaint,
            'status'         => 'waiting',
            'queueDate'      => $today,
            'createdBy'      => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Booking berhasil dikonversi ke antrian.',
            'data'    => $queue,
        ], 201);
    }

    // -------------------------------------------------------------------------
    // PUT /queue/status  — ubah status antrian
    // -------------------------------------------------------------------------
    public function updateStatus(Request $request)
    {
        $request->validate([
            'id'     => 'required|integer',
            'status' => 'required|in:called,in_service,done,no_show',
        ]);

        $queue = Queue::where('id', $request->id)->where('isDeleted', 0)->first();

        if (!$queue) {
            return response()->json(['message' => 'Antrian tidak ditemukan.'], 404);
        }

        $updateData = [
            'status'    => $request->status,
            'updatedBy' => $request->user()->id,
        ];

        $now = Carbon::now();

        if ($request->status === 'called') {
            $updateData['calledAt'] = $now;
        } elseif ($request->status === 'in_service') {
            $updateData['startServiceAt'] = $now;
        } elseif ($request->status === 'done' || $request->status === 'no_show') {
            $updateData['endServiceAt'] = $now;
        }

        $queue->update($updateData);

        return response()->json([
            'message' => 'Status antrian berhasil diperbarui.',
            'data'    => $queue->fresh(),
        ]);
    }

    // -------------------------------------------------------------------------
    // DELETE /queue  — soft delete antrian
    // -------------------------------------------------------------------------
    public function destroy(Request $request)
    {
        $request->validate(['id' => 'required|integer']);

        $queue = Queue::where('id', $request->id)->where('isDeleted', 0)->first();

        if (!$queue) {
            return response()->json(['message' => 'Antrian tidak ditemukan.'], 404);
        }

        $queue->update(['isDeleted' => 1, 'updatedBy' => $request->user()->id]);

        return response()->json(['message' => 'Antrian berhasil dihapus.']);
    }

    // -------------------------------------------------------------------------
    // PUT /queue/reset  — reset antrian hari ini per lokasi (admin/manager only)
    // -------------------------------------------------------------------------
    public function reset(Request $request)
    {
        $request->validate(['locationId' => 'required|integer']);

        $today = Carbon::today()->toDateString();

        Queue::where('queueDate', $today)
            ->where('locationId', $request->locationId)
            ->where('isDeleted', 0)
            ->update(['isDeleted' => 1, 'updatedBy' => $request->user()->id]);

        return response()->json(['message' => 'Antrian hari ini berhasil direset.']);
    }

    // -------------------------------------------------------------------------
    // GET /queue/display  — endpoint publik untuk layar antrian (token-based)
    // -------------------------------------------------------------------------
    public function display(Request $request)
    {
        $token = $request->query('token');

        if (!$token || $token !== config('app.queue_display_token')) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $today = Carbon::today()->toDateString();

        $locationId = $request->query('locationId');

        $query = DB::table('queues as q')
            ->join('customer as c', 'q.customerId', '=', 'c.id')
            ->join('customerPets as p', 'q.petId', '=', 'p.id')
            ->select([
                'q.id',
                'q.queueNumber',
                'q.serviceType',
                'q.status',
                DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                'p.petName',
            ])
            ->where('q.isDeleted', 0)
            ->where('q.queueDate', $today)
            ->whereIn('q.status', ['called', 'in_service', 'waiting'])
            ->orderByRaw("FIELD(q.status, 'in_service', 'called', 'waiting')")
            ->orderBy('q.id', 'asc');

        if ($locationId) {
            $query->where('q.locationId', $locationId);
        }

        $all = $query->get();

        // Antrian sedang dilayani (in_service)
        $inService = $all->filter(fn($q) => $q->status === 'in_service')->values();

        // Antrian dipanggil (called)
        $called = $all->filter(fn($q) => $q->status === 'called')->values();

        // 5 antrian berikutnya (waiting)
        $waiting = $all->filter(fn($q) => $q->status === 'waiting')->take(5)->values();

        return response()->json([
            'inService' => $inService,
            'called'    => $called,
            'waiting'   => $waiting,
            'updatedAt' => Carbon::now()->toDateTimeString(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /queue/booking-candidates  — daftar booking Accepted yang belum jadi antrian
    // -------------------------------------------------------------------------
    public function bookingCandidates(Request $request)
    {
        $today = Carbon::today()->toDateString();

        // Booking yang sudah diconvert ke queue hari ini
        $convertedBookingIds = Queue::where('queueDate', $today)
            ->where('isDeleted', 0)
            ->whereNotNull('bookingId')
            ->pluck('bookingId')
            ->toArray();

        $query = DB::table('bookings as b')
            ->join('customer as c', 'b.customerId', '=', 'c.id')
            ->join('customerPets as p', 'b.petId', '=', 'p.id')
            ->leftJoin('users as u', 'b.doctorId', '=', 'u.id')
            ->select([
                'b.id',
                'b.serviceType',
                'b.bookingTime',
                'b.locationId',
                'b.customerId',
                'b.petId',
                'b.doctorId',
                DB::raw("CONCAT(COALESCE(c.firstName,''), ' ', COALESCE(c.lastName,'')) as customerName"),
                'p.petName',
                DB::raw("CONCAT(COALESCE(u.firstName,''), ' ', COALESCE(u.lastName,'')) as doctorName"),
            ])
            ->where('b.isDeleted', 0)
            ->where('b.status', 1) // Accepted
            ->where('b.isCancelled', 0)
            ->whereDate('b.bookingTime', $today);

        if (!empty($convertedBookingIds)) {
            $query->whereNotIn('b.id', $convertedBookingIds);
        }

        if ($request->filled('locationId')) {
            $query->where('b.locationId', $request->locationId);
        }

        return response()->json([
            'data' => $query->orderBy('b.bookingTime', 'asc')->get(),
        ]);
    }
}
