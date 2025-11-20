<?php

namespace App\Http\Controllers;

use App\Services\SseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SseController extends Controller
{
    protected $sseService;

    public function __construct(SseNotificationService $sseService)
    {
        $this->sseService = $sseService;
    }

    /**
     * SSE endpoint للاتصال واستقبال الإشعارات
     */
    public function stream()
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Unauthorized');
        }

        $response = new StreamedResponse(function () use ($user) {
            // إرسال ping فوراً للحفاظ على الاتصال
            echo "data: " . json_encode(['type' => 'ping', 'timestamp' => time()]) . "\n\n";
            ob_flush();
            flush();

            $lastPing = time();
            $pingInterval = 30;
            $lastCheck = 0;
            $checkInterval = 2.5; // فحص كل 2.5 ثانية (محسّن للأداء - كان 0.1s)

            while (true) {
                // التحقق من انقطاع الاتصال
                if (connection_aborted()) {
                    Log::info('SSE connection closed', ['user_id' => $user->id]);
                    break;
                }

                $currentTime = microtime(true);

                // إرسال ping كل 30 ثانية
                if ($currentTime - $lastPing >= $pingInterval) {
                    echo "data: " . json_encode(['type' => 'ping', 'timestamp' => time()]) . "\n\n";
                    ob_flush();
                    flush();
                    $lastPing = $currentTime;
                }

                // جلب الإشعارات الجديدة للمستخدم كل 0.5 ثانية
                if ($currentTime - $lastCheck >= $checkInterval) {
                    try {
                        $notifications = $this->sseService->getNotificationsForUser($user->id);

                        if (!empty($notifications)) {
                            // تقليل الـ logs - فقط عند وجود إشعارات جديدة
                            Log::debug('SSE sending notifications', [
                                'user_id' => $user->id,
                                'count' => count($notifications),
                            ]);

                            $sentNotificationIds = [];

                            foreach ($notifications as $notification) {
                                $eventData = json_encode([
                                    'type' => 'notification',
                                    'data' => $notification,
                                ], JSON_UNESCAPED_UNICODE);

                                echo "data: " . $eventData . "\n\n";
                                ob_flush();
                                flush();

                                if (isset($notification['id'])) {
                                    $sentNotificationIds[] = $notification['id'];
                                }

                                // تقليل الـ logs - فقط في حالة debug
                                Log::debug('SSE notification sent', [
                                    'user_id' => $user->id,
                                    'notification_id' => $notification['id'] ?? null,
                                ]);
                            }

                            // تحديد الإشعارات التي تم إرسالها كمقروءة فقط
                            if (!empty($sentNotificationIds)) {
                                $cleared = $this->sseService->clearNotificationsForUser($user->id, $sentNotificationIds);
                                // تقليل الـ logs
                                Log::debug('SSE notifications marked as read', [
                                    'user_id' => $user->id,
                                    'cleared_count' => $cleared,
                                ]);
                            }
                        }
                        // إزالة log "no notifications found" لتقليل الـ logs
                    } catch (\Exception $e) {
                        Log::error('SSE error getting notifications: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'error' => $e->getTraceAsString(),
                        ]);
                    }

                    $lastCheck = $currentTime;
                }

                // انتظار قصير لتقليل استهلاك CPU (محسّن)
                usleep(500000); // 0.5 ثانية (كان 0.1s)
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // تعطيل buffering في Nginx

        return $response;
    }
}
