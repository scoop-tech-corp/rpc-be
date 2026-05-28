<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\bookings;
use Illuminate\Http\Request;
use Validator;
use App\Models\bookingsPetHotel;
use App\Models\bookingsPetSalon;
use App\Models\bookingsBreeding;
use App\Models\bookingsPetClinic;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $data = DB::table('bookings as e')
            ->join('users as u', 'e.userId', 'u.id')
            ->join('customer as c', 'e.customerId', 'c.id')
            ->join('customerPets as p', 'e.petId', 'p.id')
            ->join('location as l', 'e.locationId', 'l.id')
            ->select([
                'e.id',
                // Perbaikan: Menggunakan DB::raw dengan CONCAT agar title tergabung dengan benar
                DB::raw("CONCAT(e.serviceType, ' - ', CASE WHEN c.firstName IS NOT NULL AND c.lastName IS NOT NULL THEN CONCAT(c.firstName, ' ', c.lastName) WHEN c.firstName IS NOT NULL THEN c.firstName WHEN c.lastName IS NOT NULL THEN c.lastName ELSE '' END, ' (', COALESCE(p.petName, ''), ')') as title"),
                'e.bookingTime as start',
                DB::raw("'' as `end`"), // Gunakan DB::raw untuk string kosong agar konsisten
                DB::raw("0 as allDay"),
                DB::raw("CASE e.serviceType
                WHEN 'Pet Hotel' THEN '#FF0000'
                WHEN 'Pet Salon' THEN '#FFFF00'
                WHEN 'Breeding' THEN '#008000'
                WHEN 'Pet Clinic' THEN '#0000FF'
                ELSE '#CCCCCC' END as color"),
                DB::raw("CASE e.serviceType
                WHEN 'Pet Hotel' THEN '#000000'
                WHEN 'Pet Salon' THEN '#000000'
                WHEN 'Breeding' THEN '#FFFFFF'
                WHEN 'Pet Clinic' THEN '#FFFFFF'
                ELSE '#000000' END as textColor"),

                DB::raw("'' as description")
            ])
            ->where('e.isDeleted', '=', 0);

        if ($request->filled('monthBooking') && $request->filled('yearBooking')) {
            $data = $data->whereMonth('e.bookingTime', $request->monthBooking)
                ->whereYear('e.bookingTime', $request->yearBooking);
        }
        // Filter Berdasarkan Lokasi
        if ($request->filled('locationId')) {
            $data = $data->where('e.locationId', $request->locationId);
        }

        if ($request->filled('doctorId')) {
            $data = $data->where('e.doctorId', $request->doctorId);
        }

        return response()->json([
            'data' => $data->get(),
        ]);
    }

    public function create(Request $request)
    {
        $baseRules = [
            'locationId'           => 'required|integer',
            'doctorId'             => 'required|integer',
            'customerId'           => 'required|integer',
            'petId'                => 'required|integer',
            'services'             => 'required|in:Pet Hotel,Pet Salon,Breeding,Pet Clinic',
            'bookingTime'          => 'required|date',
            'emergencyPhoneNumber' => 'required|string',
            'image'                => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        $service = $request->input('services');

        $extraRules = match ($service) {
            'Pet Hotel' => [
                'socializationType'    => 'required|string',
                'emergencyContactName' => 'required|string',
                'inventoryProducts'    => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Pet Salon' => [
                'furCondition'         => 'required|string',
                'skinSensitivity'      => 'required|string',
                'emergencyContactName' => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Breeding' => [
                'stambum'              => 'required|string',
                'healthClearance'      => 'required|string',
                'emergencyContactName' => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Pet Clinic' => [
                'consultationType' => 'required|string',
                'drugAllergy'      => 'nullable|string',
                'additionalInfo'   => 'nullable|string',
            ],
            default => [],
        };

        $messages = [
            'locationId.required'            => 'Lokasi wajib diisi.',
            'locationId.integer'             => 'Lokasi harus berupa angka.',
            'doctorId.required'              => 'Dokter wajib diisi.',
            'doctorId.integer'               => 'Dokter harus berupa angka.',
            'customerId.required'            => 'Pelanggan wajib diisi.',
            'customerId.integer'             => 'Pelanggan harus berupa angka.',
            'petId.required'                 => 'Hewan peliharaan wajib diisi.',
            'petId.integer'                  => 'Hewan peliharaan harus berupa angka.',
            'services.required'              => 'Jenis layanan wajib diisi.',
            'services.in'                    => 'Jenis layanan tidak valid. Pilih salah satu: Pet Hotel, Pet Salon, Breeding, Pet Clinic.',
            'bookingTime.required'           => 'Waktu booking wajib diisi.',
            'bookingTime.date'               => 'Waktu booking harus berupa tanggal yang valid.',
            'image.required'                 => 'Foto wajib diunggah.',
            'image.image'                    => 'File yang diunggah harus berupa gambar.',
            'image.mimes'                    => 'Format gambar harus berupa: jpeg, png, jpg, gif, atau svg.',
            'image.max'                      => 'Ukuran gambar maksimal 2MB.',
            'socializationType.required'     => 'Tipe sosialisasi wajib diisi.',
            'emergencyContactName.required'  => 'Nama kontak darurat wajib diisi.',
            'emergencyPhoneNumber.required'  => 'Nomor telepon kontak darurat wajib diisi.',
            'inventoryProducts.required'     => 'Produk inventaris wajib diisi.',
            'furCondition.required'          => 'Kondisi bulu wajib diisi.',
            'skinSensitivity.required'       => 'Sensitivitas kulit wajib diisi.',
            'stambum.required'               => 'Stambum wajib diisi.',
            'healthClearance.required'       => 'Sertifikat kesehatan wajib diisi.',
            'consultationType.required'      => 'Tipe konsultasi wajib diisi.',
        ];

        $validate = Validator::make($request->all(), array_merge($baseRules, $extraRules), $messages);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $hashedName = "";
        $realName = "";

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $realName = $file->getClientOriginalName();
            $hashedName = $file->hashName();
            // Simpan ke public/uploads
            $file->move(public_path('BookingImages'), $hashedName);
        }

        $booking = bookings::create([
            'locationId'  => $request->locationId,
            'doctorId'  => $request->doctorId,
            'customerId'  => $request->customerId,
            'petId'       => $request->petId,
            'serviceType'    => $request->services,
            'status'    => 'waiting confirmation',
            'bookingTime' => $request->bookingTime,
            'realImageName' => $realName,
            'imagePath' => '/BookingImages/' . $hashedName,
            'userId' => $request->user()->id,
        ]);

        if ($request->services === 'Pet Hotel') {
            bookingsPetHotel::create([
                'bookingId'            => $booking->id,
                'socializationType'    => $request->socializationType,
                'emergencyContactName' => $request->emergencyContactName,
                'emergencyPhoneNumber' => $request->emergencyPhoneNumber,
                'inventoryProducts'    => $request->inventoryProducts,
                'additionalInfo'       => $request->additionalInfo,
                'userId'               => $request->user()->id,
            ]);
        } else if ($request->services === 'Pet Salon') {
            bookingsPetSalon::create([
                'bookingId'            => $booking->id,
                'furCondition'         => $request->furCondition,
                'skinSensitivity'      => $request->skinSensitivity,
                'emergencyContactName' => $request->emergencyContactName,
                'emergencyPhoneNumber' => $request->emergencyPhoneNumber,
                'additionalInfo'       => $request->additionalInfo,
                'userId'               => $request->user()->id,
            ]);
        } else if ($request->services === 'Breeding') {
            bookingsBreeding::create([
                'bookingId'            => $booking->id,
                'stambum'              => $request->stambum,
                'healthClearance'      => $request->healthClearance,
                'emergencyContactName' => $request->emergencyContactName,
                'emergencyPhoneNumber' => $request->emergencyPhoneNumber,
                'additionalInfo'       => $request->additionalInfo,
                'userId'               => $request->user()->id,
            ]);
        } else if ($request->services === 'Pet Clinic') {
            bookingsPetClinic::create([
                'bookingId'            => $booking->id,
                'consultationType'     => $request->consultationType,
                'drugAllergy'          => $request->drugAllergy,
                'additionalInfo'       => $request->additionalInfo,
                'userId'               => $request->user()->id,
            ]);
        }

        return responseCreate();
    }

    public function detail(Request $request)
    {
        $booking = DB::table('bookings as e')
            ->join('users as u', 'e.userId', '=', 'u.id')
            ->join('customer as c', 'e.customerId', '=', 'c.id')
            ->join('customerPets as p', 'e.petId', '=', 'p.id')
            ->join('location as l', 'e.locationId', '=', 'l.id')
            ->leftJoin('users as d', 'e.doctorId', '=', 'd.id')
            ->select([
                'e.id',
                'e.serviceType',
                'e.bookingTime',
                'e.isCancelled',
                'e.cancellationReason',
                'e.canceledByName',
                'e.cancellationDate',
                'c.id as customerId',
                'c.firstName as customerFirstName',
                'c.lastName as customerLastName',
                DB::raw("CONCAT(c.firstName, ' ', c.lastName) as customerFullName"),
                'p.id as petId',
                'p.petName',
                'l.id as locationId',
                'l.locationName',
                'd.id as doctorId',
                DB::raw("CONCAT(d.firstName, ' ', d.lastName) as doctorName"),
                'u.id as createdByUserId',
                DB::raw("CONCAT(u.firstName, ' ', u.lastName) as createdByUserName"),
                'e.realImageName',
                'e.imagePath',
            ])
            ->where('e.id', $request->id)
            ->where('e.isDeleted', 0)
            ->first();

        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $detail = match ($booking->serviceType) {
            'Pet Hotel'  => bookingsPetHotel::where('bookingId', $booking->id)->first(),
            'Pet Salon'  => bookingsPetSalon::where('bookingId', $booking->id)->first(),
            'Breeding'   => bookingsBreeding::where('bookingId', $booking->id)->first(),
            'Pet Clinic' => bookingsPetClinic::where('bookingId', $booking->id)->first(),
            default      => null,
        };

        return response()->json([
            'data' => [
                'booking' => $booking,
                'detail'  => $detail,
            ],
        ]);
    }

    public function update(Request $request)
    {
        $baseRules = [
            'id'                   => 'required|integer',
            'locationId'           => 'required|integer',
            'doctorId'             => 'required|integer',
            'customerId'           => 'required|integer',
            'petId'                => 'required|integer',
            'services'             => 'required|in:Pet Hotel,Pet Salon,Breeding,Pet Clinic',
            'bookingTime'          => 'required|date',
            'emergencyPhoneNumber' => 'required|string',
        ];

        // Use $request->json() consistently everywhere
        $data    = $request->json()->all();
        $service = $data['services'] ?? null;

        $extraRules = match ($service) {
            'Pet Hotel' => [
                'socializationType'    => 'required|string',
                'emergencyContactName' => 'required|string',
                'inventoryProducts'    => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Pet Salon' => [
                'furCondition'         => 'required|string',
                'skinSensitivity'      => 'required|string',
                'emergencyContactName' => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Breeding' => [
                'stambum'              => 'required|string',
                'healthClearance'      => 'required|string',
                'emergencyContactName' => 'required|string',
                'additionalInfo'       => 'nullable|string',
            ],
            'Pet Clinic' => [
                'consultationType' => 'required|string',
                'drugAllergy'      => 'nullable|string',
                'additionalInfo'   => 'nullable|string',
            ],
            default => [],
        };

        $messages = [
            'id.required'                    => 'ID booking wajib diisi.',
            'id.integer'                     => 'ID booking harus berupa angka.',
            'locationId.required'            => 'Lokasi wajib diisi.',
            'locationId.integer'             => 'Lokasi harus berupa angka.',
            'doctorId.required'              => 'Dokter wajib diisi.',
            'doctorId.integer'               => 'Dokter harus berupa angka.',
            'customerId.required'            => 'Pelanggan wajib diisi.',
            'customerId.integer'             => 'Pelanggan harus berupa angka.',
            'petId.required'                 => 'Hewan peliharaan wajib diisi.',
            'petId.integer'                  => 'Hewan peliharaan harus berupa angka.',
            'services.required'              => 'Jenis layanan wajib diisi.',
            'services.in'                    => 'Jenis layanan tidak valid. Pilih salah satu: Pet Hotel, Pet Salon, Breeding, Pet Clinic.',
            'bookingTime.required'           => 'Waktu booking wajib diisi.',
            'bookingTime.date'               => 'Waktu booking harus berupa tanggal yang valid.',
            'socializationType.required'     => 'Tipe sosialisasi wajib diisi.',
            'emergencyContactName.required'  => 'Nama kontak darurat wajib diisi.',
            'emergencyPhoneNumber.required'  => 'Nomor telepon kontak darurat wajib diisi.',
            'inventoryProducts.required'     => 'Produk inventaris wajib diisi.',
            'furCondition.required'          => 'Kondisi bulu wajib diisi.',
            'skinSensitivity.required'       => 'Sensitivitas kulit wajib diisi.',
            'stambum.required'               => 'Stambum wajib diisi.',
            'healthClearance.required'       => 'Sertifikat kesehatan wajib diisi.',
            'consultationType.required'      => 'Tipe konsultasi wajib diisi.',
        ];

        $validate = Validator::make($data, array_merge($baseRules, $extraRules), $messages);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $booking = bookings::find($data['id']);
        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $booking->update([
            'locationId'   => $data['locationId'],
            'doctorId'   => $data['doctorId'],
            'customerId'   => $data['customerId'],
            'petId'        => $data['petId'],
            'serviceType'  => $data['services'],
            'bookingTime'  => $data['bookingTime'],
            'userUpdateId' => $request->user()->id,
        ]);

        if ($service === 'Pet Hotel') {
            $bookingDetail = bookingsPetHotel::where('bookingId', $data['id'])->first();
            if ($bookingDetail) {
                $bookingDetail->update([
                    'socializationType'    => $data['socializationType'],
                    'emergencyContactName' => $data['emergencyContactName'],
                    'emergencyPhoneNumber' => $data['emergencyPhoneNumber'],
                    'inventoryProducts'    => $data['inventoryProducts'],
                    'additionalInfo'       => $data['additionalInfo'] ?? null,
                    'userUpdateId'         => $request->user()->id,
                ]);
            }
        } elseif ($service === 'Pet Salon') {
            $bookingDetail = bookingsPetSalon::where('bookingId', $data['id'])->first();
            if ($bookingDetail) {
                $bookingDetail->update([
                    'furCondition'         => $data['furCondition'],
                    'skinSensitivity'      => $data['skinSensitivity'],
                    'emergencyContactName' => $data['emergencyContactName'],
                    'emergencyPhoneNumber' => $data['emergencyPhoneNumber'],
                    'additionalInfo'       => $data['additionalInfo'] ?? null,
                    'userUpdateId'         => $request->user()->id,
                ]);
            }
        } elseif ($service === 'Breeding') {
            $bookingDetail = bookingsBreeding::where('bookingId', $data['id'])->first();
            if ($bookingDetail) {
                $bookingDetail->update([
                    'stambum'              => $data['stambum'],
                    'healthClearance'      => $data['healthClearance'],
                    'emergencyContactName' => $data['emergencyContactName'],
                    'emergencyPhoneNumber' => $data['emergencyPhoneNumber'],
                    'additionalInfo'       => $data['additionalInfo'] ?? null,
                    'userUpdateId'         => $request->user()->id,
                ]);
            }
        } elseif ($service === 'Pet Clinic') {
            $bookingDetail = bookingsPetClinic::where('bookingId', $data['id'])->first();
            if ($bookingDetail) {
                $bookingDetail->update([
                    'consultationType'     => $data['consultationType'],
                    'drugAllergy'          => $data['drugAllergy'] ?? null,
                    'additionalInfo'       => $data['additionalInfo'] ?? null,
                    'userUpdateId'         => $request->user()->id,
                ]);
            }
        }

        return responseUpdate();
    }

    public function acceptBooking(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ], [
            'id.required' => 'ID booking wajib diisi.',
            'id.integer'  => 'ID booking harus berupa angka.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $booking = bookings::where('id', $request->id)->where('isDeleted', false)->first();
        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        if ($booking->isCancelled) {
            return response()->json([
                'message' => 'Booking sudah dibatalkan, tidak dapat diproses.',
            ], 422);
        }

        if ($booking->isRejected) {
            return response()->json([
                'message' => 'Booking sudah ditolak, tidak dapat diproses.',
            ], 422);
        }

        $booking->update([
            'isAccepted'     => true,
            'status'         => 'accepted',
            'acceptedByName' => $request->user()->name,
            'acceptedDate'   => now(),
            'userUpdateId'   => $request->user()->id,
        ]);

        return responseUpdate();
    }

    public function rejectBooking(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'             => 'required|integer',
            'rejectionReason' => 'required|string',
        ], [
            'id.required'              => 'ID booking wajib diisi.',
            'id.integer'               => 'ID booking harus berupa angka.',
            'rejectionReason.required' => 'Alasan penolakan wajib diisi.',
            'rejectionReason.string'   => 'Alasan penolakan harus berupa teks.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $booking = bookings::where('id', $request->id)->where('isDeleted', false)->first();
        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        if ($booking->isCancelled) {
            return response()->json([
                'message' => 'Booking sudah dibatalkan, tidak dapat diproses.',
            ], 422);
        }

        if ($booking->isAccepted) {
            return response()->json([
                'message' => 'Booking sudah diterima, tidak dapat ditolak.',
            ], 422);
        }

        $booking->update([
            'isRejected'      => true,
            'status'          => 'rejected',
            'rejectionReason' => $request->rejectionReason,
            'rejectedByName'  => $request->user()->name,
            'rejectionDate'   => now(),
            'userUpdateId'    => $request->user()->id,
        ]);

        return responseUpdate();
    }

    public function cancelBooking(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'                 => 'required|integer',
            'cancellationReason' => 'required|string',
        ], [
            'id.required'                  => 'ID booking wajib diisi.',
            'id.integer'                   => 'ID booking harus berupa angka.',
            'cancellationReason.required'  => 'Alasan pembatalan wajib diisi.',
            'cancellationReason.string'    => 'Alasan pembatalan harus berupa teks.',
        ]);

        if ($validate->fails()) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors'  => $validate->errors()->all(),
            ], 422);
        }

        $booking = bookings::where('id', $request->id)->where('isDeleted', false)->first();
        if (!$booking) {
            return response()->json([
                'message' => 'Booking tidak ditemukan.',
            ], 404);
        }

        $booking->update([
            'isCancelled' => true,
            'cancellationReason' => $request->cancellationReason,
            'canceledByName' => $request->user()->name,
            'cancellationDate' => now(),
            'userUpdateId' => $request->user()->id,
        ]);

        return responseUpdate();
    }

    public function getList(Request $request)
    {
        $data = DB::table('bookings as e')
            ->join('customer as c', 'e.customerId', '=', 'c.id')
            ->join('customerPets as p', 'e.petId', '=', 'p.id')
            ->select([
                'e.id',
                DB::raw("CONCAT(e.serviceType, ' - ', COALESCE(CONCAT(c.firstName, ' ', c.lastName), ''), ' (', COALESCE(p.petName, ''), ') - ', DATE_FORMAT(e.bookingTime, '%d/%m/%Y %H:%i')) as label"),
            ])
            ->where('e.isDeleted', 0)
            ->where('e.isCancelled', 0)
            ->orderBy('e.bookingTime', 'desc');

        if ($request->filled('locationId')) {
            $data = $data->where('e.locationId', $request->locationId);
        }

        if ($request->filled('serviceType')) {
            $data = $data->where('e.serviceType', $request->serviceType);
        }

        return response()->json($data->get(), 200);
    }

    function delete(Request $request)
    {
        if (!$request->id) {
            return response()->json([
                'message' => 'Data yang diberikan tidak valid.',
                'errors' => ['Tidak ada data yang dipilih untuk dihapus.'],
            ], 422);
        }

        foreach ($request->id as $va) {
            $res = bookings::find($va);

            if (!$res) {
                return response()->json([
                    'message' => 'Data yang diberikan tidak valid.',
                    'errors' => ['Booking tidak ditemukan.'],
                ], 422);
            }
        }

        foreach ($request->id as $va) {
            $booking = bookings::find($va);
            $booking->isDeleted = true;
            $booking->deletedBy = $request->user()->name;
            $booking->deletedAt = now();
            $booking->save();
        }

        return responseDelete();
    }
}
