<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\Enums\PirepState;
use Illuminate\Support\Facades\Log;

class SPAwardsLandingrate extends Award
{
    public $name = 'SPAwards(Landingrate)';
    
    public $param_description = 'The landing rate range at which to give this award, e.g. "-145:-155"';

    public function check($lrate = null): bool
    {
        // Ensure that a valid parameter is provided
        if (is_null($lrate)) {
            Log::error('SPAwards(Landingrate) | No parameter set.');
            return false;
        }

        try {
            // Split the provided range (e.g., "-145:-155") into two values
            [$lrateFrom, $lrateTo] = explode(':', $lrate);
        } catch (\Throwable $e) {
            // Catch any parsing or format errors
            Log::error("SPAwards(Landingrate) | Invalid landing rate format: '{$lrate}'");
            return false;
        }

        // Convert both values to floats for comparison
        $lrateFrom = (float) $lrateFrom;
        $lrateTo = (float) $lrateTo;

        // Ensure the range is in correct order (smaller first)
        if ($lrateFrom > $lrateTo) {
            [$lrateFrom, $lrateTo] = [$lrateTo, $lrateFrom];
        }

        // Ensure the user has a valid last PIREP
        if (empty($this->user->last_pirep)) {
            Log::warning("SPAwards(Landingrate) | User {$this->user->id} has no last PIREP.");
            return false;
        }

        // Retrieve the landing rate from the user's last PIREP
        $landingRate = (float) ($this->user->last_pirep->landing_rate ?? 0);

        // Log for debugging
        Log::info("SPAwards(Landingrate) | Pilot (ID: {$this->user->id}) has {$landingRate} fpm, between {$lrateFrom} to {$lrateTo} fpm needed.");

        // Check if the landing rate falls within the defined range
        return $landingRate >= $lrateFrom && $landingRate <= $lrateTo;
    }
}