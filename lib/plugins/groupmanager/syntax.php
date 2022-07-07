<?php
/**
 * DokuWiki Plugin groupmanager (Syntax Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author  Harald Ronge <harald@turtur.nl>
 * @original author Alex Forencich <alex@alexforencich.com>
 *
 * Syntax:
 * ~~groupmanager|[groups to manage]|[allowed users and groups]~~
 *
 * Examples:
 *   ~~groupmanager|posters|@moderators~~
 *   Members of group 'posters' can be managed by group 'moderators'
 *
 *   ~~groupmanager|groupa, groupb|joe, @admin~~
 *   Members of groups 'groupa' and 'groupb' can be managed by user 'joe'
 *     members of the 'admin' group
 *
 * Note: superuser groups can only be managed by super users,
 *       forbidden groups can be configured,
 *       and users cannot remove themselves from the group that lets them access
 *       the group manager (including admins)
 *
 * Note: if require_conf_namespace config option is set, then plugin looks in
 *       conf_namespace:$ID for configuration.  Plugin will also check config
 *       namespace if a placeholder tag is used (~~groupmanager~~).  This is the
 *       default configuration for security reasons.
 *
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

if (!defined('DOKU_LF')) define('DOKU_LF', "\n");
if (!defined('DOKU_TAB')) define('DOKU_TAB', "\t");
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
if (!defined('GROUPMANAGER_IMAGES')) define('GROUPMANAGER_IMAGES', DOKU_BASE . 'lib/plugins/groupmanager/images/');

require_once DOKU_PLUGIN . 'syntax.php';

function remove_item_by_value($val, $arr, $preserve = true)
{
    if (empty($arr) || !is_array($arr)) {
        return false;
    }
    foreach (array_keys($arr, $val) as $key) {
        unset($arr[$key]);
    }
    return ($preserve) ? $arr : array_values($arr);
}


class syntax_plugin_groupmanager extends DokuWiki_Syntax_Plugin
{
    /**
     * Plugin information
     */
    var $_auth = null; // auth object
    var $_user_total = 0; // number of registered users
    var $_filter = array(); // user selection filter(s)
    var $_start = 0; // index of first user to be displayed
    var $_last = 0; // index of the last user to be displayed
    var $_pagesize = 20; // number of users to list on one page
    var $DefaultGroup = '';
    var $grplst = array();
    var $userlist = array();

    /**
     * Constructor
     */
    function syntax_plugin_groupmanager()
    {
        global $auth;

        $this->setupLocale();

        if (!isset($auth)) {
            $this->disabled = $this->lang['noauth'];
        } else if (!$auth->canDo('getUsers')) {
            $this->disabled = $this->lang['nosupport'];
        } else {

            // we're good to go
            $this->_auth = & $auth;

        }
    }

    function getInfo()
    {
        return array(
            'author' => 'Harald Ronge',
            'email' => 'harald@turtur.nl',
            'date' => '2013-05-26',
            'name' => 'Group Manager Syntax plugin',
            'desc' => 'Embeddable group manager, based on groupmgr from Alex Forencich and usermanager from Christopher Smith',
            'url' => 'http://www.dokuwiki.org/plugin:groupmanager/',
            'original author' => 'Alex Forencich',
            'original email' => 'alex@alexforencich.com'
        );
    }

    /**
     * Plugin type
     */
    function getType()
    {
        return 'substition';
    }

    /**
     * PType
     */
    function getPType()
    {
        return 'normal';
    }

    /**
     * Sort order
     */
    function getSort()
    {
        return 160;
    }

    /**
     * Register syntax handler
     */
    function connectTo($mode)
    {
        $this->Lexer->addSpecialPattern('~~groupmanager\|[^~]*?~~', $mode, 'plugin_groupmanager');
        $this->Lexer->addSpecialPattern('~~groupmanager~~', $mode, 'plugin_groupmanager');
    }

    /**
     * Handle match
     */
