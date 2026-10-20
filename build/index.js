(function(_wordpress_plugins, _wordpress_element, _wordpress_components, _wordpress_core_data, _wordpress_data, _wordpress_editor, _wordpress_i18n, react, react_jsx_runtime, _wordpress_api_fetch, _wordpress_url) {
	//#region \0rolldown/runtime.js
	var __create = Object.create;
	var __defProp = Object.defineProperty;
	var __getOwnPropDesc = Object.getOwnPropertyDescriptor;
	var __getOwnPropNames = Object.getOwnPropertyNames;
	var __getProtoOf = Object.getPrototypeOf;
	var __hasOwnProp = Object.prototype.hasOwnProperty;
	var __copyProps = (to, from, except, desc) => {
		if (from && typeof from === "object" || typeof from === "function") for (var keys = __getOwnPropNames(from), i = 0, n = keys.length, key; i < n; i++) {
			key = keys[i];
			if (!__hasOwnProp.call(to, key) && key !== except) __defProp(to, key, {
				get: ((k) => from[k]).bind(null, key),
				enumerable: !(desc = __getOwnPropDesc(from, key)) || desc.enumerable
			});
		}
		return to;
	};
	var __toESM = (mod, isNodeMode, target) => (target = mod != null ? __create(__getProtoOf(mod)) : {}, __copyProps(isNodeMode || !mod || !mod.__esModule || !__hasOwnProp.call(mod, "default") ? __defProp(target, "default", {
		value: mod,
		enumerable: true
	}) : target, mod));
	//#endregion
	_wordpress_api_fetch = __toESM(_wordpress_api_fetch, 1);
	//#region src/rich-results-test.ts
	var RICH_RESULTS_TEST = "https://search.google.com/test/rich-results";
	/** Link to Google's Rich Results Test for a public URL. */
	function richResultsTestUrl(pageUrl) {
		return `${RICH_RESULTS_TEST}?${new URLSearchParams({ url: pageUrl }).toString()}`;
	}
	/**
	* Google can only test a page it can load, so the link is offered for
	* published posts with an http(s) URL. Values come from the editor store and
	* are checked before use.
	*/
	function testablePostUrl(status, link) {
		if (status !== "publish" || typeof link !== "string") return null;
		return /^https?:\/\//.test(link) ? link : null;
	}
	//#endregion
	//#region src/post-details.ts
	/** Per-post details saved in post meta for the Event, Recipe and FAQ page types. */
	var ATTENDANCE_MODES = [
		"offline",
		"online",
		"mixed"
	];
	var EVENT_STATUSES = [
		"scheduled",
		"cancelled",
		"postponed",
		"rescheduled",
		"moved_online"
	];
	var EVENT_META_KEY = "_better_schema_event";
	var RECIPE_META_KEY = "_better_schema_recipe";
	var FAQ_META_KEY = "_better_schema_faq";
	var CUSTOM_JSONLD_META_KEY = "_better_schema_custom_jsonld";
	var EMPTY_EVENT = {
		start: "",
		end: "",
		attendance: "offline",
		status: "scheduled",
		location_name: "",
		location_address: "",
		offers_url: ""
	};
	var EMPTY_RECIPE = {
		ingredients: [],
		instructions: [],
		prep_minutes: 0,
		cook_minutes: 0,
		yield: "",
		calories: 0
	};
	function isRecord$2(value) {
		return typeof value === "object" && value !== null && !Array.isArray(value);
	}
	function text$1(value) {
		return typeof value === "string" ? value : "";
	}
	function whole(value) {
		return typeof value === "number" && Number.isSafeInteger(value) && value > 0 ? value : 0;
	}
	function textList(value) {
		return Array.isArray(value) ? value.filter((item) => typeof item === "string") : [];
	}
	function oneOf(options, value, fallback) {
		return options.find((option) => option === value) ?? fallback;
	}
	function metaValue(meta, key) {
		return isRecord$2(meta) ? meta[key] : void 0;
	}
	/** Post meta comes from the REST API, so every field is checked and falls back to empty. */
	function readEventDetails(meta) {
		const value = metaValue(meta, EVENT_META_KEY);
		if (!isRecord$2(value)) return EMPTY_EVENT;
		return {
			start: text$1(value.start),
			end: text$1(value.end),
			attendance: oneOf(ATTENDANCE_MODES, value.attendance, EMPTY_EVENT.attendance),
			status: oneOf(EVENT_STATUSES, value.status, EMPTY_EVENT.status),
			location_name: text$1(value.location_name),
			location_address: text$1(value.location_address),
			offers_url: text$1(value.offers_url)
		};
	}
	function readRecipeDetails(meta) {
		const value = metaValue(meta, RECIPE_META_KEY);
		if (!isRecord$2(value)) return EMPTY_RECIPE;
		return {
			ingredients: textList(value.ingredients),
			instructions: textList(value.instructions),
			prep_minutes: whole(value.prep_minutes),
			cook_minutes: whole(value.cook_minutes),
			yield: text$1(value.yield),
			calories: whole(value.calories)
		};
	}
	function readFaqItems(meta) {
		const value = metaValue(meta, FAQ_META_KEY);
		if (!Array.isArray(value)) return [];
		return value.flatMap((item) => isRecord$2(item) ? [{
			question: text$1(item.question),
			answer: text$1(item.answer)
		}] : []);
	}
	function readCustomJsonLd(meta) {
		return text$1(metaValue(meta, CUSTOM_JSONLD_META_KEY));
	}
	/**
	* Checks custom JSON-LD the way the server will: a JSON object or list whose
	* entities each have an `@type`, or a document with an `@graph` of such entities.
	* The server is the source of truth and drops anything it does not accept.
	*/
	function checkCustomJsonLd(value) {
		if (value.trim() === "") return { status: "empty" };
		let data;
		try {
			data = JSON.parse(value);
		} catch {
			return {
				status: "invalid",
				reason: "syntax"
			};
		}
		let entities = data;
		if (isRecord$2(data)) entities = Array.isArray(data["@graph"]) ? data["@graph"] : "@type" in data ? [data] : [];
		return Array.isArray(entities) && entities.some((entity) => isRecord$2(entity) && "@type" in entity) ? { status: "valid" } : {
			status: "invalid",
			reason: "shape"
		};
	}
	function isAttendanceMode(value) {
		return ATTENDANCE_MODES.some((mode) => mode === value);
	}
	function isEventStatus(value) {
		return EVENT_STATUSES.some((status) => status === value);
	}
	/** Splits text into lines, dropping blank ones. */
	function parseLines(value) {
		return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
	}
	//#endregion
	//#region src/custom-jsonld-field.tsx
	/**
	* Edits custom JSON-LD for the post. Only valid JSON, or an empty field, is
	* passed on to be saved, so the editor never stores a broken value.
	*/
	function CustomJsonLdField({ value, onChange }) {
		const [draft, setDraft] = (0, react.useState)(value);
		const check = checkCustomJsonLd(draft);
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextareaControl, {
			label: (0, _wordpress_i18n.__)("Custom JSON-LD", "better-schema-markup"),
			help: (0, _wordpress_i18n.__)("For advanced use. Paste one schema.org object, a list of objects, or a document with @graph. It is added to this page’s schema.", "better-schema-markup"),
			rows: 8,
			value: draft,
			onChange: (next) => {
				setDraft(next);
				if (checkCustomJsonLd(next).status !== "invalid") onChange(next);
			},
			__nextHasNoMarginBottom: true
		}), check.status === "invalid" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
			status: "error",
			isDismissible: false,
			children: check.reason === "syntax" ? (0, _wordpress_i18n.__)("This is not valid JSON, so it will not be saved.", "better-schema-markup") : (0, _wordpress_i18n.__)("Each object needs an @type, so this will not be saved.", "better-schema-markup")
		}) : null] });
	}
	//#endregion
	//#region src/event-fields.tsx
	function EventFields({ value, onChange }) {
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "datetime-local",
				label: (0, _wordpress_i18n.__)("Starts", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("Google requires a start date for events.", "better-schema-markup"),
				value: value.start,
				onChange: (start) => onChange({
					...value,
					start
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "datetime-local",
				label: (0, _wordpress_i18n.__)("Ends", "better-schema-markup"),
				value: value.end,
				onChange: (end) => onChange({
					...value,
					end
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
				label: (0, _wordpress_i18n.__)("Where does it happen?", "better-schema-markup"),
				value: value.attendance,
				options: [
					{
						label: (0, _wordpress_i18n.__)("In person", "better-schema-markup"),
						value: "offline"
					},
					{
						label: (0, _wordpress_i18n.__)("Online", "better-schema-markup"),
						value: "online"
					},
					{
						label: (0, _wordpress_i18n.__)("Both", "better-schema-markup"),
						value: "mixed"
					}
				],
				onChange: (attendance) => {
					if (isAttendanceMode(attendance)) onChange({
						...value,
						attendance
					});
				},
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			value.attendance !== "online" ? /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				label: (0, _wordpress_i18n.__)("Venue name", "better-schema-markup"),
				value: value.location_name,
				onChange: (location_name) => onChange({
					...value,
					location_name
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}), /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				label: (0, _wordpress_i18n.__)("Venue address", "better-schema-markup"),
				value: value.location_address,
				onChange: (location_address) => onChange({
					...value,
					location_address
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			})] }) : null,
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
				label: (0, _wordpress_i18n.__)("Status", "better-schema-markup"),
				value: value.status,
				options: [
					{
						label: (0, _wordpress_i18n.__)("Scheduled", "better-schema-markup"),
						value: "scheduled"
					},
					{
						label: (0, _wordpress_i18n.__)("Cancelled", "better-schema-markup"),
						value: "cancelled"
					},
					{
						label: (0, _wordpress_i18n.__)("Postponed", "better-schema-markup"),
						value: "postponed"
					},
					{
						label: (0, _wordpress_i18n.__)("Rescheduled", "better-schema-markup"),
						value: "rescheduled"
					},
					{
						label: (0, _wordpress_i18n.__)("Moved online", "better-schema-markup"),
						value: "moved_online"
					}
				],
				onChange: (status) => {
					if (isEventStatus(status)) onChange({
						...value,
						status
					});
				},
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "url",
				label: (0, _wordpress_i18n.__)("Ticket link", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("Optional. A full http or https URL.", "better-schema-markup"),
				value: value.offers_url,
				onChange: (offers_url) => onChange({
					...value,
					offers_url
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			})
		] });
	}
	//#endregion
	//#region src/faq-fields.tsx
	function FaqFields({ value, onChange }) {
		function update(index, item) {
			onChange(value.map((existing, position) => position === index ? item : existing));
		}
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			value.map((item, index) => /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("div", {
				style: { marginBottom: "16px" },
				children: [
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Question", "better-schema-markup"),
						value: item.question,
						onChange: (question) => update(index, {
							...item,
							question
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextareaControl, {
						label: (0, _wordpress_i18n.__)("Answer", "better-schema-markup"),
						value: item.answer,
						onChange: (answer) => update(index, {
							...item,
							answer
						}),
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
						variant: "link",
						isDestructive: true,
						onClick: () => onChange(value.filter((_, position) => position !== index)),
						children: (0, _wordpress_i18n.__)("Remove question", "better-schema-markup")
					})
				]
			}, index)),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
				variant: "secondary",
				onClick: () => onChange([...value, {
					question: "",
					answer: ""
				}]),
				children: (0, _wordpress_i18n.__)("Add question", "better-schema-markup")
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", {
				className: "description",
				children: (0, _wordpress_i18n.__)("Questions without an answer are not saved.", "better-schema-markup")
			})
		] });
	}
	//#endregion
	//#region src/recipe-fields.tsx
	function LineListField({ label, help, value, onChange }) {
		const [draft, setDraft] = (0, react.useState)(value.join("\n"));
		return /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextareaControl, {
			label,
			help,
			rows: 5,
			value: draft,
			onChange: (next) => {
				setDraft(next);
				onChange(parseLines(next));
			},
			__nextHasNoMarginBottom: true
		});
	}
	function minutes(value) {
		const parsed = Number.parseInt(value, 10);
		return Number.isSafeInteger(parsed) && parsed > 0 ? parsed : 0;
	}
	function RecipeFields({ value, onChange }) {
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(LineListField, {
				label: (0, _wordpress_i18n.__)("Ingredients", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("One ingredient per line.", "better-schema-markup"),
				value: value.ingredients,
				onChange: (ingredients) => onChange({
					...value,
					ingredients
				})
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(LineListField, {
				label: (0, _wordpress_i18n.__)("Steps", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("One step per line.", "better-schema-markup"),
				value: value.instructions,
				onChange: (instructions) => onChange({
					...value,
					instructions
				})
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "number",
				min: 0,
				label: (0, _wordpress_i18n.__)("Preparation time (minutes)", "better-schema-markup"),
				value: value.prep_minutes ? String(value.prep_minutes) : "",
				onChange: (next) => onChange({
					...value,
					prep_minutes: minutes(next)
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "number",
				min: 0,
				label: (0, _wordpress_i18n.__)("Cooking time (minutes)", "better-schema-markup"),
				value: value.cook_minutes ? String(value.cook_minutes) : "",
				onChange: (next) => onChange({
					...value,
					cook_minutes: minutes(next)
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				label: (0, _wordpress_i18n.__)("Servings", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("For example “4 servings”.", "better-schema-markup"),
				value: value.yield,
				onChange: (next) => onChange({
					...value,
					yield: next
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
				type: "number",
				min: 0,
				label: (0, _wordpress_i18n.__)("Calories per serving", "better-schema-markup"),
				value: value.calories ? String(value.calories) : "",
				onChange: (next) => onChange({
					...value,
					calories: minutes(next)
				}),
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			})
		] });
	}
	//#endregion
	//#region src/schema-details.tsx
	/** The fields for the chosen schema type, and the advanced custom JSON-LD field. */
	function SchemaDetails({ meta, schemaType, onChange }) {
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			schemaType === "Event" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(EventFields, {
				value: readEventDetails(meta),
				onChange: (value) => onChange(EVENT_META_KEY, value)
			}) : null,
			schemaType === "Recipe" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(RecipeFields, {
				value: readRecipeDetails(meta),
				onChange: (value) => onChange(RECIPE_META_KEY, value)
			}) : null,
			schemaType === "FAQPage" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(FaqFields, {
				value: readFaqItems(meta),
				onChange: (value) => onChange(FAQ_META_KEY, value)
			}) : null,
			schemaType !== "none" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.PanelBody, {
				title: (0, _wordpress_i18n.__)("Advanced", "better-schema-markup"),
				initialOpen: readCustomJsonLd(meta) !== "",
				children: /* @__PURE__ */ (0, react_jsx_runtime.jsx)(CustomJsonLdField, {
					value: readCustomJsonLd(meta),
					onChange: (value) => onChange(CUSTOM_JSONLD_META_KEY, value)
				})
			}) : null
		] });
	}
	//#endregion
	//#region src/schema-preview-section.tsx
	/** Validation hints and the JSON-LD the frontend would output. */
	function SchemaPreviewSection({ state }) {
		const { preview, isLoading, hasError } = state;
		if (hasError) return /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
			status: "error",
			isDismissible: false,
			children: (0, _wordpress_i18n.__)("The schema preview could not be loaded.", "better-schema-markup")
		});
		if (!preview) return /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Spinner, {});
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("div", {
			"aria-busy": isLoading,
			style: {
				display: "grid",
				gridTemplateColumns: "minmax(0, 1fr)",
				gap: "8px",
				marginTop: "8px"
			},
			children: [preview.hints.map((hint) => /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
				status: hint.level === "required" ? "error" : "warning",
				isDismissible: false,
				children: hint.message
			}, `${hint.type}-${hint.property}`)), preview.document ? /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("details", { children: [
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("summary", { children: (0, _wordpress_i18n.__)("Show JSON-LD", "better-schema-markup") }),
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("What this page outputs, using the type above. Save to include your other changes.", "better-schema-markup") }),
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("pre", {
					style: {
						overflowX: "auto",
						fontSize: "11px"
					},
					children: JSON.stringify(preview.document, null, 2)
				})
			] }) : /* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("This page outputs no schema.", "better-schema-markup") })]
		});
	}
	//#endregion
	//#region src/schema-types.ts
	var SCHEMA_TYPES = [
		{
			label: (0, _wordpress_i18n.__)("Article", "better-schema-markup"),
			value: "Article",
			description: (0, _wordpress_i18n.__)("News, guides and other editorial content.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("Blog post", "better-schema-markup"),
			value: "BlogPosting",
			description: (0, _wordpress_i18n.__)("A post on a blog, often more personal or informal.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("Regular page", "better-schema-markup"),
			value: "WebPage",
			description: (0, _wordpress_i18n.__)("A page that is not an article, such as About or Contact.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("Event", "better-schema-markup"),
			value: "Event",
			description: (0, _wordpress_i18n.__)("A concert, class, meetup or other event with a date and place.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("Recipe", "better-schema-markup"),
			value: "Recipe",
			description: (0, _wordpress_i18n.__)("A cooking recipe with ingredients and steps.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("FAQ page", "better-schema-markup"),
			value: "FAQPage",
			description: (0, _wordpress_i18n.__)("A page of questions and answers. Google only shows FAQ results for well-known government and health sites.", "better-schema-markup")
		},
		{
			label: (0, _wordpress_i18n.__)("No schema", "better-schema-markup"),
			value: "none",
			description: (0, _wordpress_i18n.__)("Turns off all schema on this page, including the site-wide information.", "better-schema-markup")
		}
	];
	function isSchemaType(value) {
		return SCHEMA_TYPES.some((schemaType) => schemaType.value === value);
	}
	function schemaTypeDescription(value) {
		return SCHEMA_TYPES.find((schemaType) => schemaType.value === value)?.description ?? "";
	}
	//#endregion
	//#region src/schema-meta.ts
	/**
	* Post meta comes from the REST API, so it is only trusted once the schema
	* type is known to be valid. Post types without the meta fail this check.
	*/
	function isSchemaMeta(value) {
		return typeof value === "object" && value !== null && "_better_schema_type" in value && isSchemaType(value._better_schema_type);
	}
	//#endregion
	//#region src/schema-type-picker.tsx
	function SchemaTypePicker({ value, onChange }) {
		return /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
			label: (0, _wordpress_i18n.__)("What is this page?", "better-schema-markup"),
			help: schemaTypeDescription(value),
			value,
			options: SCHEMA_TYPES.map(({ label, value: optionValue }) => ({
				label,
				value: optionValue
			})),
			onChange: (selected) => {
				if (isSchemaType(selected)) onChange(selected);
			},
			__next40pxDefaultSize: true,
			__nextHasNoMarginBottom: true
		});
	}
	//#endregion
	//#region src/admin-api.ts
	/** Use explicit backend URLs without changing other plugins' apiFetch routing. */
	async function schemaApiFetch({ path, ...options }) {
		const config = window.betterSchemaApi;
		if (typeof config !== "object" || config === null || !("rootUrl" in config) || typeof config.rootUrl !== "string") throw new Error((0, _wordpress_i18n.__)("Could not configure the WordPress backend request. Reload this page and try again.", "better-schema-markup"));
		const url = new URL(config.rootUrl);
		const queryStart = path.indexOf("?");
		const route = queryStart < 0 ? path : path.slice(0, queryStart);
		new URLSearchParams(queryStart < 0 ? "" : path.slice(queryStart + 1)).forEach((value, key) => url.searchParams.append(key, value));
		url.searchParams.set("rest_route", route);
		return (0, _wordpress_api_fetch.default)({
			...options,
			url: url.toString()
		});
	}
	//#endregion
	//#region src/schema-preview.ts
	var HINT_LEVELS = ["required", "recommended"];
	function isRecord$1(value) {
		return typeof value === "object" && value !== null && !Array.isArray(value);
	}
	function isHintLevel(value) {
		return HINT_LEVELS.some((level) => level === value);
	}
	function isValidationHint(value) {
		return isRecord$1(value) && typeof value.type === "string" && typeof value.property === "string" && isHintLevel(value.level) && typeof value.message === "string";
	}
	function isJsonLdDocument(value) {
		return isRecord$1(value) && typeof value["@context"] === "string" && Array.isArray(value["@graph"]);
	}
	function isSchemaPreview(value) {
		return isRecord$1(value) && (value.document === null || isJsonLdDocument(value.document)) && Array.isArray(value.hints) && value.hints.every(isValidationHint);
	}
	//#endregion
	//#region src/use-schema-preview.ts
	/**
	* Fetches the JSON-LD the frontend would output for a post, using the schema
	* type picked in the editor. Refetches when the type changes or the post is saved.
	*
	* @param postId     Post being edited.
	* @param schemaType Type picked in the editor, which may not be saved yet.
	* @param savedAt    Changes on every save, for example the post's `modified` date.
	*/
	function useSchemaPreview(postId, schemaType, savedAt) {
		const [state, setState] = (0, react.useState)({
			preview: null,
			isLoading: true,
			hasError: false
		});
		(0, react.useEffect)(() => {
			const controller = new AbortController();
			setState((previous) => ({
				...previous,
				isLoading: true
			}));
			schemaApiFetch({
				path: (0, _wordpress_url.addQueryArgs)(`/better-schema/v1/preview/${postId}`, { type: schemaType }),
				signal: controller.signal
			}).then((response) => {
				setState(isSchemaPreview(response) ? {
					preview: response,
					isLoading: false,
					hasError: false
				} : {
					preview: null,
					isLoading: false,
					hasError: true
				});
			}).catch(() => {
				if (!controller.signal.aborted) setState({
					preview: null,
					isLoading: false,
					hasError: true
				});
			});
			return () => controller.abort();
		}, [
			postId,
			schemaType,
			savedAt
		]);
		return state;
	}
	//#endregion
	//#region src/schema-panel.tsx
	function SchemaPanel() {
		const { postType, postId, savedAt, testableUrl } = (0, _wordpress_data.useSelect)((select) => {
			const editor = select(_wordpress_editor.store);
			const modified = editor.getCurrentPostAttribute("modified");
			return {
				postType: editor.getCurrentPostType(),
				postId: editor.getCurrentPostId(),
				savedAt: typeof modified === "string" ? modified : "",
				testableUrl: testablePostUrl(editor.getCurrentPostAttribute("status"), editor.getCurrentPostAttribute("link"))
			};
		}, []);
		const [meta, setMeta] = (0, _wordpress_core_data.useEntityProp)("postType", postType, "meta");
		if (!isSchemaMeta(meta) || typeof postId !== "number") return null;
		return /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_editor.PluginDocumentSettingPanel, {
			name: "better-schema",
			title: (0, _wordpress_i18n.__)("Schema", "better-schema-markup"),
			children: /* @__PURE__ */ (0, react_jsx_runtime.jsx)(SchemaPanelContent, {
				postId,
				meta,
				savedAt,
				testableUrl,
				onChangeType: (type) => setMeta({
					...meta,
					_better_schema_type: type
				}),
				onChangeDetail: (key, value) => setMeta({
					...meta,
					[key]: value
				})
			})
		});
	}
	function SchemaPanelContent({ postId, meta, savedAt, testableUrl, onChangeType, onChangeDetail }) {
		const schemaType = meta._better_schema_type;
		const preview = useSchemaPreview(postId, schemaType, savedAt);
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(SchemaTypePicker, {
				value: schemaType,
				onChange: onChangeType
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(SchemaDetails, {
				meta,
				schemaType,
				onChange: onChangeDetail
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(SchemaPreviewSection, { state: preview }),
			testableUrl && schemaType !== "none" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.ExternalLink, {
				href: richResultsTestUrl(testableUrl),
				children: (0, _wordpress_i18n.__)("Test with Google", "better-schema-markup")
			}) }) : null
		] });
	}
	//#endregion
	//#region src/settings-data.ts
	function isRecord(value) {
		return typeof value === "object" && value !== null && !Array.isArray(value);
	}
	function readSettingsConfig(value) {
		if (!isRecord(value) || !Array.isArray(value.postTypes)) return null;
		if (typeof value.showSetup !== "boolean" || typeof value.logoUrl !== "string" || typeof value.siteName !== "string") return null;
		const postTypes = [];
		for (const item of value.postTypes) {
			if (!isRecord(item) || typeof item.name !== "string" || typeof item.label !== "string") return null;
			postTypes.push({
				name: item.name,
				label: item.label
			});
		}
		return {
			postTypes,
			showSetup: value.showSetup,
			logoUrl: value.logoUrl,
			siteName: value.siteName
		};
	}
	function readPluginSettings(value, config) {
		if (!isRecord(value) || !isRecord(value.better_schema_identity)) return null;
		if (typeof value.better_schema_frontend_url !== "string") return null;
		const identity = value.better_schema_identity;
		if (identity.type !== "Organization" && identity.type !== "Person" || typeof identity.name !== "string" || !Number.isSafeInteger(identity.logo) || identity.logo < 0 || !Array.isArray(identity.same_as) || !identity.same_as.every((url) => typeof url === "string")) return null;
		const rawDefaults = value.better_schema_type_defaults;
		if (!isRecord(rawDefaults)) return null;
		const defaults = {};
		for (const postType of config.postTypes) {
			const schemaType = rawDefaults[postType.name];
			defaults[postType.name] = isSchemaType(schemaType) ? schemaType : postType.name === "post" ? "Article" : "WebPage";
		}
		return {
			frontendUrl: value.better_schema_frontend_url,
			identity: {
				type: identity.type,
				name: identity.name,
				logo: identity.logo,
				same_as: identity.same_as
			},
			defaults
		};
	}
	function parseSocialProfiles(value) {
		return value.split(/\r?\n/).map((url) => url.trim()).filter(Boolean);
	}
	function invalidSocialProfiles(urls) {
		return urls.filter((url) => {
			try {
				const parsed = new URL(url);
				return !["http:", "https:"].includes(parsed.protocol) || !parsed.hostname;
			} catch {
				return true;
			}
		});
	}
	function isFrontendUrl(value) {
		if (!value.trim()) return true;
		try {
			const url = new URL(value);
			return ["http:", "https:"].includes(url.protocol) && !!url.hostname && !url.username && !url.password && !value.includes("?") && !value.includes("#");
		} catch {
			return false;
		}
	}
	var WEEKDAYS = [
		"Monday",
		"Tuesday",
		"Wednesday",
		"Thursday",
		"Friday",
		"Saturday",
		"Sunday"
	];
	var EMPTY_BUSINESS = {
		enabled: false,
		telephone: "",
		price_range: "",
		address: {
			street: "",
			locality: "",
			region: "",
			postal_code: "",
			country: ""
		},
		geo: {
			lat: "",
			lng: ""
		},
		hours: []
	};
	function text(value) {
		return typeof value === "string" ? value : "";
	}
	function isWeekday(value) {
		return WEEKDAYS.some((day) => day === value);
	}
	/** Reads the local business setting from the REST settings response, falling back to empty. */
	function readBusinessSettings(value) {
		if (!isRecord(value) || !isRecord(value.better_schema_business)) return EMPTY_BUSINESS;
		const business = value.better_schema_business;
		const address = isRecord(business.address) ? business.address : {};
		const geo = isRecord(business.geo) ? business.geo : {};
		const hours = Array.isArray(business.hours) ? business.hours : [];
		return {
			enabled: business.enabled === true,
			telephone: text(business.telephone),
			price_range: text(business.price_range),
			address: {
				street: text(address.street),
				locality: text(address.locality),
				region: text(address.region),
				postal_code: text(address.postal_code),
				country: text(address.country)
			},
			geo: {
				lat: text(geo.lat),
				lng: text(geo.lng)
			},
			hours: hours.flatMap((row) => isRecord(row) ? [{
				days: Array.isArray(row.days) ? row.days.filter(isWeekday) : [],
				opens: text(row.opens),
				closes: text(row.closes)
			}] : [])
		};
	}
	/** Matches the server: a coordinate within range, or empty. */
	function isCoordinate(value, limit) {
		if (value.trim() === "") return true;
		const parsed = Number(value);
		return Number.isFinite(parsed) && Math.abs(parsed) <= limit;
	}
	//#endregion
	//#region src/business-fields.tsx
	var WEEKDAY_LABELS = {
		Monday: (0, _wordpress_i18n.__)("Monday", "better-schema-markup"),
		Tuesday: (0, _wordpress_i18n.__)("Tuesday", "better-schema-markup"),
		Wednesday: (0, _wordpress_i18n.__)("Wednesday", "better-schema-markup"),
		Thursday: (0, _wordpress_i18n.__)("Thursday", "better-schema-markup"),
		Friday: (0, _wordpress_i18n.__)("Friday", "better-schema-markup"),
		Saturday: (0, _wordpress_i18n.__)("Saturday", "better-schema-markup"),
		Sunday: (0, _wordpress_i18n.__)("Sunday", "better-schema-markup")
	};
	var NEW_HOURS = {
		days: [
			"Monday",
			"Tuesday",
			"Wednesday",
			"Thursday",
			"Friday"
		],
		opens: "09:00",
		closes: "17:00"
	};
	function BusinessFields({ value, onChange }) {
		function updateHours(index, row) {
			onChange({
				...value,
				hours: value.hours.map((existing, position) => position === index ? row : existing)
			});
		}
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h2", { children: (0, _wordpress_i18n.__)("Local business", "better-schema-markup") }),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.ToggleControl, {
				label: (0, _wordpress_i18n.__)("This is a local business with a physical location", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("Adds an address, opening hours and map location to your organization. It does not apply to a person.", "better-schema-markup"),
				checked: value.enabled,
				onChange: (enabled) => onChange({
					...value,
					enabled
				}),
				__nextHasNoMarginBottom: true
			}),
			value.enabled ? /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("div", {
				style: { marginTop: "16px" },
				children: [
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Phone number", "better-schema-markup"),
						value: value.telephone,
						onChange: (telephone) => onChange({
							...value,
							telephone
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Price range", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("For example $$ or “100–300 SEK”.", "better-schema-markup"),
						value: value.price_range,
						onChange: (price_range) => onChange({
							...value,
							price_range
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Street address", "better-schema-markup"),
						value: value.address.street,
						onChange: (street) => onChange({
							...value,
							address: {
								...value.address,
								street
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("City", "better-schema-markup"),
						value: value.address.locality,
						onChange: (locality) => onChange({
							...value,
							address: {
								...value.address,
								locality
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Region or state", "better-schema-markup"),
						value: value.address.region,
						onChange: (region) => onChange({
							...value,
							address: {
								...value.address,
								region
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Postal code", "better-schema-markup"),
						value: value.address.postal_code,
						onChange: (postal_code) => onChange({
							...value,
							address: {
								...value.address,
								postal_code
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Country", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("A two-letter country code, for example SE.", "better-schema-markup"),
						value: value.address.country,
						onChange: (country) => onChange({
							...value,
							address: {
								...value.address,
								country
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Latitude", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("Between -90 and 90. Set both latitude and longitude, or neither.", "better-schema-markup"),
						value: value.geo.lat,
						onChange: (lat) => onChange({
							...value,
							geo: {
								...value.geo,
								lat
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Longitude", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("Between -180 and 180.", "better-schema-markup"),
						value: value.geo.lng,
						onChange: (lng) => onChange({
							...value,
							geo: {
								...value.geo,
								lng
							}
						}),
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h3", { children: (0, _wordpress_i18n.__)("Opening hours", "better-schema-markup") }),
					value.hours.map((row, index) => /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("fieldset", {
						style: { marginBottom: "16px" },
						children: [
							WEEKDAYS.map((day) => /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.CheckboxControl, {
								label: WEEKDAY_LABELS[day],
								checked: row.days.includes(day),
								onChange: (checked) => updateHours(index, {
									...row,
									days: WEEKDAYS.filter((weekday) => weekday === day ? checked : row.days.includes(weekday))
								}),
								__nextHasNoMarginBottom: true
							}, day)),
							/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
								type: "time",
								label: (0, _wordpress_i18n.__)("Opens", "better-schema-markup"),
								value: row.opens,
								onChange: (opens) => updateHours(index, {
									...row,
									opens
								}),
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true
							}),
							/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
								type: "time",
								label: (0, _wordpress_i18n.__)("Closes", "better-schema-markup"),
								value: row.closes,
								onChange: (closes) => updateHours(index, {
									...row,
									closes
								}),
								__next40pxDefaultSize: true,
								__nextHasNoMarginBottom: true
							}),
							/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
								variant: "link",
								isDestructive: true,
								onClick: () => onChange({
									...value,
									hours: value.hours.filter((_, position) => position !== index)
								}),
								children: (0, _wordpress_i18n.__)("Remove these hours", "better-schema-markup")
							})
						]
					}, index)),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
						variant: "secondary",
						onClick: () => onChange({
							...value,
							hours: [...value.hours, NEW_HOURS]
						}),
						children: (0, _wordpress_i18n.__)("Add opening hours", "better-schema-markup")
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", {
						className: "description",
						children: (0, _wordpress_i18n.__)("Hours without a day, or without both times, are not saved.", "better-schema-markup")
					})
				]
			}) : null
		] });
	}
	//#endregion
	//#region src/import-report.ts
	function isImportReport(value) {
		if (typeof value !== "object" || value === null) return false;
		const data = value;
		return [
			"imported",
			"skipped",
			"unsupported",
			"failed",
			"cursor"
		].every((key) => typeof data[key] === "number" && Number.isSafeInteger(data[key]) && data[key] >= 0) && typeof data.done === "boolean" && Array.isArray(data.details) && data.details.every((item) => typeof item === "object" && item !== null && "post" in item && typeof item.post === "number" && "message" in item && typeof item.message === "string");
	}
	//#endregion
	//#region src/seo-settings.tsx
	function SeoSettings() {
		const [takeover, setTakeover] = (0, react.useState)(null);
		const [source, setSource] = (0, react.useState)("yoast");
		const [busy, setBusy] = (0, react.useState)(false);
		const [message, setMessage] = (0, react.useState)("");
		const [error, setError] = (0, react.useState)("");
		const [details, setDetails] = (0, react.useState)([]);
		(0, react.useEffect)(() => {
			let active = true;
			schemaApiFetch({ path: "/wp/v2/settings" }).then((value) => {
				if (!active) return;
				if (typeof value !== "object" || value === null || !("better_schema_takeover" in value) || typeof value.better_schema_takeover !== "boolean") throw new Error();
				setTakeover(value.better_schema_takeover);
			}).catch(() => {
				if (active) setError((0, _wordpress_i18n.__)("Could not load schema takeover settings.", "better-schema-markup"));
			});
			return () => {
				active = false;
			};
		}, []);
		async function saveTakeover(next) {
			setBusy(true);
			setError("");
			try {
				await schemaApiFetch({
					path: "/wp/v2/settings",
					method: "POST",
					data: { better_schema_takeover: next }
				});
				setTakeover(next);
				setMessage((0, _wordpress_i18n.__)("Schema takeover setting saved.", "better-schema-markup"));
			} catch {
				setError((0, _wordpress_i18n.__)("Could not save schema takeover settings.", "better-schema-markup"));
			} finally {
				setBusy(false);
			}
		}
		async function runImport(dryRun) {
			setBusy(true);
			setMessage("");
			setError("");
			setDetails([]);
			const total = {
				imported: 0,
				skipped: 0,
				unsupported: 0,
				failed: 0
			};
			let cursor = 0;
			try {
				let done = false;
				while (!done) {
					const report = await schemaApiFetch({
						path: "/better-schema/v1/import",
						method: "POST",
						data: {
							source,
							cursor,
							dry_run: dryRun
						}
					});
					if (!isImportReport(report) || !report.done && report.cursor <= cursor) throw new Error();
					for (const key of [
						"imported",
						"skipped",
						"unsupported",
						"failed"
					]) total[key] += report[key];
					setDetails((previous) => [...previous, ...report.details]);
					cursor = report.cursor;
					done = report.done;
				}
				setMessage([
					`${(0, _wordpress_i18n.__)("Imported", "better-schema-markup")}: ${total.imported}`,
					`${(0, _wordpress_i18n.__)("Skipped", "better-schema-markup")}: ${total.skipped}`,
					`${(0, _wordpress_i18n.__)("With unsupported settings", "better-schema-markup")}: ${total.unsupported}`,
					`${(0, _wordpress_i18n.__)("Failed", "better-schema-markup")}: ${total.failed}`,
					dryRun ? (0, _wordpress_i18n.__)("Dry run only; nothing was saved.", "better-schema-markup") : (0, _wordpress_i18n.__)("Review the details below. Existing choices were preserved.", "better-schema-markup")
				].join(". "));
			} catch {
				setError((0, _wordpress_i18n.__)("The import stopped. Previously completed batches were kept. You can safely retry; existing choices will be skipped.", "better-schema-markup"));
			} finally {
				setBusy(false);
			}
		}
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("section", { children: [
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h2", { children: (0, _wordpress_i18n.__)("SEO plugin compatibility", "better-schema-markup") }),
			error ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
				status: "error",
				isDismissible: false,
				children: error
			}) : null,
			message ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
				status: "info",
				isDismissible: false,
				children: message
			}) : null,
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.ToggleControl, {
				label: (0, _wordpress_i18n.__)("Let Better Schema Markup handle schema", "better-schema-markup"),
				help: (0, _wordpress_i18n.__)("Replaces Yoast SEO and Rank Math schema only. When disabled with either plugin active, that plugin handles frontend schema. Previews and WPGraphQL remain available. This setting saves immediately.", "better-schema-markup"),
				checked: takeover === true,
				disabled: busy || takeover === null,
				onChange: (next) => {
					saveTakeover(next);
				},
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h3", { children: (0, _wordpress_i18n.__)("Import saved schema settings", "better-schema-markup") }),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("Import supported per-post types and details once. Source data and existing Better Schema choices are preserved. Unsupported fields and templates are reported for manual review. Run a preview first.", "better-schema-markup") }),
			/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
				label: (0, _wordpress_i18n.__)("Import from", "better-schema-markup"),
				value: source,
				disabled: busy,
				options: [{
					label: (0, _wordpress_i18n.__)("Yoast SEO", "better-schema-markup"),
					value: "yoast"
				}, {
					label: (0, _wordpress_i18n.__)("Rank Math", "better-schema-markup"),
					value: "rank-math"
				}],
				onChange: (value) => {
					if (value === "yoast" || value === "rank-math") setSource(value);
				},
				__next40pxDefaultSize: true,
				__nextHasNoMarginBottom: true
			}),
			/* @__PURE__ */ (0, react_jsx_runtime.jsxs)("p", { children: [
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
					variant: "secondary",
					disabled: busy,
					onClick: () => {
						runImport(true);
					},
					children: (0, _wordpress_i18n.__)("Preview import", "better-schema-markup")
				}),
				" ",
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
					variant: "secondary",
					disabled: busy,
					isBusy: busy,
					onClick: () => {
						runImport(false);
					},
					children: (0, _wordpress_i18n.__)("Import settings", "better-schema-markup")
				})
			] }),
			details.length ? /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("details", {
				open: true,
				children: [/* @__PURE__ */ (0, react_jsx_runtime.jsx)("summary", { children: (0, _wordpress_i18n.__)("Import details", "better-schema-markup") }), /* @__PURE__ */ (0, react_jsx_runtime.jsx)("ul", { children: details.map((detail, index) => /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("li", { children: [/* @__PURE__ */ (0, react_jsx_runtime.jsxs)("strong", { children: [
					(0, _wordpress_i18n.__)("Post", "better-schema-markup"),
					" ",
					detail.post,
					": "
				] }), detail.message] }, index)) })]
			}) : null
		] });
	}
	//#endregion
	//#region src/settings-screen.tsx
	function errorText(error) {
		if (typeof error === "object" && error !== null && "message" in error && typeof error.message === "string") return error.message;
		return (0, _wordpress_i18n.__)("Something went wrong. Please try again.", "better-schema-markup");
	}
	function LogoPicker({ id, url, onChange }) {
		function openLibrary() {
			const media = window.wp?.media;
			if (!media) return;
			const frame = media({
				title: (0, _wordpress_i18n.__)("Choose a logo", "better-schema-markup"),
				button: { text: (0, _wordpress_i18n.__)("Use this image", "better-schema-markup") },
				multiple: false,
				library: { type: "image" }
			});
			frame.on("select", () => {
				const selected = frame.state().get("selection").first()?.toJSON();
				if (typeof selected === "object" && selected !== null && "id" in selected && Number.isSafeInteger(selected.id) && "url" in selected && typeof selected.url === "string") onChange(selected.id, selected.url);
			});
			frame.open();
		}
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("div", {
			style: { marginBottom: "24px" },
			children: [
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: /* @__PURE__ */ (0, react_jsx_runtime.jsx)("strong", { children: (0, _wordpress_i18n.__)("Logo", "better-schema-markup") }) }),
				id > 0 && url ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)("img", {
					src: url,
					alt: (0, _wordpress_i18n.__)("Selected logo", "better-schema-markup"),
					style: {
						display: "block",
						maxWidth: "180px",
						maxHeight: "120px",
						marginBottom: "12px"
					}
				}) : null,
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
					variant: "secondary",
					onClick: openLibrary,
					children: id > 0 ? (0, _wordpress_i18n.__)("Change logo", "better-schema-markup") : (0, _wordpress_i18n.__)("Choose logo", "better-schema-markup")
				}),
				id > 0 ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
					variant: "tertiary",
					onClick: () => onChange(0, ""),
					children: (0, _wordpress_i18n.__)("Remove", "better-schema-markup")
				}) : null,
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", {
					className: "description",
					children: (0, _wordpress_i18n.__)("Leave empty to use the site icon.", "better-schema-markup")
				})
			]
		});
	}
	function SettingsScreen({ config }) {
		const [settings, setSettings] = (0, react.useState)(null);
		const [identity, setIdentity] = (0, react.useState)(null);
		const [defaults, setDefaults] = (0, react.useState)({});
		const [business, setBusiness] = (0, react.useState)(EMPTY_BUSINESS);
		const [logoUrl, setLogoUrl] = (0, react.useState)(config.logoUrl);
		const [frontendUrl, setFrontendUrl] = (0, react.useState)("");
		const [socialText, setSocialText] = (0, react.useState)("");
		const [showSetup, setShowSetup] = (0, react.useState)(config.showSetup);
		const [saving, setSaving] = (0, react.useState)(false);
		const [error, setError] = (0, react.useState)("");
		const [success, setSuccess] = (0, react.useState)("");
		(0, react.useEffect)(() => {
			let active = true;
			schemaApiFetch({ path: "/wp/v2/settings" }).then((response) => {
				if (!active) return;
				const parsed = readPluginSettings(response, config);
				if (!parsed) throw new Error((0, _wordpress_i18n.__)("Could not read schema settings.", "better-schema-markup"));
				setSettings(parsed);
				setFrontendUrl(parsed.frontendUrl);
				setIdentity(parsed.identity);
				setDefaults(parsed.defaults);
				setSocialText(parsed.identity.same_as.join("\n"));
				setBusiness(readBusinessSettings(response));
			}).catch((cause) => {
				if (active) setError(errorText(cause));
			});
			return () => {
				active = false;
			};
		}, [config]);
		async function save(setupOnly = false) {
			if (!identity) return;
			const profiles = parseSocialProfiles(socialText);
			if (!setupOnly && invalidSocialProfiles(profiles).length > 0) {
				setError((0, _wordpress_i18n.__)("Social profiles must be full http or https URLs, one per line.", "better-schema-markup"));
				return;
			}
			if (!setupOnly && !isFrontendUrl(frontendUrl)) {
				setError((0, _wordpress_i18n.__)("Enter a full http or https frontend URL without credentials, a query, or a fragment.", "better-schema-markup"));
				return;
			}
			if (!setupOnly && (!isCoordinate(business.geo.lat, 90) || !isCoordinate(business.geo.lng, 180))) {
				setError((0, _wordpress_i18n.__)("Latitude must be between -90 and 90, and longitude between -180 and 180.", "better-schema-markup"));
				return;
			}
			setSaving(true);
			setError("");
			setSuccess("");
			try {
				const response = await schemaApiFetch({
					path: "/wp/v2/settings",
					method: "POST",
					data: {
						better_schema_identity: {
							...identity,
							same_as: setupOnly ? identity.same_as : profiles
						},
						...setupOnly ? {} : { better_schema_business: business },
						better_schema_frontend_url: frontendUrl.trim(),
						better_schema_type_defaults: defaults,
						better_schema_setup_complete: true
					}
				});
				const parsed = readPluginSettings(response, config);
				if (!parsed) throw new Error((0, _wordpress_i18n.__)("The saved settings could not be read.", "better-schema-markup"));
				setSettings(parsed);
				setFrontendUrl(parsed.frontendUrl);
				setIdentity(parsed.identity);
				setDefaults(parsed.defaults);
				setSocialText(parsed.identity.same_as.join("\n"));
				setBusiness(readBusinessSettings(response));
				setShowSetup(false);
				setSuccess((0, _wordpress_i18n.__)("Settings saved.", "better-schema-markup"));
			} catch (cause) {
				setError(errorText(cause));
			} finally {
				setSaving(false);
			}
		}
		async function skipSetup() {
			setSaving(true);
			setError("");
			try {
				await schemaApiFetch({
					path: "/wp/v2/settings",
					method: "POST",
					data: { better_schema_setup_complete: true }
				});
				setShowSetup(false);
			} catch (cause) {
				setError(errorText(cause));
			} finally {
				setSaving(false);
			}
		}
		if (!settings || !identity) return error ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
			status: "error",
			isDismissible: false,
			children: error
		}) : /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Spinner, {});
		return /* @__PURE__ */ (0, react_jsx_runtime.jsxs)("div", {
			style: { maxWidth: "680px" },
			children: [
				showSetup ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("Answer three questions to set up your site identity. You can change these settings later.", "better-schema-markup") }) : /* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("Set who publishes this site and choose the schema used for posts without an individual choice.", "better-schema-markup") }),
				error ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
					status: "error",
					isDismissible: false,
					children: error
				}) : null,
				success ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Notice, {
					status: "success",
					onRemove: () => setSuccess(""),
					children: success
				}) : null,
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h2", { children: (0, _wordpress_i18n.__)("Site identity", "better-schema-markup") }),
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
					label: (0, _wordpress_i18n.__)("Who publishes this site?", "better-schema-markup"),
					value: identity.type,
					options: [{
						label: (0, _wordpress_i18n.__)("Organization", "better-schema-markup"),
						value: "Organization"
					}, {
						label: (0, _wordpress_i18n.__)("Person", "better-schema-markup"),
						value: "Person"
					}],
					onChange: (type) => {
						if (type === "Organization" || type === "Person") setIdentity({
							...identity,
							type
						});
					},
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true
				}),
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
					label: (0, _wordpress_i18n.__)("Name", "better-schema-markup"),
					help: (0, _wordpress_i18n.__)("Leave empty to use the site title.", "better-schema-markup"),
					placeholder: config.siteName,
					value: identity.name,
					onChange: (name) => setIdentity({
						...identity,
						name
					}),
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true
				}),
				/* @__PURE__ */ (0, react_jsx_runtime.jsx)(LogoPicker, {
					id: identity.logo,
					url: logoUrl,
					onChange: (logo, url) => {
						setIdentity({
							...identity,
							logo
						});
						setLogoUrl(url);
					}
				}),
				!showSetup ? /* @__PURE__ */ (0, react_jsx_runtime.jsxs)(react_jsx_runtime.Fragment, { children: [
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextControl, {
						label: (0, _wordpress_i18n.__)("Frontend URL", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("For headless sites, enter the public site URL. Local schema URLs, including media, use this base. Leave empty to use WordPress URLs.", "better-schema-markup"),
						value: frontendUrl,
						onChange: setFrontendUrl,
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.TextareaControl, {
						label: (0, _wordpress_i18n.__)("Social profiles", "better-schema-markup"),
						help: (0, _wordpress_i18n.__)("Enter one full URL per line.", "better-schema-markup"),
						value: socialText,
						onChange: setSocialText,
						__nextHasNoMarginBottom: true
					}),
					identity.type === "Organization" ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(BusinessFields, {
						value: business,
						onChange: setBusiness
					}) : null,
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)(SeoSettings, {}),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)("h2", { children: (0, _wordpress_i18n.__)("Default schema by post type", "better-schema-markup") }),
					/* @__PURE__ */ (0, react_jsx_runtime.jsx)("p", { children: (0, _wordpress_i18n.__)("An individual post’s schema choice takes precedence over its post type default.", "better-schema-markup") }),
					config.postTypes.map((postType) => /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.SelectControl, {
						label: postType.label,
						value: defaults[postType.name] ?? (postType.name === "post" ? "Article" : "WebPage"),
						options: SCHEMA_TYPES.map(({ label, value }) => ({
							label,
							value
						})),
						onChange: (value) => {
							if (isSchemaType(value)) setDefaults({
								...defaults,
								[postType.name]: value
							});
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true
					}, postType.name))
				] }) : null,
				/* @__PURE__ */ (0, react_jsx_runtime.jsxs)("p", {
					style: { marginTop: "24px" },
					children: [/* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
						variant: "primary",
						isBusy: saving,
						disabled: saving,
						onClick: () => {
							save(showSetup);
						},
						children: showSetup ? (0, _wordpress_i18n.__)("Finish setup", "better-schema-markup") : (0, _wordpress_i18n.__)("Save settings", "better-schema-markup")
					}), showSetup ? /* @__PURE__ */ (0, react_jsx_runtime.jsx)(_wordpress_components.Button, {
						variant: "tertiary",
						disabled: saving,
						onClick: () => {
							skipSetup();
						},
						children: (0, _wordpress_i18n.__)("Skip setup", "better-schema-markup")
					}) : null]
				})
			]
		});
	}
	//#endregion
	//#region src/index.tsx
	var settingsRoot = document.getElementById("better-schema-settings");
	if (settingsRoot) {
		const config = readSettingsConfig(window.betterSchemaSettings);
		if (config) (0, _wordpress_element.createRoot)(settingsRoot).render(/* @__PURE__ */ (0, react_jsx_runtime.jsx)(SettingsScreen, { config }));
	} else (0, _wordpress_plugins.registerPlugin)("better-schema", { render: SchemaPanel });
	//#endregion
})(wp.plugins, wp.element, wp.components, wp.coreData, wp.data, wp.editor, wp.i18n, React, ReactJSXRuntime, wp.apiFetch, wp.url);
