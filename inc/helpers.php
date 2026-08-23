<?php

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

function curve_esc($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function curve_option($options, $name, $default = '')
{
    $value = isset($options->{$name}) ? trim((string) $options->{$name}) : '';
    return $value === '' ? $default : $value;
}

/** 只允许安全的 HTTP(S)、站内路径，以及按需允许的 mailto 地址。 */
function curve_url_is_allowed($value, $allowRelative = false, $allowMailto = false)
{
    $value = trim(htmlspecialchars_decode((string) $value, ENT_QUOTES));
    if ($value === '' || preg_match('/[\x00-\x20\x7f"\'<>\\\\]/u', $value)) {
        return false;
    }

    if ($allowRelative && $value[0] === '/' && substr($value, 0, 2) !== '//') {
        return true;
    }

    if (!filter_var($value, FILTER_VALIDATE_URL)) {
        return false;
    }

    $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
    if ($scheme === 'http' || $scheme === 'https') {
        return true;
    }
    if ($allowMailto && $scheme === 'mailto') {
        $address = preg_replace('/^mailto:/i', '', $value);
        $address = explode('?', $address, 2)[0];
        return filter_var($address, FILTER_VALIDATE_EMAIL) !== false;
    }
    return false;
}

function curve_safe_http_url($value)
{
    $value = trim((string) $value);
    return curve_url_is_allowed($value) ? $value : '';
}

function curve_safe_asset_url($value)
{
    $value = trim((string) $value);
    return curve_url_is_allowed($value, true) ? $value : '';
}

function curve_validate_http_url($value)
{
    $value = trim((string) $value);
    return $value === '' || curve_url_is_allowed($value);
}

function curve_validate_asset_url($value)
{
    $value = trim((string) $value);
    return $value === '' || curve_url_is_allowed($value, true);
}

/** 读取并校验文章正文链接的打开方式。 */
function curve_article_link_target($options)
{
    $target = curve_option($options, 'postLinkTarget', 'blank');
    return in_array($target, array('self', 'blank'), true) ? $target : 'blank';
}

/** 将文章正文中的链接统一为主题设置的打开方式。 */
function curve_apply_article_link_target($content, $target = 'blank')
{
    $target = in_array($target, array('self', 'blank'), true) ? $target : 'blank';
    return preg_replace_callback('/<a\b([^>]*)>/i', function ($match) use ($target) {
        $attributes = (string) $match[1];
        if (preg_match('/\bclass\s*=\s*(["\'])(.*?)\1/i', $attributes, $classMatch)
            && preg_match('/(?:^|\s)header-anchor(?:\s|$)/i', (string) $classMatch[2])) {
            return $match[0];
        }

        $attributes = preg_replace('/\s+target\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $attributes);
        if ($target === 'blank') {
            $attributes = rtrim($attributes) . ' target="_blank"';
            $relCount = 0;
            $attributes = preg_replace_callback('/\s+rel\s*=\s*(["\'])(.*?)\1/i', function ($relMatch) {
                $rel = trim((string) $relMatch[2]);
                if (!preg_match('/(?:^|\s)noopener(?:\s|$)/i', $rel)) {
                    $rel .= ($rel === '' ? '' : ' ') . 'noopener';
                }
                return ' rel="' . curve_esc($rel) . '"';
            }, $attributes, 1, $relCount);
            if ($relCount === 0) {
                $attributes .= ' rel="noopener"';
            }
        }
        return '<a' . $attributes . '>';
    }, (string) $content);
}

/** 校验主题设置中的背景图片地址。 */
function curve_validate_background_url($value)
{
    $value = trim((string) $value);
    return $value === '' || curve_url_is_allowed($value, true);
}

/** 主题支持的前台字体标识。 */
function curve_theme_font_ids()
{
    return array('hmos', 'lxgw', 'vivo', 'xiaolai');
}

/** 读取并校验主题设置中的全站字体默认值。 */
function curve_theme_default_font($options)
{
    $font = curve_option($options, 'defaultFont', 'vivo');
    return in_array($font, curve_theme_font_ids(), true) ? $font : 'vivo';
}

/** 读取并校验主题设置中的 Banner 默认高度。 */
function curve_theme_default_banner($options)
{
    $banner = curve_option($options, 'defaultBanner', 'half');
    return in_array($banner, array('half', 'full'), true) ? $banner : 'half';
}

/** 读取并校验主题设置中的默认背景类型。 */
function curve_theme_default_background($options)
{
    $background = curve_option($options, 'defaultBackground', 'patterns');
    if (!in_array($background, array('close', 'patterns', 'image'), true)) $background = 'patterns';
    if ($background === 'image' && curve_theme_background_url($options) === '') return 'patterns';
    return $background;
}

/** 读取主题设置中的默认背景图片地址。 */
function curve_theme_background_url($options)
{
    $url = curve_option($options, 'defaultBackgroundUrl', '');
    return curve_validate_background_url($url) ? $url : '';
}

/** 仅在默认背景确实为自定义图片时返回图片地址。 */
function curve_theme_default_background_url($options)
{
    return curve_theme_default_background($options) === 'image' ? curve_theme_background_url($options) : '';
}

/** 读取并校验主题设置中的首页标题字体。 */
function curve_theme_home_title_font($options)
{
    $font = curve_option($options, 'homeTitleFont', 'xiaolai');
    $allowed = array('global', 'hmos', 'lxgw', 'vivo', 'xiaolai');
    return in_array($font, $allowed, true) ? $font : 'xiaolai';
}

/** 读取并校验主题设置中的字体源。 */
function curve_theme_font_source($options)
{
    $source = curve_option($options, 'fontSource', 'cdn');
    return in_array($source, array('local', 'cdn'), true) ? $source : 'cdn';
}

/** 将字体标识转换为 CSS 字体栈。 */
function curve_theme_font_stack($font)
{
    $stacks = array(
        'hmos' => '"HarmonyOS Sans SC Web", "HarmonyOS Sans SC", "HarmonyOS Sans", "HarmonyOS_Regular", "Microsoft YaHei", sans-serif',
        'lxgw' => '"LXGW WenKai", sans-serif',
        'vivo' => '"vivo Sans SC Web", "vivo Sans SC", "vivo Sans", "Microsoft YaHei", sans-serif',
        'xiaolai' => '"Xiaolai Welcome", "LXGW WenKai", sans-serif',
        'global' => 'var(--main-font-family)',
    );
    return isset($stacks[$font]) ? $stacks[$font] : $stacks['xiaolai'];
}

/** 获取主题资源的绝对地址。 */
function curve_theme_asset_url($options, $path)
{
    ob_start();
    $options->themeUrl($path);
    return trim(ob_get_clean());
}

/** 生成可放入 CSS url() 的地址。 */
function curve_theme_css_url($url)
{
    return str_replace(array('\\', '"', "\r", "\n"), array('\\\\', '\\"', '', ''), (string) $url);
}

/** 输出前台字体源和首页标题字体配置。 */
function curve_theme_font_config_markup($options)
{
    $source = curve_theme_font_source($options);
    $faces = array();
    if ($source === 'local') {
        $faces[] = array('HarmonyOS Sans SC Web', 400, curve_theme_asset_url($options, 'assets/fonts/harmonyos-sans-sc-regular.ttf'), 'truetype');
        $faces[] = array('vivo Sans SC Web', 400, curve_theme_asset_url($options, 'assets/fonts/vivo-sans-sc-bold.ttf'), 'truetype');
        $faces[] = array('vivo Sans SC Web', 700, curve_theme_asset_url($options, 'assets/fonts/vivo-sans-sc-bold.ttf'), 'truetype');
        $faces[] = array('Xiaolai Welcome', 400, curve_theme_asset_url($options, 'assets/fonts/xiaolai-mono-sc-regular.woff2'), 'woff2');
    } else {
        $faces[] = array('HarmonyOS Sans SC Web', 400, 'https://cdn.jsdmirror.com/gh/SunsetMkt/HarmonyOS_Sans_SC_Webfont_Splitted@ec77417c5ea07f2eb0941252811d9787f3bc32b6/HarmonyOS_Sans_SC/HarmonyOS_SansSC_Regular.ttf', 'truetype');
        $faces[] = array('vivo Sans SC Web', 400, 'https://cdn.jsdmirror.com/gh/DustJadeEcho/vivo-sans-webfont-splitted@718da45685bf27b5fe03779ebe44709f61c723f1/vivo_Sans/vivo_Sans/vivoSans-Bold.ttf', 'truetype');
        $faces[] = array('vivo Sans SC Web', 700, 'https://cdn.jsdmirror.com/gh/DustJadeEcho/vivo-sans-webfont-splitted@718da45685bf27b5fe03779ebe44709f61c723f1/vivo_Sans/vivo_Sans/vivoSans-Bold.ttf', 'truetype');
        $faces[] = array('Xiaolai Welcome', 400, 'https://cdn.jsdmirror.com/gh/kazukokawagawa/chiyupic@main/fonts/xiaolai/XiaolaiMonoSC-Regular.woff2', 'woff2');
    }

    $css = ':root{--curve-banner-title-font-family:' . curve_theme_font_stack(curve_theme_home_title_font($options)) . ';}';
    foreach ($faces as $face) {
        $css .= '@font-face{font-family:"' . $face[0] . '";font-style:normal;font-weight:' . (int) $face[1] . ';font-display:swap;src:url("' . curve_theme_css_url($face[2]) . '") format("' . $face[3] . '");}';
    }

    $markup = '<style id="curve-theme-font-config">' . $css . '</style>';
    if ($source === 'cdn') {
        $lxgwUrl = 'https://cdn.jsdmirror.com/gh/Minngc/lxgw-wenkai-webfonts@a676ddbd89161bdae9e1ace31d27cef0d5d6bb3d/lxgw-wenkai/bold/bold.css';
    } else {
        $lxgwUrl = curve_theme_asset_url($options, 'assets/fonts/lxgw-wenkai/bold.css');
    }
    $markup .= '<link rel="stylesheet" href="' . curve_esc($lxgwUrl) . '">';
    return $markup;
}

/** 解码主题设置中的 JSON 数组。 */
function curve_json_decode($value, $default = array())
{
    if (is_array($value)) {
        return $value;
    }

    $decoded = json_decode((string) $value, true);
    return is_array($decoded) ? $decoded : $default;
}

/** 编码主题设置中的 JSON，保证中文和 URL 可读。 */
function curve_json_encode($value)
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function curve_json_option($options, $name, $default = array())
{
    $value = isset($options->{$name}) ? $options->{$name} : '';
    return curve_json_decode($value, $default);
}

function curve_accent_color($options)
{
    $value = isset($options->accentColor) ? trim((string) $options->accentColor) : '';
    if ($value === '' || strtolower($value) === '#425aef') {
        return '';
    }
    if (!preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{6})$/i', $value)) {
        return '';
    }
    if (strlen($value) === 4) {
        $value = '#' . $value[1] . $value[1] . $value[2] . $value[2] . $value[3] . $value[3];
    }
    return strtolower($value);
}

function curve_site_path($options, $path)
{
    $base = isset($options->siteUrl) ? rtrim((string) $options->siteUrl, '/') : '';
    return $base . '/' . ltrim($path, '/');
}

function curve_page_template_data()
{
    static $pagesData;
    if ($pagesData !== null) {
        return $pagesData;
    }

    $pagesData = array();
    $pages = Typecho_Widget::widget('Widget_Contents_Page_List');
    while ($pages->next()) {
        $template = trim((string) $pages->template);
        if ($template === '') {
            continue;
        }
        ob_start();
        $pages->permalink();
        $permalink = trim(ob_get_clean());
        $title = trim((string) $pages->title);
        if ($permalink !== '' || $title !== '') {
            $pagesData[$template] = array(
                'url' => $permalink,
                'title' => $title,
            );
        }
    }
    return $pagesData;
}

function curve_page_template_urls()
{
    $urls = array();
    foreach (curve_page_template_data() as $template => $pageData) {
        if ($pageData['url'] !== '') {
            $urls[$template] = $pageData['url'];
        }
    }
    return $urls;
}

function curve_page_url($template)
{
    $urls = curve_page_template_urls();
    return isset($urls[$template]) ? $urls[$template] : '';
}

function curve_page_template_title($template, $default = '')
{
    $pagesData = curve_page_template_data();
    if (isset($pagesData[$template]) && $pagesData[$template]['title'] !== '') {
        return $pagesData[$template]['title'];
    }
    return $default;
}

function curve_post_field($post, $name, $default = '')
{
    if (!isset($post->fields) || !isset($post->fields->{$name})) {
        return $default;
    }
    $value = trim((string) $post->fields->{$name});
    return $value === '' ? $default : $value;
}

function curve_lines($value)
{
    $rows = preg_split('/\r\n|\r|\n/', (string) $value);
    return array_values(array_filter(array_map('trim', $rows), 'strlen'));
}

/** 仅允许作为 About 图片使用的 HTTP(S) 或站内绝对路径。 */
function curve_about_asset_url($value, $default = '')
{
    $value = trim((string) $value);
    return curve_url_is_allowed($value, true) ? $value : $default;
}

/** 解析 page-about.php 使用的 curve-about 特殊 Markdown 块。 */
function curve_about_parse_markdown($content)
{
    $data = array(
        'intro' => array('greeting' => '', 'name' => '', 'description' => ''),
        'pursuit' => array(),
        'skills' => array(),
        'career' => array('title' => '', 'image' => '', 'items' => array()),
        'personality' => array('name' => '', 'type' => '', 'image' => '', 'url' => ''),
        'motto' => array(),
        'interest' => array('title' => '', 'description' => '', 'image' => '', 'color' => '#0c0e20'),
        'music' => array('title' => '', 'description' => '', 'image' => '', 'color' => '#7b3c25'),
        'stats' => array('enabled' => '1', 'image' => ''),
        'info' => array('location' => '', 'locationImage' => '', 'birthYear' => '', 'occupation' => ''),
        'story' => array('title' => '', 'paragraphs' => array()),
    );
    $sectionMap = array(
        '介绍' => 'intro', '个人介绍' => 'intro',
        '追求' => 'pursuit',
        '技能' => 'skills',
        '生涯' => 'career',
        '性格' => 'personality',
        '座右铭' => 'motto',
        '关注偏好' => 'interest',
        '音乐偏好' => 'music',
        '数据' => 'stats', '站点数据' => 'stats',
        '信息' => 'info', '个人信息' => 'info',
        '心路历程' => 'story',
    );
    $sectionLabels = array(
        'intro' => '介绍', 'pursuit' => '追求', 'skills' => '技能', 'career' => '生涯',
        'personality' => '性格', 'motto' => '座右铭', 'interest' => '关注偏好', 'music' => '音乐偏好',
        'stats' => '数据', 'info' => '信息', 'story' => '心路历程',
    );
    $propertyMap = array(
        'intro' => array('问候语' => 'greeting', '名称' => 'name', '简介' => 'description'),
        'career' => array('标题' => 'title', '图片' => 'image'),
        'personality' => array('名称' => 'name', '类型' => 'type', '图片' => 'image', '链接' => 'url'),
        'interest' => array('标题' => 'title', '说明' => 'description', '图片' => 'image', '颜色' => 'color'),
        'music' => array('标题' => 'title', '说明' => 'description', '图片' => 'image', '颜色' => 'color'),
        'stats' => array('开启' => 'enabled', '图片' => 'image'),
        'info' => array('所在地' => 'location', '所在地图片' => 'locationImage', '出生年份' => 'birthYear', '当前职业' => 'occupation'),
        'story' => array('标题' => 'title', '段落' => 'paragraphs'),
    );
    $current = '';
    $invalid = false;
    $errors = array();
    $sections = array();
    $addError = function ($lineNumber, $message) use (&$errors) {
        $prefix = $lineNumber > 0 ? '第' . $lineNumber . '行：' : '';
        $errors[] = $prefix . $message;
    };
    $lines = preg_split('/\r\n|\r|\n/', (string) $content);
    foreach ($lines as $lineIndex => $line) {
        $lineNumber = $lineIndex + 1;
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        if (preg_match('/^(#{1,6})\s+(.+?)\s*#*$/u', $line, $match)) {
            $heading = trim($match[2]);
            $heading = preg_replace('/\s+/u', '', $heading);
            if (strlen($match[1]) === 1 && !isset($sectionMap[$heading])) {
                continue;
            }
            if (!isset($sectionMap[$heading])) {
                $current = '';
                $invalid = true;
                $addError($lineNumber, '无法识别的区块“' . $heading . '”，请使用教程中的区块名称。');
                continue;
            }
            $current = $sectionMap[$heading];
            $sections[$current] = true;
            continue;
        }
        if ($current === '') {
            $invalid = true;
            $addError($lineNumber, '内容不属于任何有效区块，请检查上方的 ## 区块标题。');
            continue;
        }
        $isList = preg_match('/^[-*+]\s+(.+)$/u', $line, $listMatch);
        $valueLine = $isList ? trim($listMatch[1]) : $line;
        if (in_array($current, array('pursuit', 'motto'), true)) {
            if (!$isList || $valueLine === '') {
                $invalid = true;
                $addError($lineNumber, '“' . ($current === 'pursuit' ? '追求' : '座右铭') . '”区块需要使用“- 内容”格式。');
                continue;
            }
            $data[$current][] = $valueLine;
            continue;
        }
        if ($current === 'skills') {
            if (!$isList) {
                $invalid = true;
                $addError($lineNumber, '技能需要使用“- 名称 | 颜色 | 图标名 | 链接”格式。');
                continue;
            }
            $parts = array_map('trim', explode('|', $valueLine, 4));
            if (count($parts) < 4 || $parts[0] === '' || !preg_match('/^#[0-9a-f]{3,6}$/i', $parts[1]) || !preg_match('/^[a-z0-9_-]+$/i', $parts[2]) || !preg_match('/^https?:\/\//i', $parts[3])) {
                $invalid = true;
                $addError($lineNumber, '技能格式无效，颜色应为十六进制值，图标名只能包含字母、数字、下划线或短横线，链接必须是 HTTP(S)。');
                continue;
            }
            $data['skills'][] = array('name' => $parts[0], 'color' => strtolower($parts[1]), 'icon' => $parts[2], 'url' => $parts[3]);
            continue;
        }
        if ($current === 'career') {
            if (!$isList) {
                $parts = array_map('trim', explode('|', $valueLine, 2));
                if (count($parts) === 2 && isset($propertyMap['career'][$parts[0]])) {
                    $data['career'][$propertyMap['career'][$parts[0]]] = $parts[1];
                    continue;
                }
                $invalid = true;
                $addError($lineNumber, '生涯属性只能使用“标题 | 内容”或“图片 | 图片地址”。');
                continue;
            }
            $parts = array_map('trim', explode('|', $valueLine, 2));
            if (count($parts) < 2 || $parts[0] === '' || !preg_match('/^#[0-9a-f]{3,6}$/i', $parts[1])) {
                $invalid = true;
                $addError($lineNumber, '生涯经历需要使用“- 内容 | 颜色”格式。');
                continue;
            }
            $data['career']['items'][] = array('text' => $parts[0], 'color' => strtolower($parts[1]));
            continue;
        }
        if ($current === 'story' && $isList && strpos($valueLine, '|') === false) {
            $data['story']['paragraphs'][] = $valueLine;
            continue;
        }
        $parts = array_map('trim', explode('|', $valueLine, 2));
        if (count($parts) < 2) {
            $invalid = true;
            $addError($lineNumber, '请使用“字段 | 内容”格式。');
            continue;
        }
        $key = preg_replace('/\s+/u', '', $parts[0]);
        $value = $parts[1];
        if ($current === 'story' && $key === '段落') {
            if ($value === '') {
                $invalid = true;
                $addError($lineNumber, '心路历程的段落内容不能为空。');
            } else {
                $data['story']['paragraphs'][] = $value;
            }
            continue;
        }
        if (!isset($propertyMap[$current][$key])) {
            $invalid = true;
            $addError($lineNumber, '“' . $sectionLabels[$current] . '”区块不支持字段“' . $key . '”。');
            continue;
        }
        $property = $propertyMap[$current][$key];
        $data[$current][$property] = $value;
    }

    if (trim((string) $content) === '') {
        return array('valid' => false, 'data' => $data, 'sections' => $sections, 'errors' => array('curve-about 配置块为空。'));
    }

    $required = array(
        array('intro', '介绍', !empty($data['intro']['greeting']) && !empty($data['intro']['name']) && !empty($data['intro']['description']), '介绍区块需要填写“问候语、名称、简介”。'),
        array('pursuit', '追求', count($data['pursuit']) > 0, '追求区块至少需要一条列表项。'),
        array('skills', '技能', count($data['skills']) > 0, '技能区块至少需要一条有效技能。'),
        array('career', '生涯', !empty($data['career']['title']) && curve_about_asset_url($data['career']['image']) !== '' && count($data['career']['items']) > 0, '生涯区块需要填写标题、图片和至少一条经历。'),
        array('personality', '性格', !empty($data['personality']['name']) && !empty($data['personality']['type']) && curve_about_asset_url($data['personality']['image']) !== '' && preg_match('/^https?:\/\//i', $data['personality']['url']), '性格区块需要填写名称、类型、图片和详情链接。'),
        array('motto', '座右铭', count($data['motto']) > 0, '座右铭区块至少需要一条列表项。'),
        array('interest', '关注偏好', !empty($data['interest']['title']) && !empty($data['interest']['description']) && curve_about_asset_url($data['interest']['image']) !== '' && preg_match('/^#[0-9a-f]{3,6}$/i', $data['interest']['color']), '关注偏好区块需要填写标题、说明、图片和有效颜色。'),
        array('music', '音乐偏好', !empty($data['music']['title']) && !empty($data['music']['description']) && curve_about_asset_url($data['music']['image']) !== '' && preg_match('/^#[0-9a-f]{3,6}$/i', $data['music']['color']), '音乐偏好区块需要填写标题、说明、图片和有效颜色。'),
        array('stats', '数据', in_array($data['stats']['enabled'], array('0', '1'), true) && ($data['stats']['enabled'] === '0' || (!empty($data['stats']['image']) && curve_about_asset_url($data['stats']['image']) !== '')), '数据区块需要填写“开启 | 0/1”；开启时还需要图片。'),
        array('info', '信息', !empty($data['info']['location']) && curve_about_asset_url($data['info']['locationImage']) !== '' && !empty($data['info']['birthYear']) && !empty($data['info']['occupation']), '信息区块需要填写所在地、所在地图片、出生年份和当前职业。'),
        array('story', '心路历程', !empty($data['story']['title']) && count($data['story']['paragraphs']) > 0, '心路历程区块需要填写标题和至少一段内容。'),
    );
    foreach ($required as $requirement) {
        if (isset($sections[$requirement[0]]) && !$requirement[2]) {
            $invalid = true;
            $addError(0, $requirement[3]);
        }
    }
    if (empty($sections)) {
        $invalid = true;
        $addError(0, '配置块中没有可用区块，请至少填写一个教程中的 ## 区块。');
    }
    return array('valid' => !$invalid, 'data' => array_merge($data, array('sections' => $sections)), 'sections' => $sections, 'errors' => array_values(array_unique($errors)));
}

function curve_link_rows($value, $allowMailto = false)
{
    $items = array();
    $rows = curve_json_decode($value, null);
    if ($rows === null) {
        $rows = array();
        foreach (curve_lines($value) as $row) {
            $parts = array_map('trim', explode('|', $row, 2));
            if (count($parts) === 2) {
                $rows[] = array('name' => $parts[0], 'url' => $parts[1]);
            }
        }
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        $isEmail = preg_match('/(?:email|mail|邮箱)/iu', $name) === 1;
        if ($name !== '' && curve_url_is_allowed($url, false, $allowMailto && $isEmail)) {
            $items[] = array(
                'name' => $name,
                'url' => $url,
                'icon' => isset($row['icon']) ? trim((string) $row['icon']) : '',
            );
        }
    }
    return $items;
}

/** 解析左上角悬浮菜单 JSON。 */
function curve_top_left_menu_rows($value)
{
    $items = array();
    $rows = array();
    foreach (curve_json_decode($value) as $category) {
        if (!is_array($category)) {
            continue;
        }
        if (isset($category['items']) && is_array($category['items'])) {
            $group = isset($category['name']) ? trim((string) $category['name']) : (isset($category['group']) ? trim((string) $category['group']) : '');
            foreach ($category['items'] as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $rows[] = array(
                    'group' => $group,
                    'name' => isset($item['name']) ? $item['name'] : '',
                    'url' => isset($item['url']) ? $item['url'] : '',
                    'icon' => isset($item['icon']) ? $item['icon'] : '',
                );
            }
            continue;
        }
        $rows[] = $category;
    }

    foreach ($rows as $row) {
        if (!is_array($row)) {
            continue;
        }

        $group = isset($row['group']) ? trim((string) $row['group']) : '';
        $name = isset($row['name']) ? trim((string) $row['name']) : '';
        $url = isset($row['url']) ? trim((string) $row['url']) : '';
        if ($group === '' || $name === '' || !curve_url_is_allowed($url)) {
            continue;
        }

        $iconValue = isset($row['icon']) ? trim((string) $row['icon']) : '';
        $iconUrl = '';
        if (curve_url_is_allowed($iconValue)) {
            $iconUrl = $iconValue;
            $icon = '';
        } else {
            $icon = preg_replace('/^icon-/i', '', $iconValue);
            if ($icon === '' || !preg_match('/^[a-z0-9_-]+$/i', $icon)) {
                $icon = 'link';
            }
        }

        $items[] = array(
            'group' => $group,
            'name' => $name,
            'url' => $url,
            'icon' => $icon,
            'iconUrl' => $iconUrl,
        );
    }
    return $items;
}

/** 按后台配置的名称挑选侧栏社交链接；名称必须与社交链接配置完全一致。 */
function curve_pick_social_links($socialLinks, $names, $limit = 2)
{
    $socialLinks = array_values((array) $socialLinks);
    $requestedNames = array();
    foreach (curve_json_decode($names) as $name) {
        $name = trim((string) $name);
        if ($name !== '' && !in_array($name, $requestedNames, true)) {
            $requestedNames[] = $name;
        }
    }
    $selected = array();

    foreach ($requestedNames as $requestedName) {
        foreach ($socialLinks as $socialLink) {
            if ($socialLink['name'] !== $requestedName) {
                continue;
            }
            $alreadySelected = false;
            foreach ($selected as $selectedLink) {
                if ($selectedLink['name'] === $socialLink['name']) {
                    $alreadySelected = true;
                    break;
                }
            }
            if (!$alreadySelected) {
                $selected[] = $socialLink;
            }
            break;
        }
        if (count($selected) >= $limit) {
            break;
        }
    }

    foreach ($socialLinks as $socialLink) {
        if (count($selected) >= $limit) {
            break;
        }
        $alreadySelected = false;
        foreach ($selected as $selectedLink) {
            if ($selectedLink['name'] === $socialLink['name']) {
                $alreadySelected = true;
                break;
            }
        }
        if (!$alreadySelected) {
            $selected[] = $socialLink;
        }
    }

    return empty($requestedNames) || empty($selected) ? array_slice($socialLinks, 0, $limit) : $selected;
}

function curve_social_icon($name)
{
    $name = strtolower((string) $name);
    if (strpos($name, 'github') !== false) return 'github';
    if (strpos($name, 'email') !== false || strpos($name, 'mail') !== false || strpos($name, '邮箱') !== false) return 'email';
    if (strpos($name, 'qq') !== false) return 'qq';
    if (strpos($name, 'telegram') !== false || strpos($name, 'tg') !== false) return 'telegram';
    if (strpos($name, 'twitter') !== false || strpos($name, '推特') !== false || strpos($name, '𝕏') !== false || preg_match('/^x(?:$|[\s_\-.()])/u', $name)) return 'twitter';
    if (strpos($name, 'bilibili') !== false) return 'bilibili';
    if (strpos($name, 'home') !== false || strpos($name, '主页') !== false || strpos($name, '首页') !== false) return 'home';
    return 'link';
}

/** 社交链接图标只按固定平台名称匹配，不再支持自定义图标。 */
function curve_social_icon_for_link($link)
{
    return curve_social_icon(is_array($link) && isset($link['name']) ? $link['name'] : '');
}

/**
 * 解析独立页中的友情链接 Markdown 块。
 *
 * 默认只返回可渲染的分组，保持页脚等旧调用方的兼容性；传入 true
 * 时返回 valid/groups/errors，供友情链接页面显示具体的配置错误。
 */
function curve_parse_friend_markdown($content, $detailed = false)
{
    $groups = array();
    $currentIndex = null;
    $errors = array();
    $lines = preg_split('/\r\n|\r|\n/', (string) $content);
    $addError = function ($lineNumber, $message) use (&$errors) {
        $prefix = $lineNumber > 0 ? '第' . $lineNumber . '行：' : '';
        $errors[] = $prefix . $message;
    };

    foreach ($lines as $lineIndex => $rawLine) {
        $lineNumber = $lineIndex + 1;
        $line = trim($rawLine);
        if ($line === '') {
            continue;
        }

        if (preg_match('/^#{1,6}/u', $line)) {
            if (!preg_match('/^#{1,6}\s+(.+?)\s*$/u', $line, $match)) {
                $currentIndex = null;
                $addError($lineNumber, '分组标题格式无效，请使用“## 分组名称 | 分组说明”。');
                continue;
            }
            $parts = array_map('trim', explode('|', $match[1], 2));
            if ($parts[0] === '') {
                $currentIndex = null;
                $addError($lineNumber, '分组名称不能为空。');
                continue;
            }
            $groups[] = array(
                'typeName' => $parts[0],
                'typeDesc' => isset($parts[1]) && $parts[1] !== '' ? $parts[1] : '与优秀的人和站点同行',
                'typeList' => array(),
                '_line' => $lineNumber,
            );
            $currentIndex = count($groups) - 1;
            continue;
        }

        if (preg_match('/^[-*+]\s+(.+?)\s*$/u', $line, $match)) {
            if ($currentIndex === null) {
                $groups[] = array(
                    'typeName' => '',
                    'typeDesc' => '',
                    'typeList' => array(),
                    '_line' => 0,
                );
                $currentIndex = count($groups) - 1;
            }
            $parts = array_map('trim', explode('|', $match[1], 4));
            if (count($parts) < 2) {
                $addError($lineNumber, '友链格式无效，请使用“- 名称 | 链接 | 头像 | 简介”。');
                continue;
            }
            if ($parts[0] === '') {
                $addError($lineNumber, '友链名称不能为空。');
                continue;
            }
            if (!curve_url_is_allowed($parts[1])) {
                $addError($lineNumber, '友链链接不是有效 URL，请检查链接地址。');
                continue;
            }
            if (isset($parts[2]) && $parts[2] !== '' && !curve_url_is_allowed($parts[2])) {
                $addError($lineNumber, '头像地址不是有效 URL；如果不需要头像，请留空该字段。');
                continue;
            }
            $groups[$currentIndex]['typeList'][] = array(
                'name' => $parts[0],
                'url' => $parts[1],
                'avatar' => isset($parts[2]) && $parts[2] !== '' ? $parts[2] : '',
                'desc' => isset($parts[3]) && $parts[3] !== '' ? $parts[3] : parse_url($parts[1], PHP_URL_HOST),
            );
            continue;
        }

        $addError($lineNumber, '无法识别这行内容，请使用“## 分组标题”或“- 名称 | 链接 | 头像 | 简介”格式。');
    }

    if (trim((string) $content) === '') {
        $errors[] = 'curve-friends 配置块为空，请按下方教程填写至少一个友链。';
    }

    foreach ($groups as $group) {
        if (empty($group['typeList']) && !empty($group['_line'])) {
            $addError($group['_line'], '分组“' . $group['typeName'] . '”至少需要一条格式正确的友链。');
        }
    }

    $groups = array_values(array_filter($groups, function ($group) {
        return !empty($group['typeList']);
    }));
    foreach ($groups as &$group) {
        unset($group['_line']);
    }
    unset($group);

    $result = array(
        'valid' => empty($errors) && !empty($groups),
        'groups' => $groups,
        'errors' => array_values(array_unique($errors)),
    );
    return $detailed ? $result : $groups;
}

/** 将友情链接分组输出为 Curve 卡片列表。 */
function curve_render_friend_groups($groups)
{
    if (empty($groups)) {
        return '<div class="no-data">暂无友链数据</div>';
    }
    $html = '<div class="link-list" id="friends">';
    foreach ($groups as $group) {
        $html .= '<div class="link-type-list">';
        if ($group['typeName'] !== '' || $group['typeDesc'] !== '') {
            $html .= '<div class="title"><h2 class="name"><span class="name-text">' . curve_esc($group['typeName']) . '</span><span class="name-count">（' . count($group['typeList']) . '）</span></h2><span class="tip">' . curve_esc($group['typeDesc']) . '</span></div>';
        }
        $html .= '<div class="all-link">';
        foreach ($group['typeList'] as $friend) {
            $html .= '<a class="link-card s-card" href="' . curve_esc($friend['url']) . '" target="_blank" rel="noopener"><div class="cover">';
            if ($friend['avatar'] !== '') {
                $html .= '<img src="' . curve_esc($friend['avatar']) . '" class="cover-img loaded" alt="' . curve_esc($friend['name']) . '">';
            }
            $html .= '</div><div class="data"><span class="name">' . curve_esc($friend['name']) . '</span><span class="desc">' . curve_esc($friend['desc']) . '</span></div></a>';
        }
        $html .= '</div></div>';
    }
    return $html . '</div>';
}

/** 读取友情链接页中的友链；未配置时返回空数组。 */
function curve_footer_friend_links()
{
    static $friends;
    if ($friends !== null) {
        return $friends;
    }

    $friends = array();
    try {
        $db = Typecho_Db::get();
        $select = $db->select('text')
            ->from('table.contents')
            ->where('type = ?', 'page')
            ->where('status = ?', 'publish')
            ->where('template = ?', 'page-links.php')
            ->limit(1);
        $page = $db->fetchRow($select);
        $source = is_array($page) && isset($page['text']) ? (string) $page['text'] : '';
        if ($source !== '' && preg_match_all('/<!--\s*curve-friends\b(.*?)-->/is', $source, $blocks)) {
            foreach ($blocks[1] as $block) {
                foreach (curve_parse_friend_markdown($block) as $group) {
                    foreach ($group['typeList'] as $friend) {
                        $friends[] = $friend;
                    }
                }
            }
        }
    } catch (Exception $exception) {
        // 友链只影响页脚展示，读取失败时保持为空。
    }

    $unique = array();
    $validFriends = array();
    foreach ($friends as $friend) {
        $url = isset($friend['url']) ? trim((string) $friend['url']) : '';
        if ($url === '' || !curve_url_is_allowed($url) || isset($unique[$url])) {
            continue;
        }
        $unique[$url] = true;
        $validFriends[] = $friend;
    }
    $friends = $validFriends;
    return $friends;
}

function curve_default_covers($options)
{
    $covers = array();
    foreach (curve_json_option($options, 'defaultCovers') as $cover) {
        $cover = trim((string) $cover);
        if ($cover !== '' && curve_url_is_allowed($cover)) {
            $covers[] = $cover;
        }
    }
    return $covers;
}

function curve_post_cover($post, $options)
{
    $cover = curve_post_field($post, 'cover');
    if ($cover !== '') {
        return $cover;
    }
    $covers = curve_default_covers($options);
    if (empty($covers)) {
        return '';
    }
    $index = isset($post->cid) ? abs((int) $post->cid) % count($covers) : 0;
    return $covers[$index];
}

function curve_excerpt($post, $length = 130)
{
    $description = curve_post_field($post, 'description');
    if ($description !== '') {
        return $description;
    }
    ob_start();
    $post->excerpt($length, '…');
    $excerpt = trim(strip_tags(ob_get_clean()));
    /* Typecho may return the original Markdown source here. Keep the
     * automatic summary readable instead of exposing heading/list markers. */
    $excerpt = preg_replace('/!\[[^\]]*\]\([^)]*\)/u', '', $excerpt);
    $excerpt = preg_replace('/\[([^\]]+)\]\([^)]*\)/u', '$1', $excerpt);
    $excerpt = preg_replace('/(^|\s)#{1,6}\s*/u', '$1', $excerpt);
    $excerpt = preg_replace('/^\s*(?:[-*+]|\d+\.)\s+/mu', '', $excerpt);
    $excerpt = preg_replace('/[`*_~]/u', '', $excerpt);
    return trim((string) preg_replace('/\s+/u', ' ', $excerpt));
}

/** 从递归 Markdown 渲染结果中取出正文，避免自定义容器层层套 markdown-body。 */
function curve_markdown_inner_html($content)
{
    $content = trim((string) $content);
    if (preg_match('/^<div class="markdown-body">(.*)<\/div>$/is', $content, $match)) {
        return $match[1];
    }
    return $content;
}

/** 自定义标签允许使用站内绝对路径或 HTTP(S) 地址。 */
function curve_markdown_safe_url($value)
{
    $value = trim(htmlspecialchars_decode((string) $value, ENT_QUOTES));
    return curve_url_is_allowed($value, true) ? $value : '';
}

/** 渲染原 Curve 主题的 LinkCard 组件；不抓取远程站点，避免文章渲染被网络阻塞。 */
function curve_markdown_render_link_card($attributes)
{
    $values = array();
    if (preg_match_all('/([a-z][a-z0-9_-]*)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+))/i', (string) $attributes, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $values[strtolower($match[1])] = isset($match[2]) && $match[2] !== '' ? $match[2] : (isset($match[3]) && $match[3] !== '' ? $match[3] : $match[4]);
        }
    }
    $url = isset($values['url']) ? curve_markdown_safe_url($values['url']) : '';
    if ($url === '') {
        return '';
    }
    $title = isset($values['title']) ? trim(htmlspecialchars_decode($values['title'], ENT_QUOTES)) : '';
    $description = isset($values['desc']) ? trim(htmlspecialchars_decode($values['desc'], ENT_QUOTES)) : '';
    if ($description === '' && isset($values['description'])) {
        $description = trim(htmlspecialchars_decode($values['description'], ENT_QUOTES));
    }
    $icon = isset($values['icon']) ? curve_markdown_safe_url($values['icon']) : '';
    $external = preg_match('/^https?:\/\//i', $url) === 1;
    $linkAttributes = ' href="' . curve_esc($url) . '" class="link-card s-card hover"';
    if ($external) {
        $linkAttributes .= ' target="_blank" rel="noopener"';
    }
    $html = '<a' . $linkAttributes . '>';
    if ($external) {
        $html .= '<span class="link-tip">引用站外地址，请注意甄别链接安全性</span>';
    }
    $html .= '<div class="link-data"><div class="link-icon">';
    if ($icon !== '') {
        $html .= '<img class="link-img" src="' . curve_esc($icon) . '" alt="link-img" loading="lazy">';
    } else {
        $html .= '<i class="iconfont icon-link"></i>';
    }
    $html .= '</div><div class="link-desc"><span class="link-title">' . curve_esc($title !== '' ? $title : '暂无标题') . '</span><span class="link-description">' . curve_esc($description !== '' ? $description : '暂无站点描述') . '</span></div><i class="link-go iconfont icon-up"></i></div></a>';
    return $html;
}

/** 自定义提示块的类型映射，兼容 VitePress 和原主题的命名。 */
function curve_markdown_block_type($type)
{
    $type = strtolower(trim((string) $type));
    $types = array(
        'note' => 'info', 'info' => 'info', 'question' => 'info', 'summary' => 'info',
        'tip' => 'tip', 'hint' => 'tip',
        'warning' => 'warning', 'important' => 'warning', 'caution' => 'warning',
        'danger' => 'danger', 'error' => 'danger',
    );
    return isset($types[$type]) ? $types[$type] : '';
}

/** 渲染单个 Curve Markdown 容器。 */
function curve_markdown_render_extension($type, $params, $body)
{
    $type = strtolower(trim((string) $type));
    $params = trim((string) $params);
    $bodyHtml = curve_markdown_inner_html(curve_render_markdown($body));
    $admonitionType = curve_markdown_block_type($type);
    if ($admonitionType !== '') {
        $defaultTitles = array(
            'info' => 'INFO', 'note' => 'NOTE', 'question' => 'QUESTION', 'summary' => 'SUMMARY',
            'tip' => 'TIP', 'hint' => 'HINT', 'warning' => 'WARNING', 'important' => 'IMPORTANT',
            'caution' => 'CAUTION', 'danger' => 'DANGER', 'error' => 'ERROR',
        );
        $title = $params !== '' ? $params : (isset($defaultTitles[$type]) ? $defaultTitles[$type] : strtoupper($admonitionType));
        return '<div class="' . curve_esc($admonitionType) . ' custom-block"><p class="custom-block-title">' . curve_esc($title) . '</p>' . $bodyHtml . '</div>';
    }
    if ($type === 'details') {
        $open = false;
        if (preg_match('/^(?:open|opened)\b\s*/i', $params)) {
            $open = true;
            $params = trim((string) preg_replace('/^(?:open|opened)\b\s*/i', '', $params));
        }
        $title = $params !== '' ? $params : 'Details';
        return '<details class="details custom-block"' . ($open ? ' open' : '') . '><summary>' . curve_esc($title) . '</summary>' . $bodyHtml . '</details>';
    }
    if ($type === 'timeline') {
        return '<div class="timeline"><span class="timeline-title">' . curve_esc($params !== '' ? $params : 'Timeline') . '</span><div class="timeline-content">' . $bodyHtml . '</div></div>';
    }
    if ($type === 'radio') {
        $checked = preg_match('/^(?:checked|check|true|yes|x|\[x\])$/i', $params) === 1;
        return '<div class="radio"><div class="radio-point' . ($checked ? ' checked' : '') . '"></div>' . $bodyHtml . '</div>';
    }
    if ($type === 'button') {
        $classes = array();
        foreach (preg_split('/\s+/', $params) as $className) {
            if (preg_match('/^[a-z][a-z0-9_-]*$/i', $className)) {
                $classes[] = $className;
            }
        }
        return '<button type="button" class="button' . (!empty($classes) ? ' ' . curve_esc(implode(' ', $classes)) : '') . '">' . $bodyHtml . '</button>';
    }
    if ($type === 'card') {
        return '<div class="card">' . $bodyHtml . '</div>';
    }
    return '';
}

/** 渲染 vitepress-plugin-tabs 的 :::tabs / == label 语法。 */
function curve_markdown_render_tabs($params, $body)
{
    $params = trim((string) $params);
    $lines = preg_split('/\r\n|\r|\n/', (string) $body);
    $tabs = array();
    $current = null;
    foreach ($lines as $line) {
        if (preg_match('/^\s*={2,}\s*(.*?)\s*$/u', $line, $match)) {
            if ($current !== null) {
                $tabs[] = $current;
            }
            $current = array('label' => trim($match[1]), 'body' => array());
            continue;
        }
        if ($current !== null) {
            $current['body'][] = $line;
        }
    }
    if ($current !== null) {
        $tabs[] = $current;
    }
    if (empty($tabs)) {
        return curve_markdown_inner_html(curve_render_markdown($body));
    }

    $sharedKey = '';
    if (preg_match('/(?:^|\s)key:([^\s]+)/i', $params, $match)) {
        $sharedKey = $match[1];
    }
    $variant = preg_match('/(?:^|\s)variant:code(?:\s|$)/i', $params) === 1 ? ' variant-code' : '';
    $instance = substr(sha1($params . "\n" . $body . count($tabs)), 0, 10);
    $keyAttribute = $sharedKey !== '' ? ' data-curve-tabs-key="' . curve_esc($sharedKey) . '"' : '';
    $html = '<div class="plugin-tabs' . $variant . '" data-curve-tabs' . $keyAttribute . '><div class="plugin-tabs--tab-list" role="tablist">';
    foreach ($tabs as $index => $tab) {
        $panelId = 'curve-tab-' . $instance . '-' . $index;
        $html .= '<button type="button" class="plugin-tabs--tab" role="tab" aria-selected="' . ($index === 0 ? 'true' : 'false') . '" aria-controls="' . curve_esc($panelId) . '" data-curve-tab="' . $index . '">' . curve_esc($tab['label']) . '</button>';
    }
    $html .= '</div>';
    foreach ($tabs as $index => $tab) {
        $panelId = 'curve-tab-' . $instance . '-' . $index;
        $panelHtml = curve_markdown_inner_html(curve_render_markdown(implode("\n", $tab['body'])));
        $html .= '<div class="plugin-tabs--content" id="' . curve_esc($panelId) . '" role="tabpanel" data-curve-tab-panel="' . $index . '"' . ($index === 0 ? '' : ' hidden') . '>' . $panelHtml . '</div>';
    }
    return $html . '</div>';
}

/** 展开 Markdown 的引用式图片语法，例如 ![说明][1]。 */
function curve_markdown_expand_image_references($content)
{
    $referencePattern = '/^\s*\[([^\]]+)\]:\s*(?:<([^>\r\n]+)>|(\S+))(?:\s+(?:"([^"]*)"|\'([^\']*)\'|\(([^)]*)\)))?\s*$/mi';
    $references = array();
    if (!preg_match_all($referencePattern, (string) $content, $matches, PREG_SET_ORDER)) {
        return $content;
    }

    foreach ($matches as $match) {
        $id = strtolower(trim((string) $match[1]));
        $id = preg_replace('/\s+/u', ' ', $id);
        $url = isset($match[2]) && $match[2] !== '' ? $match[2] : $match[3];
        $title = isset($match[4]) && $match[4] !== '' ? $match[4] : (isset($match[5]) && $match[5] !== '' ? $match[5] : (isset($match[6]) ? $match[6] : ''));
        if ($id !== '' && $url !== '') {
            $references[$id] = array('url' => $url, 'title' => $title);
        }
    }

    if (empty($references)) {
        return $content;
    }

    $usedReferences = array();
    $content = preg_replace_callback('/!\[([^\]]*)\]\s*\[([^\]]*)\]/u', function ($match) use ($references, &$usedReferences) {
        $id = trim($match[2]) !== '' ? trim($match[2]) : trim($match[1]);
        $id = strtolower(preg_replace('/\s+/u', ' ', $id));
        if ($id === '' || !isset($references[$id])) {
            return $match[0];
        }
        $usedReferences[$id] = true;
        $reference = $references[$id];
        return '![' . $match[1] . '](' . $reference['url'] . ')';
    }, (string) $content);

    if (!empty($usedReferences)) {
        $content = preg_replace_callback($referencePattern, function ($match) use ($usedReferences) {
            $id = strtolower(trim((string) $match[1]));
            $id = preg_replace('/\s+/u', ' ', $id);
            return isset($usedReferences[$id]) ? '' : $match[0];
        }, $content);
    }
    return $content;
}

/**
 * 把原主题的容器和 LinkCard 转成占位符，交给现有 Markdown 渲染器统一处理。
 * 只识别白名单标签，未知的 ::: 内容仍按普通文本输出。
 */
function curve_markdown_prepare_extensions($content, &$extensions)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $content);
    $types = array('tabs', 'details', 'timeline', 'radio', 'button', 'card', 'note', 'info', 'question', 'summary', 'tip', 'hint', 'warning', 'important', 'caution', 'danger', 'error');
    $output = array();
    $lineCount = count($lines);
    for ($lineIndex = 0; $lineIndex < $lineCount; $lineIndex++) {
        $line = $lines[$lineIndex];
        if (!preg_match('/^\s*(:{3,})\s*([a-z][a-z0-9_-]*)(?:\s+(.*?))?\s*$/i', $line, $openMatch) || !in_array(strtolower($openMatch[2]), $types, true)) {
            $output[] = $line;
            continue;
        }
        $markerLength = strlen($openMatch[1]);
        $depth = 1;
        $closeIndex = null;
        $inFence = false;
        for ($scanIndex = $lineIndex + 1; $scanIndex < $lineCount; $scanIndex++) {
            $scanLine = $lines[$scanIndex];
            if (preg_match('/^\s*```/', $scanLine)) {
                $inFence = !$inFence;
                continue;
            }
            if ($inFence) {
                continue;
            }
            if (preg_match('/^\s*(:{3,})\s*([a-z][a-z0-9_-]*)?(?:\s+.*?)?\s*$/i', $scanLine, $nestedOpen) && isset($nestedOpen[2]) && in_array(strtolower($nestedOpen[2]), $types, true) && strlen($nestedOpen[1]) >= $markerLength) {
                $depth++;
                continue;
            }
            if (preg_match('/^\s*:{' . $markerLength . ',}\s*$/', $scanLine)) {
                $depth--;
                if ($depth === 0) {
                    $closeIndex = $scanIndex;
                    break;
                }
            }
        }
        if ($closeIndex === null) {
            $output[] = $line;
            continue;
        }
        $bodyLines = array_slice($lines, $lineIndex + 1, $closeIndex - $lineIndex - 1);
        $type = strtolower($openMatch[2]);
        $params = isset($openMatch[3]) ? trim($openMatch[3]) : '';
        $body = implode("\n", $bodyLines);
        $html = $type === 'tabs' ? curve_markdown_render_tabs($params, $body) : curve_markdown_render_extension($type, $params, $body);
        if ($html !== '') {
            $extensions[] = $html;
            $output[] = '__CURVE_EXTENSION_' . (count($extensions) - 1) . '__';
            $lineIndex = $closeIndex;
            continue;
        }
        $output[] = $line;
    }

    $content = implode("\n", $output);
    $content = preg_replace('/<p[^>]*>\s*(<LinkCard\b.*?>)\s*<\/p>/is', '$1', $content);
    $inFence = false;
    $contentLines = preg_split('/\r\n|\r|\n/', $content);
    foreach ($contentLines as $lineIndex => $line) {
        if (!$inFence && preg_match('/<LinkCard\b/i', $line)) {
            $contentLines[$lineIndex] = preg_replace_callback('/<LinkCard\b([^>]*?)(?:\/\s*>|>\s*<\/LinkCard\s*>)/is', function ($linkMatch) use (&$extensions) {
                $cardHtml = curve_markdown_render_link_card($linkMatch[1]);
                if ($cardHtml === '') {
                    return $linkMatch[0];
                }
                $extensions[] = $cardHtml;
                return '__CURVE_EXTENSION_' . (count($extensions) - 1) . '__';
            }, $line);
        }
        if (preg_match('/^\s*```/', $line)) {
            $inFence = !$inFence;
        }
    }
    return implode("\n", $contentLines);
}

