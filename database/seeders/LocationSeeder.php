<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\CommuneList;
use App\Models\Country;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        /* ══════════════════════════════════════════════
         *  CÔTE D'IVOIRE
         * ══════════════════════════════════════════════ */
        $ci = Country::updateOrCreate(['code' => 'CI'], [
            'name' => "Côte d'Ivoire",
            'phone_code' => '+225',
            'currency' => 'XOF',
            'latitude' => 7.5400,
            'longitude' => -5.5471,
            'min_lat' => 4.3571,
            'max_lat' => 10.7400,
            'min_lng' => -8.6042,
            'max_lng' => -2.4942,
            'is_active' => true,
        ]);

        // ── Villes de Côte d'Ivoire ──
        $ciCities = [
            ['name' => 'Abidjan', 'latitude' => 5.3600, 'longitude' => -4.0083, 'sort_order' => 1],
            ['name' => 'Yamoussoukro', 'latitude' => 6.8276, 'longitude' => -5.2893, 'sort_order' => 2],
            ['name' => 'Bouaké', 'latitude' => 7.6881, 'longitude' => -5.0305, 'sort_order' => 3],
            ['name' => 'San-Pédro', 'latitude' => 4.7485, 'longitude' => -6.6363, 'sort_order' => 4],
            ['name' => 'Daloa', 'latitude' => 6.8774, 'longitude' => -6.4502, 'sort_order' => 5],
            ['name' => 'Korhogo', 'latitude' => 9.4580, 'longitude' => -5.6295, 'sort_order' => 6],
            ['name' => 'Man', 'latitude' => 7.4125, 'longitude' => -7.5536, 'sort_order' => 7],
            ['name' => 'Gagnoa', 'latitude' => 6.1319, 'longitude' => -5.9506, 'sort_order' => 8],
            ['name' => 'Divo', 'latitude' => 5.8372, 'longitude' => -5.3571, 'sort_order' => 9],
            ['name' => 'Abengourou', 'latitude' => 6.7297, 'longitude' => -3.4964, 'sort_order' => 10],
            ['name' => 'Soubré', 'latitude' => 5.7836, 'longitude' => -6.5931, 'sort_order' => 11],
            ['name' => 'Séguéla', 'latitude' => 7.9610, 'longitude' => -6.6731, 'sort_order' => 12],
            ['name' => 'Odienné', 'latitude' => 9.5100, 'longitude' => -7.5642, 'sort_order' => 13],
            ['name' => 'Bondoukou', 'latitude' => 8.0402, 'longitude' => -2.8001, 'sort_order' => 14],
            ['name' => 'Adzopé', 'latitude' => 6.1057, 'longitude' => -3.8620, 'sort_order' => 15],
            ['name' => 'Grand-Bassam', 'latitude' => 5.2029, 'longitude' => -3.7400, 'sort_order' => 16],
            ['name' => 'Bassam', 'latitude' => 5.2029, 'longitude' => -3.7400, 'sort_order' => 17, 'is_active' => false],
            ['name' => 'Jacqueville', 'latitude' => 5.2058, 'longitude' => -4.4164, 'sort_order' => 18],
            ['name' => 'Assinie', 'latitude' => 5.1583, 'longitude' => -3.4667, 'sort_order' => 19],
            ['name' => 'Bingerville', 'latitude' => 5.3531, 'longitude' => -3.8833, 'sort_order' => 20],
        ];

        foreach ($ciCities as $cityData) {
            City::updateOrCreate(
                ['country_id' => $ci->id, 'slug' => \Str::slug($cityData['name'])],
                array_merge($cityData, ['country_id' => $ci->id, 'is_active' => $cityData['is_active'] ?? true])
            );
        }

        // ── Communes d'Abidjan ──
        $abidjan = City::where('country_id', $ci->id)->where('slug', 'abidjan')->first();

        $abidjanCommunes = [
            ['name' => 'Abobo', 'latitude' => 5.4194, 'longitude' => -4.0200],
            ['name' => 'Adjamé', 'latitude' => 5.3500, 'longitude' => -4.0200],
            ['name' => 'Anyama', 'latitude' => 5.4922, 'longitude' => -4.0539],
            ['name' => 'Attécoubé', 'latitude' => 5.3333, 'longitude' => -4.0500],
            ['name' => 'Bingerville', 'latitude' => 5.3531, 'longitude' => -3.8833],
            ['name' => 'Cocody', 'latitude' => 5.3500, 'longitude' => -3.9800],
            ['name' => 'Koumassi', 'latitude' => 5.3000, 'longitude' => -3.9600],
            ['name' => 'Marcory', 'latitude' => 5.3100, 'longitude' => -3.9900],
            ['name' => 'Plateau', 'latitude' => 5.3200, 'longitude' => -4.0200],
            ['name' => 'Port-Bouët', 'latitude' => 5.2600, 'longitude' => -3.9300],
            ['name' => 'Treichville', 'latitude' => 5.3000, 'longitude' => -4.0100],
            ['name' => 'Yopougon', 'latitude' => 5.3388, 'longitude' => -4.0825],
            ['name' => 'Songon', 'latitude' => 5.3200, 'longitude' => -4.2600],
        ];

        foreach ($abidjanCommunes as $communeData) {
            CommuneList::updateOrCreate(
                ['city_id' => $abidjan->id, 'slug' => \Str::slug($communeData['name'])],
                array_merge($communeData, ['city_id' => $abidjan->id, 'is_active' => true])
            );
        }

        // ── Communes de Yamoussoukro ──
        $yamoussoukro = City::where('country_id', $ci->id)->where('slug', 'yamoussoukro')->first();
        $yamoussoukroCommunes = [
            ['name' => 'Yamoussoukro Centre', 'latitude' => 6.8276, 'longitude' => -5.2893],
            ['name' => 'Attiégouakro', 'latitude' => 6.9167, 'longitude' => -5.2833],
        ];
        foreach ($yamoussoukroCommunes as $communeData) {
            CommuneList::updateOrCreate(
                ['city_id' => $yamoussoukro->id, 'slug' => \Str::slug($communeData['name'])],
                array_merge($communeData, ['city_id' => $yamoussoukro->id, 'is_active' => true])
            );
        }

        // ── Communes de Bouaké ──
        $bouake = City::where('country_id', $ci->id)->where('slug', 'bouake')->first();
        $bouakeCommunes = [
            ['name' => 'Bouaké Centre', 'latitude' => 7.6881, 'longitude' => -5.0305],
            ['name' => 'Dar-es-Salam', 'latitude' => 7.6900, 'longitude' => -5.0200],
            ['name' => 'Sokoura', 'latitude' => 7.6750, 'longitude' => -5.0400],
        ];
        foreach ($bouakeCommunes as $communeData) {
            CommuneList::updateOrCreate(
                ['city_id' => $bouake->id, 'slug' => \Str::slug($communeData['name'])],
                array_merge($communeData, ['city_id' => $bouake->id, 'is_active' => true])
            );
        }

        /* ══════════════════════════════════════════════
         *  BURKINA FASO
         * ══════════════════════════════════════════════ */
        $bf = Country::updateOrCreate(['code' => 'BF'], [
            'name' => 'Burkina Faso',
            'phone_code' => '+226',
            'currency' => 'XOF',
            'latitude' => 12.3714,
            'longitude' => -1.5197,
            'min_lat' => 9.4010,
            'max_lat' => 15.0840,
            'min_lng' => -5.5209,
            'max_lng' => 2.4043,
            'is_active' => true,
        ]);

        // ── Villes du Burkina Faso ──
        $bfCities = [
            ['name' => 'Ouagadougou', 'latitude' => 12.3714, 'longitude' => -1.5197, 'sort_order' => 1],
            ['name' => 'Bobo-Dioulasso', 'latitude' => 11.1771, 'longitude' => -4.2979, 'sort_order' => 2],
            ['name' => 'Koudougou', 'latitude' => 12.2500, 'longitude' => -2.3625, 'sort_order' => 3],
            ['name' => 'Ouahigouya', 'latitude' => 13.5833, 'longitude' => -2.4167, 'sort_order' => 4],
            ['name' => 'Banfora', 'latitude' => 10.6333, 'longitude' => -4.7667, 'sort_order' => 5],
            ['name' => 'Kaya', 'latitude' => 13.0833, 'longitude' => -1.0833, 'sort_order' => 6],
            ['name' => 'Tenkodogo', 'latitude' => 11.7833, 'longitude' => -0.3667, 'sort_order' => 7],
            ['name' => 'Fada N\'Gourma', 'latitude' => 12.0667, 'longitude' => 0.3500, 'sort_order' => 8],
            ['name' => 'Dédougou', 'latitude' => 12.4633, 'longitude' => -3.4606, 'sort_order' => 9],
            ['name' => 'Ziniaré', 'latitude' => 12.5833, 'longitude' => -1.3000, 'sort_order' => 10],
        ];

        foreach ($bfCities as $cityData) {
            City::updateOrCreate(
                ['country_id' => $bf->id, 'slug' => \Str::slug($cityData['name'])],
                array_merge($cityData, ['country_id' => $bf->id, 'is_active' => true])
            );
        }

        // ── Arrondissements de Ouagadougou ──
        $ouaga = City::where('country_id', $bf->id)->where('slug', 'ouagadougou')->first();
        $ouagaCommunes = [
            ['name' => 'Baskuy', 'latitude' => 12.3700, 'longitude' => -1.5200],
            ['name' => 'Bogodogo', 'latitude' => 12.3400, 'longitude' => -1.4800],
            ['name' => 'Boulmiougou', 'latitude' => 12.3600, 'longitude' => -1.5600],
            ['name' => 'Nongremassom', 'latitude' => 12.4000, 'longitude' => -1.5400],
            ['name' => 'Sig-Nonghin', 'latitude' => 12.3500, 'longitude' => -1.4600],
            ['name' => 'Ouaga 2000', 'latitude' => 12.3200, 'longitude' => -1.5000],
            ['name' => 'Pissy', 'latitude' => 12.3500, 'longitude' => -1.5700],
            ['name' => 'Tanghin', 'latitude' => 12.3900, 'longitude' => -1.5100],
        ];
        foreach ($ouagaCommunes as $communeData) {
            CommuneList::updateOrCreate(
                ['city_id' => $ouaga->id, 'slug' => \Str::slug($communeData['name'])],
                array_merge($communeData, ['city_id' => $ouaga->id, 'is_active' => true])
            );
        }

        // ── Arrondissements de Bobo-Dioulasso ──
        $bobo = City::where('country_id', $bf->id)->where('slug', 'bobo-dioulasso')->first();
        $boboCommunes = [
            ['name' => 'Dafra', 'latitude' => 11.1800, 'longitude' => -4.3100],
            ['name' => 'Do', 'latitude' => 11.1700, 'longitude' => -4.2800],
            ['name' => 'Konsa', 'latitude' => 11.1900, 'longitude' => -4.2900],
        ];
        foreach ($boboCommunes as $communeData) {
            CommuneList::updateOrCreate(
                ['city_id' => $bobo->id, 'slug' => \Str::slug($communeData['name'])],
                array_merge($communeData, ['city_id' => $bobo->id, 'is_active' => true])
            );
        }
    }
}
