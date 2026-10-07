<?php
namespace Exam31\Ticket;

use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;

class AdminLink
{
	public const ADMIN_URL = '/bitrix/admin/';
    public const MENU_SELECTOR = '.menu-items-body';

    public static function onEpilog(): void
    {
        if (defined('ADMIN_SECTION') && ADMIN_SECTION === true) {
            return;
        }

        if (!CurrentUser::get()->isAdmin()) {
            return;
        }

        Extension::load('ui.buttons');
        $params = Json::encode([
                'url' => self::ADMIN_URL,
                'title' => Loc::getMessage('EXAM31_TICKET_ADMIN_LINK_TITLE'),
                'selector' => self::MENU_SELECTOR,
        ]);
        Asset::getInstance()->addString('
            <script>
            BX.ready(function () {
                var params = ' . $params . '; 
                var target = document.querySelector(params.selector);
                if (!target) { return; }
                var link = BX.create("a", {
                    attrs: { href: params.url, className: "ui-btn ui-btn-sm ui-btn-border" },
                    style: { margin: "15px 0 0 18px" },
                    text: params.title
                });
                target.appendChild(link);
            });
            </script>'
        );
    }
}