/** 将 Typecho 中保存的 Markdown 正文转换为文章 HTML。 */
function curve_render_markdown($content)
{
    $content = (string) $content;
    if ($content === '') {
        return $content;
    }
    $content = curve_markdown_expand_image_references($content);
    /* Typecho may wrap an unparsed Markdown field in <p> tags. Convert that
     * wrapper back to Markdown before parsing; preserve already-rendered HTML. */
    /* Typecho's Markdown plugin may turn each source line into a paragraph.
     * Unwrap that shape before looking for Curve's block extensions. */
    $content = preg_replace('/<p[^>]*>\s*(<LinkCard\b.*?(?:\/\s*>|>\s*<\/LinkCard\s*>))\s*<\/p>/is', '$1', $content);
    /* A Markdown plugin does not know Curve's ::: / == markers and commonly
     * stores them as paragraphs. Put marker-only paragraphs back on their own
     * lines while leaving the already-rendered paragraphs and code blocks
     * intact. */
    $content = preg_replace_callback('/<p\b[^>]*>\s*((?::{3,}|={2,}|```)[^<]*?)\s*<\/p>/is', function ($match) {
        return "\n" . trim($match[1]) . "\n";
    }, $content);
    /* Some Typecho Markdown plugins keep an entire container in one
     * paragraph and use <br> for its source line breaks. Restore those lines
     * too; ordinary rendered paragraphs are left untouched. */
    $content = preg_replace_callback('/<p\b[^>]*>(.*?)<\/p>/is', function ($match) {
        if (!preg_match('/(?::{3,}|={2,}|```)/', $match[1])) {
            return $match[0];
        }
        $inner = preg_replace('/<br\s*\/?\s*>/i', "\n", $match[1]);
        return "\n" . trim($inner) . "\n";
    }, $content);
    /* Typecho has already rendered fenced code blocks by this point. Do not
     * inspect their contents when deciding whether the whole document is
     * still raw Markdown: shell prompts, Docker comments, and list-looking
     * code lines are all valid text inside <pre>/<code>. */
    $plainContentForDetection = preg_replace('/<(?:pre|code|script|style|textarea)\b[^>]*>.*?<\/(?:pre|code|script|style|textarea)>/is', '', $content);
    $plainContentForDetection = strip_tags((string) $plainContentForDetection);
    $hasExtensionSyntax = preg_match('/(?:^|\n)\s*:{3,}\s*(?:tabs|details|timeline|radio|button|card|note|info|question|summary|tip|hint|warning|important|caution|danger|error)\b/im', $plainContentForDetection)
        || preg_match('/<LinkCard\b/i', $content)
        || preg_match('/^\s*```\s*ad-[a-z]+/im', $plainContentForDetection)
        || preg_match('/<p[^>]*>\s*(?::{3,}|={2,}|```)/i', $content);
    $paragraphWrappedExtension = preg_match('/<p[^>]*>\s*(?::{3,}|={2,}|```)/i', $content)
        && !preg_match('/<(?!\/?(?:p|br)\b)[^>]+>/i', $content);
    $looksLikeWrappedMarkdown = preg_match('/(?:^|\n)\s*(?:#{1,6}\s|```|[-*+]\s|\d+\.\s|>\s|:{3,}\s)/m', $plainContentForDetection)
        && preg_match('/^\s*<p\b/i', $content);
    if ($looksLikeWrappedMarkdown || $paragraphWrappedExtension) {
        $content = preg_replace('/<br\s*\/?>/i', "\n", $content);
        $content = preg_replace('/<\/p>\s*<p[^>]*>/i', "\n\n", $content);
        $content = strip_tags($content);
    } elseif (!$hasExtensionSyntax && preg_match('/<(?:p|h[1-6]|ul|ol|pre|blockquote|div|table|img|details|figure|a|span|strong|em|del|code|input|br)\b/i', $content)) {
        return curve_normalize_markdown_html($content);
    }
    $extensions = array();
    $content = curve_markdown_prepare_extensions($content, $extensions);
    /* When Typecho has already produced HTML around the custom markers, keep
     * that HTML and only splice in the generated Curve components. Sending it
     * through the lightweight Markdown parser would escape every HTML tag. */
    if (!empty($extensions) && preg_match('/<(?:p|h[1-6]|ul|ol|pre|blockquote|div|table|img|details|figure|a|span|strong|em|del|code|input|br)\b/i', $content)) {
        $content = preg_replace_callback('/__CURVE_EXTENSION_(\d+)__/', function ($match) use ($extensions) {
            $index = (int) $match[1];
            return isset($extensions[$index]) ? $extensions[$index] : $match[0];
        }, $content);
        return curve_normalize_markdown_html($content);
    }
    $hasMathSyntax = preg_match('/(?:^|\n)\s*\$\$|\$(?!\$)[^$\n]+\$(?!\$)|\\\((?:.|\n)+?\\\)/u', $content);
    if (!$hasExtensionSyntax && empty($extensions) && !$hasMathSyntax && !preg_match('/^(?:#{1,6}\s|```|>\s|[-*+]\s|\d+\.\s|\s*\|[^\n]*\|\s*$)|(?:\*\*|__|`|!\[|\[[^\]]+\]\()/m', $content)) {
        return curve_normalize_markdown_html('<p>' . nl2br(curve_esc($content)) . '</p>');
    }

    $lines = preg_split('/\r\n|\r|\n/', $content);
    $html = '';
    $paragraph = array();
    $listType = null;
    $inCode = false;
    $codeLanguage = '';
    $codeLines = array();
    $inMath = false;
    $mathLines = array();
    $headingIds = array();

    $flushParagraph = function () use (&$html, &$paragraph) {
        if (!empty($paragraph)) {
            $parts = array_map('trim', $paragraph);
            $html .= '<p>' . implode('<br>', array_map('curve_markdown_inline', $parts)) . '</p>';
            $paragraph = array();
        }
    };
    $closeList = function () use (&$html, &$listType) {
        if ($listType !== null) {
            $html .= '</' . $listType . '>';
            $listType = null;
        }
    };

    for ($lineIndex = 0, $lineCount = count($lines); $lineIndex < $lineCount; $lineIndex++) {
        $line = $lines[$lineIndex];
        if (preg_match('/^__CURVE_EXTENSION_(\d+)__$/', trim($line), $extensionMatch)) {
            $flushParagraph();
            $closeList();
            $extensionIndex = (int) $extensionMatch[1];
            if (isset($extensions[$extensionIndex])) {
                $html .= $extensions[$extensionIndex];
            }
            continue;
        }
        if ($inMath) {
            if (preg_match('/^\s*\$\$\s*$/', $line)) {
                $html .= '<div class="math-block">\\[' . curve_esc(implode("\n", $mathLines)) . '\\]</div>';
                $inMath = false;
                $mathLines = array();
            } else {
                $mathLines[] = $line;
            }
            continue;
        }
        if ($inCode) {
            if (preg_match('/^```\s*$/', trim($line))) {
                if (preg_match('/^ad-([a-z]+)/i', $codeLanguage, $adMatch)) {
                    $adType = strtolower($adMatch[1]);
                    $adTitle = strtoupper($adType);
                    $html .= curve_markdown_render_extension($adType, $adTitle, implode("\n", $codeLines));
                } else {
                    $html .= '<pre><code' . ($codeLanguage !== '' ? ' class="language-' . curve_esc($codeLanguage) . '"' : '') . '>' . curve_esc(implode("\n", $codeLines)) . '</code></pre>';
                }
                $inCode = false;
                $codeLanguage = '';
                $codeLines = array();
            } else {
                $codeLines[] = $line;
            }
            continue;
        }
        if (preg_match('/^\s*\$\$\s*$/', trim($line))) {
            $flushParagraph();
            $closeList();
            $inMath = true;
            $mathLines = array();
            continue;
        }
        if (preg_match('/^\s*\$\$(.*?)\$\$\s*$/u', trim($line), $mathMatch)) {
            $flushParagraph();
            $closeList();
            $html .= '<div class="math-block">\\[' . curve_esc($mathMatch[1]) . '\\]</div>';
            continue;
        }
        if ($lineIndex + 1 < $lineCount && strpos($line, '|') !== false && curve_markdown_table_separator($lines[$lineIndex + 1])) {
            $flushParagraph();
            $closeList();
            $headers = curve_markdown_table_cells($line);
            $html .= '<div class="table-container"><table><thead><tr>';
            foreach ($headers as $header) $html .= '<th>' . curve_markdown_inline($header) . '</th>';
            $html .= '</tr></thead><tbody>';
            $lineIndex++;
            while ($lineIndex + 1 < $lineCount && trim($lines[$lineIndex + 1]) !== '' && strpos($lines[$lineIndex + 1], '|') !== false) {
                $lineIndex++;
                $html .= '<tr>';
                foreach (curve_markdown_table_cells($lines[$lineIndex]) as $cell) $html .= '<td>' . curve_markdown_inline($cell) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</tbody></table></div>';
            continue;
        }
        if (preg_match('/^```\s*([\w+-]*)\s*$/', trim($line), $match)) {
            $flushParagraph();
            $closeList();
            $inCode = true;
            $codeLanguage = $match[1];
            continue;
        }
        if (trim($line) === '') {
            $flushParagraph();
            $closeList();
            continue;
        }
        if (preg_match('/^(#{1,6})\s+(.+?)\s*#*$/', trim($line), $match)) {
            $flushParagraph();
            $closeList();
            $level = strlen($match[1]);
            $text = trim($match[2]);
            $baseId = 'heading-' . substr(sha1($text), 0, 8);
            $id = $baseId;
            $suffix = 2;
            while (isset($headingIds[$id])) {
                $id = $baseId . '-' . $suffix++;
            }
            $headingIds[$id] = true;
            $html .= '<h' . $level . ' id="' . curve_esc($id) . '">' . curve_markdown_inline($text) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^\s*([-*_])(?:\s*\1){2,}\s*$/', $line)) {
            $flushParagraph();
            $closeList();
            $html .= '<hr>';
            continue;
        }
        if (preg_match('/^\s*>\s?(.*)$/', $line, $match)) {
            $flushParagraph();
            $closeList();
            $html .= '<blockquote>' . curve_markdown_inline(trim($match[1])) . '</blockquote>';
            continue;
        }
        if (preg_match('/^\s*[-*+]\s+(.+)$/', $line, $match) || preg_match('/^\s*\d+\.\s+(.+)$/', $line, $match)) {
            $type = preg_match('/^\s*\d+\./', $line) ? 'ol' : 'ul';
            $flushParagraph();
            if ($listType !== $type) {
                $closeList();
                $html .= '<' . $type . '>';
                $listType = $type;
            }
            $itemText = trim($match[1]);
            if (preg_match('/^\[([ xX])\]\s*(.*)$/', $itemText, $taskMatch)) {
                $checked = strtolower($taskMatch[1]) === 'x' ? ' checked' : '';
                $itemText = '<input type="checkbox" disabled' . $checked . '> ' . curve_markdown_inline($taskMatch[2]);
            } else {
                $itemText = curve_markdown_inline($itemText);
            }
            $html .= '<li>' . $itemText . '</li>';
            continue;
        }
        $closeList();
        $paragraph[] = $line;
    }
    if ($inCode) {
        if (preg_match('/^ad-([a-z]+)/i', $codeLanguage, $adMatch)) {
            $html .= curve_markdown_render_extension($adMatch[1], strtoupper($adMatch[1]), implode("\n", $codeLines));
        } else {
            $html .= '<pre><code' . ($codeLanguage !== '' ? ' class="language-' . curve_esc($codeLanguage) . '"' : '') . '>' . curve_esc(implode("\n", $codeLines)) . '</code></pre>';
        }
    }
    if ($inMath) {
        $html .= '<div class="math-block">\\[' . curve_esc(implode("\n", $mathLines)) . '\\]</div>';
    }
    $flushParagraph();
    $closeList();
    $html = preg_replace('/<\/ul>\s*<ul>/', '', $html);
    $html = preg_replace('/<\/ol>\s*<ol>/', '', $html);
    return curve_normalize_markdown_html($html);
}

/** 将 Typecho 直出的代码块、表格补成 Curve 原主题的容器结构。 */
function curve_highlight_code($code, $language = '')
{
    if (strpos($code, 'class="line"') !== false || strpos($code, "class='line'") !== false) {
        return $code;
    }
    $language = strtolower(trim((string) $language));
    $keywords = array(
        'php' => 'and|array|as|break|case|catch|class|const|continue|declare|default|die|do|echo|else|elseif|empty|extends|final|finally|fn|for|foreach|function|global|if|implements|include|instanceof|interface|isset|namespace|new|or|print|private|protected|public|require|return|static|switch|throw|trait|try|unset|use|var|while|xor|yield|true|false|null',
        'javascript' => 'as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|export|extends|false|finally|for|from|function|get|if|import|in|instanceof|let|new|null|of|return|set|static|super|switch|this|throw|true|try|typeof|undefined|var|void|while|with|yield',
        'typescript' => 'as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|false|finally|for|from|function|if|implements|import|in|instanceof|interface| keyof |let|namespace|new|null|number|of|private|protected|public|readonly|return|string|super|switch|this|throw|true|try|type|typeof|undefined|var|void|while|with|yield',
        'css' => 'and|from|important|media|not|or|supports|url',
        'json' => 'true|false|null',
        'bash' => 'case|do|done|elif|else|esac|fi|for|function|if|in|select|then|time|until|while',
    );
    if ($language === 'js') $language = 'javascript';
    if ($language === 'ts') $language = 'typescript';
    if ($language === 'sh' || $language === 'shell' || $language === 'zsh') $language = 'bash';
    if ($language === 'yml' || $language === 'yaml') $language = 'json';
    $keywordPattern = isset($keywords[$language]) ? $keywords[$language] : 'and|break|case|class|const|continue|default|else|false|for|function|if|let|new|null|return|switch|this|throw|true|try|var|while';
    $lines = preg_split('/\r\n|\r|\n/', html_entity_decode((string) $code, ENT_QUOTES, 'UTF-8'));
    $output = array();
    $tokenize = function ($token) use ($keywordPattern) {
        $type = '';
        $light = '';
        $dark = '';
        if ($token !== '' && $token[0] === '<' && substr($token, -1) === '>') {
            $type = 'tag'; $light = '#22863a'; $dark = '#7ee787';
        } elseif ($token !== '' && ($token[0] === '/' || $token[0] === '#')) {
            $type = 'comment'; $light = '#6a737d'; $dark = '#8b949e';
        } elseif ($token !== '' && ($token[0] === '"' || $token[0] === "'" || $token[0] === '`')) {
            $type = 'string'; $light = '#032f62'; $dark = '#a5d6ff';
        } elseif (preg_match('/^\d/', $token)) {
            $type = 'number'; $light = '#005cc5'; $dark = '#79c0ff';
        } elseif (preg_match('/^(?:' . $keywordPattern . ')$/i', $token)) {
            $type = 'keyword'; $light = '#d73a49'; $dark = '#ff7b72';
        } elseif (preg_match('/^(?:console|log|greet|print_r|var_dump|strlen|count|array_map|fetch|setInterval|require|JSON|parseInt|Math)$/', $token)) {
            $type = 'function'; $light = '#6f42c1'; $dark = '#d2a8ff';
        }
        if ($type === '') return curve_esc($token);
        return '<span class="token ' . $type . '" style="--shiki-light:' . $light . ';--shiki-dark:' . $dark . '">' . curve_esc($token) . '</span>';
    };
    foreach ($lines as $line) {
        $pattern = '/<[^>\n]+>|\/\/.*$|#.*$|\/\*.*?\*\/|"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|`(?:\\\\.|[^`\\\\])*`|\b\d+(?:\.\d+)?\b|\b[A-Za-z_$][\w$]*\b/';
        $highlighted = '';
        $offset = 0;
        if (preg_match_all($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[0] as $match) {
                $token = $match[0];
                $position = (int) $match[1];
                $highlighted .= curve_esc(substr($line, $offset, $position - $offset));
                $highlighted .= $tokenize($token);
                $offset = $position + strlen($token);
            }
        }
        $highlighted .= curve_esc(substr($line, $offset));
        $output[] = '<span class="line">' . $highlighted . '</span>';
    }
    return implode("\n", $output);
}

function curve_normalize_markdown_html($content)
{
    $content = preg_replace_callback('/<pre\b([^>]*)>\s*<code\b([^>]*)>(.*?)<\/code>\s*<\/pre>/is', function ($match) {
        $language = '';
        $attributes = (string) ($match[1] ?? '') . ' ' . (string) ($match[2] ?? '');
        if (preg_match('/(?:lang|language)-([a-z0-9_+-]+)/i', $attributes, $languageMatch)) {
            $language = trim($languageMatch[1]);
        }
        if (preg_match('/(?:lang|language)-ad-([a-z0-9_-]+)/i', $attributes, $adMatch)) {
            $adType = strtolower($adMatch[1]);
            $adHtml = curve_markdown_render_extension($adType, strtoupper($adType), html_entity_decode($match[3], ENT_QUOTES, 'UTF-8'));
            if ($adHtml !== '') {
                return $adHtml;
            }
        }
        if (strpos($match[3], 'class="line"') !== false || strpos($match[3], "class='line'") !== false) {
            return $match[0];
        }
        $code = curve_highlight_code($match[3], $language);
        $langLabel = $language !== '' ? '<span class="lang">' . curve_esc($language) . '</span>' : '';
        return '<div class="language-' . curve_esc($language) . '"><button type="button" title="Copy Code" class="copy" data-copy-code aria-label="复制代码"></button>' . $langLabel . '<pre><code>' . $code . '</code></pre></div>';
    }, $content);
    if (strpos($content, 'table-container') === false) {
        $content = preg_replace_callback('/<table\b[^>]*>.*?<\/table>/is', function ($match) {
            return '<div class="table-container">' . $match[0] . '</div>';
        }, $content);
    }
    /* Markdown-it task-list output is not guaranteed when Typecho's Markdown
     * plugin is enabled. Keep the same semantic checkbox when it arrives as
     * a plain `[ ]`/`[x]` list item. */
    $content = preg_replace_callback('/<li([^>]*)>\s*(?:<p>\s*)?\[([ xX])\]\s*(.*?)(?:<\/p>\s*)?<\/li>/is', function ($match) {
        $checked = strtolower($match[2]) === 'x' ? ' checked' : '';
        return '<li' . $match[1] . '><input class="task-list-item-checkbox" type="checkbox" disabled' . $checked . '> ' . $match[3] . '</li>';
    }, $content);
    $content = preg_replace('/<details\b(?![^>]*\bclass=)/i', '<details class="custom-block details"', $content);
    /* Markdown parsers commonly wrap raw <details> blocks in paragraphs and
     * insert a <br> before <summary>. Repair that invalid HTML before the
     * browser reparses it and moves the details element out of place. */
    $content = preg_replace_callback('/<p\b([^>]*)>\s*(<details\b[^>]*>)\s*(?:<br\s*\/?>\s*)?<summary\b([^>]*)>(.*?)<\/summary>\s*<\/p>/is', function ($match) {
        return $match[2] . '<summary' . $match[3] . '>' . $match[4] . '</summary>';
    }, $content);
    $content = preg_replace('/<p\b[^>]*>\s*<\/details>\s*<\/p>/i', '</details>', $content);
    /* Typecho has already built HTML tables by this point, so their cells do
     * not pass through curve_markdown_inline. Convert only visible text
     * nodes and leave code/pre/script content untouched. */
    $protected = array();
    $content = preg_replace_callback('/<(?:pre|code|script|style|textarea)\b[^>]*>.*?<\/(?:pre|code|script|style|textarea)>/is', function ($match) use (&$protected) {
        $token = '__CURVE_PROTECTED_HTML_' . count($protected) . '__';
        $protected[$token] = $match[0];
        return $token;
    }, $content);
    $contentParts = preg_split('/(<[^>]+>)/s', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
    foreach ($contentParts as $partIndex => $part) {
        if ($part === '' || $part[0] === '<' || strpos($part, '__CURVE_PROTECTED_HTML_') !== false) {
            continue;
        }
        $contentParts[$partIndex] = preg_replace_callback('/(?<!\$)\$(?!\$)([^$\n]+?)(?<!\$)\$(?!\$)/u', function ($match) {
            $formula = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            return '<span class="math-inline">\\(' . curve_esc($formula) . '\\)</span>';
        }, $part);
    }
    $content = implode('', $contentParts);
    if (!empty($protected)) {
        $content = strtr($content, $protected);
    }
    $content = preg_replace_callback('/<img\b([^>]*)>/is', 'curve_markdown_normalize_image_tag', $content);
    $headingIndex = 0;
    $content = preg_replace_callback('/<h([1-6])([^>]*)>(.*?)<\/h\1>/is', function ($match) use (&$headingIndex) {
        $headingIndex++;
        $attrs = $match[2];
        $inner = $match[3];
        if (stripos($inner, 'header-anchor') !== false) return $match[0];
        if (preg_match('/\bid=["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
            $id = $idMatch[1];
        } else {
            $id = 'heading-' . substr(sha1(trim(strip_tags($inner)) . '-' . $headingIndex), 0, 8);
            $attrs .= ' id="' . curve_esc($id) . '"';
        }
        return '<h' . $match[1] . $attrs . '><a class="header-anchor" href="#' . curve_esc($id) . '" aria-label="Permalink"></a>' . $inner . '</h' . $match[1] . '>';
    }, $content);
    return preg_match('/class=["\']markdown-body["\']/', $content)
        ? $content
        : '<div class="markdown-body">' . $content . '</div>';
}

/** 将图片的 alt 文本渲染为图片下方的说明文字。 */
function curve_markdown_normalize_image_tag($match)
{
    $attributes = isset($match[1]) ? (string) $match[1] : '';
    if (preg_match('/\bclass\s*=\s*(["\'])(.*?)\1/i', $attributes, $classMatch)) {
        $className = trim((string) $classMatch[2]);
        if (preg_match('/(?:^|\s)(?:post-img|link-img|cover-img)(?:\s|$)/i', $className)) {
            return '<img' . $attributes . '>';
        }
        $attributes = preg_replace('/\bclass\s*=\s*(["\'])(.*?)\1/i', 'class="' . curve_esc(trim($className . ' post-img')) . '"', $attributes, 1);
    } else {
        $attributes = ' class="post-img"' . $attributes;
    }

    if (!preg_match('/\balt\s*=\s*(["\'])(.*?)\1/i', $attributes, $altMatch)) {
        return '<img' . $attributes . '>';
    }
    $alt = trim(htmlspecialchars_decode((string) $altMatch[2], ENT_QUOTES));
    if ($alt === '') {
        return '<img' . $attributes . '>';
    }
    return '<span class="img-fancybox"><img' . $attributes . '><span class="post-img-tip">' . curve_esc($alt) . '</span></span>';
}

/** 渲染 Markdown 图片，并将说明文字放到图片下方。 */
function curve_markdown_render_image($url, $alt = '', $title = '')
{
    $url = trim(htmlspecialchars_decode((string) $url, ENT_QUOTES));
    $alt = htmlspecialchars_decode((string) $alt, ENT_QUOTES);
    $title = htmlspecialchars_decode((string) $title, ENT_QUOTES);
    if ($url === '' || !curve_url_is_allowed($url, true)) {
        return '';
    }
    $attributes = ' class="post-img" src="' . curve_esc($url) . '" alt="' . curve_esc($alt) . '"';
    if ($title !== '') {
        $attributes .= ' title="' . curve_esc($title) . '"';
    }
    $caption = trim($alt) !== '' ? '<span class="post-img-tip">' . curve_esc($alt) . '</span>' : '';
    return '<span class="img-fancybox"><img' . $attributes . '>' . $caption . '</span>';
}

function curve_markdown_inline($text)
{
    $text = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    $mathTokens = array();
    $text = preg_replace_callback('/\\\\\((.+?)\\\\\)|\\\\\[(.+?)\\\\\]|(?<!\$)\$(?!\$)([^$\n]+?)(?<!\$)\$(?!\$)/us', function ($match) use (&$mathTokens) {
        $formula = isset($match[1]) && $match[1] !== '' ? $match[1] : (isset($match[2]) && $match[2] !== '' ? $match[2] : $match[3]);
        $index = count($mathTokens);
        $mathTokens[$index] = $formula;
        return 'CURVEMATHTOKEN' . $index . 'X';
    }, $text);
    $text = preg_replace_callback('/!\[([^\]]*)\]\(((?:https?:\/\/|\/)[^\s)]+)(?:\s+["\']([^"\']*)["\'])?\)/', function ($match) {
        return curve_markdown_render_image($match[2], $match[1], isset($match[3]) ? $match[3] : '');
    }, $text);
    $text = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $text);
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);
    $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
    $text = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $text);
    $text = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $text);
    $text = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '<em>$1</em>', $text);
    $text = preg_replace('/~~([^~]+)~~/', '<del>$1</del>', $text);
    $text = preg_replace_callback('/CURVEMATHTOKEN(\d+)X/', function ($match) use ($mathTokens) {
        $index = (int) $match[1];
        return isset($mathTokens[$index]) ? '<span class="math-inline">\\(' . $mathTokens[$index] . '\\)</span>' : $match[0];
    }, $text);
    return $text;
}

