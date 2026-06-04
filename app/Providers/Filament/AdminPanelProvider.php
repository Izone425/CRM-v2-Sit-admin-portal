<?php

namespace App\Providers\Filament;

use App\Filament\Pages\AddSalesInvoice;
use App\Filament\Pages\HrCompanyLicenseDetails;
use App\Filament\Pages\HrLicense;
use App\Filament\Pages\AdminDebtorProcessDataMyr;
use App\Filament\Pages\AdminDebtorProcessDataUsd;
use App\Filament\Pages\AdminPortalHrV2;
use App\Filament\Pages\AdminRenewalProcessData;
use App\Filament\Pages\AdminRenewalProcessDataMyr;
use App\Filament\Pages\AdminRenewalProcessDataUsd;
use App\Filament\Pages\AdminRenewalDashboard;
use App\Filament\Pages\AdminRenewalDashboardNonReseller;
use App\Filament\Pages\AdminRenewalRawData;
use App\Filament\Pages\AdminRepairDashboard;
use App\Filament\Pages\ApolloLeadTracker;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\Calendar;
use App\Filament\Pages\CallLogAnalysis;
use App\Filament\Pages\ChatRoom;
use App\Filament\Pages\CustomerPortalRawData;
use App\Filament\Pages\CustomPublicHolidayPage;
use App\Filament\Pages\DashboardForm;
use App\Filament\Pages\DebtorAgingProcessData;
use App\Filament\Pages\DebtorAgingRawData;
use App\Filament\Pages\DebtorAgingReport;
use App\Filament\Pages\DebtorRawData;
use App\Filament\Pages\DemoAnalysis;
use App\Filament\Pages\DemoAnalysisTableForm;
use App\Filament\Pages\DemoRanking;
use App\Filament\Pages\DepartmentCalendar;
use App\Filament\Pages\DevicePurchaseInformation;
use App\Filament\Pages\DeviceStockInformation;
use App\Filament\Pages\FinanceHandoverList;
use App\Filament\Pages\FutureEnhancement as PagesFutureEnhancement;
use App\Filament\Pages\HardwareDashboardAll;
use App\Filament\Pages\HardwareDashboardPendingStock;
use App\Filament\Pages\HeadcountHandoverList;
use App\Filament\Pages\HrdfClaimTracker;
use App\Filament\Pages\HrdfHandoverList;
use App\Filament\Pages\HrdfInvoiceList;
use App\Filament\Pages\HrdfInvoiceListV2;
use App\Filament\Pages\HRV2LicenseSeatApiTest;
use App\Filament\Pages\ImplementationSession;
use App\Filament\Pages\ImplementerAuditList;
use App\Filament\Pages\ImplementerCalendar;
use App\Filament\Pages\ImplementerDataFile;
use App\Filament\Pages\ImplementerRequestCount;
use App\Filament\Pages\ImplementerRequestList;
use App\Filament\Pages\InternalTicketsPage;
use App\Filament\Pages\QcTicketing\QcTicketingBugList;
use App\Filament\Pages\QcTicketing\QcTicketingCreativeRequestList;
use App\Filament\Pages\QcTicketing\QcTicketingDashboard;
use App\Filament\Pages\QcTicketing\QcTicketingMyWorkspace;
use App\Filament\Pages\QcTicketing\QcTicketingReleases;
use App\Filament\Pages\QcTicketing\QcTicketingSuggestionList;
use App\Filament\Pages\QcTicketing\QcTicketingTaskList;
use App\Filament\Pages\PdtTicketing\PdtTicketingCreativeRequestList;
use App\Filament\Pages\PdtTicketing\PdtTicketingDashboard;
use App\Filament\Pages\PdtTicketing\PdtTicketingMyWorkspace;
use App\Filament\Pages\PdtTicketing\PdtTicketingReleases;
use App\Filament\Pages\PdtTicketing\PdtTicketingSuggestionList;
use App\Filament\Pages\PdtTicketing\PdtTicketingTaskList;
use App\Filament\Pages\InvoicesTable;
use App\Filament\Pages\InvoiceSummary;
use App\Filament\Pages\KickOffMeetingSession;
use App\Filament\Pages\LeadAnalysis;
use App\Filament\Pages\MarketingAnalysis;
use App\Filament\Pages\MonthlyCalendar;
use App\Filament\Pages\OnsiteRepairList;
use App\Filament\Pages\PolicyManagement;
use App\Filament\Pages\ProformaInvoices;
use App\Filament\Pages\ProjectAnalysis;
use App\Filament\Pages\ProjectCategoryClosed;
use App\Filament\Pages\ProjectCategoryDelay;
use App\Filament\Pages\ProjectCategoryInactive;
use App\Filament\Pages\ProjectCategoryOpen;
use App\Filament\Pages\ProjectPlanSummary;
use App\Filament\Pages\ProjectPriority;
use App\Filament\Pages\RenewalDataAnalysis;
use App\Filament\Pages\ResellerAccount;
use App\Filament\Pages\RevenueAnalysis;
use App\Filament\Pages\RevenueTable;
use App\Filament\Pages\SalesAdminAnalysisV1;
use App\Filament\Pages\SalesAdminAnalysisV2;
use App\Filament\Pages\SalesAdminAnalysisV3;
use App\Filament\Pages\SalesAdminAnalysisV4;
use App\Filament\Pages\SalesAdminClosedDeal;
use App\Filament\Pages\SalesAdminInvoice;
use App\Filament\Pages\SalesDebtor;
use App\Filament\Pages\SalesForecast;
use App\Filament\Pages\SalesForecastSummary;
use App\Filament\Pages\SalespersonAppointment;
use App\Filament\Pages\SalespersonCalendarV1;
use App\Filament\Pages\SalespersonCalendarV2;
use App\Filament\Pages\SalespersonLeadSequence;
use App\Filament\Pages\SalespersonLeadSequenceV2;
use App\Filament\Pages\SalesPersonSurveyRequest;
use App\Filament\Pages\SalesPricingManagement;
use App\Filament\Pages\SearchLead;
use App\Filament\Pages\SearchLicense;
use App\Filament\Pages\SoftwareHandoverAnalysisV2;
use App\Filament\Pages\SubmitHrdfAttendanceLog;
use App\Filament\Pages\SupportCalendar;
use App\Filament\Pages\SupportCalendarWeekday;
use App\Filament\Pages\SupportCallLog;
use App\Filament\Pages\TechnicianAppointment;
use App\Filament\Pages\TechnicianCalendar;
use App\Filament\Pages\TicketAnalysis;
use App\Filament\Pages\TicketDashboard;
use App\Filament\Pages\TicketList;
use App\Filament\Pages\QCPromptTask;
use App\Filament\Pages\QCPromptTaskCreate;
use App\Filament\Pages\QCPromptTaskEdit;
use App\Filament\Pages\TrainerFileUpload;
use App\Filament\Pages\TrainerFileView;
use App\Filament\Pages\TrainerHandover;
use App\Filament\Pages\TrainingRequest;
use App\Filament\Pages\TrainingRequestTrainer1;
use App\Filament\Pages\TrainingRequestTrainer2;
use App\Filament\Pages\TrainingSettingTrainer1;
use App\Filament\Pages\TrainingSettingTrainer2;
use App\Filament\Pages\ViewSalesInvoice;
use App\Filament\Pages\ViewOfficialReceipt;
use App\Filament\Pages\HrBillingSalesInvoice;
use App\Filament\Pages\HrBillingExpiringInvoices;
use App\Filament\Pages\HrBillingOfficialReceipt;
use App\Filament\Pages\HrBillingPayment;
use App\Filament\Pages\HrBillingAutoRenewal;
use App\Filament\Pages\HrBillingCommission;
use App\Filament\Pages\HrBillingCreditNotes;
use App\Filament\Pages\HrCustomerCredential;
use App\Filament\Pages\HrLoginAuditTrail;
use App\Filament\Pages\ResellerCommissionDashboard;
use App\Filament\Pages\OffsetPaymentDashboard;
use App\Filament\Pages\HrDevices;
use App\Filament\Pages\HrDistributors;
use App\Filament\Pages\HrResellers;
use App\Filament\Pages\HrV2RenewalDashboard;
use App\Filament\Pages\PayrollV1Analysis;
use App\Filament\Pages\ProjectSessionList;
use App\Filament\Pages\ResellerAnalysis;
use App\Filament\Pages\SupportCalendarV2;
use App\Filament\Pages\TerminationAnalysis;
use App\Filament\Pages\Whatsapp;
use App\Filament\Resources\AdminRepairResource;
use App\Filament\Resources\CallCategoryResource;
use App\Filament\Resources\DemoResource;
use App\Filament\Resources\DeviceModelResource;
use App\Filament\Resources\EmailTemplateResource;
use App\Filament\Resources\HardwareAttachmentResource;
use App\Filament\Resources\HardwarePendingStockResource;
use App\Filament\Resources\IndustryResource;
use App\Filament\Resources\InstallerResource;
use App\Filament\Resources\InvalidLeadReasonResource;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadSourceResource;
use App\Filament\Resources\PhoneExtensionResource;
use App\Filament\Resources\PolicyCategoryResource;
use App\Filament\Resources\PolicyResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProjectTaskResource;
use App\Filament\Resources\QuotationResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\ResellerResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\SalesPricingResource;
use App\Filament\Resources\ShippingDeviceModelResource;
use App\Filament\Resources\SoftwareAttachmentResource;
use App\Filament\Resources\SoftwareResource;
use App\Filament\Resources\SparePartResource;
use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Support\Assets\Css;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Filament\Facades\Filament;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            // ->login(\App\Filament\Pages\Auth\Login::class)
            ->login()
            // ->databaseNotifications()
            // ->registration()
            // ->passwordReset()
            ->emailVerification()
            ->profile(EditProfile::class)
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->brandName('TimeTec CRM')
            ->colors([
                'primary' => '#431fa1',
            ])
            ->assets([
                Css::make('styles', public_path('/css/app/styles.css')),
                Css::make('sidebar', public_path('/css/custom-sidebar.css')),
                Css::make('admin-theme', public_path('/css/admin-theme.css')),
                \Filament\Support\Assets\Js::make('sidebar-js', public_path('/js/custom-sidebar.js')),
            ])
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_END,
                fn (): string => '<style>.fi-no-notifications{z-index:99999999!important;}</style>',
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_START,
                function (): string {
                    if (!auth()->check() || !session('impersonator_id')) {
                        return '';
                    }
                    $userName = auth()->user()->name;
                    $leaveUrl = route('impersonate.leave');
                    return <<<HTML
                    <div style="position:fixed;bottom:0;left:0;right:0;z-index:99999;background:#F59E0B;color:#1F2937;padding:8px 20px;display:flex;align-items:center;justify-content:center;gap:12px;font-size:14px;font-weight:600;box-shadow:0 -2px 4px rgba(0,0,0,0.1);">
                        <span>You are logged in as <strong>{$userName}</strong></span>
                        <a href="{$leaveUrl}" style="background:#1F2937;color:white;padding:4px 14px;border-radius:6px;text-decoration:none;font-size:13px;">Switch Back</a>
                    </div>
                    HTML;
                },
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => \Illuminate\Support\Facades\Blade::render('@livewire(\App\Livewire\CountryDivisionSelector::class)'),
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                function (): string {
                    $qcRoutes = [
                        'filament.admin.pages.pdt-ticketing.dashboard',
                        'filament.admin.pages.pdt-ticketing.my-workspace',
                        'filament.admin.pages.pdt-ticketing.task-list',
                        'filament.admin.pages.pdt-ticketing.suggestion-list',
                        'filament.admin.pages.pdt-ticketing.creative-request-list',
                        'filament.admin.pages.pdt-ticketing.releases',
                    ];
                    if (!in_array(request()->route()?->getName(), $qcRoutes, true)) {
                        return '';
                    }
                    return \Illuminate\Support\Facades\Blade::render('@livewire(\App\Livewire\QcCreateMenu::class)');
                },
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::USER_MENU_BEFORE,
                function (): string {
                    if (!in_array(auth()->id(), [64, 1, 41, 43, 51, 28, 29, 14, 24])) {
                        return '';
                    }
                    return \Illuminate\Support\Facades\Blade::render('@livewire(\App\Livewire\SwitchRoleMenu::class)');
                },
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn (): string => auth()->check()
                    ? \Illuminate\Support\Facades\Blade::render('@livewire("browser-notification-poller")')
                    : '',
            )
            ->renderHook(
                'panels::body.start',  // This is critical - it needs to be panels::body.start
                fn (): string => view('layouts.custom-sidebar')->render()
            )
            ->renderHook(
                'panels::body.start',
                function (): string {
                    // Only render the sidebar for authenticated users
                    if (auth()->check()) {
                        return view('layouts.custom-sidebar')->render();
                    }
                    return ''; // Return empty string for unauthenticated users
                }
            )
            ->renderHook(
                'panels::content.start',
                function (): string {
                    if (auth()->check()) {
                        return '<div class="custom-content-wrapper">';
                    }
                    return '';
                }
            )
            ->renderHook(
                'panels::content.end',
                function (): string {
                    if (auth()->check()) {
                        return '</div>';
                    }
                    return '';
                }
            )
            ->renderHook(
                'panels::styles.after',
                fn (): string => <<<'HTML'
                <style>
                    /* Multi-line tabs styling */
                    .multiline-tabs .fi-tabs {
                        flex-wrap: wrap;
                        max-height: none !important;
                    }

                    .multiline-tabs .fi-tabs-item {
                        margin-bottom: 0.5rem;
                    }

                    /* Target all possible notification badge selectors */
                    .fi-notification-count,
                    button[data-notifications-badge] .fi-badge,
                    .fi-icon-btn-badge-ctn .fi-badge,
                    [data-notifications-badge] .fi-badge {
                        background-color: #ef4444 !important; /* Red background */
                        color: white !important; /* White text */
                        border-color: #ef4444 !important; /* Matching border */
                        --tw-ring-color: rgba(239, 68, 68, 0.3) !important;
                    }

                    /* Make sure the bell icon's notification indicator is styled */
                    .fi-icon-btn svg + .fi-badge,
                    .fi-icon-btn-badge-ctn .fi-badge {
                        background-color: #ef4444 !important;
                        color: white !important;
                    }
                </style>
                HTML
            )
            // ->navigation(false)
            ->darkMode(false)
            // ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('4rem')
            // ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([  // Manually registering specific resources
                LeadResource::class,
                ProductResource::class,
                QuotationResource::class,
                DemoResource::class,
                IndustryResource::class,
                LeadSourceResource::class,
                InvalidLeadReasonResource::class,
                UserResource::class,
                ResellerResource::class,
                SoftwareResource::class,
                RoleResource::class,
                SoftwareAttachmentResource::class,
                HardwareAttachmentResource::class,
                InstallerResource::class,
                HardwarePendingStockResource::class,
                SparePartResource::class,
                AdminRepairResource::class,
                EmailTemplateResource::class,
                DeviceModelResource::class,
                CallCategoryResource::class,
                PhoneExtensionResource::class,
                PolicyResource::class,
                PolicyCategoryResource::class,
                ShippingDeviceModelResource::class,
                SalesPricingResource::class,
                ProjectTaskResource::class,
            ])
            // ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                // Pages\Dashboard::class,
                SalespersonCalendarV1::class,
                SalespersonCalendarV2::class,
                // SalespersonCalendarV3::class,
                MonthlyCalendar::class,
                TechnicianCalendar::class,
                DemoRanking::class,
                DashboardForm::class,
                ProformaInvoices::class,
                Whatsapp::class,
                LeadAnalysis::class,
                DemoAnalysis::class,
                MarketingAnalysis::class,
                SalesForecast::class,
                SalesAdminAnalysisV1::class,
                SalesAdminAnalysisV2::class,
                SalesAdminAnalysisV3::class,
                SalesForecastSummary::class,
                PagesFutureEnhancement::class,
                SearchLead::class,
                HardwareDashboardAll::class,
                HardwareDashboardPendingStock::class,
                OnsiteRepairList::class,
                ImplementerCalendar::class,
                ImplementerDataFile::class,
                ProjectAnalysis::class,
                DemoAnalysisTableForm::class,
                TechnicianAppointment::class,
                SalespersonAppointment::class,
                ImplementerAuditList::class,
                SalespersonLeadSequence::class,
                Calendar::class,
                ImplementerRequestCount::class,
                ImplementerRequestList::class,
                ProjectCategoryOpen::class,
                ProjectCategoryDelay::class,
                ProjectCategoryInactive::class,
                ProjectCategoryClosed::class,
                KickOffMeetingSession::class,
                ImplementationSession::class,
                SoftwareHandoverAnalysisV2::class,
                SupportCalendar::class,
                SupportCalendarWeekday::class,
                SupportCallLog::class,
                SalesPersonSurveyRequest::class,
                ProjectPriority::class,
                SalesAdminAnalysisV4::class,
                RevenueAnalysis::class,
                RevenueTable::class,
                PolicyManagement::class,
                AdminRenewalProcessData::class,
                AdminRenewalRawData::class,
                DebtorAgingRawData::class,
                DebtorAgingProcessData::class,
                DeviceStockInformation::class,
                DevicePurchaseInformation::class,
                SalesDebtor::class,
                InvoiceSummary::class,
                AdminRenewalProcessDataMyr::class,
                AdminRenewalProcessDataUsd::class,
                SearchLicense::class,
                SalesAdminClosedDeal::class,
                InvoicesTable::class,
                SalesPricingManagement::class,
                HeadcountHandoverList::class,
                HrdfHandoverList::class,
                RenewalDataAnalysis::class,
                CallLogAnalysis::class,
                SalesAdminInvoice::class,
                FinanceHandoverList::class,
                CustomerPortalRawData::class,
                HrdfClaimTracker::class,
                AdminPortalHrV2::class,
                TicketList::class,
                TicketDashboard::class,
                QCPromptTask::class,
                QCPromptTaskCreate::class,
                QCPromptTaskEdit::class,
                SubmitHrdfAttendanceLog::class,
                ProjectPlanSummary::class,
                ApolloLeadTracker::class,
                SalespersonLeadSequenceV2::class,
                TrainingSettingTrainer1::class,
                TrainingSettingTrainer2::class,
                TrainingRequest::class,
                TrainingRequestTrainer1::class,
                TrainingRequestTrainer2::class,
                HrdfInvoiceListV2::class,
                HrdfInvoiceList::class,
                AdminRenewalDashboard::class,
                AdminRenewalDashboardNonReseller::class,
                InternalTicketsPage::class,
                QcTicketingDashboard::class,
                QcTicketingMyWorkspace::class,
                QcTicketingTaskList::class,
                QcTicketingBugList::class,
                QcTicketingSuggestionList::class,
                QcTicketingCreativeRequestList::class,
                QcTicketingReleases::class,
                PdtTicketingDashboard::class,
                PdtTicketingMyWorkspace::class,
                PdtTicketingTaskList::class,
                PdtTicketingSuggestionList::class,
                PdtTicketingCreativeRequestList::class,
                PdtTicketingReleases::class,
                ResellerAccount::class,
                TicketAnalysis::class,
                TrainerHandover::class,
                TrainerFileUpload::class,
                TrainerFileView::class,
                CustomPublicHolidayPage::class,
                ViewSalesInvoice::class,
                AddSalesInvoice::class,
                HrCompanyLicenseDetails::class,
                HrLicense::class,
                // LicenseTerminatedList::class,
                // Billing Pages
                HrBillingSalesInvoice::class,
                HrBillingExpiringInvoices::class,
                HrBillingOfficialReceipt::class,
                HrBillingPayment::class,
                HrBillingAutoRenewal::class,
                HrBillingCommission::class,
                HrBillingCreditNotes::class,
                ViewOfficialReceipt::class,
                // Customer Credential
                HrCustomerCredential::class,
                // Login Audit Trail
                HrLoginAuditTrail::class,
                // Devices
                HrDevices::class,
                // Account Management
                HrDistributors::class,
                HrResellers::class,
                ResellerAnalysis::class,
                ResellerCommissionDashboard::class,
                OffsetPaymentDashboard::class,
                TerminationAnalysis::class,
                HrV2RenewalDashboard::class,
                ProjectSessionList::class,
                DebtorRawData::class,
                SupportCalendarV2::class,
                PayrollV1Analysis::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
                // LeadChartWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->plugins([
                FilamentFullCalendarPlugin::make()
            ])
            ->maxContentWidth(MaxWidth::Full);
    }
}
