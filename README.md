# undo

Mit diesem AddOn kann ein gelöschter Artikel, Slice oder eine gelöschte Kategorie wiederhergestellt werden. Das Wiederherstellen funktioniert 30 Sekunden lang. Sobald die Seite oder ein neuer Tab geladen wird, ist ein Revert nicht mehr möglich (soll nur vor versehentlichem Löschen schützen).

Das Addon zeigt nun einen visuellen Countdown an, der Redakteuren anzeigt, wie viel Zeit für die Wiederherstellung noch zur Verfügung steht.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/undo/assets/screenshot.png)

## Features

- Wiederherstellung gelöschter Artikel
- Wiederherstellung gelöschter Kategorien
- Wiederherstellung gelöschter Content-Slices
- Visueller Countdown zeigt die verbleibende Zeit (30 Sekunden) für die Undo-Aktion an
- Mehrsprachige Unterstützung (DE, EN, ES, FR, PT, SV)

## Changelog

### Version 3.1.0 (18. April 2025)

* Visueller Countdown für die Undo-Funktion hinzugefügt
* Umstellung auf rex_sql_util statt direkter SQL-Befehle
* Verbesserte Update-Routine für Tabellen
* Code-Modernisierung mit verbesserten Typdefinitionen und PHPDoc
* PhpStan-Kompatibilität verbessert
* Strikte Typprüfung in allen Dateien eingeführt
* Mehrsprachige Übersetzungen erweitert

### Version 3.0.0

* Namespace
* Refactor für PHP 8.1
* CS-Fixes

### Version 2.1.2

* Bugfixing für Redaxo 5.7+
* Es wird jetzt nicht mehr mit jedem Pagereload gelöscht, sondern es müssen mindestens 30 Sekunden vergangen sein.

### Version 2.1.0

* Unterstützung für Slices
* Refactoring
* Bugfixing

### Version 2.0.2

* Auto-Repair Funktion, wenn der Core oder ein AddOn die rex_article oder rex_article_slice verändert _(Das AddOn muss nicht mehr neu installiert werden.)_

### Version 2.0.1

* Support für mehrsprachige Seiten
* Performanceoptimierungen
* Priorität wurde behoben
* Man springt nicht mehr in den Kategorie-Root zurück

## Konfiguration

Dieses AddOn hat keine Konfigurationsparameter. Die Aktionslinks mit dem Countdown erscheinen automatisch, sobald ein Artikel, Slice oder eine Kategorie gelöscht wurden.

## Installation

Hinweis: dies ist kein Plugin!

* Release herunterladen und entpacken.
* Ordner umbenennen in `undo`.
* In den Addons-Ordner legen: `/redaxo/src/addons`.

Oder den REDAXO-Installer / ZIP-Upload AddOn nutzen!

## Voraussetzungen

* REDAXO >= 5.13.0
* structure AddOn

## ToDo

* Undo für Module
* Undo für Templates


