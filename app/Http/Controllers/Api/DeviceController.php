<?php

namespace App\Http\Controllers\Api;

use App\Events\DeviceEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeviceRequest;
use App\Models\DeviceModel;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json([
            'data' => DeviceModel::all(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(DeviceRequest $request)
    {

        $validate = $request->validated();
        $device = DeviceModel::create($validate);

        event(new DeviceEvent($device->toArray()));

        return response()->json([
            'message' => 'Device berhasil ditambahkan',
            'data' => $device,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $device = DeviceModel::findOrFail($id);

        return response()->json([
            'data' => $device,
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // TODO:clean code ini (jadii validate request)
        $device = DeviceModel::findOrFail($id);

        $validate = $request->validate([
            'device_names' => 'required|min:5',
            'mac_devices' => 'required',
            'rssi' => 'required|numeric',
            'tipe_device' => 'required',

        ]);

        $device->update($validate);
        event(new DeviceEvent($device->toArray()));

        return response()->json([
            'message' => 'Device berhasil diperbarui',
            'data' => $device,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DeviceModel::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Device berhasil dihapus',
        ], 200);
    }
}
