<?php

declare(strict_types=1);

// Each domain owns its routes. They get the /api prefix from bootstrap/app.php.
require base_path('src/Domain/Identity/Routes/api.php');
require base_path('src/Domain/Orchestrator/SignIn/Routes/api.php');
require base_path('src/Domain/Orchestrator/Onboarding/Routes/api.php');
require base_path('src/Domain/Tenant/Routes/api.php');
require base_path('src/Domain/Settings/Routes/api.php');
require base_path('src/Domain/Orchestrator/PublicBrand/Routes/api.php');
require base_path('src/Domain/Orchestrator/PublicPage/Routes/api.php');
require base_path('src/Domain/Finance/Routes/api.php');
require base_path('src/Domain/Schedule/Routes/api.php');
require base_path('src/Domain/Orchestrator/PublicBooking/Routes/api.php');
require base_path('src/Domain/Orchestrator/CompleteAppointment/Routes/api.php');
require base_path('src/Domain/Orchestrator/AnswerBookingRequest/Routes/api.php');
require base_path('src/Domain/Notification/Routes/api.php');
require base_path('src/Domain/Client/Routes/api.php');
require base_path('src/Domain/Orchestrator/ClientAppointments/Routes/api.php');
require base_path('src/Domain/Orchestrator/ClientAccount/Routes/api.php');
require base_path('src/Domain/Orchestrator/BarbershopAccount/Routes/api.php');
require base_path('src/Domain/Orchestrator/Review/Routes/api.php');
require base_path('src/Domain/Orchestrator/HistoryReport/Routes/api.php');
require base_path('src/Domain/Orchestrator/CommissionReport/Routes/api.php');
require base_path('src/Domain/Orchestrator/BrandReport/Routes/api.php');
require base_path('src/Domain/Orchestrator/Catalog/Routes/api.php');
require base_path('src/Domain/Orchestrator/Organization/Routes/api.php');
require base_path('src/Domain/Orchestrator/StaffInvite/Routes/api.php');
require base_path('src/Domain/Orchestrator/StaffSchedule/Routes/api.php');
require base_path('src/Domain/Orchestrator/StaffMove/Routes/api.php');
