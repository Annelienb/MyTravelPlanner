document.addEventListener("DOMContentLoaded", () => {
    const storageStatus = document.querySelector("#personal-trip-storage-status");

    const checklistStorageKey = "travelly.checklist.v1";
    const checklistContainer = document.querySelector("#checklist-items");
    const checklistCounter = document.querySelector("#packed-counter");
    const checklistForm = document.querySelector("#checklist-add-form");
    const checklistStatus = document.querySelector("#checklist-status");
    const checklistItemCount = document.querySelector("#checklist-item-count");
    const checklistItemCountLabel = document.querySelector("#checklist-item-count-label");
    const defaultChecklist = [
        { id: "passport", text: "Passport / ID", checked: false, custom: false },
        { id: "charger", text: "Charger", checked: false, custom: false },
        { id: "sunscreen", text: "Sunscreen", checked: false, custom: false },
    ];
    let checklistItems = defaultChecklist.map((item) => ({ ...item }));

    function saveChecklist() {
        try {
            localStorage.setItem(checklistStorageKey, JSON.stringify(checklistItems));
            return true;
        } catch (error) {
            if (checklistStatus) {
                checklistStatus.textContent = "Your checklist could not be saved in this browser.";
            }
            return false;
        }
    }

    function renderChecklist() {
        if (!checklistContainer) {
            return;
        }

        checklistContainer.replaceChildren();
        checklistItems.forEach((item) => {
            const row = document.createElement("div");
            row.className = "checklist-row";
            const label = document.createElement("label");
            label.className = "checklist-item";
            const checkbox = document.createElement("input");
            checkbox.type = "checkbox";
            checkbox.checked = item.checked;
            checkbox.addEventListener("change", () => {
                const previousState = item.checked;
                item.checked = checkbox.checked;
                if (!saveChecklist()) {
                    item.checked = previousState;
                    checkbox.checked = previousState;
                    return;
                }
                updateChecklistCounter();
                if (checklistStatus) {
                    checklistStatus.textContent = "Checklist saved in this browser.";
                }
            });
            label.append(checkbox, document.createTextNode(item.text));
            row.append(label);

            const removeButton = document.createElement("button");
            removeButton.type = "button";
            removeButton.className = "checklist-remove";
            removeButton.textContent = "Remove";
            removeButton.setAttribute("aria-label", `Remove ${item.text} from checklist`);
            removeButton.addEventListener("click", () => {
                const previousItems = checklistItems;
                checklistItems = checklistItems.filter((current) => current.id !== item.id);
                if (!saveChecklist()) {
                    checklistItems = previousItems;
                    return;
                }
                renderChecklist();
                if (checklistStatus) {
                    checklistStatus.textContent = `"${item.text}" removed from your checklist.`;
                }
            });
            row.append(removeButton);
            checklistContainer.append(row);
        });

        if (checklistItems.length === 0) {
            const emptyMessage = document.createElement("p");
            emptyMessage.className = "field-hint";
            emptyMessage.textContent = "Your checklist is empty. Add an item below.";
            checklistContainer.append(emptyMessage);
        }

        updateChecklistCounter();
    }

    function updateChecklistCounter() {
        if (checklistItemCount) {
            checklistItemCount.textContent = String(checklistItems.length);
        }
        if (checklistItemCountLabel) {
            checklistItemCountLabel.textContent = checklistItems.length === 1 ? "checklist item" : "checklist items";
        }
        if (checklistCounter) {
            const checkedCount = checklistItems.filter((item) => item.checked).length;
            checklistCounter.textContent = `${checkedCount} of ${checklistItems.length} packed`;
        }
    }

    try {
        const savedChecklist = JSON.parse(localStorage.getItem(checklistStorageKey) || "null");
        if (Array.isArray(savedChecklist)) {
            if (savedChecklist.length > 0 && savedChecklist.every((item) => typeof item === "boolean")) {
                checklistItems = defaultChecklist.map((item, index) => ({
                    ...item,
                    checked: savedChecklist[index] === true,
                }));
            } else {
                const validItems = savedChecklist.every((item) => (
                    item
                    && typeof item.id === "string"
                    && typeof item.text === "string"
                    && typeof item.checked === "boolean"
                ));
                if (validItems) {
                    checklistItems = savedChecklist.map((item) => ({
                        id: item.id,
                        text: item.text,
                        checked: item.checked,
                        custom: item.custom === true,
                    }));
                }
            }
        }
    } catch (error) {
        if (checklistStatus) {
            checklistStatus.textContent = "Your browser could not restore the saved checklist.";
        }
    }
    renderChecklist();

    if (checklistForm) {
        checklistForm.addEventListener("submit", (event) => {
            event.preventDefault();
            const input = document.querySelector("#checklist-new-item");
            const text = input.value.trim();
            if (!text) {
                return;
            }

            if (checklistItems.some((item) => item.text.toLocaleLowerCase() === text.toLocaleLowerCase())) {
                if (checklistStatus) {
                    checklistStatus.textContent = "That item is already on your checklist.";
                }
                return;
            }

            const previousItems = checklistItems;
            checklistItems = [...checklistItems, {
                id: typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(36).slice(2)}`,
                text,
                checked: false,
                custom: true,
            }];
            if (!saveChecklist()) {
                checklistItems = previousItems;
                return;
            }
            input.value = "";
            renderChecklist();
            if (checklistStatus) {
                checklistStatus.textContent = "Checklist item added and saved in this browser.";
            }
        });
    }

    const personalTripsStorageKey = "travelly.personal-trips.v1";
    const personalTripForm = document.querySelector("#personal-trip-form");
    const personalTripList = document.querySelector("#personal-trip-list");
    const personalTripError = document.querySelector("#personal-trip-error");
    const personalTripCount = document.querySelector("#personal-trip-count");
    const personalTripCountLabel = document.querySelector("#personal-trip-count-label");
    const cancelEditButton = document.querySelector("#personal-trip-cancel");
    const destinationInput = document.querySelector("#personal-trip-destination");
    const suggestionList = document.querySelector("#destination-suggestions");
    const destinationStatus = document.querySelector("#destination-status");
    const weatherSummary = document.querySelector("#trip-weather");
    const personalTripFormTitle = document.querySelector("#personal-trip-form-title");
    const personalTripSubmit = document.querySelector("#personal-trip-submit");
    const personalTripStartInput = document.querySelector("#personal-trip-start");
    let personalTrips = [];
    let selectedLocation = null;
    let searchTimer = null;
    let searchController = null;
    let weatherController = null;
    let currentWeather = null;

    function showTripError(message) {
        if (personalTripError) {
            personalTripError.textContent = message;
            personalTripError.hidden = !message;
        }
    }

    function readPersonalTrips() {
        try {
            const savedTrips = JSON.parse(localStorage.getItem(personalTripsStorageKey) || "[]");
            personalTrips = Array.isArray(savedTrips) ? savedTrips.filter((trip) => (
                trip
                && typeof trip.id === "string"
                && typeof trip.trip_name === "string"
                && typeof trip.destination === "string"
            )) : [];
        } catch (error) {
            personalTrips = [];
            if (storageStatus) {
                storageStatus.textContent = "Your browser could not load personal trips. Check its storage settings.";
            }
        }
    }

    function savePersonalTrips() {
        try {
            localStorage.setItem(personalTripsStorageKey, JSON.stringify(personalTrips));
            return true;
        } catch (error) {
            if (storageStatus) {
                storageStatus.textContent = "Your trip could not be saved. Check available browser storage.";
            }
            return false;
        }
    }

    function createTripText(tagName, className, text) {
        const element = document.createElement(tagName);
        if (className) {
            element.className = className;
        }
        element.textContent = text;
        return element;
    }

    function renderPersonalTrips() {
        if (!personalTripList) {
            return;
        }
        if (personalTripCount) {
            personalTripCount.textContent = String(personalTrips.length);
        }
        if (personalTripCountLabel) {
            personalTripCountLabel.textContent = personalTrips.length === 1 ? "personal trip" : "personal trips";
        }
        personalTripList.replaceChildren();
        if (personalTrips.length === 0) {
            personalTripList.append(createTripText("div", "empty-state personal-trip-empty", "Your personal trip list is empty. Add a trip to get started."));
            return;
        }

        personalTrips.forEach((trip) => {
            const card = document.createElement("article");
            card.className = "card personal-trip-card";
            card.append(createTripText("h3", "", trip.trip_name));
            card.append(createTripText("p", "personal-trip-destination", trip.country
                ? `${trip.destination}, ${trip.country}`
                : trip.destination));

            if (trip.start_date || trip.end_date) {
                card.append(createTripText("p", "personal-trip-date", [trip.start_date, trip.end_date].filter(Boolean).join(" – ")));
            }
            if (trip.weather) {
                card.append(createTripText("p", "personal-trip-weather", trip.weather));
            }
            if (trip.notes) {
                card.append(createTripText("p", "personal-trip-notes", trip.notes));
            }

            const actions = document.createElement("div");
            actions.className = "personal-trip-actions";
            const editButton = createTripText("button", "btn secondary", "Edit");
            editButton.type = "button";
            editButton.addEventListener("click", () => {
                personalTripForm.elements.id.value = trip.id;
                personalTripForm.elements.trip_name.value = trip.trip_name;
                personalTripForm.elements.destination.value = trip.destination;
                personalTripForm.elements.start_date.value = trip.start_date || "";
                personalTripForm.elements.end_date.value = trip.end_date || "";
                personalTripForm.elements.notes.value = trip.notes || "";
                personalTripForm.elements.latitude.value = trip.latitude ?? "";
                personalTripForm.elements.longitude.value = trip.longitude ?? "";
                personalTripForm.elements.country.value = trip.country || "";
                const hasCoordinates = trip.latitude !== "" && trip.latitude !== null && trip.latitude !== undefined
                    && trip.longitude !== "" && trip.longitude !== null && trip.longitude !== undefined;
                selectedLocation = hasCoordinates ? {
                    name: trip.destination,
                    country: trip.country || "",
                    latitude: Number(trip.latitude),
                    longitude: Number(trip.longitude),
                } : null;
                currentWeather = trip.weather ? { text: trip.weather } : null;
                if (personalTripFormTitle) {
                    personalTripFormTitle.textContent = "Edit your trip";
                }
                if (personalTripSubmit) {
                    personalTripSubmit.textContent = "Save changes";
                }
                if (cancelEditButton) {
                    cancelEditButton.hidden = false;
                }
                showTripError("");
                suggestionList.replaceChildren();
                suggestionList.hidden = true;
                updateDestinationStatus(selectedLocation
                    ? `${selectedLocation.name}${selectedLocation.country ? `, ${selectedLocation.country}` : ""} selected.`
                    : "Search worldwide destinations using Open-Meteo.");
                updateWeatherForecast();
                personalTripForm.scrollIntoView({ behavior: "smooth", block: "center" });
                personalTripForm.elements.trip_name.focus();
            });

            const deleteButton = createTripText("button", "btn danger", "Delete");
            deleteButton.type = "button";
            deleteButton.addEventListener("click", () => {
                if (!window.confirm(`Delete "${trip.trip_name}" from this browser?`)) {
                    return;
                }
                const previousTrips = personalTrips;
                personalTrips = personalTrips.filter((item) => item.id !== trip.id);
                if (!savePersonalTrips()) {
                    personalTrips = previousTrips;
                    return;
                }
                renderPersonalTrips();
            });

            actions.append(editButton, deleteButton);
            card.append(actions);
            personalTripList.append(card);
        });
    }

    function updateDestinationStatus(message) {
        if (destinationStatus) {
            destinationStatus.textContent = message;
        }
    }

    function updateWeatherForecast() {
        if (!weatherSummary) {
            return;
        }
        if (weatherController) {
            weatherController.abort();
            weatherController = null;
        }
        currentWeather = null;
        weatherSummary.textContent = "";
        if (!selectedLocation) {
            return;
        }

        const date = personalTripStartInput?.value || "";
        if (!date) {
            weatherSummary.textContent = "Choose a departure date to check the local forecast.";
            return;
        }

        const daysUntilTrip = (Date.parse(`${date}T00:00:00Z`) - Date.parse(`${new Date().toISOString().slice(0, 10)}T00:00:00Z`)) / 86400000;
        if (daysUntilTrip < 0) {
            weatherSummary.textContent = "Weather forecasts are available for upcoming travel dates.";
            return;
        }
        if (daysUntilTrip > 15) {
            weatherSummary.textContent = "A destination forecast will be available within 16 days of departure.";
            return;
        }

        const location = selectedLocation;
        const controller = new AbortController();
        weatherController = controller;
        weatherSummary.textContent = "Loading destination forecast…";
        const params = new URLSearchParams({
            latitude: String(location.latitude),
            longitude: String(location.longitude),
            daily: "weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max",
            timezone: "auto",
            forecast_days: "16",
        });

        fetch(`https://api.open-meteo.com/v1/forecast?${params}`, { signal: controller.signal })
            .then((response) => {
                if (!response.ok) {
                    throw new Error("Forecast service is unavailable.");
                }
                return response.json();
            })
            .then((forecast) => {
                if (selectedLocation !== location) {
                    return;
                }
                const daily = forecast.daily;
                const index = daily?.time?.indexOf(date) ?? -1;
                if (index < 0) {
                    weatherSummary.textContent = "Forecast not available for this date yet.";
                    return;
                }
                const weatherLabel = weatherCodeLabel(daily.weather_code[index]);
                const high = Math.round(daily.temperature_2m_max[index]);
                const low = Math.round(daily.temperature_2m_min[index]);
                const rain = daily.precipitation_probability_max?.[index];
                const text = `${weatherLabel}, ${low}–${high}°C${Number.isFinite(rain) ? `, ${rain}% chance of rain` : ""}`;
                currentWeather = { text, date };
                weatherSummary.textContent = `Forecast for ${date}: ${text}. Source: Open-Meteo.`;
            })
            .catch((error) => {
                if (error.name !== "AbortError") {
                    weatherSummary.textContent = "The forecast is temporarily unavailable. You can still save your trip.";
                }
            });
    }

    function weatherCodeLabel(code) {
        if (code === 0) return "Clear";
        if ([1, 2].includes(code)) return "Mostly clear";
        if (code === 3) return "Cloudy";
        if ([45, 48].includes(code)) return "Fog";
        if ([51, 53, 55, 56, 57].includes(code)) return "Drizzle";
        if ([61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return "Rain";
        if ([71, 73, 75, 77, 85, 86].includes(code)) return "Snow";
        if ([95, 96, 99].includes(code)) return "Thunderstorm";
        return "Variable conditions";
    }

    function renderDestinationSuggestions(results) {
        suggestionList.replaceChildren();
        if (!results.length) {
            suggestionList.hidden = true;
            updateDestinationStatus("No matching destinations found. You can still enter your destination manually.");
            return;
        }

        results.forEach((place) => {
            const button = createTripText(
                "button",
                "destination-suggestion",
                [place.name, place.admin1, place.country].filter(Boolean).join(", "),
            );
            button.type = "button";
            button.setAttribute("role", "option");
            button.addEventListener("click", () => {
                selectedLocation = {
                    name: place.name,
                    country: place.country || "",
                    latitude: place.latitude,
                    longitude: place.longitude,
                    timezone: place.timezone || "",
                };
                destinationInput.value = place.name;
                personalTripForm.elements.latitude.value = place.latitude;
                personalTripForm.elements.longitude.value = place.longitude;
                personalTripForm.elements.country.value = place.country || "";
                suggestionList.replaceChildren();
                suggestionList.hidden = true;
                destinationInput.setAttribute("aria-expanded", "false");
                updateDestinationStatus(`Location selected: ${[place.name, place.country].filter(Boolean).join(", ")}. Destination data: Open-Meteo.`);
                updateWeatherForecast();
            });
            suggestionList.append(button);
        });
        suggestionList.hidden = false;
        destinationInput.setAttribute("aria-expanded", "true");
        updateDestinationStatus("Choose a destination from the suggestions.");
    }

    if (destinationInput && suggestionList) {
        destinationInput.addEventListener("input", () => {
            selectedLocation = null;
            currentWeather = null;
            personalTripForm.elements.latitude.value = "";
            personalTripForm.elements.longitude.value = "";
            personalTripForm.elements.country.value = "";
            suggestionList.replaceChildren();
            suggestionList.hidden = true;
            destinationInput.setAttribute("aria-expanded", "false");
            if (weatherSummary) {
                weatherSummary.textContent = "";
            }

            const query = destinationInput.value.trim();
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            if (searchController) {
                searchController.abort();
                searchController = null;
            }
            if (query.length < 2) {
                updateDestinationStatus("Type at least 2 characters to search destinations.");
                return;
            }

            updateDestinationStatus("Searching destinations…");
            searchTimer = setTimeout(async () => {
                const controller = new AbortController();
                searchController = controller;
                try {
                    const params = new URLSearchParams({
                        name: query,
                        count: "6",
                        language: "en",
                        format: "json",
                    });
                    const response = await fetch(`https://geocoding-api.open-meteo.com/v1/search?${params}`, {
                        signal: controller.signal,
                    });
                    if (!response.ok) {
                        throw new Error("Destination search failed.");
                    }
                    const data = await response.json();
                    if (destinationInput.value.trim() === query) {
                        renderDestinationSuggestions(data.results || []);
                    }
                } catch (error) {
                    if (error.name !== "AbortError") {
                        updateDestinationStatus("Destination search is unavailable. You can still enter a destination manually.");
                    }
                }
            }, 300);
        });

        destinationInput.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                suggestionList.replaceChildren();
                suggestionList.hidden = true;
                destinationInput.setAttribute("aria-expanded", "false");
            }
        });

        document.addEventListener("click", (event) => {
            if (!suggestionList.contains(event.target) && event.target !== destinationInput) {
                suggestionList.hidden = true;
                destinationInput.setAttribute("aria-expanded", "false");
            }
        });

        personalTripStartInput?.addEventListener("change", updateWeatherForecast);
    }

    if (personalTripForm && personalTripList) {
        readPersonalTrips();
        renderPersonalTrips();
        personalTripForm.addEventListener("submit", (event) => {
            event.preventDefault();
            const formData = new FormData(personalTripForm);
            const tripName = String(formData.get("trip_name") || "").trim();
            const destination = String(formData.get("destination") || "").trim();
            const startDate = String(formData.get("start_date") || "");
            const endDate = String(formData.get("end_date") || "");

            if (!tripName || !destination) {
                showTripError("Enter a trip name and destination.");
                return;
            }
            if (startDate && endDate && endDate < startDate) {
                showTripError("The return date must be on or after the departure date.");
                return;
            }

            const tripId = String(formData.get("id") || "");
            const existingTrip = personalTrips.find((trip) => trip.id === tripId);
            const tripData = {
                id: tripId || (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(36).slice(2)}`),
                trip_name: tripName,
                destination,
                start_date: startDate,
                end_date: endDate,
                notes: String(formData.get("notes") || "").trim(),
                latitude: selectedLocation?.latitude ?? "",
                longitude: selectedLocation?.longitude ?? "",
                country: selectedLocation?.country || "",
                timezone: selectedLocation?.timezone || "",
                weather: currentWeather?.date === startDate ? currentWeather.text : "",
            };
            const previousTrips = personalTrips;
            personalTrips = existingTrip
                ? personalTrips.map((trip) => trip.id === tripId ? tripData : trip)
                : [tripData, ...personalTrips];

            if (!savePersonalTrips()) {
                personalTrips = previousTrips;
                return;
            }
            resetPersonalTripForm();
            renderPersonalTrips();
            if (storageStatus) {
                storageStatus.textContent = "Your personal trips are saved in this browser only.";
            }
        });
        cancelEditButton?.addEventListener("click", resetPersonalTripForm);
    }

    function resetPersonalTripForm() {
        personalTripForm.reset();
        personalTripForm.elements.id.value = "";
        personalTripForm.elements.latitude.value = "";
        personalTripForm.elements.longitude.value = "";
        personalTripForm.elements.country.value = "";
        selectedLocation = null;
        currentWeather = null;
        if (weatherSummary) {
            weatherSummary.textContent = "";
        }
        if (destinationStatus) {
            destinationStatus.textContent = "Search worldwide destinations using Open-Meteo.";
        }
        suggestionList?.replaceChildren();
        if (suggestionList) {
            suggestionList.hidden = true;
        }
        if (personalTripFormTitle) {
            personalTripFormTitle.textContent = "Add a trip";
        }
        if (personalTripSubmit) {
            personalTripSubmit.textContent = "Save my trip";
        }
        if (cancelEditButton) {
            cancelEditButton.hidden = true;
        }
        showTripError("");
    }

    const itineraryStorageKey = "travelly.itinerary.v1";
    const itineraryForm = document.querySelector("#itinerary-form");
    const itineraryList = document.querySelector("#itinerary-list");
    const itineraryStatus = document.querySelector("#itinerary-status");
    const itineraryCancel = document.querySelector("#itinerary-cancel");
    const itinerarySubmit = document.querySelector("#itinerary-submit");
    let itineraryItems = [];

    function saveItinerary() {
        try {
            localStorage.setItem(itineraryStorageKey, JSON.stringify(itineraryItems));
            return true;
        } catch (error) {
            if (itineraryStatus) {
                itineraryStatus.textContent = "Your itinerary could not be saved in this browser.";
            }
            return false;
        }
    }

    function renderItinerary() {
        itineraryList.replaceChildren();
        itineraryItems.sort((first, second) => (
            `${first.date}T${first.time || "00:00"}`.localeCompare(`${second.date}T${second.time || "00:00"}`)
        ));

        if (itineraryItems.length === 0) {
            itineraryList.append(createTripText("p", "itinerary-empty", "No plans added yet."));
            return;
        }

        itineraryItems.forEach((item) => {
            const card = document.createElement("article");
            card.className = "itinerary-item";
            card.append(createTripText("p", "itinerary-item-date", `${item.date}${item.time ? ` · ${item.time}` : ""}`));
            card.append(createTripText("h3", "", item.title));
            if (item.place) card.append(createTripText("p", "", item.place));
            if (item.notes) card.append(createTripText("p", "itinerary-item-notes", item.notes));

            const actions = document.createElement("div");
            actions.className = "itinerary-item-actions";
            const edit = createTripText("button", "checklist-remove", "Edit");
            edit.type = "button";
            edit.addEventListener("click", () => {
                itineraryForm.elements.id.value = item.id;
                itineraryForm.elements.title.value = item.title;
                itineraryForm.elements.date.value = item.date;
                itineraryForm.elements.time.value = item.time || "";
                itineraryForm.elements.place.value = item.place || "";
                itineraryForm.elements.notes.value = item.notes || "";
                if (itinerarySubmit) {
                    itinerarySubmit.textContent = "Save changes";
                }
                if (itineraryCancel) {
                    itineraryCancel.hidden = false;
                }
                itineraryForm.elements.title.focus();
            });
            const remove = createTripText("button", "checklist-remove", "Delete");
            remove.type = "button";
            remove.addEventListener("click", () => {
                const previousItems = itineraryItems;
                itineraryItems = itineraryItems.filter((current) => current.id !== item.id);
                if (!saveItinerary()) {
                    itineraryItems = previousItems;
                    return;
                }
                renderItinerary();
                itineraryStatus.textContent = "Plan item deleted.";
            });
            actions.append(edit, remove);
            card.append(actions);
            itineraryList.append(card);
        });
    }

    function resetItineraryForm() {
        itineraryForm.reset();
        itineraryForm.elements.id.value = "";
        if (itinerarySubmit) {
            itinerarySubmit.textContent = "Add to itinerary";
        }
        if (itineraryCancel) {
            itineraryCancel.hidden = true;
        }
    }

    if (itineraryForm && itineraryList && itineraryStatus) {
        try {
            const saved = JSON.parse(localStorage.getItem(itineraryStorageKey) || "[]");
            if (Array.isArray(saved) && saved.every((item) => (
                item && typeof item.id === "string" && typeof item.title === "string" && typeof item.date === "string"
            ))) {
                itineraryItems = saved;
            }
        } catch (error) {
            itineraryStatus.textContent = "Your browser could not restore the itinerary.";
        }
        renderItinerary();
        itineraryForm.addEventListener("submit", (event) => {
            event.preventDefault();
            const data = new FormData(itineraryForm);
            const id = String(data.get("id") || "");
            const item = {
                id: id || (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function"
                    ? crypto.randomUUID()
                    : `${Date.now()}-${Math.random().toString(36).slice(2)}`),
                title: String(data.get("title") || "").trim(),
                date: String(data.get("date") || ""),
                time: String(data.get("time") || ""),
                place: String(data.get("place") || "").trim(),
                notes: String(data.get("notes") || "").trim(),
            };
            if (!item.title || !item.date) {
                itineraryStatus.textContent = "Add a plan name and date.";
                return;
            }

            const previousItems = itineraryItems;
            itineraryItems = id
                ? itineraryItems.map((current) => current.id === id ? item : current)
                : [...itineraryItems, item];
            if (!saveItinerary()) {
                itineraryItems = previousItems;
                return;
            }
            renderItinerary();
            resetItineraryForm();
            itineraryStatus.textContent = id ? "Itinerary item updated." : "Itinerary item added.";
        });
        itineraryCancel?.addEventListener("click", resetItineraryForm);
    }

    const today = new Date().toISOString().slice(0, 10);
    [
        "#flight-departure",
        "#hotel-check-in",
        "#hotel-check-out",
        "#itinerary-date",
    ].forEach((selector) => {
        const input = document.querySelector(selector);
        if (input) {
            input.min = today;
        }
    });
    const flightDeparture = document.querySelector("#flight-departure");
    const flightReturn = document.querySelector("#flight-return");
    const hotelCheckIn = document.querySelector("#hotel-check-in");
    const hotelCheckOut = document.querySelector("#hotel-check-out");
    flightDeparture?.addEventListener("change", () => {
        flightReturn.min = flightDeparture.value || today;
        if (flightReturn.value && flightReturn.value <= flightDeparture.value) {
            flightReturn.value = "";
        }
    });
    hotelCheckIn?.addEventListener("change", () => {
        hotelCheckOut.min = hotelCheckIn.value || today;
        if (hotelCheckOut.value && hotelCheckOut.value <= hotelCheckIn.value) {
            hotelCheckOut.value = "";
        }
    });

    const imageInput = document.querySelector("#image");
    const imagePreview = document.querySelector("#image-preview");
    const imageError = document.querySelector("#image-error");
    let previewUrl = null;

    if (imageInput && imagePreview) {
        imageInput.addEventListener("change", () => {
            const file = imageInput.files && imageInput.files[0];
            if (!file) {
                return;
            }

            const allowedTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
            if (!allowedTypes.includes(file.type) || file.size > 5 * 1024 * 1024) {
                if (imageError) {
                    imageError.textContent = file.size > 5 * 1024 * 1024
                        ? "Choose an image smaller than 5 MB."
                        : "Choose a JPG, PNG, GIF, or WebP image.";
                    imageError.hidden = false;
                }
                imageInput.value = "";
                return;
            }

            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
            previewUrl = URL.createObjectURL(file);
            imagePreview.src = previewUrl;
            imagePreview.hidden = false;
            if (imageError) {
                imageError.textContent = "";
                imageError.hidden = true;
            }
        });
    }

    document.querySelectorAll("form[data-confirm]").forEach((form) => {
        form.addEventListener("submit", (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });
});
