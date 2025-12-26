<?php

namespace App\Console\Commands;

use App\Models\Investor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class FixInvestorPasswords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investors:fix-passwords';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix investor passwords that were double-hashed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('جارٍ فحص كلمات مرور المستثمرين...');

        $investors = Investor::all();
        $fixed = 0;
        $skipped = 0;

        foreach ($investors as $investor) {
            $password = $investor->password;
            
            // التحقق إذا كانت كلمة المرور تبدو كـ hash مزدوج
            // إذا كانت تبدأ بـ $2y$ أو $2a$ أو $2b$، فهي hashed
            $isHashed = str_starts_with($password, '$2y$') || 
                       str_starts_with($password, '$2a$') || 
                       str_starts_with($password, '$2b$');

            if (!$isHashed) {
                // إذا لم تكن hashed، قم بhashها
                $investor->password = Hash::make($password);
                $investor->save();
                $this->info("تم إصلاح كلمة مرور المستثمر: {$investor->name} (ID: {$investor->id})");
                $fixed++;
            } else {
                $skipped++;
            }
        }

        $this->info("تم الانتهاء. تم إصلاح: {$fixed}، تم تخطي: {$skipped}");
        
        return Command::SUCCESS;
    }
}
