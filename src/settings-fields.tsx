import { useId } from "react";
import type { InputHTMLAttributes, ReactNode, TextareaHTMLAttributes } from "react";

import { Field, FieldDescription, FieldError, FieldLabel } from "./components/ui/field";
import { HelpPopover } from "./components/ui/help-popover";
import { Input, Textarea } from "./components/ui/input";
import { Select } from "./components/ui/select";
import type { SelectOption } from "./components/ui/select";
import { Switch } from "./components/ui/switch";

/*
 * Settings page fields: a label (with an optional "?" help popover beside it,
 * outside the label), the control, then help text or an error, all linked by id.
 */

interface FieldTextProps {
  readonly label: string;
  /** Longer explanation, shown in a popover from a "?" button next to the label. */
  readonly tip?: ReactNode;
  /** Short help shown under the control. */
  readonly help?: ReactNode;
  /** Shown instead of the help, in red, and marks the control invalid. */
  readonly error?: ReactNode;
}

function useFieldIds(hasMessage: boolean) {
  const id = useId();
  return { id, messageId: hasMessage ? `${id}-message` : undefined };
}

function FieldHeader({
  id,
  label,
  tip,
}: {
  readonly id: string;
  readonly label: string;
  readonly tip?: ReactNode;
}) {
  return (
    <div className="bsm:flex bsm:min-h-6 bsm:items-center bsm:gap-1">
      <FieldLabel htmlFor={id}>{label}</FieldLabel>
      {tip ? <HelpPopover topic={label}>{tip}</HelpPopover> : null}
    </div>
  );
}

function FieldMessage({
  id,
  help,
  error,
}: {
  readonly id?: string;
  readonly help?: ReactNode;
  readonly error?: ReactNode;
}) {
  if (error) return <FieldError id={id}>{error}</FieldError>;
  return help ? <FieldDescription id={id}>{help}</FieldDescription> : null;
}

type TextFieldProps = FieldTextProps &
  Omit<InputHTMLAttributes<HTMLInputElement>, "id" | "onChange" | "value"> & {
    readonly value: string;
    readonly onChange: (value: string) => void;
  };

export function TextField({
  label,
  tip,
  help,
  error,
  value,
  onChange,
  ...inputProps
}: TextFieldProps) {
  const { id, messageId } = useFieldIds(Boolean(help || error));
  return (
    <Field>
      <FieldHeader id={id} label={label} tip={tip} />
      <Input
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-describedby={messageId}
        aria-invalid={error ? true : undefined}
        {...inputProps}
      />
      <FieldMessage id={messageId} help={help} error={error} />
    </Field>
  );
}

type TextareaFieldProps = FieldTextProps &
  Omit<TextareaHTMLAttributes<HTMLTextAreaElement>, "id" | "onChange" | "value"> & {
    readonly value: string;
    readonly onChange: (value: string) => void;
  };

export function TextareaField({
  label,
  tip,
  help,
  error,
  value,
  onChange,
  ...textareaProps
}: TextareaFieldProps) {
  const { id, messageId } = useFieldIds(Boolean(help || error));
  return (
    <Field>
      <FieldHeader id={id} label={label} tip={tip} />
      <Textarea
        id={id}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        aria-describedby={messageId}
        aria-invalid={error ? true : undefined}
        {...textareaProps}
      />
      <FieldMessage id={messageId} help={help} error={error} />
    </Field>
  );
}

interface SelectFieldProps<Value extends string> extends FieldTextProps {
  readonly value: Value;
  readonly options: readonly SelectOption<Value>[];
  readonly onChange: (value: Value) => void;
  readonly disabled?: boolean;
}

export function SelectField<Value extends string>({
  label,
  tip,
  help,
  error,
  value,
  options,
  onChange,
  disabled,
}: SelectFieldProps<Value>) {
  const { id, messageId } = useFieldIds(Boolean(help || error));
  return (
    <Field>
      <FieldHeader id={id} label={label} tip={tip} />
      <Select
        id={id}
        value={value}
        options={options}
        onChange={onChange}
        disabled={disabled}
        aria-describedby={messageId}
      />
      <FieldMessage id={messageId} help={help} error={error} />
    </Field>
  );
}

interface SwitchFieldProps {
  readonly label: string;
  readonly help?: ReactNode;
  readonly checked: boolean;
  readonly onChange: (checked: boolean) => void;
  readonly disabled?: boolean;
}

/** A switch with its label beside it, as a bordered row. */
export function SwitchField({ label, help, checked, onChange, disabled }: SwitchFieldProps) {
  const { id, messageId } = useFieldIds(Boolean(help));
  return (
    <div className="bsm:flex bsm:items-start bsm:justify-between bsm:gap-4 bsm:rounded-lg bsm:border bsm:border-solid bsm:border-border bsm:p-4">
      <div className="bsm:grid bsm:gap-1">
        <FieldLabel htmlFor={id}>{label}</FieldLabel>
        {help ? <FieldDescription id={messageId}>{help}</FieldDescription> : null}
      </div>
      <Switch
        id={id}
        checked={checked}
        onChange={onChange}
        disabled={disabled}
        aria-describedby={messageId}
      />
    </div>
  );
}
