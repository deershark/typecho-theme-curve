<?php
/**
 * 友情链接
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) { exit; }

/* Typecho's $this->content() has already converted Markdown to HTML and
 * escapes the custom comment block. Read the original page source instead. */
$pageSource = isset($this->text) ? (string) $this->text : '';
$pageSource = preg_replace('/^<!--markdown-->/i', '', $pageSource);
$friendBlocks = array();
$friendTotal = 0;
$hasFriendBlock = false;
$friendParseValid = true;
$friendErrors = array();
$friendBlockNumber = 0;
$pageContentSource = preg_replace('/<!--\s*curve-friends\b.*?-->/is', '', $pageSource);
$pageSource = preg_replace_callback('/<!--\s*curve-friends\b(.*?)-->/is', function ($match) use (&$friendBlocks, &$friendTotal, &$hasFriendBlock, &$friendParseValid, &$friendErrors, &$friendBlockNumber) {
    $hasFriendBlock = true;
    $friendBlockNumber++;
    $slot = 'CURVEFRIENDSSLOT' . count($friendBlocks);
    $parseResult = curve_parse_friend_markdown($match[1], true);
    if (!$parseResult['valid']) {
        $friendParseValid = false;
        foreach ($parseResult['errors'] as $error) {
            $friendErrors[] = ($friendBlockNumber > 1 ? '第' . $friendBlockNumber . '个 curve-friends 配置块，' : '') . $error;
        }
    }
    foreach ($parseResult['groups'] as $group) {
        $friendTotal += count($group['typeList']);
    }
    $friendBlocks[$slot] = curve_render_friend_groups($parseResult['groups']);
    return "\n\n" . $slot . "\n\n";
}, $pageSource);
if (preg_match('/<!--\s*curve-friends\b/is', $pageSource)) {
    $hasFriendBlock = true;
    $friendParseValid = false;
    $friendErrors[] = 'curve-friends 配置块未闭合，请补充结束标记“-->”。';
}
$pageHtml = curve_render_markdown($pageSource);
foreach ($friendBlocks as $slot => $html) {
    /* 富文本编辑器可能将占位符包成 <p><br>标记<br></p>；替换时一并去掉
     * 空段落，避免把友链卡片 <div> 塞进 <p> 后产生多余的 <br>。 */
    $slotPattern = preg_quote($slot, '/');
    $pageHtml = preg_replace_callback('/<p\b[^>]*>(?:\s|&nbsp;|<br\s*\/?>)*' . $slotPattern . '(?:\s|&nbsp;|<br\s*\/?>)*<\/p>/i', function () use ($html) {
        return $html;
    }, $pageHtml);
    $pageHtml = str_replace($slot, $html, $pageHtml);
}
if (!$friendParseValid) {
    $friendTotal = 0;
}
$pageText = trim((string) preg_replace('/\s+/u', '', strip_tags($pageContentSource)));
$hasPageContent = $pageText !== '' || preg_match('/<(?:img|hr|iframe|video|audio|table|pre|blockquote|ul|ol)\b/i', $pageContentSource);
$showFriendHelp = (!$hasFriendBlock && !$hasPageContent) || ($hasFriendBlock && !$friendParseValid);
$friendHelp = <<<'MARKDOWN'
# 友情链接配置

请在这个页面的正文中粘贴下面的特殊 Markdown 块，并按需修改内容。每一条友链都要写成列表项；头像和简介可以留空。

```md
<!-- curve-friends
## 推荐 | 都是大佬，推荐关注
- 鹿形鱼de小窝 | https://blog.xingyu.lu/ | https://example.com/avatar.png | 阮老师，知名博主

## 小伙伴们 | 我们在一起，共同进步
- 我的博客 | https://example.com/ | https://example.com/avatar.png | 分享技术与生活
-->
```

格式为：`- 名称 | 链接 | 头像 | 简介`。链接和头像必须是有效 URL；如果不需要分组，可以省略 `## 分组标题`，直接填写友链列表。特殊块外的正文仍会按普通 Markdown 渲染，可用于补充友链申请说明。
MARKDOWN
?>
<?php $this->need('header.php'); ?>
<main class="mian-layout">
    <div class="link">
        <div class="cat-or-tag">
            <div class="title">
                <h1 class="title-name"><?php $this->title(); ?></h1>
                <span class="title-num">友链总览 · <?php echo $friendTotal; ?> 个友链</span>
            </div>
        </div>
        <?php if ($showFriendHelp && $hasFriendBlock && !$friendParseValid): ?>
            <div class="link-config-errors s-card"><strong>友情链接配置有误，请按提示修改：</strong><p>下面的行号按 curve-friends 配置块内部计算。</p><ul><?php foreach ($friendErrors as $error): ?><li><?php echo curve_esc($error); ?></li><?php endforeach; ?></ul></div>
            <div class="link-config-help s-card markdown-main-style"><?php echo curve_render_markdown($friendHelp); ?></div>
        <?php elseif ($showFriendHelp): ?>
            <div class="link-config-empty s-card"><strong>友情链接页面还没有内容。</strong><p>请在正文中添加 curve-friends 配置块，下面提供了可直接复制的示例。</p></div>
            <div class="link-config-help s-card markdown-main-style"><?php echo curve_render_markdown($friendHelp); ?></div>
        <?php else: ?>
            <div class="markdown-main-style s-card">
                <?php echo $pageHtml; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php $this->need('comments.php'); ?>
</main>
<?php $this->need('footer.php'); ?>
