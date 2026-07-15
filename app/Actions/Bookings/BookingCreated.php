<?php

namespace App\Actions\Bookings;

use App\Jobs\GenerateBookingConfirmationPdf;
use App\Jobs\SendBookingConfirmationEmail;
use App\Jobs\SendClientBookingConfirmationEmail;
use App\Models\Booking;
use Lorisleiva\Actions\Concerns\AsAction;

class BookingCreated
{
    use AsAction;

    public function handle(Booking $booking): void
    {
        GenerateBookingConfirmationPdf::dispatch($booking);

        SendBookingConfirmationEmail::dispatch($booking);
        SendClientBookingConfirmationEmail::dispatch($booking);
    }
}
