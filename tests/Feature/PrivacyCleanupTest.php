<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PrivacyCleanupTest extends TestCase
{
    use RefreshDatabase;

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    /**
     * Insère une ligne minimale dans `contacts` et retourne l'ID.
     */
    private function createContact(
        ?float $lat,
        ?float $lng,
        \DateTimeInterface $createdAt,
    ): int {
        return DB::table('contacts')->insertGetId([
            'name'           => 'Test Contact',
            'email'          => 'test@example.com',
            'phone'          => '+2250700000000',
            'message'        => 'Message test',
            'user_latitude'  => $lat,
            'user_longitude' => $lng,
            'created_at'     => $createdAt,
            'updated_at'     => $createdAt,
        ]);
    }

    /**
     * Insère une ligne dans `search_histories`.
     */
    private function createSearchHistory(\DateTimeInterface $createdAt): int
    {
        return DB::table('search_histories')->insertGetId([
            'query'      => 'studio Cocody',
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /**
     * Insère une ligne dans `view_histories` (anonyme si userId === null).
     */
    private function createViewHistory(\DateTimeInterface $createdAt, ?int $userId = null): int
    {
        return DB::table('view_histories')->insertGetId([
            'residence_id' => 1,
            'user_id'      => $userId,
            'created_at'   => $createdAt,
            'updated_at'   => $createdAt,
        ]);
    }

    // ----------------------------------------------------------------
    // Tests coordonnées GPS dans contacts
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_anonymizes_old_gps_coordinates(): void
    {
        // Arrange
        $oldDate    = now()->subDays(91);
        $contactId  = $this->createContact(5.3599, -4.0083, $oldDate);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $row = DB::table('contacts')->find($contactId);
        $this->assertNull($row->user_latitude, 'La latitude doit être nullifiée après 90 jours.');
        $this->assertNull($row->user_longitude, 'La longitude doit être nullifiée après 90 jours.');
    }

    #[Test]
    public function test_recent_contacts_are_not_anonymized(): void
    {
        // Arrange : contact créé il y a 10 jours (dans le TTL de 90 jours)
        $recentDate = now()->subDays(10);
        $contactId  = $this->createContact(5.3599, -4.0083, $recentDate);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $row = DB::table('contacts')->find($contactId);
        $this->assertNotNull($row->user_latitude, 'La latitude ne doit pas être supprimée avant 90 jours.');
        $this->assertNotNull($row->user_longitude, 'La longitude ne doit pas être supprimée avant 90 jours.');
    }

    #[Test]
    public function test_contacts_without_gps_are_untouched(): void
    {
        // Arrange : contact ancien sans GPS (déjà anonymisé précédemment)
        $oldDate   = now()->subDays(200);
        $contactId = $this->createContact(null, null, $oldDate);

        // Act — ne doit pas planter
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert : la ligne existe toujours
        $this->assertDatabaseHas('contacts', ['id' => $contactId]);
    }

    #[Test]
    public function test_chunking_anonymizes_all_contacts_above_chunk_size(): void
    {
        // Arrange : créer plus de 500 contacts éligibles pour déclencher plusieurs chunks
        $oldDate   = now()->subDays(100);
        $batchSize = 550;

        $insertData = [];
        for ($i = 0; $i < $batchSize; $i++) {
            $insertData[] = [
                'name'           => "Contact {$i}",
                'email'          => "contact{$i}@example.com",
                'phone'          => '+2250700000000',
                'message'        => 'Message test',
                'user_latitude'  => 5.3599,
                'user_longitude' => -4.0083,
                'created_at'     => $oldDate,
                'updated_at'     => $oldDate,
            ];
        }
        DB::table('contacts')->insert($insertData);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert : tous les contacts doivent avoir leurs GPS nullifiés
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
        // Arrange
        $oldDate   = now()->subDays(366);
        $historyId = $this->createSearchHistory($oldDate);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('search_histories', ['id' => $historyId]);
    }

    #[Test]
    public function test_recent_search_histories_are_kept(): void
    {
        // Arrange
        $recentDate = now()->subDays(30);
        $historyId  = $this->createSearchHistory($recentDate);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('search_histories', ['id' => $historyId]);
    }

    // ----------------------------------------------------------------
    // Tests view_histories anonymes
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_deletes_old_anonymous_view_histories(): void
    {
        // Arrange
        $oldDate = now()->subDays(400);
        $viewId  = $this->createViewHistory($oldDate, userId: null);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('view_histories', ['id' => $viewId]);
    }

    #[Test]
    public function test_recent_anonymous_view_histories_are_kept(): void
    {
        // Arrange : view_history anonyme récent (dans le TTL de 365 jours)
        $recentDate = now()->subDays(30);
        $viewId     = $this->createViewHistory($recentDate, userId: null);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('view_histories', ['id' => $viewId]);
    }

    // ----------------------------------------------------------------
    // Tests view_histories authentifiés (RGPD)
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_deletes_old_authenticated_view_histories(): void
    {
        // Les view_histories d'utilisateurs identifiés > 365 j doivent être purgés (RGPD).
        // Arrange
        $oldDate = now()->subDays(400);
        $viewId  = $this->createViewHistory($oldDate, userId: 1);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseMissing('view_histories', ['id' => $viewId]);
    }

    #[Test]
    public function test_recent_authenticated_view_histories_are_kept(): void
    {
        // Les view_histories d'utilisateurs identifiés récents (dans le TTL) doivent être préservés.
        // Arrange
        $recentDate = now()->subDays(30);
        $viewId     = $this->createViewHistory($recentDate, userId: 1);

        // Act
        $this->artisan('privacy:cleanup')->assertSuccessful();

        // Assert
        $this->assertDatabaseHas('view_histories', ['id' => $viewId]);
    }

    // ----------------------------------------------------------------
    // Tests mode dry-run
    // ----------------------------------------------------------------

    #[Test]
    public function test_cleanup_dry_run_makes_no_changes(): void
    {
        // Arrange : toutes les données éligibles à la purge
        $oldDate    = now()->subDays(200);
        $contactId  = $this->createContact(5.3599, -4.0083, $oldDate);

        $searchOldDate = now()->subDays(400);
        $historyId  = $this->createSearchHistory($searchOldDate);

        $viewOldDate = now()->subDays(400);
        $anonViewId  = $this->createViewHistory($viewOldDate, userId: null);
        $authViewId  = $this->createViewHistory($viewOldDate, userId: 1);

        // Act
        $this->artisan('privacy:cleanup --dry-run')->assertSuccessful();

        // Assert : rien ne doit avoir été modifié
        $contact = DB::table('contacts')->find($contactId);
        $this->assertNotNull($contact->user_latitude, 'Dry-run ne doit pas modifier les coordonnées GPS.');

        $this->assertDatabaseHas('search_histories', ['id' => $historyId]);
        $this->assertDatabaseHas('view_histories', ['id' => $anonViewId]);
        $this->assertDatabaseHas('view_histories', ['id' => $authViewId]);
    }

    #[Test]
    public function test_dry_run_outputs_counts_without_applying(): void
    {
        // Arrange
        $oldDate = now()->subDays(200);
        $this->createContact(5.3599, -4.0083, $oldDate);

        // Act & Assert : la commande doit afficher les comptages et mentionner dry-run
        $this->artisan('privacy:cleanup --dry-run')
            ->assertSuccessful()
            ->expectsOutputToContain('dry-run');
    }
}
