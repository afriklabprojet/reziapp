<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Country;
use Illuminate\Support\Facades\DB;

class WestAfricaCountriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seed West African countries with currencies and locales
     */
    public function run(): void
    {
        $countries = [
            [
                'name' => 'Côte d\'Ivoire',
                'code' => 'CI',
                'phone_code' => '+225',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇨🇮',
                'locale' => 'fr_CI',
                'timezone' => 'Africa/Abidjan',
                'is_active' => true,
            ],
            [
                'name' => 'Sénégal',
                'code' => 'SN',
                'phone_code' => '+221',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇸🇳',
                'locale' => 'fr_SN',
                'timezone' => 'Africa/Dakar',
                'is_active' => false,
            ],
            [
                'name' => 'Mali',
                'code' => 'ML',
                'phone_code' => '+223',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇲🇱',
                'locale' => 'fr_ML',
                'timezone' => 'Africa/Bamako',
                'is_active' => false,
            ],
            [
                'name' => 'Burkina Faso',
                'code' => 'BF',
                'phone_code' => '+226',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇧🇫',
                'locale' => 'fr_BF',
                'timezone' => 'Africa/Ouagadougou',
                'is_active' => false,
            ],
            [
                'name' => 'Niger',
                'code' => 'NE',
                'phone_code' => '+227',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇳🇪',
                'locale' => 'fr_NE',
                'timezone' => 'Africa/Niamey',
                'is_active' => false,
            ],
            [
                'name' => 'Togo',
                'code' => 'TG',
                'phone_code' => '+228',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇹🇬',
                'locale' => 'fr_TG',
                'timezone' => 'Africa/Lome',
                'is_active' => false,
            ],
            [
                'name' => 'Bénin',
                'code' => 'BJ',
                'phone_code' => '+229',
                'currency_code' => 'XOF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA',
                'flag_emoji' => '🇧🇯',
                'locale' => 'fr_BJ',
                'timezone' => 'Africa/Porto-Novo',
                'is_active' => false,
            ],
            [
                'name' => 'Guinée',
                'code' => 'GN',
                'phone_code' => '+224',
                'currency_code' => 'GNF',
                'currency_symbol' => 'GNF',
                'currency_name' => 'Franc Guinéen',
                'flag_emoji' => '🇬🇳',
                'locale' => 'fr_GN',
                'timezone' => 'Africa/Conakry',
                'is_active' => false,
            ],
            [
                'name' => 'Ghana',
                'code' => 'GH',
                'phone_code' => '+233',
                'currency_code' => 'GHS',
                'currency_symbol' => 'GH₵',
                'currency_name' => 'Cedi Ghanéen',
                'flag_emoji' => '🇬🇭',
                'locale' => 'en_GH',
                'timezone' => 'Africa/Accra',
                'is_active' => false,
            ],
            [
                'name' => 'Nigeria',
                'code' => 'NG',
                'phone_code' => '+234',
                'currency_code' => 'NGN',
                'currency_symbol' => '₦',
                'currency_name' => 'Naira',
                'flag_emoji' => '🇳🇬',
                'locale' => 'en_NG',
                'timezone' => 'Africa/Lagos',
                'is_active' => false,
            ],
            [
                'name' => 'Cameroun',
                'code' => 'CM',
                'phone_code' => '+237',
                'currency_code' => 'XAF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA (CEMAC)',
                'flag_emoji' => '🇨🇲',
                'locale' => 'fr_CM',
                'timezone' => 'Africa/Douala',
                'is_active' => false,
            ],
            [
                'name' => 'Gabon',
                'code' => 'GA',
                'phone_code' => '+241',
                'currency_code' => 'XAF',
                'currency_symbol' => 'FCFA',
                'currency_name' => 'Franc CFA (CEMAC)',
                'flag_emoji' => '🇬🇦',
                'locale' => 'fr_GA',
                'timezone' => 'Africa/Libreville',
                'is_active' => false,
            ],
        ];

        foreach ($countries as $countryData) {
            // Check if country exists
            $existing = DB::table('countries')->where('code', $countryData['code'])->first();
            
            if ($existing) {
                // Update existing
                DB::table('countries')
                    ->where('code', $countryData['code'])
                    ->update([
                        'phone_code' => $countryData['phone_code'],
                        'currency_code' => $countryData['currency_code'],
                        'currency_symbol' => $countryData['currency_symbol'],
                        'currency_name' => $countryData['currency_name'],
                        'flag_emoji' => $countryData['flag_emoji'],
                        'locale' => $countryData['locale'],
                        'timezone' => $countryData['timezone'],
                        'updated_at' => now(),
                    ]);
            } else {
                // Create new
                DB::table('countries')->insert([
                    ...$countryData,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('West African countries seeded successfully!');
    }
}
