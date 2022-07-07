<?php
/**
 * DokuWiki Default Template 2012
 *
 * Modifed by Gero Gothe <practical@medizin-lernen.de>
 *
 * @link     http://dokuwiki.org/template
 * @author   Anika Henke <anika@selfthinker.org>
 * @author   Clarence Lee <clarencedglee@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

if (!defined('DOKU_INC')) die(); /* must be run from within DokuWiki */
header('X-UA-Compatible: IE=edge,chrome=1');

$hasSidebar = page_findnearest($conf['sidebar']);
$showSidebar = $hasSidebar && ($ACT=='show');

# Default Values
if ($conf['tpl']['kkh']['color background'] == '') $conf['tpl']['kkh']['color background'] = "#eeeeee";
if ($conf['tpl']['kkh']['color header'] == '') $conf['tpl']['kkh']['color header'] = "#aaaaaa";

?><!DOCTYPE html>

<html style="overflow-x:hidden;" lang="<?php echo $conf['lang'] ?>" dir="<?php echo $lang['direction'] ?>" class="no-js">
<head>
    <meta charset="utf-8" />
    
    <!-- Anpassung Grünstadt -->
    <title><?php echo strip_tags($conf['title']) ?>: <?php tpl_pagetitle() ?></title>
    <!------------------------->
    
    <script>(function(H){H.className=H.className.replace(/\bno-js\b/,'js')})(document.documentElement)</script>
    <?php tpl_metaheaders() ?>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <?php echo tpl_favicon(array('favicon', 'mobile')) ?>
    <?php tpl_includeFile('meta.html') ?>
    
    <!-- Anpassung Grünstadt -->
	<link rel="stylesheet" type="text/css" href="<?php echo tpl_basedir(); ?>css/kkh.css">
    <style>
    html {
        <?php if (isset($conf['tpl']['kkh']['color background'])) echo "background: ".$conf['tpl']['kkh']['color background'].";";?>
    }
    
    #dokuwiki__header {
        box-shadow: 0 40px 0px 0 <?php echo $conf['tpl']['kkh']['color header']?>;
        background-color: <?php echo $conf['tpl']['kkh']['color header']?>;
        
    }
    
    #dokuwiki__content h1{
        background-color:<?php echo $conf['tpl']['kkh']['color h1']?> !important;
    }
    
    <?php if ($conf['tpl']['kkh']['transparent start']===1): ?>
    /* Elemente auf Startseite ausblenden */
    .home .docInfo, .home .pageId, .home #dokuwiki__pagetools {display:none !important;}


    .home.mode_show #dokuwiki__content .page.group {
        background:none;border:none;box-shadow:none;
    }
    
    .home #plugin__approve {display:none;}
    <?php endif; ?>
    
    <?php if ($conf['tpl']['kkh']['animation']===1): ?>
    .nohome #dokuwiki__site .mode_show #dokuwiki__content .pad .group {
        animation: rollin 0.7s forwards;
        position:relative;
        left:100vw;	
        animation-delay:0.9s;
    }
    
    .startpage #dokuwiki__site .mode_show #dokuwiki__content .pad .group {
        animation: fadein 1.1s forwards;
    }
    
    .nohome .pageId, .nohome #dokuwiki__pagetools {
        visibility:hidden;
        animation:visiblein 1.7s forwards;
    }
    <?php endif; ?>
    
    <?php 
    # Styles for the button plugin
    if ($conf['tpl']['kkh']['css plugin button']===1) echo file_get_contents(DOKU_TPLINC.'css/plugins/button.css');
    ?>
    
    </style>
	<!------------------------->

</head>

