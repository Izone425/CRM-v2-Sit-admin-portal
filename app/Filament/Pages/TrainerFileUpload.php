<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Table;
use App\Models\TrainerFile;
use Filament\Forms\Components\Grid;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class TrainerFileUpload extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-arrow-up';
    protected static string $view = 'filament.pages.trainer-file-upload';
    protected static ?string $navigationLabel = 'Trainer Files';
    protected static ?string $navigationGroup = 'Training';
    protected static ?int $navigationSort = 65;

    public function getTitle(): string
    {
        return 'Trainer File Management';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TrainerFile::query()->orderBy('created_at', 'desc'))
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('version')
                    ->label('Version')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('module_type')
                    ->label('Module')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->placeholder('—'),

                TextColumn::make('is_link')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'Link' : 'File')
                    ->color(fn (bool $state) => $state ? 'info' : 'success'),

                TextColumn::make('training_type')
                    ->label('Training Type')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) return '—';
                        if (is_array($state)) return implode(', ', $state);
                        return $state;
                    })
                    ->color('warning'),

                TextColumn::make('file_name')
                    ->label('File/Link')
                    ->searchable()
                    ->limit(40)
                    ->tooltip(fn (TrainerFile $record) => $record->file_name)
                    ->url(fn (TrainerFile $record) => $record->is_link ? $record->file_path : null, shouldOpenInNewTab: true),

                TextColumn::make('uploader.name')
                    ->label('Uploaded By')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Upload Date')
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
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
                            // Use title as download name with proper extension
                            $extension = pathinfo($record->file_name, PATHINFO_EXTENSION);
                            $downloadName = $record->title . '.' . $extension;

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
                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (TrainerFile $record) {
                        // Only delete file from storage if it's not a link
                        if (!$record->is_link && Storage::disk('public')->exists($record->file_path)) {
                            Storage::disk('public')->delete($record->file_path);
                        }

                        $record->delete();

                        Notification::make()
                            ->title($record->is_link ? 'Link deleted successfully' : 'File deleted successfully')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                BulkAction::make('delete')
                    ->label('Delete Selected')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($records) {
                        foreach ($records as $record) {
                            // Only delete file from storage if it's not a link
                            if (!$record->is_link && Storage::disk('public')->exists($record->file_path)) {
                                Storage::disk('public')->delete($record->file_path);
                            }
                            $record->delete();
                        }

                        Notification::make()
                            ->title('Items deleted successfully')
                            ->success()
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
                            ->grouped(),

                        Toggle::make('is_link')
                            ->label('Upload as Link')
                            ->helperText(fn (Get $get) => $get('title') === 'TRAINING RECORDING'
                                ? 'Training Recording requires a link'
                                : 'Enable to paste a URL instead of uploading a file')
                            ->live()
                            ->default(false)
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
                        // Ensure training_type is always an array
                        $trainingTypes = is_array($trainingType) ? $trainingType : ($trainingType ? [$trainingType] : []);
                        $version = $data['version'] ?? 'general';
                        $title = $data['title'];

                        if ($isLink) {
                            // Handle link upload
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
                            // Handle file upload
                            $uploadedFile = $data['file_upload'];
                            if (is_array($uploadedFile)) {
                                $uploadedFile = $uploadedFile[0];
                            }

                            // Get file extension from original file
                            $extension = strtolower(pathinfo(Storage::disk('public')->path($uploadedFile), PATHINFO_EXTENSION));

                            // Build directory name: {training_type}_{version} in lowercase
                            $trainingTypeStr = !empty($trainingTypes) ? implode('_', $trainingTypes) : 'general';
                            $dirName = strtolower($trainingTypeStr . '_' . $version);

                            // File name: {title}_{module_type}.{ext} in lowercase
                            $safeTitle = strtolower(str_replace(['/', '\\', ' '], '_', $title));
                            $moduleType = $data['module_type'] ?? null;
                            if ($moduleType) {
                                $safeModule = strtolower(str_replace(['/', '\\', ' ', '&', '-'], '_', $moduleType));
                                $fileName = $safeTitle . '_' . $safeModule . '.' . $extension;
                            } else {
                                $fileName = $safeTitle . '.' . $extension;
                            }

                            // Full path: trainer/{training_type}_{version}/{title}.{ext}
                            $newPath = "trainer/{$dirName}/{$fileName}";

                            // Create directory if not exists
                            if (!Storage::disk('public')->exists("trainer/{$dirName}")) {
                                Storage::disk('public')->makeDirectory("trainer/{$dirName}");
                            }

                            // Move the file from temp location to organized path
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
            ]);
    }
}
