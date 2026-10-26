<?php

declare(strict_types=1);

namespace Domain\Client\Controllers;

use Domain\Client\Actions\ChangeClientPassword;
use Domain\Client\Actions\CompleteClientRegistration;
use Domain\Client\Actions\ExchangeClientGoogleCode;
use Domain\Client\Actions\FinishClientGoogleSignIn;
use Domain\Client\Actions\LoginClient;
use Domain\Client\Actions\LogoutClient;
use Domain\Client\Actions\RefreshClientSession;
use Domain\Client\Actions\ResetClientPassword;
use Domain\Client\Actions\SendClientCode;
use Domain\Client\Actions\ShowAuthenticatedClient;
use Domain\Client\Actions\StartClientGoogleSignIn;
use Domain\Client\Actions\UpdateClientEmailPreference;
use Domain\Client\Actions\UpdateClientProfile;
use Domain\Client\Data\ClientCodeRequestData;
use Domain\Client\Data\ClientData;
use Domain\Client\Data\ClientEmailPreferenceData;
use Domain\Client\Data\ClientLoginData;
use Domain\Client\Data\ClientPasswordChangeData;
use Domain\Client\Data\ClientPasswordData;
use Domain\Client\Data\ClientProfileInputData;
use Domain\Client\Data\ClientRefreshData;
use Domain\Client\Data\ClientSessionData;
use Domain\Client\Data\CodeSentData;
use Domain\Client\Data\GoogleCallbackData;
use Domain\Client\Data\GoogleExchangeData;
use Domain\Client\Data\GoogleRedirectData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;

final class ClientAuthController
{
    public function sendCode(ClientCodeRequestData $data, SendClientCode $action): CodeSentData
    {
        return $action->handle($data);
    }

    public function register(ClientPasswordData $data, CompleteClientRegistration $action): ClientSessionData
    {
        return $action->handle($data);
    }

    public function resetPassword(ClientPasswordData $data, ResetClientPassword $action): ClientSessionData
    {
        return $action->handle($data);
    }

    public function login(ClientLoginData $data, LoginClient $action): ClientSessionData
    {
        return $action->handle($data);
    }

    /** Browser navigation to Google. OAuth cannot answer with Data. */
    public function redirectToGoogle(GoogleRedirectData $data, StartClientGoogleSignIn $action): RedirectResponse
    {
        return redirect()->away($action->handle($data));
    }

    /** Google's return, sent on to the SPA. The session itself is issued later, by the one-time code. */
    public function googleCallback(GoogleCallbackData $data, FinishClientGoogleSignIn $action): RedirectResponse
    {
        return redirect()->away(rtrim((string) config('app.frontend_url'), '/').$action->handle($data));
    }

    public function exchangeGoogle(GoogleExchangeData $data, ExchangeClientGoogleCode $action): ClientSessionData
    {
        return $action->handle($data);
    }

    public function refresh(ClientRefreshData $data, RefreshClientSession $action): ClientSessionData
    {
        return $action->handle($data);
    }

    public function logout(ClientRefreshData $data, LogoutClient $action): Response
    {
        $action->handle($data);

        return response()->noContent();
    }

    public function me(ShowAuthenticatedClient $action): ClientData
    {
        return $action->handle();
    }

    public function updateProfile(ClientProfileInputData $data, UpdateClientProfile $action): ClientData
    {
        return $action->handle($data);
    }

    public function changePassword(ClientPasswordChangeData $data, ChangeClientPassword $action): ClientSessionData
    {
        return $action->handle($data);
    }

    public function updateEmailPreference(ClientEmailPreferenceData $data, UpdateClientEmailPreference $action): ClientData
    {
        return $action->handle($data);
    }
}
