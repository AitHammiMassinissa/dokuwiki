<?php
/**
 * Template footer, included in the main and detail files
 *
 * Modifed by Gero Gothe <practical@medizin-lernen.de>
 */

// must be run from within DokuWiki
if (!defined('DOKU_INC')) die();
?>

 <!-- BREADCRUMBS -->
    <?php if($conf['breadcrumbs'] || $conf['youarehere']): ?>
        <div class="breadcrumbs">
            
            <?php if($conf['breadcrumbs']): ?>
                <div class="trace"><?php tpl_breadcrumbs() ?></div>
                
            <?php endif ?>
            <?php if($conf['youarehere']): ?>
                <div class="youarehere">
					
					<?php tpl_youarehere() ?>
				</div>
            <?php endif ?>
        </div>
    <?php endif ?>



<!-- ********** FOOTER ********** -->
<div id="dokuwiki__footer"><div class="pad">
    <?php tpl_license(''); // license text ?>

    <div class="buttons">
        <?php
            tpl_license('button', true, false, false); // license button, no wrapper
            $target = ($conf['target']['extern']) ? 'target="'.$conf['target']['extern'].'"' : '';
        ?>
        <a href="https://www.dokuwiki.org/donate" title="Donate" <?php echo $target?>><img
            src="<?php echo tpl_basedir(); ?>images/button-donate.gif" width="80" height="15" alt="Donate" /></a>
        <a href="https://php.net" title="Powered by PHP" <?php echo $target?>><img
            src="<?php echo tpl_basedir(); ?>images/button-php.gif" width="80" height="15" alt="Powered by PHP" /></a>
        <a href="//validator.w3.org/check/referer" title="Valid HTML5" <?php echo $target?>><img
            src="<?php echo tpl_basedir(); ?>images/button-html5.png" width="80" height="15" alt="Valid HTML5" /></a>
        <a href="//jigsaw.w3.org/css-validator/check/referer?profile=css3" title="Valid CSS" <?php echo $target?>><img
            src="<?php echo tpl_basedir(); ?>images/button-css.png" width="80" height="15" alt="Valid CSS" /></a>
        <a href="https://dokuwiki.org/" title="Driven by DokuWiki" <?php echo $target?>><img
            src="<?php echo tpl_basedir(); ?>images/button-dw.png" width="80" height="15" alt="Driven by DokuWiki" /></a>
            
        
    </div>
	
	<br><?php echo '<span style="color:gray;font-size:8px;">'.$_SERVER['HTTP_USER_AGENT'].' IP:'.$_SERVER['REMOTE_ADDR'].'</span>'; ?>
    
    
</div></div><!-- /footer -->

<?php
tpl_includeFile('footer.html');
