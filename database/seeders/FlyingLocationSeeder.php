<?php

namespace Database\Seeders;

use App\Models\FlyingLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class FlyingLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // NOTE: Truncate has been removed to preserve existing database records.
        // This seeder will now update existing records based on the slug or create new ones.

        $locations = [
            [
                'type' => 'PARAPENTE',
                'name' => 'غابة الأرز (1)',
                'takeoff_kato' => '183076', 'takeoff_nazim' => '254568',
                'landing_kato' => '183115', 'landing_nazim' => '251143',
                'boundaries_kato' => [['lat' => 34.26, 'lng' => 36.06], '181126', '184212', '186331', '182168'],
                'boundaries_nazim' => ['253511', '256768', '248086', '246906'],
                'max_altitude' => '1000قدم وما دون فوق سطح الأرض',
                'map_image' => 'sharaai/cedars_1.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'غابة الأرز (2)',
                'takeoff_kato' => '181200', 'takeoff_nazim' => '252138',
                'landing_kato' => '182727', 'landing_nazim' => '249044',
                'boundaries_kato' => [['lat' => 34.22, 'lng' => 36.00]],
                'max_altitude' => '1000قدم وما دون فوق سطح الأرض',
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'مزيارة',
                'takeoff_kato' => '168951', 'takeoff_nazim' => '266876',
                'landing_kato' => '168218', 'landing_nazim' => '268057',
                'boundaries_kato' => [['lat' => 34.33, 'lng' => 35.93], '166124', '166845', '169725', '168164'],
                'boundaries_nazim' => ['267910', '270056', '267767', '265817'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/miziara.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'ددي',
                'takeoff_kato' => '157047', 'takeoff_nazim' => '271773',
                'landing_kato' => '156896', 'landing_nazim' => '272693',
                'boundaries_kato' => [['lat' => 34.38, 'lng' => 35.82], '154686', '157861', '159129', '155758'],
                'boundaries_nazim' => ['271686', '273886', '271649', '269975'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/deddeh.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'لاسا',
                'takeoff_kato' => '162418', 'takeoff_nazim' => '235028',
                'landing_kato' => '161425', 'landing_nazim' => '237307',
                'boundaries_kato' => [['lat' => 34.10, 'lng' => 35.86], '410160', '159851', '164290', '164602'],
                'boundaries_nazim' => ['234293', '238059', '238363', '235155'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/lassa.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'عيون السيمان - الزعرور',
                'takeoff_kato' => '159969', 'takeoff_nazim' => '226604',
                'landing_kato' => '156790', 'landing_nazim' => '224459',
                'boundaries_kato' => [['lat' => 34.00, 'lng' => 35.88], '157079', '155873', '162352', '162318'],
                'boundaries_nazim' => ['222760', '226075', '226870', '222835'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/faraya_zaarour.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'سير الضنية',
                'takeoff_kato' => '179594', 'takeoff_nazim' => '269744',
                'landing_kato' => '178762', 'landing_nazim' => '271212',
                'boundaries_kato' => [['lat' => 34.42, 'lng' => 36.08], '177610', '197690', '180228', '178455'],
                'boundaries_nazim' => ['270956', '271822', '269905', '269096'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/donnieh.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'عنايا - كفر بعال (1)',
                'takeoff_kato' => '153636', 'takeoff_nazim' => '242116',
                'landing_kato' => '153507', 'landing_nazim' => '240953',
                'boundaries_kato' => [['lat' => 34.12, 'lng' => 35.73], '152201', '154957', '157778', '153766'],
                'boundaries_nazim' => ['242182', '243155', '239970', '239077'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/annaya.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'زغرتا - إهدن',
                'takeoff_kato' => '174531', 'takeoff_nazim' => '261364',
                'landing_kato' => '171473', 'landing_nazim' => '261907',
                'boundaries_kato' => [['lat' => 34.28, 'lng' => 35.95], '170718', '171246', '175237', '174815'],
                'boundaries_nazim' => ['260175', '263186', '262238', '260581'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/ehden.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'حمانا',
                'takeoff_kato' => '151131', 'takeoff_nazim' => '207707',
                'landing_kato' => '150391', 'landing_nazim' => '208317',
                'boundaries_kato' => [['lat' => 33.83, 'lng' => 35.73], '148262', '151133', '152445', '150226'],
                'boundaries_nazim' => ['207804', '210469', '208877', '206817'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/hammana.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'الباروك',
                'takeoff_kato' => '146903', 'takeoff_nazim' => '193652',
                'landing_kato' => '143461', 'landing_nazim' => '193465',
                'boundaries_kato' => [['lat' => 33.70, 'lng' => 35.68], '141400', '145525', '151936', '149273'],
                'boundaries_nazim' => ['192884', '197775', '192927', '189088'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/barouk.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'فالوغا',
                'takeoff_kato' => '151983', 'takeoff_nazim' => '208897',
                'landing_kato' => '153400', 'landing_nazim' => '212404',
                'boundaries_kato' => [['lat' => 33.86, 'lng' => 35.78], '151282', '154316', '154563', '151345'],
                'boundaries_nazim' => ['208556', '208509', '212898', '212498'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/falougha.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'العقيبة - نهر إبراهيم',
                'takeoff_kato' => '143808', 'takeoff_nazim' => '235882',
                'landing_kato' => '142336', 'landing_nazim' => '236059',
                'boundaries_kato' => [['lat' => 34.03, 'lng' => 35.65], '141709', '141964', '144220', '144081'],
                'boundaries_nazim' => ['235423', '236866', '236828', '234674'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/okaibe.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'جباع',
                'takeoff_kato' => '130051', 'takeoff_nazim' => '170993',
                'landing_kato' => '127245', 'landing_nazim' => '172194',
                'boundaries_kato' => [['lat' => 33.50, 'lng' => 35.53], '126999', '127212', '132899', '131890'],
                'boundaries_nazim' => ['170291', '173460', '172433', '169431'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/jbaa.jpg'
            ],
            [
                'type' => 'PARAPENTE',
                'name' => 'دير القمر - عبيه - كفرمتى',
                'takeoff_kato' => '131809', 'takeoff_nazim' => '199658',
                'landing_kato' => '130529', 'landing_nazim' => '196950',
                'boundaries_kato' => [['lat' => 33.72, 'lng' => 35.53], '129938', '130907', '133142', '133056'],
                'boundaries_nazim' => ['196216', '199934', '199665', '195910'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/deir_qamar.jpg'
            ],
            [
                'type' => 'PARAPENTE + Para Motor + Hang Glider',
                'name' => 'منطقة جونية',
                'takeoff_kato' => '144641', 'takeoff_nazim' => '228649',
                'landing_kato' => '142050', 'landing_nazim' => '228801',
                'boundaries_kato' => [['lat' => 33.98, 'lng' => 35.61], '141307', '142220', '146700', '143159'],
                'boundaries_nazim' => ['228375', '230731', '230195', '227080'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/jounieh.jpg'
            ],
            [
                'type' => 'Para Motor',
                'name' => 'جبيل',
                'boundaries_kato' => [['lat' => 34.12, 'lng' => 35.64], '143300', '144766', '140945', '143103'],
                'boundaries_nazim' => ['240048', '240362', '244739', '245103'],
                'max_altitude' => 'مماثل لغابة الأرز',
                'map_image' => 'sharaai/byblos.jpg'
            ]
        ];

        foreach ($locations as $data) {
            // Generate slug based on the name
            $slug = Str::slug($data['name']);
            
            // updateOrCreate checks the slug. If found, it updates; if not, it creates.
            $location = FlyingLocation::updateOrCreate(
                ['slug' => $slug],
                $data
            );

            // Add Default Status ONLY if the location doesn't have one yet.
            if ($location->clearanceStatuses()->count() === 0) {
                $location->clearanceStatuses()->create([
                    'status' => 'green',
                    'reason' => 'مفتوح للطيران الجوي الروتيني',
                    'updated_by' => 1
                ]);
            }
        }
    }
}