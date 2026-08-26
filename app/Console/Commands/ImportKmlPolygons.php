<?php

namespace App\Console\Commands;

use App\Models\FlyingLocation;
use Illuminate\Console\Command;

class ImportKmlPolygons extends Command
{
    protected $signature = 'kml:import';

    protected $description = 'Import KML polygons';

    public function handle()
    {
        $path = public_path('parapente-borders.kml');

        $xml = simplexml_load_file($path);

        $xml->registerXPathNamespace(
            'kml',
            'http://www.opengis.net/kml/2.2'
        );

        $placemarks = $xml->xpath('//kml:Placemark');

        $groups = [];

 foreach ($placemarks as $placemark) {

    $name = trim((string) $placemark->name);

    $baseName = preg_replace('/\d+$/', '', $name);

    $polygonNode = $placemark->xpath(
        './/kml:Polygon//kml:coordinates'
    );

    // Skip BAROUK1, BAROUK2, BAROUK3, BAROUK4...
    if (!$polygonNode) {
        continue;
    }

    $points = preg_split(
        '/\s+/',
        trim((string) $polygonNode[0])
    );

    $polygon = [];

    foreach ($points as $point) {

        $parts = explode(',', trim($point));

        if (count($parts) < 2) {
            continue;
        }

        $polygon[] = [
            'lat' => (float) $parts[1],
            'lng' => (float) $parts[0],
        ];
    }

    if (count($polygon) < 3) {
        continue;
    }

    $groups[$baseName][] = $polygon;
}

        $map = [

            'CEDAR' => 'ghab-alarz-1',
            'MIZIARA' => 'mzyar',
            'DEDDE' => 'ddy',
            'LASSA' => 'lasa',
            '3YOUNSIMAN' => 'aayon-alsyman-alzaaror',
            'DONNIYE' => 'syr-aldny',
            'AANNAYA' => 'aanaya-kfr-baaal-1',
            'EHDEN' => 'zghrta-ahdn',
            'HAMMANA' => 'hmana',
            'BAROUK' => 'albarok',
            'FALOUGHA' => 'falogha',
            'AQEIBE' => 'alaakyb-nhr-abrahym',
            'JBAA' => 'gbaaa',
            'TERBOL' => 'terbol',
            'DEIR EL QAMAR' => 'dyr-alkmr-aabyh-kfrmt',
            'JOUNIEH' => 'mntk-gony',
            'JBEIL' => 'gbyl',
        ];

        foreach ($map as $kmlName => $slug) {

            if (!$slug || !isset($groups[$kmlName])) {
                continue;
            }

            $location = FlyingLocation::where(
                'slug',
                $slug
            )->first();

            if (!$location) {
                continue;
            }

            $location->update([
                'kml_polygon' => $groups[$kmlName],
            ]);

            $this->info(
                "{$location->name} imported."
            );
        }

        $this->info('Done.');
    }
}