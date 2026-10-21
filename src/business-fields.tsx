import { __ } from "@wordpress/i18n";
import { useId } from "react";

import { Button } from "./components/ui/button";
import { Checkbox } from "./components/ui/checkbox";
import { FieldDescription, FieldGroup, FieldRow, FieldSet } from "./components/ui/field";
import { HelpPopover } from "./components/ui/help-popover";
import { WEEKDAYS } from "./settings-data";
import type { BusinessSettings, OpeningHours, Weekday } from "./settings-data";
import { SwitchField, TextField } from "./settings-fields";

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

interface DayCheckboxProps {
  readonly label: string;
  readonly checked: boolean;
  readonly onChange: (checked: boolean) => void;
}

function DayCheckbox({ label, checked, onChange }: DayCheckboxProps) {
  const id = useId();
  return (
    <div className="bsm:flex bsm:items-center bsm:gap-2">
      <Checkbox id={id} checked={checked} onChange={onChange} />
      <label htmlFor={id} className="bsm:cursor-pointer bsm:text-sm bsm:text-foreground">
        {label}
      </label>
    </div>
  );
}

export function BusinessFields({ value, onChange }: BusinessFieldsProps) {
  function updateHours(index: number, row: OpeningHours) {
    onChange({ ...value, hours: value.hours.map((existing, position) => (position === index ? row : existing)) });
  }

  function toggleDay(row: OpeningHours, day: Weekday, checked: boolean): OpeningHours {
    return { ...row, days: WEEKDAYS.filter((weekday) => (weekday === day ? checked : row.days.includes(weekday))) };
  }

  return (
    <FieldSet
      title={
        <>
          {__("Local business", "better-schema-markup")}
          <HelpPopover topic={__("Local business", "better-schema-markup")}>
            {__("Turn this on if customers can visit you, such as a shop, clinic or restaurant. It adds an address, opening hours and map location to your organization so Google can show them. It does not apply to a person.", "better-schema-markup")}
          </HelpPopover>
        </>
      }
      description={__("For businesses with a physical location that customers can visit.", "better-schema-markup")}
    >
      <FieldGroup>
        <SwitchField
          label={__("This is a local business with a physical location", "better-schema-markup")}
          help={__("Adds an address, opening hours and map location to your organization.", "better-schema-markup")}
          checked={value.enabled}
          onChange={(enabled) => onChange({ ...value, enabled })}
        />
        {value.enabled ? (
          <>
            <FieldRow>
              <TextField
                label={__("Phone number", "better-schema-markup")}
                type="tel"
                value={value.telephone}
                onChange={(telephone) => onChange({ ...value, telephone })}
              />
              <TextField
                label={__("Price range", "better-schema-markup")}
                help={__("For example $$ or “100–300 SEK”.", "better-schema-markup")}
                value={value.price_range}
                onChange={(price_range) => onChange({ ...value, price_range })}
              />
            </FieldRow>
            <TextField
              label={__("Street address", "better-schema-markup")}
              value={value.address.street}
              onChange={(street) => onChange({ ...value, address: { ...value.address, street } })}
            />
            <FieldRow>
              <TextField
                label={__("City", "better-schema-markup")}
                value={value.address.locality}
                onChange={(locality) => onChange({ ...value, address: { ...value.address, locality } })}
              />
              <TextField
                label={__("Region or state", "better-schema-markup")}
                value={value.address.region}
                onChange={(region) => onChange({ ...value, address: { ...value.address, region } })}
              />
            </FieldRow>
            <FieldRow>
              <TextField
                label={__("Postal code", "better-schema-markup")}
                value={value.address.postal_code}
                onChange={(postal_code) => onChange({ ...value, address: { ...value.address, postal_code } })}
              />
              <TextField
                label={__("Country", "better-schema-markup")}
                help={__("A two-letter country code, for example SE.", "better-schema-markup")}
                value={value.address.country}
                onChange={(country) => onChange({ ...value, address: { ...value.address, country } })}
              />
            </FieldRow>
            <FieldRow>
              <TextField
                label={__("Latitude", "better-schema-markup")}
                help={__("Between -90 and 90. Set both latitude and longitude, or neither.", "better-schema-markup")}
                inputMode="decimal"
                value={value.geo.lat}
                onChange={(lat) => onChange({ ...value, geo: { ...value.geo, lat } })}
              />
              <TextField
                label={__("Longitude", "better-schema-markup")}
                help={__("Between -180 and 180.", "better-schema-markup")}
                inputMode="decimal"
                value={value.geo.lng}
                onChange={(lng) => onChange({ ...value, geo: { ...value.geo, lng } })}
              />
            </FieldRow>

            <div className="bsm:grid bsm:gap-3">
              <div className="bsm:grid bsm:gap-1">
                <h3 className="bsm:m-0 bsm:p-0 bsm:text-sm bsm:font-semibold bsm:text-foreground">{__("Opening hours", "better-schema-markup")}</h3>
                <FieldDescription>{__("Hours without a day, or without both times, are not saved.", "better-schema-markup")}</FieldDescription>
              </div>
              {value.hours.map((row, index) => (
                // Rows have no id, so the position is the identity; removing one re-keys the rest.
                <fieldset key={index} className="bsm:m-0 bsm:grid bsm:min-w-0 bsm:gap-4 bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border bsm:p-4">
                  <legend className="bsm:sr-only">{__("Opening hours", "better-schema-markup")}</legend>
                  <div className="bsm:flex bsm:flex-wrap bsm:gap-x-4 bsm:gap-y-2">
                    {WEEKDAYS.map((day) => (
                      <DayCheckbox
                        key={day}
                        label={WEEKDAY_LABELS[day]}
                        checked={row.days.includes(day)}
                        onChange={(checked) => updateHours(index, toggleDay(row, day, checked))}
                      />
                    ))}
                  </div>
                  <FieldRow>
                    <TextField type="time" label={__("Opens", "better-schema-markup")} value={row.opens} onChange={(opens) => updateHours(index, { ...row, opens })} />
                    <TextField type="time" label={__("Closes", "better-schema-markup")} value={row.closes} onChange={(closes) => updateHours(index, { ...row, closes })} />
                  </FieldRow>
                  <div>
                    <Button variant="destructive" size="sm" onClick={() => onChange({ ...value, hours: value.hours.filter((_, position) => position !== index) })}>
                      {__("Remove these hours", "better-schema-markup")}
                    </Button>
                  </div>
                </fieldset>
              ))}
              <div>
                <Button variant="outline" size="sm" onClick={() => onChange({ ...value, hours: [...value.hours, NEW_HOURS] })}>
                  {__("Add opening hours", "better-schema-markup")}
                </Button>
              </div>
            </div>
          </>
        ) : null}
      </FieldGroup>
    </FieldSet>
  );
}
