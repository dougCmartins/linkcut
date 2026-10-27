<?php

declare(strict_types=1);

namespace Domain\Review\Tests\Feature;

use Domain\Client\Models\Client;
use Domain\Identity\Models\User;
use Domain\Review\Models\Review;
use Domain\Schedule\Enums\BookingStatus;
use Domain\Schedule\Tests\Concerns\ScheduleFixtures;
use Domain\Settings\Models\PortfolioSettings;
use Domain\Settings\Support\PublicSettingsCache;
use Domain\Tenant\Context\CurrentTenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\Passport;
use Tests\TestCase;

/** A client rates a completed visit. The barber chooses what appears on the public page. */
final class ReviewsTest extends TestCase
{
    use RefreshDatabase;
    use ScheduleFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeMondayMorning();
    }

    public function test_the_owner_of_a_completed_visit_saves_one_clean_review(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $booking = $this->bookingRequest($owner, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id, 'client_name' => 'Ana Souza']);
        Passport::actingAs($ana, [], 'client');

        // Act
        $response = $this->postJson("/api/client/appointments/{$booking->id}/review", [
            'rating' => 5,
            'comment' => '  Corte <b>ótimo</b> https://evil.example  ',
        ]);
        $again = $this->postJson("/api/client/appointments/{$booking->id}/review", ['rating' => 4]);

        // Assert
        $response->assertCreated()->assertJsonPath('rating', 5);
        $again->assertStatus(409)->assertJsonPath('code', 'REVIEW_ALREADY_EXISTS');
        $this->assertSame('Corte ótimo', Review::withoutTenantScope()->firstOrFail()->comment);
        $this->assertSame('Ana', Review::withoutTenantScope()->firstOrFail()->author_first_name);
        $this->assertNull(Review::withoutTenantScope()->firstOrFail()->hidden_at);
    }

    public function test_an_empty_comment_is_stored_as_null(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $booking = $this->bookingRequest($owner, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $response = $this->postJson("/api/client/appointments/{$booking->id}/review", ['rating' => 4, 'comment' => '   ']);

        // Assert
        $response->assertCreated();
        $this->assertNull(Review::withoutTenantScope()->firstOrFail()->comment);
    }

    public function test_a_visit_that_is_not_completed_or_not_owned_cannot_be_reviewed(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $other = Client::factory()->registered()->create();
        $pending = $this->bookingRequest($owner, '2026-10-20', '10:00', BookingStatus::Confirmed, attributes: ['client_id' => $ana->id]);
        $done = $this->bookingRequest($owner, '2026-10-12', '11:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');

        // Act
        $tooSoon = $this->postJson("/api/client/appointments/{$pending->id}/review", ['rating' => 5]);
        Passport::actingAs($other, [], 'client');
        $stranger = $this->postJson("/api/client/appointments/{$done->id}/review", ['rating' => 5]);
        $invalid = $this->postJson("/api/client/appointments/{$done->id}/review", ['rating' => 6, 'comment' => str_repeat('a', 501)]);

        // Assert
        $tooSoon->assertUnprocessable()->assertJsonPath('code', 'REVIEW_NOT_ALLOWED');
        $stranger->assertNotFound()->assertJsonPath('code', 'BOOKING_REQUEST_NOT_FOUND');
        $invalid->assertUnprocessable()->assertJsonPath('code', 'VALIDATION_FAILED');
        $this->assertSame(0, Review::withoutTenantScope()->count());
    }

    public function test_the_appointment_list_says_which_visits_were_reviewed(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $done = $this->bookingRequest($owner, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        $this->bookingRequest($owner, '2026-10-21', '15:00', BookingStatus::Confirmed, 'Barba', ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');
        $this->postJson("/api/client/appointments/{$done->id}/review", ['rating' => 5])->assertCreated();

        // Act
        $response = $this->getJson('/api/client/appointments');

        // Assert
        $response->assertOk();
        $rows = collect($response->json())->keyBy('id');
        $this->assertTrue($rows[$done->id]['has_review']);
        $this->assertFalse($rows->first(fn (array $row): bool => $row['id'] !== $done->id)['has_review']);
    }

    public function test_with_both_options_off_the_public_page_does_not_read_reviews(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $booking = $this->bookingRequest($owner, '2026-10-12', '10:00', BookingStatus::Completed, attributes: ['client_id' => $ana->id]);
        Passport::actingAs($ana, [], 'client');
        $this->postJson("/api/client/appointments/{$booking->id}/review", ['rating' => 5, 'comment' => 'Ótimo'])->assertCreated();
        $this->app['auth']->forgetGuards();

        // Act
        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->getJson('/api/public/rafael');

        // Assert
        $response->assertOk()
            ->assertJsonPath('rating.average', null)
            ->assertJsonPath('rating.count', 0)
            ->assertJsonCount(0, 'featured_reviews');
        $this->assertSame(0, $this->reviewQueries(), 'an option that is off must not query reviews');
    }

    public function test_the_average_has_one_decimal_and_the_carousel_follows_the_chosen_order(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->showOnPage($owner, rating: true, reviews: true);
        $ana = Client::factory()->registered()->create();
        $first = $this->reviewed($owner, $ana, '10:00', 5, 'Primeiro');
        $second = $this->reviewed($owner, $ana, '11:00', 4, 'Segundo');
        $third = $this->reviewed($owner, $ana, '12:00', 3, 'Terceiro');
        $silent = $this->reviewed($owner, $ana, '13:00', 5, null);
        $this->actingAsOwner($owner);

        // Act
        $featured = $this->putJson('/api/reviews/featured', ['review_ids' => [$second->id, $first->id, $third->id]]);
        $reordered = $this->putJson('/api/reviews/featured', ['review_ids' => [$third->id, $second->id, $first->id]]);
        $this->app['auth']->forgetGuards();
        $page = $this->getJson('/api/public/rafael');

        // Assert
        $featured->assertOk();
        $reordered->assertOk();
        $page->assertOk()
            ->assertJsonPath('rating.average', 4.3)
            ->assertJsonPath('rating.count', 4)
            ->assertJsonPath('featured_reviews.0.first_name', 'Ana')
            ->assertJsonPath('featured_reviews.0.comment', 'Terceiro')
            ->assertJsonPath('featured_reviews.1.comment', 'Segundo')
            ->assertJsonPath('featured_reviews.2.comment', 'Primeiro')
            ->assertJsonCount(3, 'featured_reviews');
        $this->assertNull($silent->featured_position);
    }

    public function test_a_fourth_a_silent_or_a_hidden_review_cannot_be_featured(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $ana = Client::factory()->registered()->create();
        $ids = [
            $this->reviewed($owner, $ana, '10:00', 5, 'Um')->id,
            $this->reviewed($owner, $ana, '11:00', 5, 'Dois')->id,
            $this->reviewed($owner, $ana, '12:00', 5, 'Três')->id,
            $this->reviewed($owner, $ana, '13:00', 5, 'Quatro')->id,
        ];
        $silent = $this->reviewed($owner, $ana, '14:00', 5, null);
        $hidden = $this->reviewed($owner, $ana, '15:00', 5, 'Oculto');
        $this->actingAsOwner($owner);
        $this->postJson("/api/reviews/{$hidden->id}/hide")->assertOk();

        // Act
        $fourth = $this->putJson('/api/reviews/featured', ['review_ids' => $ids]);
        $noComment = $this->putJson('/api/reviews/featured', ['review_ids' => [$silent->id]]);
        $hiddenResponse = $this->putJson('/api/reviews/featured', ['review_ids' => [$hidden->id]]);

        // Assert
        $fourth->assertUnprocessable()->assertJsonPath('code', 'REVIEW_NOT_FEATURED');
        $noComment->assertUnprocessable()->assertJsonPath('code', 'REVIEW_NOT_FEATURED');
        $hiddenResponse->assertUnprocessable()->assertJsonPath('code', 'REVIEW_NOT_FEATURED');
    }

    public function test_another_barber_cannot_list_hide_or_feature_the_review(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $other = $this->barbershop('navalha');
        $ana = Client::factory()->registered()->create();
        $review = $this->reviewed($owner, $ana, '10:00', 5, 'Ótimo');
        $this->actingAsOwner($other);

        // Act
        $list = $this->getJson('/api/reviews');
        $hide = $this->postJson("/api/reviews/{$review->id}/hide");
        $feature = $this->putJson('/api/reviews/featured', ['review_ids' => [$review->id]]);

        // Assert
        $list->assertOk()->assertJsonCount(0);
        $hide->assertNotFound()->assertJsonPath('code', 'REVIEW_NOT_FOUND');
        $feature->assertNotFound()->assertJsonPath('code', 'REVIEW_NOT_FOUND');
        $this->assertNull($review->refresh()->hidden_at);
    }

    public function test_hiding_drops_the_review_from_the_average_and_the_carousel(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->showOnPage($owner, rating: true, reviews: true);
        $ana = Client::factory()->registered()->create();
        $kept = $this->reviewed($owner, $ana, '10:00', 5, 'Fica');
        $hidden = $this->reviewed($owner, $ana, '11:00', 1, 'Sai');
        $this->actingAsOwner($owner);
        $this->putJson('/api/reviews/featured', ['review_ids' => [$kept->id, $hidden->id]])->assertOk();

        // Act
        $hide = $this->postJson("/api/reviews/{$hidden->id}/hide");
        $this->app['auth']->forgetGuards();
        $page = $this->getJson('/api/public/rafael');

        // Assert
        $hide->assertOk()->assertJsonPath('featured_position', null);
        $this->assertNotNull($hidden->refresh()->hidden_at);
        $this->assertNull($hidden->featured_position);
        $page->assertOk()
            ->assertJsonPath('rating.average', 5)
            ->assertJsonPath('rating.count', 1)
            ->assertJsonPath('featured_reviews.0.comment', 'Fica')
            ->assertJsonCount(1, 'featured_reviews');
    }

    public function test_deleting_the_account_removes_the_review_and_the_export_includes_the_rating(): void
    {
        // Arrange
        $owner = $this->barbershop();
        $this->showOnPage($owner, rating: true, reviews: true);
        $ana = Client::factory()->registered()->create();
        $review = $this->reviewed($owner, $ana, '10:00', 5, 'Volto sempre');
        $this->actingAsOwner($owner);
        $this->putJson('/api/reviews/featured', ['review_ids' => [$review->id]])->assertOk();
        $this->app['auth']->forgetGuards();
        $this->getJson('/api/public/rafael')->assertOk()->assertJsonPath('featured_reviews.0.comment', 'Volto sempre');
        $this->actingAsOwner($owner);
        $barbershopExport = $this->get('/api/account/export', ['Accept' => 'application/json']);
        Passport::actingAs($ana, [], 'client');
        $clientExport = $this->get('/api/client/me/export', ['Accept' => 'application/json']);

        // Act
        $deleted = $this->deleteJson('/api/client/me', ['password' => 'segredo123']);
        $this->app['auth']->forgetGuards();
        $page = $this->getJson('/api/public/rafael');

        // Assert
        $barbershopExport->assertOk()->assertJsonPath('reviews.0.rating', 5);
        $clientExport->assertOk()->assertJsonPath('reviews.0.rating', 5)->assertJsonPath('reviews.0.comment', 'Volto sempre');
        $deleted->assertNoContent();
        $this->assertSame(0, Review::withoutTenantScope()->count());
        $page->assertOk()->assertJsonPath('rating.average', null)->assertJsonCount(0, 'featured_reviews');
    }

    private function showOnPage(User $owner, bool $rating, bool $reviews): void
    {
        app(CurrentTenant::class)->run($owner->tenant_id, function () use ($rating, $reviews): void {
            PortfolioSettings::query()->firstOrFail()->update(['show_rating' => $rating, 'show_reviews' => $reviews]);
            app(PublicSettingsCache::class)->forget();
        });
    }

    private function reviewed(User $owner, Client $client, string $time, int $rating, ?string $comment): Review
    {
        $booking = $this->bookingRequest($owner, '2026-10-12', $time, BookingStatus::Completed, attributes: [
            'client_id' => $client->id,
            'client_name' => 'Ana Souza',
        ]);
        Passport::actingAs($client, [], 'client');
        $this->postJson("/api/client/appointments/{$booking->id}/review", [
            'rating' => $rating,
            'comment' => $comment,
        ])->assertCreated();

        return Review::withoutTenantScope()->where('booking_request_id', $booking->id)->firstOrFail();
    }

    private function reviewQueries(): int
    {
        return collect(DB::getQueryLog())->filter(
            fn (array $query): bool => (bool) preg_match('/\breviews\b/', $query['query']),
        )->count();
    }
}
