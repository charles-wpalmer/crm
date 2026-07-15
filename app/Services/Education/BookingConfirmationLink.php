<?php

namespace App\Services\Education;

use App\Models\Booking;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

class BookingConfirmationLink
{
    public static function encode(Booking $booking): string
    {
        return Crypt::encryptString((string) $booking->id);
    }

    public static function decode(string $crypt): ?Booking
    {
        try {
            $id = Crypt::decryptString($crypt);
        } catch (DecryptException) {
            return null;
        }

        return Booking::withTrashed()->find($id);
    }

    public static function url(Booking $booking): string
    {
        return route('booking-confirmation.show', ['crypt' => self::encode($booking)]);
    }
}
