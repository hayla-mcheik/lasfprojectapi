<?php

namespace App\Console\Commands;

use App\Models\ClearanceStatus;
use App\Models\ClearanceStatusHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeClearanceStatuses extends Command
{
    protected $signature = 'clearances:normalize';

    protected $description =
        'Normalize clearance statuses to one effective record per location and date';

    public function handle(): int
    {
        $this->info('Checking duplicate clearance statuses...');

        $duplicates = ClearanceStatus::query()
            ->select(
                'flying_location_id',
                'permission_date',
                DB::raw('COUNT(*) AS total')
            )
            ->whereNotNull('permission_date')
            ->groupBy(
                'flying_location_id',
                'permission_date'
            )
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isEmpty()) {

            $this->info(
                'No duplicate clearance statuses found.'
            );

            return self::SUCCESS;
        }

        foreach ($duplicates as $duplicate) {

            DB::transaction(function () use ($duplicate) {

                /*
                |--------------------------------------------------------------------------
                | Get Records
                |--------------------------------------------------------------------------
                |
                | Newest record becomes effective.
                |
                */

                $statuses = ClearanceStatus::query()
                    ->where(
                        'flying_location_id',
                        $duplicate->flying_location_id
                    )
                    ->whereDate(
                        'permission_date',
                        $duplicate->permission_date
                    )
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();

                $effective = $statuses->shift();

                /*
                |--------------------------------------------------------------------------
                | Move Old Records Into History
                |--------------------------------------------------------------------------
                */

                foreach ($statuses as $old) {

                    ClearanceStatusHistory::create([

                        'clearance_status_id' =>
                            $effective->id,

                        'flying_location_id' =>
                            $old->flying_location_id,

                        'permission_date' =>
                            $old->permission_date,

                        'old_status' =>
                            $old->status,

                        'old_reason' =>
                            $old->reason,

                        'new_status' =>
                            $effective->status,

                        'new_reason' =>
                            $effective->reason,

                        'changed_by' =>
                            $effective->updated_by
                            ?? $old->updated_by,

                        'action' =>
                            'updated',
                    ]);

                    $old->delete();
                }
            });
        }

        $this->info(
            'Clearance statuses normalized successfully.'
        );

        return self::SUCCESS;
    }
}