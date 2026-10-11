# Invoice Notices für Kimai

Zentrale mehrsprachige Rechnungshinweise von **Akay — kanka.dev**. Das Plugin verwaltet Profile und Texte. Es berechnet keine Steuern, Rabatte oder Beträge und versendet keine E-Mails.

## Einrichtung

Kimai ab 2.67, PHP ab 8.2, JSON und mbstring. Getestet wurden Kimai 2.67.0 und 2.69.0 mit PHP 8.3. Andere Versionen zuerst in einer Testumgebung prüfen.

Datenbank, Originalrechnungen, Vorlagen und Plugins sichern. Das Release nach `var/plugins/KankaInvoiceNoticesBundle` entpacken; dort muss direkt `KankaInvoiceNoticesBundle.php` liegen. Als Anwendungsbenutzer im Kimai-Verzeichnis `php -d memory_limit=512M bin/console cache:clear --env=prod --no-debug` ausführen. Bei Docker das persistente Plugin-Verzeichnis verwenden und den Befehl im Anwendungscontainer ausführen. Keine eigene Migration oder zusätzlicher Dienst nötig.

Das Menü **Invoice Notices** benötigt `system_configuration`. Profile anlegen, ihren Verwendungszweck beschreiben, Übersetzungen aktivieren und kurze Hinweise/geordnete Erklärungstexte pflegen. Die native Twig-Rechnungsvorlage einmalig nach [dieser Anleitung](TEMPLATES.md) anpassen. Beliebige Vorlagen werden nicht automatisch geändert.

## Anwendung

Beim Kunden **Rechnungshinweisprofil** auswählen. Textsprachen ergeben sich aus Kunden, Rechnungsvorlagen und vorhandenen Übersetzungen. Weitere Kimai-Sprachen können ergänzt werden. Das übersetzt keine Texte. Entscheidend ist die tatsächliche Rechnungssprache; auch Sprachvarianten müssen vorhanden sein. Fehlende Übersetzungen und fehlende/deaktivierte zugeordnete Profile blockieren die Erzeugung mit einer erklärenden Meldung.

Ohne Profil werden keine Hinweise ausgegeben. Eine aktivierte leere Übersetzung ist eine ausdrückliche Entscheidung für leeren Inhalt. Eingegebene Texte einer deaktivierten Übersetzung werden nicht stillschweigend gelöscht. Sprach-/Blockaktionen bearbeiten nur das Formular; **Profil speichern** speichert alles. Veraltete Formulare überschreiben keine neueren Änderungen; die eigene Eingabe bleibt bei Konflikten sichtbar.

In der Rechnungsauswahl lässt sich das Profil für einen Export ändern oder ohne Hinweise exportieren. Die Ausnahme gilt für **alle Rechnungen der Auswahl** und verändert keine Kundenstandards. PDF-Vorschau prüfen und danach wieder die Kundenprofile auswählen. Eine Ausnahme nicht als Standardsuche speichern.

## Historie und Wiederherstellung

Neue Rechnungen erhalten unveränderliche Metadaten mit aufgelösten Texten, Sprache, Profilkennung, Revision, Renderer und Dokumenthash. Profil- und Statusänderungen verändern sie nicht. Der Hash ist keine digitale Signatur oder rechtliche Prüfung. Historische Downloads lesen die gespeicherte Originaldatei. Alte Rechnungen werden nicht ergänzt oder regeneriert. Hochgeladene Dokumente ohne Renderablauf haben keinen generierten Snapshot. Vorschauen speichern keine Rechnung. Ob die aufgelösten Texte gedruckt werden, hängt von der angepassten Vorlage ab.

Profile lassen sich deaktivieren, aber in Version eins nicht dauerhaft löschen. Insgesamt gelten 60.000 UTF-8-Bytes, maximal 200 Profile mit je maximal 100 Übersetzungen und 20 Blöcken. Der Verbrauch wird angezeigt. Es entstehen keine zusätzlichen PDF-Kopien.

Beim Entfernen zuerst eine Vorlage ohne `invoice_notices` wiederherstellen, dann das Bundle aus dem aktiven Verzeichnis nehmen und den Cache leeren. Konfiguration, Kundenzuordnungen und Rechnungssnapshots bleiben erhalten; Neuinstallation nutzt sie wieder. Datenbank und Originalrechnungen gehören ins Backup.

Der Betreiber prüft Anwendbarkeit und Formulierungen. Land, Rechtsform und 0 % Steuer lösen keine automatische Entscheidung aus. Keine rechtlichen Musterprofile werden ausgeliefert. Texte sind reiner Text; HTML und Twig werden nicht ausgeführt. Doppelte alte Hinweise bei der Umstellung bewusst prüfen. Invoice Mail und externe Angebote bleiben eigenständig; Interessenten müssen nicht in Kimai angelegt werden.

[Prüfungen](DEVELOPMENT.md) · [Roadmap](../TODO.md) · [Sicherheitsmeldungen](../SECURITY.md). Entwickler: [kanka.dev](https://kanka.dev), [mail@kanka.dev](mailto:mail@kanka.dev). AGPL-3.0-or-later.
