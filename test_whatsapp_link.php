<?php
/**
 * تمرين الخوارزمية البرمجية: إيجاد أكبر رقم في قائمة
 * Algorithm Exercise: Finding the Largest Number in a List
 */

echo "=== تمرين الخوارزمية: إيجاد أكبر رقم في قائمة ===\n";
echo "=== Algorithm Exercise: Finding Largest Number ===\n\n";

/**
 * دالة لإيجاد أكبر رقم في قائمة
 * Function to find the largest number in a list
 */
function findLargestNumber($numbers) {
    // التحقق من أن القائمة ليست فارغة
    if (empty($numbers)) {
        return "القائمة فارغة!";
    }

    // تهيئة المتغير بأول رقم في القائمة
    $largest = $numbers[0];

    // المرور عبر جميع الأرقام في القائمة
    for ($i = 1; $i < count($numbers); $i++) {
        // مقارنة كل رقم مع أكبر رقم حالياً
        if ($numbers[$i] > $largest) {
            $largest = $numbers[$i];
        }
    }

    return $largest;
}

/**
 * دالة لعرض تفاصيل الخوارزمية
 * Function to display algorithm details
 */
function displayAlgorithm($numbers) {
    echo "القائمة: [" . implode(", ", $numbers) . "]\n";
    echo "List: [" . implode(", ", $numbers) . "]\n";

    $largest = findLargestNumber($numbers);
    echo "أكبر رقم في القائمة: " . $largest . "\n";
    echo "Largest number in list: " . $largest . "\n";

    // عرض خطوات الخوارزمية
    echo "\nخطوات الخوارزمية:\n";
    echo "Algorithm Steps:\n";
    echo "1. نبدأ بأول رقم كأكبر رقم\n";
    echo "2. نمر عبر باقي الأرقام\n";
    echo "3. نقارن كل رقم مع أكبر رقم حالياً\n";
    echo "4. إذا كان الرقم أكبر، نجعله هو الأكبر\n";
    echo "5. نعيد أكبر رقم في النهاية\n\n";
}

// أمثلة مختلفة للاختبار
echo "المثال الأول (5, 20, 8, 15):\n";
echo "Example 1 (5, 20, 8, 15):\n";
displayAlgorithm([5, 20, 8, 15]);

echo "المثال الثاني (100, 50, 200, 75):\n";
echo "Example 2 (100, 50, 200, 75):\n";
displayAlgorithm([100, 50, 200, 75]);

echo "المثال الثالث (أرقام سالبة):\n";
echo "Example 3 (negative numbers):\n";
displayAlgorithm([-10, -5, -20, -1]);

echo "المثال الرابع (رقم واحد فقط):\n";
echo "Example 4 (single number):\n";
displayAlgorithm([42]);

echo "المثال الخامس (أرقام متساوية):\n";
echo "Example 5 (equal numbers):\n";
displayAlgorithm([7, 7, 7, 7]);

echo "=== انتهى التمرين ===\n";
echo "=== Exercise Complete ===\n";
?>
