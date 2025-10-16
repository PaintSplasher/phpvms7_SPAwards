<?php

namespace Modules\Awards\Awards;

use App\Contracts\Award;
use App\Models\UserField;
use App\Models\UserFieldValue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SPAwardsDiscord extends Award
{
    public $name = 'SPAwards(Discord)';
    public $param_description = 'Award given to pilots verified as members of the VA Discord server (Optional: Enter Role-ID a Pilot needs to be verified)';

    public function check($requiredRole = null): bool
    {
        // Load configuration
        $configPath = base_path('modules/Awards/spawards_config.php');
        if (!file_exists($configPath)) {
            Log::error('SPAwards(Discord) | Missing configuration file: modules/Awards/spawards_config.php');
            return false;
        }

        $config = include $configPath;

        $guildId  = $config['discord']['guild_id'] ?? null;
        $botToken = $config['discord']['bot_token'] ?? null;

        if (empty($guildId) || empty($botToken)) {
            Log::error('SPAwards(Discord) | Missing Discord configuration (GUILD_ID or BOT_TOKEN).');
            return false;
        }

        // Retrieve pilot's Discord ID from the custom field
        $discordFieldId = optional(
            UserField::select('id')->where('name', $config['customfields']['discord_id_field'])->first()
        )->id;

        if (!$discordFieldId) {
            Log::error('SPAwards(Discord) | UserField "Discord ID" not found.');
            return false;
        }

        $discordId = UserFieldValue::where('user_field_id', $discordFieldId)
            ->where('user_id', $this->user->id)
            ->value('value');

        if (empty($discordId)) {
            Log::info("SPAwards(Discord) | Pilot (ID: {$this->user->id}) has no Discord ID set.");
            return false;
        }

        // Query Discord API to verify guild membership
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bot {$botToken}",
                'Accept' => 'application/json',
            ])->get("https://discord.com/api/v10/guilds/{$guildId}/members/{$discordId}");

            if ($response->status() === 404) {
                Log::info("SPAwards(Discord) | Pilot (ID: {$this->user->id}) {$discordId} not found in Discord guild {$guildId}.");
                return false;
            }

            if ($response->failed()) {
                Log::error("SPAwards(Discord) | Discord API request failed ({$response->status()}) for user {$discordId}.");
                return false;
            }

            $member = $response->json();

            // If a Role-ID was specified in the Award parameters, check for it
            if (!empty($requiredRole)) {
                if (!in_array($requiredRole, $member['roles'] ?? [])) {
                    Log::info("SPAwards(Discord) | Pilot (ID: {$this->user->id}) {$discordId} found but missing role {$requiredRole}.");
                    return false;
                }
                // Log for debugging
                Log::info("SPAwards(Discord) | Pilot (ID: {$this->user->id}) {$discordId} verified successfully with required role {$requiredRole}.");
            } else {
                // Log for debugging
                Log::info("SPAwards(Discord) | Pilot (ID: {$this->user->id}) {$discordId} verified successfully (role check not required).");
            }

            return true;

        } catch (\Throwable $e) {
            Log::error("SPAwards(Discord) | Exception: " . $e->getMessage());
            return false;
        }
    }
}
