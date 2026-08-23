<?php if (!defined('__TYPECHO_ROOT_DIR__')) { exit; } ?>
<?php $curveDefaultFont = curve_theme_default_font($this->options); $curveDefaultBanner = curve_theme_default_banner($this->options); $curveDefaultBackground = curve_theme_default_background($this->options); $curveDefaultBackgroundUrl = curve_theme_default_background_url($this->options); $curveBackgroundUrlConfigurable = $curveDefaultBackground === 'image' ? '0' : '1'; ?>
<!doctype html>
<html lang="zh-CN" class="light" data-curve-default-font="<?php echo curve_esc($curveDefaultFont); ?>" data-curve-default-banner="<?php echo curve_esc($curveDefaultBanner); ?>" data-curve-default-background="<?php echo curve_esc($curveDefaultBackground); ?>" data-curve-default-background-url="<?php echo curve_esc($curveDefaultBackgroundUrl); ?>" data-curve-background-url-configurable="<?php echo $curveBackgroundUrlConfigurable; ?>">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php if ($this->is('category')): ?>分类 <?php $this->archiveTitle('', '', ''); ?> 下的文章 - <?php elseif ($this->is('tag')): ?>标签 <?php $this->archiveTitle('', '', ''); ?> 下的文章 - <?php elseif ($this->is('author')): ?>作者 <?php $this->archiveTitle('', '', ''); ?> 发布的文章 - <?php else: ?><?php $this->archiveTitle('', '', ' - '); ?><?php endif; ?><?php $this->options->title(); ?></title>
    <?php $curveFavicon = curve_safe_asset_url(curve_option($this->options, 'logoUrl')); if ($curveFavicon === '') $curveFavicon = curve_theme_asset_url($this->options, 'assets/images/logo.webp'); ?>
    <link rel="icon" href="<?php echo curve_esc($curveFavicon); ?>" type="image/webp">
    <script>
        (function () {
            var root = document.documentElement;
            var allowed = ["hmos", "lxgw", "vivo", "xiaolai"];
            var saved = null;
            try { saved = window.localStorage.getItem("curve-typecho-font"); } catch (error) {}
            var font = allowed.indexOf(saved) !== -1 ? saved : root.getAttribute("data-curve-default-font");
            if (allowed.indexOf(font) !== -1) root.classList.add(font);
            if (window.location.hash) root.classList.add("curve-initial-hash");
        }());
    </script>
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/curve.css'); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/assets/css/curve.css'); ?>">
    <?php echo curve_theme_font_config_markup($this->options); ?>
    <?php $accentColor = curve_accent_color($this->options); if ($accentColor !== ''): ?><style>:root{--main-color:<?php echo $accentColor; ?>;--main-color-bg:<?php echo $accentColor; ?>0d;}html.dark{--main-color:<?php echo $accentColor; ?>;--main-color-bg:<?php echo $accentColor; ?>23;}</style><?php endif; ?>
    <link rel="stylesheet" href="https://cdn2.codesign.qq.com/icons/g5ZpEgx3z4VO6j2/latest/iconfont.css">
    <link rel="alternate" type="application/rss+xml" title="RSS" href="<?php $this->options->feedUrl(); ?>">
    <?php $this->header(); ?>
