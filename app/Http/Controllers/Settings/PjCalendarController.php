<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\PjCalendar;
use App\Models\Settings\PjCalendarItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PjCalendarController extends Controller
{
    public function index()
    {
        $this->authorize('access', 'pj-calendar.index');

        return to_json([
            'collection' => PjCalendar::with(['items', 'createdBy', 'updatedBy'])->filter(),
        ]);
    }

    public function create()
    {
        $this->authorize('access', 'pj-calendar.create');

        return to_json([
            'form' => $this->defaultForm(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('access', 'pj-calendar.create');

        $validated = $this->validatePayload($request);

        DB::beginTransaction();
        try {
            $calendar = PjCalendar::create([
                'year' => $validated['year'],
            ]);

            $this->syncItems($calendar, $validated['items']);

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $calendar->id,
                'message' => 'PJ calendar created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => 'PJ calendar creation failed',
            ], 500);
        }
    }

    public function show($id)
    {
        $this->authorize('access', 'pj-calendar.show');

        $item = PjCalendar::with(['items', 'createdBy', 'updatedBy'])->findOrFail($id);

        return to_json([
            'model' => $item,
        ]);
    }

    public function edit($id)
    {
        $this->authorize('access', 'pj-calendar.update');

        $item = PjCalendar::with('items')->findOrFail($id);

        return to_json([
            'form' => $item,
        ]);
    }

    public function update($id, Request $request)
    {
        $this->authorize('access', 'pj-calendar.update');

        $validated = $this->validatePayload($request, (int) $id);

        DB::beginTransaction();
        try {
            $calendar = PjCalendar::findOrFail($id);
            $calendar->update([
                'year' => $validated['year'],
            ]);

            PjCalendarItem::where('pj_calendar_id', $calendar->id)->delete();
            $this->syncItems($calendar, $validated['items']);

            DB::commit();

            return to_json([
                'saved' => true,
                'id' => $calendar->id,
                'message' => 'PJ calendar updated successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return to_json([
                'saved' => false,
                'message' => 'PJ calendar update failed',
            ], 500);
        }
    }

    public function destroy($id)
    {
        $this->authorize('access', 'pj-calendar.delete');

        try {
            $item = PjCalendar::findOrFail($id);
            $item->delete();

            return to_json([
                'deleted' => true,
                'id' => $item->id,
                'message' => 'PJ calendar deleted successfully',
            ]);
        } catch (\Exception $e) {
            return to_json([
                'deleted' => false,
                'message' => 'PJ calendar deletion failed',
            ], 500);
        }
    }

    private function defaultForm(): array
    {
        return [
            'year' => (int) date('Y'),
            'items' => [
                [
                    'label' => 'Period 1',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 2',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 3',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 4',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 5',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 6',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 7',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 8',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 9',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 10',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 11',
                    'weeks' => [],
                ],
                [
                    'label' => 'Period 12',
                    'weeks' => [],
                ],
            ],
        ];
    }

    private function validatePayload(Request $request, ?int $id = null): array
    {
        $yearUniqueRule = Rule::unique('pj_calendars', 'year');
        if ($id) {
            $yearUniqueRule->ignore($id);
        }

        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100', $yearUniqueRule],
            'items' => 'required|array|min:1',
            'items.*.label' => 'required|string|max:255',
            'items.*.weeks' => 'required|array|min:1',
            'items.*.weeks.*' => 'required|string|date_format:Y-m-d',
        ], [
            'year.unique' => 'PJ calendar already exists for this year',
        ]);

        $this->assertUniqueWeeks($validated['items']);

        return $validated;
    }

    private function assertUniqueWeeks(array $items): void
    {
        $seen = [];

        foreach ($items as $index => $item) {
            foreach ($item['weeks'] as $week) {
                if (in_array($week, $seen, true)) {
                    throw ValidationException::withMessages([
                        "items.{$index}.weeks" => ['Each week can only be assigned to one label.'],
                    ]);
                }

                $seen[] = $week;
            }
        }
    }

    private function syncItems(PjCalendar $calendar, array $items): void
    {
        $now = now();
        $insertItems = collect($items)->map(function ($item) use ($calendar, $now) {
            return [
                'pj_calendar_id' => $calendar->id,
                'label' => $item['label'],
                'weeks' => json_encode(array_values($item['weeks'])),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->all();

        PjCalendarItem::insert($insertItems);
    }

    public function search(Request $request)
    {
        $this->authorize('access', 'pj-calendar.index');

        $year = $request->input('year');

        $items = PjCalendar:: join('pj_calendar_items', 'pj_calendars.id', '=', 'pj_calendar_items.pj_calendar_id')
                ->where('year', 'like', $year)
                ->select('pj_calendar_items.id', 'pj_calendar_items.label')
                ->get();
        return to_json([
            'collection' => $items,
        ]);
    }
}
