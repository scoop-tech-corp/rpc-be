<?php

namespace App\Http\Controllers\Product;

use App\Models\DeliveryAgent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Validator;

class DeliveryAgentController
{
    // ─────────────────────────────────────────
    // Index – list with filters
    // ─────────────────────────────────────────
    public function index(Request $request)
    {
        $query = DeliveryAgent::with(['location:id,locationName'])
            ->where('isDeleted', false);

        if ($request->filled('locationId')) {
            $query->where('locationId', $request->locationId);
        }

        if ($request->filled('isActive')) {
            $query->where('isActive', filter_var($request->isActive, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('vehiclePlate', 'like', "%{$search}%");
            });
        }

        $query->orderBy('name', 'asc');

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

        $data = DeliveryAgent::with([
            'location:id,locationName',
            'deliveryOrders' => function ($q) {
                $q->where('isDeleted', false)->orderBy('created_at', 'desc')->limit(10);
            },
        ])
            ->where('id', $request->id)
            ->where('isDeleted', false)
            ->first();

        if (!$data) {
            return responseInvalid(['Delivery Agent not found!']);
        }

        return response()->json($data, 200);
    }

    // ─────────────────────────────────────────
    // Create
    // ─────────────────────────────────────────
    public function create(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId'     => 'required|integer',
            'name'           => 'required|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'identityNumber' => 'nullable|string|max:50',
            'vehicleType'    => 'nullable|string|in:motor,mobil,sepeda,lainnya',
            'vehiclePlate'   => 'nullable|string|max:20',
            'isActive'       => 'nullable|boolean',
            'note'           => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $agent = DeliveryAgent::create([
            'locationId'     => $request->locationId,
            'name'           => $request->name,
            'phone'          => $request->phone,
            'identityNumber' => $request->identityNumber,
            'vehicleType'    => $request->vehicleType,
            'vehiclePlate'   => $request->vehiclePlate,
            'isActive'       => $request->has('isActive') ? $request->isActive : true,
            'note'           => $request->note,
            'isDeleted'      => false,
            'userId'         => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Delivery Agent created successfully.',
            'data'    => $agent,
        ], 201);
    }

    // ─────────────────────────────────────────
    // Update
    // ─────────────────────────────────────────
    public function update(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'             => 'required|integer',
            'name'           => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:20',
            'identityNumber' => 'nullable|string|max:50',
            'vehicleType'    => 'nullable|string|in:motor,mobil,sepeda,lainnya',
            'vehiclePlate'   => 'nullable|string|max:20',
            'note'           => 'nullable|string',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $agent = DeliveryAgent::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$agent) {
            return responseInvalid(['Delivery Agent not found!']);
        }

        $agent->update([
            'name'           => $request->name          ?? $agent->name,
            'phone'          => $request->phone         ?? $agent->phone,
            'identityNumber' => $request->identityNumber ?? $agent->identityNumber,
            'vehicleType'    => $request->vehicleType   ?? $agent->vehicleType,
            'vehiclePlate'   => $request->vehiclePlate  ?? $agent->vehiclePlate,
            'note'           => $request->note          ?? $agent->note,
            'userUpdateId'   => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Delivery Agent updated successfully.',
            'data'    => $agent->fresh(),
        ], 200);
    }

    // ─────────────────────────────────────────
    // Change Status (aktif / non-aktif)
    // ─────────────────────────────────────────
    public function changeStatus(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id'       => 'required|integer',
            'isActive' => 'required|boolean',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $agent = DeliveryAgent::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$agent) {
            return responseInvalid(['Delivery Agent not found!']);
        }

        $agent->update([
            'isActive'     => $request->isActive,
            'userUpdateId' => $request->user()->id,
        ]);

        $status = $request->isActive ? 'activated' : 'deactivated';

        return response()->json([
            'message' => "Delivery Agent {$status} successfully.",
        ], 200);
    }

    // ─────────────────────────────────────────
    // Delete (soft delete)
    // ─────────────────────────────────────────
    public function delete(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'id' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $agent = DeliveryAgent::where('id', $request->id)->where('isDeleted', false)->first();

        if (!$agent) {
            return responseInvalid(['Delivery Agent not found!']);
        }

        $activeOrders = \App\Models\DeliveryOrder::where('agentId', $agent->id)
            ->whereIn('status', ['assigned', 'picked_up', 'on_delivery'])
            ->where('isDeleted', false)
            ->count();

        if ($activeOrders > 0) {
            return responseInvalid(['Cannot delete agent with active delivery orders.']);
        }

        $agent->update([
            'isDeleted'    => true,
            'deletedBy'    => $request->user()->name ?? $request->user()->id,
            'deletedAt'    => Carbon::now(),
            'userUpdateId' => $request->user()->id,
        ]);

        return response()->json(['message' => 'Delivery Agent deleted successfully.'], 200);
    }

    // ─────────────────────────────────────────
    // Dropdown – aktif agents per location
    // ─────────────────────────────────────────
    public function dropdown(Request $request)
    {
        $validate = Validator::make($request->all(), [
            'locationId' => 'required|integer',
        ]);

        if ($validate->fails()) {
            return responseInvalid($validate->errors()->all());
        }

        $data = DeliveryAgent::select('id', 'name', 'phone', 'vehicleType', 'vehiclePlate')
            ->where('locationId', $request->locationId)
            ->where('isActive', true)
            ->where('isDeleted', false)
            ->orderBy('name', 'asc')
            ->get();

        return response()->json($data, 200);
    }
}
