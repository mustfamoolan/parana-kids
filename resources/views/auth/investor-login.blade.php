<x-layout.auth>
    <div class="relative flex min-h-screen items-center justify-center bg-[url(/assets/images/auth/map.png)] bg-cover bg-center bg-no-repeat px-6 py-10 dark:bg-[#060818] sm:px-16">
        <div class="relative flex w-full max-w-[1502px] flex-col justify-between overflow-hidden rounded-md bg-white/60 backdrop-blur-lg dark:bg-black/50 lg:min-h-[758px] lg:flex-row lg:gap-10 xl:gap-0">
            <div class="relative hidden w-full items-center justify-center bg-[linear-gradient(225deg,rgba(239,18,98,1)_0%,rgba(67,97,238,1)_100%)] p-5 lg:inline-flex lg:max-w-[835px] xl:-ms-32 ltr:xl:skew-x-[14deg] rtl:xl:skew-x-[-14deg]">
                <div class="ltr:xl:-skew-x-[14deg] rtl:xl:skew-x-[14deg]">
                    <a href="/" class="w-48 block lg:w-72 ms-10">
                        <img src="/assets/images/ParanaKids-removebg-preview.png" alt="المخزن" class="w-full" />
                    </a>
                    <div class="mt-24 hidden w-full max-w-[430px] lg:block">
                        <img src="/assets/images/auth/login.svg" alt="Cover Image" class="w-full" />
                    </div>
                </div>
            </div>
            <div class="relative flex w-full flex-col items-center justify-center gap-6 px-4 pb-16 pt-6 sm:px-6 lg:max-w-[667px]">
                <div class="flex w-full max-w-[440px] items-center gap-2 lg:absolute lg:end-6 lg:top-6 lg:max-w-full">
                    <a href="/" class="block w-8 lg:hidden">
                        <img src="/assets/images/ParanaKids.png" alt="Logo" class="w-full" />
                    </a>
                </div>
                <div class="w-full max-w-[440px] lg:mt-12">
                    <div class="mb-10">
                        <h1 class="text-3xl font-extrabold uppercase !leading-snug text-primary md:text-4xl">تسجيل دخول المستثمر</h1>
                        <p class="text-base font-bold leading-normal text-white-dark">أدخل بياناتك للدخول</p>
                    </div>
                    <form method="POST" action="{{ route('investor.login') }}" class="space-y-5">
                        @csrf
                        <div>
                            <label for="login_field" class="block text-sm font-medium mb-2">الاسم أو رقم الهاتف</label>
                            <input type="text" id="login_field" name="login_field" value="{{ old('login_field') }}"
                                   class="form-input @error('login_field') border-red-500 @enderror"
                                   placeholder="أدخل اسمك أو رقم هاتفك" required autofocus>
                            @error('login_field')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium mb-2">كلمة المرور</label>
                            <input type="password" id="password" name="password"
                                   class="form-input @error('password') border-red-500 @enderror"
                                   placeholder="أدخل كلمة المرور" required>
                            @error('password')
                                <div class="text-red-500 text-sm mt-2">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-full">تسجيل الدخول</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout.auth>

