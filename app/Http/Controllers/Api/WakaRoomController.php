<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WakaRoomController extends Controller
{
    public function index()
    {
        return response()->json([
            'data' => Room::orderBy('room_name')->paginate(20)
        ]);
    }

    public function store (Request $request)
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:50', 'unique:rooms,room_name'],
        ]);

        return response()->json([
            'message' => 'Ruang berhasil dibuat.',
            'data' => Room::create($data),
        ], 201);
    }

    public function update (Request $request, Room $room)
    {
        $data = $request->validate([
            'room_name' => ['required', 'string', 'max:50',
            Rule::unique('rooms', 'room_name')->ignore($room->id),
            ],
        ]);
        $room->update($data);

        return response()->json([
            'message' => 'Ruang berhasil diperbarui.',
            'data' => $room
        ]);
    }

    public function destroy (Room $room)
    {
        if ($room->schedules()->exists()
        ){
            return response()->json([
                'message' => 'Ruang masih dipakai dan tidak dapat dihapus.'
            ], 409);
        }

        $room->delete();
        return response()->noContent();
    }
}
