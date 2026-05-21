<?php

namespace App\Console\Commands;

use App\Services\AutoCountInvoiceService;
use Illuminate\Console\Command;

class GenerateAutoCountApiPassword extends Command
{
    protected $signature = 'autocount:generate-password';

    protected $description = 'Generate the AutoCount API password for today';

    public function handle()
    {
        $service = new AutoCountInvoiceService();
        $result = $service->testEncryption();

        $this->info('Date Key:    ' . $result['original']);
        $this->info('Encrypted:   ' . $result['encrypted']);
        $this->info('Decrypted:   ' . $result['decrypted']);
        $this->info('Valid:       ' . ($result['matches'] ? 'Yes' : 'No'));

        return Command::SUCCESS;
    }
}
