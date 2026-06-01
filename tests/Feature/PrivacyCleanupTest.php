<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Residence;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrivacyCleanupTest extends TestCase
{
    use RefreshDatabase;

    private int $userId;
    private int $ownerId;
    private int $residenceId;

    protected function setUp(): void
    {
        parent::setUp();

        $owner = User::factory()->create(['role' => 'owner']);
        $user  = User::factory()->create(['role' => 'user']);
        $residence = Residence::factory()->create(['owner_id' => $owner->id]);

        $this->ownerId     = $owner->id;
        $this->userId      = $user->id;
        $this->residenceId = $residence->id;
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function createContact(
        ?float $lat,
        ?float $lng,
        \DateTimeInterface $createdAt,
    ): int {
        return DB::table('contacts')->insertGetId([
            'user_id'        => $this->userId,
            'residence_id'   => $this->residenceId,
            'owner_id'       => $this->ownerId,
            'phone'          => '+2250700000000',
            'message'        => 'Message test',
            'user_latitude'  => $lat,
            'user_longitude' => $lng,
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
        ]);
    }

    private function createSearchHistory(\DateTimeInterface $createdAt): int
    {
        return DB::table('search_histories')->insertGetId([
            'user_id'    => $this->userId,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    private function createViewHistory(\DateTimeInterface $lastViewedAt): int
    {
        return DB::table('view_history')->insertGetId([
            'user_id'       => $this->userId,
            'residence_id'  => $this->residenceId,
            'last_viewed_at' => $lastViewedAt,
            'first_viewed_at' => $lastViewedAt,
            'created_at'    => $lastViewedAt,
            'updated_at'    => $lastViewedAt,
        ]);
    }

    // ----------------------------------------------------------------
    // Tests coordonnées GPS dans contacts
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_anonymizes_old_gps_coordinates(): void
    {
        $oldDate   = now()->subDays(91);
        $contactId = $this->createContact(5.3599, -4.0083, $oldDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $row = DB::table('contacts')->find($contactId);
        $this->assertNull($row->user_latitude, 'La latitude doit être nullifiée après 90 jours.');
        $this->assertNull($row->user_longitude, 'La longitude doit être nullifiée après 90 jours.');
    }

    #[Test]
    public function test_recent_contacts_are_not_anonymized(): void
    {
        $recentDate = now()->subDays(10);
        $contactId  = $this->createContact(5.3599, -4.0083, $recentDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $row = DB::table('contacts')->find($contactId);
        $this->assertNotNull($row->user_latitude, 'La latitude ne doit pas être supprimée avant 90 jours.');
        $this->assertNotNull($row->user_longitude, 'La longitude ne doit pas être supprimée avant 90 jours.');
    }

    #[Test]
    public function test_contacts_without_gps_are_untouched(): void
    {
        $oldDate   = now()->subDays(200);
        $contactId = $this->createContact(null, null, $oldDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('contacts', ['id' => $contactId]);
    }

    #[Test]
    public function test_chunking_anonymizes_all_contacts_above_chunk_size(): void
    {
        $oldDate   = now()->subDays(100);
        $batchSize = 550;

        $insertData = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $insertData[] = [
                'user_id'        => $this->userId,
                'residence_id'   => $this->residenceId,
                'owner_id'       => $this->ownerId,
                'phone'          => '+2250700000000',
                'message'        => "Message test {$i}",
                'user_latitude'  => 5.3599,
                'user_longitude' => -4.0083,
                'created_at'     => $oldDate,
                'updated_at'     => $oldDate,
            ];
        }
        DB::table('contacts')->insert($insertData);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $remaining = DB::table('contacts')
            ->whereNotNull('user_latitude')
            ->where('created_at', '<', now()->subDays(90))
            ->count();

        $this->assertSame(0, $remaining, "Tous les {$batchSize} contacts éligibles doivent être anonymisés.");
    }

    // ----------------------------------------------------------------
    // Tests search_histories
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_deletes_old_search_histories(): void
    {
        $oldDate   = now()->subDays(366);
        $historyId = $this->createSearchHistory($oldDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('search_histories', ['id' => $historyId]);
    }

    #[Test]
    public function test_recent_search_histories_are_kept(): void
    {
        $recentDate = now()->subDays(30);
        $historyId  = $this->createSearchHistory($recentDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('search_histories', ['id' => $historyId]);
    }

    // ----------------------------------------------------------------
    // Tests view_history (RGPD)
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_deletes_old_view_history(): void
    {
        $oldDate = now()->subDays(400);
        $viewId  = $this->createViewHistory($oldDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $this->assertDatabaseMissing('view_history', ['id' => $viewId]);
    }

    #[Test]
    public function test_recent_view_history_is_kept(): void
    {
        $recentDate = now()->subDays(30);
        $viewId     = $this->createViewHistory($recentDate);

        $this->artisan('privacy:cleanup')->assertSuccessful();

        $this->assertDatabaseHas('view_history', ['id' => $viewId]);
    }

    // ----------------------------------------------------------------
    // Tests mode dry-run
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_dry_run_makes_no_changes(): void
    {
        $oldDate   = now()->subDays(200);
        $contactId = $this->createContact(5.3599, -4.0083, $oldDate);

        $searchOldDate = now()->subDays(400);
        $historyId     = $this->createSearchHistory($searchOldDate);

        $viewOldDate = now()->subDays(400);
        $viewId      = $this->createViewHistory($viewOldDate);

        $this->artisan('privacy:cleanup --dry-run')->assertSuccessful();

        $contact = DB::table('contacts')->find($contactId);
        $this->assertNotNull($contact->user_latitude, 'Dry-run ne doit pas modifier les coordonnées GPS.');

        $this->assertDatabaseHas('search_histories', ['id' => $historyId]);
        $this->assertDatabaseHas('view_history', ['id' => $viewId]);
    }

    #[Test]
    public function test_dry_run_outputs_counts_without_applying(): void
    {
        $oldDate = now()->subDays(200);
        $this->createContact(5.3599, -4.0083, $oldDate);

        $this->artisan('privacy:cleanup --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('dry-run');
    }
}
