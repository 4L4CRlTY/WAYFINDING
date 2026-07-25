<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Landuse;
use App\Models\IndoorRoom;
use App\Models\DestinationKeyword;
use Illuminate\Http\Request;

class DestinationKeywordController extends Controller
{
    public function DestinationKeyword()
    {
        $buildings = Building::select('id', 'name')
            ->orderBy('name')
            ->get();

        $landuses = Landuse::select('id', 'name')
            ->orderBy('name')
            ->get();

        $rooms = IndoorRoom::with('indoorMap.building')
            ->orderBy('name')
            ->get();

        $keywords = DestinationKeyword::orderByDesc('id')->paginate(10);

        return view('admin.Destination.Destination_keyword', compact(
            'buildings',
            'landuses',
            'rooms',
            'keywords'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination_type' => 'required|in:building,landuse,room',
            'destination_id' => 'required|integer',
            'keywords' => 'required|string',
            'priority' => 'nullable|integer|in:1,2,3',
        ]);

        $destinationType = $request->destination_type;
        $destinationId = (int) $request->destination_id;
        $priority = (int) ($request->priority ?? 1);

        if (!$this->destinationExists($destinationType, $destinationId)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Selected destination does not exist.');
        }

        $rawKeywords = explode(',', $request->keywords);
        $savedCount = 0;

        foreach ($rawKeywords as $keyword) {
            $cleanKeyword = trim($keyword);

            if ($cleanKeyword === '') {
                continue;
            }

            $alreadyExists = DestinationKeyword::whereRaw('LOWER(keyword) = ?', [mb_strtolower($cleanKeyword)])
                ->where('destination_type', $destinationType)
                ->where('destination_id', $destinationId)
                ->exists();

            if ($alreadyExists) {
                continue;
            }

            DestinationKeyword::create([
                'keyword' => $cleanKeyword,
                'destination_type' => $destinationType,
                'destination_id' => $destinationId,
                'priority' => $priority,
                'is_active' => true,
            ]);

            $savedCount++;
        }

        if ($savedCount === 0) {
            return redirect()
                ->back()
                ->with('error', 'No new keyword was saved. Possible duplicate or empty input.');
        }

        return redirect()
            ->route('admin.destination-keyword')
            ->with('success', $savedCount . ' keyword(s) saved successfully.');
    }

    public function destroy(DestinationKeyword $destinationKeyword)
    {
        $destinationKeyword->delete();

        return redirect()
            ->route('admin.destination-keyword')
            ->with('success', 'Keyword deleted successfully.');
    }

    private function destinationExists(string $type, int $id): bool
    {
        return match ($type) {
            'building' => Building::where('id', $id)->exists(),
            'landuse' => Landuse::where('id', $id)->exists(),
            'room' => IndoorRoom::where('id', $id)->exists(),
            default => false,
        };
    }
}
