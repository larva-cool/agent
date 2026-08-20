<?php

declare(strict_types=1);

namespace Larva\Agent\Tests;

use Larva\Agent\Agent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class AgentTest extends TestCase
{
    private const UA_MACOS_SAFARI = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Safari/605.1.15';
    private const UA_WINDOWS_CHROME = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';
    private const UA_WINDOWS_EDGE = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0';
    private const UA_IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1';
    private const UA_IPAD = 'Mozilla/5.0 (iPad; CPU OS 17_4 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.4 Mobile/15E148 Safari/604.1';
    private const UA_ANDROID_PHONE = 'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Mobile Safari/537.36';
    private const UA_GOOGLEBOT = 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)';

    /**
     * 构造一个仅使用指定 User-Agent 的 Agent 实例，避免受运行环境 $_SERVER 影响。
     */
    private function agent(string $userAgent): Agent
    {
        $agent = new Agent(config: ['autoInitOfHttpHeaders' => false]);
        $agent->setUserAgent($userAgent);

        return $agent;
    }

    #[DataProvider('browserProvider')]
    public function testBrowser(string $userAgent, string $expected): void
    {
        $this->assertSame($expected, $this->agent($userAgent)->browser());
    }

    public static function browserProvider(): array
    {
        return [
            'macOS Safari' => [self::UA_MACOS_SAFARI, 'Safari'],
            'Windows Chrome' => [self::UA_WINDOWS_CHROME, 'Chrome'],
            // Edge 的 UA 同时包含 Chrome 标识，补充规则必须优先匹配 Edge。
            'Windows Edge' => [self::UA_WINDOWS_EDGE, 'Edge'],
            'iPhone Safari' => [self::UA_IPHONE, 'Safari'],
        ];
    }

    #[DataProvider('platformProvider')]
    public function testPlatform(string $userAgent, string $expected): void
    {
        $this->assertSame($expected, $this->agent($userAgent)->platform());
    }

    public static function platformProvider(): array
    {
        return [
            'macOS' => [self::UA_MACOS_SAFARI, 'OS X'],
            'Windows' => [self::UA_WINDOWS_CHROME, 'Windows'],
            'iOS' => [self::UA_IPHONE, 'iOS'],
            'Android' => [self::UA_ANDROID_PHONE, 'AndroidOS'],
        ];
    }

    public function testDevice(): void
    {
        $this->assertSame('iPhone', $this->agent(self::UA_IPHONE)->device());
        $this->assertSame('iPad', $this->agent(self::UA_IPAD)->device());
        $this->assertSame('Macintosh', $this->agent(self::UA_MACOS_SAFARI)->device());
    }

    #[DataProvider('deviceTypeProvider')]
    public function testDeviceType(string $userAgent, string $expected): void
    {
        $this->assertSame($expected, $this->agent($userAgent)->deviceType());
    }

    public static function deviceTypeProvider(): array
    {
        return [
            'desktop' => [self::UA_WINDOWS_CHROME, 'desktop'],
            'phone' => [self::UA_IPHONE, 'phone'],
            'tablet' => [self::UA_IPAD, 'tablet'],
            'robot' => [self::UA_GOOGLEBOT, 'robot'],
        ];
    }

    public function testIsDesktop(): void
    {
        $this->assertTrue($this->agent(self::UA_WINDOWS_CHROME)->isDesktop());
        $this->assertFalse($this->agent(self::UA_IPHONE)->isDesktop());
        $this->assertFalse($this->agent(self::UA_GOOGLEBOT)->isDesktop());
    }

    public function testIsPhone(): void
    {
        $this->assertTrue($this->agent(self::UA_IPHONE)->isPhone());
        $this->assertTrue($this->agent(self::UA_ANDROID_PHONE)->isPhone());
        $this->assertFalse($this->agent(self::UA_IPAD)->isPhone());
        $this->assertFalse($this->agent(self::UA_WINDOWS_CHROME)->isPhone());
    }

    public function testIsRobotAndRobotName(): void
    {
        $bot = $this->agent(self::UA_GOOGLEBOT);
        $this->assertTrue($bot->isRobot());
        $this->assertSame('Googlebot', $bot->robot());

        $human = $this->agent(self::UA_WINDOWS_CHROME);
        $this->assertFalse($human->isRobot());
        $this->assertFalse($human->robot());
    }

    public function testMagicIsMethod(): void
    {
        $this->assertTrue($this->agent(self::UA_ANDROID_PHONE)->isAndroidOS());
        $this->assertTrue($this->agent(self::UA_MACOS_SAFARI)->isSafari());
        $this->assertFalse($this->agent(self::UA_WINDOWS_CHROME)->isAndroidOS());
    }

    public function testIsRuleName(): void
    {
        $this->assertTrue($this->agent(self::UA_IPHONE)->is('iPhone'));
        $this->assertTrue($this->agent(self::UA_WINDOWS_CHROME)->is('Windows'));
        $this->assertFalse($this->agent(self::UA_WINDOWS_CHROME)->is('iPhone'));
    }

    public function testVersion(): void
    {
        $agent = $this->agent(self::UA_WINDOWS_EDGE);
        $this->assertSame('124.0.0.0', $agent->version('Edge'));
        $this->assertSame(124.0, $agent->version('Edge', Agent::VERSION_TYPE_FLOAT));

        $this->assertSame('14', $this->agent(self::UA_ANDROID_PHONE)->version('AndroidOS'));
        $this->assertFalse($this->agent(self::UA_WINDOWS_CHROME)->version('AndroidOS'));
    }

    public function testLanguagesSortedByPriority(): void
    {
        $agent = new Agent(config: ['autoInitOfHttpHeaders' => false]);
        $agent->setHttpHeaders([
            'HTTP_USER_AGENT' => self::UA_WINDOWS_CHROME,
            'HTTP_ACCEPT_LANGUAGE' => 'zh-CN,zh;q=0.9,en-US;q=0.8,en;q=0.7',
        ]);

        $this->assertSame(['zh-cn', 'zh', 'en-us', 'en'], $agent->languages());
    }

    public function testLanguagesReturnsEmptyArrayWithoutHeader(): void
    {
        $this->assertSame([], $this->agent(self::UA_WINDOWS_CHROME)->languages());
    }

    public function testLanguagesAcceptsExplicitHeaderValue(): void
    {
        $agent = $this->agent(self::UA_WINDOWS_CHROME);

        $this->assertSame(['nl-nl', 'nl', 'en-us'], $agent->languages('nl-nl,nl;q=0.9,en-us;q=0.5'));
    }

    public function testUnknownUserAgentReturnsFalse(): void
    {
        $agent = $this->agent('SomeCompletelyUnknownClientString');

        $this->assertFalse($agent->browser());
        $this->assertFalse($agent->platform());
        $this->assertFalse($agent->device());
    }

    public function testDetectionRulesExtendedContainsDesktopAndAdditionalRules(): void
    {
        $rules = Agent::getDetectionRulesExtended();

        $this->assertArrayHasKey('Macintosh', $rules);
        $this->assertArrayHasKey('Windows', $rules);
        $this->assertArrayHasKey('Edge', $rules);
        $this->assertArrayHasKey('iPhone', $rules);
    }

    public function testExtendedRulesDoNotAffectMobileDetection(): void
    {
        // 桌面 UA 会命中扩展规则里的 Windows，但不应被判定为移动设备。
        $agent = $this->agent(self::UA_WINDOWS_CHROME);

        $this->assertTrue($agent->is('Windows'));
        $this->assertFalse($agent->isMobile());
        $this->assertFalse($agent->isTablet());
    }
}
