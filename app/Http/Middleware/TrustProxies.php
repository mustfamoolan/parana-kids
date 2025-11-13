<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * Cloudflare IP ranges - يتم الوثوق بجميع IPs من Cloudflare
     * للحصول على IP الحقيقي للمستخدم من header CF-Connecting-IP
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*'; // Trust all proxies (Cloudflare)

    /**
     * The headers that should be used to detect proxies.
     *
     * Cloudflare يرسل IP الحقيقي في X-Forwarded-For header
     * ملف .htaccess يقوم بتحويل CF-Connecting-IP إلى X-Forwarded-For
     *
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO |
        Request::HEADER_X_FORWARDED_AWS_ELB;
}
