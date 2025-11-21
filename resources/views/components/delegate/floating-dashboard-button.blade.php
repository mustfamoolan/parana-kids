@if(auth()->check() && auth()->user()->isDelegate() && !request()->routeIs('delegate.dashboard') && !request()->routeIs('chat.index'))
    <div class="fixed bottom-6 ltr:left-6 rtl:right-6 z-50">
        <a href="{{ route('delegate.dashboard') }}"
           class="btn btn-primary rounded-full p-4 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-110 flex items-center justify-center w-14 h-14">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
            </svg>
        </a>
    </div>
@endif

