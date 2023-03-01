SiblingNav
--------------------
Author: Bruno Perner <b.perner@gmx.de>
--------------------

 * To navigate next, previous from the current resource or from the given resource
 * It can also show all or limited left/right and first/last siblings
 * Its also possible to navigate to childs of more than one parents 

Feel free to suggest ideas/improvements/bugs on GitHub:
http://github.com/Bruno17/siblingnav/issues

--------------------
Property - List 


| PROPERTY          | DEFAULT          | DESCRIPTION                                            |
|-------------------|------------------|--------------------------------------------------------|
| rowTpl            | snrow            | chunk for siblings                                     |
| selfTpl           | snself           | chunk for active row                                   |
| prevTpl           | snprev           | chunk for previous-link                                |
| nextTpl           | snnext           | chunk for next-link                                    |
| firstTpl          | snfirst          | chunk for link to first resource                       |
| lastTpl           | snlast           | chunk for link to last resource                        |
| placeholderPrefix | sn.              | example: [[+sn.rows]]                                  |
| id                | modx-recource-id | the resourceid from where to get the siblings          |
| parents           | false            | commaseperated, get siblings from more than one parent |
| showDeleted       | 0                | 
| showUnpublished   | 0                |
| showHidden        | 0                |
| sortOrder         | ASC              |
| sortBy            | menuindex        | 
| limit             | false            | 
