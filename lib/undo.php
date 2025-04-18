<?php

namespace FriendsOfRedaxo\Undo;

use Exception;
use rex;
use rex_clang;
use rex_sql;
use rex_sql_table;
use rex_sql_util;

/**
 * Undo-Klasse zum Wiederherstellen von gelöschten Artikeln, Kategorien und Slices
 */
class Undo
{
    /**
     * Speichert einen Artikel und seine Slices für die Wiederherstellung
     * Wird auch für Kategorien verwendet, da sie in der gleichen Tabelle gespeichert sind
     *
     * @throws Exception wenn ein Datenbankfehler auftritt
     */
    public static function saveArticle(int $id): void
    {
        $art = rex_sql::factory();
        $art->setQuery('INSERT INTO '.rex::getTable('article_undo').' SELECT * FROM '.rex::getTable('article').' WHERE id=?', [$id]);
        $art->setQuery('INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE article_id=?', [$id]);
    }

    /**
     * Löscht die Wiederherstellungswarteschlange
     * 
     * @throws Exception wenn ein Datenbankfehler auftritt
     */
    public static function deleteQueue(): void
    {
        $art = rex_sql::factory();
        $art->setQuery('SELECT id FROM '.rex::getTable('article_undo').' LIMIT 1');
        $slice = rex_sql::factory();
        $slice->setQuery('SELECT id FROM '.rex::getTable('article_slice_undo').' LIMIT 1');

        if ($art->getRows() > 0 || $slice->getRows() > 0) {
            $art->setQuery('DELETE FROM '.rex::getTable('article_undo'));
            $art->setQuery('DELETE FROM '.rex::getTable('article_slice_undo'));
        }
    }

    /**
     * Behebt die Priorität der Slices
     */
    public static function fixSlicePrio(int $article_id, int $ctype, int $slice_revision = 0): void
    {
        rex_sql_util::organizePriorities(
            rex::getTable('article_slice'),
            'priority',
            'article_id='.$article_id.' AND clang_id='.rex_clang::getCurrentId().' AND ctype_id='.$ctype.' AND revision='.$slice_revision,
            'priority, updatedate DESC'
        );
    }

    /**
     * Speichert einen Slice für die Wiederherstellung
     * 
     * @throws Exception wenn ein Datenbankfehler auftritt
     */
    public static function saveSlice(int $id): void
    {
        // Überprüfen, ob der Slice bereits als gelöscht gespeichert wurde
        $checkSlice = rex_sql::factory();
        $checkSlice->setQuery(
            'SELECT id FROM '.rex::getTable('article_slice_undo').' WHERE id = ? AND clang_id = ?',
            [$id, rex_clang::getCurrentId()]
        );
        
        // Nur speichern, wenn der Slice noch nicht in der Undo-Tabelle existiert
        if ($checkSlice->getRows() == 0) {
            $slice = rex_sql::factory();
            $slice->setQuery(
                'INSERT INTO '.rex::getTable('article_slice_undo').' SELECT * FROM '.rex::getTable('article_slice').' WHERE id=? AND clang_id=?', 
                [$id, rex_clang::getCurrentId()]
            );
        }
    }

    /**
     * Behebt die Tabellenstruktur des Undo-AddOns
     * Wird zur Reparatur der Tabellen verwendet
     */
    public static function fixTables(): bool
    {
        try {
            // Tabellen mit rex_sql_table löschen, wenn sie existieren
            $articleUndoTable = rex_sql_table::get(rex::getTable('article_undo'));
            if ($articleUndoTable->exists()) {
                $articleUndoTable->drop();
            }
            
            $sliceUndoTable = rex_sql_table::get(rex::getTable('article_slice_undo'));
            if ($sliceUndoTable->exists()) {
                $sliceUndoTable->drop();
            }
            
            // Tabellen mit rex_sql_util kopieren (leere Tabellen)
            rex_sql_util::copyTable(
                rex::getTable('article'),
                rex::getTable('article_undo')
            );
            
            rex_sql_util::copyTable(
                rex::getTable('article_slice'),
                rex::getTable('article_slice_undo')
            );

            return true;
        } catch (Exception) {
            return false;
        }
    }
}
