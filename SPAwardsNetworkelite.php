<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserField;
use App\Models\UserFieldValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SPAwardsNetworkelite extends Award
{
    public $name = 'SPAwards(Networkelite)';
    
    public $param_description = 'The total combined flight time in minutes across both VATSIM and IVAO networks';

    public function check($totalMinutes = null): bool
    {
        // Ensure parameter is provided and valid
        if (is_null($totalMinutes) || !is_numeric($totalMinutes)) {
            Log::error('SPAwards(Networkelite) | Invalid or missing parameter.');
            return false;
        }

        $requiredMinutes = (float) $totalMinutes;
        $totalNetworkMinutes = 0;

        // VATSIM TIME FETCH
        try {
            $vatsimFieldId = optional(
                UserField::select('id')->where('name', 'VATSIM ID')->first()
            )->id;

            if ($vatsimFieldId) {
                $vatsimId = UserFieldValue::where('user_field_id', $vatsimFieldId)
                    ->where('user_id', $this->user->id)
                    ->value('value');

                if (!empty($vatsimId)) {
                    $response = Http::acceptJson()->get("https://api.vatsim.net/v2/members/{$vatsimId}/stats");
                    if ($response->ok()) {
                        $data = $response->json();
                        if (isset($data['pilot'])) {
                            // Convert VATSIM hours to minutes
                            $vatsimMinutes = (float) $data['pilot'] * 60;
                            $totalNetworkMinutes += $vatsimMinutes;
                            Log::info("SPAwards(Networkelite) | VATSIM: {$vatsimMinutes} minutes for Pilot (ID: {$this->user->id}).");
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("SPAwards(Networkelite) | Error fetching VATSIM data: {$e->getMessage()}");
        }

        // IVAO TIME FETCH
        try {
            $ivaoFieldId = optional(
                UserField::select('id')->where('name', 'IVAO ID')->first()
            )->id;

            if ($ivaoFieldId) {
                $ivaoId = UserFieldValue::where('user_field_id', $ivaoFieldId)
                    ->where('user_id', $this->user->id)
                    ->value('value');

                if (!empty($ivaoId)) {
                    $response = Http::withHeaders([
                        'accept' => 'application/json',
                        'apiKey' => 'AMQ2OSZXZLG92KBMXGUAV30H1UVEJJKY',
                    ])->get("https://api.ivao.aero/v2/users/{$ivaoId}");

                    if ($response->ok()) {
                        $data = $response->json();
                        $pilotEntry = collect($data['hours'] ?? [])->firstWhere('type', 'pilot');
                        if ($pilotEntry && isset($pilotEntry['hours'])) {
                            // Convert IVAO seconds to minutes
                            $ivaoMinutes = round(((float) $pilotEntry['hours']) / 60, 1);
                            $totalNetworkMinutes += $ivaoMinutes;
                            Log::info("SPAwards(Networkelite) | IVAO: {$ivaoMinutes} minutes for Pilot (ID: {$this->user->id}).");
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error("SPAwards(Networkelite) | Error fetching IVAO data: {$e->getMessage()}");
        }

        // Log for debugging
        Log::info("SPAwards(Networkelite) | Pilot (ID: {$this->user->id}) total combined minutes: {$totalNetworkMinutes}, required: {$requiredMinutes}.");

        // Return true if the total meets or exceeds the requirement
        return $totalNetworkMinutes >= $requiredMinutes;
    }
}
