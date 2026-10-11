# Invoice Notices für Kimai

Zentral gepflegte, mehrsprachige Hinweisprofile für Rechnungen — von **Akay, kanka.dev**.

**Stand: frühe Entwicklungsvorschau.** Profilstruktur, native Kundenauswahl, Rechnungsmodell-Anbindung und eine nur lesbare Übersicht sind implementiert. Profilbearbeitung, historische Snapshots und PDF-Abnahme stehen aus. Keine freigegebene Version; nicht produktiv installieren.

Administratoren sollen Profile mit einem kurzen Rechnungshinweis und ausführlicheren, sortierbaren Textblöcken anlegen können. Beim Kunden wird ein Profil ausgewählt. Die vorhandene native Rechnungsvorlage gibt die bereitgestellten Texte an den gewünschten Stellen aus.

Die Oberfläche ist auf Englisch und Deutsch vorgesehen. Inhaltssprachen werden anhand der Kunden und Rechnungsvorlagen angeboten; bereits gepflegte Sprachen bleiben erhalten und weitere können ergänzt werden. Texte werden nicht automatisch übersetzt. Im PDF gilt die Sprache des Rechnungsdokuments.

Das Plugin berechnet keine Steuern oder Rabatte und verändert keine Rechnungsbeträge. Ein Profil ist keine steuerliche Prüfung. Die rechtliche Anwendbarkeit und die verwendeten Texte verantwortet der Betreiber.

Invoice Mail bleibt unabhängig. Auch externe Angebote benötigen zunächst weder eine API-Verbindung noch einen vorher angelegten Kimai-Kunden. Eine spätere Profil-Schnittstelle bleibt optional.

Details: [Entwurf](DESIGN.md), [Entwicklungsstand und Tests](DEVELOPMENT.md), [Aufgaben](../TODO.md), [englische Projektbeschreibung](../README.md).

Kontakt: [mail@kanka.dev](mailto:mail@kanka.dev). Lizenz: AGPL-3.0-or-later.
