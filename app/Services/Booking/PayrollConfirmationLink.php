<?php

namespace App\Services\Booking;

use App\Models\Client;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;

class PayrollConfirmationLink
{
    public static function encode(Client $client, CarbonInterface $weekStart): string
    {
        return Crypt::encryptString($client->id.'|'.$weekStart->toDateString());
    }

    /** @return array{client: Client, weekStart: Carbon}|null */
    public static function decode(string $crypt): ?array
    {
        try {
            $decoded = Crypt::decryptString($crypt);
        } catch (DecryptException) {
            return null;
        }

        [$clientId, $weekStart] = array_pad(explode('|', $decoded, 2), 2, null);

        if (blank($clientId) || blank($weekStart)) {
            return null;
        }

        $client = Client::withTrashed()->find($clientId);

        if (! $client) {
            return null;
        }

        return ['client' => $client, 'weekStart' => Carbon::parse($weekStart)];
    }

    public static function url(Client $client, CarbonInterface $weekStart): string
    {
        return route('payroll-confirmation.show', ['crypt' => self::encode($client, $weekStart)]);
    }
}
