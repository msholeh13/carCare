<?php

namespace App\Http\Controllers;

use App\Models\CarService;
use App\Models\CarStore;
use App\Models\City;
use App\Models\StoreService;
use Illuminate\Container\Attributes\Storage;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index()
    {
        $cities = City::all();
        $services = CarService::withCount('storeServices')->get();

        return view('front.index', compact('cities', 'services'));
    }

    public function search(Request $request)
    {
        $cityId = $request->input('city_id');
        $serviceTypeId = $request->input('service_type');

        // $carService = CarService::findOrFail($cityId);
        $carService = CarService::where('id', $serviceTypeId)->first();
        if (!$carService) {
            return redirect()->back()->with('error', 'Service type not found');
        }

        $stores = CarStore::whereHas('storeServices', function ($query) use ($carService) {
            $query->where('car_service_id', $carService->id);
        })->where('city_id', $cityId)
            ->get();

        $city = City::findOrFail($cityId);

        session([
            'serviceTypeId' => $request->input('service_type'),
            'search_url' => $request->fullUrl(),
        ]);

        return view('front.search', [
            'stores'        => $stores,
            'carService'    => $carService,
            'cityName'      => $city ? $city->name : 'Unkown City',
        ]);
    }

    public function details(CarStore $carStore)
    {
        $serviceTypeId = session()->get('serviceTypeId');
        $carService = CarService::where('id', $serviceTypeId)->first();


        return view('front.details', compact('carStore', 'carService'));
    }
}
