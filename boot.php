<?php
/**
 * media_manager_autorewrite Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */

if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('undo[]');

    // Check for vars in url
    $mode = rex_request('mode', 'string', false);
    $aid = rex_request('aid', 'int', false);
    $category_id = rex_request('category_id', 'int', false);
    $type = rex_request('type', 'string', '');
    $deleteQueue = true;


    /*
        ARTIKEL UND KATEGORIEN
    */

    // An Extionsion-Point andocken
    rex_extension::register('ART_PRE_DELETED', function (rex_extension_point $ep) {
        $content = $ep->getParams();

        // Daten in die temporäre Tabelle kopieren
        $ART = rex_sql::factory();
        $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_undo SELECT * FROM '.rex::getTablePrefix().'article where id=? and clang_id=?', [$content['id'], rex_clang::getStartId()]);
        $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_slice_undo SELECT * FROM '.rex::getTablePrefix().'article_slice where article_id=?', [$content['id']]);

    });

    rex_extension::register('ART_DELETED', function (rex_extension_point $ep) use (&$deleteQueue, $category_id) {
        $content = $ep->getParams();
        $deleteQueue = false;

        return rex_i18n::msg('article_deleted') . " <a href='?page=structure&mode=undo&type=art&category_id=".$category_id.'&aid='.$content['id']."'>".rex_i18n::msg('undo_undo_action')."</a>.";
    });

    rex_extension::register('CAT_DELETED', function (rex_extension_point $ep) use (&$deleteQueue) {
        $content = $ep->getParams();
        $deleteQueue = false;

        return rex_i18n::msg('category_deleted') . " <a href='?page=structure&mode=undo&type=cat&aid=".$content['id']."'>".rex_i18n::msg('undo_undo_action')."</a>.";
    });

    if ($mode == 'undo') {
        $outputMsg = '';

        $sql = rex_sql::factory();
        $sql->setQuery('SELECT parent_id,catpriority,priority FROM '.rex::getTablePrefix().'article_undo where id=? and clang_id=?', [$aid, rex_clang::getStartId()]);

        if ($sql->getRows()) {
            $parent_id = $sql->getValue('parent_id');
            $artpriority = $sql->getValue('priority');
            $catpriority = $sql->getValue('catpriority');

            $ART = rex_sql::factory();
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article SELECT * FROM '.rex::getTablePrefix().'article_undo where id=? and clang_id=?', [$aid, rex_clang::getStartId()]);
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_slice SELECT * FROM '.rex::getTablePrefix().'article_slice_undo where article_id=?', [$aid]);

            switch ($type) {
                case 'cat':
                $outputMsg = rex_i18n::msg('undo_category_restored');
                foreach (rex_clang::getAllIds() as $clang) {
                    rex_category_service::newCatPrio($parent_id, 0, 0, $catpriority);
                }

                break;

                default:
                $outputMsg = rex_i18n::msg('undo_article_restored');
                foreach (rex_clang::getAllIds() as $clang) {
                    rex_article_service::newArtPrio($aid, $clang, 0, $artpriority);
                }
                break;
            }

            rex_extension::register('PAGE_TITLE', function (rex_extension_point $ep) use ($outputMsg) {
                return rex_view::success($outputMsg);
            });


            // clear the cache
            rex_delete_cache();
        }
    }


    /*
        MODULE
    */



    /*
        TEMPLATES
    */

    /* Undo-Action will only last for one page reload */
    if ($deleteQueue) {
        $ART = rex_sql::factory();
        $ART->setQuery('DELETE FROM '.rex::getTablePrefix().'article_undo');
        $ART->setQuery('DELETE FROM '.rex::getTablePrefix().'article_slice_undo');
    }
}
