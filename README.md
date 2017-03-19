# undo
Mit diesem AddOn kann ein gelöschter Artikel, Slice oder eine gelöschte Kategorie wiederhergestellt werden. Das Wiederherstellen funktioniert nur einen einzigen Pageload lang. Sobald die Seite oder ein neuer Tab geladen wird, ist ein Revert nicht mehr möglich (soll nur vor versehentlichem Löschen schützen).

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/undo/assets/screenshot.png)

Changelog
------------
Version 2.1.0
* Unterstützung für Slices
* Refactoring
* Bugfixing

Version 2.0.2
* Auto-Repair Funktion, wenn der Core oder ein AddOn die rex_article oder rex_article_slice verändert _(Das AddOn muss nicht mehr neu installiert werden.)_

Version 2.0.1
* Support für mehrsprachige Seiten
* Performanceoptimierungen
* Priorität wurde behoben
* Man springt nicht mehr in den Kategorie-Root zurück

Settingspage
------------
Dieses AddOn hat keine Konfigurationsparameter. Die Aktionslinks erscheinen, sobald ein Artikel oder eine Kategorie gelöscht wurden.

Installation
------------
Hinweis: dies ist kein Plugin!

* Release herunterladen und entpacken.
* Ordner umbenennen in `undo`.
* In den Addons-Ordner legen: `/redaxo/src/addons`.

Oder den REDAXO-Installer / ZIP-Upload AddOn nutzen!

Voraussetzungen
------------

* REDAXO >= 5.3.0
* structure AddOn

ToDo
-----
* Undo für Module
* Undo für Templates
* Beachtung der Arbeitsversion

Ich würde gerne noch die Möglichkeit für Templates und Module anbieten, hierzu gibt es aber aktuell noch keine EPs. Sobald das REX-Core Team diese zur Verfügung gestellt hat, wird diese Funktionalität nachgereicht.

Weitere Hinweise
-----
* Die Arbeitsversion / Revision wird aktuell noch nicht wiederhergestellt.
