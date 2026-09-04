# Agent

[![Tests](https://github.com/larva-cool/agent/actions/workflows/tests.yml/badge.svg)](https://github.com/larva-cool/agent/actions/workflows/tests.yml)
[![Latest Stable Version](https://img.shields.io/packagist/v/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![Total Downloads](https://img.shields.io/packagist/dt/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![PHP Version](https://img.shields.io/packagist/php-v/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![License](https://img.shields.io/packagist/l/larva/agent.svg)](LICENSE.md)

桌面端 / 移动端 User-Agent 解析库，基于 [Mobile Detect](https://github.com/serbanghita/Mobile-Detect) 扩展了桌面设备识别、浏览器与操作系统识别等能力，并对 Laravel 提供开箱即用的支持。

## 关于本仓库

本项目 Fork 自 [jenssegers/agent](https://github.com/jenssegers/agent)。原仓库已停止维护，本仓库在其基础上继续维护，主要变更：

- 包名由 `jenssegers/agent` 改为 `larva/agent`，命名空间由 `Jenssegers\Agent` 改为 `Larva\Agent`
- 底层依赖升级到 Mobile Detect 4.x，最低要求 PHP 8.2，全量补齐强类型声明
- 支持 Laravel 包自动发现（无需手动注册 ServiceProvider 与 Facade）
- 新增 `deviceType()` 方法，一次性返回设备类型
- 补充完整测试套件与 GitHub Actions CI

## 环境要求

- PHP >= 8.4
- ext-mbstring

## 安装

```bash
composer require larva/agent
```

## Laravel 集成

本包已支持 Laravel 包自动发现，安装后即可直接使用 `Agent` Facade，无需任何额外配置。

```php
use Agent;

if (Agent::isMobile()) {
    // 移动端逻辑
}
```

如果你禁用了自动发现，请在 `config/app.php` 中手动注册：

```php
'providers' => [
    Larva\Agent\AgentServiceProvider::class,
],

'aliases' => [
    'Agent' => Larva\Agent\Facades\Agent::class,
],
```

## 基本用法

```php
use Larva\Agent\Agent;

$agent = new Agent();
```

默认会自动从 `$_SERVER` 读取当前请求的 HTTP 头。在 CLI 脚本或需要解析指定 UA 时，使用 `setUserAgent()` 或 `setHttpHeaders()`：

```php
$agent->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15');

// 或者一次性注入完整的 HTTP 头（会自动从中提取 User-Agent）
$agent->setHttpHeaders($headers);
```

如果不希望自动读取 `$_SERVER`（例如在测试或队列任务中），可以关闭自动初始化：

```php
$agent = new Agent(config: ['autoInitOfHttpHeaders' => false]);
$agent->setUserAgent($userAgent);
```

> 注意：未设置 User-Agent 就调用 `isMobile()`、`isTablet()`、`is()` 等方法会抛出 `Detection\Exception\MobileDetectException`。

Mobile Detect 的原生方法依然可用，更多示例见 [Mobile Detect Code Examples](https://github.com/serbanghita/Mobile-Detect/wiki/Code-examples)。

### 属性判断

检查 User-Agent 是否命中某条规则：

```php
$agent->is('Windows');
$agent->is('Firefox');
$agent->is('iPhone');
$agent->is('OS X');
```

也可以使用等价的魔术方法：

```php
$agent->isAndroidOS();
$agent->isSafari();
$agent->isWindows();
```

### 设备类型判断

```php
$agent->isMobile();   // 手机或平板
$agent->isTablet();   // 平板
$agent->isPhone();    // 手机（移动设备且非平板）
$agent->isDesktop();  // 桌面设备（非移动、非平板、非爬虫）
$agent->isRobot();    // 爬虫 / 机器人
```

一次性获取设备类型，返回 `desktop` / `phone` / `tablet` / `robot`：

```php
$type = $agent->deviceType();
```

### 正则匹配

```php
$agent->match('regexp', $userAgent);
```

## 扩展功能

以下方法在未识别时统一返回 `false`。

### 语言偏好

解析 `Accept-Language`，按优先级从高到低返回：

```php
$languages = $agent->languages();
// ['zh-cn', 'zh', 'en-us', 'en']

// 也可以直接传入 header 值
$languages = $agent->languages('nl-nl,nl;q=0.9,en-us;q=0.5');
```

### 设备名称

如 `iPhone`、`iPad`、`Macintosh`、`Nexus` 等：

```php
$device = $agent->device();
```

### 操作系统名称

如 `Windows`、`OS X`、`Ubuntu`、`AndroidOS`、`iOS`、`ChromeOS` 等：

```php
$platform = $agent->platform();
```

### 浏览器名称

如 `Chrome`、`Firefox`、`Safari`、`Edge`、`Opera`、`Vivaldi`、`UCBrowser`、`IE` 等：

```php
$browser = $agent->browser();
```

### 爬虫名称

爬虫识别基于 [jaybizzle/crawler-detect](https://github.com/JayBizzle/Crawler-Detect)：

```php
$robot = $agent->robot();
// 'Googlebot'
```

### 版本号

```php
$version = $agent->version($agent->browser());
// '124.0.0.0'

$version = $agent->version($agent->platform());

// 以浮点数返回，便于比较
$version = $agent->version($agent->browser(), Agent::VERSION_TYPE_FLOAT);
// 124.0
```

> 版本号提取依赖 UA 字符串中的特征片段，部分设备或浏览器可能无法准确返回。

## 从 jenssegers/agent 迁移

除了替换 composer 依赖和命名空间（`Jenssegers\Agent` → `Larva\Agent`），还需注意以下由 Mobile Detect 4.x 引入的破坏性变更：

| 变更点 | 旧版本 | 本版本 |
| --- | --- | --- |
| 构造函数 | `new Agent($headers, $userAgent)` | `new Agent(?CacheInterface $cache, array $config)`；HTTP 头改用 `setHttpHeaders()` 注入 |
| 检测方法参数 | `isMobile($ua, $headers)` | `isMobile()` 无参，先 `setUserAgent()` |
| `isDesktop()` / `isPhone()` / `deviceType()` | 接受 `$userAgent`、`$httpHeaders` 参数 | 均改为无参 |
| `match()` | `match($regex, $userAgent = null)` | `match(string $regex, string $userAgent)`，第二个参数必填 |
| 未设置 UA | 静默返回 `false` | 抛出 `MobileDetectException` |
| 扩展规则 | `setDetectionType()` + `getDetectionRulesExtended()` | detection type 机制已移除，`getDetectionRulesExtended()` 仍保留并自动用于 `is()` / `is*()` |
| `deviceType()` 返回值 | 可能返回 `other` | 只返回 `desktop` / `phone` / `tablet` / `robot` |

## 测试

```bash
composer install
vendor/bin/phpunit
```

## 贡献

欢迎提交 Issue 与 Pull Request。提交 PR 前请确保测试通过，并为新增行为补充对应用例。

## 许可协议

本项目基于 [MIT License](LICENSE.md) 开源。
