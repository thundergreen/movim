<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Presence extends Model
{
    protected $primaryKey = 'session_id';

    protected $attributes = [
        'session_id'    => SESSION_ID,
        'muc'    => false
    ];

    protected $fillable = [
        'session_id',
        'jid',
        'resource'
    ];

    public static function findByStanza($stanza)
    {
        $jid = explode('/',(string)$stanza->attributes()->from);
        return self::firstOrNew([
            'session_id' => SESSION_ID,
            'jid' => $jid[0],
            'resource' => isset($jid[1]) ? $jid[1] : ''
        ]);
    }

    public function set($stanza)
    {
        $jid = explode('/',(string)$stanza->attributes()->from);
        $this->jid = $jid[0];

        if (isset($jid[1])) {
            $this->resource = $jid[1];
        } else {
            $this->resource = '';
        }

        if ($stanza->status) {
            $this->status = (string)$stanza->status;
        }

        if ($stanza->c) {
            $this->node = (string)$stanza->c->attributes()->node;
            $this->ver = (string)$stanza->c->attributes()->ver;
        }

        $this->priority = ($stanza->priority) ? (int)$stanza->priority : 0;

        if ((string)$stanza->attributes()->type == 'error') {
            $this->value = 6;
        } elseif ((string)$stanza->attributes()->type == 'unavailable') {
            $this->value = 5;
        } elseif ((string)$stanza->show == 'away') {
            $this->value = 2;
        } elseif ((string)$stanza->show == 'dnd') {
            $this->value = 3;
        } elseif ((string)$stanza->show == 'xa') {
            $this->value = 4;
        } else {
            $this->value = 1;
        }

        // Specific XEP
        if ($stanza->x) {
            foreach ($stanza->children() as $name => $c) {
                switch ($c->attributes()->xmlns) {
                    /*case 'jabber:x:signed' :
                        $this->publickey = (string)$c;
                        break;*/
                    case 'http://jabber.org/protocol/muc#user' :
                        if (!isset($c->item)) break;

                        $this->muc = true;
                        if ($c->item->attributes()->jid
                        && $c->item->attributes()->jid) {
                            $this->mucjid = cleanJid((string)$c->item->attributes()->jid);
                        } else {
                            $this->mucjid = (string)$stanza->attributes()->from;
                        }

                        if ($c->item->attributes()->role) {
                            $this->mucrole = (string)$c->item->attributes()->role;
                        }
                        if ($c->item->attributes()->affiliation) {
                            $this->mucaffiliation = (string)$c->item->attributes()->affiliation;
                        }
                        break;
                    /*case 'vcard-temp:x:update' :
                        $this->photo = true;
                        break;*/
                }
            }
        }

        if ($stanza->delay) {
            $this->delay = gmdate(
                'Y-m-d H:i:s',
                strtotime(
                    (string)$stanza->delay->attributes()->stamp
                )
            );
        }

        if ($stanza->query) {
            $this->last = (int)$stanza->query->attributes()->seconds;
        }
    }
}
