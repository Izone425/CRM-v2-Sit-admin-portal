<?php

namespace App\Services;

class TemplateSelector
{
    protected array $templates = [
        // Bahasa Melayu Campaigns
        '22374404055' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        '120213654055070392' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        '120220143815230392' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HXcc05134b6c74ecc02682a25887978630'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HXbb1b933e2fa363c64c996ae0da7c8773'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HX8094ffaa4380226a4c803c10ea59655e'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX4d2db45f7de1fd07563369d87a0c8c75'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        // Default (English)
        'default' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st', 'sid' => 'HX5c9b745783710d7915fedc4e7e503da0'],
            2 => ['email' => 'emails.email_blasting_2nd', 'sid' => 'HX6531d9c843b71e0a45accd0ce2cfe5f2'],
            3 => ['email' => 'emails.email_blasting_3rd', 'sid' => 'HXcccb50b8124d29d7d21af628b92522d4'],
            4 => ['email' => 'emails.email_blasting_4th', 'sid' => 'HX517e06b8e7ddabea51aa799bfd1987f8'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
    ];

    protected array $leadSourceTemplates = [
        // Chinese templates
        'CN' => [
            0 => ['email' => 'emails.demo_notification_cn', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st_cn', 'sid' => 'HXbd3b09adc6ec254a63b9456984945357'],
            2 => ['email' => 'emails.email_blasting_2nd_cn', 'sid' => 'HX3e98ef9c87b7b95ecab108dd5fefa299'],
            3 => ['email' => 'emails.email_blasting_3rd_cn', 'sid' => 'HX56b6870ea3e16d538bccca337fa7ac84'],
            4 => ['email' => 'emails.email_blasting_4th_cn', 'sid' => 'HXf0bfe0b10f2816c62edd73cf2ff017b5'],
            5 => ['email' => 'emails.cancel_demo_notification_cn', 'sid' => null],
        ],
        // Cold Call templates
        'Cold Call' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_1st_cc', 'sid' => 'HX231bc82ce1d546549846e4217cd4d673', 'subject' => 'HR Cloud Suite used by 1,500+ organizations in Malaysia'],
            2 => ['email' => 'emails.email_blasting_2nd_cc', 'sid' => 'HX231bc82ce1d546549846e4217cd4d673', 'subject' => 'HR Cloud Suite used by 1,500+ organizations in Malaysia'],
            3 => ['email' => 'emails.email_blasting_3rd_cc', 'sid' => 'HX231bc82ce1d546549846e4217cd4d673', 'subject' => 'Follow-Up on HR Cloud Suite used by 1,500+ organizations in Malaysia'],
            4 => ['email' => 'emails.email_blasting_4th_cc', 'sid' => 'HX231bc82ce1d546549846e4217cd4d673', 'subject' => 'Final Follow-Up on HR Cloud Suite used by 1,500+ organizations in Malaysia'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        // HR Checklist templates (shared body, distinct subjects)
        'HR Checklist' => [
            0 => ['email' => 'emails.demo_notification', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_hr_checklist', 'sid' => 'HX17c9ae7a8a6082f50ff396604a5abe08', 'subject' => 'Still Looking to Simplify Your HR Processes?'],
            2 => ['email' => 'emails.email_blasting_hr_checklist', 'sid' => 'HX17c9ae7a8a6082f50ff396604a5abe08', 'subject' => 'Still Looking to Simplify Your HR Processes?'],
            3 => ['email' => 'emails.email_blasting_hr_checklist', 'sid' => 'HX17c9ae7a8a6082f50ff396604a5abe08', 'subject' => 'Follow-Up: Still Looking to Simplify Your HR Processes?'],
            4 => ['email' => 'emails.email_blasting_hr_checklist', 'sid' => 'HX17c9ae7a8a6082f50ff396604a5abe08', 'subject' => 'Follow-Up: Still Looking to Simplify Your HR Processes?'],
            5 => ['email' => 'emails.cancel_demo_notification', 'sid' => null],
        ],
        // HR Checklist (CN) templates (shared body, distinct subjects)
        'HR Checklist (CN)' => [
            0 => ['email' => 'emails.demo_notification_cn', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_hr_checklist_cn', 'sid' => 'HXf19c6c2a43322d66970c964487cabeba', 'subject' => '还在寻找简化 HR 流程的方法吗？'],
            2 => ['email' => 'emails.email_blasting_hr_checklist_cn', 'sid' => 'HXf19c6c2a43322d66970c964487cabeba', 'subject' => '还在寻找简化 HR 流程的方法吗？'],
            3 => ['email' => 'emails.email_blasting_hr_checklist_cn', 'sid' => 'HXf19c6c2a43322d66970c964487cabeba', 'subject' => 'Follow Up：还在寻找简化 HR 流程的方法吗？'],
            4 => ['email' => 'emails.email_blasting_hr_checklist_cn', 'sid' => 'HXf19c6c2a43322d66970c964487cabeba', 'subject' => '最后Follow Up：还在寻找简化 HR 流程的方法吗？'],
            5 => ['email' => 'emails.cancel_demo_notification_cn', 'sid' => null],
        ],
        // HR Checklist (BM) templates (shared body, distinct subjects)
        'HR Checklist (BM)' => [
            0 => ['email' => 'emails.demo_notification_bm', 'sid' => null],
            1 => ['email' => 'emails.email_blasting_hr_checklist_bm', 'sid' => 'HXb987196d083faf2fa8dfad28f9e56163', 'subject' => 'Masih Buntu Cara Memudahkan Proses HR Anda?'],
            2 => ['email' => 'emails.email_blasting_hr_checklist_bm', 'sid' => 'HXb987196d083faf2fa8dfad28f9e56163', 'subject' => 'Masih Mencari Cara Untuk Memudahkan Proses HR Anda?'],
            3 => ['email' => 'emails.email_blasting_hr_checklist_bm', 'sid' => 'HXb987196d083faf2fa8dfad28f9e56163', 'subject' => 'Susulan: Masih Perlukan Bantuan Untuk Pengurusan HR?'],
            4 => ['email' => 'emails.email_blasting_hr_checklist_bm', 'sid' => 'HXb987196d083faf2fa8dfad28f9e56163', 'subject' => 'Susulan Terakhir: Ingin Permudahkan Proses HR Anda?'],
            5 => ['email' => 'emails.cancel_demo_notification_bm', 'sid' => null],
        ],
    ];

    public function getTemplate(?string $utmCampaign, int $followUpCount): array
    {
        $campaignKey = array_key_exists($utmCampaign, $this->templates) ? $utmCampaign : 'default';
        return $this->templates[$campaignKey][$followUpCount] ?? $this->templates['default'][1];
    }

    public function getTemplateByLeadSource(?string $leadSource, int $followUpCount): array
    {
        if ($leadSource && isset($this->leadSourceTemplates[$leadSource]) && isset($this->leadSourceTemplates[$leadSource][$followUpCount])) {
            return $this->leadSourceTemplates[$leadSource][$followUpCount];
        }

        // Fall back to default template if lead source template doesn't exist
        return $this->templates['default'][$followUpCount] ?? $this->templates['default'][1];
    }
}
