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
            $checkInterval = 0.5; // فحص كل 0.5 ثانية

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
                            Log::info('SSE sending notifications', [
                                'user_id' => $user->id,
                                'count' => count($notifications),
                                'notifications' => array_map(function($n) {
                                    return [
                                        'id' => $n['id'] ?? null,
                                        'title' => $n['title'] ?? 'N/A',
                                        'body' => substr($n['body'] ?? 'N/A', 0, 50),
                                    ];
                                }, $notifications),
                            ]);

                            foreach ($notifications as $notification) {
                                $eventData = json_encode([
                                    'type' => 'notification',
                                    'data' => $notification,
                                ], JSON_UNESCAPED_UNICODE);

                                echo "data: " . $eventData . "\n\n";
                                ob_flush();
                                flush();

                                Log::info('SSE notification sent to client', [
                                    'user_id' => $user->id,
                                    'notification_id' => $notification['id'] ?? null,
                                    'title' => $notification['title'] ?? 'N/A',
                                    'body' => substr($notification['body'] ?? 'N/A', 0, 50),
                                ]);
                            }

                            // تحديد الإشعارات كمقروءة بعد إرسالها
                            $cleared = $this->sseService->clearNotificationsForUser($user->id);
                            Log::info('SSE notifications marked as read', [
                                'user_id' => $user->id,
                                'cleared_count' => $cleared,
                            ]);
                        } else {
                            // Log فقط كل 10 ثوان لتقليل الـ logs
                            if (($currentTime - $lastCheck) >= 10) {
                                Log::debug('SSE no notifications found', ['user_id' => $user->id]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('SSE error getting notifications: ' . $e->getMessage(), [
                            'user_id' => $user->id,
                            'error' => $e->getTraceAsString(),
                        ]);
                    }

                    $lastCheck = $currentTime;
                }

                // انتظار قصير لتقليل استهلاك CPU
                usleep(100000); // 0.1 ثانية
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('Connection', 'keep-alive');
        $response->headers->set('X-Accel-Buffering', 'no'); // تعطيل buffering في Nginx

        return $response;
    }
}
