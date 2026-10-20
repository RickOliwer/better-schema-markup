import { SelectControl, TextControl } from "@wordpress/components";
import { __ } from "@wordpress/i18n";

import { isAttendanceMode, isEventStatus } from "./post-details";
import type { EventDetails } from "./post-details";

interface EventFieldsProps {
  readonly value: EventDetails;
  readonly onChange: (value: EventDetails) => void;
}

export function EventFields({ value, onChange }: EventFieldsProps) {
  return (
    <>
      <TextControl
        type="datetime-local"
        label={__("Starts", "better-schema-markup")}
        help={__("Google requires a start date for events.", "better-schema-markup")}
        value={value.start}
        onChange={(start) => onChange({ ...value, start })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        type="datetime-local"
        label={__("Ends", "better-schema-markup")}
        value={value.end}
        onChange={(end) => onChange({ ...value, end })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <SelectControl
        label={__("Where does it happen?", "better-schema-markup")}
        value={value.attendance}
        options={[
          { label: __("In person", "better-schema-markup"), value: "offline" },
          { label: __("Online", "better-schema-markup"), value: "online" },
          { label: __("Both", "better-schema-markup"), value: "mixed" },
        ]}
        onChange={(attendance) => {
          if (isAttendanceMode(attendance)) onChange({ ...value, attendance });
        }}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      {value.attendance !== "online" ? (
        <>
          <TextControl
            label={__("Venue name", "better-schema-markup")}
            value={value.location_name}
            onChange={(location_name) => onChange({ ...value, location_name })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
          <TextControl
            label={__("Venue address", "better-schema-markup")}
            value={value.location_address}
            onChange={(location_address) => onChange({ ...value, location_address })}
            __next40pxDefaultSize
            __nextHasNoMarginBottom
          />
        </>
      ) : null}
      <SelectControl
        label={__("Status", "better-schema-markup")}
        value={value.status}
        options={[
          { label: __("Scheduled", "better-schema-markup"), value: "scheduled" },
          { label: __("Cancelled", "better-schema-markup"), value: "cancelled" },
          { label: __("Postponed", "better-schema-markup"), value: "postponed" },
          { label: __("Rescheduled", "better-schema-markup"), value: "rescheduled" },
          { label: __("Moved online", "better-schema-markup"), value: "moved_online" },
        ]}
        onChange={(status) => {
          if (isEventStatus(status)) onChange({ ...value, status });
        }}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
      <TextControl
        type="url"
        label={__("Ticket link", "better-schema-markup")}
        help={__("Optional. A full http or https URL.", "better-schema-markup")}
        value={value.offers_url}
        onChange={(offers_url) => onChange({ ...value, offers_url })}
        __next40pxDefaultSize
        __nextHasNoMarginBottom
      />
    </>
  );
}
