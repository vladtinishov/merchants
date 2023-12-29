<?php

namespace App\Http\Services;

use App\Http\Requests\MerchantGatewayRequest;
use App\Repository\PaymentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Gateway1Service
{
    protected PaymentRepository $paymentRepository;

    public function __construct(PaymentRepository $paymentRepository)
    {
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * Gateway callback
     * @param MerchantGatewayRequest $request
     * @return JsonResponse
     * @throws Exception
     */
    public function callback(Request $request): JsonResponse
    {
        $limitReached = $this->paymentRepository->checkLimits(
            env('MERCHANT_GATEWAY_1_ID'),
            env('MERCHANT_GATEWAY_1_LIMIT'),
        );

        if ($limitReached) {
            throw new Exception('Limit has been reached');
        }

        $validatedData = $request->validate([
            'merchant_id' => 'required|integer',
            'payment_id' => 'required|integer',
            'status' => 'required|string|in:new,pending,completed,expired,rejected',
            'amount' => 'required|integer',
            'amount_paid' => 'required|integer',
            'timestamp' => 'required|integer',
            'sign' => 'required|string',
        ]);

        $isValidSignature = $this->validateSignature($validatedData);

        if (!$isValidSignature) {
            throw new Exception('Invalid signature');
        }

        $this->paymentRepository->create($validatedData);

        return response()->json(['success' => true]);
    }

    /**
     * Signature verification
     * @param $data
     * @return bool
     */
    private function validateSignature($data): bool
    {
        $sortedParams = $data;
        unset($sortedParams['sign']);
        ksort($sortedParams);
        $signatureData = implode(':', $sortedParams) . env('MERCHANT_GATEWAY_1_KEY');
        $calculatedSignature = hash('sha256', $signatureData);
        return $calculatedSignature === $data['sign'];
    }
}
