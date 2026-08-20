<?php

declare(strict_types=1);

namespace Larva\Agent;

use Detection\Exception\MobileDetectException;
use Detection\MobileDetect;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

/**
 * 桌面端 / 移动端 User-Agent 解析器。
 *
 * 在 Mobile Detect 的基础上补充了桌面设备、桌面操作系统与桌面浏览器的识别规则，
 * 并通过 CrawlerDetect 提供爬虫识别能力。
 */
class Agent extends MobileDetect
{
    /**
     * 桌面设备规则。
     *
     * @var array<string, string>
     */
    protected static array $desktopDevices = [
        'Macintosh' => 'Macintosh',
    ];

    /**
     * 补充的操作系统规则。
     *
     * @var array<string, string>
     */
    protected static array $additionalOperatingSystems = [
        'Windows' => 'Windows',
        'Windows NT' => 'Windows NT',
        'OS X' => 'Mac OS X',
        'Debian' => 'Debian',
        'Ubuntu' => 'Ubuntu',
        'Macintosh' => 'PPC',
        'OpenBSD' => 'OpenBSD',
        'Linux' => 'Linux',
        'ChromeOS' => 'CrOS',
    ];

    /**
     * 补充的浏览器规则。顺序即匹配优先级，越靠前越先匹配。
     *
     * @var array<string, string>
     */
    protected static array $additionalBrowsers = [
        'Opera Mini' => 'Opera Mini',
        'Opera' => 'Opera|OPR',
        'Edge' => 'Edge|Edg',
        'Coc Coc' => 'coc_coc_browser',
        'UCBrowser' => 'UCBrowser',
        'Vivaldi' => 'Vivaldi',
        'Chrome' => 'Chrome',
        'Firefox' => 'Firefox',
        'Safari' => 'Safari',
        'IE' => 'MSIE|IEMobile|MSIEMobile|Trident/[.0-9]+',
        'Netscape' => 'Netscape',
        'Mozilla' => 'Mozilla',
    ];

    /**
     * 补充的版本号提取规则。
     *
     * @var array<string, string|array<int, string>>
     */
    protected static array $additionalProperties = [
        // 操作系统
        'Windows' => 'Windows NT [VER]',
        'Windows NT' => 'Windows NT [VER]',
        'OS X' => 'OS X [VER]',
        'BlackBerryOS' => ['BlackBerry[\w]+/[VER]', 'BlackBerry.*Version/[VER]', 'Version/[VER]'],
        'AndroidOS' => 'Android [VER]',
        'ChromeOS' => 'CrOS x86_64 [VER]',

        // 浏览器
        'Opera Mini' => 'Opera Mini/[VER]',
        'Opera' => [' OPR/[VER]', 'Opera Mini/[VER]', 'Version/[VER]', 'Opera [VER]'],
        'Netscape' => 'Netscape/[VER]',
        'Mozilla' => 'rv:[VER]',
        'IE' => ['IEMobile/[VER];', 'IEMobile [VER]', 'MSIE [VER];', 'rv:[VER]'],
        'Edge' => ['Edge/[VER]', 'Edg/[VER]'],
        'Vivaldi' => 'Vivaldi/[VER]',
        'Coc Coc' => 'coc_coc_browser/[VER]',
    ];

    /**
     * 共享的爬虫检测器实例。
     */
    protected static ?CrawlerDetect $crawlerDetect = null;

    /**
     * 获取扩展检测规则，在 Mobile Detect 原生规则之外补充桌面设备、桌面操作系统与桌面浏览器。
     *
     * 该规则集仅用于 is() / is*() 的显式特性判断，不参与 isMobile() / isTablet()，
     * 否则桌面 UA 会因命中 Windows、Linux 等规则被误判为移动设备。
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getDetectionRulesExtended(): array
    {
        static $rules;

        return $rules ??= static::mergeRules(
            static::$desktopDevices,
            static::$phoneDevices,
            static::$tabletDevices,
            static::$operatingSystems,
            static::$additionalOperatingSystems,
            static::$browsers,
            static::$additionalBrowsers,
        );
    }

    /**
     * 获取爬虫检测器。
     */
    public function getCrawlerDetect(): CrawlerDetect
    {
        return static::$crawlerDetect ??= new CrawlerDetect();
    }

    /**
     * 获取浏览器规则，补充规则优先。
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getBrowsers(): array
    {
        return static::mergeRules(
            static::$additionalBrowsers,
            static::$browsers,
        );
    }

    /**
     * 获取操作系统规则。
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getOperatingSystems(): array
    {
        return static::mergeRules(
            static::$operatingSystems,
            static::$additionalOperatingSystems,
        );
    }

    /**
     * 获取平台规则，等同于 getOperatingSystems()。
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getPlatforms(): array
    {
        return static::getOperatingSystems();
    }

    /**
     * 获取桌面设备规则。
     *
     * @return array<string, string>
     */
    public static function getDesktopDevices(): array
    {
        return static::$desktopDevices;
    }

    /**
     * 获取版本号提取规则。
     *
     * @return array<string, string|array<int, string>>
     */
    public static function getProperties(): array
    {
        return static::mergeRules(
            static::$additionalProperties,
            static::$properties,
        );
    }