function curve_markdown_table_cells($line)
{
    $line = trim($line);
    $line = trim($line, '|');
    return array_map('trim', explode('|', $line));
}

function curve_markdown_table_separator($line)
{
    $cells = curve_markdown_table_cells($line);
    return count($cells) >= 2 && !array_filter($cells, function ($cell) {
        return !preg_match('/^:?-{3,}:?$/', trim($cell));
    });
}

function curve_reading_time($post)
{
    $text = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $post->content)));
    $count = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
    return max(1, (int) ceil($count / 450));
}

/** 读取当前访客已经看过的文章 ID，限制数量避免 Cookie 无限增长。 */
function curve_views_cookie_ids()
{
    $raw = isset($_COOKIE['curve_viewed_posts']) ? (string) $_COOKIE['curve_viewed_posts'] : '';
    $ids = array();
    foreach (explode(',', $raw) as $value) {
        $id = (int) $value;
        if ($id > 0) {
            $ids[] = $id;
        }
    }
    return array_slice(array_values(array_unique($ids)), -100);
}

/** 获取站点路径，确保主题安装在子目录时 Cookie 也能正常工作。 */
function curve_views_cookie_path()
{
    $options = Typecho_Widget::widget('Widget_Options');
    $siteUrl = isset($options->siteUrl) ? (string) $options->siteUrl : '';
    $path = parse_url($siteUrl, PHP_URL_PATH);
    if (!is_string($path) || $path === '' || $path[0] !== '/') {
        return '/';
    }
    return rtrim($path, '/') . '/';
}

