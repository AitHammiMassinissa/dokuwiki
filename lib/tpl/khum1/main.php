<?php
/**
 * Khum1 DokuWiki Template 2020 (based on Zen, based on Default template)
 *
 * @link     http://dokuwiki.org/template
 * @author   Zatalyz <zatalyz@liev.re>
 * @author   Anika Henke <anika@selfthinker.org>
 * @author   Clarence Lee <clarencedglee@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

if (!defined('DOKU_INC')) die(); /* must be run from within DokuWiki */
header('X-UA-Compatible: IE=edge,chrome=1');

$hasSidebar = page_findnearest($conf['sidebar']);
$showSidebar = $hasSidebar && ($ACT=='show');
?><!DOCTYPE html>
<html lang="<?php echo $conf['lang'] ?>" dir="<?php echo $lang['direction'] ?>" class="no-js">
<head>
    <meta charset="utf-8" />
    <title><?php tpl_pagetitle() ?> [<?php echo strip_tags($conf['title']) ?>]</title>
    <script>(function(H){H.className=H.className.replace(/\bno-js\b/,'js')})(document.documentElement)</script>
    <?php tpl_metaheaders() ?>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <?php echo tpl_favicon(array('favicon', 'mobile')) ?>
    <?php tpl_includeFile('meta.html') ?>
</head>

