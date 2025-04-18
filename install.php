<?php

use rex;
use rex_sql_table;
use rex_sql_util;

// Diese Tabellen werden für die Undo-Funktionalität benötigt
// Sie sind identisch mit den Originaltabellen strukturiert

// Tabellen mit rex_sql_table löschen, wenn sie existieren
$articleUndoTable = rex_sql_table::get(rex::getTable('article_undo'));
$articleUndoTable?->exists() && $articleUndoTable->drop();

$sliceUndoTable = rex_sql_table::get(rex::getTable('article_slice_undo'));
$sliceUndoTable?->exists() && $sliceUndoTable->drop();

// Kopiere die Struktur der Artikel-Tabelle
rex_sql_util::copyTable(
    rex::getTable('article'),
    rex::getTable('article_undo')
);

// Kopiere die Struktur der Slice-Tabelle
rex_sql_util::copyTable(
    rex::getTable('article_slice'),
    rex::getTable('article_slice_undo')
);