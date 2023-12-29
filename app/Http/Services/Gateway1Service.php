<?php

namespace App\Http\Services;

use App\Http\Requests\MerchantGatewayRequest;
use App\Repository\PaymentRepository;
use Exception;
use Illuminate\Http\JsonResponse;

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
    public function callback(MerchantGatewayRequest $request): JsonResponse
    {
        $limitReached = $this->checkLimit();

        if ($limitReached) {
            throw new Exception('Limit has been reached');
        }

        $isValidSignature = $this->validateSignature($request->all());

        $paymentData = [
            'user_id' => 1,
            'merchant_id' => $request->input('merchant_id'),
            'payment_id' => $request->input('payment_id'),
            'status' => $request->input('status'),
            'amount' => $request->input('amount'),
        ];

        if (!$isValidSignature) {
            throw new Exception('Invalid signature');
        }

        $this->paymentRepository->create($paymentData);

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

    /**
     * Checks if payments limit reached
     * @return bool
     */
    private function checkLimit(): bool
    {
        $payments = $this->paymentRepository->getToday(env('MERCHANT_GATEWAY_1_ID'), 'merchant_id');

        if ($payments->count() >= env('MERCHANT_GATEWAY_1_LIMIT')) {
            return true;
        }

        return false;
    }
}
