<?php

use ComboStrap\CacheManager;
use ComboStrap\ExceptionCombo;
use ComboStrap\HtmlDocument;
use ComboStrap\LogUtility;
use ComboStrap\Page;
use ComboStrap\PluginUtility;
use ComboStrap\RenderUtility;
use ComboStrap\SnippetManager;

if (!defined('DOKU_INC')) die();

/**
 *
 *
 * Add the snippet needed by the components
 *
 */
class action_plugin_combo_snippets extends DokuWiki_Action_Plugin
{

    const CLASS_SNIPPET_IN_CONTENT = "snippet-content-combo";

    /**
     * @var bool - to trace if the header output was called
     */
    private $headerOutputWasCalled = false;

    function __construct()
    {
        // enable direct access to language strings
        // ie $this->lang
        $this->setupLocale();
    }

    public function register(Doku_Event_Handler $controller)
    {

        /**
         * To add the snippets in the header
         */
        $controller->register_hook('TPL_METAHEADER_OUTPUT', 'BEFORE', $this, 'componentSnippetHead', array());

        /**
         * To add the snippets in the content
         * if they have not been added to the header
         *
         * Not https://www.dokuwiki.org/devel:event:tpl_content_display TPL_ACT_RENDER
         * or https://www.dokuwiki.org/devel:event:tpl_act_render
         * because it works only for the main content
         * in {@link tpl_content()}
         *
         * We use
         * https://www.dokuwiki.org/devel:event:renderer_content_postprocess
         * that is in {@link p_render()} and takes into account also the slot page.
         */
        $controller->register_hook('RENDERER_CONTENT_POSTPROCESS', 'AFTER', $this, 'componentSnippetContent', array());

        /**
         * To reset the value
         */
        $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this, 'close', array());


    }

    /**
     * Reset variable
     * Otherwise in test, when we call it two times, it just fail
     */
    function close()
    {

        $this->headerOutputWasCalled = false;

        /**
         * Fighting the fact that in 7.2,
         * there is still a cache
         */
        SnippetManager::reset();

    }

    /**
     * Dokuwiki has already a canonical methodology
     * https://www.dokuwiki.org/canonical
     *
     * @param $event
     */
    function componentSnippetHead($event)
    {


        global $ID;
        if (empty($ID)) {

            global $_SERVER;
            $scriptName = $_SERVER['SCRIPT_NAME'];

            /**
             * If this is an ajax call, return
             * only if this not from webcode
             */
            if (strpos($scriptName, "/lib/exe/ajax.php") !== false) {
                global $_REQUEST;
                $call = $_REQUEST['call'];
                if ($call != action_plugin_combo_webcode::CALL_ID) {
                    return;
                }
            } else if (!(strpos($scriptName, "/lib/exe/detail.php") !== false)) {
                /**
                 * Image page has an header and footer that may needs snippet
                 * We return only if this is not a image/detail page
                 */
                return;
            }
        }

        /**
         * Advertise that the header output was called
         * If the user is using another template
         * than strap that does not put the component snippet
         * in the head
         * Used in
         */
        $this->headerOutputWasCalled = true;

        $snippetManager = PluginUtility::getSnippetManager();

        /**
         * For each processed slot in the page, retrieve the snippets
         */
        $cacheReporters = CacheManager::getOrCreate()->getCacheResults();
        if ($cacheReporters !== null) {
            foreach ($cacheReporters as $cacheReporter) {

                foreach ($cacheReporter->getResults() as $report) {

                    if ($report->getMode() !== HtmlDocument::mode) {
                        continue;
                    }

                    $slotId = $report->getSlotId();
                    Page::createPageFromId($slotId)
                        ->getHtmlDocument()
                        ->loadSnippets();

                }


            }
        }
        /**
         * Snippets
         * (Slot and request snippets)
         */
        try {
            $allSnippets = $snippetManager->getAllSnippetsToDokuwikiArray();
        } catch (ExceptionCombo $e) {
            LogUtility::msg("Error: We couldn't add the snippets in the head. Error: {$e->getMessage()}");
            return;
        }
        foreach ($allSnippets as $tagType => $tags) {

            foreach ($tags as $tag) {
                $event->data[$tagType][] = $tag;
            }

        }

        $snippetManager->close();

    }

    /**
     * Used if the template does not run the content
     * before the calling of the header as strap does.
     *
     * In this case, the {@link \ComboStrap\SnippetManager::close()} has
     * not run, and the snippets are still in memory.
     *
     * We store them in the HTML and they
     * follows then the HTML cache of DokuWiki
     * @param $event
     */
    function componentSnippetContent($event)
    {

        $format = $event->data[0];
        if ($format !== "xhtml") {
            return;
        }

        /**
         * Add snippet in the content
         *  - if the header output was already called
         *  - if this is not a page rendering (ie an admin rendering)
         * for instance, the upgrade plugin call {@link p_cached_output()} on local file
         */
        global $ACT;
        if ($ACT === RenderUtility::DYNAMIC_RENDERING) {
            return;
        }
        $putSnippetInContent =
            $this->headerOutputWasCalled
            ||
            ($ACT !== "show" && $ACT !== null); // admin page rendering
        if ($putSnippetInContent) {

            $snippetManager = PluginUtility::getSnippetManager();
            $xhtmlContent = &$event->data[1];
            try {
                $snippets = $snippetManager->getAllSnippetsToDokuwikiArray();
            } catch (ExceptionCombo $e) {
                LogUtility::msg("Error: We couldn't add the snippets in the content. Error: {$e->getMessage()}");
                return;
            }
            if (sizeof($snippets) > 0) {

                $class = self::CLASS_SNIPPET_IN_CONTENT;
                $xhtmlContent .= "<div class=\"$class\">\n";
                foreach ($snippets as $htmlElement => $tags) {

                    foreach ($tags as $tag) {
                        $xhtmlContent .= DOKU_LF . "<$htmlElement";
                        $attributes = "";
                        $content = null;

                        /**
                         * This code runs in editing mode
                         * or if the template is not strap
                         * No preload is then supported
                         */
                        if ($htmlElement === "link") {
                            $relValue = $tag["rel"];
                            $relAs = $tag["as"];
                            if ($relValue === "preload") {
                                if ($relAs === "style") {
                                    $tag["rel"] = "stylesheet";
                                    unset($tag["as"]);
                                }
                            }
                        }

                        /**
                         * Print
                         */
                        foreach ($tag as $attributeName => $attributeValue) {
                            if ($attributeName !== "_data") {
                                $attributes .= " $attributeName=\"$attributeValue\"";
                            } else {
                                $content = $attributeValue;
                            }
                        }
                        $xhtmlContent .= "$attributes>";
                        if (!empty($content)) {
                            $xhtmlContent .= $content;
                        }
                        $xhtmlContent .= "</$htmlElement>" . DOKU_LF;
                    }

                }
                $xhtmlContent .= "</div>\n";

            }

            $snippetManager->close();

        }

    }


}
