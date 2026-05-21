<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use App\Models\QuotationDetail;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->url(static::getResource()::getUrl())
                ->icon('heroicon-o-chevron-left')
                ->button()
                ->color('info'),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->cleanupInvalidQuotationItems();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['quotation_date'] = Carbon::createFromFormat('j M Y',$data['quotation_date'])->format('Y-m-d');

        // Strip empty items (no product selected) before saving
        if (isset($data['items'])) {
            $data['items'] = array_values(
                array_filter($data['items'], fn($item) => !empty($item['product_id']))
            );
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $this->cleanupInvalidQuotationItems();
        $this->record->unsetRelation('items');
        $this->record->load('items');
    }

    protected function cleanupInvalidQuotationItems(): void
    {
        QuotationDetail::query()
            ->where('quotation_id', $this->record->getKey())
            ->where(function ($query) {
                $query->whereNull('product_id')
                    ->orWhere('product_id', '<=', 0);
            })
            ->delete();
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
                ->success()
                ->title('Quotation saved')
                ->body('The quotation #'.$this->record->quotation_reference_no.' has been saved successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
