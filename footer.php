<?php if (!defined('__TYPECHO_ROOT_DIR__')) { exit; } ?>
</main>
<?php
$siteUrl = rtrim((string) $this->options->siteUrl, '/');
$archiveUrl = curve_page_url('page-archives.php');
$archiveTitle = curve_page_template_title('page-archives.php', '文章归档');
$categoriesUrl = curve_page_url('page-categories.php');
$categoriesTitle = curve_page_template_title('page-categories.php', '全部分类');
$tagsUrl = curve_page_url('page-tags.php');
$tagsTitle = curve_page_template_title('page-tags.php', '全部标签');
$aboutUrl = curve_page_url('page-about.php');
$aboutTitle = curve_page_template_title('page-about.php', '关于本站');
$linksUrl = curve_page_url('page-links.php');
$linksTitle = curve_page_template_title('page-links.php', '友情链接');
$privacyUrl = curve_page_url('page-privacy.php');
$privacyTitle = curve_page_template_title('page-privacy.php', '隐私协议');
if ($privacyUrl === '') $privacyUrl = curve_safe_http_url(curve_option($this->options, 'privacyUrl'));
$reportUrl = curve_safe_http_url(curve_option($this->options, 'reportUrl'));
$feedUrl = (string) $this->options->feedUrl;
$socialLinks = curve_link_rows(curve_option($this->options, 'socialLinks'), true);
if (empty($socialLinks)) {
    $authorLink = curve_safe_http_url(curve_option($this->options, 'siteAuthorLink'));
    $authorEmail = curve_option($this->options, 'siteAuthorEmail');
    if ($authorLink !== '' && $authorLink !== $siteUrl) $socialLinks[] = array('name' => 'GitHub', 'url' => $authorLink);
    if ($authorEmail !== '') $socialLinks[] = array('name' => 'Email', 'url' => 'mailto:' . $authorEmail);
}
$blogLinks = array(array('name' => '近期文章', 'url' => $siteUrl . '/', 'newTab' => false));
$columnLinks = array();
if ($categoriesUrl !== '') $columnLinks[] = array('name' => $categoriesTitle, 'url' => $categoriesUrl, 'newTab' => false);
if ($tagsUrl !== '') $columnLinks[] = array('name' => $tagsTitle, 'url' => $tagsUrl, 'newTab' => false);
if ($archiveUrl !== '') $columnLinks[] = array('name' => $archiveTitle, 'url' => $archiveUrl, 'newTab' => false);
$blogLinks = array_merge($blogLinks, $columnLinks);
$columnLinks = array();
if ($archiveUrl !== '') $columnLinks[] = array('name' => $archiveTitle, 'url' => $archiveUrl, 'newTab' => false);
if ($categoriesUrl !== '') $columnLinks[] = array('name' => $categoriesTitle, 'url' => $categoriesUrl, 'newTab' => false);
if ($tagsUrl !== '') $columnLinks[] = array('name' => $tagsTitle, 'url' => $tagsUrl, 'newTab' => false);
$serviceLinks = array(array('name' => '站点订阅', 'url' => $feedUrl, 'newTab' => false));
if ($privacyUrl !== '') $serviceLinks[] = array('name' => $privacyTitle, 'url' => $privacyUrl, 'newTab' => false);
if ($reportUrl !== '') $serviceLinks[] = array('name' => '反馈与投诉', 'url' => $reportUrl, 'newTab' => false);
$footerColumns = array(
    array('title' => '博客', 'links' => $blogLinks),
    array('title' => '专栏', 'links' => $columnLinks),
    array('title' => '页面', 'links' => array_filter(array(
        $aboutUrl !== '' ? array('name' => $aboutTitle, 'url' => $aboutUrl, 'newTab' => false) : null,
        $linksUrl !== '' ? array('name' => $linksTitle, 'url' => $linksUrl, 'newTab' => false) : null,
    ))),
    array('title' => '服务', 'links' => $serviceLinks),
);
$footerColumns = array_values(array_filter($footerColumns, function ($column) {
    return !empty($column['links']);
}));
if ($privacyUrl !== '') {
    $privacyAdded = false;
    foreach ($footerColumns as &$footerColumn) {
        if ($footerColumn['title'] !== '服务') continue;
        foreach ($footerColumn['links'] as $footerLink) {
            if ($footerLink['url'] === $privacyUrl || $footerLink['name'] === $privacyTitle || $footerLink['name'] === '隐私协议' || $footerLink['name'] === '隐私政策') {
                $privacyAdded = true;
                break 2;
            }
        }
        $footerColumn['links'][] = array('name' => $privacyTitle, 'url' => $privacyUrl, 'newTab' => false);
        $privacyAdded = true;
        break;
    }
    unset($footerColumn);
    if (!$privacyAdded) $footerColumns[] = array('title' => '服务', 'links' => array(array('name' => $privacyTitle, 'url' => $privacyUrl, 'newTab' => false)));
}
$hasFriendColumn = false;
foreach ($footerColumns as $footerColumn) {
    if ($footerColumn['title'] === '友链') {
        $hasFriendColumn = true;
        break;
    }
}
$footerFriends = curve_footer_friend_links();
if (!$hasFriendColumn && !empty($footerFriends)) {
    shuffle($footerFriends);
    $footerColumns[] = array('title' => '友链', 'friends' => $footerFriends, 'friendCount' => min(3, count($footerFriends)));
}
?>
<div class="footer-link">
    <?php if ($this->is('post')): ?><div class="footer-bar"><span class="site-title"><?php $this->options->title(); ?></span><span class="site-desc"><?php $this->options->description(); ?></span><a href="<?php $this->options->siteUrl(); ?>" class="to-home">了解更多</a></div><?php endif; ?>
    <div class="footer-social">
        <?php $half = (int) ceil(count($socialLinks) / 2); ?><div class="footer-social-side footer-social-left"><?php foreach (array_slice($socialLinks, 0, $half) as $link): ?><?php $socialIcon = curve_social_icon_for_link($link); ?><a href="<?php echo curve_esc($link['url']); ?>" target="_blank" rel="noopener" class="social-link" title="<?php echo curve_esc($link['name']); ?>" aria-label="<?php echo curve_esc($link['name']); ?>"><?php if ($socialIcon === 'twitter'): ?><span class="social-mark social-mark-twitter" aria-hidden="true">𝕏</span><?php else: ?><i class="iconfont icon-<?php echo curve_esc($socialIcon); ?>" aria-hidden="true"></i><?php endif; ?></a><?php endforeach; ?></div>
        <?php $authorCover = curve_safe_asset_url(curve_option($this->options, 'logoUrl')); if ($authorCover === '') { ob_start(); $this->options->themeUrl('assets/images/logo.webp'); $authorCover = ob_get_clean(); } $footerAvatarEmoji = trim((string) curve_option($this->options, 'footerAvatarEmoji')); $footerAvatarMessage = trim((string) curve_option($this->options, 'footerAvatarMessage')); ?><div class="footer-avatar"><div class="logo" title="返回顶部" data-scroll-top><img src="<?php echo curve_esc($authorCover); ?>" alt="author" class="author"></div><?php if ($footerAvatarEmoji !== '' && $footerAvatarMessage !== ''): ?><button type="button" class="footer-avatar-note" aria-label="<?php echo curve_esc($footerAvatarMessage); ?>"><span class="footer-avatar-note-emoji" aria-hidden="true"><?php echo curve_esc($footerAvatarEmoji); ?></span><span class="footer-avatar-note-text"><?php echo curve_esc($footerAvatarMessage); ?></span></button><?php endif; ?></div>
        <div class="footer-social-side footer-social-right"><?php foreach (array_slice($socialLinks, $half) as $link): ?><?php $socialIcon = curve_social_icon_for_link($link); ?><a href="<?php echo curve_esc($link['url']); ?>" target="_blank" rel="noopener" class="social-link" title="<?php echo curve_esc($link['name']); ?>" aria-label="<?php echo curve_esc($link['name']); ?>"><?php if ($socialIcon === 'twitter'): ?><span class="social-mark social-mark-twitter" aria-hidden="true">𝕏</span><?php else: ?><i class="iconfont icon-<?php echo curve_esc($socialIcon); ?>" aria-hidden="true"></i><?php endif; ?></a><?php endforeach; ?></div>
    </div>
    <?php if (!empty($footerColumns)): ?><div class="footer-sitemap">
        <?php foreach ($footerColumns as $column): ?>
            <?php if (isset($column['friends'])): ?>
                <?php $friendPayload = json_encode($column['friends'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?><div class="sitemap-item sitemap-friends" data-footer-friends data-friend-count="<?php echo (int) $column['friendCount']; ?>" data-friends="<?php echo curve_esc($friendPayload); ?>">
                    <span class="title friends"><span>友链</span><button type="button" class="friends-refresh" data-footer-friends-refresh title="刷新友链" aria-label="刷新友链"><i class="iconfont icon-refresh" aria-hidden="true"></i></button></span>
                    <div class="links friend-links" data-footer-friend-list aria-live="polite">
                        <?php foreach (array_slice($column['friends'], 0, $column['friendCount']) as $friend): ?><a href="<?php echo curve_esc($friend['url']); ?>" class="link-text" target="_blank" rel="noopener" title="<?php echo curve_esc($friend['desc']); ?>"><?php echo curve_esc($friend['name']); ?></a><?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="sitemap-item"><span class="title"><?php echo curve_esc($column['title']); ?></span><div class="links"><?php foreach ($column['links'] as $link): ?><a href="<?php echo curve_esc($link['url']); ?>" class="link-text"<?php echo !empty($link['newTab']) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo curve_esc($link['name']); ?></a><?php endforeach; ?></div></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div><?php endif; ?>
</div>
<footer id="main-footer" class="main-footer">
    <div class="footer-content">
        <div class="copyright"><span class="time">© <?php echo date('Y'); ?> By </span><?php $authorLink = curve_safe_http_url(curve_option($this->options, 'siteAuthorLink', $this->options->siteUrl)); if ($authorLink === '') $authorLink = curve_safe_http_url($this->options->siteUrl); ?><a href="<?php echo curve_esc($authorLink); ?>" class="author link" target="_blank" rel="noopener"><?php echo curve_esc(curve_option($this->options, 'siteAuthorName', '鱼鱼')); ?></a><?php $record = curve_option($this->options, 'recordNumber', curve_option($this->options, 'icp')); if ($record !== ''): ?><a class="icp link" href="https://beian.miit.gov.cn/" target="_blank" rel="noopener"><i class="iconfont icon-safe"></i><?php echo curve_esc($record); ?></a><?php endif; ?></div>
        <div class="meta"><a class="power link" href="https://typecho.org/" target="_blank" rel="noopener"><span class="by">Powered by</span><span class="name">Typecho</span></a><a class="theme link" href="https://github.com/deershark/typecho-theme-curve" target="_blank" rel="noopener"><span class="name">Curve</span></a><a class="rss link" href="<?php $this->options->feedUrl(); ?>"><i class="iconfont icon-rss"></i><span class="name">订阅</span></a><a class="cc link" href="https://creativecommons.org/licenses/by-nc-sa/4.0/deed.zh-hans" target="_blank" rel="noopener"><i class="iconfont icon-line"></i><i class="iconfont icon-by-line"></i><i class="iconfont icon-nc-line"></i><i class="iconfont icon-nd-line"></i></a></div>
    </div>
</footer>
<div class="right-menu" hidden data-right-menu><div class="menu-content s-card hover"><div class="tools"><button class="btn" data-history="back"><i class="iconfont icon-left"></i></button><button class="btn" data-history="forward"><i class="iconfont icon-right"></i></button><button class="btn" data-history="reload"><i class="iconfont icon-refresh"></i></button><button class="btn" data-scroll-top><i class="iconfont icon-arrow-up"></i></button></div><div class="all-menu"><button class="btn" data-copy-link><i class="iconfont icon-copy"></i><span class="name">复制本页地址</span></button></div></div></div>
<div class="curve-image-viewer" hidden data-image-viewer><button type="button" data-image-close>×</button><img data-image-viewer-img alt=""></div>
</div>
<!-- App.vue teleports this floating menu to body, outside the footer/app layer. -->
<div class="left-menu">
    <div class="settings">
        <button class="set-btn s-card" data-settings-open><i class="iconfont icon-style"></i><span class="set-text">个性化配置</span></button>
        <div class="modal" hidden data-settings-modal>
            <div class="modal-mask" data-settings-close></div>
            <div class="modal-main s-card" style="max-width:600px;--height:100vh">
                <div class="title"><div class="title-left"><i class="iconfont icon-style"></i><span>个性化配置</span></div><button class="close" data-settings-close><i class="iconfont icon-close"></i></button></div>
                <div class="modal-content">
                    <div class="set-list">
                        <span class="title">字体</span>
                        <div class="set-item"><span class="set-label">全站字体</span><div class="set-options"><button class="options" data-setting-font="vivo">vivo Sans</button><button class="options" data-setting-font="hmos">HarmonyOS Sans</button><button class="options" data-setting-font="lxgw">霞鹜文楷</button><button class="options" data-setting-font="xiaolai">小赖字体</button></div></div>
                        <div class="set-item"><span class="set-label">全站字体大小</span><div class="set-options"><button class="options" data-font-change="-1">−</button><span class="num" data-font-value>16</span><button class="options" data-font-change="1">+</button></div></div>
                        <span class="title">壁纸个性化</span>
                        <div class="set-item"><span class="set-label">全站背景</span><div class="set-options"><button class="options" data-setting-background="close">关闭</button><button class="options" data-setting-background="patterns">纹理</button><button class="options" data-setting-background="image">图片</button></div></div>
                        <div class="set-item" data-background-url-row hidden><span class="set-label">背景图片地址</span><div class="set-options"><input type="url" data-background-url placeholder="https://example.com/bg.jpg"></div></div>
                        <span class="title">首页样式</span>
                        <div class="set-item"><span class="set-label">Banner 高度</span><div class="set-options"><button class="options" data-banner="half">半屏</button><button class="options" data-banner="full">全屏</button></div></div>
                        <span class="title">杂项调整</span>
                        <div class="set-item"><span class="set-label">额外信息位置</span><div class="set-options"><button class="options" data-setting-info="normal">默认位置</button><button class="options" data-setting-info="fixed">右下角</button></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="<?php $this->options->themeUrl('assets/js/curve.js'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/assets/js/curve.js'); ?>"></script>
<?php $this->footer(); ?>
</body>
</html>
