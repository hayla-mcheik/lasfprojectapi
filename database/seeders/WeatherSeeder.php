<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WeatherForecast;
use App\Models\RegionalTemperature;
use App\Models\PrecipitationStat;

class WeatherSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create the Main Weather Forecast
        $forecast = WeatherForecast::create([
            'forecast_date' => '2026-02-11',
            'day_name_ar' => 'الأربعاء',
            'general_situation_ar' => 'طقس متقلب وماطر أحياناً يسيطر على لبنان والحوض الشرقي للمتوسط، يستقر تدريجياً صباح يوم الخميس.',
            'daily_description_ar' => 'غائم جزئياً إلى غائم أحياناً مع ضباب على المرتفعات وانخفاض بدرجات الحرارة.',
            'daily_details' => [
                'day_1' => 'الخميس: غائم جزئياً مع ضباب على المرتفعات واحتمال تساقط أمطار محلية صباحاً.',
                'day_2' => 'الجمعة: غائم جزئياً أحياناً بسحب مرتفعة مع ارتفاع ملموس بدرجات الحرارة.',
                'day_3' => 'السبت: طقس غائم مع انخفاض ملموس بدرجات الحرارة وضباب كثيف وتساقط أمطار غزيرة.'
            ],
            'sea_state_ar' => 'مائج إلى هائج',
            'water_temp_ar' => '19°C',
            'pressure_hpa' => '1016',
            'sunrise' => '06:27',
            'sunset' => '17:18',
            'surface_winds_ar' => 'جنوبية غربية ناشطة، سرعتها بين ١٥ و٤٠ كم/س',
            'visibility_ar' => 'متوسط إلى سيء على المرتفعات بسبب الضباب',
            'humidity_range' => '٦٠ - ٨٥ %'
        ]);

        // 2. Seed Regional Temperatures (Linked via weather_forecast_id)
        $forecast->regionalTemperatures()->createMany([
            ['region_type_ar' => 'الساحل', 'city_name_ar' => 'بيروت', 'temp_range' => '19/13'],
            ['region_type_ar' => 'الساحل', 'city_name_ar' => 'طرابلس', 'temp_range' => '19/12'],
            ['region_type_ar' => 'الجبال', 'city_name_ar' => 'الأرز', 'temp_range' => '11/6'],
            ['region_type_ar' => 'الداخل', 'city_name_ar' => 'زحلة', 'temp_range' => '14/9'],
        ]);

        // 3. Seed Precipitation Stats (Linked via weather_forecast_id)
        // This ensures the rows appear in the Edit Modal
        $forecast->precipitationStats()->createMany([
            [
                'station_name_ar' => 'طرابلس', 
                'last_24_hours' => '٧.٧ ملم', 
                'accumulated_total' => '٣٩١.٩ ملم', 
                'previous_year_total' => '٥١١.٣ ملم', 
                'yearly_average' => '٥٨٠ ملم'
            ],
            [
                'station_name_ar' => 'بيروت', 
                'last_24_hours' => '٧.٧ ملم', 
                'accumulated_total' => '٣١٦.٥ ملم', 
                'previous_year_total' => '٤٢٨ ملم', 
                'yearly_average' => '٥٤٠ ملم'
            ],
            [
                'station_name_ar' => 'زحلة', 
                'last_24_hours' => '٠.٢ ملم', 
                'accumulated_total' => '٢٠٧.٤ ملم', 
                'previous_year_total' => '٢١٦.٢ ملم', 
                'yearly_average' => '٤٥١ ملم'
            ],
        ]);
    }
}