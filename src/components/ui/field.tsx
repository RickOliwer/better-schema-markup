import type { ReactNode } from "react";

import { cn } from "./utils";

interface ChildrenProps {
  readonly className?: string;
  readonly children: ReactNode;
}

/**
 * Form layout parts, modelled on shadcn/ui's Field. Each control is linked to
 * its label with `htmlFor`/`id`, and to its description or error with
 * `aria-describedby`, by the caller.
 */
export function Field({ className, children }: ChildrenProps) {
  return <div className={cn("bsm:grid bsm:content-start bsm:gap-2", className)}>{children}</div>;
}

export function FieldLabel({ htmlFor, className, children }: ChildrenProps & { readonly htmlFor?: string }) {
  return (
    <label htmlFor={htmlFor} className={cn("bsm:flex bsm:items-center bsm:gap-1 bsm:text-sm bsm:leading-snug bsm:font-medium bsm:text-foreground", className)}>
      {children}
    </label>
  );
}

export function FieldDescription({ id, className, children }: ChildrenProps & { readonly id?: string }) {
  return (
    <p id={id} className={cn("bsm:m-0 bsm:text-sm bsm:leading-normal bsm:text-muted-foreground", className)}>
      {children}
    </p>
  );
}

export function FieldError({ id, children }: { readonly id?: string; readonly children: ReactNode }) {
  return (
    <p id={id} role="alert" className="bsm:m-0 bsm:text-sm bsm:leading-normal bsm:text-destructive">
      {children}
    </p>
  );
}

/** Stacks fields with an even gap. */
export function FieldGroup({ className, children }: ChildrenProps) {
  return <div className={cn("bsm:grid bsm:gap-6", className)}>{children}</div>;
}

/** Puts two short related fields side by side on wider screens. */
export function FieldRow({ children }: { readonly children: ReactNode }) {
  return <div className="bsm:grid bsm:gap-6 bsm:sm:grid-cols-2">{children}</div>;
}

interface FieldSetProps {
  readonly title: ReactNode;
  readonly description?: ReactNode;
  readonly children: ReactNode;
}

/** A titled section of a form. */
export function FieldSet({ title, description, children }: FieldSetProps) {
  return (
    <section className="bsm:grid bsm:gap-6">
      <div className="bsm:grid bsm:gap-1">
        <h2 className="bsm:m-0 bsm:flex bsm:items-center bsm:gap-1 bsm:p-0 bsm:text-base bsm:leading-snug bsm:font-semibold bsm:text-foreground">{title}</h2>
        {description ? <p className="bsm:m-0 bsm:text-sm bsm:text-muted-foreground">{description}</p> : null}
      </div>
      {children}
    </section>
  );
}

export function FieldSeparator() {
  return <hr className="bsm:m-0 bsm:h-px bsm:border-0 bsm:bg-border" />;
}
