<?php

namespace App\Http\Services;

use App\Http\Requests\MerchantGatewayRequest;
use App\Repository\PaymentRepository;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class Gateway2Service
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
            env('MERCHANT_GATEWAY_2_ID'),
            env('MERCHANT_GATEWAY_2_LIMIT'),
        );

        if ($limitReached) {
            throw new Exception('Limit has been reached');
        }

        $validatedData = $request->validate([
            'project' => 'required|integer',
            'invoice' => 'required|integer',
            'status' => 'required|string|in:new,pending,completed,expired,rejected',
            'amount' => 'required|integer',
            'amount_paid' => 'required|integer',
            'rand' => 'required|string',
        ]);

        $authHeader = $request->bearerToken();

        if (!$authHeader) {
            throw new Exception('Authorization header is not exists');
        }

        $isSignatureValid = $this->validateSignature($validatedData, $authHeader);

        if (!$isSignatureValid) {
            throw new Exception('Invalid signature');
        }

        $validatedData['payment_id'] = $validatedData['invoice'];
        $validatedData['merchant_id'] = $validatedData['project'];

        $this->paymentRepository->create($validatedData);

        return response()->json(['success' => true]);
    }

    /**
     * Signature verification
     * @param $data
     * @param $authHeader
     * @return bool
     */
    private function validateSignature($data, $authHeader): bool
    {
        $sortedParams = $data;
        ksort($sortedParams);
        $signatureString = implode('.', $sortedParams) . env('MERCHANT_GATEWAY_2_KEY');
        $hash = md5($signatureString);
        return hash_equals($hash, $authHeader);
    }
}
