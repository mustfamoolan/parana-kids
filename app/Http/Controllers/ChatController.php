<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
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
                    'other_user' => $otherParticipant ? [
                        'id' => $otherParticipant->id,
                        'name' => $otherParticipant->name,
                        'code' => $otherParticipant->code,
                        'path' => 'profile-' . ($otherParticipant->id % 20 + 1) . '.jpeg',
                    ] : null,
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
            ];
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

        // إرجاع القائمتين منفصلتين
        return view('apps.chat', compact('availableUsers', 'conversationsList', 'availableUsersList'));
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

                return [
                    'id' => $conversation->id,
                    'userId' => $otherParticipant ? $otherParticipant->id : null,
                    'name' => $otherParticipant ? $otherParticipant->name : 'Unknown',
                    'code' => $otherParticipant ? $otherParticipant->code : null,
                    'path' => $otherParticipant ? 'profile-' . ($otherParticipant->id % 20 + 1) . '.jpeg' : 'profile-1.jpeg',
                    'preview' => $conversation->latestMessage ? substr($conversation->latestMessage->message, 0, 50) : '',
                    'time' => $conversation->updated_at->format('g:i A'),
                    'active' => $otherParticipant ? true : false,
                    'unread_count' => $unreadCount,
                ];
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
            ->map(function($message) use ($user, $otherParticipant) {
                $data = [
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
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::user();
        $conversationId = $request->input('conversation_id');

        // التحقق من أن المستخدم مشارك في المحادثة
        $conversation = Conversation::whereHas('participants', function($q) use ($user) {
            $q->where('user_id', $user->id);
        })->findOrFail($conversationId);

        // إنشاء الرسالة
        $message = Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $user->id,
            'message' => $request->input('message'),
        ]);

        // تحديث وقت المحادثة
        $conversation->touch();

        // Log للتأكد من إرسال الرسالة
        \Log::info('Chat - Send message');
        \Log::info('Chat - Conversation ID: ' . $conversationId);
        \Log::info('Chat - User: ' . $user->name . ' (' . $user->role . ')');
        \Log::info('Chat - Message: ' . $request->input('message'));

        return response()->json([
            'success' => true,
            'message' => [
                'id' => $message->id,
                'fromUserId' => $message->user_id,
                'text' => $message->message,
                'time' => $message->created_at->format('g:i A'),
            ]
        ]);
    }

    /**
     * إرسال رسالة لمستخدم (إنشاء محادثة تلقائياً إذا لزم الأمر)
     */
    public function sendMessageToUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'message' => 'required|string|max:5000',
        ]);

        $user = Auth::user();
        $otherUserId = $request->input('user_id');

        // الحصول على أو إنشاء المحادثة
        $conversationResponse = $this->getOrCreateConversation(new Request(['user_id' => $otherUserId]));
        $conversationData = json_decode($conversationResponse->getContent(), true);

        if (isset($conversationData['error'])) {
            return response()->json($conversationData, 403);
        }

        $conversationId = $conversationData['conversation_id'];

        // إرسال الرسالة
        return $this->sendMessage(new Request([
            'conversation_id' => $conversationId,
            'message' => $request->input('message'),
        ]));
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
}
