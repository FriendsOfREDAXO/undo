<?php

namespace FriendsOfRedaxo\Undo;

use Exception;
use rex;
use rex_article_cache;
use rex_article_service;
use rex_category_service;
use rex_clang;
use rex_extension;
use rex_extension_point;
use rex_i18n;
use rex_perm;
use rex_sql;
use rex_view;

use function is_object;
use function rex_request;
use function rex_session;

if (rex::isBackend() && is_object(rex::getUser())) {
    rex_perm::register('undo[]');

    // Check for vars in url
    $mode = rex_request('undo_mode', 'string', false);
    $aid = rex_request('aid', 'int', false);
    $article_id = rex_request('article_id', 'int', false);
    $category_id = rex_request('category_id', 'int', false);
    $slice_restore_id = rex_request('slice_restore_id', 'int', false);
    $type = rex_request('type', 'string', '');
    $ctype = rex_request('ctype', 'int', 1);
    $deleteQueue = true;
    $isApiCall = rex_request('rex_api_call', 'string', false);

    /*
    ARTIKEL UND KATEGORIEN
    */

    // Listen on EP to save a tmp state
    rex_extension::register('ART_PRE_DELETED', static function (rex_extension_point $ep): void {
        $content = $ep->getParams();

        try {
            Undo::saveArticle($content['id']);
        } catch (Exception $e) {
            if (Undo::fixTables()) {
                Undo::saveArticle($content['id']);
            }
        }
    });

    rex_extension::register('SLICE_DELETE', static function (rex_extension_point $ep): void {
        $content = $ep->getParams();

        try {
            Undo::saveSlice($content['slice_id']);
        } catch (Exception $e) {
            if (Undo::fixTables()) {
                Undo::saveSlice($content['slice_id']);
            }
        }
    });

    // output message with undo-link for articles
    rex_extension::register('ART_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return rex_i18n::msg('article_deleted')." <a data-pjax='false' href='?page=structure&undo_mode=undo&type=art&category_id=".$category_id.'&aid='.$content['id'].'&clang='.rex_clang::getCurrentId()."'>".rex_i18n::msg('undo_undo_action').'</a>.';
    });

    // output message with undo-link for slices
    rex_extension::register('SLICE_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return rex_i18n::msg('block_deleted')." <a data-pjax='false' href='?page=content/edit&undo_mode=undo&pjax=false&mode=edit&type=slice&category_id=".$category_id.'&article_id='.$content['article_id'].'&slice_restore_id='.$content['slice_id'].'&ctype='.$content['ctype'].'&clang='.rex_clang::getCurrentId()."'>".rex_i18n::msg('undo_undo_action').'</a>.';
    });

    // output message with undo-link for categories
    rex_extension::register('CAT_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return rex_i18n::msg('category_deleted')." <a data-pjax='false' href='?page=structure&undo_mode=undo&type=cat&category_id=".$category_id.'&aid='.$content['id'].'&clang='.rex_clang::getCurrentId()."'>".rex_i18n::msg('undo_undo_action').'</a>.';
    });

    // undo magic if link was clicked
    if ($mode == 'undo') {
        // we need to register late, or we wont be able to triggr ART_UPDATED / CAT_UPDATED
        rex_extension::register('PACKAGES_INCLUDED', static function (rex_extension_point $ep) use ($aid, $category_id, $type, $slice_restore_id, $ctype, $article_id): void {
            $outputMsg = '';

            if ($type == 'cat' || $type == 'art') {
                $sql = rex_sql::factory();
                $elements = $sql->getArray('SELECT name,catname,parent_id,catpriority,priority,status,clang_id,template_id FROM '.rex::getTable('article_undo').' where id=?', [$aid]);

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

                        try {
                            $ART->setQuery('INSERT INTO '.rex::getTable('article').' SELECT * FROM '.rex::getTable('article_undo').' where id=? and clang_id=?', [$aid, $clang]);
                        } catch (Exception $e) {
                        }

                        // slices just need to get inserted once
                        if ($i === 1) {
                            try {
                                $ART->setQuery('INSERT INTO '.rex::getTable('article_slice').' SELECT * FROM '.rex::getTable('article_slice_undo').' where article_id=?', [$aid]);
                            } catch (Exception $e) {
                            }
                        }

                        switch ($type) {
                            case 'cat':
                                $outputMsg = rex_i18n::msg('undo_category_restored');
                                rex_category_service::newCatPrio($parent_id, $clang, $catpriority, 0);
                                rex_category_service::editCategory($aid, $clang, ['catpriority' => $catpriority, 'catname' => $catname]);
                                break;

                            default:
                                $outputMsg = rex_i18n::msg('undo_article_restored');
                                rex_article_service::newArtPrio($category_id, $clang, $artpriority, 0);
                                rex_article_service::editArticle($aid, $clang, ['name' => $artname, 'template_id' => $template_id, 'priority' => $artpriority, 'status' => $status]);
                                break;
                        }
                        ++$i;
                    }
                }
            } elseif ($type == 'slice') {
                $outputMsg = rex_i18n::msg('undo_slice_restored');
                $slice = rex_sql::factory();
                $slice->setQuery('INSERT INTO '.rex::getTable('article_slice').' SELECT * FROM '.rex::getTable('article_slice_undo').' WHERE id=?', [$slice_restore_id]);
                Undo::fixSlicePrio($article_id, $ctype);
                rex_article_cache::delete($article_id, rex_clang::getCurrentId());
                Undo::deleteQueue();
            }

            if ($outputMsg) {
                rex_extension::register('PAGE_TITLE_SHOWN', static function (rex_extension_point $ep) use ($outputMsg) {
                    return rex_view::success($outputMsg);
                });
            }
        }, rex_extension::LATE);
    } else {
        /* Undo-Action will only last for one page reload */
        if ($deleteQueue && (time() - rex_session('undo_timestamp', 'int')) >= 30 && !$isApiCall) {
            Undo::deleteQueue();
            rex_set_session('undo_timestamp', time());
        }
    }
}
