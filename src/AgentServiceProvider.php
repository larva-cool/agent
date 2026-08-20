<?php

declare(strict_types=1);

namespace Larva\Agent;

use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class AgentServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * 注册服务。
     */
    public function register(): void
    {
        $this->app->singleton('agent', function ($app) {
            $agent = new Agent();

            // Mobile Detect 4.x 不再自动读取 $_SERVER，这里显式注入当前请求的 HTTP 头。
            $agent->setHttpHeaders($app['request']->server());

            return $agent;
        });

        $this->app->alias('agent', Agent::class);
    }

    /**
     * 获取本 Provider 提供的服务。
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return ['agent', Agent::class];
    }
}
