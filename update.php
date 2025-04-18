<?php

use rex;
use rex_addon;
use rex_i18n;
use rex_sql_table;
use rex_sql_util;

// Update-Routine für das Undo-Addon
// Stellt sicher, dass alle notwendigen Tabellen vorhanden sind und ihre Struktur aktuell ist

// Entfernen der alten Tabellen, wenn sie existieren
$articleUndoTable = rex_sql_table::get(rex::getTable('article_undo'));
$articleUndoTable?->exists() && $articleUndoTable->drop();

$sliceUndoTable = rex_sql_table::get(rex::getTable('article_slice_undo'));
$sliceUndoTable?->exists() && $sliceUndoTable->drop();

// Neuerstellen der Tabellen mit aktueller Struktur
rex_sql_util::copyTable(
    rex::getTable('article'),
    rex::getTable('article_undo')
);

rex_sql_util::copyTable(
    rex::getTable('article_slice'),
    rex::getTable('article_slice_undo')
);

// Erfolgsmeldung für den Administrator
rex_addon::get('undo')->setProperty('successmsg', rex_i18n::msg('undo_tables_updated'));