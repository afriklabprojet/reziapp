<?php

declare(strict_types=1);

namespace App\Models\Concerns;

trait HasGeoSearch
{
    public function distanceFrom(float $lat, float $lng): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat);
        $lngFrom = deg2rad($lng);
        $latTo = deg2rad($this->latitude);
        $lngTo = deg2rad($this->longitude);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lngDelta / 2), 2)
        ));

        return $angle * $earthRadius;
    }

    /**
     * MySQL: MBRContains(<bbox>) drives the SPATIAL INDEX, then ST_Distance_Sphere refines.
     * SQLite: Haversine via bounding-box (no spatial functions available).
     */
    public function scopeWithinRadius($query, float $lat, float $lng, int $radius, bool $sortByDistance = true)
    {
        $latDelta = $radius / 111320;
        $lngDelta = $radius / (111320 * cos(deg2rad($lat)));

        $minLat = $lat - $latDelta;
        $maxLat = $lat + $latDelta;
        $minLng = $lng - $lngDelta;
        $maxLng = $lng + $lngDelta;

        if ($query->getConnection()->getDriverName() === 'sqlite') {
            return $this->scopeWithinRadiusSqlite(
                $query, $lat, $lng, $radius,
                $minLat, $maxLat, $minLng, $maxLng, $sortByDistance,
            );
        }

        // Use sprintf to guarantee locale-independent pure-decimal output
        $bboxWkt = sprintf(
            'POLYGON((%.10f %.10f,%.10f %.10f,%.10f %.10f,%.10f %.10f,%.10f %.10f))',
            $minLng, $minLat, $maxLng, $minLat, $maxLng, $maxLat, $minLng, $maxLat, $minLng, $minLat,
        );
        $bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
        $ptExpr = sprintf("ST_GeomFromText('POINT(%.10f %.10f)', 4326)", $lng, $lat);

        $query = $query
            ->whereRaw("MBRContains({$bboxExpr}, location)")
            ->whereRaw("ST_Distance_Sphere(location, {$ptExpr}) <= ?", [$radius])
            ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters");

        if ($sortByDistance) {
            $query->orderBy('distance_meters', 'asc');
        }

        return $query;
    }

    /**
     * MySQL: 50 km bounding box drives the SPATIAL INDEX, then sorted by distance.
     * SQLite: Haversine full scan (acceptable in test environments with small datasets).
     */
    public function scopeNearestTo($query, float $lat, float $lng, int $limit = 20)
    {
        if ($query->getConnection()->getDriverName() === 'sqlite') {
            return $this->scopeNearestToSqlite($query, $lat, $lng, $limit);
        }

        $nearbyRadiusMeters = 50000;
        $latDelta = $nearbyRadiusMeters / 111320;
        $lngDelta = $nearbyRadiusMeters / (111320 * cos(deg2rad($lat)));

        $bboxWkt = sprintf(
            'POLYGON((%.10f %.10f,%.10f %.10f,%.10f %.10f,%.10f %.10f,%.10f %.10f))',
            $lng - $lngDelta, $lat - $latDelta,
            $lng + $lngDelta, $lat - $latDelta,
            $lng + $lngDelta, $lat + $latDelta,
            $lng - $lngDelta, $lat + $latDelta,
            $lng - $lngDelta, $lat - $latDelta,
        );
        $bboxExpr = "ST_GeomFromText('{$bboxWkt}', 4326)";
        $ptExpr = sprintf("ST_GeomFromText('POINT(%.10f %.10f)', 4326)", $lng, $lat);

        return $query
            ->whereRaw("MBRContains({$bboxExpr}, location)")
            ->selectRaw("*, ST_Distance_Sphere(location, {$ptExpr}) AS distance_meters")
            ->orderBy('distance_meters', 'asc')
            ->limit($limit);
    }

    private function scopeWithinRadiusSqlite(
        $query,
        float $lat,
        float $lng,
        int $radius,
        float $minLat,
        float $maxLat,
        float $minLng,
        float $maxLng,
        bool $sortByDistance,
    ) {
        $distanceExpr = $this->haversineExpr($lat, $lng);

        $query = $query
            ->whereBetween('latitude', [$minLat, $maxLat])
            ->whereBetween('longitude', [$minLng, $maxLng])
            ->whereRaw("{$distanceExpr} <= ?", [$radius])
            ->selectRaw("*, {$distanceExpr} AS distance_meters");

        if ($sortByDistance) {
            $query->orderBy('distance_meters', 'asc');
        }

        return $query;
    }

    private function scopeNearestToSqlite($query, float $lat, float $lng, int $limit)
    {
        $distanceExpr = $this->haversineExpr($lat, $lng);

        return $query
            ->selectRaw("*, {$distanceExpr} AS distance_meters")
            ->orderBy('distance_meters', 'asc')
            ->limit($limit);
    }

    private function haversineExpr(float $lat, float $lng): string
    {
        $earthRadius = 6371000;
        $radConst = 0.017453293;
        $cosProduct = "cos({$lat} * {$radConst}) * cos(latitude * {$radConst}) *
                        cos(longitude * {$radConst} - ({$lng}) * {$radConst}) +
                        sin({$lat} * {$radConst}) * sin(latitude * {$radConst})";

        return "({$earthRadius} * acos(
            CASE
                WHEN ({$cosProduct}) > 1.0 THEN 1.0
                WHEN ({$cosProduct}) < -1.0 THEN -1.0
                ELSE ({$cosProduct})
            END
        ))";
    }
}
