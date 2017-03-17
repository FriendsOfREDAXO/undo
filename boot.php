<?php

/**
 * undo Addon.
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

    // Listen on EP to save a tmp state
    rex_extension::register('ART_PRE_DELETED', function (rex_extension_point $ep) {
        $content = $ep->getParams();
        $ART = rex_sql::factory();

        try {
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_undo SELECT * FROM '.rex::getTablePrefix().'article where id=?', [$content['id']]);
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_slice_undo SELECT * FROM '.rex::getTablePrefix().'article_slice where article_id=?', [$content['id']]);
        } catch (Exception $e) {
            /* Table was changed, we need to reinstall it */
            $ART->setQuery('DROP TABLE IF EXISTS '.rex::getTable('article_undo'));
            $ART->setQuery('DROP TABLE IF EXISTS '.rex::getTable('article_slice_undo'));

            $ART->setQuery('CREATE TABLE IF NOT EXISTS '.rex::getTable('article_undo').' LIKE '.rex::getTable('article'));
            $ART->setQuery('CREATE TABLE IF NOT EXISTS '.rex::getTable('article_slice_undo').' LIKE '.rex::getTable('article_slice'));

            // Retry last insert undo action
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_undo SELECT * FROM '.rex::getTablePrefix().'article where id=?', [$content['id']]);
            $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_slice_undo SELECT * FROM '.rex::getTablePrefix().'article_slice where article_id=?', [$content['id']]);
        }

    });

    // output message with undo-link for articles
    rex_extension::register('ART_DELETED', function (rex_extension_point $ep) use (&$deleteQueue, $category_id) {
        $content = $ep->getParams();
        $deleteQueue = false;

        return rex_i18n::msg('article_deleted')." <a href='?page=structure&mode=undo&type=art&category_id=".$category_id.'&aid='.$content['id'].'&clang='.rex_clang::getCurrentId()."'>".rex_i18n::msg('undo_undo_action').'</a>.';
    });

    // output message with undo-link for categories
    rex_extension::register('CAT_DELETED', function (rex_extension_point $ep) use (&$deleteQueue, $category_id) {
        $content = $ep->getParams();
        $deleteQueue = false;

        return rex_i18n::msg('category_deleted')." <a href='?page=structure&mode=undo&type=cat&category_id=".$category_id.'&aid='.$content['id'].'&clang='.rex_clang::getCurrentId()."'>".rex_i18n::msg('undo_undo_action').'</a>.';
    });

    // undo magic if link was clicked
    if ($mode == 'undo') {

        // we need to register late, or we wont be able to triggr ART_UPDATED / CAT_UPDATED
        rex_extension::register('PACKAGES_INCLUDED', function (rex_extension_point $ep) use ($mode, $aid, $category_id, $type) {

            $outputMsg = '';

            $sql = rex_sql::factory();
            //$sql->setDebug();
            $elements = $sql->getArray('SELECT name,catname,parent_id,catpriority,priority,status,clang_id,template_id FROM '.rex::getTablePrefix().'article_undo where id=?', [$aid]);

            if ($sql->getRows()) {
                $i = 1;
                foreach ($elements as $e) {
                    $parent_id = $e['parent_id'];
                    $template_id = $e['template_id'];
                    $status = $e['status'];

                    $artpriority = $e['priority'];
                    $artname = $e['name'];

                    $catname = $e['catname'];
                    $catpriority = $e['catpriority'];

                    $clang = $e['clang_id'];

                    $ART = rex_sql::factory();
                    $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article SELECT * FROM '.rex::getTablePrefix().'article_undo where id=? and clang_id=?', [$aid, $clang]);
                    // slices just need to get inserted once
                    if ($counter ===  1) {
                        $ART->setQuery('INSERT INTO '.rex::getTablePrefix().'article_slice SELECT * FROM '.rex::getTablePrefix().'article_slice_undo where article_id=?', [$aid]);
                    }

                    switch ($type) {
                        case 'cat':
                        $outputMsg = rex_i18n::msg('undo_category_restored');
                        rex_category_service::newCatPrio($parent_id, $clang, $catpriority, 0);
                        rex_category_service::editCategory($aid, $clang, array('catpriority' => $catpriority, 'catname' => $catname));
                        break;

                        default:
                        $outputMsg = rex_i18n::msg('undo_article_restored');
                        rex_article_service::newArtPrio($category_id, $clang, $artpriority, 0);
                        rex_article_service::editArticle($aid, $clang, array('name' => $artname, 'template_id' => $template_id, 'priority' => $artpriority, 'status' => $status));
                        break;
                    }
                    ++$i;
                }

                rex_extension::register('PAGE_TITLE', function (rex_extension_point $ep) use ($outputMsg) {
                    return rex_view::success($outputMsg);
                });
            }

        }, rex_extension::LATE);
    } else {
        /* Undo-Action will only last for one page reload */
        if ($deleteQueue) {
            $ART = rex_sql::factory();
            $ART->setQuery('SELECT id FROM '.rex::getTablePrefix().'article_undo LIMIT 1');
            if ($ART->getRows()) {
                $ART->setQuery('DELETE FROM '.rex::getTablePrefix().'article_undo');
                $ART->setQuery('DELETE FROM '.rex::getTablePrefix().'article_slice_undo');
            }
        }
    }
}
