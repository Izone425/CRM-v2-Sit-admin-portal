<?php

namespace App\Console\Commands;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class UpdatePublicHoliday extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'publicholiday:update {year}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Public Holiday From TimeTec HR API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        PublicHoliday::truncate();

        $year = (int) $this->argument('year');

        try {
            // Get authentication token
            $authResponse = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post('https://hr-api.timeteccloud.com/api/auth-mobile/token', [
                'username' => 'hr@timeteccloud.com',
                'password' => 'Abc123456'
            ]);

            if (!$authResponse->successful()) {
                $this->error('Authentication failed: ' . $authResponse->body());
                return 1;
            }

            $authData = $authResponse->json();
            $token = $authData['accessToken'] ?? null;

            if (!$token) {
                $this->error('Token not found in auth response');
                return 1;
            }

            $totalHolidayCount = 0;

            for ($month = 1; $month <= 12; $month++) {
                $holidayResponse = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])->post('https://hr-api.timeteccloud.com/api/v1/dashboard/holiday/employee', [
                    'month' => $month,
                    'year' => $year,
                ]);

                if (!$holidayResponse->successful()) {
                    $this->warn("Holiday API failed for month {$month}: " . $holidayResponse->body());
                    continue;
                }

                $holidays = $holidayResponse->json();

                if (is_array($holidays)) {
                    foreach ($holidays as $day) {
                        $dayHolidays = $day['holiday'] ?? [];
                        foreach ($dayHolidays as $holiday) {
                            $date = $holiday['date'] ?? null;
                            $name = $holiday['eventName'] ?? null;

                            if ($date && $name) {
                                PublicHoliday::create([
                                    'day_of_week' => Carbon::parse($date)->dayOfWeekIso,
                                    'date' => $date,
                                    'name' => $name,
                                ]);
                                $totalHolidayCount++;
                            }
                        }
                    }
                }

                $this->info("Month {$month}: done");
            }

            $this->info("Successfully updated {$totalHolidayCount} public holidays for year {$year}");
            return 0;

        } catch (\Exception $e) {
            $this->error('Error updating public holidays: ' . $e->getMessage());
            return 1;
        }
    }
}
