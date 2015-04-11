<?php

class SiblingNav {

    function __construct(modX & $modx, array $config = array()) {
        $this->modx = &$modx;
        $corePath = $this->modx->getOption('siblingnav.core_path', $config, $this->modx->getOption('core_path') . 'components/siblingnav/');
        $assetsUrl = $this->modx->getOption('siblingnav.assets_url', $config, $this->modx->getOption('assets_url') . 'components/siblingnav/');

        $default['rowTpl'] = 'snrow';
        $default['selfTpl'] = 'snself';
        $default['prevTpl'] = 'snprev';
        $default['nextTpl'] = 'snnext';
        $default['firstTpl'] = 'snfirst';
        $default['lastTpl'] = 'snlast';
        $default['placeholderPrefix'] = 'sn.';
        $default['id'] = $this->modx->resource->get('id');
        $default['parents'] = false;
        $default['where'] = '';

        $default['showDeleted'] = 0;
        $default['showUnpublished'] = 0;
        $default['showHidden'] = 0;
        $default['selectfields'] = ''; //select only specific fields
        $default['debug'] = 0; //debug mode
        $default['loopInfinite'] = 0;//loop from Beginnning when at last sibling

        //$default['level'] = 0;
        //$default['includeDocs'] = '';
        $default['exclude'] = '';
        //$default['ph'] = false;
        //$default['debug'] = false;
        //$default['ignoreHidden'] = false;
        //$default['hideSubMenus'] = false;
        //$default['useWeblinkUrl'] = true;
        //$default['fullLink'] = false;
        $default['sortOrder'] = 'ASC';
        $default['sortBy'] = '{"menuindex":"ASC","id":"ASC"}';
        $default['limit'] = false;
        //$default['cssTpl'] = false;
        //$default['jsTpl'] = false;
        //$default['rowIdPrefix'] = false;
        //$default['textOfLinks'] = 'menutitle';
        //$default['titleOfLinks'] = 'pagetitle';
        //$default['displayStart'] = false;
        //$default['permissions'] = 'list';
        $default['corePath'] = $corePath;
        $default['modelPath'] = $corePath . 'model/';
        $default['chunksPath'] = $corePath . 'elements/chunks/';
        $default['chunkSuffix'] = '.chunk.html';
        $default['snippetsPath'] = $corePath . 'elements/snippets/';
        $default['processorsPath'] = $corePath . 'processors/';
        $this->config = array_merge($default, $config);
    }

    function run() {

        if ($this->resource = $this->modx->getObject('modResource', $this->config['id'])) {
            $this->rawValues = $this->resource->toArray('', true);
            if ($this->config['parents']) {
                $parents = explode(',', $this->config['parents']);
                $parentsWithChilds = array();
                $c = $this->modx->newQuery('modResource');
                $c->where(array('id:IN' => $parents));
                if ($collection = $this->modx->getIterator('modResource', $c)) {
                    foreach ($collection as $object) {
                        if ($object->hasChildren()) {
                            $parentsWithChilds[] = $object->get('id');
                        }
                    }
                }
                foreach ($parents as $parent) {
                    if (in_array($parent, $parentsWithChilds)) {
                        $sortedparents[] = $parent;
                    }
                }
                if (count($sortedparents) > 0) {
                    $this->config['parents'] = implode(',', $sortedparents);
                } else {
                    $this->config['parents'] = false;
                }

            }


            $this->rows['prevlinks'] = array();
            $this->rows['nextlinks'] = array();
            $this->ph['prevlinks'] = '';
            $this->ph['nextlinks'] = '';

            $this->config['parent'] = $this->resource->get('parent');
            if ($prevrows = $this->getSiblings('down')) {
                $this->rows['prevlinks'] = $prevrows;
                $prevlinks = $this->getChunks($this->config['rowTpl'], array_reverse($this->rows['prevlinks']));
                $this->ph['prevlinks'] = implode('', $prevlinks);
            }
            if ($nextrows = $this->getSiblings('up')) {
                $this->rows['nextlinks'] = $nextrows;
                $nextlinks = $this->getChunks($this->config['rowTpl'], $this->rows['nextlinks'], $output);
                $this->ph['nextlinks'] = implode('', $nextlinks);
            }



            $this->makeFirstLast('first');
            $this->makeFirstLast('last');
            $this->makePrevNext('prev');
            $this->makePrevNext('next');
            
            $this->limitRows();

            $this->rows['self'] = $this->resource->toArray();
            $this->ph['self'] = $this->getChunk($this->config['selfTpl'], $this->rows['self']);


            //$this -> ph['rows'] = implode('', $output);

            if (!empty($this->config['debug'])) {
                foreach ($this->ph as $key => $ph) {
                    echo '<h3> Output for placeholder ' . $this->config['placeholderPrefix'] . $key . '</h3>';
                    echo '<pre>' . print_r($this->rows[$key], 1) . '</pre>';
                }
            }

            $this->modx->setPlaceholders($this->ph, $this->config['placeholderPrefix']);

        }

        return '';
    }

