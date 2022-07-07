<?php
/**
 * Template header, included in the main and detail files
 *
 * Modifed by Gero Gothe <practical@medizin-lernen.de>
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();
?>

<!-- ********** HEADER ********** -->
<div id="dokuwiki__header" ><div class="pad group">

    <?php tpl_includeFile('header.html') ?>

    <div class="headings group">
        <h1 class="kkh_template_title"><?php
            // get logo either out of the template images folder or data/media folder
            $logoSize = array();
            $logo = tpl_getMediaFile(array(':wiki:logo.png', ':logo.png', 'images/logo.png'), false, $logoSize);
			if ($conf['tpl']['kkh']['logo_image'] != "") $logo = DOKU_URL."lib/exe/fetch.php?media=".$conf['tpl']['kkh']['logo_image'];

            // display logo and wiki title in a link to the home page
            if ($conf['tpl']['kkh']['logo link'] == '') $conf['tpl']['kkh']['logo link'] = wl();
            tpl_link(
                $conf['tpl']['kkh']['logo link'],
                '<img src="'.$logo.'" alt="" /> ',
                'accesskey="h" title="[H]"'
            );
            echo '<a href="'.wl().'"><span>'.$conf['title'].'</span></a>';
        ?></h1>
        <?php if ($conf['tagline']): ?>
            <p class="claim"><?php echo $conf['tagline']; ?></p>
        <?php endif ?>
    </div>

    <div class="tools group">
        <!-- USER TOOLS -->
        <?php if ($conf['useacl']): ?>
            <div id="dokuwiki__usertools">
                <span class="a11y"><?php echo $lang['user_tools']; ?></span>
                <span>
                    <?php
                        if (!empty($_SERVER['REMOTE_USER'])) {
                            echo '<li class="user">';
                            tpl_userinfo(); /* 'Logged in as ...' */
                            echo '</li>';
                        }
                        echo (new \dokuwiki\Menu\UserMenu())->getListItems('action ');
                        
                    ?>
                    
                </span>
            </div>
        <?php endif ?>

        <!-- SITE TOOLS -->
        <div id="dokuwiki__sitetools">
            <h3 class="a11y"><?php echo $lang['site_tools']; ?></h3>
            
            <!-- Anpassung Grünstadt -->
            <!-- Suche-Formular entfernt und mit AddNew-Page-Zeile ersetzt -->
            <?php 
				$list = plugin_list();
				if(in_array('addnewpage',$list) && $conf['tpl']['kkh']['hide search']===1) {
                    echo p_render('xhtml',p_get_instructions('{{NEWPAGE}}'),$info);
                } elseif ($conf['tpl']['kkh']['hide search']==0) tpl_searchform();
			?>	
            <!------------------------->
            
            <div class="mobileTools">
                <?php echo (new \dokuwiki\Menu\MobileMenu())->getDropdown($lang['tools']); ?>
            </div>
            <ul>
                <?php echo (new \dokuwiki\Menu\SiteMenu())->getListItems('action ', false); ?>
            </ul>
        </div>
    </div>

   



    <hr class="a11y" />
</div></div><!-- /header -->
