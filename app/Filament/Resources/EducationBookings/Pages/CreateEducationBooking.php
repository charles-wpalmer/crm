<?php

namespace App\Filament\Resources\EducationBookings\Pages;

use App\Actions\Bookings\BookingCreated;
use App\Filament\Resources\EducationBookings\EducationBookingResource;
use App\Filament\Resources\EducationBookings\Schemas\EducationBookingForm;
use App\Models\EducationCandidate;
use Filament\Resources\Pages\CreateRecord;

class CreateEducationBooking extends CreateRecord
{
    protected static string $resource = EducationBookingResource::class;

    /** @param  array<string, mixed>  $data */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['consultant_id'] = EducationCandidate::find($data['education_candidate_id'] ?? null)?->consultant_id;

        return $data;
    }

    protected function afterCreate(): void
    {
        EducationBookingForm::syncDayPeriods($this->record, $this->form->getRawState()['day_periods'] ?? []);

        BookingCreated::run($this->record);
    }
}
