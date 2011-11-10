/**
 * siblingNav
 * To navigate next, previous from the current resource or from the given resource
 * It can also show all or limited left/right and first/last siblings
 * Its also possible to navigate to childs of more than one parents 
 *
 *
 * Options
 *
 * id - The id where the direction is from. Default the current resource id
 * 
 * rowTpl - snrow
 * selfTpl - snself
 * prevTpl - snprev
 * nextTpl - snnext
 * firstTpl - snfirst
 * lastTpl - snlast
 * placeholderPrefix - .sn
 * id
 * parents - false
 * showDeleted - 0
 * showUnpublished - 0
 * showHidden - 0
 * ignoreHidden - false
 * sortOrder - 'ASC'
 * sortBy - menuindex
 * limit - false
 *
 * Example usage:
 * [[!selfLink? &id=`[[*id]]` &direction=`next` &tpl=`commonName`]]
 *
 * @author Bruno Perner <b.perner@gmx.de>
 * @version 0.2-rc1
 */

//$nav = $modx->getService('siblingnav','SiblingNav',$modx->getOption('siblingnav.core_path',null,$modx->getOption('core_path').'components/siblingnav/').'model/siblingnav/',$scriptProperties);

include_once $modx->getOption('siblingnav.core_path',null,$modx->getOption('core_path').'components/siblingnav/model/siblingnav/').'siblingnav.class.php';
$nav = new SiblingNav($modx,$scriptProperties);

if (!($nav instanceof SiblingNav)) return '';


return $nav->run();