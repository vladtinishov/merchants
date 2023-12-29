<?php

namespace App\Repository;

use App\Models\Payment;

class PaymentRepository
{
    public function create(array $data)
    {
        return Payment::query()->create($data);
    }

    public function getOne($search, $field = 'id')
    {
        return Payment::query()->find($search, $field);
    }

    public function get($search, $field = 'id')
    {
        return Payment::query()->where($field, $search);
    }

    public function getToday($search, $field = 'id')
    {
        return $this->get($search, $field)->whereDate('created_at', today());
    }

    /**
     * Checks if payments limit reached
     * @return bool
     */
    public function checkLimits($merchantId, $limit)
    {
        $payments = $this->getToday($merchantId, 'merchant_id');

        if ($payments->count() >= $limit) {
            return true;
        }

        return false;
    }
}