<body>
	<?php tpl_includeFile('header.php'); ?>

    <div id="dokuwiki__top" class="site <?php echo tpl_classes(); ?> <?php
        echo ($showSidebar) ? 'showSidebar' : ''; ?> <?php echo ($hasSidebar) ? 'hasSidebar' : ''; ?>">

        <!-- Jump to content (accessibility --> 
        <ul class="a11y skip">
            <li><a href="#dokuwiki__content"><?php echo $lang['skip_to_content']; ?></a></li>
        </ul>

		<!-- ********** HEADER ********** -->
		<header id="dokuwiki__header">

			<!-- Menu spécial mobiles -->
			<div class="mobileTools">
				<?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>
				<?php tpl_searchform(); ?>
				<!-- translation (only with Translation Plugin) -->
				<?php
				$translation = plugin_load('helper','translation');
				if ($translation) echo $translation->showTranslations();
				?>
			</div>

			<!-- logo, wiki title, tagline, breadcrumb -->
			<div class="headings">
				<h1><?php
					// get logo either out of the template images folder or data/media folder
					$logo = tpl_getMediaFile(array(':wiki:logo.svg', ':wiki:logo.png', ':logo.png', 'images/logo.svg', 'images/logo.png'), false);

					// display logo and wiki title in a link to the home page
					// Add <span>'.$conf['title'].'</span> after img to have Wiki Title display
					tpl_link(
						wl(),
						'<img src="'.$logo.'" '.$logoSize[3].' alt="'.$conf['title'].'" /> ',
						'accesskey="h" title="[H] '.$conf['title'].'"'
					);
				?></h1>
				<?php if ($conf['tagline']): ?>
					<p class="claim"><?php echo $conf['tagline']; ?></p>
				<?php endif ?>
			</div>         
		</header>
		
		<hr class="a11y" />
		<!-- /header -->


		<div id="dokuwiki__site">
			<!--<div class="pad group">-->
        <div class="wrapper group">
          
                <!-- ********** Sidebar ********** -->
                <?php if($showSidebar): ?>
                <nav id="dokuwiki__sidebar">
					<div class="biseau_left_top nomobile"></div>
					<h3 class="toggle"><?php //echo $lang['sidebar'] ?></h3>
					<div class="pad sidebar include group">
						<div class="content"><div class="group">
							<?php tpl_flush() ?>
							<?php tpl_include_page($conf['sidebar'], true, true) ?>
						</div></div>
					</div>
					<div class="biseau_2_bottom nomobile"></div>
                </nav>
                <?php endif; ?>
                <!-- /Sidebar -->

            

            <!-- ********** Article ********** -->
            <div id="dokuwiki__content"><div class="pad group">
                <?php html_msgarea() ?>

				<!-- ********** Tab ********** -->
                <div class="tabblocks">
                    <div class="nomobile">
						
						<ul class="tab_ul">
					<?php 
					foreach ( (new \dokuwiki\Menu\PageMenu())->getItems() as $item ) {
						if ( preg_match('/^childrenpages_/', $item->getType()) ) {
							if ( $item->is_active ) {
								echo '<li class="current_page"><div class="toptab_current_page"></div>'.$item->asHtmlLink(false, true).'</li>';
							} else {
								echo '<li><div class="toptab"></div>'.$item->asHtmlLink(false, true).'</li>';
							}
						}
					}
						
					?>
						</ul>
						
					</div>
					
                </div>
                
                <div class="biseau_right_top"></div>
				<div class="page group">
					
					
                    <?php tpl_flush() ?>
                    <?php tpl_includeFile('pageheader.html') ?>
                    

                    <!-- wikipage start -->
					<?php tpl_toc(false) ?> 
					<article class="kharticle">
                    <?php tpl_content(false) ?>
					</article>
                    <!-- wikipage stop -->
                    <?php tpl_includeFile('pagefooter.html') ?>
                </div>
                <div class="biseau_2_bottom"></div>
				<footer class="article_footer">
					<div class="buttons">
						<?php
							tpl_license('button', true, false, false); // license button, no wrapper
							$target = ($conf['target']['extern']) ? 'target="'.$conf['target']['extern'].'"' : '';
						?>
						<a href="https://dokuwiki.org/" title="Driven by DokuWiki" <?php echo $target?>><img
							src="<?php echo tpl_basedir(); ?>images/button-dw.png" width="80" height="15" alt="Driven by DokuWiki" /></a>
					</div>
                <div class="docInfo"><?php tpl_pageinfo() ?></div>
                </footer>

                <?php tpl_flush() ?>
            </div></div><!-- /article -->

            <hr class="a11y" />

			<!-- ********** Right menu (tools) ********** -->
			<div>
			<nav class="nomobile navtool">
				<div class="biseau_right_top nomobile"></div>
				<div class="nomobile bartool"
					
				
					<!-- searchtool-->
					<div class="searchtool nomobile">
						<?php tpl_searchform(); ?>
					</div>
					<!-- You are here -->
					<?php if($conf['youarehere']): ?>
						<div class="youarehere nomobile"><?php tpl_youarehere() ?></div>
						<hr class="nomobile">
					<?php endif ?>
					
					<!-- Page Actions -->
					<div class="dokuwiki__tools nomobile">
						<!-- Don't include childrenpage here -->
						<?php 
						foreach ( (new \dokuwiki\Menu\PageMenu())->getItems() as $item ) {
							if ( ! preg_match('/^childrenpages_/', $item->getType()) ) {
								echo '<li>'.$item->asHtmlLink(false, true).'</li>';
							}
						}
 ?>
					</div>
					
					<hr class="nomobile">
					
					<!-- usertools and sitetools -->
					<div class="dokuwiki__tools nomobile">
						<?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems(); ?>
						<hr class="nomobile">
						<!-- Link interwiki of user -->
						<?php if ($conf['useacl']): ?> 
						<ul><?php
							// Afficher lien interwiki user
							if (!empty($_SERVER['REMOTE_USER'])) {
									echo '<li class="user">';
									$loginname = $_SERVER["REMOTE_USER"];
									$kh_userpage = tpl_getLang('kh_userpage');
									echo "<span>$kh_userpage </span>";
									echo (tpl_getConf("khum1_userpage")
										? html_wikilink(tpl_getConf("khum1_userpage_ns").$loginname, hsc($loginname))
											: hsc($loginname));
									echo '</li>';
									}
						?></ul>
						<?php endif ?>
						<!-- UserMenu -->
						<?php echo (new \dokuwiki\Menu\UserMenu())->getListItems(); ?>


					</div>
					<hr class="nomobile">
					
					<!-- translation (only with Translation Plugin) -->
					<div>
						<?php
						$translation = plugin_load('helper','translation');
						if ($translation) echo $translation->showTranslations();
						?>
					</div>

					<!-- Breadcrumbs -->
					<?php if($conf['breadcrumbs']): ?>
						<div class="trace nomobile"><div><?php tpl_breadcrumbs() ?></div></div>
					<?php endif ?>
				</div>
				<div class="biseau_2_bottom nomobile"></div>
			</nav>
			<div class="to_top">
					<a href="#dokuwiki__top" class="action top" accesskey="t" rel="nofollow" title="Haut de page [T]">
						<img src="<?php echo tpl_basedir(); ?>/images/top.png" / title="Haut de page [T]"></img>
					</a>
			</div>
			<div>

        </div><!-- /wrapper -->
        </div><!-- dokuwiki__site-->
        
    </div><!-- /site -->

    <div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?></div>
    <div id="screen__mode" class="no"></div><?php /* helper to detect CSS media query in script.js */ ?>
	<?php tpl_includeFile('footer.php'); ?>
</body>
</html>