    /**
     * 解析浏览器可接受的语言列表，按优先级从高到低排序。
     *
     * @param string|null $acceptLanguage 为 null 时读取当前请求的 Accept-Language 头
     * @return array<int, string>
     */
    public function languages(?string $acceptLanguage = null): array
    {
        $acceptLanguage ??= $this->getHttpHeader('HTTP_ACCEPT_LANGUAGE');

        if (empty($acceptLanguage)) {
            return [];
        }

        $languages = [];

        foreach (explode(',', $acceptLanguage) as $piece) {
            $parts = explode(';', $piece);
            $language = strtolower(trim($parts[0]));

            if ($language === '') {
                continue;
            }

            $languages[$language] = empty($parts[1])
                ? 1.0
                : (float) str_replace('q=', '', trim($parts[1]));
        }

        // 按优先级降序排列，保持相同优先级的原始顺序。
        arsort($languages);

        return array_keys($languages);
    }

    /**
     * 获取浏览器名称，未识别时返回 false。
     */
    public function browser(?string $userAgent = null): string|false
    {
        return $this->findDetectionRulesAgainstUserAgent(static::getBrowsers(), $userAgent);
    }

    /**
     * 获取操作系统名称，未识别时返回 false。
     */
    public function platform(?string $userAgent = null): string|false
    {
        return $this->findDetectionRulesAgainstUserAgent(static::getPlatforms(), $userAgent);
    }

    /**
     * 获取设备名称，未识别时返回 false。
     */
    public function device(?string $userAgent = null): string|false
    {
        $rules = static::mergeRules(
            static::getDesktopDevices(),
            static::getPhoneDevices(),
            static::getTabletDevices(),
        );

        return $this->findDetectionRulesAgainstUserAgent($rules, $userAgent);
    }

    /**
     * 判断是否为桌面设备，即非移动设备、非平板且非爬虫。
     *
     * @throws MobileDetectException 当未设置 User-Agent 时抛出
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile() && !$this->isTablet() && !$this->isRobot();
    }

    /**
     * 判断是否为手机，即移动设备且非平板。
     *
     * @throws MobileDetectException 当未设置 User-Agent 时抛出
     */
    public function isPhone(): bool
    {
        return $this->isMobile() && !$this->isTablet();
    }

    /**
     * 获取爬虫名称，非爬虫时返回 false。
     */
    public function robot(?string $userAgent = null): string|false
    {
        $crawlerDetect = $this->getCrawlerDetect();

        if (!$crawlerDetect->isCrawler($userAgent ?? $this->getUserAgent())) {
            return false;
        }

        $matches = $crawlerDetect->getMatches();

        return is_string($matches) && $matches !== '' ? ucfirst($matches) : false;
    }

    /**
     * 判断是否为爬虫。
     */
    public function isRobot(?string $userAgent = null): bool
    {
        return $this->getCrawlerDetect()->isCrawler($userAgent ?? $this->getUserAgent());
    }

    /**
     * 获取设备类型：desktop、phone、tablet、robot 之一。
     *
     * @throws MobileDetectException 当未设置 User-Agent 时抛出
     */
    public function deviceType(): string
    {
        if ($this->isRobot()) {
            return 'robot';
        }

        if ($this->isTablet()) {
            return 'tablet';
        }

        if ($this->isMobile()) {
            return 'phone';
        }

        return 'desktop';
    }

    /**
     * 使用扩展规则集匹配指定规则名，使 is('Windows')、isSafari() 等桌面特性判断可用。
     */
    protected function matchUserAgentWithRule(string $ruleName): bool
    {
        $rules = array_change_key_case(static::getDetectionRulesExtended());
        $regex = $rules[strtolower($ruleName)] ?? null;

        if (empty($regex)) {
            return false;
        }

        return $this->match(is_array($regex) ? implode('|', $regex) : $regex, (string) $this->getUserAgent());
    }

    /**
     * 在给定规则中查找第一个匹配 User-Agent 的规则名。
     *
     * @param array<string, string|array<int, string>> $rules
     */
    protected function findDetectionRulesAgainstUserAgent(array $rules, ?string $userAgent = null): string|false
    {
        $userAgent ??= $this->getUserAgent();

        if ($userAgent === null || $userAgent === '') {
            return false;
        }

        foreach ($rules as $key => $regex) {
            if (empty($regex)) {
                continue;
            }

            if (is_array($regex)) {
                $regex = implode('|', $regex);
            }

            if ($this->match($regex, $userAgent)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * 合并多组规则，同名规则的正则以 | 连接。
     *
     * @param array<string, string|array<int, string>> ...$all
     * @return array<string, string|array<int, string>>
     */
    protected static function mergeRules(array ...$all): array
    {
        $merged = [];

        foreach ($all as $rules) {
            foreach ($rules as $key => $value) {
                if (empty($merged[$key])) {
                    $merged[$key] = $value;
                } elseif (is_array($merged[$key])) {
                    $merged[$key][] = $value;
                } else {
                    $merged[$key] .= '|' . (is_array($value) ? implode('|', $value) : $value);
                }
            }
        }

        return $merged;
    }
}
