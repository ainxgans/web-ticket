@component('mail::message')
    # Hi {{ $booking->name }},

    Terima kasih telah memesan tiket wisata di Juaratiket. Kami sedang memeriksa pembayaran Anda saat ini.

    Anda dapat memeriksa secara berkala pada website kami. Berikut adalah booking transaction ID Anda:

    **{{ $booking->booking_trx_id }}**

    @component('mail::button', ['url' => route('front.check_booking')])
        Check Booking
    @endcomponent

    Terima kasih,<br>
    {{ config('app.name') }}
@endcomponent