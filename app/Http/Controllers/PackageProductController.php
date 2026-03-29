<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PackageProductController extends Controller
{
    public function create()
    {
        $products = DB::table('products')->select('id', 'name')->get();
        $servicePackage = null;

        if (request()->filled('service_package_id')) {
            $servicePackage = DB::table('service_package')->find(request('service_package_id'));
        }

        return response()->json([
            'success' => true,
            'html' => view('package_products.partials.form', [
                'packageProduct' => null,
                'products' => $products,
                'servicePackage' => $servicePackage
            ])->render()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'package_id' => 'required|integer|exists:service_package,id',
        ]);

        DB::table('package_product')->insert([
            'product_id' => $request->product_id,
            'package_id' => $request->package_id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Product added to package successfully!'
        ]);
    }

    public function edit($id)
    {
        $packageProduct = DB::table('package_product')->find($id);

        if (!$packageProduct) {
            return response()->json([
                'success' => false,
                'msg' => 'Package product not found!'
            ]);
        }

        $products = DB::table('products')->select('id', 'name')->get();
        $servicePackage = DB::table('service_package')->find($packageProduct->package_id);

        return response()->json([
            'success' => true,
            'html' => view('package_products.partials.form', [
                'packageProduct' => $packageProduct,
                'products' => $products,
                'servicePackage' => $servicePackage
            ])->render()
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'package_id' => 'required|integer|exists:service_package,id',
        ]);

        $packageProduct = DB::table('package_product')->find($id);

        if (!$packageProduct) {
            return response()->json([
                'success' => false,
                'msg' => 'Package product not found!'
            ]);
        }

        DB::table('package_product')
            ->where('id', $id)
            ->update([
                'product_id' => $request->product_id,
                'package_id' => $request->package_id,
                'updated_at' => now(),
            ]);

        return response()->json([
            'success' => true,
            'msg' => 'Package product updated successfully!'
        ]);
    }

    public function destroy($id)
    {
        $packageProduct = DB::table('package_product')->find($id);

        if (!$packageProduct) {
            return response()->json([
                'success' => false,
                'msg' => 'Package product not found!'
            ]);
        }

        DB::table('package_product')->delete($id);

        return response()->json([
            'success' => true,
            'msg' => 'Product removed from package successfully!'
        ]);
    }

    public function datatable(Request $request)
    {
        $query = DB::table('package_product as pp')
            ->join('service_package as sp', 'pp.package_id', '=', 'sp.id')
            ->join('products as p', 'pp.product_id', '=', 'p.id')
            ->select(
                'pp.id',
                'pp.package_id',
                'pp.product_id',
                'sp.name as package_name',
                'p.name as product_name',
                'p.sku as product_sku',
                'pp.created_at'
            );

        if ($request->filled('service_package_id')) {
            $query->where('pp.package_id', $request->get('service_package_id'));
        }
        if ($request->filled('package_id')) {
            $query->where('pp.package_id', $request->get('package_id'));
        }
        if ($request->filled('q')) {
            $q = $request->get('q');
            $query->where(function ($sub) use ($q) {
                $sub->where('p.name', 'like', "%$q%")
                    ->orWhere('p.sku', 'like', "%$q%")
                    ->orWhere('sp.name', 'like', "%$q%");
            });
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
