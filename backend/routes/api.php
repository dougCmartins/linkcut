<?php

declare(strict_types=1);

// Each domain owns its routes. They get the /api prefix from bootstrap/app.php.
require base_path('src/Domain/Identity/Routes/api.php');
require base_path('src/Domain/Orchestrator/SignIn/Routes/api.php');
require base_path('src/Domain/Tenant/Routes/api.php');
require base_path('src/Domain/Settings/Routes/api.php');
require base_path('src/Domain/Finance/Routes/api.php');
require base_path('src/Domain/Schedule/Routes/api.php');
require base_path('src/Domain/Orchestrator/PublicBooking/Routes/api.php');
require base_path('src/Domain/Orchestrator/CompleteAppointment/Routes/api.php');
require base_path('src/Domain/Notification/Routes/api.php');
require base_path('src/Domain/Client/Routes/api.php');
