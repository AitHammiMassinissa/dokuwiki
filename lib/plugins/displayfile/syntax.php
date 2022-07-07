<?php
/**
 * Display File Dokuwiki Plugin (Syntax Component)
 *
 * Description: The Display File Plugin displays the content of a specified file on the local system using a displayfile element
 *
 * Syntax: <displayfile lang target />
 *         lang   (Required): The language of the content file. This is used by Dokuwiki's built-in syntax highlighting GeSHi
 *                            library. To disable syntax highlighting, specify a dask (-) character for the lang value. The
 *                            supported lang values are the same as those provided by Dokuwiki's <code> and <file> markup and
 *                            can be found on the Dokuwiki syntax page: https://www.dokuwiki.org/wiki:syntax#syntax_highlighting
 *         target (Required): The last part of a file path to the desired file on the local file system. This will be appended to
 *                            the value of the plugin's root_path configuration option. The target value can be enclosed in single
 *                            or double quotes (' or "). The target path part must be enclosed in quotes if it contains spaces.
 *
 * @license    The MIT License (https://opensource.org/licenses/MIT)
 * @author     Jay Jeckel <jeckelmail@gmail.com>
 *
 * Copyright (c) 2016 Jay Jeckel
 * Licensed under the MIT license: https://opensource.org/licenses/MIT
 * Permission is granted to use, copy, modify, and distribute the work.
 * Full license information available in the project LICENSE file.
 */

if(!defined('DOKU_INC')) { die(); }

class syntax_plugin_displayfile extends DokuWiki_Syntax_Plugin
{
    const PATTERN = '<displayfile\s+[a-z0-9_\-]+?\s*.*?\s*\/>';

    function getInfo() { return confToHash(dirname(__FILE__) . '/plugin.info.txt'); }

    function getType() { return 'substition'; }

    function getPType() { return 'block'; }

    function getSort() { return 5; }// Must come before the <code>/<file> patterns are handled at 200/210.

