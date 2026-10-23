<?php

declare(strict_types=1);

namespace Domain\Settings\Controllers;

use Domain\Settings\Actions\ShowShareCard;
use Domain\Settings\Data\ShareCardQueryData;
use Domain\Settings\Enums\SharedPage;
use Illuminate\Http\Response;

/**
 * HTML for link-preview crawlers (WhatsApp, Facebook, X...). Caddy sends only them here; people get the SPA.
 * The page also redirects, in case a person lands on it.
 */
final class ShareCardController
{
    public function bioLink(string $slug, ShowShareCard $action): Response
    {
        return $this->render($action, new ShareCardQueryData(slug: $slug, page: SharedPage::BioLink));
    }

    public function portfolio(string $slug, ShowShareCard $action): Response
    {
        return $this->render($action, new ShareCardQueryData(slug: $slug, page: SharedPage::Portfolio));
    }

    private function render(ShowShareCard $action, ShareCardQueryData $data): Response
    {
        return response()
            ->view('share-card', ['card' => $action->handle($data)])
            ->header('Cache-Control', 'public, max-age=600');
    }
}
