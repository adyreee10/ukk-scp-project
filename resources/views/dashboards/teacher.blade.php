<x-app-layout>
    <!-- KODE ASLI DARI FASE 14: Pola card dan tabel responsif -->
    <section class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
        <div class="mb-3 flex items-center justify-between gap-3">
            <h2 class="font-semibold">Jadwal Hari Ini</h2>
            @unless ($configuration->is_schedule_locked)
                <a class="inline-flex min-h-11 items-center justify-center rounded-lg bg-indigo-600 px-4 text-white font-medium"
                   href="#">Tambah</a>
            @endunless
        </div>

        <!-- Tampilan Card (Khusus Layar Sempit / HP) -->
        <div class="space-y-3 md:hidden">
            @forelse ($schedules as $schedule)
                <article class="rounded-lg border p-3">
                    <p class="font-semibold">{{ $schedule->subject }}</p>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $schedule->day_of_week }},
                        {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}
                    </p>
                    <p class="text-sm text-slate-600">
                        {{ $schedule->schoolClass->class_name }} · {{ $schedule->room->room_name }}
                    </p>
                </article>
            @empty
                <p class="text-sm text-slate-500 text-center py-4">Belum ada jadwal. Silakan tambah.</p>
            @endforelse
        </div>

        <!-- Tampilan Tabel (Khusus Layar Lebar / PC Tablet) -->
        <div class="hidden overflow-x-auto md:block">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead>
                    <tr class="text-left">
                        <th class="p-3 font-semibold text-slate-700">Mapel</th>
                        <th class="p-3 font-semibold text-slate-700">Hari/Jam</th>
                        <th class="p-3 font-semibold text-slate-700">Kelas</th>
                        <th class="p-3 font-semibold text-slate-700">Ruang</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $schedule)
                        <tr>
                            <td class="p-3 font-medium text-slate-900">{{ $schedule->subject }}</td>
                            <td class="p-3 text-slate-600">{{ $schedule->day_of_week }}, {{ substr($schedule->start_time, 0, 5) }}–{{ substr($schedule->end_time, 0, 5) }}</td>
                            <td class="p-3 text-slate-600">{{ $schedule->schoolClass->class_name }}</td>
                            <td class="p-3 text-slate-600">{{ $schedule->room->room_name }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-3 text-center text-slate-500 py-4">Belum ada jadwal. Silakan tambah.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-app-layout>
