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
use rex_url;
use rex_sql;
use rex_view;
use function rex_addon;
use function rex_request;
use function rex_session;
use function rex_set_session;

if (rex::isBackend() && rex::getUser() !== null) {
    // JavaScript und CSS für den Countdown laden
    rex_view::addJsFile(rex_url::addonAssets('undo', 'undo.js'));
    rex_view::addCssFile(rex_url::addonAssets('undo', 'undo.css'));
    
    rex_perm::register('undo[]');

    // Check for vars in url
    $mode = rex_request('undo_mode', 'string', '');
    $aid = rex_request('aid', 'int', 0);
    $article_id = rex_request('article_id', 'int', 0);
    $category_id = rex_request('category_id', 'int', 0);
    $slice_restore_id = rex_request('slice_restore_id', 'int', 0);
    $type = rex_request('type', 'string', '');
    $ctype = rex_request('ctype', 'int', 1);
    $deleteQueue = true;
    $isApiCall = (bool)rex_request('rex_api_call', 'string', '');

    /*
    ARTIKEL UND KATEGORIEN
    */

    // Listen on EP to save a tmp state
    rex_extension::register('ART_PRE_DELETED', static function (rex_extension_point $ep): void {
        $content = $ep->getParams();

        try {
            Undo::saveArticle((int)$content['id']);
        } catch (Exception) {
            if (Undo::fixTables()) {
                Undo::saveArticle((int)$content['id']);
            }
        }
    });

    rex_extension::register('SLICE_DELETE', static function (rex_extension_point $ep): void {
        $content = $ep->getParams();

        try {
            Undo::saveSlice((int)$content['slice_id']);
        } catch (Exception) {
            if (Undo::fixTables()) {
                Undo::saveSlice((int)$content['slice_id']);
            }
        }
    });

    // output message with undo-link for articles
    rex_extension::register('ART_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return '<div class="undo-message">' . rex_i18n::msg('article_deleted') . ' <a class="undo-link" data-pjax="false" href="?page=structure&undo_mode=undo&type=art&category_id=' . $category_id . '&aid=' . (int)$content['id'] . '&clang=' . rex_clang::getCurrentId() . '">' . rex_i18n::msg('undo_undo_action') . '</a>. ' . rex_i18n::msg('undo_countdown_text') . ' <span id="undo-countdown">30</span> ' . rex_i18n::msg('undo_countdown_seconds') . '.</div>';
    });

    // output message with undo-link for slices
    rex_extension::register('SLICE_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return '<div class="undo-message">' . rex_i18n::msg('block_deleted') . ' <a class="undo-link" data-pjax="false" href="?page=content/edit&undo_mode=undo&pjax=false&mode=edit&type=slice&category_id=' . $category_id . '&article_id=' . (int)$content['article_id'] . '&slice_restore_id=' . (int)$content['slice_id'] . '&ctype=' . (int)$content['ctype'] . '&clang=' . rex_clang::getCurrentId() . '">' . rex_i18n::msg('undo_undo_action') . '</a>. ' . rex_i18n::msg('undo_countdown_text') . ' <span id="undo-countdown">30</span> ' . rex_i18n::msg('undo_countdown_seconds') . '.</div>';
    });

    // output message with undo-link for categories
    rex_extension::register('CAT_DELETED', static function (rex_extension_point $ep) use (&$deleteQueue, $category_id): string {
        $content = $ep->getParams();
        $deleteQueue = false;
        rex_set_session('undo_timestamp', time());

        return '<div class="undo-message">' . rex_i18n::msg('category_deleted') . ' <a class="undo-link" data-pjax="false" href="?page=structure&undo_mode=undo&type=cat&category_id=' . $category_id . '&aid=' . (int)$content['id'] . '&clang=' . rex_clang::getCurrentId() . '">' . rex_i18n::msg('undo_undo_action') . '</a>. ' . rex_i18n::msg('undo_countdown_text') . ' <span id="undo-countdown">30</span> ' . rex_i18n::msg('undo_countdown_seconds') . '.</div>';
    });

    // undo magic if link was clicked
    if ($mode === 'undo') {
        // we need to register late, or we wont be able to trigger ART_UPDATED / CAT_UPDATED
        rex_extension::register('PACKAGES_INCLUDED', static function (rex_extension_point $ep) use ($aid, $category_id, $type, $slice_restore_id, $ctype, $article_id): void {
            $outputMsg = '';

            if ($type === 'cat' || $type === 'art') {
                $sql = rex_sql::factory();
                $elements = $sql->getArray('SELECT name,catname,parent_id,catpriority,priority,status,clang_id,template_id FROM '.rex::getTable('article_undo').' where id=?', [$aid]);

                if ($sql->getRows() > 0) {
                    $i = 1;
                    foreach ($elements as $e) {
                        $parent_id = (int)$e['parent_id'];
                        $template_id = (int)$e['template_id'];
                        $status = (int)$e['status'];

                        $artpriority = (int)$e['priority'];
                        $artname = (string)$e['name'];

                        $catname = (string)$e['catname'];
                        $catpriority = (int)$e['catpriority'];

                        $clang = (int)$e['clang_id'];

                        $ART = rex_sql::factory();

                        try {
                            $ART->setQuery('INSERT INTO '.rex::getTable('article').' SELECT * FROM '.rex::getTable('article_undo').' where id=? and clang_id=?', [$aid, $clang]);
                        } catch (Exception) {
                            // Fehler beim Einfügen ignorieren
                        }

                        // slices just need to get inserted once
                        if ($i === 1) {
                            try {
                                $ART->setQuery('INSERT INTO '.rex::getTable('article_slice').' SELECT * FROM '.rex::getTable('article_slice_undo').' where article_id=?', [$aid]);
                            } catch (Exception) {
                                // Fehler beim Einfügen ignorieren
                            }
                        }

                        match ($type) {
                            'cat' => $outputMsg = rex_i18n::msg('undo_category_restored') and 
                                    rex_category_service::newCatPrio($parent_id, $clang, $catpriority, 0) and
                                    rex_category_service::editCategory($aid, $clang, ['catpriority' => $catpriority, 'catname' => $catname]),

                            default => $outputMsg = rex_i18n::msg('undo_article_restored') and
                                      rex_article_service::newArtPrio($category_id, $clang, $artpriority, 0) and
                                      rex_article_service::editArticle($aid, $clang, ['name' => $artname, 'template_id' => $template_id, 'priority' => $artpriority, 'status' => $status])
                        };
                        
                        ++$i;
                    }
                }
            } elseif ($type === 'slice') {
                $outputMsg = rex_i18n::msg('undo_slice_restored');
                $slice = rex_sql::factory();
                $slice->setQuery('INSERT INTO '.rex::getTable('article_slice').' SELECT * FROM '.rex::getTable('article_slice_undo').' WHERE id=?', [$slice_restore_id]);
                Undo::fixSlicePrio($article_id, $ctype);
                rex_article_cache::delete($article_id, rex_clang::getCurrentId());
                Undo::deleteQueue();
            }

            if ($outputMsg !== '') {
                rex_extension::register('PAGE_TITLE_SHOWN', static function (rex_extension_point $ep) use ($outputMsg): string {
                    return rex_view::success($outputMsg);
                });
            }
        }, rex_extension::LATE);
    } else {
        /* Undo-Action will only last for one page reload */
        $undoTimestamp = rex_session('undo_timestamp', 'int');
        if ($deleteQueue && (time() - $undoTimestamp) >= 30 && !$isApiCall) {
            Undo::deleteQueue();
            rex_set_session('undo_timestamp', time());
        }
    }
}
