<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\QuotationLog;
use App\Models\TransactionPetClinic;
use App\Exports\Finance\QuotationReport;
use App\Mail\QuotationMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuotationController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────
    // LIST
    // ─────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $itemPerPage = $request->rowPerPage ?? 10;
        $page        = $request->goToPage ?? 1;

        $data = DB::table('quotations as q')
            ->join('customer as c', 'q.customerId', 'c.id')
            ->join('location as l', 'q.locationId', 'l.id')
            ->join('users as u', 'q.userId', 'u.id')
            ->leftJoin('customerPets as cp', 'q.petId', 'cp.id')
            ->select(
                'q.id',
                'q.quotationNo',
                'q.status',
                'q.typeOfService',
                'q.validUntil',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'cp.petName',
                'l.locationName',
                DB::raw("TRIM(q.finalAmount)+0 as finalAmount"),
                'u.firstName as createdBy',
                DB::raw("DATE_FORMAT(q.created_at, '%d/%m/%Y %H:%i') as createdAt")
            )
            ->where('q.isDeleted', 0);

        // Filter lokasi
        if ($request->locationId) {
            $data = $data->where('q.locationId', $request->locationId);
        }

        // Filter status
        if ($request->status) {
            $data = $data->where('q.status', $request->status);
        }

        // Filter tanggal
        if ($request->dateFrom && $request->dateTo) {
            $data = $data->whereBetween(DB::raw("DATE(q.created_at)"), [$request->dateFrom, $request->dateTo]);
        }

        // Filter typeOfService
        if ($request->typeOfService) {
            $data = $data->where('q.typeOfService', $request->typeOfService);
        }

        // Search
        if ($request->search) {
            $keyword = $request->search;
            $data = $data->where(function ($q) use ($keyword) {
                $q->where('quotations.quotationNo', 'like', "%$keyword%")
                  ->orWhere(DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, ''))"), 'like', "%$keyword%")
                  ->orWhere('cp.petName', 'like', "%$keyword%");
            });
        }

        // Ordering
        $allowedColumns = [
            'quotationNo'  => 'q.quotationNo',
            'customerName' => DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, ''))"),
            'locationName' => 'l.locationName',
            'finalAmount'  => 'q.finalAmount',
            'validUntil'   => 'q.validUntil',
            'createdAt'    => 'q.created_at',
            'created_at'   => 'q.created_at',
        ];

        $orderCol = $allowedColumns[$request->orderColumn] ?? 'q.created_at';
        $orderDir = in_array(strtolower($request->orderValue ?? ''), ['asc', 'desc']) ? $request->orderValue : 'desc';
        $data = $data->orderBy($orderCol, $orderDir);

        $totalPagination = $data->count();
        $data = $data->skip(($page - 1) * $itemPerPage)->take($itemPerPage)->get();

        return response()->json([
            'totalPagination' => $totalPagination,
            'data'            => $data,
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DETAIL
    // ─────────────────────────────────────────────────────────────────────
    public function detail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = DB::table('quotations as q')
            ->join('customer as c', 'q.customerId', 'c.id')
            ->join('location as l', 'q.locationId', 'l.id')
            ->join('users as u', 'q.userId', 'u.id')
            ->leftJoin('customerPets as cp', 'q.petId', 'cp.id')
            ->select(
                'q.*',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'cp.petName',
                'l.locationName',
                'u.firstName as createdByName'
            )
            ->where('q.id', $request->id)
            ->where('q.isDeleted', 0)
            ->first();

        if (!$quotation) {
            return response()->json(['message' => 'Quotation not found.'], 404);
        }

        $items = DB::table('quotationItems')->where('quotationId', $request->id)->get();
        $logs  = DB::table('quotationLogs as ql')
            ->join('users as u', 'ql.changedBy', 'u.id')
            ->select('ql.*', 'u.firstName as changedByName')
            ->where('ql.quotationId', $request->id)
            ->orderBy('ql.created_at', 'asc')
            ->get();

        return response()->json([
            'quotation' => $quotation,
            'items'     => $items,
            'logs'      => $logs,
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // CREATE
    // ─────────────────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customerId'    => 'required|integer',
            'locationId'    => 'required|integer',
            'typeOfService' => 'required|string',
            'validUntil'    => 'required|date',
            'items'         => 'required|array|min:1',
            'items.*.itemType'  => 'required|string|in:service,product',
            'items.*.itemName'  => 'required|string',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.unitPrice' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        DB::beginTransaction();
        try {
            $userId    = auth()->id();
            $items     = $request->items;
            $subtotal  = collect($items)->sum(fn($i) => $i['quantity'] * $i['unitPrice']);
            $discount  = floatval($request->discountAmount ?? 0);
            $final     = $subtotal - $discount;

            $quotation = Quotation::create([
                'quotationNo'    => $this->generateQuotationNo(),
                'status'         => 'draft',
                'customerId'     => $request->customerId,
                'petId'          => $request->petId ?? null,
                'locationId'     => $request->locationId,
                'typeOfService'  => $request->typeOfService,
                'validUntil'     => $request->validUntil,
                'notes'          => $request->notes ?? '',
                'subtotalAmount' => $subtotal,
                'discountAmount' => $discount,
                'finalAmount'    => $final,
                'userId'         => $userId,
            ]);

            foreach ($items as $item) {
                QuotationItem::create([
                    'quotationId' => $quotation->id,
                    'itemType'    => $item['itemType'],
                    'serviceId'   => $item['serviceId'] ?? null,
                    'productId'   => $item['productId'] ?? null,
                    'itemName'    => $item['itemName'],
                    'quantity'    => $item['quantity'],
                    'unitPrice'   => $item['unitPrice'],
                    'totalPrice'  => $item['quantity'] * $item['unitPrice'],
                    'notes'       => $item['notes'] ?? null,
                ]);
            }

            QuotationLog::create([
                'quotationId' => $quotation->id,
                'fromStatus'  => null,
                'toStatus'    => 'draft',
                'remarks'     => 'Quotation created',
                'changedBy'   => $userId,
            ]);

            DB::commit();
            return response()->json(['id' => $quotation->id, 'quotationNo' => $quotation->quotationNo, 'message' => 'Quotation created successfully.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────────────────────────────
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'required|integer',
            'customerId'    => 'required|integer',
            'locationId'    => 'required|integer',
            'typeOfService' => 'required|string',
            'validUntil'    => 'required|date',
            'items'         => 'required|array|min:1',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = Quotation::where('id', $request->id)->where('isDeleted', 0)->first();
        if (!$quotation) return responseInvalid(['Quotation not found.']);
        if ($quotation->status !== 'draft') return responseInvalid(['Only draft quotations can be edited.']);

        DB::beginTransaction();
        try {
            $userId   = auth()->id();
            $items    = $request->items;
            $subtotal = collect($items)->sum(fn($i) => $i['quantity'] * $i['unitPrice']);
            $discount = floatval($request->discountAmount ?? 0);
            $final    = $subtotal - $discount;

            $quotation->update([
                'customerId'     => $request->customerId,
                'petId'          => $request->petId ?? null,
                'locationId'     => $request->locationId,
                'typeOfService'  => $request->typeOfService,
                'validUntil'     => $request->validUntil,
                'notes'          => $request->notes ?? '',
                'subtotalAmount' => $subtotal,
                'discountAmount' => $discount,
                'finalAmount'    => $final,
                'userUpdateId'   => $userId,
            ]);

            // Replace all items
            QuotationItem::where('quotationId', $quotation->id)->delete();
            foreach ($items as $item) {
                QuotationItem::create([
                    'quotationId' => $quotation->id,
                    'itemType'    => $item['itemType'],
                    'serviceId'   => $item['serviceId'] ?? null,
                    'productId'   => $item['productId'] ?? null,
                    'itemName'    => $item['itemName'],
                    'quantity'    => $item['quantity'],
                    'unitPrice'   => $item['unitPrice'],
                    'totalPrice'  => $item['quantity'] * $item['unitPrice'],
                    'notes'       => $item['notes'] ?? null,
                ]);
            }

            QuotationLog::create([
                'quotationId' => $quotation->id,
                'fromStatus'  => 'draft',
                'toStatus'    => 'draft',
                'remarks'     => 'Quotation updated',
                'changedBy'   => $userId,
            ]);

            DB::commit();
            return responseUpdate();
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // UPDATE STATUS (send | accept | reject)
    // ─────────────────────────────────────────────────────────────────────
    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'     => 'required|integer',
            'status' => 'required|string|in:sent,accepted,rejected',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = Quotation::where('id', $request->id)->where('isDeleted', 0)->first();
        if (!$quotation) return responseInvalid(['Quotation not found.']);

        $allowedTransitions = [
            'draft'    => ['sent'],
            'sent'     => ['accepted', 'rejected'],
            'accepted' => [],
            'rejected' => [],
            'expired'  => [],
            'converted'=> [],
        ];

        if (!in_array($request->status, $allowedTransitions[$quotation->status] ?? [])) {
            return responseInvalid(["Cannot change status from '{$quotation->status}' to '{$request->status}'."]);
        }

        DB::beginTransaction();
        try {
            $fromStatus = $quotation->status;
            $quotation->update([
                'status'       => $request->status,
                'userUpdateId' => auth()->id(),
            ]);

            QuotationLog::create([
                'quotationId' => $quotation->id,
                'fromStatus'  => $fromStatus,
                'toStatus'    => $request->status,
                'remarks'     => $request->remarks ?? null,
                'changedBy'   => auth()->id(),
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }

        // ── Kirim email PDF ke customer saat status berubah ke 'sent' ──────
        $emailsSent = 0;
        $emailFailed = false;

        if ($request->status === 'sent') {
            // Ambil email customer
            $customerEmails = DB::table('customerEmails')
                ->where('customerId', $quotation->customerId)
                ->where('isDeleted', 0)
                ->pluck('email');

            if ($customerEmails->count() > 0) {
                // Build data quotation lengkap untuk PDF & email
                $quotationData = DB::table('quotations as q')
                    ->join('customer as c', 'q.customerId', 'c.id')
                    ->join('location as l', 'q.locationId', 'l.id')
                    ->leftJoin('customerPets as cp', 'q.petId', 'cp.id')
                    ->leftJoin('customerTelephones as ct', function ($join) {
                        $join->on('ct.customerId', '=', 'c.id')
                             ->where('ct.usage', 'Utama');
                    })
                    ->select(
                        'q.*',
                        DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                        'c.memberNo',
                        'cp.petName',
                        'l.locationName',
                        DB::raw("COALESCE(ct.phoneNumber, '-') as customerPhone"),
                    )
                    ->where('q.id', $quotation->id)
                    ->first();

                $items = DB::table('quotationItems')->where('quotationId', $quotation->id)->get();

                // Fetch semua lokasi untuk header PDF
                $locations = DB::table('location')
                    ->leftJoin('location_telephone', 'location.codeLocation', '=', 'location_telephone.codeLocation')
                    ->where(function ($q) {
                        $q->where('location_telephone.usage', 'Utama')
                          ->orWhereNull('location_telephone.usage');
                    })
                    ->select('location.locationName', 'location.description', 'location_telephone.phoneNumber', 'location.codeLocation')
                    ->distinct()
                    ->get();

                $locationGroups = [];
                foreach ($locations as $loc) {
                    $key = $loc->codeLocation;
                    if (!isset($locationGroups[$key])) {
                        $locationGroups[$key] = [
                            'name'        => $loc->locationName,
                            'description' => $loc->description,
                            'phone'       => $loc->phoneNumber ?? '',
                        ];
                    }
                }

                $serviceLabel = match($quotation->typeOfService) {
                    'clinic'   => 'Pet Clinic',
                    'hotel'    => 'Pet Hotel',
                    'salon'    => 'Salon',
                    'grooming' => 'Grooming',
                    'shop'     => 'Pet Shop',
                    default    => ucfirst($quotation->typeOfService),
                };

                // Generate PDF bytes
                $pdfContent = Pdf::loadView('invoice.quotation', [
                    'locations'    => array_values($locationGroups),
                    'quotation'    => $quotationData,
                    'items'        => $items,
                    'serviceLabel' => $serviceLabel,
                    'nota_date'    => Carbon::parse($quotationData->created_at)->format('d/m/Y'),
                    'valid_until'  => Carbon::parse($quotationData->validUntil)->format('d/m/Y'),
                ])->output();

                // Kirim email ke setiap email customer
                try {
                    foreach ($customerEmails as $email) {
                        Mail::to(trim($email))->send(new QuotationMail($quotationData, $pdfContent));
                        $emailsSent++;
                    }
                } catch (\Throwable $e) {
                    $emailFailed = true;
                }
            }
        }

        // Response dengan info email
        if ($request->status === 'sent') {
            if ($emailsSent > 0) {
                return response()->json([
                    'result'      => 'Updated',
                    'message'     => "Status berhasil diubah. Email quotation berhasil dikirim ke {$emailsSent} alamat email customer.",
                    'emailsSent'  => $emailsSent,
                ], 200);
            } elseif ($emailFailed) {
                return response()->json([
                    'result'      => 'Updated',
                    'message'     => 'Status berhasil diubah, tetapi pengiriman email gagal. Silakan kirim PDF secara manual.',
                    'emailsSent'  => 0,
                ], 200);
            } else {
                return response()->json([
                    'result'      => 'Updated',
                    'message'     => 'Status berhasil diubah. Customer belum memiliki email terdaftar, silakan kirim PDF secara manual.',
                    'emailsSent'  => 0,
                ], 200);
            }
        }

        return responseUpdate();
    }

    // ─────────────────────────────────────────────────────────────────────
    // DUPLICATE
    // ─────────────────────────────────────────────────────────────────────
    public function duplicate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $source = Quotation::where('id', $request->id)->where('isDeleted', 0)->first();
        if (!$source) return responseInvalid(['Quotation not found.']);

        DB::beginTransaction();
        try {
            $userId = auth()->id();

            $newQuotation = Quotation::create([
                'quotationNo'    => $this->generateQuotationNo(),
                'status'         => 'draft',
                'customerId'     => $source->customerId,
                'petId'          => $source->petId,
                'locationId'     => $source->locationId,
                'typeOfService'  => $source->typeOfService,
                'validUntil'     => now()->addDays(7)->format('Y-m-d'),
                'notes'          => $source->notes,
                'subtotalAmount' => $source->subtotalAmount,
                'discountAmount' => $source->discountAmount,
                'finalAmount'    => $source->finalAmount,
                'userId'         => $userId,
            ]);

            $sourceItems = QuotationItem::where('quotationId', $source->id)->get();
            foreach ($sourceItems as $item) {
                QuotationItem::create([
                    'quotationId' => $newQuotation->id,
                    'itemType'    => $item->itemType,
                    'serviceId'   => $item->serviceId,
                    'productId'   => $item->productId,
                    'itemName'    => $item->itemName,
                    'quantity'    => $item->quantity,
                    'unitPrice'   => $item->unitPrice,
                    'totalPrice'  => $item->totalPrice,
                    'notes'       => $item->notes,
                ]);
            }

            QuotationLog::create([
                'quotationId' => $newQuotation->id,
                'fromStatus'  => null,
                'toStatus'    => 'draft',
                'remarks'     => "Duplicated from {$source->quotationNo}",
                'changedBy'   => $userId,
            ]);

            DB::commit();
            return response()->json(['id' => $newQuotation->id, 'quotationNo' => $newQuotation->quotationNo, 'message' => 'Quotation duplicated successfully.'], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // CONVERT TO TRANSACTION
    // ─────────────────────────────────────────────────────────────────────
    public function convertToTransaction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = Quotation::where('id', $request->id)->where('isDeleted', 0)->first();
        if (!$quotation) return responseInvalid(['Quotation not found.']);
        if ($quotation->status !== 'accepted') return responseInvalid(['Only accepted quotations can be converted.']);

        DB::beginTransaction();
        try {
            $userId = auth()->id();

            if ($quotation->typeOfService === 'clinic') {
                // Auto-generate registrationNo
                $lastNo = DB::table('transactionPetClinics')
                    ->where('locationId', $quotation->locationId)
                    ->orderBy('id', 'desc')
                    ->value('registrationNo');
                $nextNo = $lastNo ? ((int) substr($lastNo, -4)) + 1 : 1;
                $regNo  = 'REG-' . now()->format('Ymd') . '-' . str_pad($nextNo, 4, '0', STR_PAD_LEFT);

                $transId = DB::table('transactionPetClinics')->insertGetId([
                    'registrationNo' => $regNo,
                    'status'         => 'queue',
                    'isNewCustomer'  => 0,
                    'isNewPet'       => 0,
                    'typeOfCare'     => 1, // outpatient
                    'locationId'     => $quotation->locationId,
                    'customerId'     => $quotation->customerId,
                    'petId'          => $quotation->petId ?? 0,
                    'registrant'     => '',
                    'startDate'      => now()->format('Y-m-d'),
                    'endDate'        => null,
                    'doctorId'       => 0,
                    'note'           => $quotation->notes ?? '',
                    'isDeleted'      => 0,
                    'userId'         => $userId,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ]);

                $quotation->update([
                    'status'                   => 'converted',
                    'convertedTransactionId'   => $transId,
                    'convertedTransactionType' => 'pet_clinic',
                    'userUpdateId'             => $userId,
                ]);

                QuotationLog::create([
                    'quotationId' => $quotation->id,
                    'fromStatus'  => 'accepted',
                    'toStatus'    => 'converted',
                    'remarks'     => "Converted to Pet Clinic transaction #$transId",
                    'changedBy'   => $userId,
                ]);

                DB::commit();
                return response()->json([
                    'message'         => 'Converted to Pet Clinic transaction.',
                    'transactionId'   => $transId,
                    'transactionType' => 'pet_clinic',
                ], 200);

            } elseif ($quotation->typeOfService === 'hotel') {
                // Convert ke pet hotel — hanya buat header, staff akan isi detail (cage, checkin, dll)
                $transId = DB::table('transactionPetHotels')->insertGetId([
                    'status'        => 'queue',
                    'locationId'    => $quotation->locationId,
                    'customerId'    => $quotation->customerId,
                    'petId'         => $quotation->petId ?? 0,
                    'note'          => $quotation->notes ?? '',
                    'isDeleted'     => 0,
                    'isTreatment'   => 0,
                    'userId'        => $userId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                $quotation->update([
                    'status'                   => 'converted',
                    'convertedTransactionId'   => $transId,
                    'convertedTransactionType' => 'pet_hotel',
                    'userUpdateId'             => $userId,
                ]);

                QuotationLog::create([
                    'quotationId' => $quotation->id,
                    'fromStatus'  => 'accepted',
                    'toStatus'    => 'converted',
                    'remarks'     => "Converted to Pet Hotel transaction #$transId",
                    'changedBy'   => $userId,
                ]);

                DB::commit();
                return response()->json([
                    'message'         => 'Converted to Pet Hotel transaction.',
                    'transactionId'   => $transId,
                    'transactionType' => 'pet_hotel',
                ], 200);
            }

            // Untuk typeOfService lain (salon, shop, grooming) — returned tanpa create transaksi
            $quotation->update([
                'status'                   => 'converted',
                'convertedTransactionType' => $quotation->typeOfService,
                'userUpdateId'             => $userId,
            ]);

            QuotationLog::create([
                'quotationId' => $quotation->id,
                'fromStatus'  => 'accepted',
                'toStatus'    => 'converted',
                'remarks'     => "Marked as converted ({$quotation->typeOfService})",
                'changedBy'   => $userId,
            ]);

            DB::commit();
            return response()->json(['message' => 'Quotation marked as converted.'], 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return responseInvalid([$th->getMessage() . ' at line ' . $th->getLine()]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRINT QUOTATION (PDF)
    // ─────────────────────────────────────────────────────────────────────
    public function printQuotation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = DB::table('quotations as q')
            ->join('customer as c', 'q.customerId', 'c.id')
            ->join('location as l', 'q.locationId', 'l.id')
            ->leftJoin('customerPets as cp', 'q.petId', 'cp.id')
            ->leftJoin('customerTelephones as ct', function ($join) {
                $join->on('ct.customerId', '=', 'c.id')
                     ->where('ct.usage', 'Utama');
            })
            ->select(
                'q.*',
                DB::raw("CONCAT(c.firstName, ' ', COALESCE(c.lastName, '')) as customerName"),
                'c.memberNo',
                'cp.petName',
                'l.locationName',
                DB::raw("COALESCE(ct.phoneNumber, '-') as customerPhone"),
            )
            ->where('q.id', $request->id)
            ->where('q.isDeleted', 0)
            ->first();

        if (!$quotation) {
            return response()->json(['message' => 'Quotation not found.'], 404);
        }

        // Ambil semua lokasi (sama seperti template pet clinic)
        $locations = DB::table('location')
            ->leftJoin('location_telephone', 'location.codeLocation', '=', 'location_telephone.codeLocation')
            ->where(function ($query) {
                $query->where('location_telephone.usage', 'Utama')
                      ->orWhereNull('location_telephone.usage');
            })
            ->select(
                'location.locationName',
                'location.description',
                'location_telephone.phoneNumber',
                'location.codeLocation'
            )
            ->distinct()
            ->get();

        $locationGroups = [];
        foreach ($locations as $loc) {
            $key = $loc->codeLocation;
            if (!isset($locationGroups[$key])) {
                $locationGroups[$key] = [
                    'name'        => $loc->locationName,
                    'description' => $loc->description,
                    'phone'       => $loc->phoneNumber ?? '',
                ];
            }
        }
        $formattedLocations = array_values($locationGroups);

        $items = DB::table('quotationItems')->where('quotationId', $request->id)->get();

        $serviceLabel = match($quotation->typeOfService) {
            'clinic'   => 'Pet Clinic',
            'hotel'    => 'Pet Hotel',
            'salon'    => 'Salon',
            'grooming' => 'Grooming',
            'shop'     => 'Pet Shop',
            default    => ucfirst($quotation->typeOfService),
        };

        $pdf = Pdf::loadView('invoice.quotation', [
            'locations'    => $formattedLocations,
            'quotation'    => $quotation,
            'items'        => $items,
            'serviceLabel' => $serviceLabel,
            'nota_date'    => Carbon::parse($quotation->created_at)->format('d/m/Y'),
            'valid_until'  => Carbon::parse($quotation->validUntil)->format('d/m/Y'),
        ]);

        return $pdf->download('quotation-' . $quotation->quotationNo . '.pdf');
    }

    // ─────────────────────────────────────────────────────────────────────
    // EXPORT EXCEL
    // ─────────────────────────────────────────────────────────────────────
    public function exportExcel(Request $request)
    {
        $fileName = 'quotation-list-' . date('Ymd-His') . '.xlsx';

        return Excel::download(
            new QuotationReport(
                $request->orderValue,
                $request->orderColumn,
                $request->locationId,
                $request->status,
                $request->typeOfService,
                $request->dateFrom,
                $request->dateTo,
                $request->search,
            ),
            $fileName
        );
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE (soft delete, hanya draft)
    // ─────────────────────────────────────────────────────────────────────
    public function delete(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $quotation = Quotation::where('id', $request->id)->where('isDeleted', 0)->first();
        if (!$quotation) return responseInvalid(['Quotation not found.']);
        if ($quotation->status !== 'draft') return responseInvalid(['Only draft quotations can be deleted.']);

        $quotation->update([
            'isDeleted' => 1,
            'deletedBy' => auth()->user()->firstName ?? 'system',
            'deletedAt' => now(),
        ]);

        return responseDelete();
    }

    // ─────────────────────────────────────────────────────────────────────
    // DROPDOWN — customer list
    // ─────────────────────────────────────────────────────────────────────
    public function customerDropdown(Request $request)
    {
        $keyword    = $request->search ?? '';
        $locationId = $request->locationId ?? null;

        $data = DB::table('customer')
            ->select(
                'id',
                'memberNo',
                DB::raw("CONCAT(firstName, ' ', COALESCE(lastName, '')) as customerName"),
                DB::raw("CONCAT(firstName, ' ', COALESCE(lastName, ''), IF(memberNo IS NOT NULL AND memberNo != '', CONCAT(' (', memberNo, ')'), '')) as label")
            )
            ->where('isDeleted', 0)
            ->when($locationId, fn($q) => $q->where('locationId', $locationId))
            ->when($keyword, fn($q) => $q->where(function ($q2) use ($keyword) {
                $q2->where(DB::raw("CONCAT(firstName, ' ', COALESCE(lastName, ''))"), 'like', "%$keyword%")
                   ->orWhere('memberNo', 'like', "%$keyword%");
            }))
            ->orderBy('firstName')
            ->limit(50)
            ->get();

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DROPDOWN — pet list by customer
    // ─────────────────────────────────────────────────────────────────────
    public function petDropdown(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customerId' => 'required|integer',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $data = DB::table('customerPets')
            ->select('id', 'petName as label')
            ->where('customerId', $request->customerId)
            ->where('isDeleted', 0)
            ->get();

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DISCOUNT OPTIONS — aktif hari ini, type=2 (Discount), per lokasi
    // ─────────────────────────────────────────────────────────────────────
    public function discountOptions(Request $request)
    {
        $validator = Validator::make($request->all(), ['locationId' => 'required|integer']);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        $now = now();

        $promos = DB::table('promotionMasters as pm')
            ->join('promotionLocations as pl', 'pm.id', 'pl.promoMasterId')
            ->where('pm.type', 2)           // type 2 = Discount
            ->where('pm.status', 1)
            ->where('pm.isDeleted', 0)
            ->where('pm.startDate', '<=', $now)
            ->where('pm.endDate', '>=', $now)
            ->where('pl.locationId', $request->locationId)
            ->select('pm.id', 'pm.name', DB::raw("DATE_FORMAT(pm.endDate, '%d/%m/%Y') as endDate"))
            ->groupBy('pm.id', 'pm.name', 'pm.endDate')
            ->orderBy('pm.name')
            ->get();

        $result = $promos->map(function ($promo) {
            // Ambil semua discount detail: services + products
            $svcDetails = DB::table('promotion_discount_services as pds')
                ->join('services as s', 's.id', 'pds.serviceId')
                ->where('pds.promoMasterId', $promo->id)
                ->where('pds.isDeleted', 0)
                ->select(
                    's.id as itemId',
                    's.fullName as itemName',
                    DB::raw("'service' as itemType"),
                    'pds.discountType',
                    'pds.percent',
                    'pds.amount'
                )
                ->get();

            $prodDetails = DB::table('promotion_discount_products as pdp')
                ->join('products as p', 'p.id', 'pdp.productId')
                ->where('pdp.promoMasterId', $promo->id)
                ->where('pdp.isDeleted', 0)
                ->select(
                    'p.id as itemId',
                    'p.fullName as itemName',
                    DB::raw("'product' as itemType"),
                    'pdp.discountType',
                    'pdp.percent',
                    'pdp.amount'
                )
                ->get();

            $details = $svcDetails->merge($prodDetails)->values();

            // Bangun note deskripsi singkat
            $note = $details->map(function ($d) {
                $disc = $d->discountType === 'percent'
                    ? "diskon {$d->percent}%"
                    : 'diskon Rp ' . number_format($d->amount, 0, ',', '.');
                return "{$d->itemName} {$disc}";
            })->implode(' | ');

            return [
                'id'      => $promo->id,
                'name'    => $promo->name,
                'endDate' => $promo->endDate,
                'note'    => $note ?: 'Diskon berlaku untuk semua item',
                'details' => $details,  // dipakai frontend untuk kalkulasi
            ];
        });

        return response()->json($result, 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // CALCULATE DISCOUNT — hitung total diskon berdasarkan promo + items
    // ─────────────────────────────────────────────────────────────────────
    public function calculateDiscount(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'promoId' => 'required|integer',
            'items'   => 'required|array',
        ]);
        if ($validator->fails()) return responseInvalid($validator->errors()->all());

        // Ambil semua discount detail untuk promo ini
        $svcDiscounts = DB::table('promotion_discount_services as pds')
            ->where('pds.promoMasterId', $request->promoId)
            ->where('pds.isDeleted', 0)
            ->select('pds.serviceId as itemId', 'pds.discountType', 'pds.percent', 'pds.amount')
            ->get()
            ->keyBy('itemId');

        $prodDiscounts = DB::table('promotion_discount_products as pdp')
            ->where('pdp.promoMasterId', $request->promoId)
            ->where('pdp.isDeleted', 0)
            ->select('pdp.productId as itemId', 'pdp.discountType', 'pdp.percent', 'pdp.amount')
            ->get()
            ->keyBy('itemId');

        $totalDiscount = 0;

        foreach ($request->items as $item) {
            $itemType  = $item['itemType'];  // 'service' | 'product'
            $itemId    = $item['serviceId'] ?? $item['productId'] ?? null;
            $totalPrice = floatval($item['totalPrice'] ?? 0);

            if (!$itemId) continue;

            $disc = $itemType === 'service'
                ? ($svcDiscounts[$itemId] ?? null)
                : ($prodDiscounts[$itemId] ?? null);

            if (!$disc) continue;

            if ($disc->discountType === 'percent') {
                $totalDiscount += $totalPrice * ($disc->percent / 100);
            } else {
                // amount per item × qty
                $qty = intval($item['quantity'] ?? 1);
                $totalDiscount += min(floatval($disc->amount) * $qty, $totalPrice);
            }
        }

        return response()->json([
            'discountAmount' => round($totalDiscount, 2),
        ], 200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ─────────────────────────────────────────────────────────────────────
    private function generateQuotationNo(): string
    {
        $prefix  = 'QUO-' . now()->format('Ymd') . '-';
        $lastNo  = DB::table('quotations')
            ->where('quotationNo', 'like', "$prefix%")
            ->orderBy('id', 'desc')
            ->value('quotationNo');

        $seq = $lastNo ? ((int) substr($lastNo, -4)) + 1 : 1;
        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
}
