import type { ReactNode } from "react";

/*
 * Spacing and type for every Better Schema form, in the spirit of shadcn/ui: a
 * label above each control, small muted help below it, a steady gap between
 * fields, and sections separated by a line. WordPress components remove their
 * own bottom margin and style labels as small uppercase text, so this restores
 * a layout that shows which label and help text belong to which control.
 *
 * Label rules use `!important` because the components style their labels
 * through generated class names that cannot be targeted any other way.
 */
const FORM_CSS = `
.bsm-form { display: grid; gap: 32px; color: #1d2327; }
.bsm-section { display: grid; gap: 20px; align-content: start; min-width: 0; }
.bsm-section + .bsm-section { border-top: 1px solid #dcdcde; padding-top: 32px; }
.bsm-section__title { margin: 0; font-size: 16px; font-weight: 600; line-height: 1.4; color: #1d2327; }
.bsm-section__description { margin: 4px 0 0; font-size: 13px; line-height: 1.5; color: #646970; }
.bsm-fields { display: grid; gap: 20px; min-width: 0; }
.bsm-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
.bsm-form--compact .bsm-fields, .bsm-fields--compact { gap: 16px; }
.bsm-form--compact .bsm-row, .bsm-row--stack { grid-template-columns: minmax(0, 1fr); }
.bsm-form label:not(.components-checkbox-control__label):not(.components-toggle-control__label),
.bsm-form .bsm-label {
  display: block !important;
  margin: 0 0 8px !important;
  padding: 0 !important;
  font-size: 13px !important;
  font-weight: 500 !important;
  line-height: 1.4 !important;
  letter-spacing: 0 !important;
  text-transform: none !important;
  color: #1d2327 !important;
}
.bsm-form .components-base-control__help,
.bsm-form .components-toggle-control__help,
.bsm-form .components-checkbox-control__help,
.bsm-form .bsm-help {
  margin: 6px 0 0;
  font-size: 13px;
  line-height: 1.5;
  font-style: normal;
  color: #646970;
}
.bsm-form p.bsm-help { margin: 6px 0 0; }
.bsm-form fieldset { margin: 0; padding: 0; border: 0; display: grid; gap: 12px; }
.bsm-form h3 { margin: 0; font-size: 14px; font-weight: 600; }
`;

/** Loads the form styles. Render once at the top of each form. */
export function FormStyles() {
  return <style>{FORM_CSS}</style>;
}

interface FormProps {
  readonly compact?: boolean;
  readonly children: ReactNode;
}

/** The outer wrapper that scopes the form styles. `compact` is for the narrow editor sidebar. */
export function Form({ compact = false, children }: FormProps) {
  return (
    <div className={compact ? "bsm-form bsm-form--compact" : "bsm-form"}>
      <FormStyles />
      {children}
    </div>
  );
}

interface FormSectionProps {
  readonly title: ReactNode;
  readonly description?: ReactNode;
  readonly children: ReactNode;
}

/** A titled group of fields with an optional one-line description. */
export function FormSection({ title, description, children }: FormSectionProps) {
  return (
    <section className="bsm-section">
      <div>
        <h2 className="bsm-section__title">{title}</h2>
        {description ? <p className="bsm-section__description">{description}</p> : null}
      </div>
      <div className="bsm-fields">{children}</div>
    </section>
  );
}

/** Stacks fields with an even gap. Use where there is no section, such as inside a panel. */
export function Fields({ children }: { readonly children: ReactNode }) {
  return <div className="bsm-fields">{children}</div>;
}

/** Puts two short related fields, such as city and postal code, side by side. */
export function FieldRow({ children }: { readonly children: ReactNode }) {
  return <div className="bsm-row">{children}</div>;
}
