<?php
/**
 * DokuWiki Arcade Basic Template
 * Based on the starter template and a wordpress theme of the same name
 *
 * @link     http://dokuwiki.org/template:arcadebasic
 * @author   desbest <afaninthehouse@gmail.com>
 * @license  GPL 2 (http://www.gnu.org/licenses/gpl.html)
 */

if (!defined('DOKU_INC')) die(); /* must be run from within DokuWiki */
@require_once(dirname(__FILE__).'/tpl_functions.php'); /* include hook for template functions */
header('X-UA-Compatible: IE=edge,chrome=1');

$showTools = !tpl_getConf('hideTools') || ( tpl_getConf('hideTools') && !empty($_SERVER['REMOTE_USER']) );
$showSidebar = page_findnearest($conf['sidebar']) && ($ACT=='show');
?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo $conf['lang'] ?>"
  lang="<?php echo $conf['lang'] ?>" dir="<?php echo $lang['direction'] ?>" class="no-js">
<head>
    <meta charset="UTF-8" />
    <title><?php tpl_pagetitle() ?> [<?php echo strip_tags($conf['title']) ?>]</title>
    <script>(function(H){H.className=H.className.replace(/\bno-js\b/,'js')})(document.documentElement)</script>
    <?php tpl_metaheaders() ?>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <?php echo tpl_favicon(array('favicon', 'mobile')) ?>
    <?php tpl_includeFile('meta.html') ?>
    <link rel="stylesheet" id="arcade-basic-fonts-css" href="//fonts.googleapis.com/css?family=Megrim|Raleway|Open+Sans:400,400italic,700,700italic" type="text/css" media="all">
</head>

<body id="dokuwiki__top" class="basic site <?php echo tpl_classes(); ?> <?php echo ($showSidebar) ? 'hasSidebar' : ''; ?>">
    <?php /* with these Conditional Comments you can better address IE issues in CSS files,
             precede CSS rules by #IE8 for IE8 (div closes at the bottom) */ ?>
    <!--[if lte IE 8 ]><div id="IE8"><![endif]-->

    <?php /* the "dokuwiki__top" id is needed somewhere at the top, because that's where the "back to top" button/link links to */ ?>
    <?php /* tpl_classes() provides useful CSS classes; if you choose not to use it, the 'dokuwiki' class at least
             should always be in one of the surrounding elements (e.g. plugins and templates depend on it) */ ?>

             <div id="page">

            <?php tpl_includeFile('header.html') ?>
             <header id="header">
            <!-- <nav id="site-navigation" class="navbar navbar-inverse navbar-fixed-top" role="navigation">
                <h3 class="sr-only">Main menu</h3>
                <a class="sr-only" href="#primary" title="Skip to content">Skip to content</a>

                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                </div>

                <div class="collapse navbar-collapse">
                    <ul class="nav navbar-nav"> <li class="cat-item cat-item-1"><a href="http://localhost/wordpress/category/uncategorized/">Uncategorized</a>
