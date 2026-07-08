<?php

namespace App\Filament\Candidate\Pages;

use App\Models\EducationCandidate;
use App\Services\Document;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.candidate.pages.documents';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Documents';

    protected static ?string $title = 'Documents';

    /** @return array<string, array{document_type: string, label: string, description: string, uploaded: bool, path: ?string}> */
    public function documentTypes(): array
    {
        $candidate = $this->candidate();
        $existing = $candidate->documents()->get()->keyBy(fn ($document) => $document->document_type->value);

        $definitions = [
            'cv' => [
                'label' => 'CV',
                'description' => 'Your most recent CV.',
            ],
            'photo' => [
                'label' => 'Photo',
                'description' => 'A clear, recent photo of yourself.',
            ],
            'prevent_training' => [
                'label' => 'Prevent Training',
                'description' => 'Certificate confirming completion of Prevent duty training.',
            ],
            'safeguarding_training' => [
                'label' => 'Safeguarding Training',
                'description' => 'Certificate confirming completion of safeguarding training.',
            ],
            'proof_of_address' => [
                'label' => 'Proof of Address',
                'description' => 'A recent utility bill or bank statement.',
            ],
        ];

        match ($candidate->right_to_work_type) {
            'birth_certificate' => $definitions['birth_certificate'] = [
                'label' => 'Birth Certificate',
                'description' => 'Your birth certificate, as proof of your right to work.',
            ],
            'passport' => $definitions['passport'] = [
                'label' => 'Passport',
                'description' => 'Your passport, as proof of your right to work.',
            ],
            default => null,
        };

        if ($candidate->has_dbs === 'yes') {
            $definitions['dbs'] = [
                'label' => 'DBS',
                'description' => 'Your DBS certificate or update service check.',
            ];
        } elseif ($candidate->has_dbs === 'no') {
            $definitions['proof_of_address_2'] = [
                'label' => 'Proof of Address 2',
                'description' => 'A second, different proof of address, since you do not currently have a DBS.',
            ];
        }

        if ($candidate->has_naric === 'yes') {
            $definitions['uk_naric'] = [
                'label' => 'UK NARIC',
                'description' => 'Your UK NARIC statement of comparability.',
            ];
        }

        return collect($definitions)
            ->map(function (array $definition, string $key) use ($existing): array {
                $document = $existing->get($key);

                return [
                    'document_type' => $key,
                    'label' => $definition['label'],
                    'description' => $definition['description'],
                    'uploaded' => $document !== null,
                    'path' => $document?->path,
                ];
            })
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->records(fn (): array => $this->documentTypes())
            ->recordClasses(fn (array $record): ?string => $record['uploaded'] ? null : 'candidate-document-row-missing')
            ->columns([
                TextColumn::make('label')
                    ->label('Document'),

                TextColumn::make('description')
                    ->label('Description')
                    ->color('gray')
                    ->wrap(),

                TextColumn::make('uploaded')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Uploaded' : 'Not uploaded')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->recordActions([
                Action::make('preventTrainingInfo')
                    ->label('')
                    ->icon('heroicon-o-information-circle')
                    ->color('gray')
                    ->tooltip('More information')
                    ->visible(fn (array $record): bool => $record['document_type'] === 'prevent_training')
                    ->modalHeading('Prevent Training')
                    ->modalContent(view('filament.candidate.pages.documents.prevent-training-info'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                $this->uploadAction('upload', 'Upload', 'heroicon-o-arrow-up-tray')
                    ->visible(fn (array $record): bool => ! $record['uploaded']),

                $this->uploadAction('update', 'Update', 'heroicon-o-arrow-path')
                    ->visible(fn (array $record): bool => $record['uploaded']),

                Action::make('remove')
                    ->label('Remove')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove document')
                    ->modalDescription('Are you sure you want to remove this document? You will need to upload it again.')
                    ->visible(fn (array $record): bool => $record['uploaded'])
                    ->action(fn (array $record) => $this->removeDocument($record['document_type'])),
            ]);
    }

    private function uploadAction(string $name, string $label, string $icon): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon($icon)
            ->color('primary')
            ->modalHeading(fn (array $record): string => "{$label} {$record['label']}")
            ->schema([
                FileUpload::make('file')
                    ->label('File')
                    ->storeFiles(false)
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                    ->maxSize(10240)
                    ->required(),
            ])
            ->action(fn (array $data, array $record) => $this->uploadDocument($record['document_type'], $data['file']));
    }

    private function uploadDocument(string $documentType, UploadedFile $file): void
    {
        $candidate = $this->candidate();

        $path = Document::upload($file, $candidate, $documentType);

        $existing = $candidate->documents()->where('document_type', $documentType)->first();

        if ($existing) {
            Storage::disk('local')->delete($existing->path);
            $existing->update(['path' => $path]);
        } else {
            $candidate->documents()->create([
                'document_type' => $documentType,
                'path' => $path,
            ]);
        }

        Notification::make()
            ->success()
            ->title('Document uploaded')
            ->send();
    }

    private function removeDocument(string $documentType): void
    {
        $candidate = $this->candidate();

        $document = $candidate->documents()->where('document_type', $documentType)->first();

        if ($document) {
            Storage::disk('local')->delete($document->path);
            $document->delete();
        }

        Notification::make()
            ->success()
            ->title('Document removed')
            ->send();
    }

    private function candidate(): EducationCandidate
    {
        /** @var EducationCandidate $candidate */
        $candidate = auth()->user()->candidate;

        return $candidate;
    }
}
