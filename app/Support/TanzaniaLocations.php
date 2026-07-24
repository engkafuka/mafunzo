<?php

namespace App\Support;

/**
 * Tanzania administrative regions and districts (mainland + Zanzibar).
 * Source aligned with NBS / common LGAs listings (city, municipal, town, and district councils).
 */
class TanzaniaLocations
{
    /**
     * @return array<string, list<string>>
     */
    public static function regionsWithDistricts(): array
    {
        return [
            'Arusha' => [
                'Arusha City', 'Arusha', 'Karatu', 'Longido', 'Meru', 'Monduli', 'Ngorongoro',
            ],
            'Dar es Salaam' => [
                'Ilala', 'Kigamboni', 'Kinondoni', 'Temeke', 'Ubungo',
            ],
            'Dodoma' => [
                'Dodoma City', 'Bahi', 'Chamwino', 'Chemba', 'Kondoa', 'Kondoa Town', 'Kongwa', 'Mpwapwa',
            ],
            'Geita' => [
                'Bukombe', 'Chato', 'Geita', 'Geita Town', 'Mbogwe', 'Nyang\'hwale',
            ],
            'Iringa' => [
                'Iringa', 'Iringa Municipal', 'Kilolo', 'Mafinga Town', 'Mufindi',
            ],
            'Kagera' => [
                'Biharamulo', 'Bukoba', 'Bukoba Municipal', 'Karagwe', 'Kyerwa', 'Missenyi', 'Muleba', 'Ngara',
            ],
            'Katavi' => [
                'Mlele', 'Mpanda', 'Mpanda Town', 'Nsimbo', 'Tanganyika',
            ],
            'Kigoma' => [
                'Buhigwe', 'Kakonko', 'Kasulu', 'Kasulu Town', 'Kibondo', 'Kigoma', 'Kigoma-Ujiji Municipal', 'Uvinza',
            ],
            'Kilimanjaro' => [
                'Hai', 'Moshi', 'Moshi Municipal', 'Mwanga', 'Rombo', 'Same', 'Siha',
            ],
            'Lindi' => [
                'Kilwa', 'Lindi', 'Lindi Municipal', 'Liwale', 'Nachingwea', 'Ruangwa',
            ],
            'Manyara' => [
                'Babati', 'Babati Town', 'Hanang', 'Kiteto', 'Mbulu', 'Mbulu Town', 'Simanjiro',
            ],
            'Mara' => [
                'Bunda', 'Bunda Town', 'Butiama', 'Musoma', 'Musoma Municipal', 'Rorya', 'Serengeti', 'Tarime', 'Tarime Town',
            ],
            'Mbeya' => [
                'Busokelo', 'Chunya', 'Kyela', 'Mbarali', 'Mbeya', 'Mbeya City', 'Rungwe',
            ],
            'Morogoro' => [
                'Gairo', 'Ifakara Town', 'Kilombero', 'Kilosa', 'Malinyi', 'Morogoro', 'Morogoro Municipal', 'Mvomero', 'Ulanga',
            ],
            'Mtwara' => [
                'Masasi', 'Masasi Town', 'Mtwara', 'Mtwara Municipal', 'Nanyamba Town', 'Nanyumbu', 'Newala', 'Newala Town', 'Tandahimba',
            ],
            'Mwanza' => [
                'Buchosa', 'Ilemela', 'Kwimba', 'Magu', 'Misungwi', 'Nyamagana', 'Sengerema', 'Ukerewe',
            ],
            'Njombe' => [
                'Ludewa', 'Makambako Town', 'Makete', 'Njombe', 'Njombe Town', 'Wanging\'ombe',
            ],
            'Pwani' => [
                'Bagamoyo', 'Chalinze', 'Kibaha', 'Kibaha Town', 'Kisarawe', 'Mafia', 'Mkuranga', 'Rufiji',
            ],
            'Rukwa' => [
                'Kalambo', 'Nkasi', 'Sumbawanga', 'Sumbawanga Municipal',
            ],
            'Ruvuma' => [
                'Mbinga', 'Mbinga Town', 'Namtumbo', 'Nyasa', 'Songea', 'Songea Municipal', 'Tunduru',
            ],
            'Shinyanga' => [
                'Kahama', 'Kahama Town', 'Kishapu', 'Shinyanga', 'Shinyanga Municipal',
            ],
            'Simiyu' => [
                'Bariadi', 'Bariadi Town', 'Busega', 'Itilima', 'Maswa', 'Meatu',
            ],
            'Singida' => [
                'Ikungi', 'Iramba', 'Itigi', 'Manyoni', 'Mkalama', 'Singida', 'Singida Municipal',
            ],
            'Songwe' => [
                'Ileje', 'Mbozi', 'Momba', 'Songwe', 'Tunduma Town',
            ],
            'Tabora' => [
                'Igunga', 'Kaliua', 'Nzega', 'Nzega Town', 'Sikonge', 'Tabora Municipal', 'Urambo', 'Uyui',
            ],
            'Tanga' => [
                'Handeni', 'Handeni Town', 'Kilindi', 'Korogwe', 'Korogwe Town', 'Lushoto', 'Mkinga', 'Muheza', 'Pangani', 'Tanga City',
            ],
            'Kaskazini Unguja' => [
                'Kaskazini A', 'Kaskazini B',
            ],
            'Kusini Unguja' => [
                'Kati', 'Kusini',
            ],
            'Mjini Magharibi' => [
                'Magharibi A', 'Magharibi B', 'Mjini',
            ],
            'Kaskazini Pemba' => [
                'Micheweni', 'Wete',
            ],
            'Kusini Pemba' => [
                'Chake Chake', 'Mkoani',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function regions(): array
    {
        return array_keys(self::regionsWithDistricts());
    }

    /**
     * @return list<string>
     */
    public static function districtsFor(?string $region): array
    {
        if ($region === null || $region === '') {
            return [];
        }

        return self::regionsWithDistricts()[$region] ?? [];
    }

    public static function isValidRegion(string $region): bool
    {
        return array_key_exists($region, self::regionsWithDistricts());
    }

    public static function isValidDistrict(string $region, string $district): bool
    {
        return in_array($district, self::districtsFor($region), true);
    }

    /**
     * @return array{region: list<string>, district: list<string>}
     */
    public static function validationRules(): array
    {
        $regions = self::regions();

        return [
            'region' => ['required', 'string', 'in:'.implode(',', $regions)],
            'district' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $region = request()->input('region');
                    if (! is_string($region) || ! is_string($value) || ! self::isValidDistrict($region, $value)) {
                        $fail(__('The selected district is invalid for the chosen region.'));
                    }
                },
            ],
        ];
    }
}