/** 保存一小时内已计数的文章 ID。阅读量统计不依赖登录状态。 */
function curve_views_mark_cookie($cid, $ids)
{
    $ids[] = (int) $cid;
    $value = implode(',', array_slice(array_values(array_unique($ids)), -100));
    if (!headers_sent()) {
        $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        @setcookie('curve_viewed_posts', $value, time() + 3600, curve_views_cookie_path(), '', $secure, true);
    }
    $_COOKIE['curve_viewed_posts'] = $value;
}

/** 从文章自定义字段读取访问量；返回 null 表示字段记录还不存在或读取失败。 */
function curve_views_read($db, $cid)
{
    $select = $db->select('str_value')
        ->from('table.fields')
        ->where('cid = ?', (int) $cid)
        ->where('name = ?', 'views')
        ->limit(1);
    $row = $db->fetchRow($select);
    if (!is_array($row) || !array_key_exists('str_value', $row)) {
        return null;
    }
    return max(0, (int) $row['str_value']);
}

/** 检查 typecho_contents 是否存在原生 views 列；结果仅缓存到当前请求。 */
function curve_contents_views_column_exists($db)
{
    static $exists = null;
    if ($exists !== null) {
        return $exists;
    }

    try {
        $select = $db->select('views')
            ->from('table.contents')
            ->limit(1);
        $db->fetchRow($select);
        $exists = true;
    } catch (Exception $exception) {
        $exists = false;
    }
    return $exists;
}

