<?php
/**
 * 隐私协议
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) { exit; }

/*
 * The page body can be edited in Typecho. Keep a usable default here so the
 * page is not empty when the template is selected without adding content.
 */
$pageSource = '';
ob_start();
$this->content();
$pageSource = trim(ob_get_clean());
$pageText = trim((string) preg_replace('/\s+/u', '', strip_tags($pageSource)));
$hasPageContent = $pageText !== '' || preg_match('/<(?:img|hr|iframe|video|audio|table|pre|blockquote|ul|ol)\b/i', $pageSource);
if (!$hasPageContent) {
    $pageSource = <<<'MARKDOWN'
为了正常运行评论功能并维护友善的交流环境，本站会按照本政策处理你在评论区提交的信息。

## 我们会收集哪些信息

提交评论时，本站可能会处理以下信息：

- 昵称、邮箱、网址以及你填写的评论内容；邮箱不会直接公开显示。
- 提交评论时的 IP 地址、浏览器 User-Agent 和提交时间，用于反垃圾、审核、故障排查和安全维护。
- 评论展示时，本站可能根据 IP 地址生成大致的归属地，并根据 User-Agent 显示系统信息。归属地和系统信息不代表精确位置。

如果评论使用了头像，头像服务可能根据邮箱生成的哈希值请求 Gravatar 等第三方服务。你也可以不填写网址或不使用头像。

## 信息如何使用

本站仅将上述信息用于展示和管理评论、识别回复关系、处理垃圾评论、保障站点安全以及改进评论体验，不会将评论者信息用于与评论无关的商业推广或出售给第三方。

## 信息保存与公开

评论及其必要的元数据会保存在本站使用的 Typecho 数据库中，保存期限取决于评论是否仍有展示和管理需要，以及站点的维护策略。已经发布的评论内容、昵称、网址（如有）可能会被公开展示；邮箱、原始 IP 和 User-Agent 不会作为评论正文公开，但其派生出的归属地和系统信息可能显示在评论区。

仅当站点管理员开启“评论 IP 归属地查询”时，服务器才会向 `ipwho.is` 请求公开 IP 的大致地理信息；每个页面请求最多查询一条未缓存 IP，其余未缓存 IP 显示为未知地区。该服务的处理以其自身隐私政策为准，归属地结果会在本站服务器缓存一段时间。

## 你的选择

你可以选择不发表评论，或不填写非必填的邮箱和网址。若需要修改或删除自己的评论，或对信息处理有疑问，请通过本站提供的反馈方式联系站点维护者；在核实请求后，本站会在合理范围内处理。

## 政策更新

本站可能根据评论功能、法律法规或运营方式的变化更新本政策。更新后的内容会发布在本页面。
MARKDOWN;
}
?>
<?php $this->need('header.php'); ?>
<main class="mian-layout">
    <div class="cat-or-tag privacy-page">
        <div class="title">
            <h1 class="title-name"><?php $this->title(); ?></h1>
        </div>
        <div id="page-content" class="markdown-main-style privacy-content s-card"><?php echo curve_render_markdown($pageSource); ?></div>
    </div>
</main>
<?php $this->need('footer.php'); ?>
