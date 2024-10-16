<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'booking_trx_id', 'phone_number', 'email', 'proof', 'total_amount', 'total_participant', 'is_paid', 'started_at', 'ticket_id',
    ];
    protected $casts = [
        'started_at' => 'date',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function generateUniqueTrxId()
    {
        $prefix = 'JRT';
        do {
            $trxId = $prefix . mt_rand(1000, 9999);
        } while (BookingTransaction::where('booking_trx_id', $trxId)->exists());
        return $trxId;
    }
}
