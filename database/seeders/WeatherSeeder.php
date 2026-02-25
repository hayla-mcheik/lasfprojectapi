<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeatherForecast;
use App\Models\PrecipitationStat;

class WeatherSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create the Main Report
        $forecast = WeatherForecast::create([
            'forecast_date' => '2026-02-11',
            'day_name_ar' => 'الأربعاء',
            'general_situation_ar' => 'طقس متقلب وماطر أحياناً يسيطر على لبنان والحوض الشرقي للمتوسط، يستقر تدريجياً صباح يوم الخميس.',
            'daily_description_ar' => 'غائم جزئياً إلى غائم أحياناً مع ضباب على المرتفعات وانخفاض بدرجات الحرارة.',
            'sea_state_ar' => 'مائج إلى هائج',
            'water_temp_ar' => '19°C',
            'pressure_hpa' => '1016',
            'sunrise' => '06:27',
            'sunset' => '17:18',
'daily_details' => [
            'day_1' => 'الخميس: غائم جزئياً مع ضباب على المرتفعات...',
            'day_2' => 'الجمعة: غائم جزئياً أحياناً بسحب مرتفعة...',
            'day_3' => 'السبت: غائم مع انخفاض ملموس بدرجات الحرارة...'
        ],
        ]);

        // 2. Add Regional Temperatures from the Image Table
        $forecast->regionalTemperatures()->createMany([
            ['region_type_ar' => 'الساحل', 'city_name_ar' => 'بيروت', 'temp_range' => '19/13'],
            ['region_type_ar' => 'الساحل', 'city_name_ar' => 'طرابلس', 'temp_range' => '19/12'],
            ['region_type_ar' => 'الجبال', 'city_name_ar' => 'الأرز', 'temp_range' => '11/6'],
            ['region_type_ar' => 'الداخل', 'city_name_ar' => 'زحلة', 'temp_range' => '14/9'],
        ]);

        // 3. Add Precipitation Stats (Bottom Table)
        PrecipitationStat::create([
            'station_name_ar' => 'بيروت',
            'last_24_hours' => '7.7 ملم',
            'accumulated_total' => '316.5 ملم',
            'previous_year_total' => '518 ملم',
            'yearly_average' => '825 ملم'
        ]);
    }
}