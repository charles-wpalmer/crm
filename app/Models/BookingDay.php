<?php

namespace App\Models;

use App\Enums\BookingDayPeriod;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingDay extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'period' => BookingDayPeriod::class,
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
