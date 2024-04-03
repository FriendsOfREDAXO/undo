<?php

namespace FriendsOfRedaxo\Undo;

use Exception;
use rex;
use rex_clang;
use rex_sql;
use rex_sql_util;

class Undo
{
    /* will also save categories, as it's in the same table */
    public static function saveArticle(int $id): void
    {
        $art = rex_sql::factory();
        $art->setQuery('INSERT INTO '.rex::getTable('article_undo').' SELECT * FROM '.rex::getTable('article').' WHERE id=?', [$id]);
        $art->setQuery('INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE article_id=?', [$id]);
    }

    public static function deleteQueue(): void
    {
        $art = rex_sql::factory();
        $art->setQuery('SELECT id FROM '.rex::getTable('article_undo').' LIMIT 1');
        $slice = rex_sql::factory();
        $slice->setQuery('SELECT id FROM '.rex::getTable('article_slice_undo').' LIMIT 1');

        if ($art->getRows() || $slice->getRows()) {
            $art->setQuery('DELETE FROM '.rex::getTable('article_undo'));
            $art->setQuery('DELETE FROM '.rex::getTable('article_slice_undo'));
        }
    }

    public static function fixSlicePrio(int $article_id, int $ctype, int $slice_revision = 0): void
    {
        // dump($article_id);
        // dump($ctype);
        rex_sql_util::organizePriorities(
            rex::getTable('article_slice'),
            'priority',
            'article_id='.(int) $article_id.' AND clang_id='.(int) rex_clang::getCurrentId().' AND ctype_id='.(int) $ctype.' AND revision='.(int) $slice_revision,
            'priority, updatedate DESC'
        );
    }

    public static function saveSlice(int $id): void
    {
        $slice = rex_sql::factory();
        $slice->setQuery('INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE id=? AND clang_id=?', [$id, rex_clang::getCurrentId()]);
    }

    public static function fixTables(): bool
    {
        try {
            $art = rex_sql::factory();
            $art->setQuery('DROP TABLE IF EXISTS '.rex::getTable('article_undo'));
            $art->setQuery('DROP TABLE IF EXISTS '.rex::getTable('article_slice_undo'));

            $art->setQuery('CREATE TABLE IF NOT EXISTS '.rex::getTable('article_undo').' LIKE '.rex::getTable('article'));
            $art->setQuery('CREATE TABLE IF NOT EXISTS '.rex::getTable('article_slice_undo').' LIKE '.rex::getTable('article_slice'));

            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
