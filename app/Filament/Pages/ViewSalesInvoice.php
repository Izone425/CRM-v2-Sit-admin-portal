<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class ViewSalesInvoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.view-sales-invoice';
    protected static ?string $title = 'Sales Invoice';
    protected static ?string $slug = 'view-sales-invoice';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $invoiceId = null;
    public ?int $quotationId = null;
    public ?int $softwareHandoverId = null;
    public ?int $hrAccountId = null;
    public ?int $hrCompanyId = null;
    public ?string $invoiceNo = null;
    public ?string $from = null;

    public function mount(): void
    {
        $this->invoiceId = Request::query('invoiceId') ? (int) Request::query('invoiceId') : null;
        $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
        $this->hrAccountId = Request::query('hrAccountId') ? (int) Request::query('hrAccountId') : null;
        $this->hrCompanyId = Request::query('hrCompanyId') ? (int) Request::query('hrCompanyId') : null;
        $this->invoiceNo = Request::query('invoiceNo') ? (string) Request::query('invoiceNo') : null;
        $this->from = Request::query('from') ? (string) Request::query('from') : null;

        if (($this->hrAccountId === null || $this->hrCompanyId === null) && $this->softwareHandoverId) {
            $softwareHandover = \App\Models\SoftwareHandover::select(['id', 'hr_account_id', 'hr_company_id'])
                ->find($this->softwareHandoverId);
            $this->hrAccountId = $this->hrAccountId ?? $softwareHandover?->hr_account_id;
            $this->hrCompanyId = $this->hrCompanyId ?? $softwareHandover?->hr_company_id;
        }
    }

    public function getTitle(): string
    {
        return 'Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        if ($this->from === 'billing') {
            return [
                url('/admin/hr-billing-sales-invoice') => 'Sales of Invoice',
                '#' => 'Sales Invoice',
            ];
        }

        if ($this->from === 'expiring-invoices') {
            return [
                url('/admin/hr-billing-expiring-invoices') => 'Expiring Invoices',
                '#' => 'Sales Invoice',
            ];
        }

        if ($this->from === 'official-receipt') {
            return [
                url('/admin/hr-billing-official-receipt') => 'Official Receipt',
                '#' => 'Sales Invoice',
            ];
        }

        $breadcrumbs = [
            url('/admin/hr-license') => 'All Licenses',
        ];

        if ($this->softwareHandoverId) {
            $tab = $this->from === 'products' ? 'products' : 'invoice';
            $breadcrumbs[url('/admin/hr-company-license-details?' . http_build_query([
                'hrAccountId' => $this->hrAccountId,
                'hrCompanyId' => $this->hrCompanyId,
                'tab' => $tab,
            ]))] = 'Company Details';
        }

        $breadcrumbs['#'] = 'Sales Invoice';

        return $breadcrumbs;
    }
}
