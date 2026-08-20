<?php

declare(strict_types=1);

namespace Larva\Agent\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string|false browser(?string $userAgent = null)
 * @method static string|false platform(?string $userAgent = null)
 * @method static string|false device(?string $userAgent = null)
 * @method static string|false robot(?string $userAgent = null)
 * @method static string deviceType()
 * @method static bool isDesktop()
 * @method static bool isPhone()
 * @method static bool isMobile()
 * @method static bool isTablet()
 * @method static bool isRobot(?string $userAgent = null)
 * @method static bool is(string $ruleName)
 * @method static bool match(string $regex, string $userAgent)
 * @method static array languages(?string $acceptLanguage = null)
 * @method static float|bool|string version(string $propertyName, string $type = 'text')
 * @method static string setUserAgent(string $userAgent)
 * @method static void setHttpHeaders(array $httpHeaders = [])
 * @method static string|null getUserAgent()
 *
 * @see \Larva\Agent\Agent
 */
class Agent extends Facade
{
    /**
     * 获取容器中注册的服务名称。
     */
    protected static function getFacadeAccessor(): string
    {
        return 'agent';
    }
}
