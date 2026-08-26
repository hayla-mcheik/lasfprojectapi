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

        if (!file_exists($path)) {
            $this->error("KML file not found: {$path}");
            return self::FAILURE;
        }

        $xml = simplexml_load_file($path);

        if (!$xml) {
            $this->error('Could not load KML file.');
            return self::FAILURE;
        }

        $xml->registerXPathNamespace(
            'kml',
            'http://www.opengis.net/kml/2.2'
        );

        $placemarks = $xml->xpath('//kml:Placemark');

        if (!$placemarks) {
            $this->error('No Placemark elements found.');
            return self::FAILURE;
        }

        $groups = [];

        foreach ($placemarks as $placemark) {

            $name = trim((string) $placemark->name);

            /*
            |--------------------------------------------------------------------------
            | Normalize KML name
            |--------------------------------------------------------------------------
            |
            | Examples:
            |
            | TERBOL
            | TERBOL 1
            | TERBOL 2
            | TERBOL 3
            |
            | All become:
            |
            | TERBOL
            |
            */

            $baseName = preg_replace(
                '/\s*\d+$/',
                '',
                $name
            );

            $baseName = strtoupper(
                trim($baseName)
            );

            $this->line(
                "KML: {$name} -> Group: {$baseName}"
            );

            /*
            |--------------------------------------------------------------------------
            | Find polygon coordinates
            |--------------------------------------------------------------------------
            */

            $polygonNode = $placemark->xpath(
                './/kml:Polygon//kml:coordinates'
            );

            if (!$polygonNode) {
                $this->warn(
                    "No polygon found for {$name}"
                );

                continue;
            }

            $points = preg_split(
                '/\s+/',
                trim((string) $polygonNode[0])
            );

            $polygon = [];

            foreach ($points as $point) {

                $parts = explode(
                    ',',
                    trim($point)
                );

                if (count($parts) < 2) {
                    continue;
                }

                $polygon[] = [
                    'lat' => (float) $parts[1],
                    'lng' => (float) $parts[0],
                ];
            }

            if (count($polygon) < 3) {

                $this->warn(
                    "Polygon for {$name} has fewer than 3 points."
                );

                continue;
            }

            $groups[$baseName][] = $polygon;
        }

        /*
        |--------------------------------------------------------------------------
        | KML name => database slug
        |--------------------------------------------------------------------------
        */

        $map = [

            'CEDAR' =>
                'ghab-alarz-1',

            'MIZIARA' =>
                'mzyar',

            'DEDDE' =>
                'ddy',

            'LASSA' =>
                'lasa',

            '3YOUNSIMAN' =>
                'aayon-alsyman-alzaaror',

            'DONNIYE' =>
                'syr-aldny',

            'AANNAYA' =>
                'aanaya-kfr-baaal-1',

            'EHDEN' =>
                'zghrta-ahdn',

            'HAMMANA' =>
                'hmana',

            'BAROUK' =>
                'albarok',

            'FALOUGHA' =>
                'falogha',

            'AQEIBE' =>
                'alaakyb-nhr-abrahym',

            'JBAA' =>
                'gbaaa',

            'TERBOL' =>
                'terbol',

            'DEIR EL QAMAR' =>
                'dyr-alkmr-aabyh-kfrmt',

            'JOUNIEH' =>
                'mntk-gony',

            'JBEIL' =>
                'gbyl',
        ];

        /*
        |--------------------------------------------------------------------------
        | Import polygons
        |--------------------------------------------------------------------------
        */

        foreach ($map as $kmlName => $slug) {

            if (!isset($groups[$kmlName])) {

                $this->warn(
                    "No KML polygon group found for {$kmlName}"
                );

                continue;
            }

            $location = FlyingLocation::where(
                'slug',
                $slug
            )->first();

            if (!$location) {

                $this->warn(
                    "Location not found for slug: {$slug}"
                );

                continue;
            }

            $location->update([
                'kml_polygon' => $groups[$kmlName],
            ]);

            $this->info(
                "{$location->name} imported successfully."
            );

            $this->info(
                'Polygon count: ' .
                count($groups[$kmlName])
            );
        }

        $this->info('KML import completed.');

        return self::SUCCESS;
    }
}