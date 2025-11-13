# Willkommen bei ulrich.digital

## UD Plugin: Reinigung

Digitales Reinigungssystem für Küchen, Räume oder Geräte mit Echtzeit-Status, Aufgabenverwaltung und übersichtlicher Darstellung im Frontend.  
Entwickelt für den Einsatz in Produktions- oder Gastronomiebetrieben, um Reinigungsabläufe einfach, transparent und live nachvollziehbar zu machen.


## Funktionen

- **Gutenberg-Block „UD Reinigung“**  
  Anzeige und Steuerung von Reinigungsaufgaben direkt im Frontend – inkl. Statuswechsel (offen, in Arbeit, abgeschlossen).

- **Echtzeit-Synchronisierung (Ably)**  
  Änderungen werden sofort auf allen verbundenen Geräten aktualisiert.

- **REST-API**
  - `/ud-reinigung/v1/tasks` – Aufgaben abrufen oder erstellen  
  - `/ud-reinigung/v1/status` – Statusänderungen live übermitteln  
  - `/ud-reinigung/v1/overview` – Tages- oder Raumübersicht generieren  

- **Admin-Tools**
  - Verwaltung offener und erledigter Reinigungen  
  - Schnellbearbeitung direkt in der Listenansicht  
  - Live-Totals (z. B. Anzahl offener Aufgaben)  
  - Optionaler Export oder Archivierung älterer Einträge  

- **Frontend-Ansicht**
  - Farbige Statusanzeige (z. B. Grün = erledigt, Rot = offen)  
  - Live-Aktualisierung ohne Reload  
  - Filter nach Raum, Datum oder Verantwortlicher Person  

- **Technische Merkmale**
  - Build mit `@wordpress/scripts` (Webpack 5, SCSS → CSS, ESNext)  
  - Kompatibel mit Gutenberg ≥ WP 6.7  
  - Komponente-Props `__next40pxDefaultSize` und `__nextHasNoMarginBottom` gesetzt  
  - FSE-kompatibel, Theme `ulrichdigital_block_theme`




## Screenshots

![Frontend-Ansicht](./assets/ud-reservation.webp)
*Eine Mitarbeiterin an der Rezeption verwaltet digitale Reservationen direkt am Tablet. Die Anzeige im Hintergrund zeigt den aktuellen Buchungsstatus in Echtzeit.*

![Editor-Ansicht](./assets/ud-reservation_reservation.webp)
*Übersicht über alle Reservationen im Frontend.*

![Editor-Ansicht](./assets/ud-reservation_hinzufuegen.webp)
*Reservationen im Frontend hinzufügen und bearbeiten.*

![Editor-Ansicht](./assets/ud-reservation_statistik.webp)
*Erfassung von Produktion, Lieferung und Verkauf.*

![Editor-Ansicht](./assets/ud-reservation_mockup.webp)
*Automatische Anzeige der aktuellen Reservationen und Tagesmenüs in Echtzeit.*



---

## Installation

1. Repository in den Plugin-Ordner von WordPress kopieren:  
   `/wp-content/plugins/ud-reinigung/`
2. Plugin im WordPress-Backend aktivieren.  
3. Block **„UD Reinigung“** im Seiten- oder Beitragseditor hinzufügen.  
4. Aufgaben und Verantwortlichkeiten im Backend definieren.  
5. Änderungen erscheinen automatisch auf allen verbundenen Displays.

---

## Anforderungen

- WordPress 6.7 oder neuer  
- PHP 8.0 oder höher  
- Aktives Theme `ulrichdigital_block_theme`  
- (Optional) Ably-API-Key für Echtzeit-Übertragung

---

## Autor

[ulrich.digital gmbh](https://ulrich.digital)

---

## Lizenz

Alle Rechte vorbehalten. Dieses Plugin ist urheberrechtlich geschützt und darf ohne ausdrückliche schriftliche Genehmigung der **ulrich.digital gmbh** weder kopiert, verbreitet, verändert noch weiterverwendet werden.