</head>
<body>
<?php $logo = curve_safe_asset_url(curve_option($this->options, 'logoUrl')); if ($logo !== '') { $loadingLogo = $logo; } else { ob_start(); $this->options->themeUrl('assets/images/logo.webp'); $loadingLogo = ob_get_clean(); } ?>
<?php
$archiveUrl = curve_page_url('page-archives.php');
$archiveTitle = curve_page_template_title('page-archives.php', '文章归档');
$aboutUrl = curve_page_url('page-about.php');
$aboutTitle = curve_page_template_title('page-about.php', '关于本站');
$linksUrl = curve_page_url('page-links.php');
$linksTitle = curve_page_template_title('page-links.php', '友情链接');
$categoriesUrl = curve_page_url('page-categories.php');
$categoriesTitle = curve_page_template_title('page-categories.php', '全部分类');
$tagsUrl = curve_page_url('page-tags.php');
$tagsTitle = curve_page_template_title('page-tags.php', '全部标签');
$moreMenu = curve_top_left_menu_rows(curve_option($this->options, 'topLeftMenu'));
if (empty($moreMenu)) {
    $moreMenu = array(array('group' => '博客', 'name' => '主站', 'url' => $this->options->siteUrl, 'icon' => 'home', 'image' => true));
    if ($archiveUrl !== '') {
        $moreMenu[] = array('group' => '博客', 'name' => $archiveTitle, 'url' => $archiveUrl, 'icon' => 'article');
    }
}
$moreGroups = array();
foreach ($moreMenu as $moreItem) {
    if (!isset($moreGroups[$moreItem['group']])) {
        $moreGroups[$moreItem['group']] = array();
    }
    $moreGroups[$moreItem['group']][] = $moreItem;
}
?>
<div id="app" class="is-loading" aria-busy="true">
    <div class="background <?php echo curve_esc($curveDefaultBackground); ?> light" data-background="<?php echo curve_esc($curveDefaultBackground); ?>"<?php echo $curveDefaultBackground === 'close' ? ' hidden' : ''; ?> aria-hidden="true">
        <img id="background-cover" class="cover" data-background-cover alt="background" hidden>
    </div>
    <div class="loading fade-enter-from" data-loading>
        <img src="<?php echo curve_esc($loadingLogo); ?>" class="logo" alt="loading-logo">
        <span class="tip">一直显示？点击任意区域即可关闭</span>
    </div>
    <header class="main-header">
        <nav class="main-nav top" data-main-nav>
            <div class="nav-all">
                <div class="left-nav">
                    <div class="more-menu nav-btn" title="更多内容" tabindex="0">
                        <i class="iconfont icon-menu"></i>
                        <div class="more-card s-card">
                            <?php foreach ($moreGroups as $groupName => $groupItems): ?><div class="more-item">
                                <span class="more-name"><?php echo curve_esc($groupName); ?></span>
                                <div class="more-list">
                                    <?php foreach ($groupItems as $moreItem): ?><a href="<?php echo curve_esc($moreItem['url']); ?>" class="more-link"><?php if (!empty($moreItem['image'])): ?><img class="link-icon" src="<?php echo curve_esc($logo ?: $this->options->siteUrl); ?>" alt="<?php echo curve_esc($moreItem['name']); ?>"><?php elseif (!empty($moreItem['iconUrl'])): ?><img class="link-icon" src="<?php echo curve_esc($moreItem['iconUrl']); ?>" alt="<?php echo curve_esc($moreItem['name']); ?>"><?php else: ?><i class="iconfont icon-<?php echo curve_esc($moreItem['icon']); ?> link-icon"></i><?php endif; ?><span class="link-name"><?php echo curve_esc($moreItem['name']); ?></span></a><?php endforeach; ?>
                                </div>
                            </div><?php endforeach; ?>
                        </div>
                    </div>
                    <a class="site-name" href="<?php $this->options->siteUrl(); ?>"><?php $this->options->title(); ?></a>
                </div>
                <div class="nav-center">
                    <div class="site-menu">
                        <div class="menu-item"><span class="link-btn">文库</span><div class="link-child">
                            <?php if ($archiveUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($archiveUrl); ?>"><i class="iconfont icon-article"></i><?php echo curve_esc($archiveTitle); ?></a><?php endif; ?>
                            <?php if ($categoriesUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($categoriesUrl); ?>"><i class="iconfont icon-folder"></i><?php echo curve_esc($categoriesTitle); ?></a><?php endif; ?>
                            <?php if ($tagsUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($tagsUrl); ?>"><i class="iconfont icon-hashtag"></i><?php echo curve_esc($tagsTitle); ?></a><?php endif; ?>
                        </div></div>
                        <div class="menu-item"><span class="link-btn">友链</span><div class="link-child">
                            <?php if ($linksUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($linksUrl); ?>"><i class="iconfont icon-people"></i><?php echo curve_esc($linksTitle); ?></a><?php endif; ?>
                        </div></div>
                        <div class="menu-item"><span class="link-btn">我的</span><div class="link-child">
                            <?php if ($aboutUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($aboutUrl); ?>"><i class="iconfont icon-contacts"></i><?php echo curve_esc($aboutTitle); ?></a><?php endif; ?>
                        </div></div>
                    </div>
                    <span class="site-title" data-scroll-top><?php $this->is('index') ? $this->options->description() : $this->archiveTitle('', '', ''); ?></span>
                </div>
                <div class="right-nav">
                    <?php if (curve_is_enabled($this->options, 'travellingsEnable', true)): ?><a class="menu-btn nav-btn travellings" title="开往-友链接力" href="https://www.travellings.cn/go.html" target="_blank" rel="noopener"><i class="iconfont icon-subway"></i></a><?php endif; ?>
                    <button class="menu-btn nav-btn" title="随机前往一篇文章" data-random-post><i class="iconfont icon-shuffle"></i></button>
                    <button class="menu-btn nav-btn" title="全站搜索" data-search-open><i class="iconfont icon-search"></i></button>
                    <button id="open-control" class="menu-btn nav-btn pc" title="打开中控台" data-control-open><i class="iconfont icon-dashboard"></i></button>
                    <?php if ($this->user->hasLogin()): ?><a class="menu-btn nav-btn" title="进入后台" aria-label="进入后台" href="<?php $this->options->adminUrl(); ?>"><i class="iconfont icon-tools"></i></a><?php endif; ?>
                    <div class="to-top menu-btn hidden" title="返回顶部" data-scroll-top>
                        <div class="to-top-btn"><span class="num" data-scroll-percent>0</span><i class="iconfont icon-up"></i></div>
                    </div>
                    <button class="menu-btn nav-btn mobile" title="打开菜单" data-mobile-open><i class="iconfont icon-toc"></i></button>
                </div>
            </div>
        </nav>
        <div class="mobile-menu" hidden data-mobile-menu>
            <div class="menu-mask" data-mobile-close></div>
            <div class="menu-content s-card">
                <button class="close-control" data-mobile-close><i class="iconfont icon-close"></i></button>
                <div class="menu-list">
                    <div class="menu-item"><span class="link-title">文库</span><div class="link-child"><?php if ($archiveUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($archiveUrl); ?>"><i class="iconfont icon-article"></i><span class="name"><?php echo curve_esc($archiveTitle); ?></span></a><?php endif; ?><?php if ($categoriesUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($categoriesUrl); ?>"><i class="iconfont icon-folder"></i><span class="name"><?php echo curve_esc($categoriesTitle); ?></span></a><?php endif; ?><?php if ($tagsUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($tagsUrl); ?>"><i class="iconfont icon-hashtag"></i><span class="name"><?php echo curve_esc($tagsTitle); ?></span></a><?php endif; ?></div></div>
                    <div class="menu-item"><span class="link-title">友链</span><div class="link-child"><?php if ($linksUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($linksUrl); ?>"><span class="name"><?php echo curve_esc($linksTitle); ?></span></a><?php endif; ?></div></div>
                    <div class="menu-item"><span class="link-title">我的</span><div class="link-child"><?php if ($aboutUrl !== ''): ?><a class="link-child-btn" href="<?php echo curve_esc($aboutUrl); ?>"><span class="name"><?php echo curve_esc($aboutTitle); ?></span></a><?php endif; ?></div></div>
                </div>
            </div>
        </div>
        <div class="search modal" hidden data-search-modal>
            <div class="modal-mask" data-search-close></div>
            <div class="modal-main s-card" style="max-width:800px"><div class="title"><div class="title-left"><i class="iconfont icon-search"></i><span>全局搜索</span></div><button class="close" data-search-close><i class="iconfont icon-close"></i></button></div><div class="modal-content" style="--height:80vh"><form class="search-form" method="get" action="<?php $this->options->siteUrl(); ?>"><input name="s" type="search" placeholder="想要搜点什么" autofocus value="<?php echo $this->is('search') ? curve_esc($this->keywords) : ''; ?>"><button type="submit">搜索</button></form></div></div>
        </div>
    </header>
    <div class="control" hidden data-control>
        <div class="close-control" data-control-close><i class="iconfont icon-close"></i></div><div class="control-mask" data-control-close></div><div class="control-content"><div class="menu"><button class="menu-item open" data-theme-toggle title="当前：跟随系统，点击切换"><i class="iconfont icon-auto" data-theme-icon></i></button><button class="menu-item open" data-right-toggle title="右键菜单开关"><i class="iconfont icon-list"></i></button><button class="menu-item" data-blur-toggle title="背景模糊开关" aria-pressed="false"><i class="iconfont icon-blur"></i></button></div></div>
    </div>
    <div class="message" hidden data-message><div class="message-content"><span class="text"></span><button class="close" data-message-close><i class="iconfont icon-close"></i></button></div></div>
