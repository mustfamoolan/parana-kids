@props(['timeline', 'currentStatusId'])

<div class="panel p-6">
    <h3 class="text-lg font-bold mb-6 text-gray-800 dark:text-white flex items-center gap-2">
        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
        </svg>
        سجل حالات الطلب
    </h3>
    
    <div class="relative">
        <!-- الخط العمودي -->
        <div class="absolute right-4 top-0 bottom-0 w-0.5 bg-gray-200 dark:bg-gray-700"></div>
        
        @foreach($timeline as $index => $item)
            <div class="relative flex items-start mb-6 last:mb-0">
                <!-- الأيقونة -->
                <div class="relative z-10 flex items-center justify-center w-10 h-10 rounded-full 
                    {{ $item['is_current'] ? 'bg-success ring-4 ring-success/20' : 'bg-gray-300 dark:bg-gray-600' }}">
                    @if($item['is_current'])
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    @else
                        <div class="w-3 h-3 rounded-full bg-white"></div>
                    @endif
                </div>
                
                <!-- المحتوى -->
                <div class="mr-6 flex-1">
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 transition-all hover:shadow-md
                        {{ $item['is_current'] ? 'border-2 border-success shadow-lg' : 'border border-gray-200 dark:border-gray-700' }}">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                @if($item['is_current'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-success text-white">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                        حالي
                                    </span>
                                @endif
                                {{ $item['status_text'] }}
                            </h4>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <span class="font-medium">{{ $item['changed_at']->diffForHumans() }}</span>
                            <span class="text-xs text-gray-400">
                                ({{ $item['changed_at']->format('Y-m-d H:i') }})
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
        
        @if($timeline->isEmpty())
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-lg font-medium">لا يوجد سجل للحالات بعد</p>
                <p class="text-sm mt-1">سيتم تسجيل التغييرات تلقائياً</p>
            </div>
        @endif
    </div>
</div>

