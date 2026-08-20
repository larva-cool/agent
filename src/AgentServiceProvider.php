<?php

declare(strict_types=1);

namespace Larva\Agent;

use Illuminate\Support\ServiceProvider;

/**
 * Agent 服务提供器。
 *
 * 提供 Agent 服务，用于解析用户代理字符串。
 */
class AgentServiceProvider extends ServiceProvider
{
    /**
     * 注册服务。
     */
    public function register(): void
    {
        $this->app->singleton(Agent::class, function ($app) {
            // 默认使用 Mobile Detect 内置的内存缓存：无 I/O 开销，且有条数上限，
            // 不会因为线上海量不同的 User-Agent 污染应用共享缓存。
            // 如需换成持久化缓存，可在自己的 Provider 里绑定 new Agent($app['cache.store'])。
            $agent = new Agent();

            // Mobile Detect 4.x 默认会从 $_SERVER 自动初始化，
            // 这里用当前请求的 HTTP 头覆盖，以兼容 Octane 等常驻进程场景。
            $agent->setHttpHeaders($app['request']->server());

            return $agent;
        });

        $this->app->alias(Agent::class, 'agent');
    }
}
