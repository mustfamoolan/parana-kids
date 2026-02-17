<?php

namespace App\Http\Controllers\Mobile\Delegate;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MobileDelegateChatController extends Controller
{
    protected $sweetAlertService;

    public function __construct(SweetAlertService $sweetAlertService)
    {
        $this->sweetAlertService = $sweetAlertService;
    }

    /**
     * جلب قائمة المحادثات
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getConversations()
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $conversations = $user->conversations()
            ->with(['latestMessage.user', 'participants'])
            ->get()
            ->map(function ($conversation) use ($user) {
                $otherParticipant = $conversation->getOtherParticipant($user->id);
                $unreadCount = $conversation->unreadCount($user->id);

                if ($conversation->isGroup()) {
                    return [
                        'id' => $conversation->id,
                        'type' => 'group',
                        'userId' => null,
                        'name' => $conversation->title ?? 'مجموعة',
                        'code' => null,
                        'path' => null,
                        'preview' => $conversation->latestMessage ? substr($conversation->latestMessage->message, 0, 50) : '',
                        'time' => $conversation->updated_at->format('g:i A'),
                        'active' => true,
                        'unread_count' => $unreadCount,
                        'participants_count' => $conversation->participants()->count(),
                    ];
                } else {
                    return [
                        'id' => $conversation->id,
                        'type' => 'direct',
                        'userId' => $otherParticipant ? $otherParticipant->id : null,
                        'name' => $otherParticipant ? $otherParticipant->name : 'Unknown',
                        'code' => $otherParticipant ? $otherParticipant->code : null,
                        'path' => $otherParticipant ? $otherParticipant->profile_image_url : asset('assets/images/profile-1.jpeg'),
                        'preview' => $conversation->latestMessage ? substr($conversation->latestMessage->message, 0, 50) : '',
                        'time' => $conversation->updated_at->format('g:i A'),
                        'active' => $otherParticipant ? true : false,
                        'unread_count' => $unreadCount,
                    ];
                }
            });

        return response()->json([
            'success' => true,
            'data' => [
                'conversations' => $conversations,
            ],
        ]);
    }

    /**
     * جلب أو إنشاء محادثة مع مستخدم
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrCreateConversation(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $otherUserId = $request->input('user_id');

        // التحقق من أن المستخدم يمكنه المراسلة مع هذا المستخدم
        $availableUsers = $user->getUsersWithSharedWarehouses();
        $otherUser = $availableUsers->firstWhere('id', $otherUserId);

        if (!$otherUser) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكنك المراسلة مع هذا المستخدم',
                'error_code' => 'USER_NOT_AVAILABLE',
            ], 403);
        }

        // البحث عن محادثة موجودة
        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
            ->whereHas('participants', function ($q) use ($otherUserId) {
                $q->where('user_id', $otherUserId);
            })
            ->where('type', 'direct')
            ->first();

        // إنشاء محادثة جديدة إذا لم تكن موجودة
        if (!$conversation) {
            // العثور على المخزن المشترك
            $userWarehouseIds = $user->warehouses()->pluck('warehouse_id');
            $otherUserWarehouseIds = $otherUser->warehouses()->pluck('warehouse_id');
            $sharedWarehouseId = $userWarehouseIds->intersect($otherUserWarehouseIds)->first();

            $conversation = Conversation::create([
                'type' => 'direct',
                'warehouse_id' => $sharedWarehouseId,
            ]);

            // إضافة المشاركين
            $conversation->participants()->attach([$user->id, $otherUserId]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'conversation_id' => $conversation->id,
                'other_user' => [
                    'id' => $otherUser->id,
                    'name' => $otherUser->name,
                    'path' => $otherUser->getProfileImageUrl(),
                ]
            ],
        ]);
    }

    /**
     * جلب الرسائل
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversationId = $request->input('conversation_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // تحديث last_read_at
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        // حذف جميع إشعارات المحادثة عند فتحها
        try {
            $this->sweetAlertService->deleteConversationAlerts($conversationId, $user->id);
        } catch (\Exception $e) {
            Log::error('MobileDelegateChatController: Error deleting conversation alerts: ' . $e->getMessage());
        }

        $otherParticipant = $conversation->getOtherParticipant($user->id);

        $messages = $conversation->messages()
            ->with(['user', 'order.delegate', 'order.items.product.warehouse', 'product.primaryImage', 'product.warehouse', 'product.sizes.reservations'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) use ($user, $otherParticipant, $conversation) {
                $data = [
                    'id' => $message->id,
                    'fromUserId' => $message->user_id,
                    'toUserId' => $message->user_id == $user->id ? ($otherParticipant ? $otherParticipant->id : 0) : $user->id,
                    'text' => $message->message,
                    'type' => $message->type ?? 'text',
                    'time' => $message->created_at->format('g:i A'),
                    'created_at' => $message->created_at->toIso8601String(),
                ];

                // إذا كانت الرسالة تحتوي على طلب
                if ($message->type === 'order' && $message->order) {
                    $order = $message->order;
                    $data['order'] = [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->customer_name,
                        'customer_phone' => $order->customer_phone,
                        'customer_social_link' => $order->customer_social_link,
                        'total_amount' => (float) $order->total_amount,
                        'status' => $order->status,
                        'delegate_name' => $order->delegate ? $order->delegate->name : null,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                    ];
                } else {
                    $data['order'] = null;
                }

                // إذا كانت الرسالة تحتوي على منتج
                if ($message->type === 'product' && $message->product) {
                    $product = $message->product;
                    $data['product'] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'selling_price' => (float) $product->selling_price,
                        'gender_type' => $product->gender_type,
                        'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                        'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                        'sizes' => $product->sizes->map(function ($size) {
                            $reserved = $size->reservations()->sum('quantity_reserved');
                            return [
                                'id' => $size->id,
                                'size_name' => $size->size_name,
                                'quantity' => (int) $size->quantity,
                                'available_quantity' => (int) ($size->quantity - $reserved),
                            ];
                        }),
                    ];
                } else {
                    $data['product'] = null;
                }

                // إذا كانت المحادثة مجموعة، أضف اسم المرسل
                if ($conversation->type === 'group') {
                    $data['sender_name'] = $message->user->name;
                    $data['sender_id'] = $message->user_id;
                }

                // إذا كانت الرسالة تحتوي على صورة
                if ($message->image_path) {
                    try {
                        $data['image_url'] = Storage::disk('public')->url($message->image_path);
                    } catch (\Exception $e) {
                        // Fallback إلى asset() إذا فشل Storage::url()
                        $data['image_url'] = asset('storage/' . $message->image_path);
                    }
                } else {
                    $data['image_url'] = null;
                }

                return $data;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * إرسال رسالة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ]);

        $conversationId = $request->input('conversation_id');

        try {
            // التحقق من أن المستخدم مشارك في المحادثة
            $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })->findOrFail($conversationId);

            // معالجة رفع الصورة
            $imagePath = null;
            $messageType = 'text';

            if ($request->hasFile('image')) {
                try {
                    // التأكد من وجود المجلد قبل الحفظ
                    if (!Storage::disk('public')->exists('messages')) {
                        Storage::disk('public')->makeDirectory('messages');
                    }

                    $image = $request->file('image');
                    $imagePath = $image->store('messages', 'public');
                    $messageType = 'image';
                } catch (\Exception $e) {
                    Log::error('MobileDelegateChatController: Failed to upload message image: ' . $e->getMessage());
                    return response()->json([
                        'success' => false,
                        'message' => 'فشل رفع الصورة: ' . $e->getMessage(),
                        'error_code' => 'IMAGE_UPLOAD_ERROR',
                    ], 500);
                }
            }

            // التحقق من وجود نص أو صورة
            if (empty($request->input('message')) && !$imagePath) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب إدخال رسالة أو رفع صورة',
                    'error_code' => 'EMPTY_MESSAGE',
                ], 400);
            }

            // إنشاء الرسالة
            $message = Message::create([
                'conversation_id' => $conversationId,
                'user_id' => $user->id,
                'message' => $request->input('message', ''),
                'type' => $messageType,
                'image_path' => $imagePath,
            ]);

            // تحديث وقت المحادثة
            $conversation->touch();

            // إرسال SweetAlert للمستلم
            try {
                $otherParticipant = $conversation->getOtherParticipant($user->id);
                if ($otherParticipant) {
                    $messageText = $request->input('message', '');
                    if ($messageType === 'image') {
                        $messageText = 'صورة';
                    }
                    $this->sweetAlertService->notifyNewMessage(
                        $conversationId,
                        $user->id,
                        $otherParticipant->id,
                        $messageText
                    );

                    // إرسال FCM Notification
                    try {
                        $fcmService = app(\App\Services\FirebaseCloudMessagingService::class);
                        $fcmService->sendMessageNotification($conversationId, $user->id, $otherParticipant->id, $messageText);
                    } catch (\Exception $e) {
                        Log::error('MobileDelegateChatController: Error sending FCM: ' . $e->getMessage());
                    }

                } elseif ($conversation->isGroup()) {
                    // للمجموعات: إرسال لجميع المشاركين عدا المرسل
                    $participants = $conversation->participants()->where('user_id', '!=', $user->id)->get();
                    foreach ($participants as $participant) {
                        $messageText = $request->input('message', '');
                        if ($messageType === 'image') {
                            $messageText = 'صورة';
                        }
                        $this->sweetAlertService->notifyNewMessage(
                            $conversationId,
                            $user->id,
                            $participant->id,
                            $messageText
                        );

                        // إرسال FCM Notification لكل مشارك
                        try {
                            $fcmService = app(\App\Services\FirebaseCloudMessagingService::class);
                            $fcmService->sendMessageNotification($conversationId, $user->id, $participant->id, $messageText);
                        } catch (\Exception $e) {
                            Log::error('MobileDelegateChatController: Error sending FCM to participant ' . $participant->id . ': ' . $e->getMessage());
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('MobileDelegateChatController: Error sending SweetAlert: ' . $e->getMessage());
            }

            $responseData = [
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'fromUserId' => $message->user_id,
                    'text' => $message->message,
                    'type' => $message->type,
                    'time' => $message->created_at->format('g:i A'),
                    'created_at' => $message->created_at->toIso8601String(),
                ]
            ];

            if ($imagePath) {
                try {
                    $responseData['message']['image_url'] = Storage::disk('public')->url($imagePath);
                } catch (\Exception $e) {
                    // Fallback إلى asset() إذا فشل Storage::url()
                    $responseData['message']['image_url'] = asset('storage/' . $imagePath);
                }
            } else {
                $responseData['message']['image_url'] = null;
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('MobileDelegateChatController: Error sending message: ' . $e->getMessage());
            Log::error('MobileDelegateChatController: Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الرسالة: ' . $e->getMessage(),
                'error_code' => 'SEND_MESSAGE_ERROR',
            ], 500);
        }
    }

    /**
     * إرسال رسالة لمستخدم (إنشاء محادثة تلقائياً إذا لزم الأمر)
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessageToUser(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'nullable|string|max:5000',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
        ]);

        $otherUserId = $request->input('user_id');

        try {
            // الحصول على أو إنشاء المحادثة
            $conversationResponse = $this->getOrCreateConversation(new Request(['user_id' => $otherUserId]));
            $conversationData = json_decode($conversationResponse->getContent(), true);

            if (!$conversationData['success']) {
                return response()->json($conversationData, 403);
            }

            $conversationId = $conversationData['data']['conversation_id'];

            // إنشاء request جديد مع جميع البيانات
            $newRequest = new Request([
                'conversation_id' => $conversationId,
                'message' => $request->input('message'),
            ]);

            // إضافة الصورة إذا كانت موجودة
            if ($request->hasFile('image')) {
                $newRequest->files->set('image', $request->file('image'));
            }

            // إرسال الرسالة
            return $this->sendMessage($newRequest);
        } catch (\Exception $e) {
            Log::error('MobileDelegateChatController: Error sending message to user: ' . $e->getMessage());
            Log::error('MobileDelegateChatController: Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال الرسالة: ' . $e->getMessage(),
                'error_code' => 'SEND_MESSAGE_ERROR',
            ], 500);
        }
    }

    /**
     * تحديد الرسائل كمقروءة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
        ]);

        $conversationId = $request->input('conversation_id');

        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // تحديث last_read_at
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * البحث عن طلب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchOrder(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $query = $request->input('query');

        // بناء الاستعلام
        $ordersQuery = Order::query();

        // البحث في رقم الطلب، رقم الهاتف، الرابط، أو كود الوسيط
        $ordersQuery->where(function ($q) use ($query) {
            $q->where('order_number', 'like', "%{$query}%")
                ->orWhere('customer_phone', 'like', "%{$query}%")
                ->orWhere('customer_social_link', 'like', "%{$query}%")
                ->orWhere('delivery_code', 'like', "%{$query}%");
        });

        $orders = $ordersQuery->with(['delegate', 'items.product.warehouse'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_social_link' => $order->customer_social_link,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'delegate_name' => $order->delegate ? $order->delegate->name : null,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'orders' => $orders,
            ],
        ]);
    }

    /**
     * إرسال رسالة تحتوي على طلب
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendOrderMessage(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'order_id' => 'required|exists:orders,id',
        ]);

        $conversationId = $request->input('conversation_id');
        $orderId = $request->input('order_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // جلب الطلب
        $order = Order::findOrFail($orderId);

        // إنشاء الرسالة
        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => "طلب: {$order->order_number}",
            'type' => 'order',
            'order_id' => $orderId,
        ]);

        // إرسال FCM Notification
        try {
            $otherParticipant = $conversation->getOtherParticipant($user->id);
            if ($otherParticipant) {
                $fcmService = app(\App\Services\FirebaseCloudMessagingService::class);
                $fcmService->sendMessageNotification($conversationId, $user->id, $otherParticipant->id, "طلب: {$order->order_number}");
            }
        } catch (\Exception $e) {
            Log::error('MobileDelegateChatController: Error sending FCM for order message: ' . $e->getMessage());
        }

        // تحديث وقت المحادثة
        $conversation->touch();

        // جلب بيانات الطلب الكاملة
        $order->load(['delegate', 'items.product.warehouse']);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'fromUserId' => $message->user_id,
                'text' => $message->message,
                'type' => $message->type,
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at->toIso8601String(),
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_social_link' => $order->customer_social_link,
                    'total_amount' => (float) $order->total_amount,
                    'status' => $order->status,
                    'delegate_name' => $order->delegate ? $order->delegate->name : null,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ],
            ]
        ]);
    }

    /**
     * البحث عن منتج
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchProduct(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $query = $request->input('query');

        // بناء الاستعلام
        $productsQuery = Product::query();

        // البحث في اسم المنتج أو الكود
        $productsQuery->where(function ($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
                ->orWhere('code', 'like', "%{$query}%");
        });

        $products = $productsQuery->with(['primaryImage', 'warehouse', 'sizes.reservations'])
            ->where('is_hidden', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'selling_price' => (float) $product->selling_price,
                    'gender_type' => $product->gender_type,
                    'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                    'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                    'sizes' => $product->sizes->map(function ($size) {
                        $reserved = $size->reservations()->sum('quantity_reserved');
                        return [
                            'id' => $size->id,
                            'size_name' => $size->size_name,
                            'quantity' => (int) $size->quantity,
                            'available_quantity' => (int) ($size->quantity - $reserved),
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $products,
            ],
        ]);
    }

    /**
     * إرسال رسالة تحتوي على منتج
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendProductMessage(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $conversationId = $request->input('conversation_id');
        $productId = $request->input('product_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // جلب المنتج
        $product = Product::findOrFail($productId);

        // إنشاء الرسالة
        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => "منتج: {$product->name}",
            'type' => 'product',
            'product_id' => $productId,
        ]);

        // إرسال FCM Notification
        try {
            $otherParticipant = $conversation->getOtherParticipant($user->id);
            if ($otherParticipant) {
                $fcmService = app(\App\Services\FirebaseCloudMessagingService::class);
                $fcmService->sendMessageNotification($conversationId, $user->id, $otherParticipant->id, "منتج: {$product->name}");
            }
        } catch (\Exception $e) {
            Log::error('MobileDelegateChatController: Error sending FCM for product message: ' . $e->getMessage());
        }

        // تحديث وقت المحادثة
        $conversation->touch();

        // جلب بيانات المنتج الكاملة
        $product->load(['primaryImage', 'warehouse', 'sizes.reservations']);

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'fromUserId' => $message->user_id,
                'text' => $message->message,
                'type' => $message->type,
                'time' => $message->created_at->format('g:i A'),
                'created_at' => $message->created_at->toIso8601String(),
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'selling_price' => (float) $product->selling_price,
                    'gender_type' => $product->gender_type,
                    'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                    'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                    'sizes' => $product->sizes->map(function ($size) {
                        $reserved = $size->reservations()->sum('quantity_reserved');
                        return [
                            'id' => $size->id,
                            'size_name' => $size->size_name,
                            'quantity' => (int) $size->quantity,
                            'available_quantity' => (int) ($size->quantity - $reserved),
                        ];
                    }),
                ],
            ]
        ]);
    }

    /**
     * جلب قائمة المستخدمين المتاحين للمراسلة
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableUsers(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مندوب
        if (!$user || !$user->isDelegate()) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح. يجب أن تكون مندوباً للوصول إلى هذه البيانات.',
                'error_code' => 'FORBIDDEN',
            ], 403);
        }

        $search = $request->input('search');

        // جلب المستخدمين المتاحين للمراسلة
        $usersQuery = User::where('id', '!=', $user->id);

        if ($search) {
            $usersQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $usersQuery->limit(50)
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'code' => $u->code,
                    'phone' => $u->phone,
                    'role' => $u->role,
                    'path' => $u->profile_image_url,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'users' => $users,
            ],
        ]);
    }
}

