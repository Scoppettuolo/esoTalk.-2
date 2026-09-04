<?php
if (!defined("IN_ESOTALK")) exit;
$lang = "en";
if (!empty(ET::$languageInfo[ET::$language]["locale"])) {
	$lang = substr(str_replace("_", "-", ET::$languageInfo[ET::$language]["locale"]), 0, 2);
} elseif (ET::$language) {
	$lang = strtolower(substr(ET::$language, 0, 2));
}
$themeColor = C("skin.Default.primaryColor") ?: "#2563eb";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="<?php echo T("charset", "utf-8"); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="<?php echo sanitizeHTML($themeColor); ?>">
<meta name="color-scheme" content="light dark">
<title><?php echo sanitizeHTML(isset($data["pageTitle"]) ? $data["pageTitle"] : "esoTalk"); ?></title>
<?php if (!empty($data["head"])) echo $data["head"]; ?>
</head>

<body class="<?php echo isset($data["bodyClass"]) ? $data["bodyClass"] : ""; ?>">
<?php $this->trigger("pageStart"); ?>

<a class="skip-link" href="#body-content"><?php echo T("Skip to content"); ?></a>

<div id="messages" role="status" aria-live="polite">
<?php if (!empty($data["messages"])): foreach ($data["messages"] as $message): ?>
<div class="messageWrapper">
<div class="message <?php echo $message["className"]; ?>" data-id="<?php echo isset($message["id"]) ? $message["id"] : ""; ?>"><?php echo $message["message"]; ?></div>
</div>
<?php endforeach; endif; ?>
</div>

<div id="wrapper">

<header id="hdr" role="banner">
<div id="hdr-content">
<div id="hdr-inner">

<?php if (!empty($data["backButton"])): ?>
<a href="<?php echo $data["backButton"]["url"]; ?>" id="backButton" title="<?php echo sanitizeHTML(sprintf(T("Back to %s"), $data["backButton"]["type"])); ?>"><i class="icon-chevron-left"></i></a>
<?php endif; ?>

<h1 id="forumTitle"><a href="<?php echo URL(""); ?>"><?php echo !empty($data["forumTitle"]) ? $data["forumTitle"] : "esoTalk"; ?><?php if (!empty($data["classicForumDescription"])): ?><small id="forumDescription"><?php echo sanitizeHTML($data["classicForumDescription"]); ?></small><?php endif; ?></a></h1>

<ul id="mainMenu" class="menu" aria-label="<?php echo T("Main menu"); ?>">
<?php if (!empty($data["mainMenuItems"])) echo $data["mainMenuItems"]; ?>
</ul>

<ul id="userMenu" class="menu" aria-label="<?php echo T("User menu"); ?>">
<?php if (!empty($data["userMenuItems"])) echo $data["userMenuItems"]; ?>
<li><a href="<?php echo URL("conversation/start"); ?>" class="link-newConversation button"><?php echo T("New Conversation"); ?></a></li>
</ul>

</div>
</div>
</header>

<main id="body" role="main">
<div id="body-content" tabindex="-1">
<?php echo isset($data["content"]) ? $data["content"] : ""; ?>
</div>
</main>

<footer id="ftr" role="contentinfo">
<div id="ftr-content">
<ul id="statistics" class="menu">
<?php if (!empty($data["statisticsMenuItems"])) echo $data["statisticsMenuItems"]; ?>
</ul>
<ul id="meta" class="menu">
<?php if (!empty($data["metaMenuItems"])) echo $data["metaMenuItems"]; ?>
</ul>
</div>
</footer>
<?php $this->trigger("pageEnd"); ?>

</div>

</body>
</html>
