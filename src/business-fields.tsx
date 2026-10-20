import { Button, CheckboxControl, TextControl, ToggleControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import { WEEKDAYS } from "./settings-data";
import type { BusinessSettings, OpeningHours } from "./settings-data";

interface BusinessFieldsProps {
  readonly value: BusinessSettings;
  readonly onChange: (value: BusinessSettings) => void;
}

const WEEKDAY_LABELS = {
  Monday: __("Monday", "better-schema-markup"),
  Tuesday: __("Tuesday", "better-schema-markup"),
  Wednesday: __("Wednesday", "better-schema-markup"),
  Thursday: __("Thursday", "better-schema-markup"),
  Friday: __("Friday", "better-schema-markup"),
  Saturday: __("Saturday", "better-schema-markup"),
  Sunday: __("Sunday", "better-schema-markup"),
};

const NEW_HOURS: OpeningHours = { days: ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"], opens: "09:00", closes: "17:00" };

export function BusinessFields({ value, onChange }: BusinessFieldsProps) {
  function updateHours(index: number, row: OpeningHours) {
    onChange({ ...value, hours: value.hours.map((existing, position) => (position === index ? row : existing)) });
  }

  return (
    <>
      <h2>{__("Local business", "better-schema-markup")}</h2>
      <ToggleControl
        label={__("This is a local business with a physical location", "better-schema-markup")}
        help={__("Adds an address, opening hours and map location to your organization. It does not apply to a person.", "better-schema-markup")}
        checked={value.enabled}
        onChange={(enabled) => onChange({ ...value, enabled })}
        __nextHasNoMarginBottom
      />
      {value.enabled ? (
        <div style={{ marginTop: "16px" }}>
          <TextControl
            label={__("Phone number", "better-schema-markup")}
            value={value.telephone}
            onChange={(telephone) => onChange({ ...value, telephone })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Price range", "better-schema-markup")}
            help={__("For example $$ or “100–300 SEK”.", "better-schema-markup")}
            value={value.price_range}
            onChange={(price_range) => onChange({ ...value, price_range })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Street address", "better-schema-markup")}
            value={value.address.street}
            onChange={(street) => onChange({ ...value, address: { ...value.address, street } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("City", "better-schema-markup")}
            value={value.address.locality}
            onChange={(locality) => onChange({ ...value, address: { ...value.address, locality } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Region or state", "better-schema-markup")}
            value={value.address.region}
            onChange={(region) => onChange({ ...value, address: { ...value.address, region } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Postal code", "better-schema-markup")}
            value={value.address.postal_code}
            onChange={(postal_code) => onChange({ ...value, address: { ...value.address, postal_code } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Country", "better-schema-markup")}
            help={__("A two-letter country code, for example SE.", "better-schema-markup")}
            value={value.address.country}
            onChange={(country) => onChange({ ...value, address: { ...value.address, country } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Latitude", "better-schema-markup")}
            help={__("Between -90 and 90. Set both latitude and longitude, or neither.", "better-schema-markup")}
            value={value.geo.lat}
            onChange={(lat) => onChange({ ...value, geo: { ...value.geo, lat } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Longitude", "better-schema-markup")}
            help={__("Between -180 and 180.", "better-schema-markup")}
            value={value.geo.lng}
            onChange={(lng) => onChange({ ...value, geo: { ...value.geo, lng } })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />

          <h3>{__("Opening hours", "better-schema-markup")}</h3>
          {value.hours.map((row, index) => (
            // Rows have no id, so the position is the identity; removing one re-keys the rest.
            <fieldset key={index} style={{ marginBottom: "16px" }}>
              {WEEKDAYS.map((day) => (
                <CheckboxControl
                  key={day}
                  label={WEEKDAY_LABELS[day]}
                  checked={row.days.includes(day)}
                  onChange={(checked) =>
                    updateHours(index, {
                      ...row,
                      days: WEEKDAYS.filter((weekday) => (weekday === day ? checked : row.days.includes(weekday))),
                    })
                  }
                  __nextHasNoMarginBottom
                />
              ))}
              <TextControl
                type="time"
                label={__("Opens", "better-schema-markup")}
                value={row.opens}
                onChange={(opens) => updateHours(index, { ...row, opens })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <TextControl
                type="time"
                label={__("Closes", "better-schema-markup")}
                value={row.closes}
                onChange={(closes) => updateHours(index, { ...row, closes })}
                __next40pxDefaultSize
                __nextHasNoMarginBottom
              />
              <Button variant="link" isDestructive onClick={() => onChange({ ...value, hours: value.hours.filter((_, position) => position !== index) })}>
                {__("Remove these hours", "better-schema-markup")}
              </Button>
            </fieldset>
          ))}
          <Button variant="secondary" onClick={() => onChange({ ...value, hours: [...value.hours, NEW_HOURS] })}>
            {__("Add opening hours", "better-schema-markup")}
          </Button>
          <p className="description">{__("Hours without a day, or without both times, are not saved.", "better-schema-markup")}</p>
        </div>
      ) : null}
    </>
  );
}
