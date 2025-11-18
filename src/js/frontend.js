import "../css/frontend.scss";
import { udConfirm } from "./helpers/confirm";

// ===========================================================
// 🔹 Initialisierung
// ===========================================================
if (document.readyState === "loading") {
	document.addEventListener("DOMContentLoaded", initUDReinigung);
} else {
	initUDReinigung();
}

// ===========================================================
// 🔹 Hauptfunktion
// ===========================================================
function initUDReinigung() {
	console.log("✅ UD Reinigung: init gestartet");

	const button = document.querySelector("#ud-start-reinigung");
	if (!button) return;

	const progressText = button.querySelector(".progress-text");
	const circle = button.querySelector(".progress");
	const label = button.querySelector(".label");

	// Kreisgrundwerte initialisieren
	if (circle) {
		const radius = circle.r.baseVal.value;
		const circumference = 2 * Math.PI * radius;
		circle.style.strokeDasharray = `${circumference}`;
		circle.style.strokeDashoffset = circumference;
	}

	if (progressText) progressText.textContent = "– lädt Fortschritt –";
	if (label) label.textContent = "Reinigung";

	// ===========================================================
	// 🔹 Datumshandling
	// ===========================================================
	// ===========================================================
	// 🔹 Datumshandling
	// ===========================================================
	// versucht aktuelles Datum aus dem Flatpickr-Input zu lesen
	const initialDateInput = document.querySelector(
		"#reservation-date-flatpickr"
	);
	const currentDate =
		initialDateInput?.value || new Date().toISOString().slice(0, 10);

	button.dataset.date = currentDate;

	// Beim Laden sofort Fortschritt holen
	loadProgress(currentDate);

	// 🔸 Datumsauswahl überwachen → Fortschritt neu laden (delegiert)
	document.addEventListener("change", (e) => {
		const target = e.target;
		if (!target || !(target instanceof HTMLInputElement)) return;
		if (target.id !== "reservation-date-flatpickr") return;

		const newDate = target.value;
		console.log("[UD-Reinigung] Datum geändert:", newDate);
		if (!newDate) return;

		button.dataset.date = newDate;
		loadProgress(newDate);
	});

	// ===========================================================
	// 🔹 Globale Schließen-Handler – nur EINMAL registrieren
	// ===========================================================
	(function registerCloseHandlers() {
		const modal = document.querySelector("#ud-reinigung-modal");
		if (!modal) return;

		const backdrop = modal.querySelector(".ud-reinigung-modal-backdrop");
		const closeBtn = modal.querySelector(".ud-reinigung-modal-close");
		const cancelBtn = modal.querySelector("#cancel-reinigung");

		// Backdrop & X
		[backdrop, closeBtn].forEach((el) => {
			el?.addEventListener("click", async () => {
				const modal = document.querySelector("#ud-reinigung-modal");
				if (!modal?.udReinigungData) {
					return closeModal(true);
				}

				if (hasUnsavedChanges(modal)) {
					await confirmClose(); // 🔥 wartet auf udConfirm
				} else {
					closeModal(true);
				}
			});
		});

		// Abbrechen-Button im Modal
		cancelBtn.addEventListener("click", async (e) => {
			e.preventDefault();

			if (hasUnsavedChanges(modal)) {
				await confirmClose(); // 🔥 jetzt korrekt
			} else {
				closeModal(true);
			}
		});
	})();

	// ===========================================================
	// 🔹 Klick öffnet Modal
	// ===========================================================
	button.addEventListener("click", async (e) => {
		//    const dateInput = document.querySelector("#reservation-date");
		const dateInput = document.querySelector("#reservation-date-flatpickr");

		const date = dateInput?.value || new Date().toISOString().slice(0, 10);

		button.dataset.date = date; // ← target ➜ button

		const modal = document.querySelector("#ud-reinigung-modal");
		if (!modal)
			return console.warn("⚠️ UD Reinigung: Modal nicht gefunden!");

		const backdrop = modal.querySelector(".ud-reinigung-modal-backdrop");
		const closeBtn = modal.querySelector(".ud-reinigung-modal-close");
		const loader = modal.querySelector("#ud-reinigung-loading");
		const checklistContainer = modal.querySelector(
			"#ud-reinigung-checklisten"
		);

		// ← target ➜ button
		button.classList.add("loading");
		const labelEl = button.querySelector(".label");
		if (labelEl) labelEl.textContent = "Reinigung";

		// ===========================================================
		// 🔹 Suppentag prüfen oder erstellen
		// ===========================================================
		let suppentagId = null;
		try {
			const resSuppen = await fetch(
				`/wp-json/ud-suppentag/v1/by-date?date=${date}`
			);
			const dataSuppen = await resSuppen.json();

			if (!dataSuppen?.id) {
				console.log("ℹ️ Kein Suppentag vorhanden – wird erstellt …");
			} else {
				suppentagId = dataSuppen.id;
				console.log("📄 Suppentag gefunden:", suppentagId);
			}
		} catch (err) {
			console.error("❌ Fehler bei Suppentag-Abfrage:", err);
		}

		// ===========================================================
		// 🔹 Reinigung laden
		// ===========================================================
		try {
			const res = await fetch(
				`/wp-json/ud-reinigung/v1/clean?date=${date}&_t=${Date.now()}`,
				{
					cache: "no-store",
				}
			);
			const data = await res.json();

			if (!data.checklisten)
				throw new Error("Keine Checklisten erhalten.");
			renderReinigungUI(data, modal, date);
		} catch (err) {
			console.error("❌ Fehler beim Laden der Reinigung:", err);
			checklistContainer.innerHTML =
				"<p>Fehler beim Laden der Reinigung.</p>";
		} finally {
			// ← target ➜ button
			button.classList.remove("loading");
			loader.hidden = true;
			checklistContainer.hidden = false;
		}

		// Modal öffnen
		modal.removeAttribute("hidden");
		document.body.style.overflow = "hidden";
	});

	// ===========================================================
	// 🔹 Fortschritt laden
	// ===========================================================
	async function loadProgress(date) {
		console.log("⏳ Lade Fortschritt für Datum:", date);
		try {
			const res = await fetch(
				`/wp-json/ud-reinigung/v1/clean?date=${date}&_t=${Date.now()}`,
				{
					cache: "no-store",
				}
			);
			const data = await res.json();

			if (!data || !data.checklisten) {
				updateButtonProgress({}, 0, 49);
				return;
			}

			const checklisten = data.checklisten;
			let total = 0,
				done = 0;
			Object.values(checklisten).forEach((aufgaben) => {
				total += Object.keys(aufgaben).length;
				done += Object.values(aufgaben).filter(Boolean).length;
			});

			if (total === 0) total = 49;
			updateButtonProgress(checklisten, done, total);
		} catch (err) {
			console.error("❌ Fehler beim Laden des Fortschritts:", err);
			updateButtonProgress({}, 0, 49);
		}
	}

	// ===========================================================
	// 🔹 Fortschritt im Button anzeigen
	// ===========================================================
	function updateButtonProgress(checklisten, done, total) {
		const percent = total > 0 ? (done / total) * 100 : 0;
		const textEl = document.querySelector(
			"#ud-start-reinigung .progress-text"
		);
		if (textEl) textEl.textContent = `${done} von ${total} erledigt`;

		const circle = document.querySelector("#ud-start-reinigung .progress");
		if (circle) {
			const radius = circle.r.baseVal.value;
			const circumference = 2 * Math.PI * radius;
			const offset = circumference - (percent / 100) * circumference;
			circle.style.strokeDashoffset = offset;

			// Farbe nach Fortschritt
			if (percent === 0) circle.style.stroke = "#dcdcdc";
			else if (percent < 50) circle.style.stroke = "#f5b700";
			else circle.style.stroke = "#11863a";
		}
	}

	// ===========================================================
	// 🔹 UI Rendering
	// ===========================================================
	function renderReinigungUI(data, modal, date) {
		const container = modal.querySelector("#ud-reinigung-checklisten");
		container.innerHTML = "";

		const { id: postId, checklisten, bemerkungen } = data;
		//modal.udReinigungData = { postId, checklisten, container, date };
		modal.udReinigungData = {
			postId,
			checklisten,
			container,
			date,
			original: JSON.stringify(checklisten), // 🔥 Originalzustand speichern
		};

		const ui = document.createElement("div");
		ui.className = "ud-reinigung-ui";
		ui.innerHTML = `
			<div class="ud-reinigung-sidebar"><ul id="ud-reinigung-sections"></ul></div>
			<div class="ud-reinigung-content">
				<h3 id="ud-reinigung-current-title"></h3>
				<div id="ud-reinigung-tasks"></div>
			</div>
		`;
		container.appendChild(ui);

		const footer = document.createElement("div");
		footer.className = "ud-reinigung-footer";
		footer.innerHTML = `<span id="ud-reinigung-progress">0 von 0 erledigt</span>`;
		container.appendChild(footer);

		const remarksSection = document.createElement("div");
		remarksSection.className = "ud-checklist-section";
		remarksSection.innerHTML = `
			<h3>Bemerkungen</h3>
			<textarea id="ud-reinigung-bemerkungen" rows="4" placeholder="Bemerkungen eintragen...">${
				bemerkungen || ""
			}</textarea>
		`;
		container.appendChild(remarksSection);

		const sidebar = ui.querySelector("#ud-reinigung-sections");
		const contentTitle = ui.querySelector("#ud-reinigung-current-title");
		const taskContainer = ui.querySelector("#ud-reinigung-tasks");
		let activeBereich = Object.keys(checklisten)[0];

		function renderSidebar() {
			sidebar.innerHTML = "";
			for (const [bereich, aufgaben] of Object.entries(checklisten)) {
				const total = Object.keys(aufgaben).length;
				const done = Object.values(aufgaben).filter(Boolean).length;

				const li = document.createElement("li");
				li.textContent = bereich;
				const status = document.createElement("span");
				status.className = "status";
				if (done === total && total > 0) status.classList.add("done");
				else if (done > 0) status.classList.add("partial");

				li.appendChild(status);
				if (bereich === activeBereich) li.classList.add("active");

				li.addEventListener("click", () => {
					activeBereich = bereich;
					renderSidebar();
					renderTasks(bereich);
				});
				sidebar.appendChild(li);
			}
		}

		function renderTasks(bereich) {
			contentTitle.textContent = bereich;
			taskContainer.innerHTML = "";

			const aufgaben = checklisten[bereich];
			for (const [aufgabe, checked] of Object.entries(aufgaben)) {
				const label = document.createElement("label");
				label.className = "ud-checklist-item";
				if (checked) label.classList.add("checked");

				const checkbox = document.createElement("input");
				checkbox.type = "checkbox";
				checkbox.checked = checked;
				checkbox.dataset.bereich = bereich;
				checkbox.dataset.aufgabe = aufgabe;

				const span = document.createElement("span");
				span.textContent = aufgabe;

				checkbox.addEventListener("change", () => {
					checklisten[bereich][aufgabe] = checkbox.checked;
					label.classList.toggle("checked", checkbox.checked);
					renderSidebar();
					updateProgress();
				});

				label.append(checkbox, span);
				taskContainer.appendChild(label);
			}
			updateProgress();
		}

		function updateProgress() {
			let total = 0,
				done = 0;
			Object.values(checklisten).forEach((aufgaben) => {
				total += Object.keys(aufgaben).length;
				done += Object.values(aufgaben).filter(Boolean).length;
			});

			const footerProgress = container.querySelector(
				"#ud-reinigung-progress"
			);
			if (footerProgress)
				footerProgress.textContent = `${done} von ${total} erledigt`;
			updateButtonProgress(checklisten, done, total);
		}

		renderSidebar();
		renderTasks(activeBereich);

		// Am Ende von renderReinigungUI:

		const saveBtn = modal.querySelector("#save-reinigung");
		if (saveBtn) {
			saveBtn.onclick = async () => {
				// statt addEventListener → überschreibt alte Listener
				const data = modal.udReinigungData;
				if (!data) return;

				modal.dataset.savedByButton = "1";
				showToast("Speichere Reinigung ...");

				await saveReinigung(
					data.postId,
					data.checklisten,
					modal.querySelector("#ud-reinigung-checklisten"), // ⬅ immer frisch holen
					data.date,
					false
				);

				//showToast("Reinigung gespeichert!");

				await loadProgress(data.date);

				modal.setAttribute("hidden", "");
				document.body.style.overflow = "";
			};
		}
	}

	/* =============================================================== *\
   Title
\* =============================================================== */
	function hasUnsavedChanges(modal) {
		if (!modal || !modal.udReinigungData) return false;

		const { checklisten, original } = modal.udReinigungData;
		return JSON.stringify(checklisten) !== original;
	}

function confirmClose() {
    const modal = document.querySelector("#ud-reinigung-modal");
    if (!modal || !modal.udReinigungData) {
        console.error("❌ confirmClose konnte Modal-Daten nicht finden");
        return closeModal(true);
    }

    const { postId, checklisten, container, date } = modal.udReinigungData;

    udConfirm(
        "Du hast Änderungen vorgenommen. Möchtest du speichern?",
        "Änderungen vorhanden",
        {

    okLabel: "Speichern",
    cancelLabel: "Nicht speichern",
            onSave: async () => {
                await saveReinigung(postId, checklisten, container, date, true);
                closeModal(true);
            },
            onDiscard: () => {
                closeModal(true);
            }
        }
    );
}


	// ===========================================================
	// 🔹 Schliessen
	// ===========================================================
	function closeModal() {
		const modal = document.querySelector("#ud-reinigung-modal");
		if (!modal) return;

		// Wenn Modal bereits geschlossen oder kein State → einfach zu
		const data = modal.udReinigungData;
		if (!data) {
			modal.setAttribute("hidden", "");
			document.body.style.overflow = "";
			return;
		}

		// Wenn durch Speichern-Button geschlossen → KEINE Aktion
		if (modal.dataset.savedByButton === "1") {
			modal.dataset.savedByButton = "0"; // zurücksetzen
			modal.setAttribute("hidden", "");
			document.body.style.overflow = "";
			return;
		}

		// ❗️KEIN Speichern mehr!
		// ❗️Nur Modal schliessen.
		modal.setAttribute("hidden", "");
		document.body.style.overflow = "";
	}

	// ===========================================================
	// 🔹 REST-POST: gesamte Reinigung speichern
	// ===========================================================
	async function saveReinigung(
		postId,
		checklisten,
		container,
		date,
		showToastMsg = true
	) {
		const bemerkungen =
			container
				.querySelector("#ud-reinigung-bemerkungen")
				?.value.trim() || "";

		try {
			const res = await fetch("/wp-json/ud-reinigung/v1/clean", {
				method: "POST",
				headers: {
					"Content-Type": "application/json",
					"X-WP-Nonce": udReinigungSettings?.nonce || "",
				},
				body: JSON.stringify({ date, checklisten, bemerkungen }),
			});

			const result = await res.json();

			if (result.success) {
				console.log("✅ Reinigung gespeichert:", result);
				if (showToastMsg)
					showToast("Reinigung erfolgreich gespeichert!");
				if (result.data && result.data.checklisten)
					Object.assign(checklisten, result.data.checklisten);
			}
		} catch (err) {
			console.error("❌ Fehler beim Speichern:", err);
			if (showToastMsg)
				showToast("Fehler beim Speichern der Reinigung!", true);
		}
	}

	// ===========================================================
	// 🔹 Toast-Meldung
	// ===========================================================
	function showToast(msg, isError = false) {
		const toast = document.createElement("div");
		toast.className =
			"ud-toast" + (isError ? " ud-toast--error" : " ud-toast--success");
		toast.textContent = msg;
		document.body.appendChild(toast);

		setTimeout(() => {
			toast.classList.add("ud-toast--visible");
		}, 10); // kleiner Delay für Transition

		setTimeout(() => {
			toast.classList.remove("ud-toast--visible");
			setTimeout(() => toast.remove(), 300); // nach Animation entfernen
		}, 2500);
	}
}