/** 读取 typecho_contents.views；不存在该列时返回 null。 */
function curve_contents_views_read($db, $cid)
{
    if (!curve_contents_views_column_exists($db)) {
        return null;
    }

    try {
        $select = $db->select('views')
            ->from('table.contents')
            ->where('cid = ?', (int) $cid)
            ->limit(1);
        $row = $db->fetchRow($select);
        if (!is_array($row) || !array_key_exists('views', $row)) {
            return null;
        }
        return max(0, (int) $row['views']);
    } catch (Exception $exception) {
        return null;
    }
}

/** 创建文章访问量字段，首次访问旧文章时也能直接开始计数。 */
function curve_views_ensure_field($db, $cid)
{
    if (curve_views_read($db, $cid) !== null) {
        return true;
    }

    try {
        $insert = $db->insert('table.fields')->rows(array(
            'cid' => (int) $cid,
            'name' => 'views',
            'type' => 'str',
            'str_value' => '0'
        ));
        $db->query($insert, Typecho_Db::WRITE, Typecho_Db::INSERT);
    } catch (Exception $exception) {
        /* 并发请求可能已经插入字段，交给下面的读取确认。 */
    }

    return curve_views_read($db, $cid) !== null;
}

/** 原子增加文章自定义字段中的访问量。 */
function curve_views_increment($db, $cid)
{
    if (!curve_views_ensure_field($db, $cid)) {
        return null;
    }

    $table = $db->getPrefix() . 'fields';
    if (method_exists($db, 'quoteIdentifier')) {
        $table = $db->quoteIdentifier($table);
    } else {
        $adapterName = method_exists($db, 'getAdapterName') ? strtolower((string) $db->getAdapterName()) : '';
        $table = (strpos($adapterName, 'pgsql') !== false || strpos($adapterName, 'postgres') !== false)
            ? '"' . str_replace('"', '""', $table) . '"'
            : '`' . str_replace('`', '``', $table) . '`';
    }

    $adapterName = method_exists($db, 'getAdapterName') ? strtolower((string) $db->getAdapterName()) : '';
    if (strpos($adapterName, 'sqlite') !== false) {
        $expression = "CAST(COALESCE(NULLIF(str_value, ''), '0') AS INTEGER) + 1";
    } elseif (strpos($adapterName, 'pgsql') !== false || strpos($adapterName, 'postgres') !== false) {
        $expression = "CAST(CAST(COALESCE(NULLIF(str_value, ''), '0') AS INTEGER) + 1 AS TEXT)";
    } else {
        $expression = "CAST(COALESCE(NULLIF(str_value, ''), '0') AS UNSIGNED) + 1";
    }

    try {
        $sql = 'UPDATE ' . $table . ' SET str_value = ' . $expression . " WHERE cid = " . (int) $cid . " AND name = 'views'";
        $db->query($sql, Typecho_Db::WRITE, Typecho_Db::UPDATE);
        return curve_views_read($db, $cid);
    } catch (Exception $exception) {
        return null;
    }
}

