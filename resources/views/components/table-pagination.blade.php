@props(['paginator'])

<div class="p-4 border-t border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs">
    <!-- Pilihan Jumlah Baris per Halaman (50, 100, 500, 1000) & Total Record -->
    <div class="flex items-center gap-3">
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" 
                    @click.away="open = false" 
                    type="button" 
                    class="table-rows-btn inline-flex items-center gap-2 px-3 py-1.5 rounded-xl border border-slate-700 bg-slate-900 text-slate-200 font-bold hover:bg-slate-800 transition cursor-pointer shadow-sm text-xs">
                <span>{{ request('per_page', 50) }} rows</span>
                <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <!-- Popover Menu Pilihan Jumlah Baris (50, 100, 500, 1000) Muncul Melayang ke Atas -->
            <div x-show="open" 
                 x-cloak 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-75"
                 x-transition:leave-start="transform opacity-100 scale-100"
                 x-transition:leave-end="transform opacity-0 scale-95"
                 class="table-rows-popover absolute bottom-full mb-1.5 left-0 w-36 rounded-xl bg-[#0f172a] border border-slate-800 shadow-2xl py-1 z-30">
                <div class="px-3 py-1 text-[10px] uppercase font-bold text-slate-500 border-b border-slate-800/80 mb-0.5">
                    Tampilkan Baris:
                </div>
                @foreach([50, 100, 500, 1000] as $opt)
                    <a href="{{ request()->fullUrlWithQuery(['per_page' => $opt, 'page' => 1]) }}" 
                       class="w-full text-left px-3 py-1.5 text-xs font-semibold hover:bg-indigo-600 hover:text-white transition cursor-pointer flex items-center justify-between {{ request('per_page', 50) == $opt ? 'is-active bg-indigo-600/30 text-indigo-300 font-bold' : 'text-slate-300' }}">
                        <span>{{ $opt }} rows</span>
                        @if(request('per_page', 50) == $opt)
                            <svg class="w-3.5 h-3.5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        <span class="text-slate-400 font-bold font-mono text-xs">
            {{ number_format($paginator->total()) }} records
        </span>
    </div>

    <!-- Link Navigasi Halaman Ringkas (Compact Pagination: Tidak Terlalu Banyak Halaman Agar Tidak Overwrite) -->
    @if($paginator->hasPages())
        @php
            $currentPage = $paginator->currentPage();
            $lastPage = $paginator->lastPage();
            
            // Logika slider ringkas: maksimal 5 nomor halaman ditampilkan + tanda titik elipsis
            if ($lastPage <= 7) {
                $pages = range(1, $lastPage);
            } else {
                if ($currentPage <= 3) {
                    $pages = [1, 2, 3, 4, '...', $lastPage];
                } elseif ($currentPage >= $lastPage - 2) {
                    $pages = [1, '...', $lastPage - 3, $lastPage - 2, $lastPage - 1, $lastPage];
                } else {
                    $pages = [1, '...', $currentPage - 1, $currentPage, $currentPage + 1, '...', $lastPage];
                }
            }
        @endphp

        <div class="flex items-center gap-1 sm:gap-1.5 flex-wrap justify-end">
            {{-- Tombol Sebelumnya (Previous) --}}
            @if ($paginator->onFirstPage())
                <span class="table-page-disabled w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 border border-slate-800/80 bg-slate-900/40 cursor-not-allowed" title="Halaman Pertama">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" 
                   class="table-page-btn w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 border border-slate-700 bg-slate-900 hover:bg-slate-800 hover:text-white transition shadow-sm"
                   title="Halaman Sebelumnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
            @endif

            {{-- Nomor-Nomor Halaman --}}
            @foreach ($pages as $p)
                @if (is_string($p))
                    <span class="table-page-dots w-7 h-8 flex items-center justify-center text-slate-500 font-bold select-none text-xs">
                        &hellip;
                    </span>
                @elseif ($p == $currentPage)
                    <span class="table-page-active w-8 h-8 rounded-lg flex items-center justify-center bg-indigo-600 text-white font-extrabold border border-indigo-500 shadow-md text-xs cursor-default">
                        {{ $p }}
                    </span>
                @else
                    <a href="{{ $paginator->url($p) }}" 
                       class="table-page-btn w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 border border-slate-700 bg-slate-900 hover:bg-slate-800 hover:text-white transition font-semibold text-xs">
                        {{ $p }}
                    </a>
                @endif
            @endforeach

            {{-- Tombol Selanjutnya (Next) --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" 
                   class="table-page-btn w-8 h-8 rounded-lg flex items-center justify-center text-slate-300 border border-slate-700 bg-slate-900 hover:bg-slate-800 hover:text-white transition shadow-sm"
                   title="Halaman Selanjutnya">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            @else
                <span class="table-page-disabled w-8 h-8 rounded-lg flex items-center justify-center text-slate-600 border border-slate-800/80 bg-slate-900/40 cursor-not-allowed" title="Halaman Terakhir">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </span>
            @endif
        </div>
    @endif
</div>
