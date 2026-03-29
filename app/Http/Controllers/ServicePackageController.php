<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ServicePackageController extends Controller
{
    public function index(Request $request)
    {
        return view('service_packages.index');
    }

    public function create()
    {
        $devices = DB::table('categories')->where('category_type', 'device')->select('id', 'name')->get();
        $repairDeviceModels = DB::table('repair_device_models')->select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'html' => view('service_packages.partials.form', [
                'servicePackage' => null,
                'devices' => $devices,
                'repairDeviceModels' => $repairDeviceModels
            ])->render()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'km' => 'nullable|integer|min:0',
            'device_id' => 'required|integer',
            'repair_device_model_id' => 'nullable|integer',
            'from' => 'nullable|integer|min:1900|max:2100',
            'to' => 'nullable|integer|min:1900|max:2100',
        ]);

        DB::table('service_package')->insert([
            'name' => $request->name,
            'km' => $request->km,
            'device_id' => $request->device_id,
            'repair_device_model_id' => $request->repair_device_model_id,
            'from' => $request->from,
            'to' => $request->to,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Service package created successfully!'
        ]);
    }

    public function getRepairDeviceModels($deviceId)
    {
        $models = DB::table('repair_device_models')
            ->where('device_id', $deviceId)
            ->select('id', 'name')
            ->get();

        return response()->json($models);
    }

    public function edit($id)
    {
        $servicePackage = DB::table('service_package')->find($id);

        if (!$servicePackage) {
            return response()->json([
                'success' => false,
                'msg' => 'Service package not found!'
            ]);
        }

        $devices = DB::table('categories')->where('category_type', 'device')->select('id', 'name')->get();
        $repairDeviceModels = DB::table('repair_device_models')->select('id', 'name')->get();

        return response()->json([
            'success' => true,
            'html' => view('service_packages.partials.form', [
                'servicePackage' => $servicePackage,
                'devices' => $devices,
                'repairDeviceModels' => $repairDeviceModels
            ])->render()
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'km' => 'nullable|integer|min:0',
            'device_id' => 'required|integer',
            'repair_device_model_id' => 'nullable|integer',
            'from' => 'nullable|integer|min:1900|max:2100',
            'to' => 'nullable|integer|min:1900|max:2100',
        ]);

        $servicePackage = DB::table('service_package')->find($id);

        if (!$servicePackage) {
            return response()->json([
                'success' => false,
                'msg' => 'Service package not found!'
            ]);
        }

        DB::table('service_package')
            ->where('id', $id)
            ->update([
                'name' => $request->name,
                'km' => $request->km,
                'device_id' => $request->device_id,
                'repair_device_model_id' => $request->repair_device_model_id,
                'from' => $request->from,
                'to' => $request->to,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'msg' => 'Service package updated successfully!'
        ]);
    }

    public function destroy($id)
    {
        $servicePackage = DB::table('service_package')->find($id);

        if (!$servicePackage) {
            return response()->json([
                'success' => false,
                'msg' => 'Service package not found!'
            ]);
        }

        DB::table('service_package')->delete($id);

        return response()->json([
            'success' => true,
            'msg' => 'Service package deleted successfully!'
        ]);
    }

    public function datatable(Request $request)
    {
        $query = DB::table('service_package as sp')
            ->leftJoin('categories as c', 'sp.device_id', '=', 'c.id')
            ->leftJoin('repair_device_models as rdm', 'sp.repair_device_model_id', '=', 'rdm.id')
            ->select('sp.id', 'sp.name', 'sp.km', 'sp.device_id', 'sp.repair_device_model_id', 'sp.from', 'sp.to', 'sp.created_at',
                     'c.name as device_name', 'rdm.name as repair_device_model_name');

        // if ($request->filled('device_id')) {
        //     $query->where('sp.device_id', $request->get('device_id'));
        // }

        // if ($request->filled('from_year')) {
        //     $query->where('sp.from', '>=', (int)$request->get('from_year'));
        // }
        // if ($request->filled('to_year')) {
        //     $query->where('sp.to', '<=', (int)$request->get('to_year'));
        // }
        if ($request->filled('name')) {
            $name = $request->get('name');
            $query->where('sp.name', 'like', "%$name%");
        }

        return DataTables::of($query)
            ->addColumn('action', function ($row) {
                return [
                    'edit_id' => $row->id,
                    'delete_id' => $row->id,
                ];
            })
            ->toJson();
    }
}
