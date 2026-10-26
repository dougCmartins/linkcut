<?php

declare(strict_types=1);

namespace Domain\Settings\Controllers;

use Domain\Settings\Actions\ShowShareCard;
use Domain\Settings\Actions\ShowShareImage;
use Domain\Settings\Data\ShareCardQueryData;
use Domain\Settings\Enums\SharedPage;
use Illuminate\Http\Response;

/**
 * HTML for link-preview crawlers (WhatsApp, Facebook, X...). Caddy sends only them here; people get the SPA.
 * Bots never run JavaScript and people never get here, so the page does not redirect (some crawlers follow it).
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

    public function bioLinkImage(string $slug, ShowShareImage $action): Response
    {
        return $this->image($action->handle(new ShareCardQueryData(slug: $slug, page: SharedPage::BioLink)));
    }

    public function portfolioImage(string $slug, ShowShareImage $action): Response
    {
        return $this->image($action->handle(new ShareCardQueryData(slug: $slug, page: SharedPage::Portfolio)));
    }

    private function image(?string $jpeg): Response
    {
        abort_if($jpeg === null, 404);

        return response($jpeg, 200, ['Content-Type' => 'image/jpeg', 'Cache-Control' => 'public, max-age=86400']);
    }

    private function render(ShowShareCard $action, ShareCardQueryData $data): Response
    {
        return response()
            ->view('share-card', ['card' => $action->handle($data)])
            ->header('Cache-Control', 'public, max-age=600');
    }
}
