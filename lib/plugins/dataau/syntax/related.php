<?php
/**
 * List related pages based on similar data in the given column(s)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <andi@splitbrain.org>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class syntax_plugin_dataau_related
 */
class syntax_plugin_dataau_related extends syntax_plugin_dataau_table {

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('----+ *datarelated(?: [ a-zA-Z0-9_]*)?-+\n.*?\n----+', $mode, 'plugin_dataau_related');
    }

    /**
     * Handles the actual output creation.
     *
     * @param   string        $format   output format being rendered
     * @param   Doku_Renderer $renderer the current renderer object
     * @param   array         $dataau     data created by handler()
     * @return  boolean                 rendered correctly? (however, returned value is not used at the moment)
     */
    function render($format, Doku_Renderer $renderer, $dataau) {
        if($format != 'xhtml') return false;
        if(is_null($dataau)) return false;
        if(!$this->dthlp->ready()) return false;

        $sqlite = $this->dthlp->_getDB();
        if(!$sqlite) return false;

        if(!$dataau['sql']) return true; // sql build
        $this->dthlp->_replacePlaceholdersInSQL($dataau);

        $res = $sqlite->query($dataau['sql']);
        if(!$sqlite->res2count($res)) return true; // no rows matched
        $rows = $sqlite->res2arr($res);

        $renderer->doc .= '<dl class="' . $dataau['classes'] . '">';
        $renderer->doc .= '<dt>' . htmlspecialchars($dataau['title']) . '</dt>';
        $renderer->doc .= '<dd>';
        $renderer->listu_open();
        foreach($rows as $row) {
            $renderer->listitem_open(1);
            $renderer->internallink($row['page']);
            $renderer->listitem_close();
        }
        $renderer->listu_close();
        $renderer->doc .= '</dd>';
        $renderer->doc .= '</dl>';

        return true;
    }

    /**
     * Builds the SQL query from the given data
     */
    function _buildSQL(&$dataau, $id = null) {
        global $ID;
        if(is_null($id)) $id = $ID;

        $cnt = 1;
        $tables = array();
        $cond = array();
        $from = '';
        $where = '';

        $sqlite = $this->dthlp->_getDB();
        if(!$sqlite) return false;

        // prepare the columns to match against
        $found = false;
        foreach(array_keys($dataau['cols']) as $col) {
            // get values for current page:
            $values = array();
            $sql = "SELECT A.value
                      FROM dataau A, pages B
                     WHERE key = ?
                       AND A.pid = B.pid
                       AND B.page = ?";
            $res = $sqlite->query($sql, $col, $id);
            while($value = $sqlite->res_fetch_assoc($res)) {
                $values[] = $value['value'];
            }
            if(!count($values)) continue; // no values? ignore the column.
            $found = true;

            $cond[] = " ( T1.key = " . $sqlite->quote_string($col) .
                " AND T1.value IN (" . $sqlite->quote_and_join($values, ',') . ") )\n";
        }
        $where .= ' AND (' . join(' OR ', $cond) . ') ';

        // any tags to compare?
        if(!$found) return false;

        // prepare sorting
        if($dataau['sort'][0]) {
            $col = $dataau['sort'][0];

            if($col == '%pageid%') {
                $order = ', pages.page ' . $dataau['sort'][1];
            } elseif($col == '%title%') {
                $order = ', pages.title ' . $dataau['sort'][1];
            } else {
                // sort by hidden column?
                if(!$tables[$col]) {
                    $tables[$col] = 'T' . (++$cnt);
                    $from .= ' LEFT JOIN dataau AS ' . $tables[$col] . ' ON ' . $tables[$col] . '.pid = pages.pid';
                    $from .= ' AND ' . $tables[$col] . ".key = " . $sqlite->quote_string($col);
                }

                $order = ', ' . $tables[$col] . '.value ' . $dataau['sort'][1];
            }
        } else {
            $order = ', pages.page';
        }

        // add filters
        if(is_array($dataau['filter']) && count($dataau['filter'])) {
            $where .= ' AND ( 1=1 ';

            foreach($dataau['filter'] as $filter) {
                $col = $filter['key'];
                $closecompare = ($filter['compare'] == 'IN(' ? ')' : '');

                if($col == '%pageid%') {
                    $where .= " " . $filter['logic'] . " pages.page " . $filter['compare'] . " '" . $filter['value'] . "'" . $closecompare;
                } elseif($col == '%title%') {
                    $where .= " " . $filter['logic'] . " pages.title " . $filter['compare'] . " '" . $filter['value'] . "'" . $closecompare;
                } else {
                    // filter by hidden column?
                    if(!$tables[$col]) {
                        $tables[$col] = 'T' . (++$cnt);
                        $from .= ' LEFT JOIN dataau AS ' . $tables[$col] . ' ON ' . $tables[$col] . '.pid = pages.pid';
                        $from .= ' AND ' . $tables[$col] . ".key = " . $sqlite->quote_string($col);
                    }

                    $where .= ' ' . $filter['logic'] . ' ' . $tables[$col] . '.value ' . $filter['compare'] .
                        " '" . $filter['value'] . "'" . $closecompare; //value is already escaped
                }
            }

            $where .= ' ) ';
        }

        // build the query
        $sql = "SELECT pages.pid, pages.page as page, pages.title as title, COUNT(*) as rel
                  FROM pages, dataau as T1 $from
                 WHERE pages.pid = T1.pid
                   AND pages.page != " . $sqlite->quote_string($id) . "
                       $where
              GROUP BY pages.pid
              ORDER BY rel DESC$order";

        // limit
        if($dataau['limit']) {
            $sql .= ' LIMIT ' . ($dataau['limit']);
        }

        return $sql;
    }
}
