<?php

namespace App\Http\Controllers\Product;

use App\Models\DeliveryAgent;
use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderDetail;
use App\Models\DeliveryOrderLog;
use App\Models\ProductLocations;
use App\Models\ProductSellLocation;
use App\Models\ProductClinicLocation;
use App\Models\Products;
use App\Models\ProductSell;
use App\Models\ProductClinic;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator;

class DeliveryOrderController
{
    // ─────────────────────────────────────────
    // Generate Delivery Number
    // ─────────────────────────────────────────
    public function generateDeliveryNumber(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $locationPart = str_pad($request->locationId, 3, '0', STR_PAD_LEFT);
        $prefix = "RPC-DO-{$locationPart}-";

        $lastRecord = DeliveryOrder::where('locationId', $request->locationId)
            ->where('deliveryNumber', 'like', "{$prefix}%")
            ->orderBy('deliveryNumber', 'desc')
            ->first();

        if ($lastRecord) {
            $lastNumber = (int) substr($lastRecord->deliveryNumber, -5);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }

        return response()->json([
            'deliveryNumber' => "{$prefix}{$newNumber}",
        ], 200);
    }

    // ─────────────────────────────────────────
    // Index – list with filters
    // ─────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DeliveryOrder::with([
            'location:id,locationName',
            'agent:id,name,phone,vehicleType,vehiclePlate',
            'creator:id,name',
        ])->where('isDeleted', false);

        if ($request->filled('locationId')) {
            $query->where('locationId', $request->locationId);
        }

        if ($request->filled('agentId')) {
            $query->where('agentId', $request->agentId);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('deliveryNumber', 'like', "%{$search}%")
                    ->orWhere('customerName', 'like', "%{$search}%")
                    ->orWhere('customerPhone', 'like', "%{$search}%")
                    ->orWhere('deliveryAddress', 'like', "%{$search}%");
            });
        }

        if ($request->filled('startDate') && $request->filled('endDate')) {
            $query->whereBetween('deliveryDate', [$request->startDate, $request->endDate]);
        }

        $query->orderBy('created_at', 'desc');

        if ($request->filled('limit')) {
            $data = $query->paginate($request->limit);
        } else {
            $data = $query->get();
        }

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────
    // Detail
    // ─────────────────────────────────────────
    public function detail(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $data = DeliveryOrder::with([
            'location:id,locationName',
            'agent:id,name,phone,vehicleType,vehiclePlate',
            'creator:id,name',
            'details',
            'logs.user:id,name',
        ])
            ->where('id', $request->id)
            ->where('isDeleted', false)
            ->first();

        if (!$data) {
            return responseInvalid(['Delivery Order not found!']);
        }

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────
    // Create (status: draft)
    // ─────────────────────────────────────────
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'deliveryNumber'  => 'required|string|unique:deliveryOrders,deliveryNumber',
            'locationId'      => 'required|integer',
            'customerId'      => 'nullable|integer',
            'customerName'    => 'nullable|string|max:255',
            'customerPhone'   => 'nullable|string|max:20',
            'deliveryAddress' => 'required|string',
            'deliveryDate'    => 'required|date',
            'deliveryTime'    => 'nullable|date_format:H:i',
            'scheduledAt'     => 'nullable|date',
            'orderId'         => 'nullable|integer',
            'note'            => 'nullable|string',
            'details'         => 'required|array|min:1',
            'details.*.productType' => 'required|string|in:sell,clinic,product',
            'details.*.productId'   => 'required|integer',
            'details.*.qty'         => 'required|integer|min:1',
            'details.*.unitPrice'   => 'nullable|numeric|min:0',
            'details.*.weight'      => 'nullable|numeric|min:0',
            'details.*.note'        => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        DB::beginTransaction();
        try {
            $detailsData  = [];
            $totalItems   = 0;
            $totalWeight  = 0;
            $totalAmount  = 0;

            foreach ($request->details as $item) {
                $product = $this->resolveProduct($item['productType'], $item['productId']);

                if (!$product) {
                    DB::rollBack();
                    return responseInvalid(["Product ID {$item['productId']} (type: {$item['productType']}) not found!"]);
                }

                $unitPrice = $item['unitPrice'] ?? ($product->price ?? 0);
                $weight    = $item['weight'] ?? 0;
                $subtotal  = $item['qty'] * $unitPrice;

                $totalItems++;
                $totalWeight += $weight * $item['qty'];
                $totalAmount += $subtotal;

                $detailsData[] = [
                    'productType' => $item['productType'],
                    'productId'   => $item['productId'],
                    'productName' => $product->fullName,
                    'sku'         => $product->sku ?? null,
                    'qty'         => $item['qty'],
                    'unitPrice'   => $unitPrice,
                    'subtotal'    => $subtotal,
                    'weight'      => $weight,
                    'note'        => $item['note'] ?? null,
                ];
            }

            $order = DeliveryOrder::create([
                'deliveryNumber'  => $request->deliveryNumber,
                'locationId'      => $request->locationId,
                'agentId'         => null,
                'customerId'      => $request->customerId,
                'customerName'    => $request->customerName,
                'customerPhone'   => $request->customerPhone,
                'deliveryAddress' => $request->deliveryAddress,
                'deliveryDate'    => $request->deliveryDate,
                'deliveryTime'    => $request->deliveryTime,
                'scheduledAt'     => $request->scheduledAt,
                'orderId'         => $request->orderId,
                'status'          => 'draft',
                'totalItems'      => $totalItems,
                'totalWeight'     => $totalWeight,
                'totalAmount'     => $totalAmount,
                'note'            => $request->note,
                'isDeleted'       => false,
                'userId'          => $request->user()->id,
            ]);

            foreach ($detailsData as $detail) {
                $detail['deliveryOrderId'] = $order->id;
                DeliveryOrderDetail::create($detail);
            }

            DeliveryOrderLog::create([
                'deliveryOrderId' => $order->id,
                'action'          => 'created',
                'description'     => "Delivery order created with number {$order->deliveryNumber}",
                'userId'          => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Delivery Order created successfully.',
                'data'    => $order->load('details'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Update (hanya saat draft)
    // ─────────────────────────────────────────
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'              => 'required|integer',
            'customerId'      => 'nullable|integer',
            'customerName'    => 'nullable|string|max:255',
            'customerPhone'   => 'nullable|string|max:20',
            'deliveryAddress' => 'nullable|string',
            'deliveryDate'    => 'nullable|date',
            'deliveryTime'    => 'nullable|date_format:H:i',
            'scheduledAt'     => 'nullable|date',
            'note'            => 'nullable|string',
            'details'         => 'nullable|array|min:1',
            'details.*.productType' => 'required_with:details|string|in:sell,clinic,product',
            'details.*.productId'   => 'required_with:details|integer',
            'details.*.qty'         => 'required_with:details|integer|min:1',
            'details.*.unitPrice'   => 'nullable|numeric|min:0',
            'details.*.weight'      => 'nullable|numeric|min:0',
            'details.*.note'        => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'draft') {
            return responseInvalid(['Only draft Delivery Orders can be updated.']);
        }

        DB::beginTransaction();
        try {
            $order->update([
                'customerId'      => $request->customerId      ?? $order->customerId,
                'customerName'    => $request->customerName    ?? $order->customerName,
                'customerPhone'   => $request->customerPhone   ?? $order->customerPhone,
                'deliveryAddress' => $request->deliveryAddress ?? $order->deliveryAddress,
                'deliveryDate'    => $request->deliveryDate    ?? $order->deliveryDate,
                'deliveryTime'    => $request->deliveryTime    ?? $order->deliveryTime,
                'scheduledAt'     => $request->scheduledAt     ?? $order->scheduledAt,
                'note'            => $request->note            ?? $order->note,
                'userUpdateId'    => $request->user()->id,
            ]);

            if ($request->filled('details')) {
                DeliveryOrderDetail::where('deliveryOrderId', $order->id)->delete();

                $totalItems  = 0;
                $totalWeight = 0;
                $totalAmount = 0;

                foreach ($request->details as $item) {
                    $product = $this->resolveProduct($item['productType'], $item['productId']);

                    if (!$product) {
                        DB::rollBack();
                        return responseInvalid(["Product ID {$item['productId']} (type: {$item['productType']}) not found!"]);
                    }

                    $unitPrice = $item['unitPrice'] ?? ($product->price ?? 0);
                    $weight    = $item['weight'] ?? 0;
                    $subtotal  = $item['qty'] * $unitPrice;

                    $totalItems++;
                    $totalWeight += $weight * $item['qty'];
                    $totalAmount += $subtotal;

                    DeliveryOrderDetail::create([
                        'deliveryOrderId' => $order->id,
                        'productType'     => $item['productType'],
                        'productId'       => $item['productId'],
                        'productName'     => $product->fullName,
                        'sku'             => $product->sku ?? null,
                        'qty'             => $item['qty'],
                        'unitPrice'       => $unitPrice,
                        'subtotal'        => $subtotal,
                        'weight'          => $weight,
                        'note'            => $item['note'] ?? null,
                    ]);
                }

                $order->update([
                    'totalItems'  => $totalItems,
                    'totalWeight' => $totalWeight,
                    'totalAmount' => $totalAmount,
                ]);
            }

            DeliveryOrderLog::create([
                'deliveryOrderId' => $order->id,
                'action'          => 'updated',
                'description'     => 'Delivery order updated.',
                'userId'          => $request->user()->id,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Delivery Order updated successfully.',
                'data'    => $order->fresh()->load('details'),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────
    // Assign Agent (draft → assigned)
    // ─────────────────────────────────────────
    public function assign(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'      => 'required|integer',
            'agentId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'draft') {
            return responseInvalid(['Only draft Delivery Orders can be assigned.']);
        }

        $agent = DeliveryAgent::where('id', $request->agentId)
            ->where('isActive', true)
            ->where('isDeleted', false)
            ->first();

        if (!$agent) {
            return responseInvalid(['Delivery Agent not found or inactive!']);
        }

        $order->update([
            'agentId'      => $agent->id,
            'status'       => 'assigned',
            'userUpdateId' => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'assigned',
            'description'     => "Agent '{$agent->name}' assigned to this delivery order.",
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Agent assigned successfully.'], 200);
    }

    // ─────────────────────────────────────────
    // Pickup – agent ambil barang (assigned → picked_up)
    // ─────────────────────────────────────────
    public function pickup(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'assigned') {
            return responseInvalid(['Only assigned Delivery Orders can be picked up.']);
        }

        $order->update([
            'status'       => 'picked_up',
            'pickedUpAt'   => Carbon::now(),
            'userUpdateId' => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'picked_up',
            'description'     => 'Agent has picked up the goods.',
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order marked as picked up.'], 200);
    }

    // ─────────────────────────────────────────
    // Start Delivery (picked_up → on_delivery)
    // ─────────────────────────────────────────
    public function startDelivery(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'picked_up') {
            return responseInvalid(['Only picked up Delivery Orders can be started.']);
        }

        $order->update([
            'status'       => 'on_delivery',
            'userUpdateId' => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'on_delivery',
            'description'     => 'Delivery is now on the way.',
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order is now on delivery.'], 200);
    }

    // ─────────────────────────────────────────
    // Complete Delivery (on_delivery → delivered)
    // ─────────────────────────────────────────
    public function complete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'            => 'required|integer',
            'proofImageUrl' => 'nullable|string',
            'note'          => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'on_delivery') {
            return responseInvalid(['Only on_delivery Orders can be completed.']);
        }

        $order->update([
            'status'        => 'delivered',
            'deliveredAt'   => Carbon::now(),
            'proofImageUrl' => $request->proofImageUrl ?? $order->proofImageUrl,
            'note'          => $request->note          ?? $order->note,
            'userUpdateId'  => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'delivered',
            'description'     => 'Delivery completed successfully.',
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order marked as delivered.'], 200);
    }

    // ─────────────────────────────────────────
    // Mark as Failed (on_delivery → failed)
    // ─────────────────────────────────────────
    public function failed(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'           => 'required|integer',
            'failedReason' => 'required|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if ($order->status !== 'on_delivery') {
            return responseInvalid(['Only on_delivery Orders can be marked as failed.']);
        }

        $order->update([
            'status'       => 'failed',
            'failedReason' => $request->failedReason,
            'userUpdateId' => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'failed',
            'description'     => "Delivery failed. Reason: {$request->failedReason}",
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order marked as failed.'], 200);
    }

    // ─────────────────────────────────────────
    // Cancel (draft / assigned → cancelled)
    // ─────────────────────────────────────────
    public function cancel(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'               => 'required|integer',
            'cancelledReason'  => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if (!in_array($order->status, ['draft', 'assigned'])) {
            return responseInvalid(['Only draft or assigned Delivery Orders can be cancelled.']);
        }

        $order->update([
            'status'          => 'cancelled',
            'cancelledReason' => $request->cancelledReason,
            'userUpdateId'    => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'cancelled',
            'description'     => 'Delivery order cancelled.' . ($request->cancelledReason ? " Reason: {$request->cancelledReason}" : ''),
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order cancelled.'], 200);
    }

    // ─────────────────────────────────────────
    // Delete (soft delete, hanya draft/cancelled)
    // ─────────────────────────────────────────
    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $order = DeliveryOrder::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$order) {
            return responseInvalid(['Delivery Order not found!']);
        }

        if (!in_array($order->status, ['draft', 'cancelled'])) {
            return responseInvalid(['Only draft or cancelled Delivery Orders can be deleted.']);
        }

        $order->update([
            'isDeleted'    => true,
            'deletedBy'    => $request->user()->name ?? $request->user()->id,
            'deletedAt'    => Carbon::now(),
            'userUpdateId' => $request->user()->id,
        ]);

        DeliveryOrderLog::create([
            'deliveryOrderId' => $order->id,
            'action'          => 'cancelled',
            'description'     => 'Delivery order deleted.',
            'userId'          => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Order deleted successfully.'], 200);
    }

    // ─────────────────────────────────────────
    // Private Helper – resolve product by type
    // ─────────────────────────────────────────
    private function resolveProduct(string $type, int $productId)
    {
        switch ($type) {
            case 'sell':
                return ProductSell::find($productId);
            case 'clinic':
                return ProductClinic::find($productId);
            case 'product':
            default:
                return Products::find($productId);
        }
    }
}