/** 记录一次文章访问并返回最新阅读量。 */
function curve_record_view($post)
{
    $cid = isset($post->cid) ? (int) $post->cid : 0;
    $fallback = max(0, (int) curve_post_field($post, 'views', '0'));
    if ($cid <= 0) {
        return $fallback;
    }

    $viewedIds = curve_views_cookie_ids();
    $alreadyViewed = in_array($cid, $viewedIds, true);
    $db = Typecho_Db::get();
    $nativeViews = curve_contents_views_read($db, $cid);
    $nativeViews = $nativeViews === null ? 0 : $nativeViews;
    $current = curve_views_read($db, $cid);
    $current = $current === null ? $fallback : $current;
    if ($alreadyViewed) {
        return $nativeViews + $current;
    }

    try {
        $views = curve_views_increment($db, $cid);
        if ($views === null) {
            return $nativeViews + $current;
        }
        curve_views_mark_cookie($cid, $viewedIds);
        return $nativeViews + $views;
    } catch (Exception $exception) {
        return $nativeViews + $current;
    }
}

/** 格式化文章日期，保持与 Curve 原主题一致。 */
function curve_format_timestamp($timestamp)
{
    $timestamp = (int) $timestamp;
    if ($timestamp <= 0) {
        return '';
    }

    $now = time();
    $today = strtotime(date('Y-m-d', $now));
    $yesterday = $today - 86400;
    if ($timestamp >= $yesterday && $timestamp < $today) {
        return '1天前';
    }

    $difference = (int) floor(($today - $timestamp) / 86400);
    if ($difference <= 0) {
        return '今日内';
    }
    if ($difference < 7) {
        return $difference . '天前';
    }

    $year = date('Y', $timestamp);
    if ($year === date('Y', $now)) {
        return date('n/j', $timestamp);
    }
    return date('Y/n/j', $timestamp);
}

