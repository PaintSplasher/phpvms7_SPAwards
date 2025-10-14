# SPAwards | Award parameters can be configured directly through the phpVMS admin panel

* Upload spawards_config.php to modules/Awards/spawards_config.php
* Upload all other SPAwards files to modules/Awards/Awards
* Every Award does an entry in your laravel log for debugging
* Some awards, such as Discord or Ivao, require API credentials to be set in: modules/Awards/spawards_config.php

## Below is a list of all available award modules included in SPAwards. Each award can be configured individually through the phpVMS admin panel with specific parameters (for example: distance, time, count, or thresholds).

01. Aircraft
Rewards pilots who have completed a set number of flights using aircraft from the same family (for example, A320 or B737 series).

02. Airline
Given to pilots who have flown a specific number of flights with one airline, showing loyalty to that carrier.

03. Cargo
Awarded to pilots who have transported a significant amount of cargo over their career, showcasing their freight experience.

04. Consecutive
Recognizes pilots who have filed flights on a series of consecutive days without interruption.

05. Discord
Grants the award to pilots verified as active members of the VAs official Discord server.

06. Distance
Awarded to pilots who have flown a total cumulative distance exceeding a defined number of nautical miles.

07. Explorer
Given to pilots who have operated flights across multiple ICAO regions, proving their global flying experience.

08. Fleet
Rewards pilots who have flown with a wide range of aircraft types in the VAs fleet.

09. Ivao
Awarded to pilots with a minimum number of flight minutes logged on the IVAO network.

10. Vatsim
Awarded to pilots with a minimum number of flight minutes logged on the VATSIM network.

11. Networkelite
Given to pilots who are active on multiple online networks such as IVAO and VATSIM.

12. Landingrate
Rewards pilots who achieve a landing rate within a defined precision range (for example, between -145 and -155 fpm).

13. Longhaul
Awarded to pilots who have completed several long-haul flights exceeding a specific minimum distance.

14. Loyalty
Recognizes pilots who primarily fly from or to the same base hub, demonstrating hub loyalty.

15. Membership
Given to pilots who have reached a defined number of membership years since joining the VA.

16. Money
Awarded to pilots who have earned a certain amount of virtual money through flight operations.

17. Nightowl
Rewards pilots who have performed multiple night flights, landing between 22:00 and 06:00 UTC.

18. Passenger
Awarded to pilots who have transported a high total number of passengers across all accepted flights.

19. Performer
Recognizes pilots with a high number of accepted flights and consistent flight activity.

20. RouteCode
Given to pilots who have completed a flight using a specific route code defined by the VA.

21. ShortHaul
Awarded to pilots who have completed a set number of short-haul flights below a specified distance.

22. Streak
Rewards pilots who have achieved a continuous streak of successful (accepted) flights without rejection.

23. Weekend
Given to pilots who frequently fly during weekends, showing regular weekend activity.

24. Earlybird
Awarded to pilots who fly multiple early morning flights between 04:00 and 08:00 UTC.

25. Fuelburner
Rewards pilots who have performed at least one flight consuming more than a specified amount of fuel (for example, over 60,000 kg).

## Debug examples:

* SPAwards(RouteCode)RouteCode) | Pilot (ID: 1) has flown PF, ABC needed.  
* SPAwards(RouteCode)Distance) | Pilot (ID: 1) has 255 days, 31 days needed.  
* SPAwards(RouteCode)VATSIM) | Pilot (ID: 1) has 17781.6 minutes on VATSIM, 120 needed.  
* SPAwards(RouteCode)IVAO) | Pilot (ID: 1) has 875.1 minutes on IVAO, 120 needed.  
* SPAwards(RouteCode)Money) | Pilot (ID: 1) has 189.69, 150 needed.  
* SPAwards(RouteCode)Landingrate) | Pilot (ID: 1) has 0 fpm, between -155 to -145 fpm needed.  
* SPAwards(RouteCode)Airline) | Pilot (ID: 1) has 4 flights with DVA, 3 needed.  
* SPAwards(RouteCode)Distance) | Pilot (ID: 1) has flown 2583.74 nm, 2500 nm needed.  
* SPAwards(RouteCode)Aircraft) | Pilot (ID: 1) has 2 flights with aircraft AT75, 2 needed.  
* SPAwards(RouteCode)Streak) | Pilot (ID: 1) streak check: FAILED  
* SPAwards(RouteCode)Shorthaul) | Pilot (ID: 1) has 1 short-haul flights < 250 nm, 10 required.  
* SPAwards(RouteCode)Longhaul) | Pilot (ID: 1) has 0 flights > 4200 nm, 10 required.  
* SPAwards(RouteCode)Consecutive) | Pilot (ID: 1) 2 active days, 5 required.  
* SPAwards(RouteCode)Loyalty) | Pilot (ID: 1) has 3 flights from/to EFHK, 2 required.  
* SPAwards(RouteCode)Explorer) | Pilot (ID: 1) has 3 flights in region EF, 2 required.  
* SPAwards(RouteCode)Fleet) | Pilot (ID: 1) has flown 3 unique aircraft types, 2 required.  
* SPAwards(RouteCode)Performer) | Pilot (ID: 1) average score: 89.0000,  80 required.  
* SPAwards(RouteCode)Networkelite) | VATSIM: 17781.6 minutes for Pilot (ID: 1).  
* SPAwards(RouteCode)Networkelite) | IVAO: 875.1 minutes for Pilot (ID: 1).  
* SPAwards(RouteCode)Networkelite) | Pilot (ID: 1) total combined minutes: 18656.7, required: 3000.  
* SPAwards(RouteCode)Nightler) | Pilot (ID: 1) has 0 night landings, 3 required.  
* SPAwards(RouteCode)Cargo) | Pilot (ID: 1) carried 786 cargo units with DVA/AT75, 500000 required.  
* SPAwards(RouteCode)Passenger) | Pilot (ID: 1) carried 110 pax with DVA/A319, 100 required.  
* SPAwards(RouteCode)Weekend) | Pilot (ID: 1) has 0 weekend flights, 5 required.  
* SPAwards(RouteCode)Discord) | Pilot (ID: 1) 000000000000000 verified successfully with required role 000000000000000.  
* SPAwards(RouteCode)Earlybird) | Pilot (ID: 1) has 0 early flights, 5 required.  
* SPAwards(RouteCode)Fuelburner) | Pilot (ID: 1) has no flights exceeding 50000 kg fuel used. 

## Note:

You can combine different award types to create progression levels, for example:

- ShortHaul I – 10 flights under 600 nm
- ShortHaul II – 25 flights under 600 nm
- ShortHaul III – 50 flights under 600 nm

## Do you have any suggestions or need help?
Please use the GitHub [issue](https://github.com/PaintSplasher/phpvms7_SPAwards/issues) tracker.

## Release / Update Notes

15.OCTOBER.25
* Initial Release