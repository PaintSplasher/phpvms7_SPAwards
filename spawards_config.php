<?php
/**
 * SPAwards Configuration
 * ----------------------
 * Central configuration file for all API integrations used by the SPAwards.
 * 
 * It contains credentials and IDs for Discord and IVAO API integrations.
 * 
 * SECURITY NOTE:
 * This file contains sensitive information such as API keys and bot tokens.
 * Never publish or commit this file to a public repository.
 */

return [

    // DISCORD SETTINGS:
    'discord' => [
        // Your Discord server Guild ID | Right-click on your Discord server, copy Server-ID
        'guild_id' => 'YOUR_DISCORD_GUILD_ID',

        // Your Discord Bot Token | https://discord.com/developers/applications
        'bot_token' => 'YOUR_DISCORD_BOT_TOKEN_HERE',
    ],

    // IVAO SETTINGS:
    'ivao' => [
        // Your IVAO API key | https://developers.ivao.aero/dashboard
        'api_key' => 'YOUR_IVAO_API_KEY_HERE',
    ],

    // CUSTOM FIELD NAME SETTINGS:
    'customfields' => [
        // VATSIM ID
        'vatsim_id_field' => 'YOUR_VATSIM_ID_FIELD_NAME_HERE',

        // IVAO ID
        'ivao_id_field' => 'YOUR_IVAO_ID_FIELD_NAME_HERE',

        // DISCORD ID
        'discord_id_field' => 'YOUR_DISCORD_ID_FIELD_NAME_HERE',
    ],

];