</li>
</ul>               </div>
            </nav> --><!-- #site-navigation -->

             <div class="title-card-wrapper">
                <div class="title-card" style="height: 412px;">
                    <div id="site-meta">
                         <?php /* how to insert logo instead (if no CSS image replacement technique is used):
                        upload your logo into the data/media folder (root of the media manager) and replace 'logo.png' accordingly:
                        tpl_link(wl(),'<img src="'.ml('logo.png').'" alt="'.$conf['title'].'" />','id="dokuwiki__top" accesskey="h" title="[H]"') */ ?>
                        <h1 id="site-title"><?php tpl_link(wl(),$conf['title'],'accesskey="h" title="[H]"') ?></h1>

                        <i class="fa fa-heart"></i>
                        
                        <div id="site-description"><?php if ($conf['tagline']): ?>
                            <p class="claim"><?php echo $conf['tagline'] ?></p>
                        <?php endif ?></div>
                        <a href="#" id="more-site" class="btn btn-default btn-lg" data-scroll-to="412">See More</a>
                    </div>

                    <img class="header-img" src="<?php echo tpl_basedir();?>/images/header01.jpg" alt="" style="position: absolute; inset: -222px auto auto 0px; height: auto; width: 100%;">
                </div>
            </div>

        </header>

         <ul class="a11y skip">
            <li><a href="#dokuwiki__content"><?php echo $lang['skip_to_content'] ?></a></li>
        </ul>

        <main>
            <div class="container">
            <div class="row">
            <div id="primary" class="col-md-8 hfeed">
            <?php
            //get_template_part( 'template-parts/content', get_post_format() );
            //the_posts_navigation();
            ?>
                <article class="clearfix">
                <?php

                //get_template_part( 'template-parts/content', 'header' ); ?>

                <div class="entry-content description clearfix">
                    <!-- page content appears here -->
                    <!-- ********** CONTENT ********** -->
                    <?php tpl_flush() /* flush the output buffer */ ?>
                    <?php tpl_includeFile('pageheader.html') ?>

                    <?php html_msgarea() /* occasional error and info messages on top of the page */ ?>

                    <!-- BREADCRUMBS -->
                    <?php if($conf['breadcrumbs']){ ?>
                        <div class="breadcrumbs"><?php tpl_breadcrumbs() ?></div>
                    <?php } ?>
                    <?php if($conf['youarehere']){ ?>
                        <div class="breadcrumbs"><?php tpl_youarehere() ?></div>
                    <?php } ?>

                    <div class="page" id="dokuwiki__content">
                    <!-- wikipage start -->
                    <?php tpl_content() /* the main content */ ?>
                    <!-- wikipage stop -->
                    <div class="clearer"></div>
                    </div>

                    <?php tpl_flush() ?>
                    <?php tpl_includeFile('pagefooter.html') ?>
                </div><!-- .entry-content -->
                <?php //get_template_part( 'template-parts/content', 'footer' ); ?>
                </article>
            </div>
            <!-- sidebar is here -->
                <div id="secondary" class="col-md-4" role="complementary">
                <!-- <aside id="meta" class="widget">
                <h3 class="widget-title">Default Widget</h3>
                <p>This is just a default widget. It'll disappear as soon as you add your own widgets on the widgets admin page.</p> -->

                <div class="searchhere"><?php tpl_searchform() ?></div>


                <!-- ********** ASIDE ********** -->
                <?php if ($showSidebar): ?>
                    <aside id="writtensidebar" class="widget">
                        <?php tpl_includeFile('sidebarheader.html') ?>
                        <?php tpl_include_page($conf['sidebar'], 1, 1) /* includes the nearest sidebar page */ ?>
                        <?php tpl_includeFile('sidebarfooter.html') ?>
                        <div class="clearer"></div>
                    </aside><!-- /aside -->
                <?php endif; ?>

                <?php if ($showTools): ?>
                <aside class="widget">
                <h3 class="widget-title">Page Tools</h3><ul>
                    <!-- PAGE ACTIONS -->
                    <h3 class="a11y"><?php echo $lang['page_tools'] ?></h3>
                    <?php tpl_toolsevent('pagetools', array(
                    'edit'      => tpl_action('edit', 1, 'li', 1),
                    'discussion'=> _tpl_action('discussion', 1, 'li', 1),
                    'revisions' => tpl_action('revisions', 1, 'li', 1),
                    'backlink'  => tpl_action('backlink', 1, 'li', 1),
                    'subscribe' => tpl_action('subscribe', 1, 'li', 1),
                    'revert'    => tpl_action('revert', 1, 'li', 1),
                    //'top'       => tpl_action('top', 1, 'li', 1),
                    )); ?>
                </ul>
                </aside>
                <?php endif; ?>


                <?php if ($conf['useacl'] && $showTools): ?>
                <aside class="widget">        
                <h3 class="widget-title">User Tools</h3><ul>
                    <!-- USER TOOLS -->
                    <h3 class="a11y"><?php echo $lang['user_tools'] ?></h3>
                    <?php
                        if (!empty($_SERVER['REMOTE_USER'])) {
                            echo '<li class="user">';
                            tpl_userinfo(); /* 'Logged in as ...' */
                            echo '</li>';
                        }
                    ?>
                    <?php /* the optional second parameter of tpl_action() switches between a link and a button,
                             e.g. a button inside a <li> would be: tpl_action('edit', 0, 'li') */
                    ?>
                    <?php tpl_toolsevent('usertools', array(
                    'admin'     => tpl_action('admin', 1, 'li', 1),
                    'userpage'  => _tpl_action('userpage', 1, 'li', 1),
                    'profile'   => tpl_action('profile', 1, 'li', 1),
                    'register'  => tpl_action('register', 1, 'li', 1),
                    'login'     => tpl_action('login', 1, 'li', 1),
                    )); ?>
                </ul>
                </aside>
                <?php endif ?>

                <aside class="widget">        
                <h3 class="widget-title">Site Tools</h3><ul>
                <!-- SITE TOOLS -->
                <h3 class="a11y"><?php echo $lang['site_tools'] ?></h3>
                    <?php tpl_toolsevent('sitetools', array(
                        'recent'    => tpl_action('recent', 1, 'li', 1),
                        'media'     => tpl_action('media', 1, 'li', 1),
                        'index'     => tpl_action('index', 1, 'li', 1),
                    )); ?>
                </ul>
                </aside>
            </div><!-- #secondary.widget-area -->
            <!-- sidebar ends -->
            </div>
    </div>
        </main>

        <?php tpl_includeFile('footer.html') ?>
        <footer id="footer" role="contentinfo">
        <div id="footer-content" class="container">
            <div class="row">
                <div class="copyright col-lg-12">
                    <span class="pull-left">
                        Copyright &copy; <?php echo date("Y"); ?> <a href=""><?php tpl_link(wl(),$conf['title'],'accesskey="h" title="[H]"') ?></a>. All rights reserved.
                        <div class="doc"><?php tpl_pageinfo() /* 'Last modified' etc */ ?></div>
                        <?php tpl_license('button') /* content license, parameters: img=*badge|button|0, imgonly=*0|1, return=*0|1 */ ?>
                    </span>

                    <span class="credit-link pull-right"><i class="fa fa-leaf"></i>
                        The Arcade Basic Theme by <a href="http://dokuwiki.org/theme:arcadebasic">bavotasan.com and desbest</a>
                    </span>
                </div><!-- .col-lg-12 -->
            </div><!-- .row -->
        </div><!-- #footer-content.container -->
        <div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?> <!-- this adds extra space on the page --></div>
        </footer><!-- #footer -->



        </div><!-- #page -->

        
</body>
</html>
