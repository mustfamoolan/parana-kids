<x-layout.default>


    <div x-data="chat">
        <div class="flex gap-0 xl:gap-5 relative h-screen sm:h-[calc(100vh_-_150px)] overflow-hidden">
            <div class="panel p-4 flex-none overflow-hidden w-full xl:max-w-xs absolute xl:relative z-10 space-y-4 h-full xl:h-full block xl:block inset-0 xl:inset-auto"
                :class="isShowUserChat && 'hidden xl:block'">
                <div class="flex justify-between items-center">
                    <div class="flex items-center">
                        <div class="flex-none"><img src="{{ auth()->user()->profile_image_url }}"
                                class="rounded-full h-12 w-12 object-cover" /></div>
                        <div class="mx-3">
                            <p class="mb-1 font-semibold">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-white-dark">{{ ucfirst(auth()->user()->role) }}</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <button type="button"
                        class="hover:text-primary group"
                        :class="{ 'text-primary': activeTab === 'chats' }"
                        @click="activeTab = 'chats'">

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto mb-1">
                            <path opacity="0.5"
                                d="M13.0867 21.3877L13.7321 21.7697L13.0867 21.3877ZM13.6288 20.4718L12.9833 20.0898L13.6288 20.4718ZM10.3712 20.4718L9.72579 20.8539H9.72579L10.3712 20.4718ZM10.9133 21.3877L11.5587 21.0057L10.9133 21.3877ZM13.5 2.75C13.9142 2.75 14.25 2.41421 14.25 2C14.25 1.58579 13.9142 1.25 13.5 1.25V2.75ZM22.75 10.5C22.75 10.0858 22.4142 9.75 22 9.75C21.5858 9.75 21.25 10.0858 21.25 10.5H22.75ZM2.3806 15.9134L3.07351 15.6264V15.6264L2.3806 15.9134ZM7.78958 18.9915L7.77666 19.7413L7.78958 18.9915ZM5.08658 18.6194L4.79957 19.3123H4.79957L5.08658 18.6194ZM21.6194 15.9134L22.3123 16.2004V16.2004L21.6194 15.9134ZM16.2104 18.9915L16.1975 18.2416L16.2104 18.9915ZM18.9134 18.6194L19.2004 19.3123H19.2004L18.9134 18.6194ZM4.38751 2.7368L3.99563 2.09732V2.09732L4.38751 2.7368ZM2.7368 4.38751L2.09732 3.99563H2.09732L2.7368 4.38751ZM9.40279 19.2098L9.77986 18.5615L9.77986 18.5615L9.40279 19.2098ZM13.7321 21.7697L14.2742 20.8539L12.9833 20.0898L12.4412 21.0057L13.7321 21.7697ZM9.72579 20.8539L10.2679 21.7697L11.5587 21.0057L11.0166 20.0898L9.72579 20.8539ZM12.4412 21.0057C12.2485 21.3313 11.7515 21.3313 11.5587 21.0057L10.2679 21.7697C11.0415 23.0767 12.9585 23.0767 13.7321 21.7697L12.4412 21.0057ZM10.5 2.75H13.5V1.25H10.5V2.75ZM21.25 10.5V11.5H22.75V10.5H21.25ZM2.75 11.5V10.5H1.25V11.5H2.75ZM1.25 11.5C1.25 12.6546 1.24959 13.5581 1.29931 14.2868C1.3495 15.0223 1.45323 15.6344 1.68769 16.2004L3.07351 15.6264C2.92737 15.2736 2.84081 14.8438 2.79584 14.1847C2.75041 13.5189 2.75 12.6751 2.75 11.5H1.25ZM7.8025 18.2416C6.54706 18.2199 5.88923 18.1401 5.37359 17.9265L4.79957 19.3123C5.60454 19.6457 6.52138 19.7197 7.77666 19.7413L7.8025 18.2416ZM1.68769 16.2004C2.27128 17.6093 3.39066 18.7287 4.79957 19.3123L5.3736 17.9265C4.33223 17.4951 3.50486 16.6678 3.07351 15.6264L1.68769 16.2004ZM21.25 11.5C21.25 12.6751 21.2496 13.5189 21.2042 14.1847C21.1592 14.8438 21.0726 15.2736 20.9265 15.6264L22.3123 16.2004C22.5468 15.6344 22.6505 15.0223 22.7007 14.2868C22.7504 13.5581 22.75 12.6546 22.75 11.5H21.25ZM16.2233 19.7413C17.4786 19.7197 18.3955 19.6457 19.2004 19.3123L18.6264 17.9265C18.1108 18.1401 17.4529 18.2199 16.1975 18.2416L16.2233 19.7413ZM20.9265 15.6264C20.4951 16.6678 19.6678 17.4951 18.6264 17.9265L19.2004 19.3123C20.6093 18.7287 21.7287 17.6093 22.3123 16.2004L20.9265 15.6264ZM10.5 1.25C8.87781 1.25 7.6085 1.24921 6.59611 1.34547C5.57256 1.44279 4.73445 1.64457 3.99563 2.09732L4.77938 3.37628C5.24291 3.09223 5.82434 2.92561 6.73809 2.83873C7.663 2.75079 8.84876 2.75 10.5 2.75V1.25ZM2.75 10.5C2.75 8.84876 2.75079 7.663 2.83873 6.73809C2.92561 5.82434 3.09223 5.24291 3.37628 4.77938L2.09732 3.99563C1.64457 4.73445 1.44279 5.57256 1.34547 6.59611C1.24921 7.6085 1.25 8.87781 1.25 10.5H2.75ZM3.99563 2.09732C3.22194 2.57144 2.57144 3.22194 2.09732 3.99563L3.37628 4.77938C3.72672 4.20752 4.20752 3.72672 4.77938 3.37628L3.99563 2.09732ZM11.0166 20.0898C10.8136 19.7468 10.6354 19.4441 10.4621 19.2063C10.2795 18.9559 10.0702 18.7304 9.77986 18.5615L9.02572 19.8582C9.07313 19.8857 9.13772 19.936 9.24985 20.0898C9.37122 20.2564 9.50835 20.4865 9.72579 20.8539L11.0166 20.0898ZM7.77666 19.7413C8.21575 19.7489 8.49387 19.7545 8.70588 19.7779C8.90399 19.7999 8.98078 19.832 9.02572 19.8582L9.77986 18.5615C9.4871 18.3912 9.18246 18.3215 8.87097 18.287C8.57339 18.2541 8.21375 18.2487 7.8025 18.2416L7.77666 19.7413ZM14.2742 20.8539C14.4916 20.4865 14.6287 20.2564 14.7501 20.0898C14.8622 19.936 14.9268 19.8857 14.9742 19.8582L14.2201 18.5615C13.9298 18.7304 13.7204 18.9559 13.5379 19.2063C13.3646 19.4441 13.1864 19.7468 12.9833 20.0898L14.2742 20.8539ZM16.1975 18.2416C15.7862 18.2487 15.4266 18.2541 15.129 18.287C14.8175 18.3215 14.5129 18.3912 14.2201 18.5615L14.9742 19.8582C15.0192 19.832 15.096 19.7999 15.2941 19.7779C15.5061 19.7545 15.7842 19.7489 16.2233 19.7413L16.1975 18.2416Z"
                                fill="currentColor" />
                            <circle cx="19" cy="5" r="3" stroke="currentColor"
                                stroke-width="1.5" />
                        </svg>
                        Chats </button>

                    <button type="button"
                        class="hover:text-primary group"
                        :class="{ 'text-primary': activeTab === 'contacts' }"
                        @click="activeTab = 'contacts'">

                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                            xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mx-auto mb-1">
                            <circle cx="10" cy="6" r="4" stroke="currentColor"
                                stroke-width="1.5" />
                            <path opacity="0.5"
                                d="M18 17.5C18 19.9853 18 22 10 22C2 22 2 19.9853 2 17.5C2 15.0147 5.58172 13 10 13C14.4183 13 18 15.0147 18 17.5Z"
                                stroke="currentColor" stroke-width="1.5" />
                            <path d="M21 10H19M19 10H17M19 10L19 8M19 10L19 12" stroke="currentColor"
                                stroke-width="1.5" stroke-linecap="round" />
                        </svg>
                        Contacts </button>
                </div>
                @if(auth()->user()->isAdmin())
                <div class="flex justify-center">
                    <button type="button"
                        class="btn btn-primary btn-sm w-full"
                        @click="showCreateGroupModal = true">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="ltr:mr-2 rtl:ml-2">
                            <path d="M12 5v14m7-7H5"></path>
                        </svg>
                        إنشاء مجموعة
                    </button>
                </div>
                @endif
                <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                <div class="!mt-0">
                    <div
                        class="chat-users perfect-scrollbar relative h-full min-h-[100px] sm:h-[calc(100vh_-_357px)] xl:h-[calc(100vh_-_357px)] space-y-0.5 pr-3.5 -mr-3.5">
                        <template x-for="person in searchUsers">
                            <button type="button"
                                class="w-full flex justify-between items-center p-2 hover:bg-gray-100 dark:hover:bg-[#050b14] rounded-md dark:hover:text-primary hover:text-primary "
                                :class="{
                                    'bg-gray-100 dark:bg-[#050b14] dark:text-primary text-primary': selectedUser
                                        .conversationId === person.conversationId
                                }"
                                @click="selectUser(person)">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 relative">
                                            <template x-if="person.type === 'group'">
                                                <div class="rounded-full h-12 w-12 bg-primary/20 flex items-center justify-center">
                                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                        <circle cx="9" cy="7" r="4"></circle>
                                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                                    </svg>
                                                </div>
                                            </template>
                                            <template x-if="person.type !== 'group'">
                                                <img :src="person.path || `/assets/images/profile-${(person.userId || person.id) % 20 + 1}.jpeg`"
                                                class="rounded-full h-12 w-12 object-cover" />
                                            </template>
                                            <!-- Badge للإشعارات غير المقروءة - فوق صورة البروفايل -->
                                            <template x-if="person.conversationId && person.type !== 'group'">
                                                <span :id="`conversation-badge-${person.conversationId}`" class="hidden absolute -top-1 ltr:-right-1 rtl:-left-1 w-5 h-5 bg-danger rounded-full border-2 border-white dark:border-gray-800 shadow-lg z-20"></span>
                                            </template>
                                            <!-- النقطة الخضراء (active status) - تظهر فقط إذا لم يكن هناك إشعار غير مقروء -->
                                            <template x-if="person.active && person.type !== 'group'">
                                                <div :id="`active-indicator-${person.conversationId}`" class="absolute bottom-0 ltr:right-0 rtl:left-0">
                                                    <div class="w-4 h-4 bg-success rounded-full"></div>
                                                </div>
                                            </template>
                                        </div>
                                        <div class="mx-3 ltr:text-left rtl:text-right">
                                            <div class="flex items-center gap-2 mb-1">
                                                <p class="font-semibold" x-text="person.name"></p>
                                                <template x-if="person.code && person.type !== 'group'">
                                                    <span class="badge badge-outline-primary text-xs" x-text="person.code"></span>
                                                </template>
                                                <template x-if="person.type === 'group' && person.participants_count">
                                                    <span class="badge badge-outline-info text-xs" x-text="person.participants_count + ' مشارك'"></span>
                                                </template>
                                            </div>
                                            <p class="text-xs text-white-dark truncate max-w-[185px]"
                                                x-text="person.preview"></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="font-semibold whitespace-nowrap text-xs flex flex-col items-end gap-1">
                                    <p x-text="person.time"></p>
                                    <template x-if="person.type === 'group' && loginUser.role === 'admin'">
                                        <button type="button"
                                            class="text-primary hover:text-primary-dark"
                                            @click.stop="openGroupManageModal(person.conversationId)">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="3"></circle>
                                                <path d="M12 1v6m0 6v6M5.64 5.64l4.24 4.24m8.24 8.24l-4.24-4.24m0-8.48l4.24 4.24M18.36 18.36l-4.24-4.24"></path>
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
            <div class="bg-black/60 z-[5] w-full h-full absolute rounded-md hidden"
                :class="isShowChatMenu && isShowUserChat && '!block xl:!hidden'" @click="isShowChatMenu = false; isShowUserChat = false"></div>
            <div class="panel p-0 flex-1 w-full h-full">
                <template x-if="!isShowUserChat">
                    <div class="flex items-center justify-center h-full relative p-4">
                        <button type="button"
                            class="xl:hidden absolute top-4 ltr:left-4 rtl:right-4 hover:text-primary"
                            @click="isShowChatMenu = !isShowChatMenu">

                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg" class="w-6 h-6">
                                <path d="M20 7L4 7" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <path opacity="0.5" d="M20 12L4 12" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                                <path d="M20 17L4 17" stroke="currentColor" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </button>

                        <div class="py-8 flex items-center justify-center flex-col">
                            <div
                                class="w-[280px] md:w-[430px] mb-8 h-[calc(100vh_-_320px)] min-h-[120px] text-white dark:text-[#0e1726]">
                                <svg xmlns="http://www.w3.org/2000/svg" data-name="Layer 1" class="w-full h-full"
                                    viewBox="0 0 891.29496 745.19434" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <ellipse cx="418.64354" cy="727.19434" rx="352" ry="18"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <path
                                        d="M778.64963,250.35008h-3.99878V140.80476a63.40187,63.40187,0,0,0-63.4018-63.40193H479.16232a63.40188,63.40188,0,0,0-63.402,63.4017v600.9744a63.40189,63.40189,0,0,0,63.4018,63.40192H711.24875a63.40187,63.40187,0,0,0,63.402-63.40168V328.32632h3.99878Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#3f3d56" />
                                    <path
                                        d="M761.156,141.24713v600.09a47.35072,47.35072,0,0,1-47.35,47.35h-233.2a47.35084,47.35084,0,0,1-47.35-47.35v-600.09a47.3509,47.3509,0,0,1,47.35-47.35h28.29a22.50659,22.50659,0,0,0,20.83,30.99h132.96a22.50672,22.50672,0,0,0,20.83-30.99h30.29A47.35088,47.35088,0,0,1,761.156,141.24713Z"
                                        transform="translate(-154.35252 -77.40283)" fill="currentColor" />
                                    <path
                                        d="M686.03027,400.0032q-2.32543,1.215-4.73047,2.3-2.18994.99-4.4497,1.86c-.5503.21-1.10987.42-1.66992.63a89.52811,89.52811,0,0,1-13.6001,3.75q-3.43506.675-6.96,1.06-2.90991.33-5.87989.47c-1.41015.07-2.82031.1-4.24023.1a89.84124,89.84124,0,0,1-16.75977-1.57c-1.44043-.26-2.85009-.57-4.26025-.91a88.77786,88.77786,0,0,1-19.66992-7.26c-.56006-.28-1.12012-.58-1.68018-.87-.83008-.44-1.63965-.9-2.4497-1.38.38964-.54.81005-1.07,1.23974-1.59a53.03414,53.03414,0,0,1,78.87012-4.1,54.27663,54.27663,0,0,1,5.06006,5.86C685.25977,398.89316,685.6499,399.44321,686.03027,400.0032Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#6c63ff" />
                                    <circle cx="492.14325" cy="234.76352" r="43.90974" fill="#2f2e41" />
                                    <circle cx="642.49883" cy="327.46205" r="32.68086"
                                        transform="translate(-232.6876 270.90663) rotate(-28.66315)" fill="#a0616a" />
                                    <path
                                        d="M676.8388,306.90589a44.44844,44.44844,0,0,1-25.402,7.85033,27.23846,27.23846,0,0,0,10.796,4.44154,89.62764,89.62764,0,0,1-36.61.20571,23.69448,23.69448,0,0,1-7.66395-2.63224,9.699,9.699,0,0,1-4.73055-6.3266c-.80322-4.58859,2.77227-8.75743,6.488-11.567a47.85811,47.85811,0,0,1,40.21662-8.03639c4.49246,1.16124,8.99288,3.12327,11.91085,6.731s3.78232,9.16981,1.00224,12.88488Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#2f2e41" />
                                    <path
                                        d="M644.5,230.17319a89.98675,89.98675,0,0,0-46.83984,166.83l.58007.34q.72.43506,1.43995.84c.81005.48,1.61962.94,2.4497,1.38.56006.29,1.12012.59,1.68018.87a88.77786,88.77786,0,0,0,19.66992,7.26c1.41016.34,2.81982.65,4.26025.91a89.84124,89.84124,0,0,0,16.75977,1.57c1.41992,0,2.83008-.03,4.24023-.1q2.97-.135,5.87989-.47,3.52513-.39,6.96-1.06a89.52811,89.52811,0,0,0,13.6001-3.75c.56005-.21,1.11962-.42,1.66992-.63q2.26464-.87,4.4497-1.86,2.40015-1.08,4.73047-2.3a90.7919,90.7919,0,0,0,37.03955-35.97c.04-.07995.09034-.16.13038-.24a89.30592,89.30592,0,0,0,9.6499-26.41,90.051,90.051,0,0,0-88.3501-107.21Zm77.06006,132.45c-.08008.14-.1499.28-.23.41a88.17195,88.17195,0,0,1-36.48,35.32q-2.29542,1.2-4.66992,2.25c-1.31006.59-2.64991,1.15-4,1.67-.57032.22-1.14991.44-1.73.64a85.72126,85.72126,0,0,1-11.73,3.36,84.69473,84.69473,0,0,1-8.95019,1.41c-1.8501.2-3.73.34-5.62012.41-1.21.05-2.42969.08-3.6499.08a86.762,86.762,0,0,1-16.21973-1.51,85.62478,85.62478,0,0,1-9.63037-2.36,88.46592,88.46592,0,0,1-13.98974-5.67c-.52-.27-1.04-.54-1.5503-.82-.73-.39-1.46972-.79-2.18994-1.22-.54-.3-1.08008-.62-1.60986-.94-.31006-.18-.62012-.37-.93018-.56a88.06851,88.06851,0,1,1,123.18018-32.47Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#3f3d56" />
                                    <path
                                        d="M624.2595,268.86254c-.47244-4.968-6.55849-8.02647-11.3179-6.52583s-7.88411,6.2929-8.82863,11.19308a16.0571,16.0571,0,0,0,2.16528,12.12236c2.40572,3.46228,6.82664,5.623,10.95,4.74406,4.70707-1.00334,7.96817-5.59956,8.90127-10.32105s.00667-9.58929-.91854-14.31234Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#2f2e41" />
                                    <path
                                        d="M691.24187,275.95964c-.47245-4.968-6.5585-8.02646-11.3179-6.52582s-7.88412,6.29289-8.82864,11.19307a16.05711,16.05711,0,0,0,2.16529,12.12236c2.40571,3.46228,6.82663,5.623,10.95,4.74406,4.70707-1.00334,7.96817-5.59955,8.90127-10.32105s.00667-9.58929-.91853-14.31234Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#2f2e41" />
                                    <path
                                        d="M488.93638,356.14169a4.47525,4.47525,0,0,1-3.30664-1.46436L436.00767,300.544a6.02039,6.02039,0,0,0-4.42627-1.94727H169.3618a15.02615,15.02615,0,0,1-15.00928-15.00927V189.025a15.02615,15.02615,0,0,1,15.00928-15.00928H509.087A15.02615,15.02615,0,0,1,524.0963,189.025v94.5625A15.02615,15.02615,0,0,1,509.087,298.59676h-9.63135a6.01157,6.01157,0,0,0-6.00464,6.00489v47.0332a4.474,4.474,0,0,1-2.87011,4.1958A4.52563,4.52563,0,0,1,488.93638,356.14169Z"
                                        transform="translate(-154.35252 -77.40283)" fill="currentColor" />
                                    <path
                                        d="M488.93638,356.14169a4.47525,4.47525,0,0,1-3.30664-1.46436L436.00767,300.544a6.02039,6.02039,0,0,0-4.42627-1.94727H169.3618a15.02615,15.02615,0,0,1-15.00928-15.00927V189.025a15.02615,15.02615,0,0,1,15.00928-15.00928H509.087A15.02615,15.02615,0,0,1,524.0963,189.025v94.5625A15.02615,15.02615,0,0,1,509.087,298.59676h-9.63135a6.01157,6.01157,0,0,0-6.00464,6.00489v47.0332a4.474,4.474,0,0,1-2.87011,4.1958A4.52563,4.52563,0,0,1,488.93638,356.14169ZM169.3618,176.01571A13.024,13.024,0,0,0,156.35252,189.025v94.5625a13.024,13.024,0,0,0,13.00928,13.00927H431.5814a8.02436,8.02436,0,0,1,5.90039,2.59571l49.62208,54.1333a2.50253,2.50253,0,0,0,4.34716-1.69092v-47.0332a8.0137,8.0137,0,0,1,8.00464-8.00489H509.087a13.024,13.024,0,0,0,13.00928-13.00927V189.025A13.024,13.024,0,0,0,509.087,176.01571Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#3f3d56" />
                                    <circle cx="36.81601" cy="125.19345" r="13.13371" fill="#6c63ff" />
                                    <path
                                        d="M493.76439,275.26947H184.68447a7.00465,7.00465,0,1,1,0-14.00929H493.76439a7.00465,7.00465,0,0,1,0,14.00929Z"
                                        transform="translate(-154.35252 -77.40283)"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <path
                                        d="M393.07263,245.49973H184.68447a7.00465,7.00465,0,1,1,0-14.00929H393.07263a7.00464,7.00464,0,0,1,0,14.00929Z"
                                        transform="translate(-154.35252 -77.40283)"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <path
                                        d="M709.41908,676.83065a4.474,4.474,0,0,1-2.87011-4.1958v-47.0332a6.01157,6.01157,0,0,0-6.00464-6.00489H690.913a15.02615,15.02615,0,0,1-15.00928-15.00927V510.025A15.02615,15.02615,0,0,1,690.913,495.01571H1030.6382a15.02615,15.02615,0,0,1,15.00928,15.00928v94.5625a15.02615,15.02615,0,0,1-15.00928,15.00927H768.4186a6.02039,6.02039,0,0,0-4.42627,1.94727l-49.62207,54.1333a4.47525,4.47525,0,0,1-3.30664,1.46436A4.52563,4.52563,0,0,1,709.41908,676.83065Z"
                                        transform="translate(-154.35252 -77.40283)" fill="currentColor" />
                                    <path
                                        d="M709.41908,676.83065a4.474,4.474,0,0,1-2.87011-4.1958v-47.0332a6.01157,6.01157,0,0,0-6.00464-6.00489H690.913a15.02615,15.02615,0,0,1-15.00928-15.00927V510.025A15.02615,15.02615,0,0,1,690.913,495.01571H1030.6382a15.02615,15.02615,0,0,1,15.00928,15.00928v94.5625a15.02615,15.02615,0,0,1-15.00928,15.00927H768.4186a6.02039,6.02039,0,0,0-4.42627,1.94727l-49.62207,54.1333a4.47525,4.47525,0,0,1-3.30664,1.46436A4.52563,4.52563,0,0,1,709.41908,676.83065ZM690.913,497.01571A13.024,13.024,0,0,0,677.9037,510.025v94.5625A13.024,13.024,0,0,0,690.913,617.59676h9.63135a8.0137,8.0137,0,0,1,8.00464,8.00489v47.0332a2.50253,2.50253,0,0,0,4.34716,1.69092l49.62208-54.1333a8.02436,8.02436,0,0,1,5.90039-2.59571h262.2196a13.024,13.024,0,0,0,13.00928-13.00927V510.025a13.024,13.024,0,0,0-13.00928-13.00928Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#3f3d56" />
                                    <path
                                        d="M603.53027,706.11319a89.06853,89.06853,0,0,1-93.65039,1.49,54.12885,54.12885,0,0,1,9.40039-12.65,53.43288,53.43288,0,0,1,83.90967,10.56994C603.2998,705.71316,603.41992,705.91318,603.53027,706.11319Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#6c63ff" />
                                    <circle cx="398.44256" cy="536.68841" r="44.20157" fill="#2f2e41" />
                                    <circle cx="556.81859" cy="629.4886" r="32.89806"
                                        transform="translate(-416.96496 738.72884) rotate(-61.33685)"
                                        fill="#ffb8b8" />
                                    <path
                                        d="M522.25039,608.79582a44.74387,44.74387,0,0,0,25.57085,7.9025,27.41946,27.41946,0,0,1-10.8677,4.47107,90.22316,90.22316,0,0,0,36.85334.20707,23.852,23.852,0,0,0,7.71488-2.64973,9.76352,9.76352,0,0,0,4.762-6.36865c.80855-4.61909-2.7907-8.81563-6.53113-11.64387a48.17616,48.17616,0,0,0-40.4839-8.08981c-4.52231,1.169-9.05265,3.144-11.99,6.77579s-3.80746,9.23076-1.0089,12.97052Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#2f2e41" />
                                    <path
                                        d="M555.5,721.17319a89.97205,89.97205,0,1,1,48.5708-14.21875A89.87958,89.87958,0,0,1,555.5,721.17319Zm0-178a88.00832,88.00832,0,1,0,88,88A88.09957,88.09957,0,0,0,555.5,543.17319Z"
                                        transform="translate(-154.35252 -77.40283)" fill="#3f3d56" />
                                    <circle cx="563.81601" cy="445.19345" r="13.13371" fill="#6c63ff" />
                                    <path
                                        d="M1020.76439,595.26947H711.68447a7.00465,7.00465,0,1,1,0-14.00929h309.07992a7.00464,7.00464,0,0,1,0,14.00929Z"
                                        transform="translate(-154.35252 -77.40283)"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <path
                                        d="M920.07263,565.49973H711.68447a7.00465,7.00465,0,1,1,0-14.00929H920.07263a7.00465,7.00465,0,0,1,0,14.00929Z"
                                        transform="translate(-154.35252 -77.40283)"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <ellipse cx="554.64354" cy="605.66091" rx="24.50394" ry="2.71961"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                    <ellipse cx="335.64354" cy="285.66091" rx="24.50394" ry="2.71961"
                                        :fill="$store.app.theme === 'dark' || $store.app.isDarkMode ? '#888ea8' : '#e6e6e6'" />
                                </svg>
                            </div>
                            <p
                                class="flex justify-center bg-white-dark/20 p-2 font-semibold rounded-md max-w-[190px] mx-auto">

                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 ltr:mr-2 rtl:ml-2">
                                    <path
                                        d="M13.0867 21.3877L13.7321 21.7697L13.0867 21.3877ZM13.6288 20.4718L12.9833 20.0898L13.6288 20.4718ZM10.3712 20.4718L9.72579 20.8539H9.72579L10.3712 20.4718ZM10.9133 21.3877L11.5587 21.0057L10.9133 21.3877ZM2.3806 15.9134L3.07351 15.6264V15.6264L2.3806 15.9134ZM7.78958 18.9915L7.77666 19.7413L7.78958 18.9915ZM5.08658 18.6194L4.79957 19.3123H4.79957L5.08658 18.6194ZM21.6194 15.9134L22.3123 16.2004V16.2004L21.6194 15.9134ZM16.2104 18.9915L16.1975 18.2416L16.2104 18.9915ZM18.9134 18.6194L19.2004 19.3123H19.2004L18.9134 18.6194ZM19.6125 2.7368L19.2206 3.37628L19.6125 2.7368ZM21.2632 4.38751L21.9027 3.99563V3.99563L21.2632 4.38751ZM4.38751 2.7368L3.99563 2.09732V2.09732L4.38751 2.7368ZM2.7368 4.38751L2.09732 3.99563H2.09732L2.7368 4.38751ZM9.40279 19.2098L9.77986 18.5615L9.77986 18.5615L9.40279 19.2098ZM13.7321 21.7697L14.2742 20.8539L12.9833 20.0898L12.4412 21.0057L13.7321 21.7697ZM9.72579 20.8539L10.2679 21.7697L11.5587 21.0057L11.0166 20.0898L9.72579 20.8539ZM12.4412 21.0057C12.2485 21.3313 11.7515 21.3313 11.5587 21.0057L10.2679 21.7697C11.0415 23.0767 12.9585 23.0767 13.7321 21.7697L12.4412 21.0057ZM10.5 2.75H13.5V1.25H10.5V2.75ZM21.25 10.5V11.5H22.75V10.5H21.25ZM2.75 11.5V10.5H1.25V11.5H2.75ZM1.25 11.5C1.25 12.6546 1.24959 13.5581 1.29931 14.2868C1.3495 15.0223 1.45323 15.6344 1.68769 16.2004L3.07351 15.6264C2.92737 15.2736 2.84081 14.8438 2.79584 14.1847C2.75041 13.5189 2.75 12.6751 2.75 11.5H1.25ZM7.8025 18.2416C6.54706 18.2199 5.88923 18.1401 5.37359 17.9265L4.79957 19.3123C5.60454 19.6457 6.52138 19.7197 7.77666 19.7413L7.8025 18.2416ZM1.68769 16.2004C2.27128 17.6093 3.39066 18.7287 4.79957 19.3123L5.3736 17.9265C4.33223 17.4951 3.50486 16.6678 3.07351 15.6264L1.68769 16.2004ZM21.25 11.5C21.25 12.6751 21.2496 13.5189 21.2042 14.1847C21.1592 14.8438 21.0726 15.2736 20.9265 15.6264L22.3123 16.2004C22.5468 15.6344 22.6505 15.0223 22.7007 14.2868C22.7504 13.5581 22.75 12.6546 22.75 11.5H21.25ZM16.2233 19.7413C17.4786 19.7197 18.3955 19.6457 19.2004 19.3123L18.6264 17.9265C18.1108 18.1401 17.4529 18.2199 16.1975 18.2416L16.2233 19.7413ZM20.9265 15.6264C20.4951 16.6678 19.6678 17.4951 18.6264 17.9265L19.2004 19.3123C20.6093 18.7287 21.7287 17.6093 22.3123 16.2004L20.9265 15.6264ZM13.5 2.75C15.1512 2.75 16.337 2.75079 17.2619 2.83873C18.1757 2.92561 18.7571 3.09223 19.2206 3.37628L20.0044 2.09732C19.2655 1.64457 18.4274 1.44279 17.4039 1.34547C16.3915 1.24921 15.1222 1.25 13.5 1.25V2.75ZM22.75 10.5C22.75 8.87781 22.7508 7.6085 22.6545 6.59611C22.5572 5.57256 22.3554 4.73445 21.9027 3.99563L20.6237 4.77938C20.9078 5.24291 21.0744 5.82434 21.1613 6.73809C21.2492 7.663 21.25 8.84876 21.25 10.5H22.75ZM19.2206 3.37628C19.7925 3.72672 20.2733 4.20752 20.6237 4.77938L21.9027 3.99563C21.4286 3.22194 20.7781 2.57144 20.0044 2.09732L19.2206 3.37628ZM10.5 1.25C8.87781 1.25 7.6085 1.24921 6.59611 1.34547C5.57256 1.44279 4.73445 1.64457 3.99563 2.09732L4.77938 3.37628C5.24291 3.09223 5.82434 2.92561 6.73809 2.83873C7.663 2.75079 8.84876 2.75 10.5 2.75V1.25ZM2.75 10.5C2.75 8.84876 2.75079 7.663 2.83873 6.73809C2.92561 5.82434 3.09223 5.24291 3.37628 4.77938L2.09732 3.99563C1.64457 4.73445 1.44279 5.57256 1.34547 6.59611C1.24921 7.6085 1.25 8.87781 1.25 10.5H2.75ZM3.99563 2.09732C3.22194 2.57144 2.57144 3.22194 2.09732 3.99563L3.37628 4.77938C3.72672 4.20752 4.20752 3.72672 4.77938 3.37628L3.99563 2.09732ZM11.0166 20.0898C10.8136 19.7468 10.6354 19.4441 10.4621 19.2063C10.2795 18.9559 10.0702 18.7304 9.77986 18.5615L9.02572 19.8582C9.07313 19.8857 9.13772 19.936 9.24985 20.0898C9.37122 20.2564 9.50835 20.4865 9.72579 20.8539L11.0166 20.0898ZM7.77666 19.7413C8.21575 19.7489 8.49387 19.7545 8.70588 19.7779C8.90399 19.7999 8.98078 19.832 9.02572 19.8582L9.77986 18.5615C9.4871 18.3912 9.18246 18.3215 8.87097 18.287C8.57339 18.2541 8.21375 18.2487 7.8025 18.2416L7.77666 19.7413ZM14.2742 20.8539C14.4916 20.4865 14.6287 20.2564 14.7501 20.0898C14.8622 19.936 14.9268 19.8857 14.9742 19.8582L14.2201 18.5615C13.9298 18.7304 13.7204 18.9559 13.5379 19.2063C13.3646 19.4441 13.1864 19.7468 12.9833 20.0898L14.2742 20.8539ZM16.1975 18.2416C15.7862 18.2487 15.4266 18.2541 15.129 18.287C14.8175 18.3215 14.5129 18.3912 14.2201 18.5615L14.9742 19.8582C15.0192 19.832 15.096 19.7999 15.2941 19.7779C15.5061 19.7545 15.7842 19.7489 16.2233 19.7413L16.1975 18.2416Z"
                                        fill="currentColor" />
                                </svg>
                                Click User To Chat
                            </p>
                        </div>
                    </div>
                </template>
                <template x-if="isShowUserChat && selectedUser">
                    <div class="relative h-full flex flex-col">
                        <div class="flex justify-between items-center p-4 flex-shrink-0 fixed xl:relative top-0 left-0 right-0 xl:left-auto xl:right-auto bg-white dark:bg-[#0e1726] border-b border-[#e0e6ed] dark:border-[#1b2e4b] z-10">
                            <div class="flex items-center space-x-2 rtl:space-x-reverse">
                                <button type="button" class="xl:hidden text-primary dark:text-gray-300 hover:text-primary-dark"
                                    @click="isShowUserChat = false; isShowChatMenu = true">

                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                        xmlns="http://www.w3.org/2000/svg" class="w-6 h-6">
                                        <path d="M15 18L9 12L15 6" stroke="currentColor" stroke-width="1.5"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </button>
                                <div class="relative flex-none">
                                    <template x-if="selectedUser.type === 'group'">
                                        <div class="rounded-full w-10 h-10 sm:h-12 sm:w-12 bg-primary/20 flex items-center justify-center">
                                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-primary">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                        </div>
                                    </template>
                                    <template x-if="selectedUser.type !== 'group'">
                                    <img :src="selectedUser.path || `/assets/images/profile-${(selectedUser.userId || selectedUser.id) % 20 + 1}.jpeg`"
                                        class="rounded-full w-10 h-10 sm:h-12 sm:w-12 object-cover" />
                                    </template>
                                    <template x-if="selectedUser.type !== 'group'">
                                    <div class="absolute bottom-0 ltr:right-0 rtl:left-0">
                                        <div class="w-4 h-4 bg-success rounded-full"></div>
                                    </div>
                                    </template>
                                </div>
                                <div class="mx-3">
                                    <p class="font-semibold" x-text="selectedUser.name"></p>
                                    <template x-if="selectedUser.type === 'group'">
                                        <p class="text-white-dark text-xs" x-text="(selectedUser.participants_count || 0) + ' مشارك'"></p>
                                    </template>
                                    <template x-if="selectedUser.type !== 'group'">
                                    <p class="text-white-dark text-xs"
                                            x-text="selectedUser.active ? 'Active now' : 'Last seen at '+selectedUser.time"></p>
                                    </template>
                                </div>
                            </div>
                        </div>
                        <div class="h-px w-full border-b border-[#e0e6ed] dark:border-[#1b2e4b]"></div>
                        <div class="perfect-scrollbar relative overflow-y-auto flex-1 min-h-0 pb-24 xl:pb-0 pt-20 xl:pt-0">
                            <div
                                class="space-y-5 p-4 chat-conversation-box sm:pb-0 sm:min-h-[300px] min-h-[400px]">
                                <div class="block m-6 mt-0">
                                    <h4
                                        class="text-xs text-center border-b border-[#f4f4f4] dark:border-gray-800 relative">
                                        <span class="relative top-2 px-3 bg-white dark:bg-[#0e1726]"
                                            x-text="'Today, ' + selectedUser.time"></span>
                                    </h4>
                                </div>
                                <template x-for="message in selectedUser.messages">
                                    <div class="flex items-start gap-3"
                                        :class="{ 'justify-end': loginUser.id === message.fromUserId && selectedUser.type !== 'group' }">
                                        <div class="flex-none"
                                            :class="{ 'order-2': loginUser.id === message.fromUserId && selectedUser.type !== 'group' }">
                                            <template x-if="selectedUser.type === 'group'">
                                                <img :src="`/assets/images/profile-${(message.sender_id || message.fromUserId) % 20 + 1}.jpeg`"
                                                    class="rounded-full h-10 w-10 object-cover" />
                                            </template>
                                            <template x-if="selectedUser.type !== 'group'">
                                                <template x-if="loginUser.id === message.fromUserId">
                                                <img :src="loginUser.path || `/assets/images/profile-${loginUser.id % 20 + 1}.jpeg`"
                                                    class="rounded-full h-10 w-10 object-cover" />
                                            </template>
                                                <template x-if="loginUser.id !== message.fromUserId">
                                                <img :src="selectedUser.path || `/assets/images/profile-${(selectedUser.userId || selectedUser.id) % 20 + 1}.jpeg`"
                                                    class="rounded-full h-10 w-10 object-cover" />
                                                </template>
                                            </template>
                                        </div>
                                        <div class="flex flex-col space-y-2"
                                            :class="(loginUser.id === message.fromUserId && selectedUser.type !== 'group') ? 'items-end' : 'items-start'">
                                            <template x-if="selectedUser.type === 'group' && message.sender_name">
                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="message.sender_name"></p>
                                            </template>
                                            <div class="flex items-center gap-3">
                                                <!-- عرض card الطلب إذا كانت الرسالة من نوع order -->
                                                <template x-if="message.type === 'order' && message.order">
                                                    <div class="panel min-w-[280px] max-w-[320px]"
                                                        :class="message.order.status === 'confirmed' ? 'border-2 border-green-500 dark:border-green-600' : 'border-2 border-yellow-500 dark:border-yellow-600'">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <div>
                                                                <a :href="'{{ auth()->user()->isDelegate() ? url('/delegate/orders') : url('/admin/orders-management') }}?search=' + encodeURIComponent(message.order.order_number) + '#order-' + message.order.id"
                                                                   class="text-lg font-bold text-primary dark:text-primary-light hover:underline"
                                                                   x-text="message.order.order_number"></a>
                                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1" x-text="message.order.customer_name"></p>
                                                            </div>
                                                            <span class="badge shrink-0"
                                                                  :class="message.order.status === 'confirmed' ? 'badge-outline-success' : 'badge-outline-warning'"
                                                                  x-text="message.order.status === 'confirmed' ? 'مقيد' : 'قيد الانتظار'"></span>
                                                        </div>
                                                        <div class="space-y-2 mb-3">
                                                            <div class="flex justify-between text-sm">
                                                                <span class="text-gray-500 dark:text-gray-400">الهاتف:</span>
                                                                <span x-text="message.order.customer_phone"></span>
                                                            </div>
                                                            <div class="flex justify-between text-sm">
                                                                <span class="text-gray-500 dark:text-gray-400">المبلغ:</span>
                                                                <span class="font-semibold" x-text="message.order.total_amount + ' د.ع'"></span>
                                                            </div>
                                                        </div>
                                                        <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                                                            <a :href="'{{ auth()->user()->isDelegate() ? url('/delegate/orders') : url('/admin/orders-management') }}?search=' + encodeURIComponent(message.order.order_number) + '#order-' + message.order.id"
                                                               class="btn btn-primary btn-sm w-full">عرض التفاصيل</a>
                                                        </div>
                                                    </div>
                                                </template>
                                                <!-- عرض card المنتج إذا كانت الرسالة من نوع product -->
                                                <template x-if="message.type === 'product' && message.product">
                                                    <div class="panel min-w-[280px] max-w-[320px] border-2 border-primary dark:border-primary-light">
                                                        <div class="flex items-center gap-3 mb-3">
                                                            <template x-if="message.product.image_url">
                                                                <img :src="message.product.image_url"
                                                                     class="w-16 h-16 object-cover rounded-lg"
                                                                     :alt="message.product.name" />
                                                            </template>
                                                            <template x-if="!message.product.image_url">
                                                                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                                    </svg>
                                                                </div>
                                                            </template>
                                                            <div class="flex-1">
                                                                <h6 class="font-semibold text-primary dark:text-primary-light" x-text="message.product.name"></h6>
                                                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="message.product.code"></p>
                                                            </div>
                                                        </div>
                                                        <div class="space-y-2 mb-3">
                                                            <div class="flex justify-between text-sm">
                                                                <span class="text-gray-500 dark:text-gray-400">سعر البيع:</span>
                                                                <span class="font-semibold" x-text="message.product.selling_price + ' د.ع'"></span>
                                                            </div>
                                                            <template x-if="message.product.warehouse_name">
                                                                <div class="flex justify-between text-sm">
                                                                    <span class="text-gray-500 dark:text-gray-400">المخزن:</span>
                                                                    <span x-text="message.product.warehouse_name"></span>
                                                                </div>
                                                            </template>
                                                            <template x-if="message.product.sizes && message.product.sizes.length > 0">
                                                                <div class="pt-2 border-t border-gray-200 dark:border-gray-700">
                                                                    <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 mb-2">القياسات والكميات:</p>
                                                                    <div class="space-y-1">
                                                                        <template x-for="size in message.product.sizes" :key="size.id">
                                                                            <div class="flex justify-between text-xs">
                                                                                <span class="text-gray-500 dark:text-gray-400" x-text="size.size_name"></span>
                                                                                <span class="font-semibold"
                                                                                      :class="size.available_quantity > 0 ? 'text-success' : 'text-danger'"
                                                                                      x-text="size.available_quantity + ' متوفر'"></span>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                                <!-- عرض الصورة إذا كانت موجودة -->
                                                <template x-if="message.image_url">
                                                    <div class="mb-2">
                                                        <img :src="message.image_url"
                                                             class="max-w-xs rounded-lg cursor-pointer hover:opacity-90"
                                                             @click="window.open(message.image_url, '_blank')"
                                                             alt="صورة" />
                                                    </div>
                                                </template>
                                                <!-- عرض الرسالة النصية العادية -->
                                                <template x-if="message.type !== 'order' && message.type !== 'product' || (!message.order && !message.product)">
                                                    <template x-if="message.text">
                                                <div class="dark:bg-gray-800 p-4 py-2 rounded-md bg-black/10"
                                                                :class="loginUser.id === message.fromUserId ?
                                                        'ltr:rounded-br-none rtl:rounded-bl-none !bg-primary text-white' :
                                                        'ltr:rounded-bl-none rtl:rounded-br-none'"
                                                    x-text="message.text"></div>
                                                    </template>
                                                </template>
                                                <div :class="{ 'hidden': loginUser.id === message.fromUserId }">

                                                    <svg width="24" height="24" viewBox="0 0 24 24"
                                                        fill="none" xmlns="http://www.w3.org/2000/svg"
                                                        class="w-5 h-5 hover:text-primary">
                                                        <circle opacity="0.5" cx="12" cy="12"
                                                            r="10" stroke="currentColor"
                                                            stroke-width="1.5" />
                                                        <path
                                                            d="M9 16C9.85038 16.6303 10.8846 17 12 17C13.1154 17 14.1496 16.6303 15 16"
                                                            stroke="currentColor" stroke-width="1.5"
                                                            stroke-linecap="round" />
                                                        <path
                                                            d="M16 10.5C16 11.3284 15.5523 12 15 12C14.4477 12 14 11.3284 14 10.5C14 9.67157 14.4477 9 15 9C15.5523 9 16 9.67157 16 10.5Z"
                                                            fill="currentColor" />
                                                        <ellipse cx="9" cy="10.5" rx="1"
                                                            ry="1.5" fill="currentColor" />
                                                    </svg>
                                                </div>
                                            </div>
                                            <div class="text-xs text-white-dark"
                                                :class="{
                                                    'ltr:text-right rtl:text-left': loginUser.id === message.fromUserId
                                                }"
                                                x-text="message.time ? message.time: '5h ago'"></div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="p-4 fixed xl:sticky bottom-0 left-0 right-0 xl:right-auto w-full bg-white dark:bg-[#0e1726] border-t border-[#e0e6ed] dark:border-[#1b2e4b] z-10 flex-shrink-0">
                            <div class="flex w-full space-x-2 rtl:space-x-reverse items-center">
                                <div class="relative flex-1">
                                    <input id=""
                                        class="form-input rounded-full border-0 bg-[#f4f4f4] px-12 focus:outline-none py-2"
                                        placeholder="Type a message" x-model="textMessage"
                                        @keyup.enter="sendMessage()" />
                                    <button type="button"
                                        class="absolute ltr:left-4 rtl:right-4 top-1/2 -translate-y-1/2 hover:text-primary">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                            <circle opacity="0.5" cx="12" cy="12" r="10"
                                                stroke="currentColor" stroke-width="1.5" />
                                            <path
                                                d="M9 16C9.85038 16.6303 10.8846 17 12 17C13.1154 17 14.1496 16.6303 15 16"
                                                stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                            <path
                                                d="M16 10.5C16 11.3284 15.5523 12 15 12C14.4477 12 14 11.3284 14 10.5C14 9.67157 14.4477 9 15 9C15.5523 9 16 9.67157 16 10.5Z"
                                                fill="currentColor" />
                                            <ellipse cx="9" cy="10.5" rx="1" ry="1.5"
                                                fill="currentColor" />
                                        </svg>
                                    </button>
                                    <button type="button"
                                        class="absolute ltr:right-4 rtl:left-4 top-1/2 -translate-y-1/2 hover:text-primary"
                                        @click="sendMessage()">

                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"
                                            xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                            <path
                                                d="M17.4975 18.4851L20.6281 9.09373C21.8764 5.34874 22.5006 3.47624 21.5122 2.48782C20.5237 1.49939 18.6511 2.12356 14.906 3.37189L5.57477 6.48218C3.49295 7.1761 2.45203 7.52305 2.13608 8.28637C2.06182 8.46577 2.01692 8.65596 2.00311 8.84963C1.94433 9.67365 2.72018 10.4495 4.27188 12.0011L4.55451 12.2837C4.80921 12.5384 4.93655 12.6658 5.03282 12.8075C5.22269 13.0871 5.33046 13.4143 5.34393 13.7519C5.35076 13.9232 5.32403 14.1013 5.27057 14.4574C5.07488 15.7612 4.97703 16.4131 5.0923 16.9147C5.32205 17.9146 6.09599 18.6995 7.09257 18.9433C7.59255 19.0656 8.24576 18.977 9.5522 18.7997L9.62363 18.79C9.99191 18.74 10.1761 18.715 10.3529 18.7257C10.6738 18.745 10.9838 18.8496 11.251 19.0285C11.3981 19.1271 11.5295 19.2585 11.7923 19.5213L12.0436 19.7725C13.5539 21.2828 14.309 22.0379 15.1101 21.9985C15.3309 21.9877 15.5479 21.9365 15.7503 21.8474C16.4844 21.5244 16.8221 20.5113 17.4975 18.4851Z"
                                                stroke="currentColor" stroke-width="1.5" />
                                            <path opacity="0.5" d="M6 18L21 3" stroke="currentColor"
                                                stroke-width="1.5" stroke-linecap="round" />
                                        </svg>
                                    </button>
                                </div>
                            <div class="flex items-center space-x-2 rtl:space-x-reverse py-2">
                                <!-- زر رفع صورة -->
                                    <button type="button"
                                    class="bg-[#f4f4f4] dark:bg-[#1b2e4b] hover:bg-primary-light rounded-md p-2 hover:text-primary"
                                    @click="document.getElementById('imageInput').click()">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                        <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                        <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                    </button>
                                <input type="file" id="imageInput" accept="image/*" class="hidden" @change="handleImageUpload($event)">
                                <!-- زر البحث عن منتج -->
                                    <button type="button"
                                    class="bg-[#f4f4f4] dark:bg-[#1b2e4b] hover:bg-primary-light rounded-md p-2 hover:text-primary"
                                    @click="showProductSearch = true">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                            <path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                    <!-- زر البحث عن طلب -->
                                    <button type="button"
                                        class="bg-[#f4f4f4] dark:bg-[#1b2e4b] hover:bg-primary-light rounded-md p-2 hover:text-primary"
                                        @click="showOrderSearch = true">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                            <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Modal البحث عن الطلبات -->
            <div class="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto hidden"
                :class="showOrderSearch && '!block'">
                <div class="flex items-center justify-center min-h-screen px-4"
                    @click.self="showOrderSearch = false">
                    <div x-show="showOrderSearch" x-transition x-transition.duration.300
                        class="panel border-0 p-0 rounded-lg overflow-hidden md:w-full max-w-lg w-[90%] my-8">
                                    <button type="button"
                            class="absolute top-4 ltr:right-4 rtl:left-4 text-white-dark hover:text-dark"
                            @click="showOrderSearch = false">

                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                        <h3 class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">البحث عن طلب</h3>
                        <div class="p-5">
                            <div class="mb-5">
                                <input type="text"
                                       x-model="orderSearchQuery"
                                       @input.debounce.300ms="searchOrders()"
                                       placeholder="ابحث برقم الطلب، رقم الهاتف، أو الرابط..."
                                       class="form-input" />
                            </div>

                            <div class="space-y-2 max-h-[400px] overflow-y-auto">
                                <template x-if="orderSearchResults.length === 0 && orderSearchQuery.length >= 3">
                                    <div class="text-center py-8 text-gray-500">
                                        <p>لا توجد نتائج</p>
                                    </div>
                                </template>

                                <template x-for="order in orderSearchResults" :key="order.id">
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                         @click="sendOrderMessage(order)">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="font-semibold text-primary" x-text="order.order_number"></span>
                                            <span class="text-xs px-2 py-1 rounded"
                                                  :class="order.status === 'confirmed' ? 'bg-success/20 text-success' : 'bg-warning/20 text-warning'"
                                                  x-text="order.status === 'confirmed' ? 'مقيد' : 'قيد الانتظار'"></span>
                                        </div>
                                        <div class="text-sm space-y-1">
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">العميل:</span>
                                                <span x-text="order.customer_name"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">الهاتف:</span>
                                                <span x-text="order.customer_phone"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">المبلغ:</span>
                                                <span class="font-semibold" x-text="order.total_amount + ' د.ع'"></span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">التاريخ:</span>
                                                <span x-text="order.created_at"></span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal البحث عن المنتجات -->
            <div class="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto hidden"
                :class="showProductSearch && '!block'">
                <div class="flex items-center justify-center min-h-screen px-4"
                    @click.self="showProductSearch = false">
                    <div x-show="showProductSearch" x-transition x-transition.duration.300
                        class="panel border-0 p-0 rounded-lg overflow-hidden md:w-full max-w-lg w-[90%] my-8">
                                    <button type="button"
                            class="absolute top-4 ltr:right-4 rtl:left-4 text-white-dark hover:text-dark"
                            @click="showProductSearch = false">

                            <svg xmlns="http://www.w3.org/2000/svg" width="24px" height="24px"
                                viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                                stroke-linecap="round" stroke-linejoin="round" class="w-6 h-6">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                        <h3 class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">البحث عن منتج</h3>
                        <div class="p-5">
                            <div class="mb-5">
                                <input type="text"
                                       x-model="productSearchQuery"
                                       @input.debounce.300ms="searchProducts()"
                                       placeholder="ابحث باسم المنتج أو الكود..."
                                       class="form-input" />
                                </div>

                            <div class="space-y-2 max-h-[400px] overflow-y-auto">
                                <template x-if="productSearchResults.length === 0 && productSearchQuery.length >= 2">
                                    <div class="text-center py-8 text-gray-500">
                                        <p>لا توجد نتائج</p>
                            </div>
                                </template>

                                <template x-for="product in productSearchResults" :key="product.id">
                                    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer"
                                         @click="sendProductMessage(product)">
                                        <div class="flex items-center gap-3">
                                            <template x-if="product.image_url">
                                                <img :src="product.image_url"
                                                     class="w-16 h-16 object-cover rounded-lg"
                                                     :alt="product.name" />
                                            </template>
                                            <template x-if="!product.image_url">
                                                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                    </svg>
                        </div>
                                            </template>
                                            <div class="flex-1">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="font-semibold text-primary" x-text="product.name"></span>
                    </div>
                                                <p class="text-xs text-gray-500 mb-1" x-text="product.code"></p>
                                                <div class="flex items-center gap-3 text-sm mb-1">
                                                    <span class="text-gray-500">سعر البيع:</span>
                                                    <span class="font-semibold" x-text="product.selling_price + ' د.ع'"></span>
                                                </div>
                                                <template x-if="product.warehouse_name">
                                                    <p class="text-xs text-gray-500 mb-1" x-text="'المخزن: ' + product.warehouse_name"></p>
                </template>
                                                <template x-if="product.sizes && product.sizes.length > 0">
                                                    <div class="mt-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                                        <template x-for="size in product.sizes" :key="size.id">
                                                            <div class="flex justify-between text-xs mb-1">
                                                                <span class="text-gray-500" x-text="size.size_name"></span>
                                                                <span class="font-semibold"
                                                                      :class="size.available_quantity > 0 ? 'text-success' : 'text-danger'"
                                                                      x-text="size.available_quantity + ' متوفر'"></span>
            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal إنشاء مجموعة -->
        <div class="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto hidden"
            :class="showCreateGroupModal && '!block'">
            <div class="flex items-center justify-center min-h-screen px-4"
                @click.self="showCreateGroupModal = false">
                <div x-show="showCreateGroupModal" x-transition x-transition.duration.300
                    class="panel border-0 p-0 rounded-lg overflow-hidden md:w-full max-w-lg w-[90%] my-8">
                                    <button type="button"
                        class="absolute top-4 ltr:right-4 rtl:left-4 text-white-dark hover:text-dark"
                        @click="showCreateGroupModal = false">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <h3 class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">إنشاء مجموعة جديدة</h3>
                    <div class="p-5">
                        <div class="mb-5">
                            <label class="block text-sm font-semibold mb-2">اسم المجموعة</label>
                            <input type="text"
                                   x-model="groupTitle"
                                   placeholder="أدخل اسم المجموعة..."
                                   class="form-input" />
                        </div>
                        <div class="mb-5">
                            <label class="block text-sm font-semibold mb-2">اختر المستخدمين</label>
                            <div class="max-h-[300px] overflow-y-auto space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <template x-for="user in availableUsersForGroup" :key="user.id">
                                    <label class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded cursor-pointer">
                                        <input type="checkbox"
                                               :value="user.id"
                                               x-model="selectedUserIds"
                                               class="form-checkbox" />
                                        <img :src="user.path || `/assets/images/profile-${user.id % 20 + 1}.jpeg`"
                                             class="rounded-full h-10 w-10 object-cover" />
                                        <div class="flex-1">
                                            <p class="font-semibold" x-text="user.name"></p>
                                            <p class="text-xs text-gray-500" x-text="user.role"></p>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button"
                                class="btn btn-outline-danger"
                                @click="showCreateGroupModal = false; groupTitle = ''; selectedUserIds = []">
                                إلغاء
                            </button>
                            <button type="button"
                                class="btn btn-primary"
                                @click="createGroup()">
                                إنشاء المجموعة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal إدارة المجموعة -->
        <div class="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto hidden"
            :class="showGroupManageModal && '!block'">
            <div class="flex items-center justify-center min-h-screen px-4"
                @click.self="showGroupManageModal = false">
                <div x-show="showGroupManageModal" x-transition x-transition.duration.300
                    class="panel border-0 p-0 rounded-lg overflow-hidden md:w-full max-w-lg w-[90%] my-8">
                    <button type="button"
                        class="absolute top-4 ltr:right-4 rtl:left-4 text-white-dark hover:text-dark"
                        @click="showGroupManageModal = false">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                    <h3 class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">إدارة المجموعة</h3>
                    <div class="p-5">
                        <div class="mb-5">
                            <h4 class="font-semibold mb-3">المشاركون الحاليون</h4>
                            <div class="max-h-[200px] overflow-y-auto space-y-2">
                                <template x-for="participant in currentGroupParticipants" :key="participant.id">
                                    <div class="flex items-center justify-between p-2 border border-gray-200 dark:border-gray-700 rounded-lg">
                                        <div class="flex items-center gap-3">
                                            <img :src="participant.path || `/assets/images/profile-${participant.id % 20 + 1}.jpeg`"
                                                 class="rounded-full h-10 w-10 object-cover" />
                                            <div>
                                                <p class="font-semibold" x-text="participant.name"></p>
                                                <p class="text-xs text-gray-500" x-text="participant.role"></p>
                                </div>
                            </div>
                                        <template x-if="loginUser.role === 'admin' && participant.id !== loginUser.id">
                                    <button type="button"
                                                class="btn btn-sm btn-outline-danger"
                                                @click="removeParticipant(participant.id)">
                                                إزالة
                                            </button>
                                        </template>
                        </div>
                                </template>
                    </div>
                        </div>
                        <template x-if="loginUser.role === 'admin'">
                            <div class="mb-5">
                                <h4 class="font-semibold mb-3">إضافة مستخدمين جدد</h4>
                                <div class="max-h-[200px] overflow-y-auto space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                    <template x-for="user in availableUsersForGroup" :key="user.id">
                                        <label class="flex items-center gap-3 p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded cursor-pointer"
                                               x-show="!currentGroupParticipants.find(p => p.id === user.id)">
                                            <input type="checkbox"
                                                   :value="user.id"
                                                   x-model="selectedUserIds"
                                                   class="form-checkbox" />
                                            <img :src="`/assets/images/profile-${user.id % 20 + 1}.jpeg`"
                                                 class="rounded-full h-10 w-10 object-cover" />
                                            <div class="flex-1">
                                                <p class="font-semibold" x-text="user.name"></p>
                                                <p class="text-xs text-gray-500" x-text="user.role"></p>
                                            </div>
                                        </label>
                </template>
            </div>
                                <button type="button"
                                    class="btn btn-primary btn-sm mt-3"
                                    @click="addParticipantsToGroup()">
                                    إضافة المستخدمين المختارين
                                </button>
                            </div>
                        </template>
                        <div class="flex justify-end">
                            <button type="button"
                                class="btn btn-outline-primary"
                                @click="showGroupManageModal = false; selectedUserIds = []; currentGroupParticipants = []">
                                إغلاق
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal إعدادات الإشعارات -->
        <div class="fixed inset-0 bg-[black]/60 z-[999] overflow-y-auto hidden"
            :class="showNotificationSettings && '!block'">
            <div class="flex items-center justify-center min-h-screen px-4"
                @click.self="showNotificationSettings = false">
                <div x-show="showNotificationSettings" x-transition x-transition.duration.300
                    class="panel border-0 p-0 rounded-lg overflow-hidden md:w-full max-w-md w-[90%] my-8">
                    <button type="button"
                        class="absolute top-4 ltr:right-4 rtl:left-4 text-white-dark hover:text-dark"
                        @click="showNotificationSettings = false">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                                        </svg>
                                    </button>
                    <h3 class="text-lg font-medium bg-[#fbfbfb] dark:bg-[#121c2c] ltr:pl-5 rtl:pr-5 py-3 ltr:pr-[50px] rtl:pl-[50px]">إعدادات الإشعارات</h3>
                    <div class="p-5">
                        <div class="space-y-4">
                            <!-- تفعيل/إيقاف الصوت -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold mb-1">صوت التنبيه</h4>
                                    <p class="text-sm text-gray-500">تشغيل صوت عند وصول رسالة جديدة</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="sr-only peer"
                                           :checked="soundEnabled"
                                           @change="toggleSound()" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/40 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:ltr:left-[2px] after:rtl:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                                </label>
                            </div>

                            <!-- تفعيل/إيقاف الإشعارات -->
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="font-semibold mb-1">إشعارات المتصفح</h4>
                                    <p class="text-sm text-gray-500">عرض إشعارات عند وصول رسالة جديدة</p>
                        </div>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox"
                                           class="sr-only peer"
                                           :checked="notificationsEnabled"
                                           @change="toggleNotifications()" />
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary/20 dark:peer-focus:ring-primary/40 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:ltr:left-[2px] after:rtl:right-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-primary"></div>
                                </label>
                    </div>

                            <!-- حالة إذن الإشعارات -->
                            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-sm text-gray-500">حالة الإذن:</span>
                                    <span class="text-sm font-semibold"
                                          :class="{
                                              'text-success': notificationPermission === 'granted',
                                              'text-warning': notificationPermission === 'default',
                                              'text-danger': notificationPermission === 'denied'
                                          }"
                                          x-text="notificationPermission === 'granted' ? 'مفعل' :
                                                  notificationPermission === 'denied' ? 'مرفوض' :
                                                  notificationPermission === 'unsupported' ? 'غير مدعوم' : 'في الانتظار'"></span>
                                </div>
                                <template x-if="notificationPermission !== 'granted' && notificationPermission !== 'unsupported'">
                                    <button type="button"
                                        class="btn btn-primary btn-sm w-full"
                                        @click="requestPermissionIfNeeded()">
                                        طلب إذن الإشعارات
                                    </button>
                </template>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("alpine:init", () => {
            Alpine.data("chat", () => ({
                init() {
                    // في الموبايل: إظهار القائمة افتراضياً
                    if (window.innerWidth < 1280) { // xl breakpoint
                        this.isShowChatMenu = true;
                        this.isShowUserChat = false;
                    }
                    // بدء polling للمحادثات عند تحميل المكون
                    setTimeout(() => {
                        this.startConversationsPolling();
                    }, 1000);
                    // جلب المستخدمين المتاحين للمجموعات
                    this.loadAvailableUsersForGroup();
                    // طلب إذن الإشعارات
                    this.requestNotificationPermission();
                    // تحميل الإعدادات من localStorage
                    this.loadNotificationSettings();
                    // التحقق من إشعارات المحادثات
                    this.checkConversationAlerts();
                    // تحديث الإشعارات كل 10 ثوانٍ
                    setInterval(() => {
                        this.checkConversationAlerts();
                    }, 10000);
                },
                isShowUserChat: false,
                isShowChatMenu: false,
                loginUser: {
                    id: {{ auth()->id() }},
                    name: '{{ auth()->user()->name }}',
                    path: '{{ auth()->user()->profile_image_url }}',
                    designation: '{{ auth()->user()->role }}',
                    role: '{{ auth()->user()->role }}',
                },
                conversationsList: @json($conversationsList->values()->all()),
                availableUsersList: @json($availableUsersList->values()->all()),
                availableUsersForGroup: @json($availableUsersForGroup ?? []),
                activeTab: 'contacts', // 'chats' أو 'contacts' - افتراضي contacts
                searchUser: '',
                textMessage: '',
                selectedUser: '',
                showOrderSearch: false,
                orderSearchQuery: '',
                orderSearchResults: [],
                selectedOrder: null,
                showProductSearch: false,
                productSearchQuery: '',
                productSearchResults: [],
                selectedProduct: null,
                pollingInterval: null,
                conversationsPollingInterval: null,
                showCreateGroupModal: false,
                selectedImage: null,
                imagePreview: null,
                showGroupManageModal: false,
                showNotificationSettings: false,
                notificationPermission: 'default',
                soundEnabled: true,
                notificationsEnabled: true,
                lastMessageIds: {},
                unreadCount: 0,
                groupTitle: '',
                selectedUserIds: [],
                availableUsersForGroup: [],
                currentGroupParticipants: [],
                currentGroupId: null,

                get contactList() {
                    // إرجاع القائمة المناسبة حسب التبويب النشط
                    if (this.activeTab === 'chats') {
                        return this.conversationsList;
                    } else {
                        return this.availableUsersList;
                    }
                },

                get searchUsers() {
                    setTimeout(() => {
                        const element = document.querySelector('.chat-users');
                        if (element) {
                        element.scrollTop = 0;
                        element.behavior = "smooth";
                        }
                    });
                    return this.contactList.filter((d) => {
                        return d.name.toLowerCase().includes(this.searchUser.toLowerCase())
                    });
                },

                async selectUser(user) {
                    // إيقاف polling السابق
                    this.stopPolling();

                    // في الموبايل: إخفاء القائمة وإظهار المحادثة
                    if (window.innerWidth < 1280) { // xl breakpoint
                        this.isShowChatMenu = false;
                    }

                    this.selectedUser = user;
                    this.isShowUserChat = true;

                    // إذا كانت المجموعة، جلب الرسائل مباشرة
                    if (user.type === 'group' && user.conversationId) {
                        await this.loadMessages(user.conversationId);
                        this.startPolling();
                        this.scrollToBottom;
                        return;
                    }

                    // إذا لم تكن هناك محادثة، أنشئ واحدة
                    if (!user.conversationId && user.userId) {
                        try {
                            const response = await fetch('{{ route("chat.get-or-create-conversation") }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ user_id: user.userId })
                            });
                            const data = await response.json();
                            if (data.conversation_id) {
                                user.conversationId = data.conversation_id;
                                // إضافة المستخدم إلى conversationsList إذا لم يكن موجوداً
                                if (!this.conversationsList.find(c => c.userId === user.userId)) {
                                    this.conversationsList.push({
                                        userId: user.userId,
                                        name: user.name,
                                        path: user.path,
                                        time: '',
                                        preview: '',
                                        messages: [],
                        active: true,
                                        conversationId: data.conversation_id
                                    });
                                }
                                // جلب الرسائل بعد إنشاء المحادثة
                                await this.loadMessages(data.conversation_id);
                                // بدء polling للرسائل
                                this.startPolling();
                            }
                        } catch (error) {
                            console.error('Error creating conversation:', error);
                            alert('حدث خطأ في إنشاء المحادثة');
                        }
                    } else if (user.conversationId) {
                        // جلب الرسائل إذا كانت المحادثة موجودة
                        await this.loadMessages(user.conversationId);
                        // بدء polling للرسائل
                        this.startPolling();
                    }

                    this.scrollToBottom;
                },

                async loadMessages(conversationId) {
                    try {
                        const response = await fetch(`{{ route("chat.messages") }}?conversation_id=${conversationId}`);
                        const messages = await response.json();

                        // التحقق من وجود رسائل جديدة
                        const currentMessagesCount = this.selectedUser?.messages?.length || 0;
                        const hasNewMessages = messages.length > currentMessagesCount;

                        // التحقق من رسائل جديدة للإشعارات
                        this.checkForNewMessages(messages, conversationId);

                        // تحديث الرسائل في selectedUser مباشرة
                        if (this.selectedUser) {
                            this.selectedUser.messages = messages;
                        }
                        // تحديث الرسائل في القوائم أيضاً
                        let user = this.conversationsList.find((d) => d.conversationId === conversationId);
                        if (user) {
                            user.messages = messages;
                            // تحديث preview و time إذا كانت هناك رسائل جديدة
                            if (hasNewMessages && messages.length > 0) {
                                const lastMessage = messages[messages.length - 1];
                                user.preview = lastMessage.text || (lastMessage.image_url ? 'صورة' : '') || '';
                                user.time = lastMessage.time || '';
                            }
                        }
                        user = this.availableUsersList.find((d) => d.conversationId === conversationId);
                        if (user) {
                            user.messages = messages;
                            // تحديث preview و time إذا كانت هناك رسائل جديدة
                            if (hasNewMessages && messages.length > 0) {
                                const lastMessage = messages[messages.length - 1];
                                user.preview = lastMessage.text || (lastMessage.image_url ? 'صورة' : '') || '';
                                user.time = lastMessage.time || '';
                            }
                        }

                        // تحديث lastMessageId
                        if (messages.length > 0) {
                            const lastMessage = messages[messages.length - 1];
                            if (lastMessage.id) {
                                this.lastMessageIds[conversationId] = lastMessage.id;
                            }
                        }

                        // التمرير للأسفل فقط إذا كانت هناك رسائل جديدة
                        if (hasNewMessages) {
                            this.scrollToBottom;
                        }
                    } catch (error) {
                        console.error('Error loading messages:', error);
                    }
                },

                startPolling() {
                    // إيقاف أي polling سابق
                    this.stopPolling();

                    // بدء polling جديد كل 5 ثوان (تم تحسينه من 1 ثانية لتقليل الحمل)
                    // الاعتماد على الإشعارات الفورية (FCM + Web Push) بدلاً من polling المكثف
                    this.pollingInterval = setInterval(async () => {
                        if (this.selectedUser && this.selectedUser.conversationId) {
                            await this.loadMessages(this.selectedUser.conversationId);
                        }
                    }, 5000); // 5 ثوان (تم تحسينه من 1 ثانية)
                },

                stopPolling() {
                    if (this.pollingInterval) {
                        clearInterval(this.pollingInterval);
                        this.pollingInterval = null;
                    }
                },

                startConversationsPolling() {
                    // إيقاف أي polling سابق
                    if (this.conversationsPollingInterval) {
                        clearInterval(this.conversationsPollingInterval);
                    }

                    // بدء polling لقائمة المحادثات كل 15 ثانية (تم تحسينه من 10 ثوان)
                    // الاعتماد على الإشعارات الفورية لتحديث القائمة
                    this.conversationsPollingInterval = setInterval(async () => {
                        await this.loadConversations();
                    }, 15000); // 15 ثانية (تم تحسينه من 10 ثوان)
                },

                stopConversationsPolling() {
                    if (this.conversationsPollingInterval) {
                        clearInterval(this.conversationsPollingInterval);
                        this.conversationsPollingInterval = null;
                    }
                },

                async loadConversations() {
                    try {
                        const response = await fetch('{{ route("chat.conversations") }}');
                        const conversations = await response.json();

                        // تحديث conversationsList مع الحفاظ على الرسائل المحلية
                        conversations.forEach(conv => {
                            const existingConv = this.conversationsList.find(c => c.conversationId === conv.id);
                            if (existingConv) {
                                // تحديث البيانات مع الحفاظ على الرسائل
                                existingConv.preview = conv.preview;
                                existingConv.time = conv.time;
                                existingConv.unread_count = conv.unread_count;
                            } else {
                                // إضافة محادثة جديدة
                                this.conversationsList.push({
                                    userId: conv.userId,
                                    name: conv.name,
                                    code: conv.code,
                                    path: conv.path,
                                    time: conv.time,
                                    preview: conv.preview,
                                    messages: [],
                                    active: conv.active,
                                    conversationId: conv.id,
                                    unread_count: conv.unread_count,
                                    type: conv.type || 'direct',
                                    participants_count: conv.participants_count
                                });
                            }
                        });

                        // تحديث Badge بعدد الرسائل غير المقروءة
                        this.updateBadge();
                    } catch (error) {
                        console.error('Error loading conversations:', error);
                    }
                },

                handleImageUpload(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        return;
                    }

                    // التحقق من نوع الملف
                    if (!file.type.startsWith('image/')) {
                        alert('يرجى اختيار ملف صورة');
                        event.target.value = '';
                        return;
                    }

                    // التحقق من حجم الملف (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        alert('حجم الصورة يجب أن يكون أقل من 5MB');
                        event.target.value = '';
                        return;
                    }

                    // حفظ الملف وعرض preview
                    this.selectedImage = file;
                    const reader = new FileReader();
                    const self = this;
                    reader.onload = function(e) {
                        self.imagePreview = e.target.result;
                        // إرسال الصورة مباشرة بعد تحميل preview
                        self.sendImageMessage();
                    };
                    reader.readAsDataURL(file);
                },

                async sendImageMessage() {
                    if (!this.selectedImage || !this.selectedUser) {
                        return;
                    }

                    const messageText = this.textMessage.trim();
                    const imageFile = this.selectedImage;
                    const imagePreviewUrl = this.imagePreview;

                    // إضافة الرسالة محلياً أولاً
                    if (!this.selectedUser.messages) {
                        this.selectedUser.messages = [];
                    }
                    this.selectedUser.messages.push({
                        fromUserId: this.loginUser.id,
                        toUserId: this.selectedUser.userId,
                        text: messageText || '',
                        image_url: imagePreviewUrl,
                        type: 'image',
                        time: 'Just now',
                    });

                    this.scrollToBottom;

                    // إرسال الرسالة للخادم
                    try {
                        let url;
                        const conversationId = this.selectedUser.conversationId;
                        const formData = new FormData();

                        if (conversationId) {
                            url = '{{ route("chat.send") }}';
                            formData.append('conversation_id', conversationId);
                        } else if (this.selectedUser.userId) {
                            url = '{{ route("chat.send-to-user") }}';
                            formData.append('user_id', this.selectedUser.userId);
                        } else {
                            return;
                        }

                        formData.append('message', messageText);
                        formData.append('image', imageFile);

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: formData
                        });

                        // التحقق من نوع الاستجابة
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            const text = await response.text();
                            console.error('Expected JSON but got:', contentType, text.substring(0, 200));
                            alert('حدث خطأ في إرسال الصورة. يرجى المحاولة مرة أخرى.');
                            // إزالة الرسالة المحلية في حالة الخطأ
                            if (this.selectedUser.messages.length > 0) {
                                this.selectedUser.messages.pop();
                            }
                            return;
                        }

                        const data = await response.json();
                        if (data.success && data.message) {
                            // تحديث الرسالة بالبيانات من الخادم
                            if (this.selectedUser.messages.length > 0) {
                                const lastMessage = this.selectedUser.messages[this.selectedUser.messages.length - 1];
                                if (lastMessage.image_url === imagePreviewUrl) {
                                    lastMessage.id = data.message.id;
                                    lastMessage.time = data.message.time;
                                    lastMessage.text = data.message.text;
                                    if (data.message.image_url) {
                                        lastMessage.image_url = data.message.image_url;
                                    }
                                }
                            }
                            // تحديث preview و time في conversationsList
                            let conv = this.conversationsList.find(c => c.conversationId === conversationId);
                            if (conv) {
                                conv.preview = messageText || 'صورة';
                                conv.time = data.message.time;
                            }
                            // تحديث preview و time في availableUsersList
                            let userInAvailable = this.availableUsersList.find(u => u.conversationId === conversationId);
                            if (userInAvailable) {
                                userInAvailable.preview = messageText || 'صورة';
                                userInAvailable.time = data.message.time;
                            }

                            // إذا تم إنشاء محادثة جديدة
                            if (data.conversation_id) {
                                this.selectedUser.conversationId = data.conversation_id;
                                const userInList = this.conversationsList.find(c => c.userId === this.selectedUser.userId);
                                if (userInList) {
                                    userInList.conversationId = data.conversation_id;
                                }
                                const userInAvailable = this.availableUsersList.find(c => c.userId === this.selectedUser.userId);
                                if (userInAvailable) {
                                    userInAvailable.conversationId = data.conversation_id;
                                }
                            }

                            // إعادة تعيين الحقول
                            this.textMessage = '';
                            this.selectedImage = null;
                            this.imagePreview = null;
                            document.getElementById('imageInput').value = '';

                            this.scrollToBottom;
                        } else {
                            alert(data.error || 'حدث خطأ في إرسال الصورة');
                            // إزالة الرسالة المحلية في حالة الخطأ
                            if (this.selectedUser.messages.length > 0) {
                                this.selectedUser.messages.pop();
                            }
                        }
                    } catch (error) {
                        console.error('Error sending image:', error);
                        // إزالة الرسالة المحلية في حالة الخطأ
                        if (this.selectedUser.messages.length > 0) {
                            this.selectedUser.messages.pop();
                        }
                        alert('حدث خطأ في إرسال الصورة');
                    }
                },

                async sendMessage() {
                    // إذا كانت هناك صورة محدد، استخدم sendImageMessage
                    if (this.selectedImage) {
                        await this.sendImageMessage();
                        return;
                    }

                    if (!this.textMessage.trim() || !this.selectedUser) {
                        return;
                    }

                    const messageText = this.textMessage.trim();
                    this.textMessage = '';

                    // إضافة الرسالة محلياً أولاً إلى selectedUser.messages
                    if (!this.selectedUser.messages) {
                        this.selectedUser.messages = [];
                    }
                    this.selectedUser.messages.push({
                        fromUserId: this.loginUser.id,
                        toUserId: this.selectedUser.userId,
                        text: messageText,
                            time: 'Just now',
                        });

                        this.scrollToBottom;

                    // إرسال الرسالة للخادم
                    try {
                        let url, body;
                        const conversationId = this.selectedUser.conversationId;

                        if (conversationId) {
                            url = '{{ route("chat.send") }}';
                            body = JSON.stringify({
                                conversation_id: conversationId,
                                message: messageText
                            });
                        } else if (this.selectedUser.userId) {
                            url = '{{ route("chat.send-to-user") }}';
                            body = JSON.stringify({
                                user_id: this.selectedUser.userId,
                                message: messageText
                            });
                        } else {
                            return;
                        }

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: body
                        });

                        const data = await response.json();
                        if (data.success && data.message) {
                            // تحديث الرسالة بالبيانات من الخادم
                            if (this.selectedUser.messages.length > 0) {
                                const lastMessage = this.selectedUser.messages[this.selectedUser.messages.length - 1];
                                if (lastMessage.text === messageText) {
                                    lastMessage.id = data.message.id;
                                    lastMessage.time = data.message.time;
                                }
                            }

                            // إذا تم إنشاء محادثة جديدة
                            if (data.conversation_id) {
                                this.selectedUser.conversationId = data.conversation_id;
                                // تحديث conversationId في القوائم أيضاً
                                const userInList = this.conversationsList.find(c => c.userId === this.selectedUser.userId);
                                if (userInList) {
                                    userInList.conversationId = data.conversation_id;
                                }
                                const userInAvailable = this.availableUsersList.find(c => c.userId === this.selectedUser.userId);
                                if (userInAvailable) {
                                    userInAvailable.conversationId = data.conversation_id;
                                }
                            }
                        } else {
                            // في حالة الخطأ، إزالة الرسالة المحلية
                            this.selectedUser.messages.pop();
                            alert('حدث خطأ في إرسال الرسالة');
                        }
                    } catch (error) {
                        console.error('Error sending message:', error);
                        // في حالة الخطأ، إزالة الرسالة المحلية
                        if (this.selectedUser.messages.length > 0) {
                            this.selectedUser.messages.pop();
                        }
                        alert('حدث خطأ في إرسال الرسالة');
                    }
                },

                get scrollToBottom() {
                    if (this.isShowUserChat) {
                        setTimeout(() => {
                            const element = document.querySelector(
                                '.chat-conversation-box');
                            element.scrollIntoView({
                                behavior: "smooth",
                                block: "end",
                            });
                        });
                    }
                },

                async searchOrders() {
                    if (this.orderSearchQuery.length < 3) {
                        this.orderSearchResults = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route("chat.search-order") }}?query=${encodeURIComponent(this.orderSearchQuery)}`);
                        const orders = await response.json();
                        this.orderSearchResults = orders;
                    } catch (error) {
                        console.error('Error searching orders:', error);
                        this.orderSearchResults = [];
                    }
                },

                async sendOrderMessage(order) {
                    if (!this.selectedUser || !this.selectedUser.conversationId) {
                        alert('يرجى اختيار محادثة أولاً');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("chat.send-order") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                conversation_id: this.selectedUser.conversationId,
                                order_id: order.id
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.message) {
                            // إضافة الرسالة إلى المحادثة
                            if (!this.selectedUser.messages) {
                                this.selectedUser.messages = [];
                            }
                            this.selectedUser.messages.push(data.message);

                            // إغلاق modal البحث
                            this.showOrderSearch = false;
                            this.orderSearchQuery = '';
                            this.orderSearchResults = [];

                            this.scrollToBottom;
                        } else {
                            alert(data.error || 'حدث خطأ في إرسال الطلب');
                        }
                    } catch (error) {
                        console.error('Error sending order message:', error);
                        alert('حدث خطأ في إرسال الطلب');
                    }
                },

                async searchProducts() {
                    if (this.productSearchQuery.length < 2) {
                        this.productSearchResults = [];
                        return;
                    }

                    try {
                        const response = await fetch(`{{ route("chat.search-product") }}?query=${encodeURIComponent(this.productSearchQuery)}`);
                        const products = await response.json();
                        this.productSearchResults = products;
                    } catch (error) {
                        console.error('Error searching products:', error);
                        this.productSearchResults = [];
                    }
                },

                async sendProductMessage(product) {
                    if (!this.selectedUser || !this.selectedUser.conversationId) {
                        alert('يرجى اختيار محادثة أولاً');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("chat.send-product") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                conversation_id: this.selectedUser.conversationId,
                                product_id: product.id
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.message) {
                            // إضافة الرسالة إلى المحادثة
                            if (!this.selectedUser.messages) {
                                this.selectedUser.messages = [];
                            }
                            this.selectedUser.messages.push(data.message);

                            // إغلاق modal البحث
                            this.showProductSearch = false;
                            this.productSearchQuery = '';
                            this.productSearchResults = [];

                            this.scrollToBottom;
                        } else {
                            alert(data.error || 'حدث خطأ في إرسال المنتج');
                        }
                    } catch (error) {
                        console.error('Error sending product message:', error);
                        alert('حدث خطأ في إرسال المنتج');
                    }
                },

                async loadAvailableUsersForGroup() {
                    // استخدام البيانات من الخادم
                    this.availableUsersForGroup = @json($availableUsersForGroup ?? []);
                },

                async createGroup() {
                    if (!this.groupTitle.trim()) {
                        alert('يرجى إدخال اسم المجموعة');
                        return;
                    }

                    if (this.selectedUserIds.length === 0) {
                        alert('يرجى اختيار مستخدم واحد على الأقل');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("chat.create-group") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                title: this.groupTitle,
                                user_ids: this.selectedUserIds
                            })
                        });

                        const data = await response.json();
                        if (data.success && data.conversation) {
                            // إضافة المجموعة إلى قائمة المحادثات
                            const newGroup = {
                                userId: null,
                                name: data.conversation.title,
                                code: null,
                                path: 'group-icon.svg',
                                time: 'Just now',
                                preview: '',
                        messages: [],
                        active: true,
                                conversationId: data.conversation.id,
                                type: 'group',
                                participants_count: data.conversation.participants_count,
                            };
                            this.conversationsList.push(newGroup);

                            // إغلاق modal وإعادة تعيين الحقول
                            this.showCreateGroupModal = false;
                            this.groupTitle = '';
                            this.selectedUserIds = [];

                            // فتح المحادثة الجديدة
                            await this.selectUser(newGroup);

                            alert('تم إنشاء المجموعة بنجاح');
                        } else {
                            alert(data.error || 'حدث خطأ في إنشاء المجموعة');
                        }
                    } catch (error) {
                        console.error('Error creating group:', error);
                        alert('حدث خطأ في إنشاء المجموعة');
                    }
                },

                async openGroupManageModal(conversationId) {
                    this.currentGroupId = conversationId;
                    this.selectedUserIds = [];

                    try {
                        const response = await fetch(`{{ url('/api/chat/group-participants') }}/${conversationId}`);
                        const data = await response.json();

                        if (data.participants) {
                            this.currentGroupParticipants = data.participants;
                            this.showGroupManageModal = true;
                        } else {
                            alert(data.error || 'حدث خطأ في جلب بيانات المجموعة');
                        }
                    } catch (error) {
                        console.error('Error loading group participants:', error);
                        alert('حدث خطأ في جلب بيانات المجموعة');
                    }
                },

                async addParticipantsToGroup() {
                    if (this.selectedUserIds.length === 0) {
                        alert('يرجى اختيار مستخدم واحد على الأقل');
                        return;
                    }

                    if (!this.currentGroupId) {
                        alert('خطأ: لا توجد مجموعة محددة');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("chat.add-participants") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                conversation_id: this.currentGroupId,
                                user_ids: this.selectedUserIds
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // تحديث قائمة المشاركين
                            await this.openGroupManageModal(this.currentGroupId);
                            this.selectedUserIds = [];

                            // تحديث conversationsList
                            const group = this.conversationsList.find(c => c.conversationId === this.currentGroupId);
                            if (group) {
                                group.participants_count = data.participants_count;
                            }

                            alert(`تم إضافة ${data.added_count} مستخدم بنجاح`);
                        } else {
                            alert(data.error || 'حدث خطأ في إضافة المستخدمين');
                        }
                    } catch (error) {
                        console.error('Error adding participants:', error);
                        alert('حدث خطأ في إضافة المستخدمين');
                    }
                },

                async removeParticipant(userId) {
                    if (!confirm('هل أنت متأكد من إزالة هذا المستخدم من المجموعة؟')) {
                        return;
                    }

                    if (!this.currentGroupId) {
                        alert('خطأ: لا توجد مجموعة محددة');
                        return;
                    }

                    try {
                        const response = await fetch('{{ route("chat.remove-participant") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                conversation_id: this.currentGroupId,
                                user_id: userId
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            // تحديث قائمة المشاركين
                            await this.openGroupManageModal(this.currentGroupId);

                            // تحديث conversationsList
                            const group = this.conversationsList.find(c => c.conversationId === this.currentGroupId);
                            if (group) {
                                group.participants_count = data.participants_count;
                            }

                            alert('تم إزالة المستخدم بنجاح');
                        } else {
                            alert(data.error || 'حدث خطأ في إزالة المستخدم');
                        }
                    } catch (error) {
                        console.error('Error removing participant:', error);
                        alert('حدث خطأ في إزالة المستخدم');
                    }
                },

                async requestNotificationPermission() {
                    if (!('Notification' in window)) {
                        console.log('This browser does not support notifications');
                        this.notificationPermission = 'unsupported';
                        return false;
                    }

                    if (Notification.permission === 'granted') {
                        this.notificationPermission = 'granted';
                        return true;
                    }

                    if (Notification.permission === 'denied') {
                        this.notificationPermission = 'denied';
                        return false;
                    }

                    // طلب الإذن فقط إذا كان المستخدم في صفحة المحادثة
                    // سنطلب الإذن عند أول رسالة واردة بدلاً من فوراً
                    this.notificationPermission = Notification.permission;
                    return false;
                },

                async requestPermissionIfNeeded() {
                    if (this.notificationPermission === 'granted') {
                        return true;
                    }

                    if (this.notificationPermission === 'denied' || this.notificationPermission === 'unsupported') {
                        return false;
                    }

                    const permission = await Notification.requestPermission();
                    this.notificationPermission = permission;

                    // حفظ الإذن في localStorage
                    localStorage.setItem('chat_notification_permission', permission);

                    return permission === 'granted';
                },

                showNotification(title, body, icon, conversationId) {
                    // التحقق من تفعيل الإشعارات
                    if (!this.notificationsEnabled) {
                        return;
                    }

                    // التحقق من الإذن
                    if (this.notificationPermission !== 'granted') {
                        // محاولة طلب الإذن
                        this.requestPermissionIfNeeded().then(granted => {
                            if (granted) {
                                this.showNotification(title, body, icon, conversationId);
                            }
                        });
                        return;
                    }

                    // التحقق من أن الصفحة غير مرئية أو المحادثة غير مفتوحة
                    const isPageVisible = document.visibilityState === 'visible';
                    const isConversationOpen = this.selectedUser &&
                                               this.selectedUser.conversationId === conversationId;

                    if (isPageVisible && isConversationOpen) {
                        return; // لا نعرض إشعار إذا كانت المحادثة مفتوحة والصفحة مرئية
                    }

                    try {
                        const notification = new Notification(title, {
                            body: body,
                            icon: icon || '/assets/images/icons/icon-192x192.png',
                            badge: '/assets/images/icons/icon-192x192.png',
                            tag: `chat-${conversationId}`, // لمنع الإشعارات المكررة
                            requireInteraction: false,
                            silent: false,
                        });

                        notification.onclick = () => {
                            window.focus();
                            // فتح المحادثة
                            const conversation = this.conversationsList.find(c => c.conversationId === conversationId);
                            if (conversation) {
                                this.selectUser(conversation);
                            }
                            notification.close();
                        };

                        // إغلاق الإشعار تلقائياً بعد 5 ثوانٍ
                        setTimeout(() => notification.close(), 5000);
                    } catch (error) {
                        console.error('Error showing notification:', error);
                    }
                },

                playNotificationSound() {
                    if (!this.soundEnabled) {
                        return;
                    }

                    try {
                        const audio = new Audio('/assets/sounds/notification.mp3');
                        audio.volume = 0.5; // 50% volume
                        audio.play().catch(error => {
                            console.log('Error playing notification sound:', error);
                            // إذا فشل تحميل الصوت، استخدم صوت المتصفح الافتراضي
                            if (audio.error) {
                                // محاولة استخدام Web Audio API كبديل
                                this.playFallbackSound();
                            }
                        });
                    } catch (error) {
                        console.error('Error creating audio:', error);
                        this.playFallbackSound();
                    }
                },

                playFallbackSound() {
                    // استخدام Web Audio API لإنشاء صوت بسيط
                    try {
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);

                        oscillator.frequency.value = 800;
                        oscillator.type = 'sine';

                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.2);

                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.2);
                    } catch (error) {
                        console.log('Fallback sound not supported');
                    }
                },

                updateBadge() {
                    if (!('setAppBadge' in navigator)) {
                        return;
                    }

                    try {
                        const totalUnread = this.conversationsList.reduce((sum, conv) => {
                            return sum + (conv.unread_count || 0);
                        }, 0);

                        this.unreadCount = totalUnread;

                        if (totalUnread > 0) {
                            navigator.setAppBadge(totalUnread);
                        } else {
                            navigator.clearAppBadge();
                        }
                    } catch (error) {
                        console.error('Error updating badge:', error);
                    }
                },

                checkForNewMessages(messages, conversationId) {
                    if (!messages || messages.length === 0) {
                        return;
                    }

                    const lastMessageId = this.lastMessageIds[conversationId] || 0;
                    const newMessages = messages.filter(msg => {
                        return msg.id &&
                               msg.id > lastMessageId &&
                               msg.fromUserId !== this.loginUser.id;
                    });

                    if (newMessages.length > 0) {
                        // تحديث lastMessageId
                        const latestMessage = newMessages[newMessages.length - 1];
                        if (latestMessage.id) {
                            this.lastMessageIds[conversationId] = latestMessage.id;
                        }

                        // فقط إذا كانت الصفحة غير مرئية أو المستخدم ليس في هذه المحادثة
                        // الاعتماد على نظام الإشعارات الموحد (NotificationManager) للإشعارات
                        // هذا يمنع التكرار ويحسن الأداء
                        if (document.visibilityState === 'hidden' ||
                            !this.selectedUser ||
                            this.selectedUser.conversationId !== conversationId) {

                            // الحصول على معلومات المحادثة
                            const conversation = this.conversationsList.find(c => c.conversationId === conversationId) ||
                                              this.availableUsersList.find(c => c.conversationId === conversationId);

                            // استخدام sender_name من الرسالة إذا كانت متوفرة (للمجموعات)، وإلا استخدم اسم المحادثة
                            let senderName = latestMessage.sender_name || (conversation ? conversation.name : 'مستخدم');
                            let messageText = latestMessage.text || '';

                            if (!messageText) {
                                if (latestMessage.image_url) {
                                    messageText = 'صورة';
                                } else if (latestMessage.type === 'order' && latestMessage.order) {
                                    messageText = 'طلب: ' + (latestMessage.order.order_number || '');
                                } else if (latestMessage.type === 'product' && latestMessage.product) {
                                    messageText = 'منتج: ' + (latestMessage.product.name || '');
                                } else {
                                    messageText = 'رسالة جديدة';
                                }
                            }

                            // تقصير النص إذا كان طويلاً
                            if (messageText.length > 50) {
                                messageText = messageText.substring(0, 50) + '...';
                            }

                            // عرض إشعار وصوت (فقط إذا لم يكن المستخدم في المحادثة)
                            this.showNotification(
                                `رسالة جديدة من ${senderName}`,
                                messageText,
                                null,
                                conversationId
                            );
                            this.playNotificationSound();
                        }
                    }
                },

                async checkConversationAlerts() {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const allConversations = [...this.conversationsList, ...this.availableUsersList];

                    for (const conversation of allConversations) {
                        if (!conversation.conversationId) continue;

                        try {
                            const response = await fetch(`/api/sweet-alerts/check-conversation/${conversation.conversationId}`, {
                                method: 'GET',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': csrfToken || '',
                                },
                                credentials: 'same-origin',
                            });

                            if (response.ok) {
                                const data = await response.json();
                                const badge = document.getElementById(`conversation-badge-${conversation.conversationId}`);
                                const activeIndicator = document.getElementById(`active-indicator-${conversation.conversationId}`);

                                if (badge) {
                                    if (data.has_unread) {
                                        badge.classList.remove('hidden');
                                        // إخفاء النقطة الخضراء إذا كان هناك إشعار
                                        if (activeIndicator) {
                                            activeIndicator.style.display = 'none';
                                        }
                                    } else {
                                        badge.classList.add('hidden');
                                        // إظهار النقطة الخضراء إذا لم يكن هناك إشعار وكان المستخدم نشط
                                        if (activeIndicator && conversation.active) {
                                            activeIndicator.style.display = 'block';
                                        }
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('Error checking conversation alert:', error);
                        }
                    }
                },

                loadNotificationSettings() {
                    // تحميل الإعدادات من localStorage
                    const savedSoundEnabled = localStorage.getItem('chat_sound_enabled');
                    const savedNotificationsEnabled = localStorage.getItem('chat_notifications_enabled');
                    const savedPermission = localStorage.getItem('chat_notification_permission');

                    if (savedSoundEnabled !== null) {
                        this.soundEnabled = savedSoundEnabled === 'true';
                    }

                    if (savedNotificationsEnabled !== null) {
                        this.notificationsEnabled = savedNotificationsEnabled === 'true';
                    }

                    if (savedPermission) {
                        this.notificationPermission = savedPermission;
                    } else if ('Notification' in window) {
                        this.notificationPermission = Notification.permission;
                    }
                },

                saveNotificationSettings() {
                    // حفظ الإعدادات في localStorage
                    localStorage.setItem('chat_sound_enabled', this.soundEnabled.toString());
                    localStorage.setItem('chat_notifications_enabled', this.notificationsEnabled.toString());
                    localStorage.setItem('chat_notification_permission', this.notificationPermission);
                },

                toggleSound() {
                    this.soundEnabled = !this.soundEnabled;
                    this.saveNotificationSettings();
                },

                toggleNotifications() {
                    this.notificationsEnabled = !this.notificationsEnabled;
                    this.saveNotificationSettings();

                    if (this.notificationsEnabled && this.notificationPermission !== 'granted') {
                        this.requestPermissionIfNeeded();
                    }
                },
            }));
        });

        // إيقاف polling عند إغلاق الصفحة
        window.addEventListener('beforeunload', function() {
            const chatElement = document.querySelector('[x-data="chat"]');
            if (chatElement && chatElement._x_dataStack) {
                const chatComponent = chatElement._x_dataStack[0];
                if (chatComponent) {
                    chatComponent.stopPolling();
                    chatComponent.stopConversationsPolling();
                }
            }
        });

        // إيقاف polling عندما تكون الصفحة غير مرئية
        document.addEventListener('visibilitychange', function() {
            const chatElement = document.querySelector('[x-data="chat"]');
            if (chatElement && chatElement._x_dataStack) {
                const chatComponent = chatElement._x_dataStack[0];
                if (chatComponent) {
                    if (document.hidden) {
                        chatComponent.stopPolling();
                    } else {
                        if (chatComponent.selectedUser && chatComponent.selectedUser.conversationId) {
                            chatComponent.startPolling();
                        }
                    }
                }
            }
        });
    </script>

    <style>
        @keyframes ping {
            75%, 100% {
                transform: scale(1.5);
                opacity: 0;
            }
        }
        @keyframes glow {
            0%, 100% {
                box-shadow: 0 0 5px rgba(239, 68, 68, 0.5), 0 0 10px rgba(239, 68, 68, 0.3);
            }
            50% {
                box-shadow: 0 0 10px rgba(239, 68, 68, 0.8), 0 0 20px rgba(239, 68, 68, 0.5);
            }
        }
        [id^="conversation-badge-"]:not(.hidden) {
            animation: ping 2s cubic-bezier(0, 0, 0.2, 1) infinite, glow 2s ease-in-out infinite;
        }
        /* تحسين الموضع للـ badge فوق صورة البروفايل */
        [id^="conversation-badge-"] {
            z-index: 20 !important;
        }
        /* للموبايل */
        @media (max-width: 640px) {
            [id^="conversation-badge-"] {
                width: 0.875rem !important;
                height: 0.875rem !important;
                top: -2px !important;
            }
        }
        /* للديسكتوب */
        @media (min-width: 641px) {
            [id^="conversation-badge-"] {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }
        }
    </style>

</x-layout.default>
