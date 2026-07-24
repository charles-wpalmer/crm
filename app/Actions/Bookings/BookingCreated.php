<?php

namespace App\Actions\Bookings;

use App\Actions\Clients\EnsureClientCandidatePool;
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

        $this->addCandidateToClientPool($booking);
    }

    private function addCandidateToClientPool(Booking $booking): void
    {
        $client = $booking->client;

        if (! $client || ! $booking->candidate_type || ! $booking->candidate_id) {
            return;
        }

        $pool = EnsureClientCandidatePool::run($client);

        $pool->candidatesOfType($booking->candidate_type)->syncWithoutDetaching([$booking->candidate_id]);
    }
}