    function limitRows() {
        if ($this->config['limit']) {
            $prevrows = $this->rows['prevlinks'];
            $nextrows = $this->rows['nextlinks'];
            $limit = $this->config['limit'];
            $prevcount = count($prevrows);
            $nextcount = count($nextrows);
            $left_right = $limit - 1;
            $left = round($left_right / 2);
            $right = $left_right - $left;

            if ($nextcount < $right) {
                $left = $left_right - $nextcount;
                $right = $nextcount;
            } elseif ($prevcount < $left) {
                $right = $left_right - $prevcount;
                $left = $prevcount;
            }

            $this->rows['prevlinks'] = array_slice($prevrows, 0, $left);
            $this->rows['nextlinks'] = array_slice($nextrows, 0, $right);

        }

        return;

    }

    function makePrevNext($dir) {
        $rows = $this->rows[$dir . 'links'];
        $row = array();
        $row['_isactive'] = '0';
        $row['id'] = '0';

        if (count($rows) > 0) {
            $row = $rows[0];
            $row['_isactive'] = '1';
        } else {
            if ($this->config['parents']) {
                $parents = explode(',', $this->config['parents']);
                $key = array_search($this->config['parent'], $parents);

                switch ($dir) {
                    case 'next':
                        $key = $key + 1;
                        //$direction = 'up';
                        break;
                    case 'prev':
                        $key = $key - 1;
                        //$direction = 'down';
                        break;
                }
                if ($key < 0 || $key > (count($parents) - 1)) {
                    //there aren't more parents
                } else {
                    $parent = $parents[$key];
                    if ($collection = $this->getSiblings($dir, $parent, false)) {
                        $this->rows[$dir . 'links'] = $collection;
                    }
                    return $this->makePrevNext($dir);
                }
            }
            //if still here and no rows found check for infinitLoop - setting
            if (!empty($this->config['infinitLoop'])){
                switch ($dir) {
                    case 'next':
                        $row = $this->rows['first'];
                        $row['_isactive'] = 1;
                        break;
                    case 'prev':
                        $row = $this->rows['last'];
                        $row['_isactive'] = 1;
                        break;
                }                
            }
            
        }
        $this->rows[$dir] = $row;
        $this->ph[$dir] = $this->getChunk($this->config[$dir . 'Tpl'], $row);

        return '';

    }

    function makeFirstLast($pos) {
        if ($collection = $this->getSiblings($pos, '_default', false, 1)) {
            $rows = $collection;
            $row = $rows[0];
            $row['_isself'] = $row['id'] == $this->config['id'] ? '1' : '0';
            $this->ph[$pos] = $this->getChunk($this->config[$pos . 'Tpl'], $row);
            $this->rows[$pos] = $row;
        }

        return '';

    }

    function getChunks($tpl, $rows, $output = array()) {
        if (count($rows) > 0) {
            foreach ($rows as $row) {
                $output[] = $this->getChunk($tpl, $row);
            }
        }
        return $output;
    }

