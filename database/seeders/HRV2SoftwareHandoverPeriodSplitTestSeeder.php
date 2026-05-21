<?php

namespace Database\Seeders;

use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\LicenseCertificate;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HRV2SoftwareHandoverPeriodSplitTestSeeder extends Seeder
{
    public function run(): void
    {
        $faker = fake();

        $leadColumns = array_flip(Schema::getColumnListing('leads'));
        $companyColumns = array_flip(Schema::getColumnListing('company_details'));
        $handoverColumns = array_flip(Schema::getColumnListing('software_handovers'));
        $quotationColumns = array_flip(Schema::getColumnListing('quotations'));
        $quotationDetailColumns = array_flip(Schema::getColumnListing('quotation_details'));
        $productColumns = array_flip(Schema::getColumnListing('products'));
        $licCertColumns = array_flip(Schema::getColumnListing('license_certificates'));
        $createdBy = User::query()->value('id');

        $products = $this->resolveProducts($productColumns, $faker);

        // ============================================================
        // CASE A: Single handover with 2 PIs (webinar training)
        //   PI-A: TA + TL (75 seats, 12 months)
        //   PI-B: TC (75 seats, 12 months)
        // ============================================================
        $this->createMultiPiCase($faker, $products, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $handoverColumns, $licCertColumns, $createdBy, [
            'tag' => 'MPI01',
            'name' => 'CASE A - 2 PIs: TA+TL and TC',
            'headcount' => 75,
            'training_type' => 'online_webinar_training',
            'quotations' => [
                [
                    'suffix' => 'A',
                    'sales_type' => 'NEW SALES',
                    'modules' => ['ta' => 75, 'tl' => 75],
                    'period' => 12,
                ],
                [
                    'suffix' => 'B',
                    'sales_type' => 'ADD ON NEW SALES',
                    'modules' => ['tc' => 75],
                    'period' => 12,
                ],
            ],
        ]);

        // ============================================================
        // CASE B: Single handover with 3 PIs (HRDF training)
        //   PI-A: TA + TL (100 seats, 12 months) — software_hardware_pi
        //   PI-B: TC + TP (100 seats, 12 months) — non_hrdf_pi
        //   PI-C: HRDF training grant               — proforma_invoice_hrdf
        // ============================================================
        $this->createMultiPiCase($faker, $products, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $handoverColumns, $licCertColumns, $createdBy, [
            'tag' => 'MPI02',
            'name' => 'CASE B - 3 PIs: SW+HW, Non-HRDF, HRDF',
            'headcount' => 100,
            'training_type' => 'online_hrdf_training',
            'quotations' => [
                [
                    'suffix' => 'A',
                    'sales_type' => 'NEW SALES',
                    'pi_type' => 'software_hardware_pi',
                    'modules' => ['ta' => 100, 'tl' => 100],
                    'period' => 12,
                ],
                [
                    'suffix' => 'B',
                    'sales_type' => 'ADD ON NEW SALES',
                    'pi_type' => 'non_hrdf_pi',
                    'modules' => ['tc' => 100, 'tp' => 100],
                    'period' => 12,
                ],
                [
                    'suffix' => 'C',
                    'sales_type' => 'NEW SALES',
                    'pi_type' => 'proforma_invoice_hrdf',
                    'modules' => [],
                    'period' => 12,
                    'is_training' => true,
                ],
            ],
        ]);

        // ============================================================
        // CASE C: BAKHACHE-style — same lead, 2 separate handovers
        //   SW1: TA only (new sales, 75 headcount, 1 PI)
        //   SW2: TL only (addon module, 75 headcount, 1 PI)
        // ============================================================
        $this->createAddonCase($faker, $products, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $handoverColumns, $licCertColumns, $createdBy, [
            'tag' => 'MPI03',
            'name' => 'CASE C - ADDON: SW1=TA, SW2=TL',
        ]);

        // ============================================================
        // CASE D: Same lead, 2 handovers, SW2 has 2 PIs
        //   SW1: TA + TL (new sales, 50 seats, 1 PI)
        //   SW2: TC + TP (addon, 200 seats, 2 PIs — TC in PI-A, TP in PI-B)
        // ============================================================
        $this->createAddonMultiPiCase($faker, $products, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $handoverColumns, $licCertColumns, $createdBy, [
            'tag' => 'MPI04',
            'name' => 'CASE D - ADDON+MULTI-PI: SW1=TA+TL, SW2=TC(PI-A)+TP(PI-B)',
        ]);

        // ============================================================
        // CASE E: Headcount Handover — addon headcount for existing lead
        //   Uses addon HC R products (114-117) with date ranges in description
        //   Linked to MPI03 lead (which already has SW handovers)
        // ============================================================
        $this->createHeadcountHandoverCase($faker, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $createdBy);

        // ============================================================
        // CASE F: 1 month full set — TA+TL+TC+TP, 30 HC, 1 month only
        //   Short-term / trial-like paid license
        // ============================================================
        $this->createMultiPiCase($faker, $products, $leadColumns, $companyColumns, $quotationColumns, $quotationDetailColumns, $handoverColumns, $licCertColumns, $createdBy, [
            'tag' => 'MPI05',
            'name' => 'CASE F - 1 MONTH FULL SET TA+TL+TC+TP',
            'headcount' => 30,
            'training_type' => 'online_webinar_training',
            'quotations' => [
                [
                    'suffix' => 'A',
                    'sales_type' => 'NEW SALES',
                    'modules' => ['ta' => 30, 'tl' => 30, 'tc' => 30, 'tp' => 30],
                    'period' => 1,
                ],
            ],
        ]);
    }

    /**
     * Single handover with multiple quotations/PIs
     */
    private function createMultiPiCase(
        $faker, array $products, array $leadColumns, array $companyColumns,
        array $quotationColumns, array $quotationDetailColumns, array $handoverColumns,
        array $licCertColumns, ?int $createdBy, array $case
    ): void {
        $tag = $case['tag'];
        $companyName = Str::upper('TEST ' . $tag . ' ' . $faker->unique()->numerify('####'));
        $picName = Str::upper(Str::limit($faker->name(), 20, ''));
        $email = Str::lower($faker->unique()->safeEmail());
        $phone = substr(preg_replace('/\D+/', '', $faker->phoneNumber()) ?: '60111222333', 0, 20);
        $implementer = $faker->randomElement(['John Low', 'Ameerul Asyraf', 'Muhamad Izzul Aiman']);

        // Lead & Company
        $lead = Lead::create(array_intersect_key([
            'name' => $picName, 'email' => $email, 'phone' => $phone,
            'company_name' => $companyName, 'company_size' => '25-99',
            'country' => 'MALAYSIA', 'products' => ['TIMETEC HR'],
            'lead_code' => 'L' . now()->format('ymd') . $tag,
            'categories' => 'New', 'stage' => 'New', 'lead_status' => 'New',
            'remark' => $case['name'], 'salesperson' => 'SYSTEM',
            'lead_owner' => 'SYSTEM', 'customer_type' => 'END USER', 'region' => 'LOCAL',
        ], $leadColumns));

        CompanyDetail::create(array_intersect_key([
            'lead_id' => $lead->id, 'company_name' => $companyName,
            'industry' => 'TECHNOLOGY',
            'company_address1' => Str::upper(Str::limit($faker->streetAddress(), 50, '')),
            'company_address2' => Str::upper(Str::limit($faker->city(), 50, '')),
            'postcode' => (string) $faker->numberBetween(10000, 98000),
            'state' => Str::upper($faker->state()),
            'name' => $picName, 'contact_no' => $phone,
            'position' => 'HR MANAGER', 'email' => $email,
        ], $companyColumns));

        // Create quotations
        $quotationIds = [];
        $piFieldMap = [
            'proforma_invoice_product' => [],
            'software_hardware_pi' => [],
            'non_hrdf_pi' => [],
            'proforma_invoice_hrdf' => [],
        ];
        $allModules = ['ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0];
        $type1Entries = [];
        $type2Entries = [];
        $type3Entries = [];

        foreach ($case['quotations'] as $qi => $qDef) {
            $submittedAt = now()->subDays(14 - $qi);

            $quotation = Quotation::create(array_intersect_key([
                'lead_id' => $lead->id,
                'headcount' => (int) $case['headcount'],
                'quotation_date' => $submittedAt->toDateString(),
                'quotation_reference_no' => "QT/SW/{$tag}/{$qDef['suffix']}/" . $submittedAt->format('ymdHis'),
                'pi_reference_no' => "PI/{$tag}/{$qDef['suffix']}",
                'quotation_type' => 'product',
                'currency' => 'MYR',
                'sales_type' => $qDef['sales_type'],
                'hrdf_status' => ($qDef['pi_type'] ?? '') === 'proforma_invoice_hrdf' ? 'HRDF' : 'NON HRDF',
                'subscription_period' => $qDef['period'],
                'status' => 'accepted',
                'tax_rate' => 8,
                'mark_as_final' => 1,
            ], $quotationColumns));

            $quotationIds[] = $quotation->id;

            // Determine which PI field this quotation belongs to
            $piType = $qDef['pi_type'] ?? 'proforma_invoice_product';
            $piFieldMap[$piType][] = (string) $quotation->id;

            // For software_hardware_pi, also add to proforma_invoice_product
            if ($piType === 'software_hardware_pi') {
                $piFieldMap['proforma_invoice_product'][] = (string) $quotation->id;
            }

            $invoiceNo = 'EPIN' . now()->format('ym') . '-' . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT);

            // Build type entries for invoice tracking
            $entry = [
                'quotation_id' => $quotation->id,
                'pi_number' => $quotation->pi_reference_no,
                'company_name' => $companyName,
                'invoice_number' => $invoiceNo,
            ];

            if ($piType === 'proforma_invoice_product' || $piType === 'software_hardware_pi') {
                $type1Entries[] = $entry;
            } elseif ($piType === 'non_hrdf_pi') {
                $type2Entries[] = $entry;
            } elseif ($piType === 'proforma_invoice_hrdf') {
                $type3Entries[] = $entry;
            }

            // Create quotation details for modules
            $sortOrder = 1;
            foreach ($qDef['modules'] as $module => $quantity) {
                if (!isset($products[$module])) continue;
                $product = $products[$module];
                $allModules[$module] = 1;

                $this->createQuotationDetailWithTotals([
                    'quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'description' => $product->description ?? $product->code,
                    'quantity' => (int) $quantity,
                    'subscription_period' => $qDef['period'],
                    'unit_price' => (float) ($product->unit_price ?? 10),
                    'sort_order' => $sortOrder++,
                    'license_start_date' => now()->addMonth()->startOfMonth()->toDateString(),
                    'license_end_date' => now()->addMonth()->startOfMonth()->addMonths($qDef['period'])->subDay()->toDateString(),
                    'year' => 'Year 1',
                    'tax_code' => 'SV-8',
                    'tariff_code' => '9907101676',
                    'convert_pi' => 1,
                ], $quotationDetailColumns);
            }
        }

        // Create software handover with status New (so salesperson can submit)
        $handoverData = [
            'lead_id' => $lead->id,
            'created_by' => $createdBy,
            'status' => 'New',
            'status_handover' => 'Open',
            'project_priority' => 'High',
            'company_name' => $companyName,
            'headcount' => (string) $case['headcount'],
            'salesperson' => 'SYSTEM',
            'pic_name' => $picName,
            'pic_phone' => $phone,
            'ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => null,
            'training_type' => $case['training_type'],
            'speaker_category' => 'english / malay',
            'remarks' => $case['name'],
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false,
            'data_migrated' => false,
            'follow_up_counter' => false,
            'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode($piFieldMap['proforma_invoice_product']),
            'software_hardware_pi' => !empty($piFieldMap['software_hardware_pi']) ? json_encode($piFieldMap['software_hardware_pi']) : null,
            'non_hrdf_pi' => !empty($piFieldMap['non_hrdf_pi']) ? json_encode($piFieldMap['non_hrdf_pi']) : null,
            'proforma_invoice_hrdf' => !empty($piFieldMap['proforma_invoice_hrdf']) ? json_encode($piFieldMap['proforma_invoice_hrdf']) : null,
            'hrdf_grant_ids' => json_encode([]),
            'type_1_pi_invoice_data' => null,
            'type_2_pi_invoice_data' => null,
            'type_3_pi_invoice_data' => null,
            'hr_account_id' => null,
            'hr_company_id' => null,
            'hr_user_id' => null,
            'license_type' => 'new sales',
            'submitted_at' => now()->subDays(14),
            'completed_at' => null,
            'db_creation' => null,
            'kick_off_meeting' => null,
            'go_live_date' => null,
        ];

        if (Schema::hasColumn('software_handovers', 'hr_version')) {
            $handoverData['hr_version'] = 2;
        }

        $handover = SoftwareHandover::create(array_intersect_key($handoverData, $handoverColumns));

        $this->command?->info(
            "Created {$case['name']}"
            . " | Lead: {$lead->id}"
            . " | SW: {$handover->id}"
            . " | PIs: " . implode(', ', $quotationIds)
        );
    }

    /**
     * CASE C: BAKHACHE-style — one lead, two separate software handovers
     * SW1: TA only (new sales), SW2: TL only (addon module)
     */
    private function createAddonCase(
        $faker, array $products, array $leadColumns, array $companyColumns,
        array $quotationColumns, array $quotationDetailColumns, array $handoverColumns,
        array $licCertColumns, ?int $createdBy, array $case
    ): void {
        $tag = $case['tag'];
        $companyName = Str::upper('TEST ' . $tag . ' ' . $faker->unique()->numerify('####'));
        $picName = Str::upper(Str::limit($faker->name(), 20, ''));
        $email = Str::lower($faker->unique()->safeEmail());
        $phone = substr(preg_replace('/\D+/', '', $faker->phoneNumber()) ?: '60111222333', 0, 20);
        $implementer = $faker->randomElement(['John Low', 'Ameerul Asyraf', 'Muhamad Izzul Aiman']);

        $lead = Lead::create(array_intersect_key([
            'name' => $picName, 'email' => $email, 'phone' => $phone,
            'company_name' => $companyName, 'company_size' => '25-99',
            'country' => 'MALAYSIA', 'products' => ['TIMETEC HR'],
            'lead_code' => 'L' . now()->format('ymd') . $tag,
            'categories' => 'New', 'stage' => 'New', 'lead_status' => 'New',
            'remark' => $case['name'], 'salesperson' => 'SYSTEM',
            'lead_owner' => 'SYSTEM', 'customer_type' => 'END USER', 'region' => 'LOCAL',
        ], $leadColumns));

        CompanyDetail::create(array_intersect_key([
            'lead_id' => $lead->id, 'company_name' => $companyName,
            'industry' => 'RETAIL',
            'company_address1' => Str::upper(Str::limit($faker->streetAddress(), 50, '')),
            'company_address2' => Str::upper(Str::limit($faker->city(), 50, '')),
            'postcode' => (string) $faker->numberBetween(10000, 98000),
            'state' => Str::upper($faker->state()),
            'name' => $picName, 'contact_no' => $phone,
            'position' => 'HR MANAGER', 'email' => $email,
        ], $companyColumns));

        // SW1: TA only — new sales
        $sw1At = now()->subWeeks(8);
        $q1 = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 75,
            'quotation_date' => $sw1At->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/A/" . $sw1At->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/A",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $this->createQuotationDetailWithTotals([
            'quotation_id' => $q1->id, 'product_id' => $products['ta']->id,
            'description' => $products['ta']->description ?? $products['ta']->code,
            'quantity' => 75, 'subscription_period' => 12,
            'unit_price' => (float) ($products['ta']->unit_price ?? 10),
            'sort_order' => 1,
            'license_start_date' => $sw1At->copy()->addMonth()->startOfMonth()->toDateString(),
            'license_end_date' => $sw1At->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
            'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
        ], $quotationDetailColumns);

        $inv1 = 'EPIN' . $sw1At->format('ym') . '-' . str_pad(rand(1, 999), 4, '0', STR_PAD_LEFT);

        $sw1Data = [
            'lead_id' => $lead->id, 'created_by' => $createdBy,
            'status' => 'New', 'status_handover' => 'Open', 'project_priority' => 'High',
            'company_name' => $companyName, 'headcount' => '75',
            'salesperson' => 'SYSTEM', 'pic_name' => $picName, 'pic_phone' => $phone,
            'ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => null, 'training_type' => 'online_webinar_training',
            'speaker_category' => 'english / malay', 'remarks' => 'NEW SALES TA ONLY',
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false, 'data_migrated' => false,
            'follow_up_counter' => false, 'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode([(string) $q1->id]),
            'software_hardware_pi' => null, 'non_hrdf_pi' => null,
            'proforma_invoice_hrdf' => null, 'hrdf_grant_ids' => null,
            'type_1_pi_invoice_data' => null,
            'hr_account_id' => null, 'hr_company_id' => null, 'hr_user_id' => null,
            'license_type' => 'new sales',
            'submitted_at' => $sw1At, 'completed_at' => null,
            'db_creation' => null, 'kick_off_meeting' => null, 'go_live_date' => null,
        ];
        if (Schema::hasColumn('software_handovers', 'hr_version')) $sw1Data['hr_version'] = 2;
        $sw1 = SoftwareHandover::create(array_intersect_key($sw1Data, $handoverColumns));

        // SW2: TL only — addon module
        $sw2At = now()->subWeeks(2);
        $q2 = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 75,
            'quotation_date' => $sw2At->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/B/" . $sw2At->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/B",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'ADD ON NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $this->createQuotationDetailWithTotals([
            'quotation_id' => $q2->id, 'product_id' => $products['tl']->id,
            'description' => $products['tl']->description ?? $products['tl']->code,
            'quantity' => 75, 'subscription_period' => 12,
            'unit_price' => (float) ($products['tl']->unit_price ?? 10),
            'sort_order' => 1,
            'license_start_date' => $sw2At->copy()->addMonth()->startOfMonth()->toDateString(),
            'license_end_date' => $sw2At->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
            'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
        ], $quotationDetailColumns);

        $sw2Data = [
            'lead_id' => $lead->id, 'created_by' => $createdBy,
            'status' => 'New', 'status_handover' => 'Open', 'project_priority' => 'High',
            'company_name' => $companyName, 'headcount' => '75',
            'salesperson' => 'SYSTEM', 'pic_name' => $picName, 'pic_phone' => $phone,
            'ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => null, 'training_type' => 'online_webinar_training',
            'speaker_category' => 'english / malay', 'remarks' => 'EXISTING CUSTOMER ADD-ON LEAVE MODULE',
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false, 'data_migrated' => false,
            'follow_up_counter' => false, 'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode([(string) $q2->id]),
            'software_hardware_pi' => null, 'non_hrdf_pi' => null,
            'proforma_invoice_hrdf' => null, 'hrdf_grant_ids' => null,
            'type_1_pi_invoice_data' => null,
            'hr_account_id' => null, 'hr_company_id' => null, 'hr_user_id' => null,
            'license_type' => 'addon module',
            'submitted_at' => $sw2At, 'completed_at' => null,
            'db_creation' => null, 'kick_off_meeting' => null, 'go_live_date' => null,
        ];
        if (Schema::hasColumn('software_handovers', 'hr_version')) $sw2Data['hr_version'] = 2;
        $sw2 = SoftwareHandover::create(array_intersect_key($sw2Data, $handoverColumns));

        $this->command?->info(
            "Created {$case['name']}"
            . " | Lead: {$lead->id}"
            . " | SW1 (TA): {$sw1->id} PI: {$q1->id}"
            . " | SW2 (TL): {$sw2->id} PI: {$q2->id}"
        );
    }

    /**
     * CASE D: Same lead, 2 handovers, SW2 has 2 PIs
     * SW1: TA + TL (1 PI), SW2: TC (PI-A) + TP (PI-B)
     */
    private function createAddonMultiPiCase(
        $faker, array $products, array $leadColumns, array $companyColumns,
        array $quotationColumns, array $quotationDetailColumns, array $handoverColumns,
        array $licCertColumns, ?int $createdBy, array $case
    ): void {
        $tag = $case['tag'];
        $companyName = Str::upper('TEST ' . $tag . ' ' . $faker->unique()->numerify('####'));
        $picName = Str::upper(Str::limit($faker->name(), 20, ''));
        $email = Str::lower($faker->unique()->safeEmail());
        $phone = substr(preg_replace('/\D+/', '', $faker->phoneNumber()) ?: '60111222333', 0, 20);

        $lead = Lead::create(array_intersect_key([
            'name' => $picName, 'email' => $email, 'phone' => $phone,
            'company_name' => $companyName, 'company_size' => '100-500',
            'country' => 'MALAYSIA', 'products' => ['TIMETEC HR'],
            'lead_code' => 'L' . now()->format('ymd') . $tag,
            'categories' => 'New', 'stage' => 'New', 'lead_status' => 'New',
            'remark' => $case['name'], 'salesperson' => 'SYSTEM',
            'lead_owner' => 'SYSTEM', 'customer_type' => 'END USER', 'region' => 'LOCAL',
        ], $leadColumns));

        CompanyDetail::create(array_intersect_key([
            'lead_id' => $lead->id, 'company_name' => $companyName,
            'industry' => 'MANUFACTURING',
            'company_address1' => Str::upper(Str::limit($faker->streetAddress(), 50, '')),
            'company_address2' => Str::upper(Str::limit($faker->city(), 50, '')),
            'postcode' => (string) $faker->numberBetween(10000, 98000),
            'state' => Str::upper($faker->state()),
            'name' => $picName, 'contact_no' => $phone,
            'position' => 'HR MANAGER', 'email' => $email,
        ], $companyColumns));

        // SW1: TA + TL, 1 PI
        $sw1At = now()->subWeeks(8);
        $q1 = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 50,
            'quotation_date' => $sw1At->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/A/" . $sw1At->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/A",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        foreach (['ta', 'tl'] as $i => $mod) {
            $this->createQuotationDetailWithTotals([
                'quotation_id' => $q1->id, 'product_id' => $products[$mod]->id,
                'description' => $products[$mod]->description ?? $products[$mod]->code,
                'quantity' => 50, 'subscription_period' => 12,
                'unit_price' => (float) ($products[$mod]->unit_price ?? 10),
                'sort_order' => $i + 1,
                'license_start_date' => $sw1At->copy()->addMonth()->startOfMonth()->toDateString(),
                'license_end_date' => $sw1At->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
                'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
            ], $quotationDetailColumns);
        }

        $sw1Data = [
            'lead_id' => $lead->id, 'created_by' => $createdBy,
            'status' => 'New', 'status_handover' => 'Open', 'project_priority' => 'High',
            'company_name' => $companyName, 'headcount' => '50',
            'salesperson' => 'SYSTEM', 'pic_name' => $picName, 'pic_phone' => $phone,
            'ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => null, 'training_type' => 'online_webinar_training',
            'speaker_category' => 'english / malay', 'remarks' => 'NEW SALES TA+TL',
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false, 'data_migrated' => false,
            'follow_up_counter' => false, 'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode([(string) $q1->id]),
            'software_hardware_pi' => null, 'non_hrdf_pi' => null,
            'proforma_invoice_hrdf' => null, 'hrdf_grant_ids' => null,
            'type_1_pi_invoice_data' => null,
            'hr_account_id' => null, 'hr_company_id' => null, 'hr_user_id' => null,
            'license_type' => 'new sales',
            'submitted_at' => $sw1At, 'completed_at' => null,
            'db_creation' => null, 'kick_off_meeting' => null, 'go_live_date' => null,
        ];
        if (Schema::hasColumn('software_handovers', 'hr_version')) $sw1Data['hr_version'] = 2;
        $sw1 = SoftwareHandover::create(array_intersect_key($sw1Data, $handoverColumns));

        // SW2: TC + TP, 2 PIs (TC in PI-A, TP in PI-B)
        $sw2At = now()->subWeeks(2);
        $q2a = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 200,
            'quotation_date' => $sw2At->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/B/" . $sw2At->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/B",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'ADD ON NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $this->createQuotationDetailWithTotals([
            'quotation_id' => $q2a->id, 'product_id' => $products['tc']->id,
            'description' => $products['tc']->description ?? $products['tc']->code,
            'quantity' => 200, 'subscription_period' => 12,
            'unit_price' => (float) ($products['tc']->unit_price ?? 10),
            'sort_order' => 1,
            'license_start_date' => $sw2At->copy()->addMonth()->startOfMonth()->toDateString(),
            'license_end_date' => $sw2At->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
            'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
        ], $quotationDetailColumns);

        $q2b = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 200,
            'quotation_date' => $sw2At->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/C/" . $sw2At->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/C",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'ADD ON NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $this->createQuotationDetailWithTotals([
            'quotation_id' => $q2b->id, 'product_id' => $products['tp']->id,
            'description' => $products['tp']->description ?? $products['tp']->code,
            'quantity' => 200, 'subscription_period' => 12,
            'unit_price' => (float) ($products['tp']->unit_price ?? 10),
            'sort_order' => 1,
            'license_start_date' => $sw2At->copy()->addMonth()->startOfMonth()->toDateString(),
            'license_end_date' => $sw2At->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
            'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
        ], $quotationDetailColumns);

        $sw2Data = [
            'lead_id' => $lead->id, 'created_by' => $createdBy,
            'status' => 'New', 'status_handover' => 'Open', 'project_priority' => 'High',
            'company_name' => $companyName, 'headcount' => '200',
            'salesperson' => 'SYSTEM', 'pic_name' => $picName, 'pic_phone' => $phone,
            'ta' => 0, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => null, 'training_type' => 'online_webinar_training',
            'speaker_category' => 'english / malay', 'remarks' => 'ADDON TC(PI-B) + TP(PI-C)',
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false, 'data_migrated' => false,
            'follow_up_counter' => false, 'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode([(string) $q2a->id, (string) $q2b->id]),
            'software_hardware_pi' => null, 'non_hrdf_pi' => null,
            'proforma_invoice_hrdf' => null, 'hrdf_grant_ids' => null,
            'type_1_pi_invoice_data' => null,
            'hr_account_id' => null, 'hr_company_id' => null, 'hr_user_id' => null,
            'license_type' => 'addon module',
            'submitted_at' => $sw2At, 'completed_at' => null,
            'db_creation' => null, 'kick_off_meeting' => null, 'go_live_date' => null,
        ];
        if (Schema::hasColumn('software_handovers', 'hr_version')) $sw2Data['hr_version'] = 2;
        $sw2 = SoftwareHandover::create(array_intersect_key($sw2Data, $handoverColumns));

        $this->command?->info(
            "Created {$case['name']}"
            . " | Lead: {$lead->id}"
            . " | SW1 (TA+TL): {$sw1->id} PI: {$q1->id}"
            . " | SW2 (TC+TP): {$sw2->id} PIs: {$q2a->id}, {$q2b->id}"
        );
    }

    /**
     * CASE E: Headcount Handover with addon HC products
     * Creates a new lead with:
     *   1. A completed software handover (new sales TA, 50 seats) — so CRM account exists
     *   2. A headcount handover (addon HC TA+TL, 30 seats, 2 years) — pending completion
     */
    private function createHeadcountHandoverCase(
        $faker, array $leadColumns, array $companyColumns,
        array $quotationColumns, array $quotationDetailColumns, ?int $createdBy
    ): void {
        $tag = 'HPI01';
        $handoverColumns = array_flip(Schema::getColumnListing('software_handovers'));
        $companyName = Str::upper('TEST ' . $tag . ' ' . $faker->unique()->numerify('####'));
        $picName = Str::upper(Str::limit($faker->name(), 20, ''));
        $email = Str::lower($faker->unique()->safeEmail());
        $phone = substr(preg_replace('/\D+/', '', $faker->phoneNumber()) ?: '60111222333', 0, 20);

        // Lead & Company
        $lead = Lead::create(array_intersect_key([
            'name' => $picName, 'email' => $email, 'phone' => $phone,
            'company_name' => $companyName, 'company_size' => '25-99',
            'country' => 'MALAYSIA', 'products' => ['TIMETEC HR'],
            'lead_code' => 'L' . now()->format('ymd') . $tag,
            'categories' => 'New', 'stage' => 'New', 'lead_status' => 'New',
            'remark' => 'CASE E - SW + Headcount Handover', 'salesperson' => 'SYSTEM',
            'lead_owner' => 'SYSTEM', 'customer_type' => 'END USER', 'region' => 'LOCAL',
        ], $leadColumns));

        CompanyDetail::create(array_intersect_key([
            'lead_id' => $lead->id, 'company_name' => $companyName,
            'industry' => 'MANUFACTURING',
            'company_address1' => Str::upper(Str::limit($faker->streetAddress(), 50, '')),
            'company_address2' => Str::upper(Str::limit($faker->city(), 50, '')),
            'postcode' => (string) $faker->numberBetween(10000, 98000),
            'state' => Str::upper($faker->state()),
            'name' => $picName, 'contact_no' => $phone,
            'position' => 'HR MANAGER', 'email' => $email,
        ], $companyColumns));

        // ---- Step 1: Software Handover (new sales, TA, completed) ----
        $swProduct = Product::where('code', 'TCL_TA USER-NEW')->first();
        $swAt = now()->subWeeks(4);

        $swQ = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => 50,
            'quotation_date' => $swAt->toDateString(),
            'quotation_reference_no' => "QT/SW/{$tag}/" . $swAt->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/SW",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $this->createQuotationDetailWithTotals([
            'quotation_id' => $swQ->id, 'product_id' => $swProduct->id,
            'description' => $swProduct->description ?? $swProduct->code,
            'quantity' => 50, 'subscription_period' => 12,
            'unit_price' => (float) ($swProduct->unit_price ?? 10),
            'sort_order' => 1,
            'license_start_date' => $swAt->copy()->addMonth()->startOfMonth()->toDateString(),
            'license_end_date' => $swAt->copy()->addMonth()->startOfMonth()->addMonths(12)->subDay()->toDateString(),
            'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
        ], $quotationDetailColumns);

        $swData = [
            'lead_id' => $lead->id, 'created_by' => $createdBy,
            'status' => 'New', 'status_handover' => 'Open', 'project_priority' => 'High',
            'company_name' => $companyName, 'headcount' => '50',
            'salesperson' => 'SYSTEM', 'pic_name' => $picName, 'pic_phone' => $phone,
            'ta' => 1, 'tl' => 0, 'tc' => 0, 'tp' => 0,
            'tapp' => 0, 'thire' => 0, 'tacc' => 0, 'tpbi' => 0,
            'implementer' => 'John Low', 'training_type' => 'online_webinar_training',
            'speaker_category' => 'english / malay', 'remarks' => 'NEW SALES TA ONLY',
            'implementation_pics' => json_encode([[
                'pic_name_impl' => $picName, 'position' => 'HR',
                'pic_phone_impl' => $phone, 'pic_email_impl' => $email,
            ]]),
            'license_activated' => false, 'data_migrated' => false,
            'follow_up_counter' => 0, 'manual_follow_up_count' => 0,
            'follow_up_date' => now()->addDays(7)->toDateString(),
            'proforma_invoice_product' => json_encode([(string) $swQ->id]),
            'software_hardware_pi' => null, 'non_hrdf_pi' => null,
            'proforma_invoice_hrdf' => null, 'hrdf_grant_ids' => null,
            'type_1_pi_invoice_data' => null,
            'hr_account_id' => null, 'hr_company_id' => null, 'hr_user_id' => null,
            'license_type' => 'new sales',
            'submitted_at' => $swAt, 'completed_at' => null,
            'db_creation' => null, 'kick_off_meeting' => null, 'go_live_date' => null,
        ];
        if (Schema::hasColumn('software_handovers', 'hr_version')) $swData['hr_version'] = 2;
        $sw = SoftwareHandover::create(array_intersect_key($swData, $handoverColumns));

        // ---- Step 2: Headcount Handover (addon HC, TA+TL, 30 seats, 2 years) ----
        $addonProducts = [
            'ta_r' => Product::where('code', 'TCL_TA USER-ADDON(R)')->first(),
            'tl_r' => Product::where('code', 'TCL_LEAVE USER-ADDON(R)')->first(),
        ];

        $y1Start = '2026-03-25';
        $y1End = '2027-03-24';
        $y2Start = '2027-03-25';
        $y2End = '2028-03-24';

        $hcQ = Quotation::create(array_intersect_key([
            'lead_id' => $lead->id, 'headcount' => null,
            'quotation_date' => now()->toDateString(),
            'quotation_reference_no' => "QT/HC/{$tag}/" . now()->format('ymdHis'),
            'pi_reference_no' => "PI/{$tag}/HC",
            'quotation_type' => 'product', 'currency' => 'MYR',
            'sales_type' => 'NEW SALES', 'hrdf_status' => 'NON HRDF',
            'subscription_period' => 12, 'status' => 'accepted',
            'tax_rate' => 8, 'mark_as_final' => 1,
        ], $quotationColumns));

        $sort = 0;
        // Year 1: TA(R) + TL(R)
        foreach (['ta_r', 'tl_r'] as $mod) {
            $product = $addonProducts[$mod];
            if (!$product) continue;
            $sort++;
            $desc = str_replace('(START DATE TO EXPIRY DATE)', '(25/3/2026 - 24/3/2027)', $product->description);
            $this->createQuotationDetailWithTotals([
                'quotation_id' => $hcQ->id, 'product_id' => $product->id,
                'description' => $desc,
                'quantity' => 30, 'subscription_period' => 12,
                'unit_price' => (float) ($product->unit_price ?? 2),
                'sort_order' => $sort,
                'license_start_date' => $y1Start, 'license_end_date' => $y1End,
                'year' => 'Year 1', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
            ], $quotationDetailColumns);
        }
        // Year 2: TA(R) + TL(R)
        foreach (['ta_r', 'tl_r'] as $mod) {
            $product = $addonProducts[$mod];
            if (!$product) continue;
            $sort++;
            $desc = str_replace('(START DATE TO EXPIRY DATE)', '(25/3/2027 - 24/3/2028)', $product->description);
            $this->createQuotationDetailWithTotals([
                'quotation_id' => $hcQ->id, 'product_id' => $product->id,
                'description' => $desc,
                'quantity' => 30, 'subscription_period' => 12,
                'unit_price' => (float) ($product->unit_price ?? 2),
                'sort_order' => $sort,
                'license_start_date' => $y2Start, 'license_end_date' => $y2End,
                'year' => 'Year 2', 'tax_code' => 'SV-8', 'tariff_code' => '9907101676', 'convert_pi' => 1,
            ], $quotationDetailColumns);
        }

        // Create headcount handover
        $hc = \App\Models\HeadcountHandover::create([
            'lead_id' => $lead->id,
            'proforma_invoice_product' => [(string) $hcQ->id],
            'product_pi_invoice_data' => [[
                'quotation_id' => $hcQ->id,
                'pi_number' => "PI/{$tag}/HC",
                'company_name' => $companyName,
                'invoice_number' => null,
            ]],
            'status' => 'New',
            'submitted_at' => now(),
            'created_by' => $createdBy,
            'salesperson_remark' => 'ADDON HC TEST: TA+TL, 30 seats, 2 years',
        ]);

        $this->command?->info(
            "Created CASE E - SW + Headcount Handover"
            . " | Lead: {$lead->id}"
            . " | SW: {$sw->id} PI: {$swQ->id} (new sales TA, 50 seats — complete this first)"
            . " | HC: {$hc->id} PI: {$hcQ->id} (addon TA+TL, 30 seats, 2 years)"
        );
    }

    /**
     * Create a QuotationDetail with proper total calculations.
     * Formula: total_before_tax = quantity × subscription_period × unit_price
     */
    private function createQuotationDetailWithTotals(array $data, array $quotationDetailColumns, float $taxRate = 8.0): QuotationDetail
    {
        $quantity = (int) ($data['quantity'] ?? 0);
        $subscriptionPeriod = (int) ($data['subscription_period'] ?? 12);
        $unitPrice = (float) ($data['unit_price'] ?? 0);

        $totalBeforeTax = round($quantity * $subscriptionPeriod * $unitPrice, 2);
        $taxation = round($totalBeforeTax * ($taxRate / 100), 2);
        $totalAfterTax = round($totalBeforeTax + $taxation, 2);

        $data['total_before_tax'] = $totalBeforeTax;
        $data['taxation'] = $taxation;
        $data['total_after_tax'] = $totalAfterTax;

        return QuotationDetail::create(array_intersect_key($data, $quotationDetailColumns));
    }

    private function resolveProducts(array $productColumns, $faker): array
    {
        $definitions = [
            'ta' => 'TCL_TA USER-NEW',
            'tl' => 'TCL_LEAVE USER-NEW',
            'tc' => 'TCL_CLAIM USER-NEW',
            'tp' => 'TCL_PAYROLL USER-NEW',
        ];

        $resolved = [];

        foreach ($definitions as $key => $code) {
            $product = Product::query()->where('code', $code)->first();
            if (!$product) {
                $product = Product::create(array_intersect_key([
                    'code' => $code,
                    'description' => $code,
                    'solution' => 'software',
                    'unit_price' => $faker->numberBetween(8, 30),
                    'subscription_period' => 12,
                    'package_group' => json_encode(['Subscription Package']),
                    'taxable' => true,
                    'editable' => true,
                    'is_active' => true,
                    'sort_order' => 1,
                    'convert_pi' => true,
                ], $productColumns));
            }
            $resolved[$key] = $product;
        }

        return $resolved;
    }
}
