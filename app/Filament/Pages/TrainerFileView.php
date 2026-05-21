<?php

namespace App\Filament\Pages;

use App\Models\TrainerFile;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;

class TrainerFileView extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.trainer-file-view';
    protected static bool $shouldRegisterNavigation = false; // Hide from default navigation
    protected static ?string $title = '';
    public string $trainingType = '';
    public string $fileTitle = '';
    public string $activeTab = '';

    // Available file categories
    public array $fileTabs = [
        'TRAINING_DECK' => ['label' => 'Training Deck', 'icon' => 'presentation'],
        'TRAINING_SOP' => ['label' => 'Training SOP', 'icon' => 'document'],
        'TRAINING_GUIDELINE' => ['label' => 'Training Guideline', 'icon' => 'book'],
        'TRAINING_RECORDING' => ['label' => 'Training Recording', 'icon' => 'video'],
    ];

    public function mount(): void
    {
        // Get parameters from URL
        $this->trainingType = request()->query('type', '');
        $this->fileTitle = request()->query('title', '');
        $this->activeTab = $this->fileTitle ?: 'TRAINING_DECK';
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->fileTitle = $tab;
        $this->resetTable();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFilteredQuery())
            ->columns([
                TextColumn::make('module_type')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(function ($state) {
                        return match($state) {
                            'Attendance' => 'DAY 1 - ATTENDANCE',
                            'Leave_Claim' => 'DAY 2 - LEAVE & CLAIM',
                            'Payroll' => 'DAY 3 - PAYROLL',
                            default => $state ?? '—'
                        };
                    }),

                TextColumn::make('version')
                    ->label('Version')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('file_name')
                    ->label('File Name')
                    ->limit(50)
                    ->tooltip(fn (TrainerFile $record) => $record->file_name),

                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->actions([
                Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (TrainerFile $record) => $record->file_path, shouldOpenInNewTab: true)
                    ->visible(fn (TrainerFile $record) => $record->is_link),
                Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->visible(fn (TrainerFile $record) => !$record->is_link)
                    ->url(fn (TrainerFile $record) => '/' . $record->file_path, shouldOpenInNewTab: true),
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (TrainerFile $record) => !$record->is_link)
                    ->action(function (TrainerFile $record) {
                        if (Storage::disk('public')->exists($record->file_path)) {
                            $extension = pathinfo($record->file_name, PATHINFO_EXTENSION);
                            $downloadName = $record->title . '_' . ($record->module_type ?? 'file') . '.' . $extension;

                            return response()->download(
                                storage_path('app/public/' . $record->file_path),
                                $downloadName
                            );
                        }

                        Notification::make()
                            ->title('File not found')
                            ->danger()
                            ->send();
                    }),
            ])
            ->headerActions([
                Action::make('upload')
                    ->label('Upload New File')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Select::make('title')
                            ->label('Title')
                            ->options([
                                'TRAINING SOP' => 'Training SOP',
                                'TRAINING GUIDELINE' => 'Training Guideline',
                                'TRAINING DECK' => 'Training Deck',
                                'TRAINING RECORDING' => 'Training Recording',
                            ])
                            ->required()
                            ->default(fn () => str_replace('_', ' ', $this->activeTab))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'TRAINING RECORDING') {
                                    $set('is_link', true);
                                } else {
                                    $set('is_link', false);
                                }
                            }),

                        Grid::make(2)
                            ->schema([
                                Select::make('version')
                                    ->label('Version')
                                    ->options([
                                        'V1' => 'V1',
                                        'V2' => 'V2',
                                    ]),

                                Select::make('module_type')
                                    ->label('Module')
                                    ->options([
                                        'Attendance' => 'DAY 1 - ATTENDANCE MODULE',
                                        'Leave_Claim' => 'DAY 2 - LEAVE & CLAIM MODULE',
                                        'Payroll' => 'DAY 3 - PAYROLL MODULE',
                                    ])
                                    ->visible(fn (Get $get) => in_array($get('title'), ['TRAINING RECORDING', 'TRAINING DECK', 'TRAINING GUIDELINE']))
                                    ->required(fn (Get $get) => in_array($get('title'), ['TRAINING RECORDING', 'TRAINING DECK', 'TRAINING GUIDELINE'])),
                            ]),

                        ToggleButtons::make('training_type')
                            ->label('Training Type')
                            ->options([
                                'WEBINAR' => 'Webinar',
                                'HRDF' => 'HRDF',
                            ])
                            ->required()
                            ->default(fn () => $this->trainingType ?: null)
                            ->grouped(),

                        Toggle::make('is_link')
                            ->label('Upload as Link')
                            ->helperText(fn (Get $get) => $get('title') === 'TRAINING RECORDING'
                                ? 'Training Recording requires a link'
                                : 'Enable to paste a URL instead of uploading a file')
                            ->live()
                            ->default(fn () => str_replace('_', ' ', $this->activeTab) === 'TRAINING RECORDING')
                            ->disabled(fn (Get $get) => $get('title') === 'TRAINING RECORDING')
                            ->dehydrated(true),

                        TextInput::make('link_url')
                            ->label('Link URL')
                            ->url()
                            ->placeholder('https://...')
                            ->visible(fn (Get $get) => $get('is_link'))
                            ->required(fn (Get $get) => $get('is_link')),

                        FileUpload::make('file_upload')
                            ->label('File')
                            ->disk('public')
                            ->directory('trainer')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-powerpoint',
                                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'video/mp4',
                                'video/webm',
                                'video/quicktime',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'application/zip',
                                'application/x-rar-compressed',
                            ])
                            ->maxSize(102400) // 100MB
                            ->visible(fn (Get $get) => !$get('is_link'))
                            ->required(fn (Get $get) => !$get('is_link')),
                    ])
                    ->action(function (array $data) {
                        $isLink = $data['is_link'] ?? false;
                        $trainingType = $data['training_type'] ?? null;
                        $trainingTypes = is_array($trainingType) ? $trainingType : ($trainingType ? [$trainingType] : []);
                        $version = $data['version'] ?? 'general';
                        $title = $data['title'];

                        if ($isLink) {
                            TrainerFile::create([
                                'title' => $title,
                                'version' => $data['version'] ?? null,
                                'module_type' => $data['module_type'] ?? null,
                                'file_name' => $data['link_url'],
                                'file_path' => $data['link_url'],
                                'is_link' => true,
                                'training_type' => $trainingTypes,
                                'uploaded_by' => Auth::id(),
                            ]);
                        } else {
                            $uploadedFile = $data['file_upload'];
                            if (is_array($uploadedFile)) {
                                $uploadedFile = $uploadedFile[0];
                            }

                            $extension = strtolower(pathinfo(Storage::disk('public')->path($uploadedFile), PATHINFO_EXTENSION));
                            $trainingTypeStr = !empty($trainingTypes) ? implode('_', $trainingTypes) : 'general';
                            $dirName = strtolower($trainingTypeStr . '_' . $version);
                            $safeTitle = strtolower(str_replace(['/', '\\', ' '], '_', $title));
                            $moduleType = $data['module_type'] ?? null;

                            if ($moduleType) {
                                $safeModule = strtolower(str_replace(['/', '\\', ' ', '&', '-'], '_', $moduleType));
                                $fileName = $safeTitle . '_' . $safeModule . '.' . $extension;
                            } else {
                                $fileName = $safeTitle . '.' . $extension;
                            }

                            $newPath = "trainer/{$dirName}/{$fileName}";

                            if (!Storage::disk('public')->exists("trainer/{$dirName}")) {
                                Storage::disk('public')->makeDirectory("trainer/{$dirName}");
                            }

                            Storage::disk('public')->move($uploadedFile, $newPath);

                            TrainerFile::create([
                                'title' => $title,
                                'version' => $data['version'] ?? null,
                                'module_type' => $data['module_type'] ?? null,
                                'file_name' => $fileName,
                                'file_path' => $newPath,
                                'is_link' => false,
                                'training_type' => $trainingTypes,
                                'uploaded_by' => Auth::id(),
                            ]);
                        }

                        Notification::make()
                            ->title($isLink ? 'Link added successfully' : 'File uploaded successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->emptyStateHeading('No files found')
            ->emptyStateDescription('No training files match your criteria.')
            ->emptyStateIcon('heroicon-o-document');
    }

    protected function getFilteredQuery()
    {
        $query = TrainerFile::query()->orderBy('module_type')->orderBy('version');

        // Filter by training type (HRDF or WEBINAR)
        if ($this->trainingType) {
            $query->whereJsonContains('training_type', $this->trainingType);
        }

        // Filter by title using activeTab (TRAINING DECK, TRAINING SOP, etc.)
        if ($this->activeTab) {
            $title = str_replace('_', ' ', $this->activeTab);
            $query->where('title', $title);
        }

        return $query;
    }

    public static function getSlug(): string
    {
        return 'trainer-file-view';
    }
}