function curve_is_enabled($options, $name, $default = true)
{
    $value = curve_option($options, $name, $default ? '1' : '0');
    return $value === '1';
}

function curve_comment_form($archive, $comments, $options, $user, $commentUrl)
{
    $commentAction = Typecho_Widget::widget('Widget_Security')->getTokenUrl($commentUrl);
    $commentMailRequired = !empty($options->commentsRequireMail);
    $commentUrlRequired = !empty($options->commentsRequireUrl);
    $commentPlaceholder = curve_option($options, 'commentPlaceholder', '友善交流，请遵守当地法律法规。');
    ?>
    <div id="<?php $archive->respondId(); ?>" class="respond">
    <div class="cancel-comment-reply"><?php $comments->cancelReply(); ?></div>
    <form method="post" action="<?php echo curve_esc($commentAction); ?>" class="comment-form" id="comment-form">
        <div class="comment-fields">
            <?php if (!$user->hasLogin()): ?>
            <label>昵称<input type="text" name="author" value="<?php $archive->remember('author'); ?>" required></label>
            <label>邮箱<input type="email" name="mail" value="<?php $archive->remember('mail'); ?>"<?php echo $commentMailRequired ? ' required' : ''; ?>></label>
            <label>网址<input type="url" name="url" value="<?php $archive->remember('url'); ?>"<?php echo $commentUrlRequired ? ' required' : ''; ?>></label>
            <?php else: ?>
            <p>已登录为 <?php $user->screenName(); ?></p>
            <?php endif; ?>
        </div>
        <?php if (class_exists('reCAPTCHAv3_Plugin') && is_callable(array('reCAPTCHAv3_Plugin', 'output'))): reCAPTCHAv3_Plugin::output(); endif; ?>
        <textarea name="text" rows="5" placeholder="<?php echo curve_esc($commentPlaceholder); ?>" required><?php $archive->remember('text'); ?></textarea>
        <button type="submit">提交评论</button>
    </form>
    </div>
    <?php
}

