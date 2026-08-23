<?php if (!defined('__TYPECHO_ROOT_DIR__')) { exit; } ?>
<div id="main-comment" class="comment">
    <?php $privacyUrl = curve_page_url('page-privacy.php'); $privacyTitle = curve_page_template_title('page-privacy.php', '隐私协议'); if ($privacyUrl === '') $privacyUrl = curve_safe_http_url(curve_option($this->options, 'privacyUrl')); ?>
    <div class="title"><span class="name"><i class="iconfont icon-chat"></i>评论</span><?php if ($privacyUrl !== ''): ?><a class="tool" href="<?php echo curve_esc($privacyUrl); ?>"><?php echo curve_esc($privacyTitle); ?></a><?php endif; ?></div>
    <?php $this->comments()->to($comments); ?>
    <?php $commentEnabled = curve_is_enabled($this->options, 'commentEnable', true); $commentFormPosition = curve_option($this->options, 'commentFormPosition', 'bottom'); if ($commentEnabled && $this->allow('comment') && $commentFormPosition === 'top') curve_comment_form($this, $comments, $this->options, $this->user, $this->commentUrl); ?>
    <?php if ($comments->have()): ?><?php $comments->listComments(); ?><?php $comments->pageNav('上页', '下页', 1, '…'); ?><?php else: ?><p class="comment-empty">暂无评论，欢迎留下第一条评论。</p><?php endif; ?>
    <?php if ($commentEnabled && $this->allow('comment') && $commentFormPosition !== 'top'): curve_comment_form($this, $comments, $this->options, $this->user, $this->commentUrl); elseif (!$commentEnabled): ?><p class="comment-empty">评论功能已关闭。</p>
    <?php elseif (!$this->allow('comment')): ?><p class="comment-empty">当前内容不允许评论。</p><?php endif; ?>
</div>
