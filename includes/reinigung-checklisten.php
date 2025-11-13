<?php
/**
 * Zentrale Referenz der Reinigungs-Checklisten
 * (definiert alle Bereiche und Aufgaben als Standard-Array)
 */

defined('ABSPATH') || exit;

/**
 * Gibt die Standard-Checklisten mit Aufgaben als einfache Arrays zurück.
 *
 * @return array
 */
function ud_reinigung_get_default_checklisten() {
	return [
		'Ess-Saal' => [
			'Tische und Bänke gereinigt und ausgerichtet',
			'Boden nass gereinigt',
			'Infowand Angebotstafel gewechselt (Folgetag)',
			'Garderobe geordnet',
			'Fundgegenstände deponiert',
			'alle Schränke abgeschlossen',
		],

		'Suppenküche / Materialschrank' => [
			'Ablagen und Tische gereinigt und aufgeräumt',
			'Kochutensilien gereinigt und deponiert',
			'Fensterreinigung sporadisch (ja / nein)',
			'Schneidemaschine (Käse) gereinigt',
			'Brotschneidebrett und Platz sauber',
			'Brotkörbe gereinigt, mit Servietten belegt',
			'Materialschrank und Wäsche aufgeräumt',
			'Trinkgläser korrekt deponiert (Öffnung oben)',
		],

		'Vorratsraum / Putzraum' => [
			'Kühlschränke kontrolliert',
			'Küchenmaterial korrekt versorgt',
			'Bodenreinigungsmaterial korrekt versorgt',
			'Getränke-Nachschub (ja / nein)',
			'Getränkelager und Leergut geordnet und deponiert',
			'Getränkelager kontrolliert',
			'leere Flaschen (Wein), Karton entsorgt',
		],

		'Abwaschküche' => [
			'Geschirrspülmaschine gereinigt, geöffnet, aus',
			'Spültröge und Spülbereich gereinigt',
			'Abwaschmaterial und Bürsten gereinigt und deponiert',
			'Stahlwatte brauchbar oder ersetzt',
			'Papierhandtuchautomat aufgefüllt',
			'Putzmittel genügend vorhanden / Fenstersims',
			'Schmutzschleuse und Teppich gereinigt',
			'Bodenreinigungsmaterial gereinigt und deponiert',
			'Bodenlappen gereinigt, zum Trocknen auf Putzkübel',
			'Nasse Wäsche korrekt aufgehängt',
			'Schmutzwäsche für Salon Proper deponiert',
			'Abfallsack in Abfallkübel ersetzt (max. ½ voll)',
		],

		'Maschinen / Apparate / Energie' => [
			'Lüftung aus',
			'Kaffeemaschine aus',
			'Kippkessel und Kippbräter aus',
			'Tellerwärmer aus',
			'Kochherd aus',
			'Tiefkühltruhe kontrolliert, geschlossen, an',
			'Kühlschränke kontrolliert, geschlossen, an',
			'Lampenkontrolle',
			'Licht (Essraum, Vorratsraum) aus',
			'Aussenlicht aus',
		],

		'Aussenbereich' => [
			'Schweinekübel sauber und verschlossen',
			'Platz Schweinekübel sauber und gereinigt',
			'Vorplatz Suppenküche sauber und gereinigt',
			'Streusalzvorrat kontrolliert und genügend',
		],

		'Pult / Admin' => [
			'Liste Reservation und Verbrauch aktualisiert',
			'Boardleiste: aktuelle / Folgeliste Reservation',
		],
	];
}

/**
 * Erstellt aus den Standardaufgaben ein leeres boolean-Array.
 *
 * @return array
 */
function ud_reinigung_get_empty_checklisten() {
	$result = [];
	foreach (ud_reinigung_get_default_checklisten() as $bereich => $aufgaben) {
		foreach ($aufgaben as $aufgabe) {
			$result[$bereich][$aufgabe] = false;
		}
	}
	return $result;
}

/**
 * 🔄 Validiert eine gespeicherte Checkliste gegen das aktuelle Standard-Set.
 * Ergänzt neue Aufgaben, entfernt alte und erhält erledigte Statuswerte.
 *
 * @param array $stored  Bereits gespeicherte Checkliste (vom CPT)
 * @return array         Synchronisierte Checkliste
 */
function ud_reinigung_sync_checklisten(array $stored) {
	$default = ud_reinigung_get_default_checklisten();
	$merged  = [];

	foreach ($default as $bereich => $aufgaben) {
		$merged[$bereich] = [];

		foreach ($aufgaben as $aufgabe) {
			// bereits gespeicherten Wert übernehmen, sonst false
			$merged[$bereich][$aufgabe] = isset($stored[$bereich][$aufgabe])
				? (bool) $stored[$bereich][$aufgabe]
				: false;
		}
	}

	// Optional: Bereiche entfernen, die es nicht mehr gibt
	foreach ($stored as $bereich => $_) {
		if (!isset($default[$bereich])) {
			unset($merged[$bereich]);
		}
	}

	return $merged;
}
