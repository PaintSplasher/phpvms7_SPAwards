<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserField;
use App\Models\UserFieldValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SPAwardsIvao extends Award
{
    public $name = 'SPAwards(IVAO)';
    public $param_description = 'Amount of flight time in minutes at which to give this award';

    public function check($flight_minutes = null): bool
    {
        // Ensure the flight_minutes parameter is provided and numeric
        if (is_null($flight_minutes) || !is_numeric($flight_minutes)) {
            Log::error('SPAwards(IVAO) | Invalid or missing flight_minutes parameter.');
            return false;
        }

        // Retrieve the UserField ID for "IVAO ID"
        $ivao_field_id = optional(
            UserField::select('id')->where('name', $config['customfields']['ivao_id_field'])->first()
        )->id;

        // If the IVAO field doesn't exist, stop here
        if (!$ivao_field_id) {
            Log::error('SPAwards(IVAO) | UserField "IVAO ID" not found.');
            return false;
        }

        // Retrieve the user's IVAO ID
        $ivao_id = UserFieldValue::where('user_field_id', $ivao_field_id)
            ->where('user_id', $this->user->id)
            ->value('value');

        // Ensure that the user has an IVAO ID set
        if (empty($ivao_id)) {
            Log::error("SPAwards(IVAO) | Pilot {$this->user->id} has no IVAO ID set.");
            return false;
        }

        try {
            // Send a request to the IVAO API
            $config = include base_path('modules/Awards/spawards_config.php');

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'apiKey' => $config['ivao']['api_key'],
            ])->get("https://api.ivao.aero/v2/users/{$ivao_id}");

            // Validate API response
            if ($response->failed()) {
                Log::error("SPAwards(IVAO) | IVAO API request failed for VID {$ivao_id}.");
                return false;
            }

            $data = $response->json();

            // Find the entry that contains pilot hours
            $pilotHoursEntry = collect($data['hours'] ?? [])->firstWhere('type', 'pilot');

            // Ensure pilot hours exist in the response
            if (!$pilotHoursEntry || !isset($pilotHoursEntry['hours'])) {
                Log::error("SPAwards(IVAO) | No pilot hours found for IVAO ID {$ivao_id}.");
                return false;
            }

            // Convert IVAO time (seconds) to minutes
            $ivaoSeconds = (float) $pilotHoursEntry['hours'];
            $ivaoMinutes = round($ivaoSeconds / 60, 1);

            // Log for debugging
            Log::info("SPAwards(IVAO) | Pilot (ID: {$this->user->id}) has {$ivaoMinutes} minutes on IVAO, {$flight_minutes} needed.");

            // Check if the pilot meets or exceeds the required minutes
            return $ivaoMinutes >= (int) $flight_minutes;

        } catch (\Throwable $e) {
            // Handle unexpected errors (network, JSON parsing, etc.)
            Log::error("SPAwards(IVAO) | Exception: {$e->getMessage()}");
            return false;
        }
    }
}
