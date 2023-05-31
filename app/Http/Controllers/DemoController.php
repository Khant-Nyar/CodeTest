<?php

namespace App\Http\Controllers;

use Stevebauman\Location\Facades\Location;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Request;
use AmrShawky\Currency;



class DemoController extends Controller
{
    public function list(Request $request,)
    {

        [$apiKey, $route] = $this->getApiInfo();
        $getuser        = $this->getReqUser($request);
        $countryCode    = $getuser->countryCode;

        $urrencycode    = $this->unitConver($countryCode);


        // dd($urrencycode); //MMK

        // https://v6.exchangerate-api.com/v6/229009ada05e7c636483e4a8/pair/USD/MMK/1
        // $data = Http::get("{$route}/latest?access_key={$apiKey}&symbols={$data}"); //&base={$base}&symbols={$target}
        $data = "{$route}/latest/{$urrencycode}";
        $data = Http::get($data);
        $currencydata = [];
        if (false !== $data) {
            $data = json_decode($data);
            $currencydata = $data->rates;
        }


        $userInfo = [
            'userIp'        => $getuser->ip,
            'countryName'   => $getuser->countryName,
            'regionName'    => $getuser->regionName,
            'currencyRate'  => $currencydata,
        ];

        if (!count($userInfo)) {
            return $this->sendError(204, 'No Data Found');
        }
        return $this->sendResponse('get user data', $userInfo);
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
        $orginaltvalue = $amount * 1;
        $targetvalue = $amount * $fetchApi['conversion_rate'];
        // return $targetvalue;
        $percentage  = (($targetvalue - $orginaltvalue) / $orginaltvalue) * 100;

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
        $ip = '203.23.128.176';
        $data = Location::get($ip);
        return $data;
    }

    // & from = GBP
    // & to = JPY
    // & amount = 25
    // symbols
    private function unitConver($country)
    {
        $jsonData = json_decode(Storage::get('public/currencybycountry.json'), true);
        return $jsonData[$country];
    }
}
