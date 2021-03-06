<?php
/**
 * DokuWiki Syntax Plugin Combostrap.
 *
 */

use ComboStrap\CallStack;
use ComboStrap\ColorRgb;
use ComboStrap\Dimension;
use ComboStrap\DokuPath;
use ComboStrap\ExceptionCombo;
use ComboStrap\FileSystems;
use ComboStrap\Icon;
use ComboStrap\LogUtility;
use ComboStrap\PluginUtility;
use ComboStrap\Site;
use ComboStrap\SvgDocument;
use ComboStrap\TagAttributes;


require_once(__DIR__ . '/../ComboStrap/PluginUtility.php');

/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 *
 * The name of the class must follow a pattern (don't change it)
 * ie:
 *    syntax_plugin_PluginName_ComponentName
 *
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!!!!!!!!! The component name must be the name of the php file !!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 *
 * https://icons.getbootstrap.com/
 * https://remixicon.com/
 */
class syntax_plugin_combo_icon extends DokuWiki_Syntax_Plugin
{
    const TAG = "icon";
    const CANONICAL = self::TAG;
    const ICON_NAME_ATTRIBUTE = "name";

    private static function exceptionHandling(Exception $e, $tagAttribute): string
    {
        $errorClass = syntax_plugin_combo_media::SVG_RENDERING_ERROR_CLASS;
        $message = "Icon ({$tagAttribute->getValue("name")}). Error while rendering: {$e->getMessage()}";
        $html = "<span class=\"text-alert $errorClass\">" . hsc(trim($message)) . "</span>";
        if (!PluginUtility::isTest()) {
            LogUtility::msg($message, LogUtility::LVL_MSG_WARNING, self::CANONICAL);
        }
        return $html;
    }


    /**
     * Syntax Type.
     *
     * Needs to return one of the mode types defined in $PARSER_MODES in parser.php
     * @see DokuWiki_Syntax_Plugin::getType()
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * @return array
     * Allow which kind of plugin inside
     *
     * No one of array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs')
     * because we manage self the content and we call self the parser
     */
    public function getAllowedTypes()
    {
        // You can't put anything in a icon
        return array('formatting');
    }

    /**
     * How Dokuwiki will add P element
     *
     *  * 'normal' - The plugin can be used inside paragraphs
     *  * 'block'  - Open paragraphs need to be closed before plugin output - block should not be inside paragraphs
     *  * 'stack'  - Special case. Plugin wraps other paragraphs. - Stacks can contain paragraphs
     *
     * @see DokuWiki_Syntax_Plugin::getPType()
     */
    function getPType()
    {
        return 'normal';
    }

    /**
     * @see Doku_Parser_Mode::getSort()
     * the mode with the lowest sort number will win out
     * the lowest in the tree must have the lowest sort number
     * No idea why it must be low but inside a teaser, it will work
     * https://www.dokuwiki.org/devel:parser#order_of_adding_modes_important
     */
    function getSort()
    {
        return 10;
    }

    /**
     * Create a pattern that will called this plugin
     *
     * @param string $mode
     * @see Doku_Parser_Mode::connectTo()
     */
    function connectTo($mode)
    {


        $specialPattern = PluginUtility::getEmptyTagPattern(self::TAG);
        $this->Lexer->addSpecialPattern($specialPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));

        /**
         * The content is used to add a {@link syntax_plugin_combo_tooltip}
         */
        $entryPattern = PluginUtility::getContainerTagPattern(self::TAG);
        $this->Lexer->addEntryPattern($entryPattern, $mode, PluginUtility::getModeFromTag($this->getPluginComponent()));


    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern('</' . self::TAG . '>', PluginUtility::getModeFromTag($this->getPluginComponent()));
    }


    /**
     *
     * The handle function goal is to parse the matched syntax through the pattern function
     * and to return the result for use in the renderer
     * This result is always cached until the page is modified.
     * @param string $match
     * @param int $state
     * @param int $pos
     * @param Doku_Handler $handler
     * @return array|bool
     * @throws Exception
     * @see DokuWiki_Syntax_Plugin::handle()
     *
     */
    function handle($match, $state, $pos, Doku_Handler $handler)
    {

        switch ($state) {

            case DOKU_LEXER_SPECIAL:
            case DOKU_LEXER_ENTER:
                // Get the parameters
                $knownTypes = [];
                $defaultAttributes = [];
                $tagAttributes = TagAttributes::createFromTagMatch($match, $defaultAttributes, $knownTypes);
                $callStack = CallStack::createFromHandler($handler);
                $parent = $callStack->moveToParent();
                $context = "";
                if ($parent !== false) {
                    $context = $parent->getTagName();
                    if ($context === syntax_plugin_combo_link::TAG) {
                        $context = $parent->getTagName();
                    }
                }
                /**
                 * Color setting should know the color of its parent
                 * For now, we don't set any color if the parent is a button, note, link
                 * As a header is not a parent, we may say that if the icon is contained, the default
                 * branding color is not set ?
                 */
                $requestedColor = $tagAttributes->getValue(ColorRgb::COLOR);
                if (
                    $requestedColor === null &&
                    Site::isBrandingColorInheritanceEnabled() &&
                    !in_array($context, [
                        syntax_plugin_combo_button::TAG,
                        syntax_plugin_combo_note::TAG,
                        syntax_plugin_combo_link::TAG
                    ])
                ) {
                    $requestedWidth = $tagAttributes->getValue(Dimension::WIDTH_KEY, SvgDocument::DEFAULT_ICON_WIDTH);
                    $requestedWidthInPx = Dimension::toPixelValue($requestedWidth);
                    if ($requestedWidthInPx > 36) {
                        // Illustrative icon
                        $color = Site::getPrimaryColor();
                    } else {
                        // Character icon
                        $color = Site::getSecondaryColor();
                    }
                    if ($color !== null) {
                        $tagAttributes->setComponentAttributeValue(ColorRgb::COLOR, $color);
                    }
                }
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $tagAttributes->toCallStackArray(),
                    PluginUtility::CONTEXT => $context
                );
            case DOKU_LEXER_EXIT:
                $callStack = CallStack::createFromHandler($handler);
                $openingCall = $callStack->moveToPreviousCorrespondingOpeningCall();
                return array(
                    PluginUtility::STATE => $state,
                    PluginUtility::ATTRIBUTES => $openingCall->getAttributes(),
                    PluginUtility::CONTEXT => $openingCall->getContext()
                );


        }

        return array();

    }

    /**
     * Render the output
     * @param string $format
     * @param Doku_Renderer $renderer
     * @param array $data - what the function handle() return'ed
     * @return boolean - rendered correctly? (however, returned value is not used at the moment)
     * @see DokuWiki_Syntax_Plugin::render()
     *
     *
     */
    function render($format, Doku_Renderer $renderer, $data): bool
    {

        switch ($format) {

            case 'xhtml':
                {
                    /** @var Doku_Renderer_xhtml $renderer */
                    $state = $data[PluginUtility::STATE];
                    switch ($state) {


                        case DOKU_LEXER_SPECIAL:
                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                            $renderer->doc .= $this->printIcon($tagAttributes);
                            break;
                        case DOKU_LEXER_ENTER:

                            $tagAttributes = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                            $tooltip = $tagAttributes->getValueAndRemoveIfPresent(\ComboStrap\Tooltip::TOOLTIP_ATTRIBUTE);
                            if ($tooltip !== null) {
                                /**
                                 * If there is a tooltip, we need
                                 * to start with a span to wrap the svg with it
                                 */


                                $tooltipTag = TagAttributes::createFromCallStackArray([\ComboStrap\Tooltip::TOOLTIP_ATTRIBUTE => $tooltip])
                                    ->addClassName(syntax_plugin_combo_tooltip::TOOLTIP_CLASS_INLINE_BLOCK);
                                $renderer->doc .= $tooltipTag->toHtmlEnterTag("span");
                            }
                            /**
                             * Print the icon
                             */
                            $renderer->doc .= $this->printIcon($tagAttributes);
                            /**
                             * Close the span if we are in a tooltip context
                             */
                            if ($tooltip !== null) {
                                $renderer->doc .= "</span>";
                            }

                            break;
                        case DOKU_LEXER_EXIT:

                            break;
                    }

                }
                break;
            case 'metadata':
                /**
                 * @var Doku_Renderer_metadata $renderer
                 */
                $tagAttribute = TagAttributes::createFromCallStackArray($data[PluginUtility::ATTRIBUTES]);
                try {
                    $name = $tagAttribute->getValueAndRemoveIfPresent("name");
                    if ($name === null) {
                        throw new ExceptionCombo("The attributes should have a name. It's mandatory for an icon.", self::CANONICAL);
                    }
                    $mediaPath = Icon::create($name, $tagAttribute)->getPath();
                } catch (ExceptionCombo $e) {
                    // error is already fired in the renderer
                    return false;
                }
                if ($mediaPath instanceof DokuPath && FileSystems::exists($mediaPath)) {
                    $mediaId = $mediaPath->getDokuwikiId();
                    syntax_plugin_combo_media::registerFirstMedia($renderer, $mediaId);
                }
                break;

        }
        return true;
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return string
     */
    private function printIcon(TagAttributes $tagAttributes): string
    {
        try {
            $name = $tagAttributes->getValue("name");
            if ($name === null) {
                throw new ExceptionCombo("The attributes should have a name. It's mandatory for an icon.", self::CANONICAL);
            }
            return Icon::create($name, $tagAttributes)
                ->render();
        } catch (ExceptionCombo $e) {
            return self::exceptionHandling($e, $tagAttributes);
        }
    }


}
