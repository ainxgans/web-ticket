<?php

namespace App\Services;

use App\Jobs\SendBookingConfirmedEmail;
use App\Models\BookingTransaction;
use App\Repositories\Contracts\BookingRepositoryInterface;
use App\Repositories\Contracts\TicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

class BookingService
{
    protected $ticketRepository;
    protected $bookingRepository;

    public function __construct(TicketRepositoryInterface $ticketRepository, BookingRepositoryInterface $bookingRepository)
    {
        $this->ticketRepository = $ticketRepository;
        $this->bookingRepository = $bookingRepository;
    }

    public function getBookingDetails(array $validated)
    {
        return $this->bookingRepository->findByTrxIdAndPhoneNumber($validated['booking_trx_id'], $validated['phone_number']);
    }

    public function calclulateTotals($ticketId, $totalParticipant)
    {
        $ppn = 0.11;
        $price = $this->ticketRepository->getPrice($ticketId);

        $subTotal = $price * $totalParticipant;
        $totalPpn = $subTotal * $ppn;
        $totalAmount = $subTotal + $totalPpn;

        return [
            'sub_total' => $subTotal,
            'total_ppn' => $totalPpn,
            'total_amount' => $totalAmount
        ];
    }

    public function storeBookingInSession($ticket, $validateData, $totals)
    {
        session()->put('booking', [
            'ticket_id' => $ticket->id,
            'name' => $validateData['name'],
            'email' => $validateData['email'],
            'phone_number' => $validateData['phone_number'],
            'started_at' => $validateData['started_at'],
            'total_participant' => $validateData['total_participant'],
            'sub_total' => $totals['sub_total'],
            'total_ppn' => $totals['total_ppn'],
            'total_amount' => $totals['total_amount'],
        ]);
    }

    public function payment()
    {
        $booking = session('booking');
        $ticket = $this->ticketRepository->find($booking['ticket_id']);
        return compact('booking', 'ticket');
    }

    public function paymentStore(array $validated)
    {
        $booking = session('booking');
        $bookingTransactionId = null;
        DB::transaction(function () use ($validated, &$bookingTransactionId, $booking) {

            if (isset($validated['proof'])) {
                $proofPath = $validated['proof']->store('proofs', 'public');
                $validated['proof'] = $proofPath;
            }
            $validated['name'] = $booking['name'];
            $validated['email'] = $booking['email'];
            $validated['phone_number'] = $booking['phone_number'];
            $validated['total_participant'] = $booking['total_participant'];
            $validated['started_at'] = $booking['started_at'];
            $validated['total_amount'] = $booking['total_amount'];
            $validated['ticket_id'] = $booking['ticket_id'];
            $validated['is_paid'] = false;

            // Create an instance of BookingTransaction to call the non-static method
            $bookingTransaction = new BookingTransaction();
            $validated['booking_trx_id'] = $bookingTransaction->generateUniqueTrxId();

            $newBooking = $this->bookingRepository->createBooking($validated);
            $bookingTransactionId = $newBooking->id;

            SendBookingConfirmedEmail::dispatch($newBooking);
        });
        return $bookingTransactionId;
    }

}