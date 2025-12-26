<?php

namespace App\Console\Commands;

use App\Models\Investor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetInvestorPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investor:reset-password {investor_id} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset password for a specific investor';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $investorId = $this->argument('investor_id');
        $password = $this->option('password');

        $investor = Investor::find($investorId);

        if (!$investor) {
            $this->error("المستثمر غير موجود (ID: {$investorId})");
            return Command::FAILURE;
        }

        $this->info("المستثمر: {$investor->name} (ID: {$investor->id})");

        if (!$password) {
            $password = $this->secret('أدخل كلمة المرور الجديدة (أو اضغط Enter لاستخدام كلمة المرور الافتراضية "password"):');
            
            if (empty($password)) {
                $password = 'password';
                $this->warn('سيتم استخدام كلمة المرور الافتراضية: password');
            }
        }

        // تحديث كلمة المرور مباشرة بدون استخدام boot() method
        // لأن boot() قد يسبب مشاكل إذا كانت كلمة المرور hashed بالفعل
        $investor->password = $password;
        $investor->save();

        $this->info("تم إعادة تعيين كلمة المرور بنجاح للمستثمر: {$investor->name}");
        $this->info("كلمة المرور الجديدة: {$password}");

        return Command::SUCCESS;
    }
}
