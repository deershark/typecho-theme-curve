<?php
/**
 * Curve for Typecho
 *
 * @package Curve
 * @author 鹿形鱼
 * @version 0.1.0
 * @link https://github.com/deershark/typecho-theme-curve
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

require_once __DIR__ . '/inc/helpers.php';

/** 判断是否为单个 Emoji（允许变体、肤色、国旗和 ZWJ 组合）。 */
function curve_is_single_emoji($value)
{
    $value = trim((string) $value);
    if ($value === '') return true;

    $emojiPattern = '/^(?:\p{Regional_Indicator}{2}|(?:\p{Extended_Pictographic}(?:\x{FE0F}|\x{FE0E})?(?:\p{Emoji_Modifier})?(?:\x{200D}\p{Extended_Pictographic}(?:\x{FE0F}|\x{FE0E})?(?:\p{Emoji_Modifier})?)*)|(?:[0-9#*]\x{FE0F}?\x{20E3}))$/u';
    $matched = @preg_match($emojiPattern, $value);
    if ($matched !== false) {
        return $matched === 1;
    }

    /* Older PCRE builds do not know the Extended_Pictographic property.
     * Keep the same single-emoji rule with common Unicode ranges as a fallback. */
    $emojiBase = '(?:[\x{1F000}-\x{1FAFF}]|[\x{2300}-\x{23FF}]|[\x{2600}-\x{27BF}]|[\x{2B00}-\x{2BFF}]|[\x{3030}-\x{303D}]|[\x{3297}-\x{3299}]|[\x{00A9}\x{00AE}\x{203C}\x{2049}\x{2122}\x{2139}])';
    $fallbackPattern = '/^(?:[\x{1F1E6}-\x{1F1FF}]{2}|' . $emojiBase . '(?:[\x{FE0F}\x{FE0E}])?(?:[\x{1F3FB}-\x{1F3FF}])?(?:\x{200D}' . $emojiBase . '(?:[\x{FE0F}\x{FE0E}])?(?:[\x{1F3FB}-\x{1F3FF}])?)*|[0-9#*]\x{FE0F}?\x{20E3})$/u';
    return @preg_match($fallbackPattern, $value) === 1;
}

/** 侧栏社交链接只允许选择最多两个社交链接名称。 */
function curve_validate_sidebar_social_links($value)
{
    $names = json_decode((string) $value, true);
    if (!is_array($names)) {
        return false;
    }

    $seen = array();
    if (count($names) > 2) {
        return false;
    }
    foreach ($names as $name) {
        $name = trim((string) $name);
        if ($name === '' || strlen($name) > 100 || isset($seen[$name])) {
            return false;
        }
        $seen[$name] = true;
    }
    return true;
}

/** 主题设置中的 JSON 字段必须是 JSON 数组。 */
function curve_validate_json_array($value)
{
    $decoded = json_decode((string) $value, true);
    return is_array($decoded);
}

/** 文章访问量必须是非负整数。 */
function curve_validate_non_negative_integer($value)
{
    return preg_match('/^\d+$/', trim((string) $value)) === 1;
}

/** 主题设置中的社交链接 JSON。 */
function curve_validate_social_links_json($value)
{
    $links = json_decode((string) $value, true);
    if (!is_array($links)) {
        return false;
    }

    $allowedNames = array('email', 'github', 'telegram', 'bilibili', 'qq', 'twitter', 'home');
    $seenNames = array();
    foreach ($links as $link) {
        $name = is_array($link) && isset($link['name']) ? strtolower(trim((string) $link['name'])) : '';
        if ($name === 'twitter-x') {
            $name = 'twitter';
        }
        if (!is_array($link) || !in_array($name, $allowedNames, true) || isset($seenNames[$name]) || empty($link['url']) || !curve_url_is_allowed($link['url'], false, $name === 'email')) {
            return false;
        }
        $seenNames[$name] = true;
    }
    return true;
}

/** 主题设置中的左上角菜单 JSON。 */
function curve_validate_top_left_menu_json($value)
{
    $items = json_decode((string) $value, true);
    if (!is_array($items)) {
        return false;
    }

    foreach ($items as $category) {
        if (!is_array($category)) {
            return false;
        }
        if (isset($category['items']) && is_array($category['items'])) {
            if (empty($category['name'])) {
                return false;
            }
            foreach ($category['items'] as $item) {
                if (!is_array($item) || empty($item['name']) || empty($item['url']) || !curve_url_is_allowed($item['url'])) {
                    return false;
                }
            }
            continue;
        }
        if (empty($category['group']) || empty($category['name']) || empty($category['url']) || !curve_url_is_allowed($category['url'])) {
            return false;
        }
    }
    return true;
}

