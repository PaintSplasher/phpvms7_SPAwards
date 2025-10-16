<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserAward;
use App\Models\UserField;
use App\Models\UserFieldValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SPAwardsVatsim extends Award
{
    public $name = 'SPAwards(VATSIM)';

    public $param_description = 'Amount of flight time in minutes at which to give this award';

    public function check($flight_minutes = null): bool
    {
        // Load configuration
        $configPath = base_path('modules/Awards/spawards_config.php');
        if (!file_exists($configPath)) {
            Log::error('SPAwards(VATSIM) | Missing configuration file: modules/Awards/spawards_config.php');
            return false;
        }

        $config = include $configPath;

        $vatsimId  = $config['customfields']['vatsim_id_field'] ?? null;

        if (empty($vatsimId)) {
            Log::error('SPAwards(VATSIM) | Missing VATSIM configuration (VATSIM ID).');
            return false;
        }
        
        // Ensure the flight_minutes parameter is provided and valid
        if (is_null($flight_minutes) || !is_numeric($flight_minutes)) {
            Log::error('SPAwards(VATSIM) | Invalid or missing flight_minutes parameter.');
            return false;
        }

        // Check if the award is already granted
        $award = \App\Models\Award::where('ref_model', get_class($this))
            ->where('ref_model_params', (string) $flight_minutes)
            ->first();

        if (!$award) {
            Log::error("SPAwards(VATSIM) | No matching award found.");
            return false;
        }

        $alreadyGranted = UserAward::where('user_id', $this->user->id)
            ->where('award_id', $award->id)
            ->exists();

        if ($alreadyGranted) {
            Log::info("SPAwards(VATSIM) | Award already granted to Pilot (ID: {$this->user->id}). Skipping...");
            return false;
        }

        // Retrieve the UserField ID for "VATSIM ID"
        $vatsim_field_id = optional(
            UserField::select('id')->where('name', $config['customfields']['vatsim_id_field'])->first()
        )->id;

        // If the VATSIM field is missing, stop execution
        if (!$vatsim_field_id) {
            Log::error('SPAwards(VATSIM) | UserField "VATSIM ID" not found.');
            return false;
        }

        // Retrieve the user's VATSIM ID
        $vatsim_id = UserFieldValue::where('user_field_id', $vatsim_field_id)
            ->where('user_id', $this->user->id)
            ->value('value');

        // Ensure that the VATSIM ID is set for this user
        if (empty($vatsim_id)) {
            Log::error("SPAwards(VATSIM) | Pilot {$this->user->id} has no VATSIM ID set.");
            return false;
        }

        try {
            // Fetch pilot stats from VATSIM API
            $response = Http::acceptJson()->get("https://api.vatsim.net/v2/members/{$vatsim_id}/stats");

            // Check if the request failed
            if ($response->failed()) {
                Log::error("SPAwards(VATSIM) | Failed to fetch VATSIM stats for ID {$vatsim_id}.");
                return false;
            }

            $data = $response->json();

            // Ensure that the API returned a valid pilot field
            if (!isset($data['pilot'])) {
                Log::error("SPAwards(VATSIM) | No pilot field returned for VATSIM ID {$vatsim_id}.");
                return false;
            }

            // Convert flight hours (VATSIM reports hours) to minutes
            $vatsim_minutes = (float) $data['pilot'] * 60;

            // Log for debugging
            Log::info("SPAwards(VATSIM) | Pilot (ID: {$this->user->id}) has {$vatsim_minutes} minutes on VATSIM, {$flight_minutes} needed.");

            // Check if the pilot meets or exceeds the required VATSIM minutes
            return $vatsim_minutes >= (int) $flight_minutes;

        } catch (\Throwable $e) {
            // Catch any unexpected errors (network, JSON, etc.)
            Log::error("SPAwards(VATSIM) | Exception: {$e->getMessage()}");
            return false;
        }
    }
}
