@props(['items'])

@if ($items->hasPages())
<div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
    <!-- Per Page Selector -->
    <div class="flex items-center gap-2">
        <label for="per_page" class="text-sm text-gray-700 dark:text-gray-300">عرض:</label>
        <select id="per_page" name="per_page" onchange="changePerPage(this.value)"
                class="form-select text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white rounded-md">
            <option value="10" {{ request('per_page', 15) == 10 ? 'selected' : '' }}>10</option>
            <option value="15" {{ request('per_page', 15) == 15 ? 'selected' : '' }}>15</option>
            <option value="25" {{ request('per_page', 15) == 25 ? 'selected' : '' }}>25</option>
            <option value="50" {{ request('per_page', 15) == 50 ? 'selected' : '' }}>50</option>
            <option value="100" {{ request('per_page', 15) == 100 ? 'selected' : '' }}>100</option>
        </select>
        <span class="text-sm text-gray-700 dark:text-gray-300">نتيجة</span>
    </div>

    <!-- Results Counter -->
    <div class="text-sm text-gray-700 dark:text-gray-300">
        عرض
        <span class="font-semibold">{{ $items->firstItem() ?? 0 }}</span>
        إلى
        <span class="font-semibold">{{ $items->lastItem() ?? 0 }}</span>
        من
        <span class="font-semibold">{{ $items->total() }}</span>
        نتيجة
    </div>

    <!-- Pagination Links -->
    <div class="flex items-center gap-2">
        {{-- Previous Page Link --}}
        @if ($items->onFirstPage())
            <span class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 rounded-md cursor-not-allowed">
                السابق
            </span>
        @else
            <a href="{{ $items->appends(request()->query())->previousPageUrl() }}"
               class="px-3 py-2 text-sm text-white bg-primary hover:bg-primary/90 rounded-md transition-colors">
                السابق
            </a>
        @endif

        {{-- Pagination Elements --}}
        <div class="hidden sm:flex gap-1">
            @foreach ($items->appends(request()->query())->getUrlRange(1, $items->lastPage()) as $page => $url)
                @if ($page == $items->currentPage())
                    <span class="px-3 py-2 text-sm font-semibold text-white bg-primary rounded-md">
                        {{ $page }}
                    </span>
                @else
                    <a href="{{ $url }}"
                       class="px-3 py-2 text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-md transition-colors">
                        {{ $page }}
                    </a>
                @endif
            @endforeach
        </div>

        {{-- Next Page Link --}}
        @if ($items->hasMorePages())
            <a href="{{ $items->appends(request()->query())->nextPageUrl() }}"
               class="px-3 py-2 text-sm text-white bg-primary hover:bg-primary/90 rounded-md transition-colors">
                التالي
            </a>
        @else
            <span class="px-3 py-2 text-sm text-gray-400 dark:text-gray-600 bg-gray-100 dark:bg-gray-800 rounded-md cursor-not-allowed">
                التالي
            </span>
        @endif
    </div>
</div>

<script>
function changePerPage(perPage) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', perPage);
    url.searchParams.delete('page'); // Reset to first page
    window.location.href = url.toString();
}
</script>
@endif

