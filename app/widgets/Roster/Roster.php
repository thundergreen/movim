<?php

use Moxl\Xec\Action\Roster\GetList;
use Moxl\Xec\Action\Roster\AddItem;
use Moxl\Xec\Action\Roster\RemoveItem;
use Moxl\Xec\Action\Presence\Subscribe;
use Moxl\Xec\Action\Presence\Unsubscribe;
use Moxl\Utils;

class Roster extends \Movim\Widget\Base
{
    function load()
    {
        $this->addcss('roster.css');
        $this->addjs('roster.js');
        $this->registerEvent('roster_getlist_handle', 'onRoster');
        $this->registerEvent('roster_additem_handle', 'onAdd');
        $this->registerEvent('roster_removeitem_handle', 'onDelete');
        $this->registerEvent('roster_updateitem_handle', 'onUpdate');
        $this->registerEvent('roster', 'onChange');
        $this->registerEvent('presence', 'onPresence');
    }

    function onChange($packet)
    {
        $this->rpc(
            'MovimTpl.fill',
            '#roster',
            $this->prepareItems()
        );
    }

    function onDelete($packet)
    {
        Notification::append(null, $this->__('roster.deleted'));
    }

    function onPresence($packet)
    {
        if ($packet->content != null){
            $html = $this->prepareItem(
                $packet->content
            );

            if ($html) {
                $this->rpc('MovimTpl.replace', '#'.cleanupId($packet->content->jid), $html);
            }
        }
    }

    function onAdd($packet)
    {
        Notification::append(null, $this->__('roster.added'));
    }

    function onUpdate($packet = false)
    {
        Notification::append(null, $this->__('roster.updated'));
    }

    function onRoster()
    {
        $this->onUpdate();
    }

    /**
     * @brief Force the roster refresh
     * @returns
     */
    function ajaxGetRoster()
    {
        $this->onRoster();
    }

    /**
     * @brief Force the roster refresh
     * @returns
     */
    function ajaxRefreshRoster()
    {
        $r = new GetList;
        $r->request();
    }

    /**
     * @brief Display the search contact form
     */
    /*function ajaxDisplaySearch($jid = null)
    {
        $view = $this->tpl();

        $rd = new \Modl\RosterLinkDAO;

        $view->assign('jid', $jid);
        $view->assign('groups', $rd->getGroups());
        $view->assign('search', $this->call('ajaxDisplayFound', 'this.value'));

        Dialog::fill($view->draw('_roster_search', true));
    }*/

    /**
     * @brief Return the found jid
     */
    /*function ajaxDisplayFound($jid)
    {
        if (!empty($jid)) {
            $cd = new \Modl\ContactDAO;
            $contacts = $cd->searchJid($jid);

            $view = $this->tpl();
            $view->assign('contacts', $contacts);
            $html = $view->draw('_roster_search_results', true);

            $this->rpc('MovimTpl.fill', '#search_results', $html);
        }
    }*/

    /**
     * @brief Add a contact to the roster and subscribe
     */
    function ajaxAdd($form)
    {
        $r = new AddItem;
        $r->setTo((string)$form->searchjid->value)
          ->setName((string)$form->alias->value)
          ->setGroup((string)$form->group->value)
          ->request();

        $p = new Subscribe;
        $p->setTo((string)$form->searchjid->value)
          ->request();

        Dialog::ajaxClear();
    }

    /**
     *  @brief Search for a contact to add
     */
    /*function ajaxSearchContact($jid)
    {
        if (filter_var($jid, FILTER_VALIDATE_EMAIL)) {
            $this->rpc('MovimUtils.redirect', $this->route('contact', $jid));
        } else {
            Notification::append(null, $this->__('roster.jid_error'));
        }
    }*/

    function prepareItems()
    {
        $view = $this->tpl();
        $view->assign('contacts', \App\User::me()->session->contacts);
        //$view->assign('presencestxt', getPresencesTxt());

        return $view->draw('_roster_list', true);
    }

    function prepareItem(App\Roster $contact)
    {
        $view = $this->tpl();
        $view->assign('contact', $contact);
        //$view->assign('presences', getPresences());
        //$view->assign('presencestxt', getPresencesTxt());

        return $view->draw('_roster_item', true);
    }
}
