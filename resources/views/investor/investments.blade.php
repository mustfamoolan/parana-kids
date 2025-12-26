<x-layout.investor>
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold dark:text-white-light">الاستثمارات</h1>
                <p class="text-gray-600 dark:text-gray-400 mt-1">جميع استثماراتك</p>
            </div>
            <div>
                <a href="{{ route('investor.dashboard') }}" class="btn btn-outline-primary">
                    <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    العودة للوحة التحكم
                </a>
            </div>
        </div>

        @if($allInvestments->count() > 0)
            <div class="grid grid-cols-1 gap-4">
                @foreach($allInvestments as $index => $item)
                    <div class="panel">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h6 class="font-semibold text-lg dark:text-white-light mb-3">
                                    @if($item['type'] === 'new' && $item['project'])
                                        {{ $item['project']->project_name }}
                                    @elseif($item['type'] === 'old')
                                        @if($item['investment']->product)
                                            {{ $item['investment']->product->name }}
                                        @elseif($item['investment']->warehouse)
                                            {{ $item['investment']->warehouse->name }}
                                        @elseif($item['investment']->privateWarehouse)
                                            {{ $item['investment']->privateWarehouse->name }}
                                        @else
                                            استثمار #{{ $index + 1 }}
                                        @endif
                                    @else
                                        استثمار #{{ $index + 1 }}
                                    @endif
                                </h6>
                                
                                <div class="space-y-2">
                                    <!-- نوع الاستثمار -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">النوع:</span>
                                        @if($item['type'] === 'new')
                                            <span class="badge badge-outline-primary dark:border-primary dark:text-primary">
                                                {{ $item['investment']->investment_type === 'product' ? 'منتج' : 'مخزن' }}
                                            </span>
                                        @else
                                            <span class="badge badge-outline-primary dark:border-primary dark:text-primary">
                                                {{ $item['investment']->investment_type === 'product' ? 'منتج' : ($item['investment']->investment_type === 'warehouse' ? 'مخزن' : 'مخزن خاص') }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- مبلغ الاستثمار -->
                                    <div class="flex items-center gap-2">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">مبلغ الاستثمار:</span>
                                        <span class="font-semibold text-primary dark:text-primary">
                                            @if($item['type'] === 'new' && $item['investmentInvestor'])
                                                {{ number_format($item['investmentInvestor']->investment_amount, 2) }} دينار
                                            @else
                                                {{ number_format($item['investment']->investment_amount ?? 0, 2) }} دينار
                                            @endif
                                        </span>
                                    </div>

                                    <!-- نسبة الربح ونسبة التكلفة -->
                                    <div class="flex items-center gap-4 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">نسبة الربح:</span>
                                            @if($item['type'] === 'new' && $item['investmentInvestor'])
                                                <span class="badge badge-info dark:bg-info dark:text-white">{{ number_format($item['investmentInvestor']->profit_percentage, 2) }}%</span>
                                            @else
                                                <span class="badge badge-info dark:bg-info dark:text-white">{{ number_format($item['investment']->profit_percentage ?? 0, 2) }}%</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">نسبة التكلفة:</span>
                                            @if($item['type'] === 'new' && $item['investmentInvestor'])
                                                <span class="badge badge-warning dark:bg-warning dark:text-white">{{ number_format($item['investmentInvestor']->cost_percentage, 2) }}%</span>
                                            @else
                                                <span class="badge badge-warning dark:bg-warning dark:text-white">{{ number_format($item['investment']->cost_percentage ?? 0, 2) }}%</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- التفاصيل الإضافية -->
                                    @if($item['type'] === 'new' && $item['investment']->targets && $item['investment']->targets->count() > 0)
                                        <div class="mt-2">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">الأهداف:</span>
                                            <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                @foreach($item['investment']->targets->take(3) as $target)
                                                    @if($target->target_type === 'product')
                                                        {{ \App\Models\Product::find($target->target_id)->name ?? '-' }}
                                                    @elseif($target->target_type === 'warehouse')
                                                        {{ \App\Models\Warehouse::find($target->target_id)->name ?? '-' }}
                                                    @endif
                                                    @if(!$loop->last), @endif
                                                @endforeach
                                                @if($item['investment']->targets->count() > 3)
                                                    +{{ $item['investment']->targets->count() - 3 }} أكثر
                                                @endif
                                            </div>
                                        </div>
                                    @elseif($item['type'] === 'old' && $item['investment']->product && $item['investment']->product->warehouse)
                                        <div class="mt-2">
                                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">المخزن:</span>
                                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $item['investment']->product->warehouse->name ?? '-' }}</span>
                                        </div>
                                    @endif

                                    <!-- تاريخ الإنشاء -->
                                    <div class="mt-2">
                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">تاريخ الإنشاء:</span>
                                        <span class="text-sm text-gray-500 dark:text-gray-400">
                                            {{ $item['investment']->created_at->format('Y-m-d') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- الحالة -->
                            <div>
                                @php
                                    $status = $item['type'] === 'new' ? $item['investment']->status : $item['investment']->status;
                                @endphp
                                @if($status === 'active')
                                    <span class="badge badge-success dark:bg-success dark:text-white">نشط</span>
                                @else
                                    <span class="badge badge-danger dark:bg-danger dark:text-white">غير نشط</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="panel">
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-medium">لا توجد استثمارات</p>
                        <p class="text-sm">لم يتم إضافة أي استثمارات بعد</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layout.investor>

