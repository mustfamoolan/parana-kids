<x-layout.admin>
    <div>
        <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h5 class="text-lg font-semibold dark:text-white-light">تعديل المشروع: {{ $project->name }}</h5>
            <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-secondary">
                <svg class="w-4 h-4 ltr:mr-2 rtl:ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                العودة
            </a>
        </div>

        <form method="POST" action="{{ route('admin.projects.update', $project) }}" id="projectForm" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- عرض الأخطاء العامة -->
            @if($errors->any())
                <div class="panel bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800">
                    <div class="text-red-600 dark:text-red-400">
                        <strong>حدثت الأخطاء التالية:</strong>
                        <ul class="list-disc list-inside mt-2">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- اسم المشروع -->
            <div class="panel">
                <h6 class="text-lg font-semibold mb-4">معلومات المشروع</h6>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="project_name" class="block text-sm font-medium mb-2">اسم المشروع <span
                                class="text-red-500">*</span></label>
                        <input type="text" id="project_name" name="project_name"
                            value="{{ old('project_name', $project->name) }}"
                            class="form-input @error('project_name') border-red-500 @enderror" required>
                        @error('project_name')
                            <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- قسم الاستثمار والمستثمرين -->
            <div id="investorsSection">
                <!-- قسم الاستثمار -->
                <div class="panel mb-5">
                    <h6 class="text-lg font-semibold mb-4">الاستثمار</h6>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="investment_type" class="block text-sm font-medium mb-2">نوع الاستثمار <span
                                    class="text-red-500">*</span></label>
                            <select id="investment_type" name="investment[type]" class="form-select" required
                                onchange="onInvestmentTypeChange()">
                                <option value="warehouse" selected>مخزن</option>
                            </select>
                            <small class="text-gray-500">الاستثمار متاح فقط للمخازن</small>
                        </div>
                        <div id="targets_field">
                            <label for="investment_targets" class="block text-sm font-medium mb-2">اختر المخزن <span
                                    class="text-red-500">*</span></label>
                            <select id="investment_targets" name="investment[targets][]" class="form-select" multiple
                                size="5" onchange="calculateInvestmentValue()">
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}" {{ $targetWarehouses->contains('id', $warehouse->id) ? 'selected' : '' }}>
                                        {{ $warehouse->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="remaining_percentage_info" class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded"
                                style="display: none;">
                                <div class="text-sm font-medium mb-1">النسبة المتبقية للمخزن:</div>
                                <div id="remaining_percentage_list" class="text-sm"></div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">القيمة الإجمالية</label>
                            <input type="number" id="total_value_display" name="investment[total_value]" step="0.01"
                                min="0" class="form-input"
                                value="{{ old('investment.total_value', $investment->total_value ?? 0) }}" readonly>
                            <small class="text-gray-500">سيتم تحديثها تلقائياً عند إضافة منتجات للمخزن (للاطلاع
                                فقط)</small>
                        </div>
                    </div>
                </div>

                <!-- قسم المستثمرين -->
                <div class="panel">
                    <div class="flex items-center justify-between mb-4">
                        <h6 class="text-lg font-semibold">المستثمرين</h6>
                        <button type="button" onclick="addInvestorRow()" class="btn btn-primary btn-sm">
                            <svg class="w-4 h-4 ltr:mr-1 rtl:ml-1" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            إضافة مستثمر
                        </button>
                    </div>

                    <div id="investorsContainer">
                        @if($investment && $investment->investors)
                            @foreach($investment->investors as $index => $investmentInvestor)
                                <div class="investor-row panel mb-4" data-investor-index="{{ $index }}"
                                    data-investor-id="{{ $investmentInvestor->investor_id }}">
                                    <div class="flex items-center justify-between mb-4 pb-4 border-b">
                                        <h6 class="text-lg font-semibold">مستثمر #{{ $index + 1 }}</h6>
                                        <button type="button" onclick="removeInvestorRow({{ $index }})"
                                            class="btn btn-danger btn-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                            حذف
                                        </button>
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium mb-2">اختر المستثمر <span
                                                    class="text-red-500">*</span></label>
                                            <select name="investment[investors][{{ $index }}][investor_id]"
                                                class="form-select investor-select" required
                                                onchange="loadInvestorBalance({{ $index }}, this.value)">
                                                <option value="">-- اختر المستثمر --</option>
                                                @foreach($investors as $investor)
                                                    <option value="{{ $investor->id }}" {{ $investmentInvestor->investor_id == $investor->id ? 'selected' : '' }}>
                                                        {{ $investor->name }}{{ $investor->is_admin ? ' (المدير)' : '' }}
                                                        ({{ $investor->phone }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">الرصيد المتاح</label>
                                            <input type="text" id="available_balance_{{ $index }}" class="form-input" readonly
                                                style="background-color: #f3f4f6;"
                                                value="{{ number_format($investmentInvestor->investor->treasury->current_balance ?? 0, 2) }} IQD">
                                            <small class="text-gray-500">يُجلب تلقائياً عند اختيار المستثمر</small>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">مبلغ الاستثمار (IQD)</label>
                                            <input type="number" name="investment[investors][{{ $index }}][investment_amount]"
                                                step="0.01" min="0" class="form-input investment-amount-input"
                                                value="{{ old("investment.investors.$index.investment_amount", ($investmentInvestor->cost_percentage ?? 0) / 100 * ($investment->total_value ?? 0)) }}"
                                                oninput="calculateCostPercentageFromAmount({{ $index }})"
                                                onchange="validateInvestmentAmount({{ $index }})">
                                            <small class="text-gray-500">تلقائياً يحدد النسبة</small>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">نسبة التكلفة (%) <span
                                                    class="text-red-500">*</span></label>
                                            <input type="number" name="investment[investors][{{ $index }}][cost_percentage]"
                                                step="0.01" min="0" max="100" class="form-input cost-percentage-input" required
                                                value="{{ old("investment.investors.$index.cost_percentage", $investmentInvestor->cost_percentage ?? 0) }}"
                                                oninput="calculateInvestmentAmountFromPercentage({{ $index }})">
                                            <small class="text-gray-500">نسبة التكلفة من المنتجات التي ستُضاف للمخزن</small>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium mb-2">نسبة الربح (%) <span
                                                    class="text-red-500">*</span></label>
                                            <input type="number" name="investment[investors][{{ $index }}][profit_percentage]"
                                                step="0.01" min="0" max="100" class="form-input profit-percentage-input"
                                                required
                                                value="{{ old("investment.investors.$index.profit_percentage", $investmentInvestor->profit_percentage ?? 0) }}"
                                                oninput="calculateRemainingAdminPercentage()">
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>

                    <!-- عرض النسب -->
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- نسب الربح -->
                        <div
                            class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">نسبة الربح
                                        المتبقية للمدير:</div>
                                    <div id="admin_remaining_percentage"
                                        class="text-2xl font-bold text-blue-600 dark:text-blue-400">100%</div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <div>مجموع نسب المستثمرين: <span id="total_investors_percentage">0%</span></div>
                                </div>
                            </div>
                        </div>

                        <!-- نسب التكلفة -->
                        <div
                            class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <div class="text-sm font-medium text-gray-600 dark:text-gray-400">نسبة التكلفة
                                        المتبقية على المدير:</div>
                                    <div id="admin_remaining_cost_percentage"
                                        class="text-2xl font-bold text-green-600 dark:text-green-400">100%</div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <div>مجموع نسب التكلفة للمستثمرين: <span
                                            id="total_investors_cost_percentage">0%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- زر الحفظ -->
            <div class="panel">
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.projects.show', $project) }}" class="btn btn-outline-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </div>
        </form>
    </div>

    <script>
        let investorIndex = {{ $investment && $investment->investors ? $investment->investors->count() : 0 }};
        let totalValue = {{ old('investment.total_value', $investment->total_value ?? 0) }};
        const warehouses = @json($warehouses);
        const investors = @json($investors ?? []);

        // عند تغيير نوع الاستثمار (دائماً مخزن الآن)
        function onInvestmentTypeChange() {
            // لا حاجة لفعل شيء لأن النوع ثابت
        }

        // حساب قيمة الاستثمار
        async function calculateInvestmentValue() {
            const targetsSelect = document.getElementById('investment_targets');
            const selectedTargets = Array.from(targetsSelect?.selectedOptions || []).map(opt => ({ id: parseInt(opt.value) }));

            if (selectedTargets.length === 0) {
                totalValue = 0;
                document.getElementById('total_value_display').value = '0';
                calculateRemainingAdminPercentage();
                return;
            }

            try {
                const response = await fetch('{{ route("admin.projects.calculate-value") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        type: 'warehouse',
                        targets: selectedTargets
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                totalValue = data.total_value || 0;

                const totalValueDisplay = document.getElementById('total_value_display');
                if (totalValueDisplay) {
                    totalValueDisplay.value = totalValue.toFixed(2);
                }

                calculateRemainingAdminPercentage();
            } catch (error) {
                console.error('Error calculating investment value:', error);
            }
        }

        // إضافة صف مستثمر جديد
        function addInvestorRow() {
            const container = document.getElementById('investorsContainer');
            const index = investorIndex++;

            let investorsOptions = '<option value="">-- اختر المستثمر --</option>';
            investors.forEach(investor => {
                const isAdmin = investor.is_admin ? ' (المدير)' : '';
                investorsOptions += `<option value="${investor.id}">${investor.name}${isAdmin} (${investor.phone})</option>`;
            });

            const investorHtml = `
                <div class="investor-row panel mb-4" data-investor-index="${index}">
                    <div class="flex items-center justify-between mb-4 pb-4 border-b">
                        <h6 class="text-lg font-semibold">مستثمر #${index + 1}</h6>
                        <button type="button" onclick="removeInvestorRow(${index})" class="btn btn-danger btn-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            حذف
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">اختر المستثمر <span class="text-red-500">*</span></label>
                            <select name="investment[investors][${index}][investor_id]" class="form-select investor-select" required onchange="loadInvestorBalance(${index}, this.value)">
                                ${investorsOptions}
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">الرصيد المتاح</label>
                            <input type="text" id="available_balance_${index}" class="form-input" readonly style="background-color: #f3f4f6;">
                            <small class="text-gray-500">يُجلب تلقائياً عند اختيار المستثمر</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">مبلغ الاستثمار (IQD)</label>
                            <input type="number" name="investment[investors][${index}][investment_amount]" step="0.01" min="0" class="form-input investment-amount-input" oninput="calculateCostPercentageFromAmount(${index})" onchange="validateInvestmentAmount(${index})">
                            <small class="text-gray-500">تلقائياً يحدد النسبة</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">نسبة التكلفة (%) <span class="text-red-500">*</span></label>
                            <input type="number" name="investment[investors][${index}][cost_percentage]" step="0.01" min="0" class="form-input cost-percentage-input" required oninput="calculateInvestmentAmountFromPercentage(${index})">
                            <small class="text-gray-500">نسبة التكلفة من المنتجات التي ستُضاف للمخزن</small>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-2">نسبة الربح (%) <span class="text-red-500">*</span></label>
                            <input type="number" name="investment[investors][${index}][profit_percentage]" step="0.01" min="0" class="form-input profit-percentage-input" required oninput="calculateRemainingAdminPercentage()">
                        </div>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', investorHtml);
            calculateRemainingAdminPercentage();
        }

        // جلب رصيد المستثمر
        async function loadInvestorBalance(index, investorId) {
            if (!investorId) {
                const balanceInput = document.getElementById(`available_balance_${index}`);
                if (balanceInput) balanceInput.value = '';
                return;
            }

            try {
                const response = await fetch(`{{ url('/admin/investors') }}/${investorId}/treasury-balance`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                const balanceInput = document.getElementById(`available_balance_${index}`);
                if (balanceInput) {
                    balanceInput.value = `${data.balance.toFixed(2)} IQD`;
                }

                // إعادة حساب نسبة التكلفة بعد جلب الرصيد
                calculateCostPercentageFromAmount(index);
            } catch (error) {
                console.error('Error loading investor balance:', error);
                alert('حدث خطأ أثناء جلب رصيد المستثمر');
            }
        }

        // التحقق من أن مبلغ الاستثمار لا يتجاوز الرصيد المتاح والقيمة الإجمالية
        function validateInvestmentAmount(index) {
            const investorSelect = document.querySelector(`.investor-row[data-investor-index="${index}"] .investor-select`);
            const amountInput = document.querySelector(`.investor-row[data-investor-index="${index}"] .investment-amount-input`);
            const balanceInput = document.getElementById(`available_balance_${index}`);

            if (!investorSelect || !amountInput || !balanceInput) return;

            const investorId = investorSelect.value;
            const amount = parseFloat(amountInput.value) || 0;
            const balanceText = balanceInput.value;
            const balance = parseFloat(balanceText.replace(/[^0-9.]/g, '')) || 0;

            // التحقق من الرصيد المتاح
            if (amount > balance) {
                alert(`مبلغ الاستثمار (${amount.toFixed(2)}) يتجاوز الرصيد المتاح (${balance.toFixed(2)})`);
                amountInput.value = balance.toFixed(2);
                calculateCostPercentageFromAmount(index);
                return;
            }

            // تم إزالة التحقق من تجاوز القيمة الإجمالية بناءً على طلب المستخدم

            // إعادة حساب نسبة التكلفة بعد التحقق
            calculateCostPercentageFromAmount(index);
        }

        // حذف صف مستثمر
        function removeInvestorRow(index) {
            const row = document.querySelector(`.investor-row[data-investor-index="${index}"]`);
            if (row) {
                row.remove();
                calculateRemainingAdminPercentage();
            }
        }

        // حساب نسبة التكلفة من المبلغ (عند كتابة المبلغ)
        function calculateCostPercentageFromAmount(investorIndex) {
            const row = document.querySelector(`.investor-row[data-investor-index="${investorIndex}"]`);
            if (!row) return;

            const amountInput = row.querySelector('.investment-amount-input');
            const costPercentageInput = row.querySelector('.cost-percentage-input');

            // الحصول على القيمة الإجمالية من الحقل
            const totalValueInput = document.getElementById('total_value_display');
            const currentTotalValue = totalValueInput ? (parseFloat(totalValueInput.value) || totalValue) : totalValue;

            const amount = parseFloat(amountInput?.value || 0);

            if (currentTotalValue > 0 && amount > 0) {
                let costPercentage = (amount / currentTotalValue) * 100;

                if (amountInput) {
                    amountInput.setCustomValidity('');
                }

                // تحديث النسبة فقط إذا لم يكن المستخدم يكتب فيها
                if (costPercentageInput && document.activeElement !== costPercentageInput) {
                    costPercentageInput.value = costPercentage.toFixed(2);
                }
            } else {
                if (costPercentageInput && document.activeElement !== costPercentageInput) {
                    costPercentageInput.value = '';
                }
                if (amountInput) {
                    amountInput.setCustomValidity('');
                }
            }
            calculateRemainingAdminPercentage();
        }

        // تحديث جميع مبالغ الاستثمار بناءً على القيمة الإجمالية الحالية
        function updateInvestmentAmountsFromTotalValue() {
            const totalValueInput = document.getElementById('total_value_display');
            if (!totalValueInput) return;

            // الحصول على القيمة الإجمالية من الحقل
            const newTotalValue = parseFloat(totalValueInput.value) || 0;
            if (newTotalValue <= 0) return;

            // تحديث المتغير العام
            totalValue = newTotalValue;

            // تحديث جميع صفوف المستثمرين
            const investorRows = document.querySelectorAll('.investor-row');
            investorRows.forEach((row) => {
                const investorIndex = row.getAttribute('data-investor-index');
                if (investorIndex) {
                    const costPercentageInput = row.querySelector('.cost-percentage-input');
                    const costPercentage = parseFloat(costPercentageInput?.value || 0);

                    if (costPercentage > 0) {
                        // حساب المبلغ الجديد بناءً على النسبة
                        const newAmount = (newTotalValue * costPercentage) / 100;
                        const amountInput = row.querySelector('.investment-amount-input');
                        if (amountInput) {
                            amountInput.value = newAmount.toFixed(2);
                        }
                    }
                }
            });

            // تحديث النسب المتبقية
            calculateRemainingAdminPercentage();
        }

        // حساب المبلغ من نسبة التكلفة (عند كتابة النسبة)
        function calculateInvestmentAmountFromPercentage(investorIndex) {
            const row = document.querySelector(`.investor-row[data-investor-index="${investorIndex}"]`);
            if (!row) return;

            const amountInput = row.querySelector('.investment-amount-input');
            const costPercentageInput = row.querySelector('.cost-percentage-input');
            const balanceInput = document.getElementById(`available_balance_${investorIndex}`);

            // الحصول على القيمة الإجمالية من الحقل
            const totalValueInput = document.getElementById('total_value_display');
            const currentTotalValue = totalValueInput ? (parseFloat(totalValueInput.value) || totalValue) : totalValue;

            const costPercentage = parseFloat(costPercentageInput?.value || 0);

            if (currentTotalValue > 0 && costPercentage > 0) {
                // حساب المبلغ من النسبة
                let calculatedAmount = (currentTotalValue * costPercentage) / 100;

                // التحقق من الرصيد المتاح
                const balanceText = balanceInput?.value || '';
                const balance = parseFloat(balanceText.replace(/[^0-9.]/g, '')) || 0;

                // إذا تجاوز المبلغ الرصيد المتاح، قم بتعديله
                if (calculatedAmount > balance && balance > 0) {
                    calculatedAmount = balance;
                    // تحديث النسبة ليتوافق مع الرصيد المتاح
                    const adjustedPercentage = (calculatedAmount / currentTotalValue) * 100;
                    if (costPercentageInput) {
                        costPercentageInput.value = adjustedPercentage.toFixed(2);
                    }
                    if (amountInput) {
                        amountInput.setCustomValidity('المبلغ المحسوب يتجاوز الرصيد المتاح. تم تعديله');
                        amountInput.reportValidity();
                    }
                } else {
                    if (amountInput) {
                        amountInput.setCustomValidity('');
                    }
                }

                // تحديث المبلغ فقط إذا لم يكن المستخدم يكتب فيها
                if (amountInput && document.activeElement !== amountInput) {
                    amountInput.value = calculatedAmount.toFixed(2);
                }
            } else {
                if (amountInput && document.activeElement !== amountInput) {
                    amountInput.value = '';
                }
                if (amountInput) {
                    amountInput.setCustomValidity('');
                }
            }
            calculateRemainingAdminPercentage();
        }

        // حساب النسبة المتبقية للمدير
        function calculateRemainingAdminPercentage() {
            const profitInputs = document.querySelectorAll('.profit-percentage-input');
            const costPercentageInputs = document.querySelectorAll('.cost-percentage-input');

            let totalInvestorsPercentage = 0;
            let totalInvestorsCostPercentage = 0;

            // حساب مجموع نسب الربح للمستثمرين
            profitInputs.forEach(input => {
                const value = parseFloat(input.value || 0);
                totalInvestorsPercentage += value;
            });

            // حساب مجموع نسب التكلفة للمستثمرين
            costPercentageInputs.forEach(input => {
                const value = parseFloat(input.value || 0);
                totalInvestorsCostPercentage += value;
            });

            const adminPercentage = Math.max(0, 100 - totalInvestorsPercentage);
            const adminCostPercentage = Math.max(0, 100 - totalInvestorsCostPercentage);

            // تحديث نسب الربح
            const totalInvestorsPercentageEl = document.getElementById('total_investors_percentage');
            const adminRemainingPercentageEl = document.getElementById('admin_remaining_percentage');
            if (totalInvestorsPercentageEl) {
                totalInvestorsPercentageEl.textContent = totalInvestorsPercentage.toFixed(2) + '%';
            }
            if (adminRemainingPercentageEl) {
                adminRemainingPercentageEl.textContent = adminPercentage.toFixed(2) + '%';
            }

            // تحديث نسب التكلفة
            const totalInvestorsCostPercentageEl = document.getElementById('total_investors_cost_percentage');
            const adminRemainingCostPercentageEl = document.getElementById('admin_remaining_cost_percentage');
            if (totalInvestorsCostPercentageEl) {
                totalInvestorsCostPercentageEl.textContent = totalInvestorsCostPercentage.toFixed(2) + '%';
            }
            if (adminRemainingCostPercentageEl) {
                adminRemainingCostPercentageEl.textContent = adminCostPercentage.toFixed(2) + '%';
            }

            // تغيير اللون بناءً على النسبة (للربح)
            if (adminRemainingPercentageEl) {
                if (adminPercentage < 10) {
                    adminRemainingPercentageEl.className = 'text-2xl font-bold text-red-600 dark:text-red-400';
                } else if (adminPercentage < 20) {
                    adminRemainingPercentageEl.className = 'text-2xl font-bold text-yellow-600 dark:text-yellow-400';
                } else {
                    adminRemainingPercentageEl.className = 'text-2xl font-bold text-blue-600 dark:text-blue-400';
                }
            }

            // تغيير اللون بناءً على النسبة (للتكلفة)
            if (adminRemainingCostPercentageEl) {
                if (adminCostPercentage < 10) {
                    adminRemainingCostPercentageEl.className = 'text-2xl font-bold text-red-600 dark:text-red-400';
                } else if (adminCostPercentage < 20) {
                    adminRemainingCostPercentageEl.className = 'text-2xl font-bold text-yellow-600 dark:text-yellow-400';
                } else {
                    adminRemainingCostPercentageEl.className = 'text-2xl font-bold text-green-600 dark:text-green-400';
                }
            }
        }

        // تهيئة الواجهة عند تحميل الصفحة
        document.addEventListener('DOMContentLoaded', function () {
            calculateRemainingAdminPercentage();

            // جلب رصيد المستثمرين الموجودين
            document.querySelectorAll('.investor-select').forEach(select => {
                const index = select.closest('.investor-row').getAttribute('data-investor-index');
                const investorId = select.value;
                if (investorId && index) {
                    loadInvestorBalance(parseInt(index), investorId);
                }
            });
        });

    </script>
</x-layout.admin>