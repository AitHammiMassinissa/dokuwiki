<?php
/**
 * DokuWiki Pinboard Template
 * Based on the starter template and a wordpress theme of the same name
 *
 * @link     http://dokuwiki.org/template:mywiki
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
    <link rel="preconnect" href="https://fonts.gstatic.com"> 
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300;0,400;0,600;0,700;0,800;1,300;1,400;1,700;1,800&family=Oswald:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<?php if ($showSidebar){
    $contentclass = "twothirdcol";
} else { 
    $contentclass = "onecol";
}
?>


<body class="site <?php echo tpl_classes(); ?> <?php echo ($showSidebar) ? 'hasSidebar' : ''; ?>">
    <?php /* with these Conditional Comments you can better address IE issues in CSS files,
             precede CSS rules by #IE8 for IE8 (div closes at the bottom) */ ?>
    <!--[if lte IE 8 ]><div id="IE8"><![endif]-->

    <?php /* the "dokuwiki__top" id is needed somewhere at the top, because that's where the "back to top" button/link links to */ ?>
    <?php /* tpl_classes() provides useful CSS classes; if you choose not to use it, the 'dokuwiki' class at least
             should always be in one of the surrounding elements (e.g. plugins and templates depend on it) */ ?>

    <!-- <div id="dokuwiki__site"> --><div id="wrapper"><div id="dokuwiki__top">
    <?php tpl_includeFile('header.html') ?>

    <header id="header">
            <div id="site-title"><?php tpl_link(wl(),$conf['title'],'accesskey="h" title="[H]"') ?></div>
            <?php /* how to insert logo instead (if no CSS image replacement technique is used):
                    upload your logo into the data/media folder (root of the media manager) and replace 'logo.png' accordingly:
                    tpl_link(wl(),'<img src="'.ml('logo.png').'" alt="'.$conf['title'].'" />','id="dokuwiki__top" accesskey="h" title="[H]"') */ ?>
            <?php if ($conf['tagline']): ?>
                <div id="site-description"><?php echo $conf['tagline'] ?></div>
            <?php endif ?>

            <?php tpl_searchform() ?>

            <ul class="a11y skip">
                <li><a href="#dokuwiki__content"><?php echo $lang['skip_to_content'] ?></a></li>
            </ul>

            <div class="clear"></div>

            <nav id="access">
                <a class="nav-show" href="#access">Show Navigation</a>
                <a class="nav-hide" href="#nogo">Hide Navigation</a>
                <div class="menu"><ul>
                       <!-- USER TOOLS -->
                    <?php tpl_toolsevent('usertools', array(
                        'admin'     => tpl_action('admin', 1, 'li', 1),
                        'userpage'  => _tpl_action('userpage', 1, 'li', 1),
                        'profile'   => tpl_action('profile', 1, 'li', 1),
                        'register'  => tpl_action('register', 1, 'li', 1),
                        'login'     => tpl_action('login', 1, 'li', 1),
                    )); ?>
                </ul></div>
                <div class="menu"><ul>

                <?php /* the optional second parameter of tpl_action() switches between a link and a button,
                             e.g. a button inside a <li> would be: tpl_action('edit', 0, 'li') */
                    ?>

                 
                    <!-- SITE TOOLS -->
                    <?php tpl_toolsevent('sitetools', array(
                    'recent'    => tpl_action('recent', 1, 'li', 1),
                    'media'     => tpl_action('media', 1, 'li', 1),
                    'index'     => tpl_action('index', 1, 'li', 1),
                )); ?>

                <!-- PAGE ACTIONS -->
                <?php if ($showTools): ?>
                    <!-- <div id="dokuwiki__pagetools"> -->
                        <!-- <h3 class="a11y"><?php //echo $lang['page_tools'] ?></h3> -->
                        <!-- <ul> -->
                            <?php tpl_toolsevent('pagetools', array(
                                'edit'      => tpl_action('edit', 1, 'li', 1),
                                'discussion'=> _tpl_action('discussion', 1, 'li', 1),
                                'revisions' => tpl_action('revisions', 1, 'li', 1),
                                'backlink'  => tpl_action('backlink', 1, 'li', 1),
                                'subscribe' => tpl_action('subscribe', 1, 'li', 1),
                                'revert'    => tpl_action('revert', 1, 'li', 1),
                                //'top'       => tpl_action('top', 1, 'li', 1),
                            )); ?>
                        <!-- </ul> -->
                    <!-- </div> -->
                <?php endif; ?>
                </ul></div>
                <div class="clear"></div>
            </nav>

            <div class="tools">
            <?php if ($conf['useacl'] && $showTools): ?>
                <!-- <div id="dokuwiki__usertools"> --><div class="breadcrumbs">
                <h3 class="a11y"><?php echo $lang['user_tools'] ?></h3>
                <ul class="resetlist">
                <?php
                if (!empty($_SERVER['REMOTE_USER'])) {
                echo '<li class="user">';
                tpl_userinfo(); /* 'Logged in as ...' */
                echo '</li>';
                }
                ?>
                </ul>
                </div><!-- </div> -->
            <?php endif ?>
      
        <div id="dokuwiki__sitetools">
            <h3 class="a11y"><?php echo $lang['site_tools'] ?></h3>
        </div>

        <div class="clearer"></div>

        <!-- BREADCRUMBS -->
        <?php if($conf['breadcrumbs']){ ?>
            <div class="breadcrumbs"><?php tpl_breadcrumbs() ?></div>
        <?php } ?>
        <?php if($conf['youarehere']){ ?>
            <div class="breadcrumbs"><?php tpl_youarehere() ?></div>
        <?php } ?>

        <div class="clearer"></div>
        <hr class="a11y" />

    </div>
    </header>

    <div id="container">

        <section id="content" class="column <?=$contentclass;?>"><article id="post-9" class="post-9 post type-post status-publish format-standard has-post-thumbnail hentry category-uncategorized column onecol"><?php html_msgarea() /* occasional error and info messages on top of the page */ ?><div class="entry">



    <!-- ********** CONTENT ********** -->
            <div id="dokuwiki__content">
                <?php tpl_flush() /* flush the output buffer */ ?>
                <?php tpl_includeFile('pageheader.html') ?>

                <div class="page">
                    <!-- wikipage start -->
                    <?php tpl_content() /* the main content */ ?>
                    <!-- wikipage stop -->
                    <div class="clearer"></div>
                </div>

                <?php tpl_flush() ?>
                <?php tpl_includeFile('pagefooter.html') ?>
            </div><!-- /content -->

            <div class="clearer"></div>
            <hr class="a11y" />

            
    </div></article></section>

    <div id="sidebar" class="column threecol">
    <div class="column twocol">
    <div id="sidebar-right" class="widget-area" role="complementary">
    <div class="column onecol">
        <!-- <aside id="categories-13" class="widget"><h3 class="widget-title">Categories</h3>      <ul>
        <li class="cat-item"><a href="http://localhost/wordpress/category/uncategorized/">Uncategorized</a></li>
        </ul>
        </aside> -->

        <aside id="writtensidebar" class="widget"><!-- <h3 class="widget-title">Pages</h3> -->
              <?php tpl_includeFile('sidebarheader.html') ?>
              <?php tpl_include_page($conf['sidebar'], 1, 1) /* includes the nearest sidebar page */ ?>
              <?php tpl_includeFile('sidebarfooter.html') ?>
              <div class="clearer"></div>
        </aside><!-- .widget -->
    </div>      
    </div><!-- #sidebar-right -->
    </div><!-- .twocol -->
    </div>


  <div id="footer">
            <div class="no"><?php tpl_indexerWebBug() /* provide DokuWiki housekeeping, required in all templates */ ?></div>
            <div id="copyright">
                <p>&nbsp;</p>
                <div class="doc"><?php tpl_pageinfo() /* 'Last modified' etc */ ?></div>
                <?php tpl_license('button') /* content license, parameters: img=*badge|button|0, imgonly=*0|1, return=*0|1 */ ?>
            </div>
            <!-- ********** FOOTER ********** -->

            <?php tpl_includeFile('footer.html') ?>
            <!--[if lte IE 8 ]></div><![endif]-->
        </div>

</div><!-- end header -->

      


    </div></div>

</body>
</html>
