<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SweetAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    protected $sweetAlertService;

    public function __construct(SweetAlertService $sweetAlertService)
    {
        $this->sweetAlertService = $sweetAlertService;
    }

    /**
     * عرض صفحة المراسلة
     */
    public function index()
    {
        $user = Auth::user();

        // جلب المستخدمين الذين يمكن المراسلة معهم
        $availableUsers = $user->getUsersWithSharedWarehouses();

        // التأكد من أن المدير يرى كل المستخدمين
        if ($user->isAdmin() && $availableUsers->isEmpty()) {
            $availableUsers = User::where('id', '!=', $user->id)->get();
        }

        // Log للتأكد من جلب المستخدمين
        \Log::info('Chat - User: ' . $user->name . ' (' . $user->role . ')');
        \Log::info('Chat - Available users count: ' . $availableUsers->count());
        \Log::info('Chat - Available users: ' . $availableUsers->pluck('name', 'role')->toJson());

        // جلب المحادثات الحالية
        $conversationsData = $user->conversations()
            ->with(['latestMessage.user', 'participants'])
            ->get()
            ->map(function($conversation) use ($user) {
                $otherParticipant = $conversation->getOtherParticipant($user->id);
                $unreadCount = $conversation->unreadCount($user->id);

                return [
                    'id' => $conversation->id,
                    'type' => $conversation->type,
                    'title' => $conversation->isGroup() ? $conversation->title : null,
                    'other_user' => $otherParticipant ? [
                        'id' => $otherParticipant->id,
                        'name' => $otherParticipant->name,
                        'code' => $otherParticipant->code,
                        'path' => 'profile-' . ($otherParticipant->id % 20 + 1) . '.jpeg',
                    ] : null,
                    'participants_count' => $conversation->isGroup() ? $conversation->participants()->count() : null,
                    'latest_message' => $conversation->latestMessage ? [
                        'text' => $conversation->latestMessage->message,
                        'time' => $conversation->latestMessage->created_at->format('g:i A'),
                    ] : null,
                    'unread_count' => $unreadCount,
                    'time' => $conversation->updated_at->format('g:i A'),
                ];
            });

        // تحويل المحادثات إلى تنسيق contactList
        $conversationsList = $conversationsData->map(function($conv) {
            if ($conv['type'] === 'group') {
                return [
                    'userId' => null,
                    'name' => $conv['title'] ?? 'Group',
                    'code' => null,
                    'path' => 'group-icon.svg',
                    'time' => $conv['time'],
                    'preview' => $conv['latest_message']['text'] ?? '',
                    'messages' => [],
                    'active' => true,
                    'conversationId' => $conv['id'],
                    'type' => 'group',
                    'participants_count' => $conv['participants_count'],
                ];
            } else {
                return [
                    'userId' => $conv['other_user']['id'] ?? null,
                    'name' => $conv['other_user']['name'] ?? 'Unknown',
                    'code' => $conv['other_user']['code'] ?? null,
                    'path' => $conv['other_user']['path'] ?? 'profile-1.jpeg',
                    'time' => $conv['time'],
                    'preview' => $conv['latest_message']['text'] ?? '',
                    'messages' => [],
                    'active' => true,
                    'conversationId' => $conv['id'],
                    'type' => 'direct',
                ];
            }
        });

        // تحويل المستخدمين المتاحين إلى تنسيق مناسب للعرض
        // في Contacts نعرض كل المستخدمين المتاحين (حتى لو كان لديهم محادثة)
        $availableUsersList = $availableUsers->map(function($user) use ($conversationsData) {
            // البحث عن محادثة موجودة مع هذا المستخدم
            $existingConversation = $conversationsData->first(function($conv) use ($user) {
                return ($conv['other_user']['id'] ?? null) == $user->id;
            });

            return [
                'id' => $user->id,
                'userId' => $user->id,
                'name' => $user->name,
                'code' => $user->code,
                'path' => 'profile-' . (($user->id % 20) + 1) . '.jpeg',
                'time' => $existingConversation ? $existingConversation['time'] : '',
                'preview' => $existingConversation ? ($existingConversation['latest_message']['text'] ?? '') : '',
                'messages' => [],
                'active' => true,
                'conversationId' => $existingConversation ? $existingConversation['id'] : null,
            ];
        });

        // جلب جميع المستخدمين المتاحين للمجموعات (للمدير فقط)
        $availableUsersForGroup = collect();
        if ($user->isAdmin()) {
            $availableUsersForGroup = User::where('id', '!=', $user->id)
                ->select('id', 'name', 'role', 'code')
                ->get()
                ->map(function($u) {
                    return [
                        'id' => $u->id,
                        'name' => $u->name,
                        'role' => $u->role,
                        'code' => $u->code,
                    ];
                });
        }

        // إرجاع القائمتين منفصلتين
        return view('apps.chat', compact('availableUsers', 'conversationsList', 'availableUsersList', 'availableUsersForGroup'));
    }

    /**
     * جلب المحادثات (AJAX)
     */
    public function getConversations()
    {
        $user = Auth::user();

        $conversations = $user->conversations()
            ->with(['latestMessage.user', 'participants'])
            ->get()
            ->map(function($conversation) use ($user) {
                $otherParticipant = $conversation->getOtherParticipant($user->id);
                $unreadCount = $conversation->unreadCount($user->id);

                if ($conversation->isGroup()) {
                    return [
                        'id' => $conversation->id,
                        'type' => 'group',
                        'userId' => null,
                        'name' => $conversation->title,
                        'code' => null,
                        'path' => 'group-icon.svg',
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
                        'path' => $otherParticipant ? 'profile-' . ($otherParticipant->id % 20 + 1) . '.jpeg' : 'profile-1.jpeg',
                        'preview' => $conversation->latestMessage ? substr($conversation->latestMessage->message, 0, 50) : '',
                        'time' => $conversation->updated_at->format('g:i A'),
                        'active' => $otherParticipant ? true : false,
                        'unread_count' => $unreadCount,
                    ];
                }
            });

        return response()->json($conversations);
    }

    /**
     * جلب أو إنشاء محادثة مع مستخدم
     */
    public function getOrCreateConversation(Request $request)
    {
        $user = Auth::user();
        $otherUserId = $request->input('user_id');

        // التحقق من أن المستخدم يمكنه المراسلة مع هذا المستخدم
        $availableUsers = $user->getUsersWithSharedWarehouses();
        $otherUser = $availableUsers->firstWhere('id', $otherUserId);

        if (!$otherUser) {
            return response()->json(['error' => 'لا يمكنك المراسلة مع هذا المستخدم'], 403);
        }

        // البحث عن محادثة موجودة
        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->whereHas('participants', function($q) use ($otherUserId) {
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
            'conversation_id' => $conversation->id,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => $otherUser->name,
                'path' => 'profile-' . ($otherUser->id % 20 + 1) . '.jpeg',
            ]
        ]);
    }

    /**
     * جلب الرسائل (AJAX)
     */
    public function getMessages(Request $request)
    {
        $user = Auth::user();
        $conversationId = $request->input('conversation_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // تحديث last_read_at
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        $otherParticipant = $conversation->getOtherParticipant($user->id);

        $messages = $conversation->messages()
            ->with(['user', 'order.delegate', 'order.items.product.warehouse', 'product.primaryImage', 'product.warehouse', 'product.sizes.reservations'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function($message) use ($user, $otherParticipant, $conversation) {
                $data = [
                    'id' => $message->id,
                    'fromUserId' => $message->user_id,
                    'toUserId' => $message->user_id == $user->id ? ($otherParticipant ? $otherParticipant->id : 0) : $user->id,
                    'text' => $message->message,
                    'type' => $message->type ?? 'text',
                    'time' => $message->created_at->format('g:i A'),
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
                        'total_amount' => $order->total_amount,
                        'status' => $order->status,
                        'delegate_name' => $order->delegate ? $order->delegate->name : null,
                        'created_at' => $order->created_at->format('Y-m-d H:i'),
                    ];
                }

                // إذا كانت الرسالة تحتوي على منتج
                if ($message->type === 'product' && $message->product) {
                    $product = $message->product;
                    $data['product'] = [
                        'id' => $product->id,
                        'name' => $product->name,
                        'code' => $product->code,
                        'selling_price' => $product->selling_price,
                        'gender_type' => $product->gender_type,
                        'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                        'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                        'sizes' => $product->sizes->map(function($size) {
                            $reserved = $size->reservations()->sum('quantity_reserved');
                            return [
                                'id' => $size->id,
                                'size_name' => $size->size_name,
                                'quantity' => $size->quantity,
                                'available_quantity' => $size->quantity - $reserved,
                            ];
                        }),
                    ];
                }

                // إذا كانت المحادثة مجموعة، أضف اسم المرسل
                if ($conversation->type === 'group') {
                    $data['sender_name'] = $message->user->name;
                    $data['sender_id'] = $message->user_id;
                }

                // إذا كانت الرسالة تحتوي على صورة
                if ($message->image_path) {
                    $data['image_url'] = asset('storage/' . $message->image_path);
                }

                return $data;
            });

        // Log للتأكد من جلب الرسائل
        \Log::info('Chat - Get messages for conversation: ' . $conversationId);
        \Log::info('Chat - Messages count: ' . $messages->count());
        \Log::info('Chat - User: ' . $user->name . ', Other: ' . ($otherParticipant ? $otherParticipant->name : 'None'));

        return response()->json($messages);
    }

    /**
     * إرسال رسالة (AJAX)
     */
    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'conversation_id' => 'required|exists:conversations,id',
                'message' => 'nullable|string|max:5000',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        $user = Auth::user();
        $conversationId = $request->input('conversation_id');

        try {
            // التحقق من أن المستخدم مشارك في المحادثة
            $conversation = Conversation::whereHas('participants', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->findOrFail($conversationId);

            // معالجة رفع الصورة
            $imagePath = null;
            $messageType = 'text';

            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imagePath = $image->store('messages', 'public');
                $messageType = 'image';
            }

            // التحقق من وجود نص أو صورة
            if (empty($request->input('message')) && !$imagePath) {
                return response()->json(['error' => 'يجب إدخال رسالة أو رفع صورة'], 400);
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
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Chat - Error sending SweetAlert: ' . $e->getMessage());
            }

            // Log للتأكد من إرسال الرسالة
            \Log::info('Chat - Send message');
            \Log::info('Chat - Conversation ID: ' . $conversationId);
            \Log::info('Chat - User: ' . $user->name . ' (' . $user->role . ')');
            \Log::info('Chat - Message: ' . $request->input('message'));
            \Log::info('Chat - Image: ' . ($imagePath ? 'Yes' : 'No'));

            $responseData = [
                'success' => true,
                'message' => [
                    'id' => $message->id,
                    'fromUserId' => $message->user_id,
                    'text' => $message->message,
                    'type' => $message->type,
                    'time' => $message->created_at->format('g:i A'),
                ]
            ];

            if ($imagePath) {
                $responseData['message']['image_url'] = asset('storage/' . $imagePath);
            }

            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('Chat - Error sending message: ' . $e->getMessage());
            \Log::error('Chat - Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ في إرسال الرسالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * إرسال رسالة لمستخدم (إنشاء محادثة تلقائياً إذا لزم الأمر)
     */
    public function sendMessageToUser(Request $request)
    {
        try {
            $request->validate([
                'user_id' => 'required|exists:users,id',
                'message' => 'nullable|string|max:5000',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB max
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'خطأ في التحقق من البيانات',
                'errors' => $e->errors()
            ], 422);
        }

        $user = Auth::user();
        $otherUserId = $request->input('user_id');

        try {
            // الحصول على أو إنشاء المحادثة
            $conversationResponse = $this->getOrCreateConversation(new Request(['user_id' => $otherUserId]));
            $conversationData = json_decode($conversationResponse->getContent(), true);

            if (isset($conversationData['error'])) {
                return response()->json($conversationData, 403);
            }

            $conversationId = $conversationData['conversation_id'];

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
            \Log::error('Chat - Error sending message to user: ' . $e->getMessage());
            \Log::error('Chat - Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'error' => 'حدث خطأ في إرسال الرسالة: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديد الرسائل كمقروءة
     */
    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        $conversationId = $request->input('conversation_id');

        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // تحديث last_read_at
        $conversation->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now()
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * البحث عن طلب
     */
    public function searchOrder(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:3',
        ]);

        $user = Auth::user();
        $query = $request->input('query');

        // بناء الاستعلام
        $ordersQuery = Order::query();

        // البحث في رقم الطلب، رقم الهاتف، الرابط، أو كود الوسيط
        $ordersQuery->where(function($q) use ($query) {
            $q->where('order_number', 'like', "%{$query}%")
              ->orWhere('customer_phone', 'like', "%{$query}%")
              ->orWhere('customer_social_link', 'like', "%{$query}%")
              ->orWhere('delivery_code', 'like', "%{$query}%");
        });

        // إزالة جميع قيود الفلترة - جميع المستخدمين يمكنهم البحث عن أي طلب

        $orders = $ordersQuery->with(['delegate', 'items.product.warehouse'])
                             ->orderBy('created_at', 'desc')
                             ->limit(10)
                             ->get()
                             ->map(function($order) {
                                 return [
                                     'id' => $order->id,
                                     'order_number' => $order->order_number,
                                     'customer_name' => $order->customer_name,
                                     'customer_phone' => $order->customer_phone,
                                     'customer_social_link' => $order->customer_social_link,
                                     'total_amount' => $order->total_amount,
                                     'status' => $order->status,
                                     'delegate_name' => $order->delegate ? $order->delegate->name : null,
                                     'created_at' => $order->created_at->format('Y-m-d H:i'),
                                 ];
                             });

        return response()->json($orders);
    }

    /**
     * إرسال رسالة تحتوي على طلب
     */
    public function sendOrderMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'order_id' => 'required|exists:orders,id',
        ]);

        $user = Auth::user();
        $conversationId = $request->input('conversation_id');
        $orderId = $request->input('order_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // جلب الطلب
        $order = Order::findOrFail($orderId);

        // إزالة جميع التحققات من الصلاحيات - جميع المستخدمين يمكنهم إرسال أي طلب

        // إنشاء الرسالة
        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => "طلب: {$order->order_number}",
            'type' => 'order',
            'order_id' => $orderId,
        ]);

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
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'customer_social_link' => $order->customer_social_link,
                    'total_amount' => $order->total_amount,
                    'status' => $order->status,
                    'delegate_name' => $order->delegate ? $order->delegate->name : null,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                ],
                'time' => $message->created_at->format('g:i A'),
            ]
        ]);
    }

    /**
     * البحث عن منتج
     */
    public function searchProduct(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $user = Auth::user();
        $query = $request->input('query');

        // بناء الاستعلام
        $productsQuery = Product::query();

        // البحث في اسم المنتج أو الكود
        $productsQuery->where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('code', 'like', "%{$query}%");
        });

        // إزالة جميع قيود الفلترة - جميع المستخدمين يمكنهم البحث عن أي منتج

        $products = $productsQuery->with(['primaryImage', 'warehouse', 'sizes.reservations'])
                                 ->where('is_hidden', false)
                                 ->orderBy('created_at', 'desc')
                                 ->limit(10)
                                 ->get()
                                 ->map(function($product) {
                                     return [
                                         'id' => $product->id,
                                         'name' => $product->name,
                                         'code' => $product->code,
                                         'selling_price' => $product->selling_price,
                                         'gender_type' => $product->gender_type,
                                         'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                                         'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                                         'sizes' => $product->sizes->map(function($size) {
                                             $reserved = $size->reservations()->sum('quantity_reserved');
                                             return [
                                                 'id' => $size->id,
                                                 'size_name' => $size->size_name,
                                                 'quantity' => $size->quantity,
                                                 'available_quantity' => $size->quantity - $reserved,
                                             ];
                                         }),
                                     ];
                                 });

        return response()->json($products);
    }

    /**
     * إرسال رسالة تحتوي على منتج
     */
    public function sendProductMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'product_id' => 'required|exists:products,id',
        ]);

        $user = Auth::user();
        $conversationId = $request->input('conversation_id');
        $productId = $request->input('product_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // جلب المنتج
        $product = Product::findOrFail($productId);

        // إزالة جميع التحققات من الصلاحيات - جميع المستخدمين يمكنهم إرسال أي منتج

        // إنشاء الرسالة
        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => "منتج: {$product->name}",
            'type' => 'product',
            'product_id' => $productId,
        ]);

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
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'code' => $product->code,
                    'selling_price' => $product->selling_price,
                    'gender_type' => $product->gender_type,
                    'warehouse_name' => $product->warehouse ? $product->warehouse->name : null,
                    'image_url' => $product->primaryImage ? $product->primaryImage->image_url : null,
                    'sizes' => $product->sizes->map(function($size) {
                        $reserved = $size->reservations()->sum('quantity_reserved');
                        return [
                            'id' => $size->id,
                            'size_name' => $size->size_name,
                            'quantity' => $size->quantity,
                            'available_quantity' => $size->quantity - $reserved,
                        ];
                    }),
                ],
                'time' => $message->created_at->format('g:i A'),
            ]
        ]);
    }

    /**
     * إنشاء مجموعة جديدة (للمدير فقط)
     */
    public function createGroup(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'غير مصرح - المدير فقط يمكنه إنشاء المجموعات'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        // إنشاء المجموعة
        $conversation = Conversation::create([
            'type' => 'group',
            'title' => $request->input('title'),
            'warehouse_id' => null, // المجموعات لا ترتبط بمخزن محدد
        ]);

        // إضافة المدير والمستخدمين المختارين
        $participants = array_merge([$user->id], $request->input('user_ids'));
        $conversation->participants()->attach($participants);

        // جلب بيانات المجموعة الكاملة
        $conversation->load('participants');

        return response()->json([
            'success' => true,
            'conversation_id' => $conversation->id,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'type' => 'group',
                'participants_count' => $conversation->participants()->count(),
                'participants' => $conversation->participants->map(function($participant) {
                    return [
                        'id' => $participant->id,
                        'name' => $participant->name,
                        'code' => $participant->code,
                        'role' => $participant->role,
                    ];
                }),
            ]
        ]);
    }

    /**
     * إضافة مستخدمين للمجموعة (للمدير فقط)
     */
    public function addParticipantsToGroup(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'غير مصرح - المدير فقط يمكنه إضافة مستخدمين'], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
        ]);

        $conversation = Conversation::findOrFail($request->input('conversation_id'));

        // التحقق من أن المحادثة مجموعة
        if (!$conversation->isGroup()) {
            return response()->json(['error' => 'هذه المحادثة ليست مجموعة'], 400);
        }

        // إضافة المستخدمين (تجنب التكرار)
        $existingParticipants = $conversation->participants()->pluck('user_id')->toArray();
        $newParticipants = array_diff($request->input('user_ids'), $existingParticipants);

        if (!empty($newParticipants)) {
            $conversation->participants()->attach($newParticipants);
        }

        return response()->json([
            'success' => true,
            'added_count' => count($newParticipants),
            'participants_count' => $conversation->participants()->count(),
        ]);
    }

    /**
     * إزالة مستخدم من المجموعة (للمدير فقط)
     */
    public function removeParticipantFromGroup(Request $request)
    {
        $user = Auth::user();

        // التحقق من أن المستخدم مدير
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'غير مصرح - المدير فقط يمكنه إزالة مستخدمين'], 403);
        }

        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'user_id' => 'required|exists:users,id',
        ]);

        $conversation = Conversation::findOrFail($request->input('conversation_id'));

        // التحقق من أن المحادثة مجموعة
        if (!$conversation->isGroup()) {
            return response()->json(['error' => 'هذه المحادثة ليست مجموعة'], 400);
        }

        // منع إزالة المدير من المجموعة
        if ($request->input('user_id') == $user->id) {
            return response()->json(['error' => 'لا يمكنك إزالة نفسك من المجموعة'], 400);
        }

        // إزالة المستخدم
        $conversation->participants()->detach($request->input('user_id'));

        return response()->json([
            'success' => true,
            'participants_count' => $conversation->participants()->count(),
        ]);
    }

    /**
     * جلب قائمة المشاركين في المجموعة
     */
    public function getGroupParticipants(Request $request, $conversationId)
    {
        $user = Auth::user();

        $conversation = Conversation::findOrFail($conversationId);

        // التحقق من أن المستخدم مشارك في المجموعة
        if (!$conversation->participants()->where('user_id', $user->id)->exists()) {
            return response()->json(['error' => 'غير مصرح'], 403);
        }

        // التحقق من أن المحادثة مجموعة
        if (!$conversation->isGroup()) {
            return response()->json(['error' => 'هذه المحادثة ليست مجموعة'], 400);
        }

        $participants = $conversation->participants()->get()->map(function($participant) {
            return [
                'id' => $participant->id,
                'name' => $participant->name,
                'code' => $participant->code,
                'role' => $participant->role,
                'path' => 'profile-' . ($participant->id % 20 + 1) . '.jpeg',
            ];
        });

        return response()->json([
            'conversation_id' => $conversation->id,
            'title' => $conversation->title,
            'participants' => $participants,
            'participants_count' => $participants->count(),
            'is_admin' => $user->isAdmin(),
        ]);
    }
}
