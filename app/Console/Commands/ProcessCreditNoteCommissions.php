<?php

namespace App\Console\Commands;

use App\Models\CrmInvoiceDetail;
use App\Models\ResellerCommissionHandover;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCreditNoteCommissions extends Command
{
    protected $signature = 'reseller-commission:process-credit-notes';

    protected $description = 'Auto-mark reseller commission invoices as Credit Note when a matching entry exists in credit_notes';

    public function handle()
    {
        $invoiceNumbers = DB::table('credit_notes')
            ->whereNotNull('invoice_number')
            ->where('invoice_number', '!=', '')
            ->distinct()
            ->pluck('invoice_number');

        if ($invoiceNumbers->isEmpty()) {
            $this->info('No credit notes to process.');
            return Command::SUCCESS;
        }

        $processed = 0;
        $skipped = 0;

        foreach ($invoiceNumbers as $invoiceNumber) {
            $records = CrmInvoiceDetail::query()
                ->select([
                    'crm_invoice_details.f_id',
                    'crm_invoice_details.f_invoice_no',
                    'crm_invoice_details.f_desc',
                    'crm_invoice_details.f_company_id',
                    'crm_invoice_details.f_total_amount',
                    'crm_invoice_details.f_currency',
                    DB::raw("TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) as tt_invoice_no"),
                    DB::raw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) as autocount_inv_no"),
                    DB::raw("(SELECT LPAD(rl.reseller_id, 10, '0') FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as crm_reseller_id"),
                    DB::raw("(SELECT rl.reseller_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as reseller_name"),
                    DB::raw("(SELECT rl.f_company_name FROM crm_reseller_link rl WHERE rl.f_id = crm_invoice_details.f_company_id LIMIT 1) as subscriber_name"),
                ])
                ->where('crm_invoice_details.f_invoice_no', 'LIKE', 'AP%')
                ->where('crm_invoice_details.f_created_time', '>=', '2026-01-01')
                ->where('crm_invoice_details.f_total_amount', '>', 0)
                ->whereRaw("TRIM(BOTH '\\r\\n' FROM (SELECT tt.f_auto_count_inv FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1)) = ?", [$invoiceNumber])
                ->whereRaw("(SELECT tt.f_status FROM crm_invoice_details tt WHERE tt.f_invoice_no = TRIM(SUBSTRING(crm_invoice_details.f_desc, LOCATE('TT', crm_invoice_details.f_desc))) LIMIT 1) IN (0, 1)")
                ->get();

            foreach ($records as $record) {
                if (ResellerCommissionHandover::where('ap_invoice_no', $record->f_invoice_no)->exists()) {
                    $skipped++;
                    continue;
                }

                ResellerCommissionHandover::create([
                    'reseller_id' => $record->crm_reseller_id,
                    'ap_invoice_no' => $record->f_invoice_no,
                    'tt_invoice_no' => $record->tt_invoice_no,
                    'autocount_inv_no' => $record->autocount_inv_no,
                    'reseller_name' => $record->reseller_name,
                    'subscriber_name' => $record->subscriber_name,
                    'amount' => $record->f_total_amount,
                    'currency' => $record->f_currency ?? 'MYR',
                    'status' => 'credit_note',
                ]);

                Log::info('Auto-created credit_note commission handover', [
                    'ap_invoice_no' => $record->f_invoice_no,
                    'autocount_inv_no' => $record->autocount_inv_no,
                    'credit_note_invoice_number' => $invoiceNumber,
                ]);

                $processed++;
            }
        }

        $this->info("Processed: {$processed}, Skipped (handover already exists): {$skipped}");

        return Command::SUCCESS;
    }
}