/** 主题设置中的默认封面 JSON。 */
function curve_validate_cover_urls_json($value)
{
    $covers = json_decode((string) $value, true);
    if (!is_array($covers)) {
        return false;
    }

    foreach ($covers as $cover) {
        if (!is_string($cover) || trim($cover) === '' || !curve_url_is_allowed($cover)) {
            return false;
        }
    }
    return true;
}

/** 主题后台设置。 */
function themeConfig($form)
{
    /* 基础信息 */
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('siteAuthorName', null, '鱼鱼', _t('博主名称'), _t('显示在侧栏和页脚。')));
    $siteAuthorLink = new Typecho_Widget_Helper_Form_Element_Text('siteAuthorLink', null, '', _t('博主链接'), _t('社交链接为空且填写此项时，显示为侧栏和页脚社交入口；也会作为页脚版权署名的链接。'));
    $siteAuthorLink->addRule('curve_validate_http_url', _t('博主链接必须是 http(s) 地址。'));
    $form->addInput($siteAuthorLink);
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('siteAuthorEmail', null, '', _t('博主邮箱'), _t('社交链接为空且填写此项时，显示为侧栏和页脚 Email 社交入口。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('intro', null, '记录值得分享的技术、想法与生活。', _t('站点简介'), _t('显示在侧栏时钟卡片中，鼠标悬停时替换时钟显示。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'homeSubtitleMode', array('custom' => _t('自定义文本'), 'hitokoto' => _t('一言')), 'custom', _t('首页副标题来源'), _t('控制“你好，欢迎来到……”下面的文本；选择一言后才会请求一言接口。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'homeSubtitle', null, '记录值得分享的技术、想法与生活。', _t('首页副标题文本'), _t('选择“自定义文本”时显示。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'sidebarAuthorDescription', null, '分享技术生活', _t('侧栏作者简介'), _t('显示在右侧时钟卡片的作者名称下方。')
    ));
    $logoUrl = new Typecho_Widget_Helper_Form_Element_Text('logoUrl', null, '', _t('Logo 地址'), _t('用于页面加载动画和页脚头像；留空时使用主题默认 Logo。'));
    $logoUrl->addRule('curve_validate_asset_url', _t('Logo 地址必须是 http(s) 地址或站内绝对路径。'));
    $form->addInput($logoUrl);
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('recordNumber', null, '', _t('备案号'), _t('留空则不显示备案号。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('since', null, '', _t('建站日期'), _t('格式：2020-07-28；用于侧栏站点数据和“关于本站”页面的建站天数。')));

    /* 外观 */
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('accentColor', null, '', _t('强调色'), _t('留空使用主题原色；支持三位或六位十六进制颜色。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'homeTitleFont', array(
            'xiaolai' => _t('小赖字体'),
            'global' => _t('跟随全站字体'),
            'hmos' => _t('HarmonyOS Sans'),
            'vivo' => _t('vivo Sans'),
            'lxgw' => _t('霞鹜文楷'),
        ), 'xiaolai', _t('首页标题字体'), _t('控制首页“你好，欢迎来到……”标题的字体，不影响导航和文章正文。'))
    );
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'defaultFont', array(
            'vivo' => _t('vivo Sans'),
            'hmos' => _t('HarmonyOS Sans'),
            'lxgw' => _t('霞鹜文楷'),
            'xiaolai' => _t('小赖字体'),
        ), 'vivo', _t('全站字体默认值'), _t('仅作为访客首次访问时的默认字体；访客在前台个性化配置中选择后，以浏览器保存的选择为准。'))
    );
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'defaultBanner', array('half' => _t('半屏'), 'full' => _t('全屏')), 'half', _t('Banner 高度默认值'), _t('仅作为访客首次访问时的默认高度；访客在前台个性化配置中选择后，以浏览器保存的选择为准。'))
    );
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'defaultBackground', array('close' => _t('无'), 'patterns' => _t('使用纹理'), 'image' => _t('自定义图片')), 'patterns', _t('背景默认值'), _t('仅作为访客首次访问时的默认背景；选择自定义图片时，请填写下面的图片地址。'))
    );
    $defaultBackgroundUrl = new Typecho_Widget_Helper_Form_Element_Text(
        'defaultBackgroundUrl', null, '', _t('默认背景图片地址'), _t('支持 http(s) 地址或站内绝对路径；仅在背景默认值为“自定义图片”时生效。')
    );
    $defaultBackgroundUrl->addRule('curve_validate_background_url', _t('背景图片地址必须是 http(s) 地址或站内绝对路径。'));
    $form->addInput($defaultBackgroundUrl);
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'fontSource', array('local' => _t('本地字体'), 'cdn' => _t('CDN（cdn.jsdmirror.com）')), 'cdn', _t('字体源'), _t('本地模式使用主题内置字体，不依赖 cdn.jsdmirror.com；CDN 模式使用对应的远程字体。'))
    );
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'coverLayout', array('left' => _t('封面在左'), 'right' => _t('封面在右'), 'both' => _t('交替排列'), 'grid' => _t('双列卡片')), 'both', _t('文章列表封面布局')
    ));
    $defaultCovers = new Typecho_Widget_Helper_Form_Element_Textarea(
        'defaultCovers', null, '[]', _t('默认封面'), _t('使用下方的封面列表编辑器添加图片；文章未设置 cover 时按文章 ID 从列表中分配。')
    );
    $defaultCovers->addRule('curve_validate_cover_urls_json', _t('默认封面配置格式不正确，请检查图片地址。'));
    $form->addInput($defaultCovers);

    /* 导航 */
    $topLeftMenu = new Typecho_Widget_Helper_Form_Element_Textarea(
        'topLeftMenu', null, '[]', _t('左上角菜单'), _t('使用下方的菜单编辑器配置分类、名称、链接和图标。留空则使用主题默认菜单。')
    );
    $topLeftMenu->addRule('curve_validate_top_left_menu_json', _t('左上角菜单配置格式不正确，请检查每一项的名称和链接。'));
    $form->addInput($topLeftMenu);

    /* 社交 */
    $socialLinks = new Typecho_Widget_Helper_Form_Element_Textarea(
        'socialLinks', null, '[]', _t('社交链接'), _t('使用下方的列表编辑器添加平台和链接；平台名称使用固定枚举，图标会根据平台自动匹配。')
    );
    $socialLinks->addRule('curve_validate_social_links_json', _t('社交链接配置格式不正确，请检查名称和链接。'));
    $form->addInput($socialLinks);
    $footerAvatarEmoji = new Typecho_Widget_Helper_Form_Element_Text(
        'footerAvatarEmoji', null, '', _t('页脚头像表情'), _t('显示在页脚头像右下角，例如：👋；留空则不显示。')
    );
    $footerAvatarEmoji->addRule('curve_is_single_emoji', _t('请输入一个有效的 Emoji，不要填写普通文字或多个 Emoji。'));
    $form->addInput($footerAvatarEmoji);
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'footerAvatarMessage', null, '', _t('页脚头像悬浮文案'), _t('鼠标移到头像右下角表情时显示；需同时填写表情才会生效。')
    ));
    $sidebarSocialLinks = new Typecho_Widget_Helper_Form_Element_Textarea(
        'sidebarSocialLinks', null, '[]', _t('侧栏社交链接'), _t('在下方勾选社交链接，最多显示两个；不选择时自动显示社交链接中的前两个。')
    );
    $sidebarSocialLinks->addRule('curve_validate_sidebar_social_links', _t('请从社交链接中选择，最多两个。'));
    $form->addInput($sidebarSocialLinks);

    /* 文章与摘要 */
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text('postSize', null, '8', _t('每页文章数'), _t('控制首页及各类文章归档页的分页数量。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'fakeGptEnable', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('FakeGPT 开关'), _t('控制文章中的 FakeGPT 摘要卡片是否显示。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea(
        'fakeGptClickText', null, '你好，我是 FakeGPT：名字听起来很懂，实际上只负责把作者认真写好的摘要一个字一个字端上来。没有联网、没有偷看正文，也没有在后台煮咖啡；这段内容由作者亲自审核，放心食用。', _t('FakeGPT 点击文案'), _t('点击文章摘要卡片右上角的 FakeGPT 后显示的内容。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select(
        'postLinkTarget', array('self' => _t('本页打开'), 'blank' => _t('新标签页打开')), 'blank', _t('文章正文链接打开方式'), _t('控制文章正文中的 Markdown、HTML 和 LinkCard 链接；文章标题、分类、评论等页面结构链接不受影响。')
    ));
    $reportUrl = new Typecho_Widget_Helper_Form_Element_Text('reportUrl', null, '', _t('投诉反馈地址'), _t('填写后在文章页显示“反馈与投诉”，并加入页脚服务链接；主题不内置反馈页面。'));
    $reportUrl->addRule('curve_validate_http_url', _t('投诉反馈地址必须是 http(s) 地址。'));
    $form->addInput($reportUrl);
    $rewardWechat = new Typecho_Widget_Helper_Form_Element_Text(
        'rewardWechat', null, '', _t('微信收款码地址'), _t('填写图片地址后，在文章页赞赏弹窗中显示微信收款码。')
    );
    $rewardWechat->addRule('curve_validate_asset_url', _t('微信收款码地址必须是 http(s) 地址或站内绝对路径。'));
    $form->addInput($rewardWechat);
    $rewardAlipay = new Typecho_Widget_Helper_Form_Element_Text(
        'rewardAlipay', null, '', _t('支付宝收款码地址'), _t('填写图片地址后，在文章页赞赏弹窗中显示支付宝收款码。')
    );
    $rewardAlipay->addRule('curve_validate_asset_url', _t('支付宝收款码地址必须是 http(s) 地址或站内绝对路径。'));
    $form->addInput($rewardAlipay);
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'travellingsEnable', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('开往按钮'), _t('控制页面右上角的“开往-友链接力”按钮是否显示。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'showToc', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('文章目录'), _t('控制文章页侧栏目录卡片；文章没有二级或三级标题时会自动隐藏。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'showCopyright', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('文章版权卡片'), _t('控制文章页版权卡片的默认显示；单篇文章可通过“版权卡片”字段单独隐藏。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'countdownName', null, '', _t('倒计时名称'), _t('例如：春节；需同时填写倒计时日期，任一项留空则不显示。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Text(
        'countdownDate', null, '', _t('倒计时日期'), _t('格式：2026-01-01；需同时填写倒计时名称，任一项留空则不显示。')
    ));

    /* 评论与文章关系 */
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('commentEnable', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('评论开关'), _t('控制全站评论表单是否显示。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Select('commentFormPosition', array('bottom' => _t('评论最后'), 'top' => _t('评论最前')), 'bottom', _t('评论框位置'), _t('控制评论输入框显示在评论列表之前或之后。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Textarea('commentPlaceholder', null, '友善交流，请遵守当地法律法规。', _t('评论框提示语'), _t('显示在评论内容输入框中。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio('relatedEnable', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('相关推荐'), _t('控制文章页相关推荐卡片是否显示。')));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'commentAuthorShowSensitive', array('1' => _t('显示'), '0' => _t('隐藏')), '0', _t('作者评论/回复显示 IP 和系统信息'), _t('关闭后，作者发布的评论和回复只显示时间，不公开 IP 归属地和系统信息。')
    ));
    $form->addInput(new Typecho_Widget_Helper_Form_Element_Radio(
        'commentLocationEnable', array('1' => _t('开启'), '0' => _t('关闭')), '0', _t('评论 IP 归属地查询'), _t('默认关闭。开启后仅对每个请求中第一条未缓存的公开 IP 查询 ipwho.is，其余未缓存 IP 显示未知地区。')
    ));
}

/** 文章自定义字段。 */
function themeFields($layout)
{
    $layout->addItem(new Typecho_Widget_Helper_Form_Element_Text('cover', null, '', _t('封面图地址'), _t('用于文章列表卡片；文章页正文顶部不显示封面。')));
    $layout->addItem(new Typecho_Widget_Helper_Form_Element_Textarea('description', null, '', _t('文章摘要'), _t('填写后用于文章列表；开启 FakeGPT 时也会在正文顶部以摘要卡片展示，留空时列表由正文自动截取。')));
    $layout->addItem(new Typecho_Widget_Helper_Form_Element_Radio('top', array('0' => _t('否'), '1' => _t('是')), '0', _t('置顶标记'), _t('显示置顶样式，并让文章在首页和首页分类筛选中优先排序。')));
    $layout->addItem(new Typecho_Widget_Helper_Form_Element_Textarea('references', null, '', _t('参考资料'), _t('一行一个，格式：标题|链接。')));
    $layout->addItem(new Typecho_Widget_Helper_Form_Element_Radio('copyright', array('1' => _t('显示'), '0' => _t('隐藏')), '1', _t('版权卡片')));
    $views = new Typecho_Widget_Helper_Form_Element_Text('views', null, '0', _t('访问量'), _t('主题自定义访问量，主题会在访问文章时自动累加；如果数据库的 typecho_contents 表存在 views 列，文章页显示该列与此字段之和。'));
    $views->addRule('curve_validate_non_negative_integer', _t('访问量必须是非负整数。'));
    $layout->addItem($views);
}

/** 首页按 top 自定义字段排序，并保留 Typecho 原生分页查询流程。 */
function curve_archive_query_top_first($archive, $select)
{
    /* 首页分类筛选复用分类归档模板，但仍应沿用首页的置顶规则。 */
    $isHomeCategoryFilter = $archive->is('category')
        && isset($_GET['curve_home_filter'])
        && (string) $_GET['curve_home_filter'] === '1';
    if ($archive->is('index') || $isHomeCategoryFilter) {
        $select->cleanAttribute('order');
        $select->join(
            'table.fields',
            "table.contents.cid = table.fields.cid AND table.fields.name = 'top' AND table.fields.str_value = '1'",
            Typecho_Db::LEFT_JOIN
        );
        /* PostgreSQL 的分类查询可能按 contents.cid 分组，排序字段需要聚合；
         * 首页查询没有分组，不能使用 MAX，否则会把结果聚合成一行。
         * MySQL 保留普通字段排序，避免影响原有分页行为。 */
        $db = Typecho_Db::get();
        $adapterName = method_exists($db, 'getAdapterName') ? strtolower((string) $db->getAdapterName()) : '';
        $isPgsql = strpos($adapterName, 'pgsql') !== false || strpos($adapterName, 'postgres') !== false;
        $hasGroup = method_exists($select, 'getAttribute') && trim((string) $select->getAttribute('group')) !== '';
        if ($isPgsql && $hasGroup) {
            $select->order('COALESCE(MAX(table.fields.cid), 0)', Typecho_Db::SORT_DESC);
        } elseif ($isPgsql) {
            $select->order('COALESCE(table.fields.cid, 0)', Typecho_Db::SORT_DESC);
        } else {
            $select->order('table.fields.cid', Typecho_Db::SORT_DESC);
        }
        $select->order('table.contents.created', Typecho_Db::SORT_DESC);
    }
    Typecho_Db::get()->fetchAll($select, array($archive, 'push'));
}

function themeInit($archive)
{
    $options = Typecho_Widget::widget('Widget_Options');
    $pageSize = (int) curve_option($options, 'postSize', 8);
    $archive->parameter->pageSize = $pageSize > 0 ? $pageSize : 8;

    static $registered = false;
    if (!$registered) {
        Typecho_Plugin::factory('Widget_Archive')->query = 'curve_archive_query_top_first';
        $registered = true;
    }
}

/** 在 Typecho 写文章页面挂载 Curve Markdown 快捷插入工具栏。 */
function curve_admin_editor_assets()
{
    $options = Typecho_Widget::widget('Widget_Options');
    ob_start();
    $options->themeUrl('assets/css/curve-editor.css');
    $styleUrl = trim(ob_get_clean());
    ob_start();
    $options->themeUrl('assets/js/curve-editor.js');
    $scriptUrl = trim(ob_get_clean());
    $styleVersion = @filemtime(__DIR__ . '/assets/css/curve-editor.css');
    $scriptVersion = @filemtime(__DIR__ . '/assets/js/curve-editor.js');

    echo '<link rel="stylesheet" href="' . curve_esc($styleUrl) . '?v=' . (int) $styleVersion . '">';
    echo '<script src="' . curve_esc($scriptUrl) . '?v=' . (int) $scriptVersion . '"></script>';
}

/** 在主题设置页加载 Curve 的分组配置编辑器。 */
function curve_admin_theme_settings_assets()
{
    $requestUri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '';
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
    if (strpos($requestUri, 'options-theme.php') === false && strpos($scriptName, 'options-theme.php') === false) {
        return;
    }

    $options = Typecho_Widget::widget('Widget_Options');
    ob_start();
    $options->themeUrl('assets/css/curve-settings.css');
    $styleUrl = trim(ob_get_clean());
    ob_start();
    $options->themeUrl('assets/js/curve-settings.js');
    $scriptUrl = trim(ob_get_clean());

    echo '<link rel="stylesheet" href="https://cdn2.codesign.qq.com/icons/g5ZpEgx3z4VO6j2/latest/iconfont.css">';
    echo '<link rel="stylesheet" href="' . curve_esc($styleUrl) . '?v=' . (int) @filemtime(__DIR__ . '/assets/css/curve-settings.css') . '">';
    echo '<script src="' . curve_esc($scriptUrl) . '?v=' . (int) @filemtime(__DIR__ . '/assets/js/curve-settings.js') . '"></script>';
}

if (defined('__TYPECHO_ADMIN__')) {
    Typecho_Plugin::factory('admin/write-js.php')->write = 'curve_admin_editor_assets';
    Typecho_Plugin::factory('admin/footer.php')->end = 'curve_admin_theme_settings_assets';
}
