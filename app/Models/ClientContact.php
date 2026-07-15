<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Database\Factories\ClientContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientContact extends Model
{
    /** @use HasFactory<ClientContactFactory> */
    use BelongsToCompany;

    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'main_contact' => 'boolean',
            'timesheet_contact' => 'boolean',
            'invoice_contact' => 'boolean',
            'booking_contact' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ClientContact $contact): void {
            if ($contact->main_contact) {
                static::where('client_id', $contact->client_id)
                    ->when($contact->exists, fn ($query) => $query->whereKeyNot($contact->getKey()))
                    ->update(['main_contact' => false]);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function jobTitle(): BelongsTo
    {
        return $this->belongsTo(JobTitle::class);
    }
}
