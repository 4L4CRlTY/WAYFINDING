<?php

namespace App\Console\Commands;

use App\Models\Path;
use App\Models\Node;
use App\Models\Edge;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateGraphFromPaths extends Command
{
    protected $signature = 'graph:generate-from-paths';
    protected $description = 'Generate nodes and edges from paths table';

    public function handle()
    {
        DB::transaction(function () {
            Edge::query()->delete();
            Node::query()->delete();

            $paths = Path::all();

            foreach ($paths as $path) {
                $geometry = $path->geometry;

                if (!is_array($geometry) || !isset($geometry['type'], $geometry['coordinates'])) {
                    continue;
                }

                $coordinates = $geometry['coordinates'];

                if ($geometry['type'] === 'LineString') {
                    $this->createEdgeFromLineString($path, $coordinates);
                } elseif ($geometry['type'] === 'MultiLineString') {
                    foreach ($coordinates as $lineCoords) {
                        $this->createEdgeFromLineString($path, $lineCoords);
                    }
                }
            }
        });

        $this->info('Nodes and edges generated successfully from paths.');
        return Command::SUCCESS;
    }

    private function createEdgeFromLineString($path, array $coordinates): void
    {
        if (count($coordinates) < 2) {
            return;
        }

        $start = $coordinates[0];
        $end = $coordinates[count($coordinates) - 1];

        // GeoJSON = [lng, lat]
        $startLng = (float) $start[0];
        $startLat = (float) $start[1];
        $endLng = (float) $end[0];
        $endLat = (float) $end[1];

        $startNode = $this->findOrCreateNode($startLat, $startLng);
        $endNode = $this->findOrCreateNode($endLat, $endLng);

        $distance = $this->calculatePolylineDistance($coordinates);

        Edge::create([
            'path_id' => $path->id,
            'start_node_id' => $startNode->id,
            'end_node_id' => $endNode->id,
            'distance' => $distance,
            'type' => $path->type ?? 'walkway',
            'risk_level' => $path->risk_level ?? 1,
            'difficulty_level' => $path->difficulty_level ?? 1,
            'is_blocked' => (bool) ($path->is_blocked ?? false),
        ]);
    }

    private function findOrCreateNode(float $lat, float $lng): Node
    {
        // rounded para dili duplicate tungod sa tiny decimal differences
        $roundedLat = round($lat, 7);
        $roundedLng = round($lng, 7);

        $existing = Node::where('latitude', $roundedLat)
            ->where('longitude', $roundedLng)
            ->first();

        if ($existing) {
            return $existing;
        }

        return Node::create([
            'name' => null,
            'latitude' => $roundedLat,
            'longitude' => $roundedLng,
        ]);
    }

    private function calculatePolylineDistance(array $coordinates): float
    {
        $total = 0.0;

        for ($i = 0; $i < count($coordinates) - 1; $i++) {
            $lng1 = (float) $coordinates[$i][0];
            $lat1 = (float) $coordinates[$i][1];
            $lng2 = (float) $coordinates[$i + 1][0];
            $lat2 = (float) $coordinates[$i + 1][1];

            $total += $this->haversine($lat1, $lng1, $lat2, $lng2);
        }

        return $total;
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
