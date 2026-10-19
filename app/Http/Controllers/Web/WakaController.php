<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Room;
use App\Models\Schedule;
use App\Models\SchoolClass;
use App\Models\SystemConfiguration;
use App\Models\User;
use App\Services\JournalReminderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;


class WakaController extends Controller
{
    /**
     * Dashboard Waka Kurikulum
     */
    public function dashboard(JournalReminderService $service)
    {
        $configuration = SystemConfiguration::first() ?? SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        $lateTeachers = $service->overdueFor();
        $teacherCount = User::whereHas('role', fn($q) => $q->where('role_name', 'Guru'))->count();
        $studentCount = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))->count();

        return view('dashboards.waka', compact('configuration', 'lateTeachers', 'teacherCount', 'studentCount'));
    }

    /**
     * Halaman Khusus Pengelolaan Siklus Blok & Kuncian Jadwal
     */
    public function cycleIndex()
    {
        $configuration = SystemConfiguration::first() ?? SystemConfiguration::create([
            'active_cycle' => 'Putaran 1',
            'is_schedule_locked' => false,
        ]);

        return view('waka.cycle.index', compact('configuration'));
    }

    /**
     * Toggle lock jadwal
     */
    public function toggleLock()
    {
        $config = SystemConfiguration::first();
        if ($config) {
            $config->update([
                'is_schedule_locked' => !$config->is_schedule_locked,
                'updated_by' => auth()->id(),
            ]);
            $status = $config->is_schedule_locked ? 'dikunci' : 'dibuka';
            return redirect()->back()->with('success', "Status jadwal berhasil $status!");
        }

        return redirect()->back()->with('error', 'Konfigurasi sistem tidak ditemukan.');
    }

    /**
     * Toggle putaran aktif
     */
    public function toggleCycle()
    {
        $config = SystemConfiguration::first();
        if ($config) {
            $newCycle = $config->active_cycle === 'Putaran 1' ? 'Putaran 2' : 'Putaran 1';
            $config->update([
                'active_cycle' => $newCycle,
                'updated_by' => auth()->id(),
            ]);
            return redirect()->back()->with('success', "Putaran aktif berhasil diubah menjadi $newCycle!");
        }

        return redirect()->back()->with('error', 'Konfigurasi sistem tidak ditemukan.');
    }

    /**
     * Jadwal Pelajaran (Input Jadwal Mandiri)
     */
    public function schedulesIndex(Request $request)
    {
        $config = SystemConfiguration::current();
        $query = Schedule::with(['teacher', 'schoolClass', 'room']);

        if ($request->filled('cycle_type')) {
            $query->where('cycle_type', $request->cycle_type);
        } else {
            $query->where('cycle_type', $config->active_cycle);
        }

        if ($request->filled('day_of_week')) {
            $query->where('day_of_week', $request->day_of_week);
        }

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('teacher_id')) {
            $query->where('teacher_id', $request->teacher_id);
        }

        $schedules = $query->orderByRaw("CASE day_of_week WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3 WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 END")
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $teachers = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas']))->orderBy('name')->get();
        $classes = SchoolClass::orderBy('class_name')->get();
        $rooms = Room::orderBy('room_name')->get();

        return view('waka.schedules.index', compact('schedules', 'teachers', 'classes', 'rooms', 'config'));
    }

    public function schedulesStore(Request $request)
    {
        $validated = $request->validate([
            'teacher_id'  => ['required', 'exists:users,id'],
            'class_id'    => ['required', 'exists:classes,id'],
            'room_id'     => ['required', 'exists:rooms,id'],
            'subject'     => ['required', 'string', 'max:100'],
            'day_of_week' => ['required', 'in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu'],
            'start_time'  => ['required', 'date_format:H:i'],
            'end_time'    => ['required', 'date_format:H:i', 'after:start_time'],
            'cycle_type'  => ['required', 'in:Putaran 1,Putaran 2'],
        ]);

        Schedule::create($validated);

        return redirect()->route('waka.schedules.index')->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function schedulesDestroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->route('waka.schedules.index')->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }

    /**
     * Master Data: Guru
     */
    public function teachersIndex(Request $request)
    {
        $query = User::whereHas('role', fn($q) => $q->whereIn('role_name', ['Guru', 'Wali Kelas']))
            ->with(['role', 'homeroomClass'])
            ->withCount('schedules');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $teachers = $query->orderBy('name')->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('class_name')->get();
        $roles = Role::whereIn('role_name', ['Guru', 'Wali Kelas'])->get();

        return view('waka.teachers.index', compact('teachers', 'classes', 'roles'));
    }

    public function teachersStore(Request $request)
    {
        $validated = $request->validate([
            'name'              => ['required', 'string', 'max:255'],
            'username'          => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'             => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'          => ['required', 'string', 'min:6'],
            'role_id'           => ['required', 'exists:roles,id'],
            'homeroom_class_id' => ['nullable', 'exists:classes,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil ditambahkan.');
    }

    public function teachersDestroy(User $teacher)
    {
        if ($teacher->id === auth()->id()) {
            return redirect()->back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $teacher->delete();

        return redirect()->route('waka.teachers.index')->with('success', 'Data guru berhasil dihapus.');
    }

    /**
     * Master Data: Siswa
     */
    public function studentsIndex(Request $request)
    {
        $query = User::whereHas('role', fn($q) => $q->where('role_name', 'Siswa'))
            ->with(['schoolClass', 'parent']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $students = $query->orderBy('name')->paginate(15)->withQueryString();
        $classes = SchoolClass::orderBy('class_name')->get();
        $studentRole = Role::where('role_name', 'Siswa')->first();

        return view('waka.students.index', compact('students', 'classes', 'studentRole'));
    }

    public function studentsStore(Request $request)
    {
        $studentRole = Role::where('role_name', 'Siswa')->firstOrFail();

        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:255'],
            'username'  => ['required', 'string', 'max:50', 'unique:users,username'],
            'email'     => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:6'],
            'class_id'  => ['required', 'exists:classes,id'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role_id']  = $studentRole->id;

        User::create($validated);

        return redirect()->route('waka.students.index')->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function studentsDestroy(User $student)
    {
        $student->delete();
        return redirect()->route('waka.students.index')->with('success', 'Data siswa berhasil dihapus.');
    }

    /**
     * Master Data: Kelas
     */
    public function classesIndex()
    {
        $classes = SchoolClass::withCount('users')
            ->with(['homeroomTeachers'])
            ->orderBy('class_name')
            ->paginate(15);

        return view('waka.classes.index', compact('classes'));
    }

    public function classesStore(Request $request)
    {
        $validated = $request->validate([
            'class_name' => ['required', 'string', 'max:50', 'unique:classes,class_name'],
        ]);

        SchoolClass::create($validated);

        return redirect()->route('waka.classes.index')->with('success', 'Kelas baru berhasil ditambahkan.');
    }

    public function classesDestroy(SchoolClass $schoolClass)
    {
        if ($schoolClass->users()->exists()) {
            return redirect()->back()->with('error', 'Kelas tidak dapat dihapus karena masih memiliki siswa/pengguna.');
        }

        $schoolClass->delete();

        return redirect()->route('waka.classes.index')->with('success', 'Kelas berhasil dihapus.');
    }

    /**
     * Master Data: Ruang
     */
    public function roomsIndex()
    {
        $rooms = Room::withCount('schedules')
            ->orderBy('room_name')
            ->paginate(15);

        return view('waka.rooms.index', compact('rooms'));
    }

    public function roomsStore(Request $request)
    {
        $validated = $request->validate([
            'room_name' => ['required', 'string', 'max:50', 'unique:rooms,room_name'],
        ]);

        Room::create($validated);

        return redirect()->route('waka.rooms.index')->with('success', 'Ruangan baru berhasil ditambahkan.');
    }

    public function roomsDestroy(Room $room)
    {
        if ($room->schedules()->exists()) {
            return redirect()->back()->with('error', 'Ruangan tidak dapat dihapus karena terikat jadwal pelajaran.');
        }

        $room->delete();

        return redirect()->route('waka.rooms.index')->with('success', 'Ruangan berhasil dihapus.');
    }
}
