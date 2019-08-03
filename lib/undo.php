<?php

/**
 * undo Addon.
 *
 * @author Friends Of REDAXO
 *
 * @var rex_addon
 */
class undo
{
    /* will also save categories, as it's in the same table */
    public static function saveArticle($id)
    {
        $art = rex_sql::factory();
        $art->setQuery('INSERT INTO '.rex::getTable('article_undo').' SELECT * FROM '.rex::getTable('article').' WHERE id=?', [$id]);
        $art->setQuery('INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE article_id=?', [$id]);
    }

    public static function deleteQueue()
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

    public static function fixSlicePrio($article_id, $ctype, $slice_revision = 0)
    {
        //dump($article_id);
        //dump($ctype);
        rex_sql_util::organizePriorities(
            rex::getTable('article_slice'),
            'priority',
            'article_id='.(int) $article_id.' AND clang_id='.(int) rex_clang::getCurrentId().' AND ctype_id='.(int) $ctype.' AND revision='.(int) $slice_revision,
            'priority, updatedate DESC'
        );
    }

    public static function saveSlice($id)
    {
        $slice = rex_sql::factory();
        $slice->setQuery('INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE id=? AND clang_id=?', [$id, rex_clang::getCurrentId()]);
    }

    public static function fixTables()
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