    function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern(self::PATTERN, $mode, 'plugin_displayfile');
        $pattern = '<<' . 'display' . '\s' . 'file' . '\s' . '[a-z0-9_\-]+?' . '\s*' . '.*?' . '>>';
        $this->Lexer->addSpecialPattern($pattern, $mode, 'plugin_displayfile');
    }

    function handle($match, $state, $pos, Doku_Handler $handler)
    {
        $match = $match[1] == '<' ? substr($match, 15, -2) : substr($match, 13, -3);
        list($language, $target) = explode(' ', $match, 2);

        $target = trim($target, " \t\n\r\0\x0B\"'");
        $target = str_replace("\\", '/', $target);
        $target = ltrim($target, '/');

        $target_title = false;
        $title = ($target_title ? $target : basename($target));

        $text = 'CONTENT NOT SET';
        $error = $this->_validate($target, $title, $text);
        return $error === false ? array($text, $language, $title) : array("ERROR: {$error}", null, null);
        //$output = $error === false ? "$language $title>$text" : "->ERROR: $error";
        //$handler->file($output, DOKU_LEXER_UNMATCHED, $pos);
        //return null;
    }

    function render($format, Doku_Renderer $renderer, $data)
    {
        if ($format == 'xhtml')
        {
            list($text, $language, $title) = $data;
            if (!$this->_isPhpFixRequired($text, $language))
            { $renderer->file($text, $language, $title); }
            else
            {
                $this->_displayHeader($renderer, $title);
                $this->_displayBody($renderer, $text, $language);
                $renderer->doc .= '</dd></dl>'.DOKU_LF;
            }
            return true;
        }
        return false;
    }

    // Returns error message or false; if false, then $content holds the file contents.
    function _validate($target, $title, &$content)
    {
        $root = $this->getConf('root_path');
        $root = str_replace("\\", '/', $root);
        $root = rtrim($root, '/');

        // First, validate that the root path is set to something.
        if (!isset($root) || $root === '')
        { return $this->getLang('error_root_empty'); }

        $real_root = realpath($root);

        // Second, check that the root path exists and is a directory.
        if ($real_root === false || !is_dir($real_root))
        { return $this->getLang('error_root_invalid'); }

        // We now know that root exists.

        // Third, defend against simple traversal attacks in the target.
        else if (strpos($target, '../') !== false)
        { return sprintf($this->getLang('error_traversal_token'), $title); }

        $ext = pathinfo($target , PATHINFO_EXTENSION);

        // Fourth, validate deny of extension.
        $deny_extensions = explode(' ', $this->getConf('deny_extensions'));//array('sh');
        if (count($deny_extensions) > 0 && in_array($ext, $deny_extensions))
        { return sprintf($this->getLang('error_extension_deny'), $ext); }

        // Fifth, validate deny of extension.
        $allow_extensions = explode(' ', $this->getConf('allow_extensions'));//array('txt', 'php', 'js', 'css');
        if (count($allow_extensions) > 0 && !in_array($ext, $allow_extensions))
        { return sprintf($this->getLang('error_extension_allow'), $ext); }

        $real_path = realpath($real_root . "/" . $target);

        // Sixth, catch the file trying to read itself.
        // Behavior undefined if this file doesn't exist.
        if (realpath(__FILE__) == $real_path)
        { return sprintf($this->getLang('error_self'), $title); }

        // !!ATTENTION!! Security must be considered in the following sections.
        //
        // The root path is assumed safe as it is provided by the admin,
        // but the target path stub is user-provided input and must be
        // treated as dangerous by default. Specifically, path traversal
        // attacks need to be mitigated.
        //
        // The main threat is use of path traversal shortcuts, such as the
        // '.', '..', and '~' tokens, to cause the resolved path to point at
        // files and directories outside the expected root path. Thankfully,
        // it is fairly easy to detect these attacks by normalizing and expanding
        // both the root and the path using realpath(). If the real path doesn't
        // literally begin with the real root, then you know you have a traversal.
        // Good, then that's done, right? Mostly, but not quite.
        //
        // The quirk is that realpath() only works for files that exist. If the
        // file doesn't exist, then you don't have a real path to compare to the
        // real root. Where the issue appears is when the developer follows their
        // natural instinct and tries to help the user by providing more useful
        // error messages; first check if the file doesn't exist, say as much; then
        // when we know the file exists, another message to say it is invalid if it
        // traverses outside the root.
        //
        // And there it is, you've opened a hole into the underlying system. What
        // we've done is give an attacker the ability to check if any arbitrary File
        // does or doesn't exist on whatever system is running the plugin. I know
        // that doesn't seem like much, but by checking the existence of files an
        // attacker can determine what OS the system is running, down to the specific
        // version of the OS, and that could inform more tailored attacks.
        //
        // Fortunately, there is a simple fix for this: deny the urge to be helpful.
        // Don't provide separate messages for the files that don't exist and files
        // that exist but have traversed outside the root path. This way the only
        // thing an attacker knows is that they can't access a file, nothing more, and
        // that is all they need to know. For good measure, all invalid paths should
        // return this same message to avoid any kind of proping of file information
        // through error messages.

        // Seventh, ensure the path exists.
        else if ($real_path === false) { return sprintf($this->getLang('error_access'), $title); }

        // We know that root and path exist.

        // Eighth, the final wall against path traversal outside the root.
        // Simple begins-with comparison, it's simple but effective.
        else if (strpos($real_path, $real_root) !== 0) { return sprintf($this->getLang('error_access'), $title); }

        // Ninth, ensure the file is a file and readable, and do any other good-measure checks.
        else if (is_dir($real_path)) { return sprintf($this->getLang('error_access'), $title); }
        else if (!is_readable($real_path)) { return sprintf($this->getLang('error_access'), $title); }

        // Tenth, attempt to read the contents and return error if failed.
        $result = file_get_contents($real_path);
        if ($result === false)
        {
            // This represents an actual error outside our control. Since the user should
            // have access to this content, it's ok to have a distinct error message.
            //return "The file '$title' could not be read.";
            return sprintf($this->getLang('error_read'), $title);
        }

        // Finally, set the out variable to the result of the read and return false.
        $content = $result;
        return false;
    }

    // There is a bug in GeShi, the syntax highlighting engine underlying Dokuwiki.
    // If it is passed content that starts with a < character, is told to highlight as
    // php, and contains a multiline comment of more than 610 characters, then the page
    // will crash. If any one of those isn't met, then the bug isn't raised.
    function _isPhpFixCandidate($text, $language)
    { return $text[0] == '<' && ($language == 'php' || $language == 'php-brief'); }

    function _isPhpFixRequired($text, $language)
    {
        if ($this->_isPhpFixCandidate($text, $language))
        {
            $result = preg_match_all('/\/\*.{610,}?\*\//s', $text);
            return $result === false || $result > 0;
        }
        return false;
    }

    function _getCodeBlockCount(Doku_Renderer $renderer)
    {
        $reflection = new ReflectionClass($renderer);
        $property = $reflection->getProperty('_codeblock');
        $property->setAccessible(true);
        return $property->getValue($renderer);
    }

    function _incCodeBlockCount(Doku_Renderer $renderer)
    {
        $reflection = new ReflectionClass($renderer);
        $property = $reflection->getProperty('_codeblock');
        $property->setAccessible(true);
        $codeblock = $property->getValue($renderer);
        $property->setValue($renderer, $codeblock + 1);
        return $property->getValue($renderer);
    }

    function _displayHeader(Doku_Renderer $renderer, $title)
    {
        global $ID;
        global $lang;
        global $INPUT;

        list($ext) = mimetype($title, false);
        $class = preg_replace('/[^_\-a-z0-9]+/i', '_', $ext);
        $class = 'mediafile mf_' . $class;

        $offset = 0;
        if ($INPUT->has('codeblockOffset'))
        { $offset = $INPUT->str('codeblockOffset'); }

        $renderer->doc .= '<dl class="file">'.DOKU_LF;
        $renderer->doc .= '<dt><a href="'
            . exportlink($ID, 'code', array('codeblock' => $offset + $this->_getCodeBlockCount($renderer)))
            . '" title="' . $lang['download'] . '" class="' . $class . '">';
        $renderer->doc .= hsc($title);
        $renderer->doc .= '</a></dt>'.DOKU_LF.'<dd>';
    }

    function _displayBody(Doku_Renderer $renderer, $text, $language = null, $options = null)
    {
        $language = preg_replace(PREG_PATTERN_VALID_LANGUAGE, '', $language);
        $language = preg_replace(PREG_PATTERN_VALID_LANGUAGE, '', $language);

        //if($text[0] == "\n") { $text = substr($text, 1); }
        //if(substr($text, -1) == "\n") { $text = substr($text, 0, -1); }

        if(empty($language))
        { $renderer->doc .= '<pre class="file">' .$renderer->_xmlEntities($text) . '</pre>' . DOKU_LF; }
        else
        {
            $renderer->doc .= "<pre class=\"code file {$language}\">" . DOKU_LF;
            // This method is only called when this is true.
            //if ($this->_isPhpFixRequired($text, $language))
            {
                // To avoid the GeShi error, we strip the first line
                // and process it separately from the rest.
                $index = strpos($text, "\n");
                $line = substr($text, 0, $index);
                $renderer->doc .= p_xhtml_cached_geshi($line, $language, '', $options) . DOKU_LF;
                $text = substr($text, $index + 1);
            }
            $renderer->doc .= p_xhtml_cached_geshi($text, $language, '', $options) . DOKU_LF;
            $renderer->doc .= '</pre>' . DOKU_LF;
        }

        //$renderer->_codeblock++;
        $this->_incCodeBlockCount($renderer);
    }
}

//Setup VIM: ex: et ts=4 enc=utf-8 :
?>