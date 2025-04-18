<?php

use rex;
use rex_sql_table;

// Lösche die Undo-Tabellen bei der Deinstallation des Addons

// Lösche die Artikel-Undo-Tabelle und die Slice-Undo-Tabelle, falls sie existieren
$articleUndoTable = rex_sql_table::get(rex::getTable('article_undo'));
$articleUndoTable?->exists() && $articleUndoTable->drop();

$sliceUndoTable = rex_sql_table::get(rex::getTable('article_slice_undo'));
$sliceUndoTable?->exists() && $sliceUndoTable->drop();