function curve_since_days($options)
{
    $since = curve_option($options, 'since');
    if ($since === '' || strtotime($since) === false) {
        return null;
    }
    return max(0, (int) floor((time() - strtotime($since)) / 86400));
}

function curve_page_title($archive)
{
    if ($archive->is('search')) {
        return '搜索：' . $archive->keywords;
    }
    if ($archive->is('category') || $archive->is('tag') || $archive->is('date')) {
        ob_start();
        $archive->archiveTitle('', '', '');
        return trim(strip_tags(ob_get_clean()));
    }
    return '文章归档';
}

/** 输出与 Curve 原主题一致的分页，并附加快速跳转输入框。 */
function curve_page_nav($archive)
{
    ob_start();
    $archive->pageNav(
        '<i class="iconfont icon-page-right"></i><span class="page-text">上页</span>',
        '<span class="page-text">下页</span><i class="iconfont icon-page-right"></i>',
        3,
        '…',
        'textTag='
    );
    $navigation = ob_get_clean();
    if ($navigation === '' || strpos($navigation, '</ol>') === false) {
        echo $navigation;
        return;
    }

    $totalPages = (int) $archive->getTotalPage();
    if ($totalPages > 1) {
        $jump = '<li class="fast-jump" data-fast-jump data-total-pages="' . $totalPages . '" title="快速跳转">'
            . '<input type="text" min="1" max="' . $totalPages . '" inputmode="numeric" aria-label="输入页码">'
            . '<i class="iconfont icon-arrow-right" data-fast-jump-submit></i></li>';
        $navigation = preg_replace('/<\/ol>\s*$/', $jump . '</ol>', $navigation, 1);
    }
    echo $navigation;
}

/** 将评论时间转换为更适合评论区阅读的相对时间。 */
function curve_comment_relative_time($comments)
{
    $created = isset($comments->created) ? (int) $comments->created : 0;
    if ($created <= 0) {
        ob_start();
        $comments->date('U');
        $created = (int) trim(ob_get_clean());
    }

    if ($created <= 0) {
        return '具体时间';
    }

    $diff = time() - $created;
    if ($diff < 0) {
        $diff = 0;
    }
    if ($diff < 60) {
        return '刚刚';
    }
    if ($diff < 3600) {
        return floor($diff / 60) . ' 分钟前';
    }
    if ($diff < 86400) {
        return floor($diff / 3600) . ' 小时前';
    }
    if ($diff < 2592000) {
        return floor($diff / 86400) . ' 天前';
    }

    return date('Y-m-d H:i', $created);
}

/** 从评论 User-Agent 中提取操作系统信息。 */
function curve_comment_client_meta($agent)
{
    $agent = trim((string) $agent);
    if ($agent === '') {
        return '未知系统';
    }

    $platform = '';
    if (preg_match('/HarmonyOS|OpenHarmony/i', $agent)) {
        $platform = 'HarmonyOS';
    } elseif (preg_match('/Android/i', $agent)) {
        $platform = 'Android';
    } elseif (preg_match('/Windows NT/i', $agent)) {
        $platform = 'Windows';
    } elseif (preg_match('/CrOS/i', $agent)) {
        $platform = 'ChromeOS';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $agent)) {
        $platform = 'IOS';
    } elseif (preg_match('/Mac OS X/i', $agent)) {
        $platform = 'MacOS';
    } elseif (preg_match('/Linux/i', $agent)) {
        $platform = 'Linux';
    }

    return $platform !== '' ? $platform : '未知系统';
}

/** 将 IP 归属地格式化为“中国省份 / 中国港澳台 / 其他国家”。 */
function curve_comment_format_location($data)
{
    if (!is_array($data)) {
        return '';
    }

    $code = strtoupper(trim(isset($data['country_code']) ? $data['country_code'] : ''));
    $country = trim(isset($data['country']) ? $data['country'] : '');
    $region = trim(isset($data['region']) ? $data['region'] : '');

    if ($code === 'HK') {
        return '中国香港';
    }
    if ($code === 'MO') {
        return '中国澳门';
    }
    if ($code === 'TW') {
        return '中国台湾';
    }
    if ($code === 'CN') {
        return $region !== '' ? $region : '中国';
    }

    return $country !== '' ? $country : '未知地区';
}

/** 查询并缓存 IP 归属地，缓存 7 天，避免每次刷新评论页都请求外部服务。 */
function curve_comment_location($ip)
{
    $ip = trim((string) $ip);
    if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
        return $ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) ? '本地网络' : '未知地区';
    }

    static $runtimeCache = array();
    static $remoteLookups = 0;
    if (isset($runtimeCache[$ip])) {
        return $runtimeCache[$ip];
    }

    $cacheDir = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ . '/usr/cache/curve-comment-location' : sys_get_temp_dir() . '/curve-comment-location';
    $cacheFile = $cacheDir . '/' . sha1($ip) . '.json';
    if (is_file($cacheFile) && filemtime($cacheFile) > time() - 604800) {
        $cached = json_decode((string) @file_get_contents($cacheFile), true);
        if (is_array($cached) && isset($cached['location'])) {
            return $runtimeCache[$ip] = (string) $cached['location'];
        }
    }

    /* Never let a comment page fan out into one blocking request per comment.
     * The caller must explicitly opt in, and only one uncached IP is looked up
     * during a request; other IPs remain unknown until a later cache warm-up. */
    if ($remoteLookups >= 1) {
        return $runtimeCache[$ip] = '未知地区';
    }
    $remoteLookups++;

    $url = 'https://ipwho.is/' . rawurlencode($ip) . '?lang=zh-CN&fields=success,country,country_code,region,city';
    $body = false;
    if (function_exists('curl_init')) {
        $curl = curl_init($url);
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 1,
            CURLOPT_TIMEOUT => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Curve-Typecho-Theme/1.0',
        ));
        $body = curl_exec($curl);
        curl_close($curl);
    } elseif (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
        $context = stream_context_create(array(
            'http' => array('timeout' => 2, 'ignore_errors' => true),
            'ssl' => array('verify_peer' => true, 'verify_peer_name' => true),
        ));
        $body = @file_get_contents($url, false, $context);
    }

    $data = is_string($body) ? json_decode($body, true) : null;
    $location = is_array($data) && (!isset($data['success']) || $data['success']) ? curve_comment_format_location($data) : '未知地区';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0755, true);
    }
    @file_put_contents($cacheFile, json_encode(array('location' => $location), JSON_UNESCAPED_UNICODE), LOCK_EX);
    return $runtimeCache[$ip] = $location;
}

/** 返回仅当前评论提交者可见的审核状态文案。 */
function curve_comment_status_label($status)
{
    $labels = array(
        'waiting' => '待审核',
        'spam' => '未通过审核',
        'hidden' => '已隐藏',
    );
    $status = strtolower(trim((string) $status));
    return isset($labels[$status]) ? $labels[$status] : '';
}

/** 返回评论审核状态对应的图标。 */
function curve_comment_status_icon($status)
{
    $icons = array(
        'waiting' => 'icon-time',
        'spam' => 'icon-report',
        'hidden' => 'icon-safe',
    );
    $status = strtolower(trim((string) $status));
    return isset($icons[$status]) ? $icons[$status] : 'icon-time';
}

/** Typecho 评论列表回调。Typecho 会自动查找 threadedComments。 */
function threadedComments($comments, $options)
{
    $isAuthor = (int) $comments->authorId > 0 && (int) $comments->authorId === (int) $comments->ownerId;
    $themeOptions = Typecho_Widget::widget('Widget_Options');
    $showAuthorSensitive = curve_is_enabled($themeOptions, 'commentAuthorShowSensitive', false);
    $showCommentLocation = curve_is_enabled($themeOptions, 'commentLocationEnable', false);
    $showSensitiveMeta = !$isAuthor || $showAuthorSensitive;
    $commentAgent = isset($comments->agent) ? $comments->agent : '';
    $commentIp = isset($comments->ip) ? $comments->ip : '';
    $commentStatus = isset($comments->status) ? $comments->status : 'approved';
    $commentStatusLabel = curve_comment_status_label($commentStatus);
    $commentStatusIcon = curve_comment_status_icon($commentStatus);
    $commentTime = isset($comments->created) ? (int) $comments->created : 0;
    if ($commentTime <= 0) {
        ob_start();
        $comments->date('U');
        $commentTime = (int) trim(ob_get_clean());
    }
    $commentIsAbsolute = $commentTime > 0 && time() - $commentTime >= 2592000;
    ?>
    <li id="li-<?php $comments->theId(); ?>" class="comment-item">
        <article id="<?php $comments->theId(); ?>" class="comment-item__body<?php echo $isAuthor ? ' is-author-comment' : ''; ?>">
            <div class="comment-item__avatar"><?php $comments->gravatar(72, ''); ?></div>
            <div class="comment-item__content">
                <header>
                    <span class="comment-item__author<?php echo $isAuthor ? ' is-author' : ''; ?>"><?php $comments->author(); ?><?php if ($isAuthor): ?><span class="comment-author-badge">作者</span><?php endif; ?></span>
                    <?php if ($commentStatusLabel !== ''): ?><span class="comment-author-badge comment-item__status is-pending"><i class="iconfont <?php echo curve_esc($commentStatusIcon); ?>" aria-hidden="true"></i><?php echo curve_esc($commentStatusLabel); ?></span><?php endif; ?>
                    <?php $comments->reply(isset($options->replyWord) ? $options->replyWord : '回复'); ?>
                </header>
                <div class="comment-item__meta">
                    <time data-comment-time="<?php echo $commentTime > 0 ? $commentTime : ''; ?>" data-comment-absolute="<?php echo $commentIsAbsolute ? '1' : '0'; ?>" datetime="<?php echo $commentTime > 0 ? date('c', $commentTime) : ''; ?>" title="<?php echo $commentTime > 0 ? date('Y-m-d H:i', $commentTime) : ''; ?>"><?php echo curve_esc(curve_comment_relative_time($comments)); ?></time>
                    <?php if ($showSensitiveMeta): ?>
                    <?php if ($showCommentLocation): ?><span>IP 归属地: <?php echo curve_esc(curve_comment_location($commentIp)); ?></span><?php endif; ?>
                    <span>系统: <?php echo curve_esc(curve_comment_client_meta($commentAgent)); ?></span>
                    <?php endif; ?>
                </div>
                <div class="comment-item__text"><?php $comments->content(); ?></div>
            </div>
        </article>
        <?php if ($comments->children): ?>
        <div class="children"><?php $comments->threadedComments(); ?></div>
        <?php endif; ?>
    </li>
    <?php
}
