<div x-data="globalNotifications()" x-init="init()" class="fixed top-4 ltr:right-4 rtl:left-4 z-50 space-y-2 max-w-md w-full">
    <template x-for="notification in notifications" :key="notification.id">
        <div
            x-show="notification.visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-full"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform translate-x-full"
            @click="handleNotificationClick(notification)"
            class="panel cursor-pointer hover:shadow-lg transition-all duration-300 border-l-4"
            :class="{
                'border-primary': notification.type === 'message',
                'border-success': notification.type === 'order',
                'border-warning': notification.type === 'product',
                'border-info': !['message', 'order', 'product'].includes(notification.type)
            }"
        >
            <div class="flex items-start gap-3">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                        :class="{
                            'bg-primary/20 text-primary': notification.type === 'message',
                            'bg-success/20 text-success': notification.type === 'order',
                            'bg-warning/20 text-warning': notification.type === 'product',
                            'bg-info/20 text-info': !['message', 'order', 'product'].includes(notification.type)
                        }">
                        <svg x-show="notification.type === 'message'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                        </svg>
                        <svg x-show="notification.type === 'order'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg x-show="notification.type === 'product'" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <svg x-show="!['message', 'order', 'product'].includes(notification.type)" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-semibold text-sm mb-1" x-text="notification.title"></h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 line-clamp-2" x-text="notification.body"></p>
                </div>
                <button
                    @click.stop="removeNotification(notification.id)"
                    class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>
    </template>
</div>

<script>
function globalNotifications() {
    return {
        notifications: [],

        init() {
            console.log('GlobalNotifications: Initializing...');
            
            // الاستماع للإشعارات من SSE
            if (window.notificationManager) {
                console.log('GlobalNotifications: NotificationManager found, registering callback');
                window.notificationManager.onNotification((notification) => {
                    console.log('GlobalNotifications: Notification received via callback:', notification);
                    this.addNotification(notification);
                });
            } else {
                console.warn('GlobalNotifications: NotificationManager not found');
                // محاولة مرة أخرى بعد 1 ثانية
                setTimeout(() => {
                    if (window.notificationManager) {
                        window.notificationManager.onNotification((notification) => {
                            console.log('GlobalNotifications: Notification received via callback (delayed):', notification);
                            this.addNotification(notification);
                        });
                    }
                }, 1000);
            }
            
            // الاستماع للإشعارات من custom events أيضاً
            window.addEventListener('newNotification', (e) => {
                console.log('GlobalNotifications: Notification received via event:', e.detail);
                this.addNotification(e.detail);
            });
        },

        addNotification(notification) {
            console.log('GlobalNotifications: addNotification called', notification);
            
            const notificationData = {
                id: notification.id || Date.now() + Math.random(),
                type: notification.type || notification.data?.type || 'message',
                title: notification.title || 'إشعار جديد',
                body: notification.body || notification.message_text || notification.data?.message_text || 'لديك إشعار جديد',
                data: notification.data || {},
                visible: true,
            };
            
            console.log('GlobalNotifications: Adding notification:', notificationData);
            this.notifications.unshift(notificationData);
            console.log('GlobalNotifications: Notifications count:', this.notifications.length);
            
            // إزالة الإشعار بعد 5 ثوان
            setTimeout(() => {
                this.removeNotification(notificationData.id);
            }, 5000);
            
            // تحديد كمقروء
            if (notificationData.id && typeof notificationData.id === 'number') {
                fetch(`/api/notifications/${notificationData.id}/mark-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                    },
                }).catch(err => console.error('Error marking notification as read:', err));
            }
        },

        removeNotification(id) {
            const index = this.notifications.findIndex(n => n.id === id);
            if (index > -1) {
                this.notifications[index].visible = false;
                setTimeout(() => {
                    this.notifications.splice(index, 1);
                }, 200);
            }
        },

        handleNotificationClick(notification) {
            // الانتقال للمحادثة إذا كان نوع الإشعار message
            if (notification.type === 'message' && notification.data?.conversation_id) {
                window.location.href = `/chat?conversation=${notification.data.conversation_id}`;
            } else if (notification.type === 'order' && notification.data?.order_id) {
                window.location.href = `/admin/orders-management?search=${notification.data.order_id}`;
            } else if (notification.type === 'product' && notification.data?.product_id) {
                window.location.href = `/admin/products?search=${notification.data.product_id}`;
            }

            this.removeNotification(notification.id);
        }
    }
}
</script>

