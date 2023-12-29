<?php

namespace App\Http\Controllers;

use App\Http\Requests\MerchantGatewayRequest;
use App\Http\Services\Gateway1Service;
use App\Http\Services\Gateway2Service;
use Illuminate\Http\Request;
use Mockery\Exception;

class PaymentController extends Controller
{
    const MERCHANT_1_ID = 6;
    const MERCHANT_2_ID = 816;
    protected Gateway1Service $gateway1Service;
    protected Gateway2Service $gateway2Service;

    public function __construct(Gateway1Service $gateway1Service, Gateway2Service $gateway2Service)
    {
        $this->gateway1Service = $gateway1Service;
        $this->gateway2Service = $gateway2Service;
    }

    public function callback(MerchantGatewayRequest $request)
    {
        try {
            $merchantId = $request->get('merchant_id');
            $service = $this->getGatewayService($merchantId);

            $service->callback($request);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    protected function getGatewayService($merchantId): Gateway1Service|Gateway2Service
    {
        return match ($merchantId) {
            self::MERCHANT_1_ID => $this->gateway1Service,
            self::MERCHANT_2_ID => $this->gateway2Service,
            default => throw new \InvalidArgumentException("Invalid merchant ID: $merchantId"),
        };

    }
}
