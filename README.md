# Agent

[![Latest Stable Version](https://img.shields.io/packagist/v/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![Total Downloads](https://img.shields.io/packagist/dt/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![PHP Version](https://img.shields.io/packagist/php-v/larva/agent.svg)](https://packagist.org/packages/larva/agent)
[![License](https://img.shields.io/packagist/l/larva/agent.svg)](LICENSE.md)

桌面端 / 移动端 User-Agent 解析库，基于 [Mobile Detect](https://github.com/serbanghita/Mobile-Detect) 扩展了桌面设备识别、浏览器与操作系统识别等能力，并对 Laravel 提供开箱即用的支持。

## 关于本仓库

本项目 Fork 自 [jenssegers/agent](https://github.com/jenssegers/agent)。原仓库已停止维护，本仓库在其基础上继续维护，主要变更：

- 包名由 `jenssegers/agent` 改为 `larva/agent`
- 命名空间由 `Jenssegers\Agent` 改为 `Larva\Agent`
- 最低要求 PHP 8.2，测试套件升级至 PHPUnit 12
- 支持 Laravel 包自动发现（无需手动注册 ServiceProvider 与 Facade）
- 新增 `deviceType()` 方法，一次性返回设备类型

> 从 `jenssegers/agent` 迁移：替换 composer 依赖，并把代码中的 `Jenssegers\Agent` 全部替换为 `Larva\Agent` 即可，API 保持兼容。

## 环境要求

- PHP >= 8.2
- ext-mbstring（由 Mobile Detect 依赖）

## 安装

```bash
composer require larva/agent
```

## Laravel 集成

本包已支持 Laravel 包自动发现，安装后即可直接使用 `Agent` Facade，无需任何额外配置。

如果你在 `config/app.php` 中禁用了自动发现，请手动注册：

```php
// config/app.php
'providers' => [
    Larva\Agent\AgentServiceProvider::class,
],

'aliases' => [
    'Agent' => Larva\Agent\Facades\Agent::class,
],
```

在 Laravel 中使用：

```php
use Agent;

if (Agent::isMobile()) {
    // 移动端逻辑
}
```

## 基本用法

创建一个 `Agent` 实例（Laravel 中可直接使用 `Agent` Facade）：

```php
use Larva\Agent\Agent;

$agent = new Agent();
```

默认会读取当前请求的 HTTP 头。在 CLI 脚本或需要解析指定 UA 时，可以使用 `setUserAgent()` 与 `setHttpHeaders()`：

```php
$agent->setUserAgent('Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/537.13+ (KHTML, like Gecko) Version/5.1.7 Safari/534.57.2');
$agent->setHttpHeaders($headers);
```

Mobile Detect 的全部原生方法依然可用，更多示例见 [Mobile Detect Code Examples](https://github.com/serbanghita/Mobile-Detect/wiki/Code-examples)。

### 属性判断

检查 User-Agent 是否包含某个特性：

```php
$agent->is('Windows');
$agent->is('Firefox');
$agent->is('iPhone');
$agent->is('OS X');
```

也可以使用等价的魔术方法：

```php
$agent->isAndroidOS();
$agent->isNexus();
$agent->isSafari();
```

### 设备类型判断

```php
$agent->isMobile();   // 手机或平板
$agent->isTablet();   // 平板
$agent->isPhone();    // 手机（移动设备且非平板）
$agent->isDesktop();  // 桌面设备（非移动、非平板、非爬虫）
$agent->isRobot();    // 爬虫 / 机器人
```

一次性获取设备类型，返回 `desktop` / `phone` / `tablet` / `robot` / `other`：

```php
$type = $agent->deviceType();
```

### 正则匹配

```php
$agent->match('regexp');
```

## 扩展功能

### 语言偏好

获取浏览器的 `Accept-Language` 列表（按优先级排序）：

```php
$languages = $agent->languages();
// ['nl-nl', 'nl', 'en-us', 'en']
```

### 设备名称

获取设备名称，如 `iPhone`、`Nexus`、`AsusTablet` 等：

```php
$device = $agent->device();
```

### 操作系统名称

如 `Windows`、`OS X`、`Ubuntu`、`AndroidOS`、`ChromeOS` 等：

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
$robot = $agent->robot(); // 非爬虫时返回 false
```

### 版本号

`version()` 可以获取浏览器或平台的版本号：

```php
$browser = $agent->browser();
$version = $agent->version($browser);

$platform = $agent->platform();
$version = $agent->version($platform);
```

版本号也可以按浮点数返回，便于比较：

```php
use Larva\Agent\Agent;

$version = $agent->version($agent->browser(), Agent::VERSION_TYPE_FLOAT);
```

> 版本号提取依赖 UA 字符串中的特征片段，部分设备或浏览器可能无法准确返回。

## 测试

```bash
composer install
vendor/bin/phpunit
```

## 贡献

欢迎提交 Issue 与 Pull Request。提交 PR 前请确保测试通过，并为新增行为补充对应用例。

## 许可协议

本项目基于 [MIT License](LICENSE.md) 开源。
