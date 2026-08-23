<?php
/**
 * 基于原主题 <a href="https://github.com/imsyy/vitepress-theme-curve" target="_blank" rel="noopener">VitePress Theme Curve</a> 移植并重构的 Typecho 主题；项目地址：<a href="https://github.com/deershark/typecho-theme-curve" target="_blank" rel="noopener">Curve for Typecho</a>。
 *
 * @package Curve for Typecho
 * @author 鹿形鱼 <https://blog.xingyu.lu/>
 * @version 0.0.18
 * @link https://github.com/deershark/typecho-theme-curve
 */
?>
<?php if (!defined('__TYPECHO_ROOT_DIR__')) { exit; } ?>
<?php $categoriesUrl = curve_page_url('page-categories.php'); ?>
<?php $defaultBanner = curve_theme_default_banner($this->options); ?>
<?php $homeSubtitleMode = curve_option($this->options, 'homeSubtitleMode', 'custom'); if (!in_array($homeSubtitleMode, array('custom', 'hitokoto'), true)) $homeSubtitleMode = 'custom'; ?>
<?php $homeSubtitle = curve_option($this->options, 'homeSubtitle', '记录值得分享的技术、想法与生活。'); ?>
<?php $this->need('header.php'); ?>
<main class="mian-layout">
    <div class="home">
        <div class="banner <?php echo curve_esc($defaultBanner); ?>" id="main-banner"><h1 class="title">你好，欢迎来到<?php $this->options->title(); ?></h1><div class="subtitle"><span class="text" data-home-subtitle data-home-subtitle-mode="<?php echo curve_esc($homeSubtitleMode); ?>"><?php echo $homeSubtitleMode === 'hitokoto' ? '一言加载中…' : nl2br(curve_esc($homeSubtitle)); ?></span></div><i class="iconfont icon-up" hidden data-scroll-home></i></div>
        <div class="home-content">
            <div class="posts-content">
                <div class="type-bar s-card hover"><div class="all-type"><a href="<?php $this->options->siteUrl(); ?>" class="type-item choose" data-category-filter>首页</a><?php $categories = $this->widget('Widget_Metas_Category_List'); while ($categories->next()): ?><a href="<?php $categories->permalink(); ?>" class="type-item" data-category-filter><?php $categories->name(); ?></a><?php endwhile; ?></div><?php if ($categoriesUrl !== ''): ?><a href="<?php echo curve_esc($categoriesUrl); ?>" class="more-type"><i class="iconfont icon-arrow-right"></i>更多</a><?php endif; ?></div>
                <div class="post-list-stage">
                    <div class="category-loading" hidden data-category-loading><i class="iconfont icon-refresh"></i><span>正在加载文章</span></div>
                    <div class="post-lists<?php echo curve_option($this->options, 'coverLayout') === 'grid' ? ' layout-grid' : ''; ?>">
                        <?php if ($this->have()): while ($this->next()): $this->need('partials/post-card.php'); endwhile; else: ?><div class="post-item s-card"><div class="post-content"><span class="post-title">暂无文章</span></div></div><?php endif; ?>
                    </div>
                </div>
                <div class="pagination"><?php curve_page_nav($this); ?></div>
            </div>
            <?php $this->need('sidebar.php'); ?>
        </div>
    </div>
<?php $this->need('footer.php'); ?>
