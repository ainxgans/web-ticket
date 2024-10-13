<?php

namespace App\Repositories;

use App\Models\BookingTransaction;
use App\Repositories\Contracts\BookingRepositoryInterface;

class BookingRepository implements BookingRepositoryInterface
{
    public function createBooking(array $data)
    {
        return BookingTransaction::create($data);
    }

    public function findByTrxIdAndPhoneNumber($bookingId, $phoneNumber)
    {
        return BookingTransaction::where('booking_id', $bookingId)
            ->where('phone_number', $phoneNumber)
            ->first();
    }
}
