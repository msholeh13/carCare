<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Models\CarStore;
use App\Models\CarService;
use App\Models\StoreService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreBookingRequest;
use Illuminate\Container\Attributes\Storage;
use App\Http\Requests\StoreBookingPaymentRequest;
use App\Models\BookingTransaction;
use Carbon\Carbon;

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

    public function booking(CarStore $carStore)
    {
        session()->put('carStoreId', $carStore->id);

        $serviceTypeId = session()->get('serviceTypeId');
        $carService = CarService::where('id', $serviceTypeId)->first();

        return view('front.booking', compact('carStore', 'carService'));
    }

    public function booking_store(StoreBookingRequest $request)
    {
        $customerName = $request->input('name');
        $customerPhoneNumber = $request->input('phone_number');
        $CustomerTimeAt = $request->input('time_at');

        session([
            'customerName' => $customerName,
            'customerPhoneNumber' => $customerPhoneNumber,
            'customerTimeAt' => $CustomerTimeAt,
        ]);

        $serviceTypeId = session()->get('serviceTypeId');
        $carStoreId = session()->get('carStoreId');

        return redirect()->route('front.booking.payment', [$carStoreId, $serviceTypeId]);
    }

    public function booking_payment(CarStore $carStore, CarService $carService)
    {
        $Ppn = 0.11;
        $bookingFee = 25000;
        $totalPpn = $Ppn * $carService->price;
        $grandTotal = $totalPpn + $bookingFee + $carService->price;

        session()->put('totalAmount', $grandTotal);
        return view('front.payment', compact('carStore', 'carService', 'bookingFee', 'totalPpn', 'grandTotal'));
    }

    public function booking_payment_store(StoreBookingPaymentRequest $request)
    {

        $customerName = session()->get('customerName');
        $customerPhoneNumber = session()->get('customerPhoneNumber');
        $CustomerTimeAt = session()->get('customerTimeAt');
        $customerTotalAmount = session()->get('totalAmount');

        $serviceTypeId = session()->get('serviceTypeId');
        $carStoreId = session()->get('carStoreId');

        $bookingTransactionId = null;

        // closure based database transaction
        DB::transaction(function () use ($request, $customerName, $customerPhoneNumber, $CustomerTimeAt, $customerTotalAmount, $serviceTypeId, $carStoreId, &$bookingTransactionId) {

            $validated = $request->validated();

            if ($request->hasFile('proof')) {
                $proofPath = $request->file('proof')->store('proofs', 'public');
                $validated['proof'] = $proofPath;
            }

            $validated['name'] = $customerName;
            $validated['phone_number'] = $customerPhoneNumber;
            $validated['is_paid'] = false;
            $validated['total_amount'] = $customerTotalAmount;
            $validated['car_store_id'] = $carStoreId;
            $validated['car_service_id'] = $serviceTypeId;
            $validated['started_at'] = Carbon::tomorrow()->format('Y-m-d');
            $validated['time_at'] = $CustomerTimeAt;
            $validated['trx_id'] = BookingTransaction::generateUniqueTrxId();

            $newBooking = BookingTransaction::create($validated);

            $bookingTransactionId = $newBooking->id;
        });

        return redirect()->route('front.success.booking', $bookingTransactionId);
    }

    public function success_booking(BookingTransaction $bookingTransaction)
    {
        return view('front.success_booking', compact('bookingTransaction'));
    }
}