    function getSiblings($dir, $parent = '_default', $fromhere = true, $limit = '_default') {

        $parent = $parent == '_default' ? $this->config['parent'] : $parent;
        $limit = $limit == '_default' ? $this->config['limit'] : $limit;

        //$sortby = $this->config['sortBy'];
        $id = $this->config['id'];
        $classname = 'modResource';
        $selectfields = $this->modx->getOption('selectfields', $this->config, '');
        $selectfields = !empty($selectfields) ? explode(',', $selectfields) : null;
        $c = $this->modx->newQuery($classname);
        $c->select($this->modx->getSelectColumns($classname, $classname, '', $selectfields));

        if (empty($this->config['showDeleted'])) {
            $c->where(array('deleted' => '0'));
        }
        if (empty($this->config['showUnpublished'])) {
            $c->where(array('published' => '1'));
        }
        if (empty($this->config['showHidden'])) {
            $c->where(array('hidemenu' => '0'));
        }
        if (!empty($this->config['exclude'])) {
            $exclude = explode(',', $this->config['exclude']);
            $c->where(array('id:NOT IN' => $exclude));
        }
        if (!empty($this->config['where'])) {
            $where = $this->modx->fromJson($this->config['where']);
            $c->where($where);
        }

        $c->where(array('parent' => $parent));

        if ($dir != 'first' && $dir != 'last') {
            $c->where(array('id:!=' => $id));
        }

        //echo '<br /><h2>'.$dir . '</h2><br />';

        switch ($dir) {

            case 'first':
            case 'next':
            case 'up':
                $this->sort($c);

                if ($fromhere) {
                    $sortfield1 = $this->sortfields[0];
                    $sortfield2 = $this->sortfields[1];

                    $sortvalue1 = $this->rawValues[$sortfield1];
                    $sortvalue2 = $this->rawValues[$sortfield2];
                    $greater_lower1 = $this->sortdirs[0] == 'DESC' ? '<' : '>';
                    $greater_lower2 = $this->sortdirs[1] == 'DESC' ? '<' : '>';

                    //select resources to the right - fall back to filter by the second sortfield (default:id), on duplicate sortvalues
                    $where = 'IF(' . $sortfield1 . ' = ' . $this->modx->quote($sortvalue1) . ',' . $sortfield2 . ' ' . $greater_lower2 . ' ' . $this->modx->quote($sortvalue2) . ',' . $sortfield1 . ' ' . $greater_lower1 .
                        ' ' . $this->modx->quote($sortvalue1) . ')';
                    //echo $where.'<br />';
                    $c->where($where, xPDOQuery::SQL_AND);
                }

                break;

            case 'last':
            case 'prev':
            case 'down':
                $this->sort($c, true);
                if ($fromhere) {
                    $sortfield1 = $this->sortfields[0];
                    $sortfield2 = $this->sortfields[1];
                    $sortvalue1 = $this->rawValues[$sortfield1];
                    $sortvalue2 = $this->rawValues[$sortfield2];
                    $greater_lower1 = $this->sortdirs[0] == 'DESC' ? '<' : '>';
                    $greater_lower2 = $this->sortdirs[1] == 'DESC' ? '<' : '>';
                    //select resources to the left - fall back to filter by the second sortfield (default:id), on duplicate sortvalues
                    $where = 'IF(' . $sortfield1 . ' = ' . $this->modx->quote($sortvalue1) . ',' . $sortfield2 . ' ' . $greater_lower2 . ' ' . $this->modx->quote($sortvalue2) . ',' . $sortfield1 . ' ' . $greater_lower1 .
                        ' ' . $this->modx->quote($sortvalue1) . ')';
                    //echo $where.'<br />';
                    $c->where($where, xPDOQuery::SQL_AND);
                }

                break;
        }

        if ($limit) {
            $c->limit($limit);
        }
        $c->prepare();

        if (!empty($this->config['debug'])) {
            echo '<h3> Query for ' . $dir . '</h3>';
            echo $c->toSql() . '<br />';
        }

        if ($collection = $this->getCollection($c)) {
            return $collection;
        } else {
            return false;
        }

    }

    public function getCollection($c) {
        $rows = array();
        if ($c->stmt->execute()) {
            if (!$rows = $c->stmt->fetchAll(PDO::FETCH_ASSOC)) {
                $rows = array();
            }
        }
        return $rows;
    }

    function sort(&$c, $reverse = false) {
        $sortby = $this->config['sortBy'];
        if (!empty($sortby)) {
            if (strpos($sortby, '{') === 0) {
                $sorts = $this->modx->fromJSON($sortby);
            }
            if (is_array($sorts)) {
                $this->sortfields = array();
                $this->sortdirs = array();
                while (list($sort, $dir) = each($sorts)) {
                    if ($reverse) {
                        $dir = $dir == 'ASC' ? 'DESC' : 'ASC';
                    }
                    //echo $sort.' '.$dir.'<br />';
                    $this->sortfields[] = $sort;
                    $this->sortdirs[] = $dir;
                    $c->sortby($sort, $dir);
                }
                if (!in_array('id', $this->sortfields)) {
                    $sort = 'id';
                    $dir = $this->sortdirs[0];
                    //echo $sort.' '.$dir.'<br />';
                    $this->sortfields[] = $sort;
                    $this->sortdirs[] = $dir;
                    $c->sortby($sort, $dir);
                }

            }
        }
        return;
    }


    /**
     * Gets a Chunk and caches it; also falls back to file-based templates
     * for easier debugging.
     *
     * @access public
     * @param string $name The name of the Chunk
     * @param array $properties The properties for the Chunk
     * @return string The processed content of the Chunk
     */
    public function getChunk($name, array $properties = array()) {
        $chunk = null;
        if (!isset($this->chunks[$name])) {
            $chunk = $this->modx->getObject('modChunk', array('name' => $name), true);
            if (empty($chunk)) {
                $chunk = $this->_getTplChunk($name, $this->config['chunkSuffix']);
                if ($chunk == false)
                    return false;
            }
            $this->chunks[$name] = $chunk->getContent();
        } else {
            $o = $this->chunks[$name];
            $chunk = $this->modx->newObject('modChunk');
            $chunk->setContent($o);
        }
        $chunk->setCacheable(false);
        return $chunk->process($properties);
    }

    /**
     * Returns a modChunk object from a template file.
     *
     * @access private
     * @param string $name The name of the Chunk. Will parse to name.chunk.html by default.
     * @param string $suffix The suffix to add to the chunk filename.
     * @return modChunk/boolean Returns the modChunk object if found, otherwise
     * false.
     */
    private function _getTplChunk($name, $suffix = '.chunk.tpl') {
        $chunk = false;
        $f = $this->config['chunksPath'] . strtolower($name) . $suffix;
        if (file_exists($f)) {
            $o = file_get_contents($f);
            $chunk = $this->modx->newObject('modChunk');
            $chunk->set('name', $name);
            $chunk->setContent($o);
        }
        return $chunk;
    }

}