<body <?php echo 'class="'.($ID!="start"? 'nohome':'startpage').'"';?>>
	
	<!-- Anpassung Grünstadt -->
	<?php if(strpos(tpl_classes(),'mode_show') !== false): ?>
	<?php 
	# Set defaults
	if (!isset($conf['tpl']['kkh']['logo_image']) || $conf['tpl']['kkh']['logo_image']=='') $conf['tpl']['kkh']['logo_image'] = "wiki:logo.png";
    
	# Check if configured loader image exists. Otherwise: Use standard image of the template
    $loader_img = DOKU_INC.'data/media/'.str_replace(':','/',$conf['tpl']['kkh']['loader_image']);
    if (!isset($conf['tpl']['kkh']['loader_image']) || 
        $conf['tpl']['kkh']['loader_image']=='' ||
        !file_exists($loader_img)
       ) {$loader_img = tpl_basedir().'images/loader.gif';} else
         {$loader_img = 'lib/exe/fetch.php?media='.$conf['tpl']['kkh']['loader_image'];}
	?>
	<div class="branding">
		<img class="logo" src="lib/exe/fetch.php?media=<?php echo $conf['tpl']['kkh']['logo_loader']; ?>"> 
		<img class="loader" src="<?php echo $loader_img ?>">
		<span><?php if ($conf['tpl']['kkh']['show_title']===1) echo $conf['title']; ?></span>
	</div>
	<?php endif; ?>
	<!------------------------->
	
    <div id="dokuwiki__site"><div id="dokuwiki__top" class="site <?php echo tpl_classes(); ?> <?php
        echo ($showSidebar) ? 'showSidebar' : ''; ?> <?php echo ($hasSidebar) ? 'hasSidebar' : ''; ?>">

        <?php include('tpl_header.php') ?>

        <div class="wrapper group">

            <?php if($showSidebar): ?>
                <!-- ********** ASIDE ********** -->
                <div id="dokuwiki__aside"><div class="pad aside include group">
                    <h3 class="toggle"><?php echo $lang['sidebar'] ?></h3>
                    <div class="content"><div class="group">
                        <?php tpl_flush() ?>
                        <?php tpl_includeFile('sidebarheader.html') ?>
                        <?php tpl_include_page($conf['sidebar'], true, true) ?>
                        <?php tpl_includeFile('sidebarfooter.html') ?>
                        <?php
                        ############# Gothe ###############
						if ($INFO['editable']) {
							echo "<hr>
								  <div style='padding-left:1em;padding-bottom:1em;'>
								  <a href='".DOKU_BASE."doku.php?id=".$INFO['namespace'].":sidebar'>Menü anzeigen/bearbeiten</a>
								  </div>
								 ";
						}
						###################################
                        ?>
                    </div></div>
                </div></div><!-- /aside -->
            <?php endif; ?>

            <!-- ********** CONTENT ********** -->
            <div id="dokuwiki__content"><div class="pad group">
				
                <?php html_msgarea() ?>

                <div class="pageId"><span><?php echo hsc($ID) ?></span></div>

                <div class="page group">
                    <?php tpl_flush() ?>
                    <?php tpl_includeFile('pageheader.html') ?>
                    <!-- wikipage start -->
                    <?php tpl_content() ?>
                    <!-- wikipage stop -->
                    <?php tpl_includeFile('pagefooter.html') ?>
                </div>
				
                <div class="docInfo"><?php tpl_pageinfo() ?></div>

                <?php tpl_flush() ?>
            </div></div><!-- /content -->

            <hr class="a11y" />

            <!-- PAGE ACTIONS -->
            <div id="dokuwiki__pagetools">
                <h3 class="a11y"><?php echo $lang['page_tools']; ?></h3>
                <div class="tools">
                    <ul>
                        <?php echo (new \dokuwiki\Menu\PageMenu())->getListItems(); ?>
                    </ul>
                </div>
            </div>
        </div><!-- /wrapper -->

        <?php include('tpl_footer.php') ?>
    </div></div><!-- /site -->

    <div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?></div>
    <div id="screen__mode" class="no"></div><?php /* helper to detect CSS media query in script.js */ ?>
        
    <?php if ($conf['tpl']['kkh']['slider show']): ?>
    <!-- Slider -->
	<div class="rightslider" style="top:300px;background:white;">
		<div class="rightslider_toggle"><img src="<?php echo tpl_basedir(); ?>images/external_link.png"></div>
		<div class="kkh_box"><a href="<?php echo $conf['tpl']['kkh']['slider link']; ?>"><?php echo $conf['tpl']['kkh']['slider text'];?></a></div>	
	</div>
    <?php endif; ?>
    
</body>
</html>
