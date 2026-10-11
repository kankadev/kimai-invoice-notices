# Invoice Notices für Kimai

Zentral gepflegte, mehrsprachige Hinweisprofile für Rechnungen — von **Akay, kanka.dev**.

**Stand: frühe Entwicklungsvorschau.** Profilbearbeitung, Sprachverwaltung, native Kundenauswahl und Rechnungsmodell-Anbindung sind implementiert. Historische Snapshots und PDF-Abnahme stehen aus. Keine freigegebene Version; nicht produktiv installieren.

Administratoren können Profile mit einem kurzen Rechnungshinweis und ausführlicheren, sortierbaren Textblöcken anlegen. Beim Kunden wird ein Profil ausgewählt. Die vorhandene native Rechnungsvorlage muss einmalig um die Ausgabe der bereitgestellten Texte ergänzt werden; diese PDF-Einbindung steht noch aus.

Die Oberfläche ist auf Englisch und Deutsch verfügbar. Inhaltssprachen werden anhand der Kunden und Rechnungsvorlagen angeboten; bereits gepflegte Sprachen bleiben erhalten und weitere können ergänzt werden. Texte werden nicht automatisch übersetzt. Im PDF gilt die Sprache des Rechnungsdokuments.

Jede gewünschte Übersetzung wird ausdrücklich aktiviert. Sprachen und Textblöcke hinzufügen oder umsortieren verändert zunächst nur das Formular. Erst „Profil speichern“ schreibt die Änderungen. Eine aktive, leere Übersetzung bedeutet ausdrücklich „keine Hinweise“. Nicht aktivierte Übersetzungen mit eingegebenen Texten werden beim Speichern beanstandet, damit nichts unbemerkt verloren geht.

Bei parallelen Änderungen bleiben die eigenen Eingaben sichtbar. Den aktuellen Stand in einem zweiten Tab öffnen und die Änderungen dort abgleichen; ein Neuladen des alten Formulars verwirft dessen ungespeicherte Eingaben. Die Vorschau speichert insgesamt höchstens 60.000 UTF-8-Bytes, 200 Profile und 20 Textblöcke je Sprache. Die Übersicht zeigt den belegten Speicher. Profile können deaktiviert, bislang aber nicht gelöscht werden.

Das Plugin berechnet keine Steuern oder Rabatte und verändert keine Rechnungsbeträge. Ein Profil ist keine steuerliche Prüfung. Die rechtliche Anwendbarkeit und die verwendeten Texte verantwortet der Betreiber.

Invoice Mail bleibt unabhängig. Auch externe Angebote benötigen zunächst weder eine API-Verbindung noch einen vorher angelegten Kimai-Kunden. Eine spätere Profil-Schnittstelle bleibt optional.

Details: [Entwurf](DESIGN.md), [Entwicklungsstand und Tests](DEVELOPMENT.md), [Aufgaben](../TODO.md), [englische Projektbeschreibung](../README.md).

Kontakt: [mail@kanka.dev](mailto:mail@kanka.dev). Lizenz: AGPL-3.0-or-later.
