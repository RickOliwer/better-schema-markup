import { __ } from "@wordpress/i18n";

import { FieldRow } from "./components/ui/field";
import type { AttendanceMode, EventDetails, EventStatus } from "./post-details";
import { SelectField, TextField } from "./settings-fields";

interface EventFieldsProps {
  readonly value: EventDetails;
  readonly onChange: (value: EventDetails) => void;
}

export function EventFields({ value, onChange }: EventFieldsProps) {
  const attendanceOptions: { label: string; value: AttendanceMode }[] = [
    { label: __("In person", "better-schema-markup"), value: "offline" },
    { label: __("Online", "better-schema-markup"), value: "online" },
    { label: __("Both", "better-schema-markup"), value: "mixed" },
  ];
  const statusOptions: { label: string; value: EventStatus }[] = [
    { label: __("Scheduled", "better-schema-markup"), value: "scheduled" },
    { label: __("Cancelled", "better-schema-markup"), value: "cancelled" },
    { label: __("Postponed", "better-schema-markup"), value: "postponed" },
    { label: __("Rescheduled", "better-schema-markup"), value: "rescheduled" },
    { label: __("Moved online", "better-schema-markup"), value: "moved_online" },
  ];

  return (
    <>
      <FieldRow>
        <TextField
          type="datetime-local"
          label={__("Starts", "better-schema-markup")}
          help={__("Google requires a start date for events.", "better-schema-markup")}
          value={value.start}
          onChange={(start) => onChange({ ...value, start })}
        />
        <TextField
          type="datetime-local"
          label={__("Ends", "better-schema-markup")}
          value={value.end}
          onChange={(end) => onChange({ ...value, end })}
        />
      </FieldRow>
      <SelectField
        label={__("Where does it happen?", "better-schema-markup")}
        value={value.attendance}
        options={attendanceOptions}
        onChange={(attendance) => onChange({ ...value, attendance })}
      />
      {value.attendance !== "online" ? (
        <FieldRow>
          <TextField
            label={__("Venue name", "better-schema-markup")}
            value={value.location_name}
            onChange={(location_name) => onChange({ ...value, location_name })}
          />
          <TextField
            label={__("Venue address", "better-schema-markup")}
            value={value.location_address}
            onChange={(location_address) => onChange({ ...value, location_address })}
          />
        </FieldRow>
      ) : null}
      <SelectField
        label={__("Status", "better-schema-markup")}
        value={value.status}
        options={statusOptions}
        onChange={(status) => onChange({ ...value, status })}
      />
      <TextField
        type="url"
        label={__("Ticket link", "better-schema-markup")}
        help={__("Optional. A full http or https URL.", "better-schema-markup")}
        placeholder="https://"
        value={value.offers_url}
        onChange={(offers_url) => onChange({ ...value, offers_url })}
      />
    </>
  );
}
