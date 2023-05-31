<?php

namespace App\Http\Controllers;

use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use AmrShawky\Currency;



// https://v6.exchangerate-api.com/v6/229009ada05e7c636483e4a8/pair/USD/MMK/1
class DemoController extends Controller
{
    public function list(Request $request,)
    {

        [$apiKey, $route] = $this->getApiInfo();
        $getuser        = $this->getReqUser($request);
        $countryCode    = $getuser->countryCode;

        $jsonData = json_decode(Storage::get('public/currencybycountry.json'), true);
        $urrencycode = $jsonData[$countryCode];
        // dd($urrencycode);//MMK

        $data = '';
        foreach ($jsonData as $key) {
            $data .= $key . ',';
        }

        // $data = Http::get("{$route}/latest?access_key={$apiKey}&symbols={$data}"); //&base={$base}&symbols={$target}
        $data = "https://open.er-api.com/v6/latest/{$urrencycode}";
        $data = Http::get($data);
        if (false !== $data) {
            $data = json_decode($data);
        }
        return $data;


        $userInfo = [
            'userIp'        => $getuser->ip,
            'countryName'   => $getuser->countryName,
            'regionName'    => $getuser->regionName,
            'currencyRate'  => $data,
        ];

        if (!count($userInfo)) {
            return $this->sendError(204, 'No Data Found');
        }
        return $this->sendResponse($userInfo);
    }

    public function calc(Request $request)
    {
        // request parameter
        $amount  = $request->amount;
        $orginal = $request->original;
        $target  = $request->target;

        $fetchApi = Http::get("https://v6.exchangerate-api.com/v6/229009ada05e7c636483e4a8/pair/{$orginal}/{$target}/{$amount}");

        // return $fetchApi;
        $rate = $fetchApi['conversion_result'];
        $orginaltvalue = $amount;
        $targetvalue = $amount * $fetchApi['conversion_rate'];
        // return $targetvalue;
        $percentage  = (($targetvalue - $orginaltvalue) / $rate) * 100;

        // return $percentage;
        $data = [
            'source'        => [
                'currencycode'  => $request->original,
                'orginal_pirce' => $orginaltvalue
            ],
            'target'        => [
                'currencycode'  =>  $request->target,
                'target_price'  =>  $targetvalue
            ],
            'percentage'    => $percentage,
        ];
        return $data;
    }

    private function getApiInfo()
    {
        $key    = config('app.accessKey');
        $route  = config('app.accessRoute');

        return [$key, $route];
    }

    private function getReqUser($req)
    {
        // $ip = $req->ip();
        $ip = '37.111.0.129';
        $data = Location::get($ip);
        return $data;
    }

    // & from = GBP
    // & to = JPY
    // & amount = 25
    // symbols
    private function unitConver($request = '', $base = 'EUR', $target = 'USD', $amout = 1)
    {
        $convert = Currency::convert()->from($base)->to($target)->amount($amout)->get();
        return $convert;
    }
}