// is called without config, but do not know by whom, possibly with literal match
    function handle($match, $state, $pos, Doku_Handler $handler)
    {
// groupmanager only
        $data = array(null, $state, $pos);

        if (strlen($match) == 16)
            return $data;

        // Strip away tag
        $match = substr($match, 15, -2);

        // split arguments
        $ar = explode("|", $match);

        $match = array();

        // reorganize into array
        foreach ($ar as $it) {
            $ar2 = explode(",", $it);
            foreach ($ar2 as &$it2)
                $it2 = trim($it2);
            $match[] = $ar2;
        }

        // pass to render method
        $data[0] = $match;

        return $data;
    }

    /**
     * Render it
     */
    function render($mode, Doku_Renderer $renderer, $data)
    {
        // start usermanager
        global $auth;
        global $lang;
        global $INFO;
        global $conf;
        global $ID;

        // start groupmanager

        if ($mode == 'xhtml') {

			//TurTur, if submit and security token does not match stop anyway
			if(isset($_REQUEST['fn']) && !checkSecurityToken()) return false;

            // need config namespace?
            $allow_add_user = $conf_namespace = $this->getConf('allow_add_user');
            $allow_delete_user = $conf_namespace = $this->getConf('allow_delete_user');
            $conf_namespace = $this->getConf('conf_namespace');

            if ($this->getConf('require_conf_namespace')) {
                if (!$conf_namespace) return false;
                else $data[0] = null; // set it to null, it will be reloaded anyway
            }

            // empty tag?
            if (is_null($data[0]) || count($data[0]) == 0) {
                // load from conf namespace
                // build page name
                $conf_page = "";
                if (substr($ID, 0, strlen($conf_namespace)) != $conf_namespace) {
                    $conf_page .= $conf_namespace;
                    if (substr($conf_page, -1) != ':') $conf_page .= ":";
                }
                $conf_page .= $ID;

                // get file name
                $fn = wikiFN($conf_page);

                if (!file_exists($fn))
                    return false;

                // read file
                $page = file_get_contents($fn);

                // find config tag

                $i = preg_match('/~~groupmanager\|[^~]*?~~/', $page, $match);

                if ($i == 0)
                    return false;

                // parse config
                $match = substr($match[0], 15, -2);

                $ar = explode("|", $match);
                $match = array();

                // reorganize into array
                foreach ($ar as $it) {
                    $ar2 = explode(",", $it);
                    foreach ($ar2 as &$it2)
                        $it2 = trim($it2);
                    $match[] = $ar2;
                }

                // pass to render method
                $data[0] = $match;
            }

            // don't render if an argument hasn't been specified
            if (!isset($data[0][0]) || !isset($data[0][1]))
                return false;

            $this->grplst = $data[0][0];
            $authlst = $data[0][1];

            // parse forbidden groups
            $forbiddengrplst = array();
            $str = $this->getConf('forbidden_groups');
            if (isset($str)) {
                $arr = explode(",", $str);
                foreach ($arr as $val) {
                    $val = trim($val);
                    $forbiddengrplst[] = $val;
                }
            }

            // parse admin groups
            $admingrplst = array();
            if (isset($conf['superuser'])) {
                $arr = explode(",", $conf['superuser']);
                foreach ($arr as $val) {
                    $val = trim($val);
                    if ($val[0] == "@") {
                        $val = substr($val, 1);
                        $admingrplst[] = $val;
                    }
                }
            }

            // forbid admin groups if user is not a superuser
            if (!$INFO['isadmin']) {
                foreach ($admingrplst as $val) {
                    $forbiddengrplst[] = $val;
                }
            }

            // remove forbidden groups from group list
            foreach ($forbiddengrplst as $val) {
                $this->grplst = remove_item_by_value($val, $this->grplst, false);
            }
            if (count($this->grplst) > 0) $this->DefaultGroup = $this->grplst[0];

            // build array of user's credentials
            $check = array($_SERVER['REMOTE_USER']);
            if (is_array($INFO['userinfo'])) {
                foreach ($INFO['userinfo']['grps'] as $val) {
                    $check[] = "@" . $val;
                }
            }

            // does user have permission?
            // Also, save authenticated group for later
            $authbygrp = "";
            $ok = 0;
            foreach ($authlst as $val) {
                if (in_array($val, $check)) {
                    $ok = 1;
                    if ($val[0] == "@") {
                        $authbygrp = substr($val, 1);
                    }
                }
            }

            // continue if user has explicit permission or is an admin
            if ($INFO['isadmin'] || $ok) {
                // authorized
                $status = 0;

                // Begin inserted from usermanager

                if (is_null($this->_auth)) return false;
                
                // extract the command and any specific parameters
                // submit button name is of the form - fn[cmd][param(s)]

                $fn = $_REQUEST['fn'];

                if (is_array($fn)) {
                    $cmd = key($fn);
                    $param = is_array($fn[$cmd]) ? key($fn[$cmd]) : null;
                } else {
                    $cmd = $fn;
                    $param = null;
                }

                if ($cmd != "search") {
                    if (!empty($_REQUEST['start'])) {
                        $this->_start = $_REQUEST['start'];
                    }
                    $this->_filter = $this->_retrieveFilter();
                    $this->_setFilter("new");
                }

                switch ($cmd) {
                    case "add"    :
                        if ($allow_add_user) {
                            $this->_addUser();
                        } else msg($this->lang['add_without_form'], -1);
                        break;
                    case "update" :
                        $this->_deleteUser();
                        break;
                    /*
                    //case "add"    : if ($allow_add_user) {$this->_addUser()} else msg('Trying to add user without form!',-1); break;/*
                    case "modify" : $this->_modifyUser(); break;
                    case "edit"   : $this->_editUser($param); break;
                    */
                    case "search" :
                        $this->_setFilter($param);
                        $this->_start = 0;
                        break;
                }
                /*
                else {
                    $this->_setFilter($param);
                    $this->_start = 0;
                }
                */


                $this->_user_total = $this->_auth->canDo('getUserCount') ? $this->_auth->getUserCount($this->_filter) : -1;

                // page handling
                switch ($cmd) {
                    case 'start' :
                        $this->_start = 0;
                        break;
                    // case 'update' : $this->_start = $this->_start; break; //do nothing
                    case 'prev'  :
                        $this->_start -= $this->_pagesize;
                        break;
                    case 'next'  :
                        $this->_start += $this->_pagesize;
                        break;
                    case 'last'  :
                        $this->_start = $this->_user_total;
                        break;
                }
                $this->_validatePagination();

                // we are parsing a submitted comment...
                if (isset($_REQUEST['comment']))
                    return false;

                // disable caching
                $renderer->info['cache'] = false;

                if (!method_exists($auth, "retrieveUsers")) return false;

                if (is_null($this->_auth)) {
                    print $this->lang['badauth'];
                    return false;
                }

                // watch out: plain authentication takes limit = 0 for get all users
                // MySQL will take 0 to retrieve none (makes sense), so the code below did not work
                // $users = $auth->retrieveUsers(0, 10000, array());
                $this->userlist = $this->_auth->retrieveUsers($this->_start, $this->_pagesize, $this->_filter);
                $page_buttons = $this->_pagination();
                $colspan = 4 + count($this->grplst) + ($allow_delete_user?0:-1) ;
                // open form
                $renderer->doc .= "<form method=\"post\" action=\"" . htmlspecialchars($_SERVER['REQUEST_URI'])
                    . "\" name=\"groupmanager\" enctype=\"application/x-www-form-urlencoded\">";
				//TurTur
				$renderer->doc .= formSecurityToken(false);				

                // open table and print header
                if ($this->_user_total > 0) {
                    $renderer->doc .= "<p>" . sprintf($this->lang['summary'], $this->_start + 1, $this->_last, $this->_user_total, $this->_auth->getUserCount()) . "</p>";
                } else {
                    $renderer->doc .= "<p>" . sprintf($this->lang['nonefound'], $this->_auth->getUserCount()) . "</p>";
                }
				
                $renderer->doc .= "<table class=\"inline\" >\n"; //width=\"95%\" style=\"max-width: 500px; overflow:scroll;\">\n";
                // $renderer->doc .= "  <tbody>\n";
                $renderer->doc .= "    <tbody>";

                $renderer->doc .= "      <tr><td colspan=\"" . $colspan . "\" class=\"centeralign\"  STYLE='border-bottom: 3px solid #ccc'>";
                $renderer->doc .= "        <span class=\"medialeft\" >";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "search.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "search_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "search.png'\" STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][new]\" title=\"" . $this->lang['search_prompt'] . "\" alt=\"" . $this->lang['search'] . "\" />";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "workgroup.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "workgroup_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "workgroup.png'\"  STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][show_default]\" title=\"" . $this->lang['search_default_group'] . "\" alt=\"" . $this->lang['search_default_group'] . "\" />";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "everybody.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "everybody_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "everybody.png'\"  STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][clear]\" title=\"" . $this->lang['clear'] . "\" alt=\"" . $this->lang['clear'] . "\" />";
                $renderer->doc .= "        </span>";
                $renderer->doc .= "        <span class=\"mediaright\">";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[start]\" " . $page_buttons['start'] . " class=\"button\" value=\"" . $this->lang['start'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[prev]\" " . $page_buttons['prev'] . " class=\"button\" value=\"" . $this->lang['prev'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[next]\" " . $page_buttons['next'] . " class=\"button\" value=\"" . $this->lang['next'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[last]\" " . $page_buttons['last'] . " class=\"button\" value=\"" . $this->lang['last'] . "\" />";

                $renderer->doc .= "        </span>";
                $renderer->doc .= "      </td></tr>";
                $renderer->doc .= "      <tr>";
                //if delete column is hidden, Filter-header is part of the same column
                if ($allow_delete_user) {$renderer->doc .= "        <td STYLE='border-bottom: 3px solid #ccc; color: #FF9900'> Filter:</td>";}
                $renderer->doc .= "        <td class=\"centeralign\" style=\"border-bottom: 3px solid #ccc\"><input type=\"text\" style=\"width:90%; color: #FF9900';\" name=\"userid\" class=\"edit\" value=\"" . $this->_htmlFilter('user') . "\" /></td>";
                $renderer->doc .= "        <td class=\"centeralign\" style=\"border-bottom: 3px solid #ccc\"><input type=\"text\" style=\"width:90%; color: #FF9900';\" name=\"username\" class=\"edit\" value=\"" . $this->_htmlFilter('name') . "\" /></td>";
                $renderer->doc .= "       <td style=\"border-bottom: 3px solid #ccc\"><input type=\"text\" style=\"width:90%; color: #FF9900';\" name=\"usermail\" class=\"edit\" value=\"" . $this->_htmlFilter('mail') . "\" /></td>";
                $renderer->doc .= "       <td colspan=\"" . $colspan . "\" class=\"centeralign\" style=\"border-bottom: 3px solid #ccc\">";
                $renderer->doc .= "        <span> <input type=\"text\" style=\"width:95%; color: #FF9900';\" name=\"usergroups\" class=\"edit\" value=\"" . $this->_htmlFilter('grps') . "\" /></span></td>";
                $renderer->doc .= "      </tr>\n";

                $renderer->doc .= "    <tr>\n";
                if ($allow_delete_user) $renderer->doc .= "      <th style='color: #FF9900'>" . $this->lang['delete'] . "</th>\n";
                $renderer->doc .= "      <th>" . $this->lang['user_id'] . "</th>\n";
                $renderer->doc .= "      <th>" . $this->lang['user_name'] . "</th>\n";
                $renderer->doc .= "      <th>" . $this->lang['user_mail'] . "</th>\n";
                // loop through available groups
                foreach ($this->grplst as $g) {
                    $renderer->doc .= "      <th>" . ucwords(str_replace("_", " ", str_replace("wg_", "", htmlspecialchars($g)))) . "</th>\n";
                }

                $renderer->doc .= "    </tr>\n";


                // loop through users
                foreach ($this->userlist as $name => $u) {
                    // print user info
                    $renderer->doc .= "    <tr>\n";
                    if ($allow_delete_user) $renderer->doc .= "<td class=\"centeralign\"><input type=\"checkbox\" name=\"delete[" . $name . "]\"  /></td>";

                    $renderer->doc .= "      <td>" . htmlspecialchars($name);

                    // need tag so user isn't pulled out of a group if it was added
                    // between initial page load and update

                    //change TurTur:
                    //$hn = md5($name); // caused trouble, on dreamhost md5 did not work
                    //output is better readable too
                    //end change

                    $renderer->doc .= "<input type=\"hidden\" name=\"id_" . $name . "\" value=\"1\" />";
                    $renderer->doc .= "</td>\n";
                    $renderer->doc .= "      <td>" . htmlspecialchars($u['name']) . "</td>\n";
                    $renderer->doc .= "      <td>";
                    $renderer->emaillink($u['mail']);

                    $renderer->doc .= "</td>\n";
                    // loop through groups
                    foreach ($this->grplst as $g) {
                        $renderer->doc .= "      <td class=\"centeralign\">";

                        $chk = "chk_" . $name . "_" . $g;

                        // does this box need to be disabled?
                        // prevents user from taking himself out of an important group
                        $disabled = 0;
                        // if this box applies to a current group membership of the current user, continue check
                        if (in_array($g, $u['grps']) && $_SERVER['REMOTE_USER'] == $name) {
                            // if user is an admin and group is an admin group, disable
                            if ($INFO['isadmin'] && in_array($g, $admingrplst)) {
                                $disabled = 1;
                                // if user was authenticated by this group, disable
                            } else if (strlen($authbygrp) > 0 && $g == $authbygrp) {
                                $disabled = 1;
                            }
                        }

                        // update user group membership
                        // only update if something changed
                        // keep track of status
                        $update = array();
                        if (!$disabled && $_POST["id_" . $name]) {
                            if ($_POST[$chk]) {
                                if (!in_array($g, $u['grps'])) {
                                    $u['grps'][] = $g;
                                    $update['grps'] = $u['grps'];
                                }
                            } else {
                                if (in_array($g, $u['grps'])) {
                                    $u['grps'] = remove_item_by_value($g, $u['grps'], false);
                                    $update['grps'] = $u['grps'];
                                }
                            }
                            if (count($update) > 0) {
                                if ($this->_auth->triggerUserMod('modify',array($name, $update))) {
                                    io_saveFile($conf['cachedir'] . '/sessionpurge', time()); //invalidate all sessions	
                                    if ($status == 0) $status = 1;
                                } else {
                                    $status = 2;
                                }
                            }
                        }

                        // display check box
                        $renderer->doc .= "<input type=\"checkbox\" name=\"" . $chk . "\"";
                        if (in_array($g, $u['grps'])) {
                            $renderer->doc .= " checked=\"true\"";
                        }
                        if ($disabled) {
                            $renderer->doc .= " disabled=\"true\"";
                        }

                        $renderer->doc .= " />";

                        $renderer->doc .= "</td>\n";
                    }
                    $renderer->doc .= "    </tr>\n\n";
                }

                $renderer->doc .= "  </tbody>\n";

                $renderer->doc .= "    <tbody>";
                $renderer->doc .= "      <tr><td colspan=\"" . $colspan . "\" class=\"centeralign\" STYLE='border-top: 3px solid #ccc'>";
                $renderer->doc .= "        <span class=\"medialeft\">";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "search.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "search_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "search.png'\" STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][new]\" title=\"" . $this->lang['search_prompt'] . "\" alt=\"" . $this->lang['search'] . "\" />";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "workgroup.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "workgroup_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "workgroup.png'\"  STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][show_default]\" title=\"" . $this->lang['search_default_group'] . "\" alt=\"" . $this->lang['search_default_group'] . "\" />";
                $renderer->doc .= "        <input type=\"image\" src=\"" . GROUPMANAGER_IMAGES . "everybody.png\" onmouseover=\"this.src='" . GROUPMANAGER_IMAGES . "everybody_hilite.png'\" onmouseout=\"this.src='" . GROUPMANAGER_IMAGES . "everybody.png'\"  STYLE=\"float: left; padding-right: 5px;\" name=\"fn[search][clear]\" title=\"" . $this->lang['clear'] . "\" alt=\"" . $this->lang['clear'] . "\" />";
                $renderer->doc .= "        </span>";
                $renderer->doc .= "        <span class=\"mediaright\">";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[start]\" " . $page_buttons['start'] . " class=\"button\" value=\"" . $this->lang['start'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[prev]\" " . $page_buttons['prev'] . " class=\"button\" value=\"" . $this->lang['prev'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[next]\" " . $page_buttons['next'] . " class=\"button\" value=\"" . $this->lang['next'] . "\" />";
                $renderer->doc .= "          <input type=\"submit\" name=\"fn[last]\" " . $page_buttons['last'] . " class=\"button\" value=\"" . $this->lang['last'] . "\" />";

                $renderer->doc .= "        </span>";
                $renderer->doc .= "      </td></tr>";

                $renderer->doc .= "    </tbody>";

                $renderer->doc .= "</table>\n";

                $renderer->doc .= "<input type=\"hidden\" name=\"start\" value=\"" . $this->_start . "\" />";
                // update button
                $renderer->doc .= "<div><input type=\"submit\" name=\"fn[update]\" " . $page_buttons['update'] . " class=\"button\" value=\"" . $this->lang['btn_update_group'] . "\" /></div>";

                $renderer->doc .= "</form>";


                if ($this->_auth->canDo('addUser') && $allow_add_user) {
                    $style = $this->_add_user ? " class=\"add_user\"" : "";
                    $renderer->doc .= "<div" . $style . ">";
                    $renderer->doc .= $this->locale_xhtml('add');
                    $renderer->doc .= "  <div class=\"level2\">";

                    $UserData['grps'][0] = $this->DefaultGroup;
                    $this->_htmlUserForm($renderer, 'add', null, $UserData, 4);

                    $renderer->doc .= "  </div>";
                    $renderer->doc .= "</div>";
                }

                // display relevant status message
                if ($status == 1) {
                    msg($this->lang['updatesuccess'], 1);
                } else if ($status == 2) {
                    msg($this->lang['updatefailed'], -1);
                }
            } else {
                // not authorized
                $renderer->doc .= "<p>" . $this->lang['notauthorized'] . "</p>\n";
            }

            return true;
        }
        return false;
    }


     function _htmlUserForm(&$renderer, $cmd, $user = '', $userdata = array(), $indent = 0)
    {
        global $conf;
        global $ID;

        $name = $mail = $groups = '';
        $notes = array();

        extract($userdata);
        if (!empty($grps)) $groups = join(',', $grps);

        if (!$user) {
            //$groups will contain the default group when users are added
            $notes[] = sprintf($this->lang['note_group'], $groups);
        }

        $renderer->doc .= "<form action=\"" . wl($ID) . "\" method=\"post\">";
        $renderer->doc .= formSecurityToken(false);
        $renderer->doc .= "  <table class=\"inline\" width='75%'>";
        $renderer->doc .= "    <thead>";
        $renderer->doc .= "      <tr><th>" . $this->lang["field"] . "</th><th>" . $this->lang["value"] . "</th></tr>";
        $renderer->doc .= "    </t>";
        $renderer->doc .= "    <tbody>";

        $this->_htmlInputField($renderer, $cmd . "_userid", "userid", $this->lang["user_id"], $user, $this->_auth->canDo("modLogin"), $indent + 6);
        $this->_htmlInputField($renderer, $cmd . "_userpass", "userpass", $this->lang["user_pass"], "", $this->_auth->canDo("modPass"), $indent + 6);
        $this->_htmlInputField($renderer, $cmd . "_username", "username", $this->lang["user_name"], $name, $this->_auth->canDo("modName"), $indent + 6);
        $this->_htmlInputField($renderer, $cmd . "_usermail", "usermail", $this->lang["user_mail"], $mail, $this->_auth->canDo("modMail"), $indent + 6);
        $renderer->doc .= "<input type='hidden' id='" . $cmd . "_usergroups' name='usergroups' value='" . $groups . "'";

        if ($this->_auth->canDo("modPass")) {
            $notes[] = $this->lang['note_pass'];
            if ($user) {
                $notes[] = $this->lang['note_notify'];
            }

            $renderer->doc .= "<tr><td><label for=\"" . $cmd . "_usernotify\" >" . $this->lang["user_notify"] . ": </label></td><td><input type=\"checkbox\" id=\"" . $cmd . "_usernotify\" name=\"usernotify\" value=\"1\" /></td></tr>";
        }

        $renderer->doc .= "    </tbody>";
        $renderer->doc .= "    <tbody>";
        $renderer->doc .= "      <tr>";
        $renderer->doc .= "        <td colspan=\"2\">";

        $this->_htmlFilterSettings($renderer, $indent + 10);

        $renderer->doc .= "          <input type=\"submit\" name=\"fn[" . $cmd . "]\" class=\"button\" value=\"" . $this->lang[$cmd] . "\" />";
        $renderer->doc .= "        </td>";
        $renderer->doc .= "      </tr>";
        $renderer->doc .= "    </tbody>";
        $renderer->doc .= "  </table>";

        foreach ($notes as $note)
            $renderer->doc .= "<div class=\"fn\">" . $note . "</div>";

        $renderer->doc .= "</form>";
    }

    function _htmlInputField(&$renderer, $id, $name, $label, $value, $cando, $indent = 0)
    {
        $class = $cando ? '' : ' class="disabled"';
        $disabled = $cando ? '' : ' disabled="disabled"';
        $renderer->doc .= str_pad('', $indent);

        if ($name == 'userpass') {
            $fieldtype = 'password';
            $autocomp = 'autocomplete="off"';
        } else {
            $fieldtype = 'text';
            $autocomp = '';
        }


        $renderer->doc .= "<tr $class>";
        $renderer->doc .= "<td style='width: 30%'><label for=\"$id\" >$label: </label></td>";
        $renderer->doc .= "<td>";
        if ($cando) {
            $renderer->doc .= "<input type=\"$fieldtype\" id=\"$id\" name=\"$name\" value=\"$value\" class=\"edit\" $autocomp style='width: 95%'/>";
        } else {
            $renderer->doc .= "<input type=\"hidden\" name=\"$name\" value=\"$value\" />";
            $renderer->doc .= "<input type=\"$fieldtype\" id=\"$id\" name=\"$name\" value=\"$value\" class=\"edit disabled\" disabled=\"disabled\" />";
        }
        $renderer->doc .= "</td>";
        $renderer->doc .= "</tr>";
    }

    function _addUser()
    {
        if (!checkSecurityToken()) return false;
        if (!$this->_auth->canDo('addUser')) return false;

        list($user, $pass, $name, $mail, $grps) = $this->_retrieveUser();
        if (empty($user)) return false;

        if ($this->_auth->canDo('modPass')) {
            if (empty($pass)) {
                if (!empty($_REQUEST['usernotify'])) {
                    $pass = auth_pwgen();
                } else {
                    msg($this->lang['user_must_be_notified_with_generated_pwd'], -1);
                    msg($this->lang['add_fail'], -1);
                    return false;
                }
            }
        } else {
            if (!empty($pass)) {
                msg($this->lang['add_fail'], -1);
                return false;
            }
        }

        if ($this->_auth->canDo('modName')) {
            if (empty($name)) {
                msg($this->lang['add_fail'], -1);
                return false;
            }
        } else {
            if (!empty($name)) {
                return false;
            }
        }

        if ($this->_auth->canDo('modMail')) {
            if (empty($mail)) {
                msg($this->lang['mail_required'], -1);
                msg($this->lang['add_fail'], -1);
                return false;
            }
        } else {
            if (!empty($mail)) {
                return false;
            }
        }

        if ($ok = $this->_auth->triggerUserMod('create', array($user, $pass, $name, $mail, $grps))) {

            msg($this->lang['add_ok'], 1);

            if (!empty($_REQUEST['usernotify']) && $pass) {
                $this->_notifyUser($user, $pass);
            }
        } else {
            msg($this->lang['add_fail'], -1);
        }

        return $ok;
    }

    /**
     * Delete user
     */
    function _deleteUser()
    {
        global $conf;
        //$MayDelete = false;

        if (!checkSecurityToken()) return false;
        if (!$this->_auth->canDo('delUser')) return false;

        $selected = $_REQUEST['delete'];
        if (!is_array($selected) || empty($selected)) return false;
        $selected = array_keys($selected);

        if (in_array($_SERVER['REMOTE_USER'], $selected)) {
            msg($this->lang['cant_delete_yourself'], -1);
            return false;
        }
        
		//user may only be deleted if not member of any group other than the current working group roles
        foreach ($selected as $selection) {
            $currentfilter['user'] = $selection;
            $currentuser = $this->_auth->retrieveUsers(0, 100000, $currentfilter);
            $currentgroups = $currentuser[$selection]['grps'];
            //user may only be part of working group parts
            //if (count($currentgroups) <= count($this->grplst)) { commented out, If the user is in more groups than this page manages it would skip the test to see if the user was in any other groups! 
                foreach ($currentgroups as $g) {
                    if (!in_array($g, $this->grplst)) {
                        msg($this->lang['cant_delete_if_more_groups'], -1);
                        return false;
                    }
                }
            //}
        }

        $count = $this->_auth->triggerUserMod('delete', array($selected));
        if ($count == count($selected)) {
            $text = str_replace('%d', $count, $this->lang['delete_ok']);
            msg("$text.", 1);
        } else {
            $part1 = str_replace('%d', $count, $this->lang['delete_ok']);
            $part2 = str_replace('%d', (count($selected) - $count), $this->lang['delete_fail']);
            msg("$part1, $part2", -1);
        }

        // invalidate all sessions
        io_saveFile($conf['cachedir'] . '/sessionpurge', time());

        return true;
    }

    /**
     * send password change notification email
     */
    function _notifyUser($user, $password)
    {

        if ($sent = auth_sendPassword($user, $password)) {
            msg($this->lang['notify_ok'], 1);
        } else {
            msg($this->lang['notify_fail'], -1);
        }

        return $sent;
    }

    function _htmlFilter($key)
    {
        if (empty($this->_filter)) return '';
        return (isset($this->_filter[$key]) ? hsc($this->_filter[$key]) : '');
    }

    function _htmlFilterSettings(&$renderer, $indent = 0)
    {

        $renderer->doc .= "<input type=\"hidden\" name=\"start\" value=\"" . $this->_start . "\" />";

        foreach ($this->_filter as $key => $filter) {
            $renderer->doc .= "<input type=\"hidden\" name=\"filter[" . $key . "]\" value=\"" . hsc($filter) . "\" />";
        }
    }

    /**
     * retrieve & clean user data from the form
     *
     * @return  array(user, password, full name, email, array(groups))
     */
    function _retrieveUser($clean = true)
    {
        global $auth;

        $user[0] = ($clean) ? $auth->cleanUser($_REQUEST['userid']) : $_REQUEST['userid'];
        $user[1] = $_REQUEST['userpass'];
        $user[2] = $_REQUEST['username'];
        $user[3] = $_REQUEST['usermail'];
        $user[4] = explode(',', $_REQUEST['usergroups']);

        $user[4] = array_map('trim', $user[4]);
        if ($clean) $user[4] = array_map(array($auth, 'cleanGroup'), $user[4]);
        $user[4] = array_filter($user[4]);
        $user[4] = array_unique($user[4]);
        if (!count($user[4])) $user[4] = null;

        return $user;
    }

    function _setFilter($op)
    {

        $this->_filter = array();

        switch ($op) {
            case 'clear':
                break;

            case 'new':
                list($user, $pass, $name, $mail, $grps) = $this->_retrieveUser(false);
                if (!empty($user)) $this->_filter['user'] = str_replace(' ', '_', $user);
                if (!empty($name)) $this->_filter['name'] = $name;
                if (!empty($mail)) $this->_filter['mail'] = $mail;
                if (!empty($grps)) $this->_filter['grps'] = str_replace(' ', '_', join('|', $grps));
                //if (!empty($grps)) $this->_filter['grps'] = join('|',$grps);
                break;
            case show_default:
                $this->_filter['grps'] = $this->DefaultGroup;
                break;
        }

    }

    function _retrieveFilter()
    {

        $t_filter = $_REQUEST['filter'];
        if (!is_array($t_filter)) return array();

        // messy, but this way we ensure we aren't getting any additional crap from malicious users
        $filter = array();

        if (isset($t_filter['user'])) $filter['user'] = $t_filter['user'];
        if (isset($t_filter['name'])) $filter['name'] = $t_filter['name'];
        if (isset($t_filter['mail'])) $filter['mail'] = $t_filter['mail'];
        if (isset($t_filter['grps'])) $filter['grps'] = $t_filter['grps'];

        return $filter;
    }

    function _validatePagination()
    {

        if ($this->_start >= $this->_user_total) {
            $this->_start = $this->_user_total - $this->_pagesize;
        }
        if ($this->_start < 0) $this->_start = 0;

        $this->_last = min($this->_user_total, $this->_start + $this->_pagesize);
    }

    /*
     *  return an array of strings to enable/disable pagination buttons
     */
    function _pagination()
    {

        $disabled = 'disabled="disabled"';

        $buttons['start'] = $buttons['prev'] = ($this->_start == 0) ? $disabled : '';

        if ($this->_user_total == -1) {
            $buttons['last'] = $disabled;
            $buttons['next'] = '';
        } else {
            $buttons['last'] = $buttons['next'] = (($this->_start + $this->_pagesize) >= $this->_user_total) ? $disabled : '';
        }

        $buttons['update'] = '';

        return $buttons;
    }
}

// vim:ts=4:sw=4:et:enc=utf-8:
